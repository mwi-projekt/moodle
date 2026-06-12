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
     * Uses direct DB inserts + Moodle core course API for the dataform setup so
     * that no dataform PHP files need to be loaded. This is necessary because
     * moodle-plugin-ci installs dependency plugins without their tests/ directory,
     * making the dataform generator unavailable in the @mod_dhbwio Behat suite.
     *
     * @Given a dhbwio application environment is set up for course :shortname
     * @param string $shortname Course shortname, e.g. "WWI23B2"
     */
    public function a_dhbwio_application_environment_is_set_up_for_course(string $shortname): void {
        global $DB, $CFG;

        $course = $DB->get_record('course', ['shortname' => $shortname], 'id', MUST_EXIST);
        $generator = testing_util::get_data_generator();

        require_once($CFG->dirroot . '/course/lib.php');

        // --- Dataform: direct DB + core course API ---------------------------
        // No dataform PHP code is loaded; only Moodle core functions are used.
        // This avoids failures caused by the absent tests/ directory in CI.

        $datamodule = $DB->get_record('modules', ['name' => 'dataform'], 'id', MUST_EXIST);
        $now = time();

        $dataform_id = $DB->insert_record('dataform', (object)[
            'course'                  => $course->id,
            'name'                    => 'Bewerbungsformular',
            'intro'                   => '',
            'introformat'             => FORMAT_HTML,
            'inlineview'              => 0,
            'embedded'                => 0,
            'timemodified'            => $now,
            'timeavailable'           => 0,
            'timedue'                 => 0,
            'timeinterval'            => 0,
            'intervalcount'           => 1,
            'grade'                   => 0,
            'maxentries'              => -1,
            'entriesrequired'         => 0,
            'individualized'          => 0,
            'grouped'                 => 0,
            'anonymous'               => 0,
            'timelimit'               => -1,
            'defaultview'             => 0,
            'defaultfilter'           => 0,
            'completionentries'       => 0,
            'completionspecificgrade' => 0,
        ]);

        $cm = new stdClass();
        $cm->course    = $course->id;
        $cm->module    = $datamodule->id;
        $cm->instance  = $dataform_id;
        $cm->visible   = 1;
        $cm->groupmode = 0;
        $cm->completion = 0;
        $cm_id = add_course_module($cm);
        course_add_cm_to_section($course->id, $cm_id, 0);
        rebuild_course_cache($course->id);

        $this->dhbwio_dataform_instance_id = (int) $dataform_id;

        // Fields — insert first to collect IDs for the patterns blob.
        $field_ids = [];
        foreach (['Kursgruppe', 'Vorname', 'Nachname', 'E-Mail'] as $fname) {
            $field_ids[$fname] = $DB->insert_record('dataform_fields', (object)[
                'dataid'             => $dataform_id,
                'type'               => 'text',
                'name'               => $fname,
                'description'        => '',
                'visible'            => 2,
                'editable'           => 1,
                'defaultcontentmode' => 0,
            ]);
        }

        // dataformview::compile_view_template() reads the serialised patterns blob
        // to know which ##...## and [[...]] tokens appear in the templates.
        // Without it, get_pattern_set('view') returns null and ##addnewentry## is
        // never replaced with the link — the student sees no "Add a new entry" button.
        $patterns_array = [
            'view' => [
                '##addnewentry##' => '##addnewentry##',
                '##entries##'     => '##entries##',
            ],
            'field' => [],
        ];
        foreach ($field_ids as $fname => $fid) {
            $pattern = ($fname === 'Nachname') ? "[[*$fname]]" : "[[$fname]]";
            $patterns_array['field'][$fid] = [$pattern => $pattern];
        }

        // View: section holds the page template (##addnewentry## renders the link),
        // param2 holds the per-entry edit template ([[field]] patterns),
        // submission holds the serialised save/cancel settings (base64-encoded).
        $view_id = $DB->insert_record('dataform_views', (object)[
            'dataid'      => $dataform_id,
            'type'        => 'aligned',
            'name'        => 'Ansicht',
            'description' => '',
            'visible'     => 1,
            'perpage'     => 0,
            'filterid'    => 0,
            'section'     => '<div>##addnewentry##</div><div>##entries##</div>',
            'param2'      => "[[Kursgruppe]]\n[[Vorname]]\n[[*Nachname]]\n[[E-Mail]]",
            'submission'  => base64_encode(serialize(['save' => '', 'cancel' => '', 'timeout' => 1])),
            'patterns'    => serialize($patterns_array),
        ]);
        $DB->set_field('dataform', 'defaultview', $view_id, ['id' => $dataform_id]);

        // --- dhbwio ----------------------------------------------------------
        $dhbwiogen = $generator->get_plugin_generator('mod_dhbwio');
        $dhbwio    = $dhbwiogen->create_instance(['course' => $course->id]);

        // dhbwio.dataform_id stores the CM id of the linked dataform because
        // the observer matches it against entry_created event->contextinstanceid.
        $DB->set_field('dhbwio', 'dataform_id', $cm_id, ['id' => $dhbwio->id]);
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
     * Verifies that a required-field validation error is both visible in the UI
     * and carries ARIA attributes so screen readers can announce it (Issue #25).
     *
     * Checks:
     *  a) A <span class="error"> element is present and visible on the page.
     *  b) The associated input has aria-describedby pointing to the error element
     *     OR aria-invalid="true", OR the error element itself has role="alert" —
     *     any one of these satisfies WCAG 1.3.1 (Info and Relationships).
     *
     * @Then the form error for :fieldname is visible and accessible
     * @param string $fieldname  Field name as stored in dataform_fields.name
     */
    public function the_form_error_for_field_is_visible_and_accessible(string $fieldname): void {
        global $DB;

        if ($this->dhbwio_dataform_instance_id === null) {
            throw new \RuntimeException(
                'Dataform instance ID not initialised. ' .
                'Run "a dhbwio application environment is set up for course ..." first.'
            );
        }

        $field = $DB->get_record(
            'dataform_fields',
            ['dataid' => $this->dhbwio_dataform_instance_id, 'name' => $fieldname],
            'id',
            MUST_EXIST
        );
        $inputid  = "id_field_{$field->id}_-1";
        $errorid  = "{$inputid}_error";
        $page     = $this->getSession()->getPage();

        // AC3a: the error element must be present and visible.
        $errorel = $page->find('css', "#{$errorid}");
        if (!$errorel) {
            $errorel = $page->find('css', 'span.error');
        }
        if (!$errorel || !$errorel->isVisible()) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "No visible error message found for required field \"$fieldname\". " .
                "Expected an element with id=\"{$errorid}\" or class=\"error\" to be visible after " .
                "attempting to save without a value.",
                $this->getSession()
            );
        }

        // AC3b: ARIA accessibility — at least one of the following must be true:
        //   • input has aria-describedby referencing the error element
        //   • input has aria-invalid="true"
        //   • error element carries role="alert" (live region)
        $input = $page->find('css', "#{$inputid}");
        $accessible = false;
        if ($input) {
            $ariadescribedby = $input->getAttribute('aria-describedby');
            $ariainvalid     = $input->getAttribute('aria-invalid');
            if (!empty($ariadescribedby) || $ariainvalid === 'true') {
                $accessible = true;
            }
        }
        if (!$accessible && $errorel->getAttribute('role') === 'alert') {
            $accessible = true;
        }
        if (!$accessible) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Error message for \"$fieldname\" is visible but not accessible to screen readers. " .
                "Expected aria-describedby or aria-invalid on #{$inputid}, " .
                "or role=\"alert\" on the error element.",
                $this->getSession()
            );
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
