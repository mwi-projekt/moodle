<?php
// NOTE: no MOODLE_INTERNAL check here – this file is loaded by Behat before config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;

/**
 * Behat step definitions for mod_dhbwio system tests.
 *
 * Provides three custom steps used in application_process.feature:
 *  - Environment setup (dhbwio instance + dataform link)
 *  - Application form filling (dynamic field-ID resolution)
 *  - AC2/AC3 assertions (no 500 error, email log entry)
 *
 * @package    mod_dhbwio
 * @category   tests
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_dhbwio extends behat_base {

    /**
     * Dataform instance ID resolved during the setup step.
     * Used by the form-filling step to look up field IDs by name.
     * @var int|null
     */
    protected $dhbwio_dataform_instance_id = null;

    // =========================================================================
    // Setup step
    // =========================================================================

    /**
     * Creates a dhbwio module instance in the given course and links it to an
     * existing dataform identified by its course-module idnumber.
     *
     * Why the linking matters: the observer in observer.php matches incoming
     * dataform entry_created events against dhbwio.dataform_id, which stores
     * the CM id of the dataform (not the instance id).
     *
     * @Given a dhbwio instance is set up in course :shortname linked to dataform :dfidnumber
     * @param string $shortname  Course shortname, e.g. "C1"
     * @param string $dfidnumber Dataform CM idnumber, e.g. "dataform1"
     */
    public function a_dhbwio_instance_is_set_up_in_course_linked_to_dataform(
        string $shortname,
        string $dfidnumber
    ): void {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $shortname], 'id', MUST_EXIST);

        // The CM record gives us both the CM id (stored in dhbwio.dataform_id)
        // and the instance id (needed to resolve dataform_fields.dataid).
        $dataform_cm = $DB->get_record(
            'course_modules',
            ['idnumber' => $dfidnumber],
            'id, instance',
            MUST_EXIST
        );

        $this->dhbwio_dataform_instance_id = (int) $dataform_cm->instance;

        require_once(__DIR__ . '/../generator/lib.php');
        $generator = testing_util::get_data_generator()->get_plugin_generator('mod_dhbwio');
        $dhbwio = $generator->create_instance(['course' => $course->id]);

        // Link dhbwio to the dataform. dhbwio_add_instance() already created
        // the default email templates, so notifications will fire correctly.
        $DB->set_field('dhbwio', 'dataform_id', $dataform_cm->id, ['id' => $dhbwio->id]);
    }

    // =========================================================================
    // Form-filling step
    // =========================================================================

    /**
     * Fills the dataform application entry form by resolving each field name
     * to its numeric ID and targeting the input as "field_{id}_-1" (dataform
     * convention for new entries).
     *
     * @When I fill the dhbwio application form with:
     * @param TableNode $fields Two-column table: | field name | value |
     */
    public function i_fill_the_dhbwio_application_form_with(TableNode $fields): void {
        global $DB;

        if ($this->dhbwio_dataform_instance_id === null) {
            throw new \RuntimeException(
                'Dataform instance ID not initialised. ' .
                'Run "a dhbwio instance is set up in course ... linked to dataform ..." first.'
            );
        }

        foreach ($fields->getRowsHash() as $fieldname => $value) {
            $field = $DB->get_record(
                'dataform_fields',
                ['dataid' => $this->dhbwio_dataform_instance_id, 'name' => $fieldname],
                'id',
                MUST_EXIST
            );
            $this->getSession()->getPage()->fillField("field_{$field->id}_-1", $value);
        }
    }

    // =========================================================================
    // Assertion steps
    // =========================================================================

    /**
     * AC2 – Verifies that no HTTP 500 / technical error indicators are present
     * on the current page after the form submission.
     *
     * @Then no HTTP 500 error is visible on the page
     */
    public function no_http_500_error_is_visible_on_the_page(): void {
        $content = $this->getSession()->getPage()->getContent();

        $indicators = [
            'HTTP/1.1 500',
            'Internal Server Error',
            'Coding error detected',
            'Error code: ',
        ];

        foreach ($indicators as $indicator) {
            if (strpos($content, $indicator) !== false) {
                throw new \Behat\Mink\Exception\ExpectationException(
                    "HTTP 500 error indicator found on page: \"$indicator\"",
                    $this->getSession()
                );
            }
        }
    }

    /**
     * AC3 – Verifies that the dhbwio_email_log table contains an entry of the
     * given type for the specified user, confirming the email notification
     * pipeline was triggered by the observer.
     *
     * @Then an :emailtype email log entry exists for user :username
     * @param string $emailtype E.g. "application_received"
     * @param string $username  Moodle username of the expected recipient
     */
    public function an_email_log_entry_exists_for_user(string $emailtype, string $username): void {
        global $DB;

        $user = $DB->get_record('user', ['username' => $username], 'id', MUST_EXIST);

        $exists = $DB->record_exists('dhbwio_email_log', [
            'user_id'    => $user->id,
            'email_type' => $emailtype,
        ]);

        if (!$exists) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "No \"$emailtype\" entry found in dhbwio_email_log for user \"$username\". " .
                'The observer may not have fired or email sending failed.',
                $this->getSession()
            );
        }
    }
}
