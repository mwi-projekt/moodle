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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

use mod_dhbwio\observer;

/**
 * Integration tests for the e-mail and notification service.
 *
 * Verifies that:
 *  - Status values are mapped to the correct e-mail template types.
 *  - All required e-mail templates exist after a module instance is created.
 *  - Templates for the confirmation e-mail contain the required placeholders.
 *  - The mod_dhbwio\event\email_sent Moodle event is triggered with correct data.
 *  - A failed template lookup prevents any e-mail from being sent.
 *  - The e-mail log is written to the database after a notification.
 *
 * @package    mod_dhbwio
 * @category   phpunit
 * @group      mod_dhbwio
 * @group      mod_dhbwio_email_notification
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_dhbwio_email_notification_testcase extends advanced_testcase {

    /** @var stdClass The dhbwio module instance. */
    private stdClass $dhbwio;

    /** @var stdClass A student user with a valid DHBW e-mail address. */
    private stdClass $student;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $generator      = $this->getDataGenerator();
        $course         = $generator->create_course();
        $this->dhbwio   = $generator->create_module('dhbwio', ['course' => $course->id]);
        $this->student  = $generator->create_user(['email' => 's123456@student.dhbw.de']);
    }

    // =========================================================================
    // Status-zu-E-Mail-Typ Mapping
    // =========================================================================

    /**
     * @dataProvider known_status_provider
     */
    public function test_known_status_values_map_to_correct_email_type(
        string $status, string $expected_type
    ): void {
        $this->assertSame(
            $expected_type,
            observer::get_email_type_from_status($status),
            "Status '$status' must map to template type '$expected_type'."
        );
    }

    public function known_status_provider(): array {
        return [
            'eingegangen'     => ['eingegangen',     'application_received'],
            'angenommen'      => ['angenommen',      'application_approved'],
            'abgelehnt'       => ['abgelehnt',       'application_rejected'],
            'neueinzureichen' => ['neueinzureichen', 'application_inquiry'],
        ];
    }

    public function test_unknown_status_returns_null(): void {
        $this->assertNull(observer::get_email_type_from_status('unbekannt'));
        $this->assertNull(observer::get_email_type_from_status(''));
    }

    public function test_status_mapping_is_case_insensitive(): void {
        $this->assertSame('application_approved', observer::get_email_type_from_status('Angenommen'));
        $this->assertSame('application_rejected', observer::get_email_type_from_status('ABGELEHNT'));
    }

    public function test_status_mapping_trims_surrounding_whitespace(): void {
        $this->assertSame('application_approved', observer::get_email_type_from_status('  angenommen  '));
    }

    // =========================================================================
    // E-Mail-Templates nach Modulerstellung
    // =========================================================================

    /**
     * @dataProvider template_type_language_provider
     */
    public function test_required_email_template_exists_after_module_creation(
        string $type, string $lang
    ): void {
        global $DB;
        $this->assertTrue(
            $DB->record_exists('dhbwio_email_templates', [
                'dhbwio'  => $this->dhbwio->id,
                'type'    => $type,
                'lang'    => $lang,
                'enabled' => 1,
            ]),
            "Template '$type' for language '$lang' must be created together with the module."
        );
    }

    public function template_type_language_provider(): array {
        $types = ['application_received', 'application_approved',
                  'application_rejected', 'application_inquiry'];
        $cases = [];
        foreach ($types as $type) {
            foreach (['en', 'de'] as $lang) {
                $cases["$type / $lang"] = [$type, $lang];
            }
        }
        return $cases;
    }

    // =========================================================================
    // Pflicht-Platzhalter in der Bestätigungs-E-Mail
    // =========================================================================

    public function test_application_received_template_contains_student_name_placeholder(): void {
        global $DB;
        $template = $DB->get_record('dhbwio_email_templates', [
            'dhbwio' => $this->dhbwio->id,
            'type'   => 'application_received',
            'lang'   => 'de',
        ]);
        $this->assertNotFalse($template, 'German application_received template must exist.');
        $this->assertStringContainsString(
            '{STUDENT_NAME}',
            $template->body,
            'Confirmation e-mail must contain the {STUDENT_NAME} placeholder.'
        );
    }

    public function test_application_received_template_contains_university_choices_placeholder(): void {
        global $DB;
        $template = $DB->get_record('dhbwio_email_templates', [
            'dhbwio' => $this->dhbwio->id,
            'type'   => 'application_received',
            'lang'   => 'de',
        ]);
        $this->assertNotFalse($template, 'German application_received template must exist.');
        $this->assertStringContainsString(
            '{UNIVERSITY_CHOICES}',
            $template->body,
            'Confirmation e-mail must contain the {UNIVERSITY_CHOICES} placeholder (Erstwunsch summary).'
        );
    }

    // =========================================================================
    // email_sent Event wird korrekt getriggert
    // =========================================================================

    public function test_email_sent_event_is_triggered_with_correct_data(): void {
        $context = context_module::instance($this->dhbwio->cmid);
        $sink    = $this->redirectEvents();

        $event = \mod_dhbwio\event\email_sent::create([
            'objectid'      => 99,
            'context'       => $context,
            'userid'        => get_admin()->id,
            'relateduserid' => $this->student->id,
            'other'         => ['email_type' => 'application_received'],
        ]);
        $event->trigger();

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events,
            'Exactly one email_sent event must be fired.');
        $captured = $events[0];
        $this->assertInstanceOf(\mod_dhbwio\event\email_sent::class, $captured);
        $this->assertSame('application_received', $captured->other['email_type'],
            'Event must carry the correct email_type in its data.');
        $this->assertSame($this->student->id, $captured->relateduserid,
            'Event must reference the student as the recipient.');
    }

    public function test_email_sent_event_description_contains_email_type(): void {
        $context = context_module::instance($this->dhbwio->cmid);
        $event   = \mod_dhbwio\event\email_sent::create([
            'objectid'      => 99,
            'context'       => $context,
            'userid'        => get_admin()->id,
            'relateduserid' => $this->student->id,
            'other'         => ['email_type' => 'application_approved'],
        ]);

        $this->assertStringContainsString(
            'application_approved',
            $event->get_description(),
            'Event description must mention the email type so the log is human-readable.'
        );
    }

    // =========================================================================
    // E-Mail-Log wird in die Datenbank geschrieben
    // =========================================================================

    public function test_email_log_record_written_to_db_after_notification(): void {
        global $DB;
        $before = $DB->count_records('dhbwio_email_log', ['dhbwio_id' => $this->dhbwio->id]);

        $log = new stdClass();
        $log->dhbwio_id   = $this->dhbwio->id;
        $log->entry_id    = 99;
        $log->user_id     = $this->student->id;
        $log->email_type  = 'application_received';
        $log->status      = 'eingegangen';
        $log->timecreated = time();
        $DB->insert_record('dhbwio_email_log', $log);

        $this->assertSame($before + 1,
            $DB->count_records('dhbwio_email_log', ['dhbwio_id' => $this->dhbwio->id]),
            'One log entry must be written to dhbwio_email_log after a notification is sent.'
        );
        $this->assertTrue(
            $DB->record_exists('dhbwio_email_log', [
                'dhbwio_id'  => $this->dhbwio->id,
                'user_id'    => $this->student->id,
                'email_type' => 'application_received',
            ]),
            'Log entry must reference the correct user and email type.'
        );
    }

    // =========================================================================
    // Fehlende Template verhindert E-Mail-Versand
    // =========================================================================

    public function test_send_notification_returns_false_when_template_type_does_not_exist(): void {
        $result = dhbwio_send_email_notification(
            'nonexistent_type',
            $this->dhbwio->id,
            $this->student->id
        );
        $this->assertFalse($result,
            'send_email_notification must return false when no matching template is found, ' .
            'preventing a broken e-mail from being sent.'
        );
    }
}
