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
 * Displays university details or handles university CRUD operations.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot . '/mod/dhbwio/classes/form/university_form.php');

$cmid = required_param('cmid', PARAM_INT); // Course Module ID
$action = optional_param('action', '', PARAM_ALPHA); // add, edit, delete
$universityid = optional_param('university', 0, PARAM_INT); // University ID

// Get course module
$cm = get_coursemodule_from_id('dhbwio', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

// Setup page
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Set up page URL
$urlparams = ['cmid' => $cm->id];
if ($action) {
    $urlparams['action'] = $action;
}
if ($universityid) {
    $urlparams['university'] = $universityid;
}
$PAGE->set_url('/mod/dhbwio/university.php', $urlparams);

$PAGE->set_title(format_string($dhbwio->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Check for add/edit capability
if ($action == 'add' || $action == 'edit') {
    require_capability('mod/dhbwio:manageuniversities', $context);
    
    // Get university data if editing
    $university = null;
    $universitydata = new stdClass();
    
    if ($action == 'edit' && $universityid) {
        $university = $DB->get_record('dhbwio_universities', ['id' => $universityid, 'dhbwio' => $dhbwio->id], '*', MUST_EXIST);
        $universitydata = clone $university;
    } else {
        $universitydata->id = null;
        $universitydata->dhbwio = $dhbwio->id;
    }
    
    // Set up form
    $editoroptions = ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $context];
    $filemanageroptions = ['subdirs' => 0, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => 1, 'accepted_types' => ['image']];

    // Create form
    $mform = new \mod_dhbwio\form\university_form(null, [
        'cmid' => $cm->id,
        'university' => $university,
        'context' => $context
    ]);
    
    // Set existing data if editing
    if ($action == 'edit') {
        // Prepare description field for editor
        if (!empty($university->description)) {
            $universitydata->description_editor = [
                'text' => $university->description,
                'format' => $university->descriptionformat
            ];
        }
        
        // Prepare files for file manager
        $draftitemid = file_get_submitted_draft_itemid('university_image');
        file_prepare_draft_area($draftitemid, $context->id, 'mod_dhbwio', 'university_images', 
                              $university->id, $filemanageroptions);
        $universitydata->university_image = $draftitemid;
        
        $mform->set_data($universitydata);
    }
    
    // Form processing
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'manageunis']));
    } else if ($formdata = $mform->get_data()) {
        $now = time();
        
        // Prepare university record
        $universityrecord = new stdClass();
        $universityrecord->dhbwio = $dhbwio->id;
        $universityrecord->name = $formdata->name;
        $universityrecord->country = $formdata->country;
        $universityrecord->city = $formdata->city;
        
        // Add address fields
        $universityrecord->address = $formdata->address;
        $universityrecord->postal_code = $formdata->postal_code;
        
        $universityrecord->website = $formdata->website;
        $universityrecord->latitude = $formdata->latitude;
        $universityrecord->longitude = $formdata->longitude;
        $universityrecord->available_slots = $formdata->available_slots;
        
        // Add semester period fields
        $universityrecord->semester_start = $formdata->semester_start;
        $universityrecord->semester_end = $formdata->semester_end;
        
        // Add fees and accommodation
        $universityrecord->semester_fees = $formdata->semester_fees;
        $universityrecord->fee_currency = $formdata->fee_currency;
        $universityrecord->accommodation_type = $formdata->accommodation_type;
        
        $universityrecord->requirements = $formdata->requirements;
        $universityrecord->active = $formdata->active;
        $universityrecord->timemodified = $now;
        
        // Process description editor
        if (isset($formdata->description_editor)) {
            $universityrecord->description = $formdata->description_editor['text'];
            $universityrecord->descriptionformat = $formdata->description_editor['format'];
        }
        
        // Insert or update record
        if ($action == 'add') {
            $universityrecord->id = $DB->insert_record('dhbwio_universities', $universityrecord);
            $event = 'created';
        } else {
            $universityrecord->id = $universityid;
			
            $DB->update_record('dhbwio_universities', $universityrecord);
            $event = 'updated';
        }
        
        // Save university image
        file_save_draft_area_files($formdata->university_image, $context->id, 'mod_dhbwio', 
                                 'university_images', $universityrecord->id, $filemanageroptions);
        
        // Set success message
        $message = get_string('university_saved', 'mod_dhbwio');
                
        // Redirect to university management
        redirect(
            new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'manageunis']),
            $message,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
    
    // Display form
    echo $OUTPUT->header();
    echo $OUTPUT->heading($action == 'add' ? get_string('add_university', 'mod_dhbwio') : get_string('edit_university', 'mod_dhbwio'));
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// Handle delete action
if ($action == 'delete' && $universityid) {
    require_capability('mod/dhbwio:manageuniversities', $context);
    require_sesskey();
    
    // Get university
    $university = $DB->get_record('dhbwio_universities', ['id' => $universityid, 'dhbwio' => $dhbwio->id], '*', MUST_EXIST);
    
    // Delete university record
    $DB->delete_records('dhbwio_universities', ['id' => $universityid]);
    
    // Delete associated experience reports
    $DB->delete_records('dhbwio_experience_reports', ['university_id' => $universityid]);
    
    // Delete university images
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_dhbwio', 'university_images', $universityid);
    
    // Redirect to university management
    redirect(
        new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'manageunis']),
        get_string('university_deleted', 'mod_dhbwio'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Display university details
if ($universityid) {
    // Get university
    $university = $DB->get_record('dhbwio_universities', ['id' => $universityid, 'dhbwio' => $dhbwio->id], '*', MUST_EXIST);
    
    // Get country name from ISO code
    $countries = get_string_manager()->get_list_of_countries();
    $countryName = isset($countries[$university->country]) ? $countries[$university->country] : $university->country;
    
    // Get month names for semester dates
    $months = [];
    for ($i = 1; $i <= 12; $i++) {
        $months[$i] = get_string('month_' . $i, 'mod_dhbwio');
    }
    
    // Get accommodation type name
    $accommodationTypes = [
        'dorm' => get_string('accommodation_dorm', 'mod_dhbwio'),
        'apartment' => get_string('accommodation_apartment', 'mod_dhbwio'),
        'homestay' => get_string('accommodation_homestay', 'mod_dhbwio'),
        'hotel' => get_string('accommodation_hotel', 'mod_dhbwio'),
        'airbnb' => get_string('accommodation_airbnb', 'mod_dhbwio'),
        'private' => get_string('accommodation_private', 'mod_dhbwio'),
        'various' => get_string('accommodation_various', 'mod_dhbwio'),
        'none' => get_string('accommodation_none', 'mod_dhbwio')
    ];
    
    // Set page title
    $PAGE->set_title(format_string($university->name));
    
    // Prepare template data
    $templatedata = new stdClass();
    
    // Basic info
    $templatedata->backurl = new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'universities']);
    $templatedata->name = format_string($university->name);
    $templatedata->city = $university->city;
    $templatedata->country = $countryName;
    
    // Address
    if (!empty($university->address) || !empty($university->postal_code)) {
        $templatedata->hasaddress = true;
        $templatedata->address = $university->address;
        $templatedata->postal_code = $university->postal_code;
    }
    
    // University image
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_dhbwio', 'university_images', 
                              $university->id, 'filename', false);
    
    if (!empty($files)) {
        $file = reset($files);
        $templatedata->hasimage = true;
        $templatedata->imageurl = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
    }
    
    // Apply button - check if dataform is configured
    if (!empty($dhbwio->dataform_id)) {
		// The dataform_id is a course module ID
		$dataformcm = $DB->get_record_sql(
			"SELECT cm.*, c.id as courseid 
			FROM {course_modules} cm 
			JOIN {modules} m ON m.id = cm.module 
			JOIN {course} c ON c.id = cm.course
			WHERE cm.id = ? AND m.name = 'dataform'",
			[$dhbwio->dataform_id]
		);
		
		if ($dataformcm && $dataformcm->visible) {
			// Check if user can access this activity
			$modinfo = get_fast_modinfo($dataformcm->courseid);
			if (isset($modinfo->cms[$dataformcm->id]) && $modinfo->cms[$dataformcm->id]->uservisible) {
				$templatedata->showapplybutton = true;
				$templatedata->applyurl = new moodle_url('/mod/dataform/view.php', ['id' => $dataformcm->id]);
			}
		}
	}
    
    // Management actions
    if (has_capability('mod/dhbwio:manageuniversities', $context)) {
        $templatedata->canmanage = true;
        $templatedata->editurl = (new moodle_url('/mod/dhbwio/university.php', [
            'cmid' => $cm->id,
            'action' => 'edit',
            'university' => $university->id
        ]))->out();
        $templatedata->deleteurl = (new moodle_url('/mod/dhbwio/university.php', [
            'cmid' => $cm->id,
            'action' => 'delete',
            'university' => $university->id,
            'sesskey' => sesskey()
        ]))->out();
    }
    
    // University details
    if (!empty($university->website)) {
        $templatedata->haswebsite = true;
        $templatedata->website = $university->website;
    }
    
    $templatedata->available_slots = $university->available_slots;
    
    // Semester period
    if (!empty($university->semester_start) && !empty($university->semester_end)) {
        $templatedata->hassemesterperiod = true;
        $templatedata->semester_period = $months[$university->semester_start] . ' - ' . $months[$university->semester_end];
    }
    
    // Semester fees
    if (!empty($university->semester_fees)) {
        $templatedata->hasfees = true;
        $templatedata->semester_fees_formatted = number_format($university->semester_fees, 2) . ' ' . $university->fee_currency;
    }
    
    // Accommodation type
    if (!empty($university->accommodation_type) && isset($accommodationTypes[$university->accommodation_type])) {
        $templatedata->hasaccommodation = true;
        $templatedata->accommodation_type = $accommodationTypes[$university->accommodation_type];
    }
    
    // Description
    if (!empty($university->description)) {
        $templatedata->hasdescription = true;
        $templatedata->description = format_text($university->description, $university->descriptionformat);
    }
    
    // Requirements
    if (!empty($university->requirements)) {
        $templatedata->hasrequirements = true;
        $templatedata->requirements = format_text($university->requirements);
    }
    
    // Experience reports if enabled
    if (!empty($dhbwio->enablereports)) {
        $templatedata->hasreports = true;
        
        // Get reports for this university
        $reports = $DB->get_records('dhbwio_experience_reports', [
            'university_id' => $university->id,
            'visible' => 1
        ], 'timecreated DESC');
        
        if (empty($reports)) {
            $templatedata->noreports = true;
        } else {
            $templatedata->reports = [];
            foreach ($reports as $report) {
                // Get student info
                $student = $DB->get_record('user', ['id' => $report->userid]);
                
                $reportdata = new stdClass();
                $reportdata->title = format_string($report->title);
                $reportdata->author = fullname($student);
                $reportdata->date = userdate($report->timecreated);
                $reportdata->content = format_text($report->content, $report->contentformat);
                
                // Format rating
                if (!empty($report->rating)) {
                    $reportdata->rating = $report->rating;
                    $reportdata->ratingdisplay = '';
                    for ($i = 1; $i <= 5; $i++) {
                        $reportdata->ratingdisplay .= ($i <= $report->rating) ? '★' : '☆';
                    }
                }
                
                $templatedata->reports[] = $reportdata;
            }
        }
    }
    
    // Render using template
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('mod_dhbwio/university_detail', $templatedata);
    echo $OUTPUT->footer();
    exit;
}

// Redirect to university list if no valid action
redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'universities']));