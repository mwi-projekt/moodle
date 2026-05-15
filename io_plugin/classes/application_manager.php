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
 * CRUD operations for dhbwio_applications.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dhbwio;

defined('MOODLE_INTERNAL') || die();

class application_manager {

    /**
     * Creates a new application in status DRAFT for the given user.
     * If the user already has an application for this instance, returns the existing one.
     *
     * @param int $dhbwio_id  Plugin instance ID
     * @param int $userid     Student user ID
     * @return \stdClass      The (new or existing) application record
     */
    public static function create_application(int $dhbwio_id, int $userid): \stdClass {
        global $DB;

        $existing = self::get_application_for_user($dhbwio_id, $userid);
        if ($existing !== null) {
            return $existing;
        }

        $now = time();
        $application = new \stdClass();
        $application->dhbwio       = $dhbwio_id;
        $application->userid       = $userid;
        $application->status       = application_status::DRAFT;
        $application->timecreated  = $now;
        $application->timemodified = $now;

        $application->id = $DB->insert_record('dhbwio_applications', $application);
        return $application;
    }

    /**
     * Returns a single application by its ID.
     *
     * @param int $application_id
     * @return \stdClass
     * @throws \dml_exception  When not found
     */
    public static function get_application(int $application_id): \stdClass {
        global $DB;
        return $DB->get_record('dhbwio_applications', ['id' => $application_id], '*', MUST_EXIST);
    }

    /**
     * Returns the application a student has for a given plugin instance, or null if none exists.
     *
     * @param int $dhbwio_id
     * @param int $userid
     * @return \stdClass|null
     */
    public static function get_application_for_user(int $dhbwio_id, int $userid): ?\stdClass {
        global $DB;
        $record = $DB->get_record('dhbwio_applications', [
            'dhbwio' => $dhbwio_id,
            'userid' => $userid,
        ]);
        return $record ?: null;
    }

    /**
     * Returns all applications for a plugin instance, optionally filtered by status.
     *
     * @param int         $dhbwio_id
     * @param string|null $status  One of the application_status constants, or null for all
     * @return \stdClass[]  Keyed by application ID
     */
    public static function get_all_applications(int $dhbwio_id, ?string $status = null): array {
        global $DB;

        $params = ['dhbwio' => $dhbwio_id];
        if ($status !== null) {
            $params['status'] = $status;
        }
        return $DB->get_records('dhbwio_applications', $params, 'timemodified DESC');
    }

    /**
     * Persists changes to an existing application record (e.g. updated fields from a form).
     * Always updates timemodified.
     *
     * @param \stdClass $application  Must have a valid ->id
     * @return \stdClass  The updated record
     */
    public static function save_application(\stdClass $application): \stdClass {
        global $DB;

        $application->timemodified = time();
        $DB->update_record('dhbwio_applications', $application);
        return $application;
    }

    /**
     * Returns all audit notes for an application, newest first.
     *
     * @param int $application_id
     * @return \stdClass[]
     */
    public static function get_notes(int $application_id): array {
        global $DB;
        return $DB->get_records(
            'dhbwio_application_notes',
            ['application_id' => $application_id],
            'timecreated DESC'
        );
    }
}
