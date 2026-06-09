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
     * Creates the complete test environment for dhbwio application tests:
     * a dataform activity with four text fields and an aligned default view,
     * plus a dhbwio instance linked to it.
     *
     * Self-contained – uses only Moodle core data generators, so this step
     * works regardless of which Behat contexts are loaded by the test suite.
     *
     * @Given a dhbwio application environment is set up for course :shortname
     * @param string $shortname Course shortname, e.g. "WWI23B2"
     */
    public function a_dhbwio_application_environment_is_set_up_for_course(string $shortname): void {
        global $DB, $CFG;

        $course = $DB->get_record('course', ['shortname' => $shortname], 'id', MUST_EXIST);
        $generator = testing_util::get_data_generator();

        // --- Dataform --------------------------------------------------------
        // Explicit require needed: behat_mod_dataform.php is not loaded in the
        // @mod_dhbwio suite, so Moodle's auto-discovery for the generator class
        // mod_dataform_generator fails. Loading the file here registers the class.
        require_once($CFG->dirroot . '/mod/dataform/tests/generator/lib.php');
        $dataformgen = $generator->get_plugin_generator('mod_dataform');

        $dataform = $dataformgen->create_instance([
            'course' => $course->id,
            'name'   => 'Bewerbungsformular',
        ]);

        // Fields (created before the view so generate_default_view() includes them)
        foreach (['Kursgruppe', 'Vorname', 'Nachname', 'E-Mail'] as $fname) {
            $dataformgen->create_field([
                'type'   => 'text',
                'dataid' => $dataform->id,
                'name'   => $fname,
            ]);
        }

        // Default aligned view – renders "Add a new entry" link and a Save button
        $dataformgen->create_view([
            'type'    => 'aligned',
            'dataid'  => $dataform->id,
            'name'    => 'Ansicht',
            'default' => 1,
        ]);

        // Store instance ID so the form-filling step can resolve field names → IDs.
        $this->dhbwio_dataform_instance_id = (int) $dataform->id;

        // --- dhbwio ----------------------------------------------------------
        $dhbwiogen = $generator->get_plugin_generator('mod_dhbwio');
        $dhbwio = $dhbwiogen->create_instance(['course' => $course->id]);

        // dhbwio.dataform_id stores the CM id of the linked dataform because
        // the observer matches it against entry_created event->contextinstanceid.
        $dataform_cm = get_coursemodule_from_instance('dataform', $dataform->id, $course->id);
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
                'Run "a dhbwio application environment is set up for course ..." first.'
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
