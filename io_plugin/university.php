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

$id = required_param('id', PARAM_INT); // Course Module ID
$action = optional_param('action', '', PARAM_ALPHA); // add, edit, delete
$universityid = optional_param('university', 0, PARAM_INT); // University ID

// Get course module
$cm = get_coursemodule_from_id('dhbwio', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

// Setup page
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Set up page URL
$urlparams = ['id' => $cm->id];
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

			// Debug information to help understand what's happening
            if (debugging()) {
                mtrace('Updating university with ID: ' . $universityrecord->id);
            }
			
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
    
    // Output
    echo $OUTPUT->header();
    
    // Display back button
    $backurl = new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'universities']);
    echo html_writer::div(
        $OUTPUT->single_button($backurl, get_string('back_to_universities', 'mod_dhbwio')),
        'dhbwio-actions mb-4'
    );
    
    // Display university details
    echo '<div class="university-details">';
    echo '<h2>' . format_string($university->name) . '</h2>';
    echo '<h4>' . $university->city . ', ' . $countryName . '</h4>';
    
    // Display address if available
    if (!empty($university->address) || !empty($university->postal_code)) {
        echo '<p>';
        if (!empty($university->address)) {
            echo $university->address;
        }
        if (!empty($university->postal_code)) {
            if (!empty($university->address)) {
                echo ', ';
            }
            echo $university->postal_code;
        }
        echo '</p>';
    }
    
    // University image
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_dhbwio', 'university_images', 
                              $university->id, 'filename', false);
    
    if (!empty($files)) {
        $file = reset($files);
        $fileurl = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
        
        echo '<div class="university-image mb-3">';
        echo '<img src="' . $fileurl . '" class="img-fluid rounded" alt="' . $university->name . '">';
        echo '</div>';
    }
    
    // Actions for staff
    if (has_capability('mod/dhbwio:manageuniversities', $context)) {
        $editurl = new moodle_url('/mod/dhbwio/university.php', [
            'id' => $cm->id,
            'action' => 'edit',
            'university' => $university->id
        ]);
        
        $deleteurl = new moodle_url('/mod/dhbwio/university.php', [
            'id' => $cm->id,
            'action' => 'delete',
            'university' => $university->id,
            'sesskey' => sesskey()
        ]);
        
        echo '<div class="university-actions mt-3 mb-4">';
        echo '<a href="' . $editurl . '" class="btn btn-secondary mr-2">';
        echo get_string('edit_university', 'mod_dhbwio') . '</a>';
        
        echo '<a href="' . $deleteurl . '" class="btn btn-danger" onclick="return confirm(\'' . 
             get_string('delete_university_confirm', 'mod_dhbwio') . '\')">';
        echo get_string('delete_university', 'mod_dhbwio') . '</a>';
        echo '</div>';
    }
    
    // Display university details
    echo '<div class="card mb-4">';
    echo '<div class="card-header">';
    echo '<h3>' . get_string('university_details', 'mod_dhbwio') . '</h3>';
    echo '</div>';
    
    echo '<div class="card-body">';
    
    // Website
    if (!empty($university->website)) {
        echo '<p><strong>' . get_string('university_website', 'mod_dhbwio') . ':</strong> ';
        echo '<a href="' . $university->website . '" target="_blank">' . $university->website . '</a></p>';
    }
    
    // Available slots
    echo '<p><strong>' . get_string('university_available_slots', 'mod_dhbwio') . ':</strong> ' . 
         $university->available_slots . '</p>';
    
    // Semester periods
    if (!empty($university->semester_start) && !empty($university->semester_end)) {
        echo '<p><strong>' . get_string('semester_period', 'mod_dhbwio') . ':</strong> ';
        echo $months[$university->semester_start] . ' - ' . $months[$university->semester_end] . '</p>';
    }
    
    // Semester fees
    if (!empty($university->semester_fees)) {
        echo '<p><strong>' . get_string('semester_fees', 'mod_dhbwio') . ':</strong> ';
        echo number_format($university->semester_fees, 2) . ' ' . $university->fee_currency . '</p>';
    }
    
    // Accommodation type
    if (!empty($university->accommodation_type) && isset($accommodationTypes[$university->accommodation_type])) {
        echo '<p><strong>' . get_string('accommodation_type', 'mod_dhbwio') . ':</strong> ';
        echo $accommodationTypes[$university->accommodation_type] . '</p>';
    }
    
    // Description
    if (!empty($university->description)) {
        echo '<div class="university-description mt-4">';
        echo '<h4>' . get_string('university_description', 'mod_dhbwio') . '</h4>';
        echo format_text($university->description, $university->descriptionformat);
        echo '</div>';
    }
    
    // Requirements
    if (!empty($university->requirements)) {
        echo '<div class="university-requirements mt-4">';
        echo '<h4>' . get_string('university_requirements', 'mod_dhbwio') . '</h4>';
        echo format_text($university->requirements);
        echo '</div>';
    }
    
    echo '</div>'; // End card-body
    echo '</div>'; // End card
    
    // Experience reports if enabled
    if (!empty($dhbwio->enablereports)) {
        echo '<div class="university-reports mt-4">';
        echo '<h3>' . get_string('reports', 'mod_dhbwio') . '</h3>';
        
        // Get reports for this university
        $reports = $DB->get_records('dhbwio_experience_reports', [
            'university_id' => $university->id,
            'visible' => 1
        ], 'timecreated DESC');
        
        if (empty($reports)) {
            echo $OUTPUT->notification(get_string('no_reports_for_university', 'mod_dhbwio'), 'info');
        } else {
            foreach ($reports as $report) {
                // Get student info
                $student = $DB->get_record('user', ['id' => $report->userid]);
                
                echo '<div class="card mb-3">';
                echo '<div class="card-header">';
                echo '<h4>' . format_string($report->title) . '</h4>';
                echo '<p>' . get_string('by', 'mod_dhbwio') . ' ' . fullname($student) . ' | ' . 
                     userdate($report->timecreated) . '</p>';
                
                // Display rating if any
                if (!empty($report->rating)) {
                    echo '<p>' . get_string('rating', 'mod_dhbwio') . ': ';
                    for ($i = 1; $i <= 5; $i++) {
                        echo ($i <= $report->rating) ? '★' : '☆';
                    }
                    echo '</p>';
                }
                
                echo '</div>'; // End card-header
                
                echo '<div class="card-body">';
                echo format_text($report->content, $report->contentformat);
                echo '</div>'; // End card-body
                echo '</div>'; // End card
            }
        }
        
        echo '</div>'; // End university-reports
    }
    
    echo '</div>'; // End university-details
    
    echo $OUTPUT->footer();
    exit;
}

// Redirect to university list if no valid action
redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'universities']));