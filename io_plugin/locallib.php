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
 * Internal library of functions for module dhbwio.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

/**
 * Display list of partner universities.
 *
 * @param object $dhbwio DHBW IO instance.
 * @param object $cm Course module.
 */
function dhbwio_display_universities_list($dhbwio, $cm) {
    global $DB, $OUTPUT;
    
    $context = context_module::instance($cm->id);
    
    // Get all active universities
    $universities = $DB->get_records('dhbwio_universities', [
        'dhbwio' => $dhbwio->id,
        'active' => 1
    ], 'country, name');
    
    // Group universities by country
    $countries = [];
    foreach ($universities as $university) {
        if (!isset($countries[$university->country])) {
            $countries[$university->country] = [];
        }
        $countries[$university->country][] = $university;
    }
    
    // Display universities grouped by country
    echo $OUTPUT->box_start('generalbox');
    
    // Display switcher between list and map view if enabled
    if (!empty($dhbwio->enablemap)) {
        $listurl = new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'universities', 'view' => 'list']);
        $mapurl = new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'universities', 'view' => 'map']);
        
        echo '<div class="dhbwio-view-switcher">';
        echo '<a href="'.$listurl.'" class="btn btn-secondary active">'.get_string('list_view', 'mod_dhbwio').'</a>';
        echo '<a href="'.$mapurl.'" class="btn btn-secondary">'.get_string('map_view', 'mod_dhbwio').'</a>';
        echo '</div>';
    }
    
    if (empty($universities)) {
        echo $OUTPUT->notification(get_string('no_universities', 'mod_dhbwio'), 'info');
    } else {
        foreach ($countries as $country => $unis) {
            echo '<h3>'.$country.'</h3>';
            echo '<div class="dhbwio-university-grid">';
            
            foreach ($unis as $university) {
                $detailurl = new moodle_url('/mod/dhbwio/university.php', [
                    'cmid' => $cm->id,
                    'university' => $university->id
                ]);
                
                echo '<div class="dhbwio-university-card">';
                echo '<h4><a href="'.$detailurl.'">'.$university->name.'</a></h4>';
                echo '<div class="dhbwio-university-info">';
                echo '<p><strong>'.get_string('university_city', 'mod_dhbwio').':</strong> '.$university->city.'</p>';
                
                // Display university slots
                echo '<p><strong>'.get_string('university_available_slots', 'mod_dhbwio').':</strong> ';
                echo $university->available_slots.'</p>';
                
                // Get experience reports count
                $reportscount = $DB->count_records('dhbwio_experience_reports', [
                    'university_id' => $university->id,
                    'visible' => 1
                ]);
                
                if ($reportscount > 0) {
                    echo '<p><span class="badge badge-info">'.$reportscount.' ';
                    echo get_string('reports', 'mod_dhbwio').'</span></p>';
                }
                
                echo '</div>';
                echo '<a href="'.$detailurl.'" class="btn btn-primary btn-sm">';
                echo get_string('view_details', 'mod_dhbwio').'</a>';
                echo '</div>';
            }
            
            echo '</div>';
        }
    }
    
    echo $OUTPUT->box_end();
}

/**
 * Display experience reports for a specific dhbwio instance or university.
 *
 * @param object $dhbwio DHBW IO instance.
 * @param object $cm Course module.
 * @param int $universityid Optional university ID to filter reports by.
 */
function dhbwio_display_experience_reports($dhbwio, $cm, $universityid = null) {
    global $DB, $OUTPUT, $USER;
    
    $context = context_module::instance($cm->id);
    
    // Build query conditions
    $conditions = [
        'dhbwio' => $dhbwio->id,
        'visible' => 1
    ];
    
    if ($universityid) {
        $conditions['university_id'] = $universityid;
    }
    
    // Get reports
    $reports = $DB->get_records('dhbwio_experience_reports', $conditions, 'timecreated DESC');
    
    // Show add report button if student has permission
    $showaddbutton = false;
    if (has_capability('mod/dhbwio:submitreport', $context)) {
        $showaddbutton = true;
        $addurl = new moodle_url('/mod/dhbwio/report.php', [
            'id' => $cm->id,
            'action' => 'add'
        ]);
        
        echo '<div class="dhbwio-actions mb-4">';
        echo '<a href="' . $addurl . '" class="btn btn-primary">';
        echo get_string('add_report', 'mod_dhbwio') . '</a>';
        echo '</div>';
    }
    
    if (empty($reports)) {
        echo $OUTPUT->notification(get_string('no_reports', 'mod_dhbwio'), 'info');
        return;
    }
    
    echo $OUTPUT->box_start('generalbox');
    
    foreach ($reports as $report) {
        // Get university and student details
        $university = $DB->get_record('dhbwio_universities', ['id' => $report->university_id]);
        $student = $DB->get_record('user', ['id' => $report->userid]);
        
        if (!$university || !$student) {
            continue;
        }
        
        echo '<div class="dhbwio-report card mb-4">';
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
        echo ' (' . $university->country . ')';
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
                'id' => $cm->id,
                'action' => 'edit',
                'report' => $report->id
            ]);
            
            $deleteurl = new moodle_url('/mod/dhbwio/report.php', [
                'id' => $cm->id,
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

/**
 * Display management interface for partner universities.
 *
 * @param object $dhbwio DHBW IO instance.
 * @param object $cm Course module.
 */
function dhbwio_display_manage_universities($dhbwio, $cm) {
    global $DB, $OUTPUT;
    
    $context = context_module::instance($cm->id);
    
    // Add university button
    $addurl = new moodle_url('/mod/dhbwio/university.php', [
        'cmid' => $cm->id,
        'action' => 'add'
    ]);
    
    echo '<div class="dhbwio-actions mb-4">';
    echo '<a href="' . $addurl . '" class="btn btn-primary">';
    echo get_string('add_university', 'mod_dhbwio') . '</a>';
    echo '</div>';
    
    // Get all universities for this instance
    $universities = $DB->get_records('dhbwio_universities', [
        'dhbwio' => $dhbwio->id
    ], 'country, name');
    
    if (empty($universities)) {
        echo $OUTPUT->notification(get_string('no_universities', 'mod_dhbwio'), 'info');
        return;
    }
    
    // Group universities by country
    $countries = [];
    foreach ($universities as $university) {
        if (!isset($countries[$university->country])) {
            $countries[$university->country] = [];
        }
        $countries[$university->country][] = $university;
    }
    
    // Display universities table
    echo $OUTPUT->box_start('generalbox');
    
    foreach ($countries as $country => $unis) {
        echo '<h3>' . $country . '</h3>';
        
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

/**
 * Display statistics and visualizations.
 *
 * @param object $dhbwio DHBW IO instance.
 * @param object $cm Course module.
 */
function dhbwio_display_statistics($dhbwio, $cm) {
    global $DB, $OUTPUT, $PAGE;
    
    $context = context_module::instance($cm->id);
    
    // Load required JS for charts
    $PAGE->requires->js_call_amd('mod_dhbwio/statistics', 'init', [$cm->id]);
    
    echo $OUTPUT->box_start('generalbox');
    
    // Create statistics grid with 4 cards
    echo '<div class="container-fluid px-0">';
    echo '<div class="row">';
    
    // Card 1: Total Universities
    echo '<div class="col-md-6 col-lg-3 mb-4">';
    echo '<div class="card h-100">';
    echo '<div class="card-header bg-primary text-white">';
    echo '<h4 class="m-0">' . get_string('stats_universities_total', 'mod_dhbwio') . '</h4>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<div id="total-universities-stat" class="dhbwio-stat-number text-center"></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Card 2: Universities per Country (will contain a chart)
    echo '<div class="col-md-6 col-lg-3 mb-4">';
    echo '<div class="card h-100">';
    echo '<div class="card-header bg-success text-white">';
    echo '<h4 class="m-0">' . get_string('stats_universities_per_country', 'mod_dhbwio') . '</h4>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<canvas id="countries-chart" height="200"></canvas>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Card 3: Total Experience Reports
    echo '<div class="col-md-6 col-lg-3 mb-4">';
    echo '<div class="card h-100">';
    echo '<div class="card-header bg-info text-white">';
    echo '<h4 class="m-0">' . get_string('stats_reports_total', 'mod_dhbwio') . '</h4>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<div id="total-reports-stat" class="dhbwio-stat-number text-center"></div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Card 4: Capacity Utilization (will contain a chart)
    echo '<div class="col-md-6 col-lg-3 mb-4">';
    echo '<div class="card h-100">';
    echo '<div class="card-header bg-warning">';
    echo '<h4 class="m-0">' . get_string('stats_capacity_utilization', 'mod_dhbwio') . '</h4>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<canvas id="capacity-chart" height="200"></canvas>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // End row
    
    // Reports per University - Larger chart
    echo '<div class="row mt-4">';
    echo '<div class="col-12">';
    echo '<div class="card">';
    echo '<div class="card-header bg-secondary text-white">';
    echo '<h4 class="m-0">' . get_string('stats_reports_per_university', 'mod_dhbwio') . '</h4>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<canvas id="reports-chart" height="300"></canvas>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>'; // End row
    
    echo '</div>'; // End container-fluid
    
    echo $OUTPUT->box_end();
}

/**
 * Display email template management interface.
 *
 * @param object $dhbwio DHBW IO instance.
 * @param object $cm Course module.
 */
function dhbwio_display_email_templates($dhbwio, $cm) {
    global $DB, $OUTPUT;
    
    $context = context_module::instance($cm->id);
    
    // Add template button
    $addurl = new moodle_url('/mod/dhbwio/email_template.php', [
        'id' => $cm->id,
        'action' => 'add'
    ]);
    
    echo '<div class="dhbwio-actions mb-4">';
    echo '<a href="' . $addurl . '" class="btn btn-primary">';
    echo get_string('email_template_add', 'mod_dhbwio') . '</a>';
    echo '</div>';
    
    // Display filter for language
    echo '<div class="dhbwio-filters mb-4">';
    echo '<form method="get" action="" class="form-inline">';
    echo '<input type="hidden" name="id" value="' . $cm->id . '">';
    echo '<input type="hidden" name="tab" value="emailtemplates">';
    
    // Language filter
    echo '<div class="form-group mr-2">';
    echo '<label for="lang-filter" class="mr-2">' . get_string('language') . ':</label>';
    echo '<select name="lang" id="lang-filter" class="form-control">';
    echo '<option value="">' . get_string('all') . '</option>';
    echo '<option value="en">' . get_string('en', 'core_langconfig') . '</option>';
    echo '<option value="de">' . get_string('de', 'core_langconfig') . '</option>';
    echo '</select>';
    echo '</div>';
    
    // Submit button
    echo '<button type="submit" class="btn btn-secondary">' . get_string('filter', 'mod_dhbwio') . '</button>';
    echo '&nbsp;<a href="' . new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'emailtemplates']) . 
         '" class="btn btn-link">' . get_string('reset', 'mod_dhbwio') . '</a>';
    echo '</form>';
    echo '</div>';
    
    // Get selected language filter
    $langfilter = optional_param('lang', '', PARAM_ALPHA);
    
    // Get templates based on filter
    $params = ['dhbwio' => $dhbwio->id];
    if (!empty($langfilter)) {
        $params['lang'] = $langfilter;
    }
    
    $templates = $DB->get_records('dhbwio_email_templates', $params, 'type, lang');
    
    if (empty($templates)) {
        echo $OUTPUT->notification(get_string('no_templates', 'mod_dhbwio'), 'info');
        return;
    }
    
    // Display templates table
    echo $OUTPUT->box_start('generalbox');
    
    // Start table
    $table = new html_table();
    $table->head = [
        get_string('email_template_name', 'mod_dhbwio'),
        get_string('email_template_type', 'mod_dhbwio'),
        get_string('language'),
        get_string('email_template_subject', 'mod_dhbwio'),
        get_string('email_template_enabled', 'mod_dhbwio'),
        get_string('actions', 'mod_dhbwio')
    ];
    $table->attributes['class'] = 'table table-striped table-hover';
    
    foreach ($templates as $template) {
        // Create action links
        $editurl = new moodle_url('/mod/dhbwio/email_template.php', [
            'id' => $cm->id,
            'action' => 'edit',
            'template' => $template->id
        ]);
        
        $deleteurl = new moodle_url('/mod/dhbwio/email_template.php', [
            'id' => $cm->id,
            'action' => 'delete',
            'template' => $template->id,
            'sesskey' => sesskey()
        ]);
        
        // Build actions column
        $actions = html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit')));
        $actions .= '&nbsp;';
        $actions .= html_writer::link($deleteurl, $OUTPUT->pix_icon('t/delete', get_string('delete')),
                                     ['onclick' => 'return confirm("' . get_string('delete_template_confirm', 'mod_dhbwio') . '")']);
        
        // Type label with translation
        $typelabel = get_string('email_template_' . $template->type, 'mod_dhbwio');
        
        // Language name
        $langname = get_string($template->lang, 'core_langconfig');
        
        // Enabled status display
        $enabledstatus = $template->enabled ? 
                        $OUTPUT->pix_icon('i/checked', get_string('yes')) : 
                        $OUTPUT->pix_icon('i/unchecked', get_string('no'));
        
        // Add table row
        $table->data[] = [
            format_string($template->name),
            $typelabel,
            $langname,
            format_string($template->subject),
            $enabledstatus,
            $actions
        ];
    }
    
    echo html_writer::table($table);
    
    // Add template variable documentation
    echo '<div class="mt-4 card">';
    echo '<div class="card-header">';
    echo '<h3>' . get_string('template_variables', 'mod_dhbwio') . '</h3>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<p>' . get_string('template_variables_info', 'mod_dhbwio') . '</p>';
    
    echo '<table class="table table-sm">';
    echo '<thead><tr><th>' . get_string('variable', 'mod_dhbwio') . '</th><th>' . 
         get_string('description', 'mod_dhbwio') . '</th></tr></thead>';
    echo '<tbody>';
    
    $variables = [
        'STUDENT_NAME' => get_string('var_student_name', 'mod_dhbwio'),
        'UNIVERSITY_NAME' => get_string('var_university_name', 'mod_dhbwio'),
        'REPORT_TITLE' => get_string('var_report_title', 'mod_dhbwio'),
        'REPORT_DATE' => get_string('var_report_date', 'mod_dhbwio')
    ];
    
    foreach ($variables as $var => $desc) {
        echo '<tr><td><code>{' . $var . '}</code></td><td>' . $desc . '</td></tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>'; // End card-body
    echo '</div>'; // End card
    
    echo $OUTPUT->box_end();
}

/**
 * Get CSS class for status badge.
 *
 * @param string $status Status string.
 * @return string CSS class.
 */
function dhbwio_get_status_class($status) {
    switch ($status) {
        case 'success':
        case 'approved':
            return 'success';
        case 'pending':
            return 'warning';
        case 'rejected':
            return 'danger';
        default:
            return 'secondary';
    }
}

/**
 * Loads the Leaflet library and initializes the map.
 *
 * @param int $cmid Course module ID.
 * @return void
 */
function dhbwio_load_leaflet_map($cmid) {
    global $PAGE, $CFG;
    
    // Pfad zur Bibliothek
    $leafletpath = $CFG->dirroot . '/mod/dhbwio/thirdparty/leaflet';
    $leafleturl = $CFG->wwwroot . '/mod/dhbwio/thirdparty/leaflet';
    
    // CSS einbinden
    $PAGE->requires->css(new moodle_url($leafleturl . '/leaflet.css'));
    
    // JS einbinden (nicht-AMD Methode)
    $PAGE->requires->js(new moodle_url($leafleturl . '/leaflet.js'), true);

    // Nach dem Laden von Leaflet das AMD-Modul initialisieren
    $PAGE->requires->js_call_amd('mod_dhbwio/university_map', 'init', [$cmid]);
}