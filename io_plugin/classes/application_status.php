<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Application status model and transition logic.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dhbwio;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the status constants and allowed transitions for exchange applications.
 *
 * Roles:
 *   - ACTOR_STUDENT : the applying student
 *   - ACTOR_IO      : International Office staff (requires mod/dhbwio:reviewapplications)
 *
 * State machine (see Sprint 1 design diagram):
 *
 *   draft            --[student]--> submitted
 *   submitted        --[io]------> under_review
 *   submitted        --[student]--> withdrawn
 *   under_review     --[io]------> waitlist
 *   under_review     --[io]------> needs_supplement
 *   under_review     --[io]------> accepted
 *   under_review     --[io]------> rejected
 *   waitlist         --[io]------> accepted
 *   waitlist         --[io]------> rejected
 *   waitlist         --[io]------> withdrawn
 *   needs_supplement --[student]--> submitted       (re-submit with supplements)
 *   needs_supplement --[student]--> withdrawn
 */
class application_status {

    // Status constants
    const DRAFT            = 'draft';
    const SUBMITTED        = 'submitted';
    const UNDER_REVIEW     = 'under_review';
    const WAITLIST         = 'waitlist';
    const NEEDS_SUPPLEMENT = 'needs_supplement';
    const ACCEPTED         = 'accepted';
    const REJECTED         = 'rejected';
    const WITHDRAWN        = 'withdrawn';

    // Actor constants used as keys in the transition map
    const ACTOR_STUDENT = 'student';
    const ACTOR_IO      = 'io';

    /**
     * All valid statuses in display order.
     * @return string[]
     */
    public static function all(): array {
        return [
            self::DRAFT,
            self::SUBMITTED,
            self::UNDER_REVIEW,
            self::WAITLIST,
            self::NEEDS_SUPPLEMENT,
            self::ACCEPTED,
            self::REJECTED,
            self::WITHDRAWN,
        ];
    }

    /**
     * Terminal statuses — no further transitions are possible.
     * @return string[]
     */
    public static function terminal(): array {
        return [self::ACCEPTED, self::REJECTED, self::WITHDRAWN];
    }

    /**
     * Transition map: from_status => [actor => [to_status, ...]]
     * @return array
     */
    private static function transitions(): array {
        return [
            self::DRAFT => [
                self::ACTOR_STUDENT => [self::SUBMITTED],
            ],
            self::SUBMITTED => [
                self::ACTOR_IO      => [self::UNDER_REVIEW],
                self::ACTOR_STUDENT => [self::WITHDRAWN],
            ],
            self::UNDER_REVIEW => [
                self::ACTOR_IO => [
                    self::WAITLIST,
                    self::NEEDS_SUPPLEMENT,
                    self::ACCEPTED,
                    self::REJECTED,
                ],
            ],
            self::WAITLIST => [
                self::ACTOR_IO => [self::ACCEPTED, self::REJECTED, self::WITHDRAWN],
            ],
            self::NEEDS_SUPPLEMENT => [
                self::ACTOR_STUDENT => [self::SUBMITTED, self::WITHDRAWN],
            ],
        ];
    }

    /**
     * Returns the statuses reachable from $from_status for a given actor.
     *
     * @param string $from_status  Current status constant
     * @param string $actor        ACTOR_STUDENT or ACTOR_IO
     * @return string[]
     */
    public static function allowed_targets(string $from_status, string $actor): array {
        return self::transitions()[$from_status][$actor] ?? [];
    }

    /**
     * Returns true when the transition from → to is permitted for the given actor.
     *
     * @param string $from   Current status
     * @param string $to     Desired new status
     * @param string $actor  ACTOR_STUDENT or ACTOR_IO
     */
    public static function is_allowed(string $from, string $to, string $actor): bool {
        return in_array($to, self::allowed_targets($from, $actor), true);
    }

    /**
     * Determines the actor role for a user in a given module context.
     *
     * IO staff have mod/dhbwio:reviewapplications; students have mod/dhbwio:apply.
     *
     * @param \context_module $context
     * @return string  ACTOR_IO or ACTOR_STUDENT
     * @throws \moodle_exception  When the user has neither capability
     */
    public static function actor_for_context(\context_module $context): string {
        if (has_capability('mod/dhbwio:reviewapplications', $context)) {
            return self::ACTOR_IO;
        }
        if (has_capability('mod/dhbwio:apply', $context)) {
            return self::ACTOR_STUDENT;
        }
        throw new \moodle_exception('nopermissions', 'error', '', 'apply or reviewapplications');
    }

    /**
     * Performs a status transition and writes an audit note.
     *
     * @param int              $application_id
     * @param string           $to_status
     * @param string           $actor           ACTOR_STUDENT or ACTOR_IO
     * @param string           $content         Optional justification text
     * @return \stdClass       Updated application record
     * @throws \moodle_exception  On invalid transition
     */
    public static function transition(int $application_id, string $to_status, string $actor, string $content = ''): \stdClass {
        global $DB, $USER;

        $application = $DB->get_record('dhbwio_applications', ['id' => $application_id], '*', MUST_EXIST);

        if (!self::is_allowed($application->status, $to_status, $actor)) {
            throw new \moodle_exception(
                'invalidstatustransition',
                'mod_dhbwio',
                '',
                ['from' => $application->status, 'to' => $to_status, 'actor' => $actor]
            );
        }

        $now = time();
        $from_status = $application->status;

        // Update the application record
        $application->status       = $to_status;
        $application->timemodified = $now;
        if ($to_status === self::SUBMITTED && $application->timesubmitted === null) {
            $application->timesubmitted = $now;
        }
        $DB->update_record('dhbwio_applications', $application);

        // Write audit note
        $note = new \stdClass();
        $note->application_id = $application_id;
        $note->authorid       = $USER->id;
        $note->type           = 'status_change';
        $note->content        = $content;
        $note->status_from    = $from_status;
        $note->status_to      = $to_status;
        $note->timecreated    = $now;
        $DB->insert_record('dhbwio_application_notes', $note);

        return $application;
    }

    /**
     * Returns the lang string key for a given status.
     */
    public static function string_key(string $status): string {
        return 'applicationstatus_' . $status;
    }
}
