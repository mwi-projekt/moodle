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

/**
 * Integration tests for duplicate application prevention (Issue #27).
 *
 * Verifies that:
 *  - A user with no prior application passes the duplicate check.
 *  - A user with an existing application_received log entry is blocked.
 *  - The correct error key is returned so the form layer can display the message.
 *  - A second student is not affected by another student's application.
 *  - Only application_received entries count as active applications;
 *    other email types do not trigger the duplicate block.
 *
 * @package    mod_dhbwio
 * @category   phpunit
 * @group      mod_dhbwio
 * @group      mod_dhbwio_duplicate_application
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_dhbwio_duplicate_application_testcase extends advanced_testcase {

    /** @var stdClass The dhbwio module instance. */
    private stdClass $dhbwio;

    /** @var stdClass A student user. */
    private stdClass $student;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $gen          = $this->getDataGenerator();
        $course       = $gen->create_course();
        $this->dhbwio = $gen->create_module('dhbwio', ['course' => $course->id]);
        $this->student = $gen->create_user();
    }

    // =========================================================================
    // Helper
    // =========================================================================

    /**
     * Simulates a submitted application by writing an application_received log entry.
     */
    private function simulate_submitted_application(int $userid, int $entry_id = 1): void {
        global $DB;
        $DB->insert_record('dhbwio_email_log', [
            'dhbwio_id'   => $this->dhbwio->id,
            'entry_id'    => $entry_id,
            'user_id'     => $userid,
            'email_type'  => 'application_received',
            'status'      => 'eingegangen',
            'timecreated' => time(),
        ]);
    }

    // =========================================================================
    // Kein vorhandener Eintrag → Bewerbung erlaubt
    // =========================================================================

    public function test_user_with_no_application_passes_duplicate_check(): void {
        $result = dhbwio_check_existing_application($this->dhbwio->id, $this->student->id);
        $this->assertNull($result,
            'A user without a prior application must not be blocked.');
    }

    // =========================================================================
    // Vorhandener Eintrag → Bewerbung gesperrt
    // =========================================================================

    public function test_user_with_existing_application_is_blocked(): void {
        $this->simulate_submitted_application($this->student->id);

        $result = dhbwio_check_existing_application($this->dhbwio->id, $this->student->id);
        $this->assertNotNull($result,
            'A user with an existing application must be blocked from submitting again.');
    }

    public function test_correct_error_key_returned_for_duplicate(): void {
        $this->simulate_submitted_application($this->student->id);

        $result = dhbwio_check_existing_application($this->dhbwio->id, $this->student->id);
        $this->assertSame('application_already_submitted', $result,
            'The returned error key must match the string used to display the error message.');
    }

    public function test_saving_is_prevented_when_duplicate_detected(): void {
        global $DB;
        $this->simulate_submitted_application($this->student->id);

        $error = dhbwio_check_existing_application($this->dhbwio->id, $this->student->id);

        // Simulate what the form handler does: only write if no error.
        if ($error === null) {
            $DB->insert_record('dhbwio_email_log', [
                'dhbwio_id'   => $this->dhbwio->id,
                'entry_id'    => 99,
                'user_id'     => $this->student->id,
                'email_type'  => 'application_received',
                'status'      => 'eingegangen',
                'timecreated' => time(),
            ]);
        }

        $count = $DB->count_records('dhbwio_email_log', [
            'dhbwio_id' => $this->dhbwio->id,
            'user_id'   => $this->student->id,
        ]);
        $this->assertSame(1, $count,
            'Only one application record must exist — the second save must have been prevented.');
    }

    // =========================================================================
    // Andere Studierenden nicht betroffen
    // =========================================================================

    public function test_second_student_is_not_blocked_by_first_students_application(): void {
        $second_student = $this->getDataGenerator()->create_user();

        $this->simulate_submitted_application($this->student->id);

        $result = dhbwio_check_existing_application($this->dhbwio->id, $second_student->id);
        $this->assertNull($result,
            'A duplicate application by one user must not block a different user.');
    }

    // =========================================================================
    // Nur application_received zählt als aktive Bewerbung
    // =========================================================================

    /**
     * @dataProvider non_blocking_email_types_provider
     */
    public function test_other_email_types_do_not_trigger_duplicate_block(string $email_type): void {
        global $DB;
        $DB->insert_record('dhbwio_email_log', [
            'dhbwio_id'   => $this->dhbwio->id,
            'entry_id'    => 1,
            'user_id'     => $this->student->id,
            'email_type'  => $email_type,
            'status'      => '',
            'timecreated' => time(),
        ]);

        $result = dhbwio_check_existing_application($this->dhbwio->id, $this->student->id);
        $this->assertNull($result,
            "Email type '$email_type' must not count as an active application.");
    }

    public function non_blocking_email_types_provider(): array {
        return [
            'approved'  => ['application_approved'],
            'rejected'  => ['application_rejected'],
            'inquiry'   => ['application_inquiry'],
        ];
    }
}
