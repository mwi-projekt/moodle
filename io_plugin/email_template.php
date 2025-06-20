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
 * Email template management for DHBW International Office.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot . '/mod/dhbwio/classes/form/email_template_form.php');

$id = required_param('cmid', PARAM_INT); // Course Module ID
$action = optional_param('action', '', PARAM_ALPHA); // edit, preview, test, createdefaults
$templateid = optional_param('template', 0, PARAM_INT); // Template ID

// Get course module
$cm = get_coursemodule_from_id('dhbwio', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

// Setup page
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Check capability
require_capability('mod/dhbwio:managetemplates', $context);

// Set up page URL
$urlparams = ['cmid' => $cm->id];
if ($action) {
    $urlparams['action'] = $action;
}
if ($templateid) {
    $urlparams['template'] = $templateid;
}
$PAGE->set_url('/mod/dhbwio/email_template.php', $urlparams);

$PAGE->set_title(format_string($dhbwio->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Handle create defaults action
if ($action == 'createdefaults') {
    require_sesskey();
    
    // Create default templates
    dhbwio_create_default_email_templates($dhbwio->id);
    
    redirect(
        new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'emailtemplates']),
        get_string('default_templates_created', 'mod_dhbwio'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Handle edit action
if ($action == 'edit') {
    // Get template data
    $template = null;
    $templatedata = new stdClass();
    
	$template = $DB->get_record('dhbwio_email_templates', ['id' => $templateid, 'dhbwio' => $dhbwio->id], '*', MUST_EXIST);
	$templatedata = clone $template;

    // Create form
    $mform = new \mod_dhbwio\form\email_template_form(null, [
        'cmid' => $cm->id,
        'template' => $template,
        'context' => $context,
        'dhbwio_id' => $dhbwio->id
    ]);
    
    $mform->set_data($templatedata);
    
    // Form processing
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'emailtemplates']));
    } else if ($formdata = $mform->get_data()) {
        $now = time();
        
        // Prepare template record
        $templaterecord = new stdClass();
        $templaterecord->dhbwio = $dhbwio->id;
        $templaterecord->name = $formdata->name;
        $templaterecord->subject = $formdata->subject;
        $templaterecord->enabled = $formdata->enabled;
        $templaterecord->timemodified = $now;
        
        // Process body editor
        if (isset($formdata->body_editor)) {
            $templaterecord->body = $formdata->body_editor['text'];
            $templaterecord->bodyformat = $formdata->body_editor['format'];
        }
        
		$templaterecord->id = $templateid;
		$templaterecord->type = $template->type; // Keep original type
		$templaterecord->lang = $template->lang; // Keep original language
		$DB->update_record('dhbwio_email_templates', $templaterecord);
		$message = get_string('template_updated', 'mod_dhbwio');
        
        // Redirect to email templates
        redirect(
            new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'emailtemplates']),
            $message,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
    
    // Display form
    echo $OUTPUT->header();
    
    echo $OUTPUT->heading(get_string('edit_template', 'mod_dhbwio'));
    
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// Handle preview action
if ($action == 'preview' && $templateid) {
    $template = $DB->get_record('dhbwio_email_templates', ['id' => $templateid, 'dhbwio' => $dhbwio->id], '*', MUST_EXIST);
    
    // Display preview
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('preview_template', 'mod_dhbwio'));
    
    // Back button
    $backurl = new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'emailtemplates']);
    echo html_writer::div(
        $OUTPUT->single_button($backurl, get_string('back')),
        'dhbwio-actions mb-4'
    );
    
    // Preview with sample data including dataform fields
    $sampledata = [
        'STUDENT_NAME' => 'Max Mustermann',
        'STUDENT_FIRSTNAME' => 'Max',
        'STUDENT_EMAIL' => 'max.mustermann@student.dhbw.de',
        'SUBMISSION_DATE' => userdate(time()),
        'SEMESTER' => 'Winter 2025/26',
        'UNIVERSITY_CHOICES' => '1. Harvard University (USA)<br>2. Oxford University (UK)<br>3. Sorbonne University (France)',
        'FIRST_WISH' => 'Harvard University (USA)',
        'SECOND_WISH' => 'Oxford University (UK)',
        'THIRD_WISH' => 'Sorbonne University (France)',
        'APPROVED_UNIVERSITY' => 'Harvard University',
        'FEEDBACK' => 'Ihre Bewerbung war überzeugend, aber leider hatten wir mehr qualifizierte Kandidaten als freie Plätze.',
        'INQUIRY_COMMENT' => 'Bitte legen Sie eine Kopie Ihres letzten Zeugnisses und ein Empfehlungsschreiben vor.'
    ];
    
    // Add sample dataform data based on actual fields
    $dataformfields = dhbwio_get_dataform_fields($dhbwio->id);
    foreach ($dataformfields as $field) {
        $varname = 'DATAFORM_' . strtoupper($field['name']);
        
        // Generate appropriate sample data based on field type
        switch ($field['type']) {
            case 'text':
                $sampledata[$varname] = 'Beispieltext für ' . $field['name'];
                break;
            case 'textarea':
                $sampledata[$varname] = 'Dies ist ein Beispieltext für das Feld ' . $field['name'] . '.';
                break;
            case 'number':
                $sampledata[$varname] = '42';
                break;
            case 'select':
            case 'radiobutton':
                $sampledata[$varname] = 'Option 1';
                break;
            case 'checkbox':
                $sampledata[$varname] = get_string('yes');
                break;
            case 'url':
                $sampledata[$varname] = 'https://example.com';
                break;
            case 'email':
                $sampledata[$varname] = 'sample@example.com';
                break;
            case 'date':
                $sampledata[$varname] = userdate(time());
                break;
            default:
                $sampledata[$varname] = 'Beispielwert';
        }
    }
    
    // Replace variables
    $subject = $template->subject;
    $body = $template->body;
    
    foreach ($sampledata as $key => $value) {
        $subject = str_replace('{' . $key . '}', $value, $subject);
        $body = str_replace('{' . $key . '}', $value, $body);
    }
    
    // Display preview
    echo '<div class="card">';
    echo '<div class="card-header">';
    echo '<h4>' . get_string('email_template_subject', 'mod_dhbwio') . '</h4>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<p class="lead">' . $subject . '</p>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="card mt-3">';
    echo '<div class="card-header">';
    echo '<h4>' . get_string('email_template_body', 'mod_dhbwio') . '</h4>';
    echo '</div>';
    echo '<div class="card-body">';
    echo format_text($body, $template->bodyformat);
    echo '</div>';
    echo '</div>';
    
    // Show variables used
    echo '<div class="card mt-3">';
    echo '<div class="card-header">';
    echo '<h4>' . get_string('variables_in_preview', 'mod_dhbwio') . '</h4>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<table class="table table-sm">';
    echo '<thead><tr><th>Variable</th><th>Sample Value</th></tr></thead>';
    echo '<tbody>';
    foreach ($sampledata as $var => $value) {
        if (strpos($template->subject . $template->body, '{' . $var . '}') !== false) {
            echo '<tr><td><code>{' . $var . '}</code></td><td>' . htmlspecialchars($value) . '</td></tr>';
        }
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    
    echo $OUTPUT->footer();
    exit;
}

// Handle test email action
if ($action == 'test' && $templateid) {
    require_sesskey();
    
    $template = $DB->get_record('dhbwio_email_templates', ['id' => $templateid, 'dhbwio' => $dhbwio->id], '*', MUST_EXIST);
    
    // Send test email to current user using enhanced function
    $testparams = [
        'STUDENT_NAME' => fullname($USER),
        'STUDENT_FIRSTNAME' => $USER->firstname,
        'STUDENT_EMAIL' => $USER->email,
        'SUBMISSION_DATE' => userdate(time()),
        'SEMESTER' => 'Test Semester',
        'UNIVERSITY_CHOICES' => 'Test University 1<br>Test University 2<br>Test University 3',
        'FIRST_WISH' => 'Test University 1',
        'SECOND_WISH' => 'Test University 2',
        'THIRD_WISH' => 'Test University 3',
        'APPROVED_UNIVERSITY' => 'Test University',
        'FEEDBACK' => 'This is a test feedback message.',
        'INQUIRY_COMMENT' => 'This is a test inquiry comment.'
    ];
    
    $result = dhbwio_send_email_notification($template->type, $dhbwio->id, $USER->id, $testparams, $template->lang);
    
    if ($result) {
        $message = get_string('test_email_sent', 'mod_dhbwio');
        $type = \core\output\notification::NOTIFY_SUCCESS;
    } else {
        $message = get_string('test_email_failed', 'mod_dhbwio');
        $type = \core\output\notification::NOTIFY_ERROR;
    }
    
    redirect(
        new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'emailtemplates']),
        $message,
        null,
        $type
    );
}

// If no action, redirect to view
redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'emailtemplates']));