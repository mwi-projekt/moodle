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
 * Handles experience report CRUD operations.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot . '/mod/dhbwio/classes/form/report_form.php');

$cmid = required_param('cmid', PARAM_INT); // Course Module ID
$action = required_param('action', PARAM_ALPHA); // add, edit, delete
$reportid = optional_param('report', 0, PARAM_INT); // Report ID

// Get course module
$cm = get_coursemodule_from_id('dhbwio', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

// Setup page
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Check if reports are enabled
if (empty($dhbwio->enablereports)) {
    redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id]));
}

// Set up page URL
$urlparams = ['cmid' => $cm->id, 'action' => $action];
if ($reportid) {
    $urlparams['report'] = $reportid;
}
$PAGE->set_url('/mod/dhbwio/report.php', $urlparams);

$PAGE->set_title(format_string($dhbwio->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Handle add/edit report
if ($action == 'add' || $action == 'edit') {
    // Check capability
    if ($action == 'add') {
        require_capability('mod/dhbwio:submitreport', $context);
    }
    
    // Get report data if editing
    $report = null;
    $reportdata = new stdClass();
    
    if ($action == 'edit' && $reportid) {
        $report = $DB->get_record('dhbwio_experience_reports', ['id' => $reportid, 'dhbwio' => $dhbwio->id], '*', MUST_EXIST);
        
        // Check if user is owner or has manage capability
        if ($report->userid != $USER->id && !has_capability('mod/dhbwio:manageuniversities', $context)) {
            throw new moodle_exception('nopermission');
        }
        
        $reportdata = clone $report;
    } else {
        $reportdata->id = null;
        $reportdata->dhbwio = $dhbwio->id;
        $reportdata->userid = $USER->id;
        $reportdata->visible = 1;
    }
    
    // Get universities for dropdown
    $universities = $DB->get_records('dhbwio_universities', [
        'dhbwio' => $dhbwio->id,
        'active' => 1
    ], 'country, name');
    
    $universityoptions = [];
    foreach ($universities as $university) {
        $universityoptions[$university->id] = $university->name . ' (' . $university->country . ')';
    }
    
    if (empty($universityoptions)) {
        redirect(
            new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'reports']),
            get_string('no_universities', 'mod_dhbwio'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
    
    // Set up form options
    $editoroptions = ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $context];
    $filemanageroptions = ['subdirs' => 0, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => 5];
    
    // Create form
    $mform = new \mod_dhbwio\form\report_form(null, [
        'cmid' => $cm->id,
        'report' => $report,
        'context' => $context,
        'universities' => $universityoptions
    ]);
    
    // Set existing data if editing
    if ($action == 'edit') {
        // Prepare content field for editor
        if (!empty($report->content)) {
            $reportdata->content_editor = [
                'text' => $report->content,
                'format' => $report->contentformat
            ];
        }
        
        // Prepare files for file manager
        $draftitemid = file_get_submitted_draft_itemid('attachments');
        file_prepare_draft_area($draftitemid, $context->id, 'mod_dhbwio', 'report_attachments', 
                              $report->id, $filemanageroptions);
        $reportdata->attachments = $draftitemid;
        
        $mform->set_data($reportdata);
    }
    
    // Form processing
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'reports']));
    } else if ($formdata = $mform->get_data()) {
        $now = time();
        
        // Prepare report record
        $reportrecord = new stdClass();
        $reportrecord->dhbwio = $dhbwio->id;
        $reportrecord->university_id = $formdata->university_id;
        $reportrecord->title = $formdata->title;
        $reportrecord->rating = $formdata->rating;
        $reportrecord->timemodified = $now;
        
        // Set visibility (staff can change, students always visible)
        if (has_capability('mod/dhbwio:manageuniversities', $context) && isset($formdata->visible)) {
            $reportrecord->visible = $formdata->visible;
        } else {
            $reportrecord->visible = 1;
        }
        
        // Process content editor
        if (isset($formdata->content_editor)) {
            $reportrecord->content = $formdata->content_editor['text'];
            $reportrecord->contentformat = $formdata->content_editor['format'];
        }
        
        // Insert or update record
        if ($action == 'add') {
            $reportrecord->userid = $USER->id;
            $reportrecord->timecreated = $now;
            $reportrecord->id = $DB->insert_record('dhbwio_experience_reports', $reportrecord);
        } else {
            $reportrecord->id = $reportid;
            $DB->update_record('dhbwio_experience_reports', $reportrecord);
        }
        
        // Save attachments
        file_save_draft_area_files($formdata->attachments, $context->id, 'mod_dhbwio', 
                                 'report_attachments', $reportrecord->id, $filemanageroptions);
        
        // Redirect to reports list
        redirect(
            new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'reports']),
            get_string('report_submitted', 'mod_dhbwio'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
    
    // Display form
    echo $OUTPUT->header();
    echo $OUTPUT->heading($action == 'add' ? get_string('add_report', 'mod_dhbwio') : get_string('edit_report', 'mod_dhbwio'));
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// Handle delete action
if ($action == 'delete' && $reportid) {
    require_sesskey();
    
    // Get report
    $report = $DB->get_record('dhbwio_experience_reports', ['id' => $reportid, 'dhbwio' => $dhbwio->id], '*', MUST_EXIST);
    
    // Check if user is owner or has manage capability
    if ($report->userid != $USER->id && !has_capability('mod/dhbwio:manageuniversities', $context)) {
        throw new moodle_exception('nopermission');
    }
    
    // Delete report record
    $DB->delete_records('dhbwio_experience_reports', ['id' => $reportid]);
    
    // Delete attachments
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_dhbwio', 'report_attachments', $reportid);
    
    // Redirect to reports list
    redirect(
        new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'reports']),
        get_string('report_deleted', 'mod_dhbwio'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Redirect to reports list if no valid action
redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'reports']));