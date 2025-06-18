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
 * Displays the DHBW International Office Module.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT); // Course Module ID
$tab = optional_param('tab', 'universities', PARAM_ALPHA); // Active tab

// Get course module
$cm = get_coursemodule_from_id('dhbwio', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

// Setup page
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => $tab]);
$PAGE->set_title(format_string($dhbwio->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Load necessary JS and CSS
$PAGE->requires->css('/mod/dhbwio/styles.css');

// For map view
if ($tab == 'universities' && !empty($dhbwio->enablemap)) {
    // Load Leaflet and initialize map
    dhbwio_load_leaflet_map($cm->id);
}

// Start output
echo $OUTPUT->header();

// Display module name
//echo $OUTPUT->heading(format_string($dhbwio->name));

// Display intro if set
if (!empty($dhbwio->intro)) {
    echo $OUTPUT->box(format_module_intro('dhbwio', $dhbwio, $cm->id), 'generalbox mod_introbox', 'dhbwiointro');
}

// Create tabs for navigation
$tabs = [];

// Tab for viewing partner universities - available to all users
$tabs[] = new tabobject(
    'universities',
    new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'universities']),
    get_string('nav_universities', 'mod_dhbwio')
);

// Tab for experience reports - available to all users if enabled
if (!empty($dhbwio->enablereports)) {
    $tabs[] = new tabobject(
        'reports',
        new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'reports']),
        get_string('nav_reports', 'mod_dhbwio')
    );
}

// Tabs for IO staff
if (has_capability('mod/dhbwio:manageuniversities', $context)) {
    $tabs[] = new tabobject(
        'manageunis',
        new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'manageunis']),
        get_string('nav_manageunis', 'mod_dhbwio')
    );
}

if (has_capability('mod/dhbwio:viewreports', $context)) {
    $tabs[] = new tabobject(
        'statistics',
        new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'statistics']),
        get_string('nav_statistics', 'mod_dhbwio')
    );
}

if (has_capability('mod/dhbwio:managetemplates', $context)) {
    $tabs[] = new tabobject(
        'emailtemplates',
        new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'emailtemplates']),
        get_string('nav_emailtemplates', 'mod_dhbwio')
    );
}

echo $OUTPUT->tabtree($tabs, $tab);

// Display the content based on the selected tab
switch ($tab) {
    case 'universities':
        // Check if map view is enabled
        if (!empty($dhbwio->enablemap)) {
            // Get view parameter from URL
            $view = optional_param('view', 'map', PARAM_ALPHA);
            
            $renderer = $PAGE->get_renderer('mod_dhbwio');
            echo $renderer->render_university_view($dhbwio, $cm);
        } else {
            // Fallback to list view
            $renderer = $PAGE->get_renderer('mod_dhbwio');
            $renderer->display_universities_list($dhbwio, $cm);
        }
        break;
        
    case 'reports':
        if (empty($dhbwio->enablereports)) {
            // Reports are disabled, redirect to main view
            redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id]));
        }
        
        // Display reports
        if (!empty($dhbwio->enablereports)) {
            // Show add report button if student has permission
            if (has_capability('mod/dhbwio:submitreport', $context)) {
                $addurl = new moodle_url('/mod/dhbwio/report.php', [
                    'cmid' => $cm->id,
                    'action' => 'add'
                ]);
                
                echo '<div class="dhbwio-actions mb-4">';
                echo '<a href="' . $addurl . '" class="btn btn-primary">';
                echo get_string('add_report', 'mod_dhbwio') . '</a>';
                echo '</div>';
            }
            
            // Get all reports
            $reports = $DB->get_records('dhbwio_experience_reports', [
                'dhbwio' => $dhbwio->id,
                'visible' => 1
            ], 'timecreated DESC');
            
            if (empty($reports)) {
                echo $OUTPUT->notification(get_string('no_reports', 'mod_dhbwio'), 'info');
            } else {
                echo $OUTPUT->box_start('generalbox');
                
                foreach ($reports as $report) {
                    // Get university and student details
                    $university = $DB->get_record('dhbwio_universities', ['id' => $report->university_id]);
                    $student = $DB->get_record('user', ['id' => $report->userid]);
                    
                    if (!$university || !$student) {
                        continue;
                    }
                    
                    // Get country name
                    $countries = get_string_manager()->get_list_of_countries();
                    $countryName = isset($countries[$university->country]) ? $countries[$university->country] : $university->country;
                    
                    echo '<div class="card mb-4">';
                    echo '<div class="card-header">';
                    echo '<h3>' . format_string($report->title) . '</h3>';
                    
                    // Display university and author
                    echo '<p>';
                    
                    // Link to university detail page
                    $universityurl = new moodle_url('/mod/dhbwio/university.php', [
                        'cmid' => $cm->id,
                        'university' => $university->id
                    ]);
                    
                    echo '<a href="' . $universityurl . '">' . format_string($university->name) . '</a>';
                    echo ' (' . $countryName . ')';
                    echo ' | ' . get_string('by', 'mod_dhbwio') . ' ' . fullname($student);
                    echo ' | ' . userdate($report->timecreated);
                    
                    // Display rating if any
                    if (!empty($report->rating)) {
                        echo ' | ' . get_string('rating', 'mod_dhbwio') . ': ';
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $report->rating) {
                                echo '★';
                            } else {
                                echo '☆';
                            }
                        }
                    }
                    
                    echo '</p>';
                    echo '</div>'; // End card-header
                    
                    echo '<div class="card-body">';
                    echo format_text($report->content, $report->contentformat);
                    
                    // Show edit/delete actions if user is the author
                    if ($report->userid == $USER->id || has_capability('mod/dhbwio:manageuniversities', $context)) {
                        echo '<div class="dhbwio-actions mt-3">';
                        
                        $editurl = new moodle_url('/mod/dhbwio/report.php', [
                            'cmid' => $cm->id,
                            'action' => 'edit',
                            'report' => $report->id
                        ]);
                        
                        $deleteurl = new moodle_url('/mod/dhbwio/report.php', [
                            'cmid' => $cm->id,
                            'action' => 'delete',
                            'report' => $report->id,
                            'sesskey' => sesskey()
                        ]);
                        
                        echo '<a href="' . $editurl . '" class="btn btn-secondary btn-sm">';
                        echo get_string('edit', 'mod_dhbwio') . '</a> ';
                        
                        echo '<a href="' . $deleteurl . '" class="btn btn-danger btn-sm" onclick="return confirm(\'' .
                            get_string('delete_report_confirm', 'mod_dhbwio') . '\')">';
                        echo get_string('delete', 'mod_dhbwio') . '</a>';
                        
                        echo '</div>';
                    }
                    
                    echo '</div>'; // End card-body
                    echo '</div>'; // End card
                }
                
                echo $OUTPUT->box_end();
            }
        }
        break;
        
    case 'manageunis':
        // Check capability
        require_capability('mod/dhbwio:manageuniversities', $context);
        
        // Display management interface
        
        // Add university button
        $addurl = new moodle_url('/mod/dhbwio/university.php', [
            'cmid' => $cm->id,
            'action' => 'add'
        ]);
        
        echo '<div class="dhbwio-actions mb-4">';
        echo '<a href="' . $addurl . '" class="btn btn-primary">';
        echo get_string('add_university', 'mod_dhbwio') . '</a>';
        echo '</div>';
        
        // Get all universities
        $universities = $DB->get_records('dhbwio_universities', [
            'dhbwio' => $dhbwio->id
        ], 'country, name');
        
        if (empty($universities)) {
            echo $OUTPUT->notification(get_string('no_universities', 'mod_dhbwio'), 'info');
        } else {
            // Group universities by country
            $countries = get_string_manager()->get_list_of_countries();
            $countryGroups = [];
            
            foreach ($universities as $university) {
                $countryCode = $university->country;
                $countryName = isset($countries[$countryCode]) ? $countries[$countryCode] : $countryCode;
                
                if (!isset($countryGroups[$countryName])) {
                    $countryGroups[$countryName] = [];
                }
                $countryGroups[$countryName][] = $university;
            }
            
            echo $OUTPUT->box_start('generalbox');
            
            foreach ($countryGroups as $countryName => $unis) {
                echo '<h3>' . $countryName . '</h3>';
                
                // Start table
                $table = new html_table();
                $table->head = [
                    get_string('university_name', 'mod_dhbwio'),
                    get_string('university_city', 'mod_dhbwio'),
                    get_string('university_available_slots', 'mod_dhbwio'),
                    get_string('university_active', 'mod_dhbwio'),
                    get_string('actions', 'mod_dhbwio')
                ];
                $table->attributes['class'] = 'table table-striped table-hover';
                
                foreach ($unis as $university) {
                    // Create action links
                    $editurl = new moodle_url('/mod/dhbwio/university.php', [
                        'cmid' => $cm->id,
                        'action' => 'edit',
                        'university' => $university->id
                    ]);
                    
                    $deleteurl = new moodle_url('/mod/dhbwio/university.php', [
                        'cmid' => $cm->id,
                        'action' => 'delete',
                        'university' => $university->id,
                        'sesskey' => sesskey()
                    ]);
                    
                    $viewurl = new moodle_url('/mod/dhbwio/university.php', [
                        'cmid' => $cm->id,
                        'university' => $university->id
                    ]);
                    
                    // Build actions column
                    $actions = html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit')));
                    $actions .= '&nbsp;';
                    $actions .= html_writer::link($deleteurl, $OUTPUT->pix_icon('t/delete', get_string('delete')),
                                                ['onclick' => 'return confirm("' . get_string('delete_university_confirm', 'mod_dhbwio') . '")']);
                    $actions .= '&nbsp;';
                    $actions .= html_writer::link($viewurl, $OUTPUT->pix_icon('i/preview', get_string('view')));
                    
                    // Active status display
                    $activestatus = $university->active ? 
                                    $OUTPUT->pix_icon('i/checked', get_string('yes')) : 
                                    $OUTPUT->pix_icon('i/unchecked', get_string('no'));
                    
                    // Add table row
                    $table->data[] = [
                        format_string($university->name),
                        format_string($university->city),
                        $university->available_slots,
                        $activestatus,
                        $actions
                    ];
                }
                
                echo html_writer::table($table);
            }
            
            echo $OUTPUT->box_end();
        }
        break;
        
    case 'statistics':
        // Check capability
        require_capability('mod/dhbwio:viewreports', $context);
        echo $OUTPUT->notification("Statistics feature is coming soon", 'info');
        break;
        
    case 'emailtemplates':
        // Check capability
        require_capability('mod/dhbwio:managetemplates', $context);
        echo $OUTPUT->notification("Email templates feature is coming soon", 'info');
        break;
        
    default:
        // Default to universities view
        $renderer = $PAGE->get_renderer('mod_dhbwio');
        $renderer->display_universities_list($dhbwio, $cm);
        break;
}

// Finish the page
echo $OUTPUT->footer();