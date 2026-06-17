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

/**
 * Integration tests for role-based access control (Issue #23).
 *
 * Verifies that:
 *  - Guest users are not authenticated and have no module access.
 *  - Users not enrolled in the course cannot view the module.
 *  - Enrolled students can view the module and submit reports.
 *  - Enrolled students are blocked from all management capabilities.
 *  - IO staff (editingteacher) has all required management capabilities.
 *  - The role hierarchy Site > Course > Module is correctly enforced.
 *
 * @package    mod_dhbwio
 * @category   phpunit
 * @group      mod_dhbwio
 * @group      mod_dhbwio_role_access
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_dhbwio_role_access_testcase extends advanced_testcase {

    /** @var stdClass The course. */
    private stdClass $course;

    /** @var stdClass The dhbwio module instance. */
    private stdClass $dhbwio;

    /** @var context_module The module context used for capability checks. */
    private context_module $context;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        $gen          = $this->getDataGenerator();
        $this->course = $gen->create_course();
        $this->dhbwio = $gen->create_module('dhbwio', ['course' => $this->course->id]);

        // Resolve the course-module context via the instance ID so the test
        // does not depend on which property create_module() puts the cmid into.
        $cm             = get_coursemodule_from_instance('dhbwio', $this->dhbwio->id);
        $this->context  = context_module::instance($cm->id);
    }

    // =========================================================================
    // Gast-Nutzer: kein Zugang
    // =========================================================================

    public function test_guest_user_is_recognised_as_guest(): void {
        $this->setGuestUser();
        $this->assertTrue(isguestuser(),
            'setGuestUser() must result in isguestuser() returning true.');
    }

    public function test_guest_user_cannot_view_module(): void {
        $this->setGuestUser();
        $this->assertFalse(
            has_capability('mod/dhbwio:view', $this->context),
            'Guest users must not have mod/dhbwio:view — require_login() would redirect them.'
        );
    }

    // =========================================================================
    // Nicht-eingeschriebener Nutzer: kein Zugang
    // =========================================================================

    public function test_logged_in_but_not_enrolled_user_cannot_view_module(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->assertFalse(
            has_capability('mod/dhbwio:view', $this->context),
            'A user who is logged in but not enrolled in the course must not have view access.'
        );
    }

    public function test_logged_in_but_not_enrolled_user_cannot_manage_universities(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->assertFalse(
            has_capability('mod/dhbwio:manageuniversities', $this->context),
            'A user not enrolled in the course must not be able to manage universities.'
        );
    }

    // =========================================================================
    // Eingeschriebener Studierender: Zugang zum Formular
    // =========================================================================

    public function test_enrolled_student_can_view_module(): void {
        $student = $this->create_enrolled_user('student');
        $this->setUser($student);
        $this->assertTrue(
            has_capability('mod/dhbwio:view', $this->context),
            'An enrolled student must be able to view the module and access the form.'
        );
    }

    public function test_enrolled_student_can_submit_reports(): void {
        $student = $this->create_enrolled_user('student');
        $this->setUser($student);
        $this->assertTrue(
            has_capability('mod/dhbwio:submitreport', $this->context),
            'An enrolled student must be able to submit experience reports.'
        );
    }

    // =========================================================================
    // Eingeschriebener Studierender: keine Management-Rechte
    // =========================================================================

    /**
     * @dataProvider management_capabilities_provider
     */
    public function test_enrolled_student_is_blocked_from_management_capability(
        string $capability
    ): void {
        $student = $this->create_enrolled_user('student');
        $this->setUser($student);
        $this->assertFalse(
            has_capability($capability, $this->context),
            "Student must not have the management capability '$capability'."
        );
    }

    public function management_capabilities_provider(): array {
        return [
            'manage universities' => ['mod/dhbwio:manageuniversities'],
            'manage templates'    => ['mod/dhbwio:managetemplates'],
            'view reports'        => ['mod/dhbwio:viewreports'],
        ];
    }

    // =========================================================================
    // IO-Mitarbeiter (editingteacher): vollständige Management-Rechte
    // =========================================================================

    public function test_io_staff_can_view_module(): void {
        $teacher = $this->create_enrolled_user('editingteacher');
        $this->setUser($teacher);
        $this->assertTrue(
            has_capability('mod/dhbwio:view', $this->context),
            'IO staff must be able to view the module.'
        );
    }

    public function test_io_staff_can_manage_universities(): void {
        $teacher = $this->create_enrolled_user('editingteacher');
        $this->setUser($teacher);
        $this->assertTrue(
            has_capability('mod/dhbwio:manageuniversities', $this->context),
            'IO staff (editingteacher) must have the manageuniversities capability.'
        );
    }

    /**
     * @dataProvider management_capabilities_provider
     */
    public function test_io_staff_has_all_management_capabilities(string $capability): void {
        $teacher = $this->create_enrolled_user('editingteacher');
        $this->setUser($teacher);
        $this->assertTrue(
            has_capability($capability, $this->context),
            "IO staff must have the management capability '$capability'."
        );
    }

    // =========================================================================
    // Rollen-Hierarchie Site > Kurs > Modul
    // =========================================================================

    public function test_student_capability_is_granted_at_module_context_not_course(): void {
        $student      = $this->create_enrolled_user('student');
        $this->setUser($student);

        $course_context = context_course::instance($this->course->id);

        // view is a module-level capability — students get it via the module context
        // because of their course enrolment, but it is NOT a course-level capability.
        $this->assertTrue(
            has_capability('mod/dhbwio:view', $this->context),
            'Student must have view capability at module context.'
        );
        $this->assertFalse(
            has_capability('mod/dhbwio:manageuniversities', $course_context),
            'manageuniversities must not bleed up to course context for a student.'
        );
    }

    public function test_role_separation_student_and_io_staff_differ_on_manage_capability(): void {
        $student = $this->create_enrolled_user('student');
        $teacher = $this->create_enrolled_user('editingteacher');

        $this->setUser($student);
        $student_can_manage = has_capability('mod/dhbwio:manageuniversities', $this->context);

        $this->setUser($teacher);
        $teacher_can_manage = has_capability('mod/dhbwio:manageuniversities', $this->context);

        $this->assertFalse($student_can_manage,
            'Student must NOT have manageuniversities.');
        $this->assertTrue($teacher_can_manage,
            'IO staff must have manageuniversities.');
        $this->assertNotSame($student_can_manage, $teacher_can_manage,
            'Student and IO staff must have different access levels — role hierarchy must be enforced.');
    }

    // =========================================================================
    // Helper
    // =========================================================================

    /**
     * Creates a user and enrols them in $this->course with the given role.
     */
    private function create_enrolled_user(string $role): stdClass {
        $gen  = $this->getDataGenerator();
        $user = $gen->create_user();
        $gen->enrol_user($user->id, $this->course->id, $role);
        return $user;
    }
}
