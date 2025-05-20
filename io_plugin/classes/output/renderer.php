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
 * Renderer for the DHBW International Office module.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dhbwio\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use moodle_page;
use html_writer;
use renderable;
use stdClass;

/**
 * Renderer class for mod_dhbwio
 */
class renderer extends plugin_renderer_base {
    
    /**
 * Render the university view with map and list toggle
 *
 * @param object $dhbwio The dhbwio instance
 * @param object $cm The course module
 * @return string HTML output
 */
public function render_university_view($dhbwio, $cm) {
    global $DB, $PAGE;
    
    // Check if map is enabled
    if (empty($dhbwio->enablemap)) {
        // Just show the list if map is disabled
        return $this->render_universities_list($dhbwio, $cm);
    }
    
    // Determine current view from URL parameter
    $view = optional_param('view', 'map', PARAM_ALPHA);
    $map_active = ($view === 'map');
    
    // Prepare data for university_view template
    $data = new \stdClass();
    
    // View switcher URLs
    $data->list_url = new \moodle_url('/mod/dhbwio/view.php', 
        ['id' => $cm->id, 'tab' => 'universities', 'view' => 'list']);
    $data->map_url = new \moodle_url('/mod/dhbwio/view.php', 
        ['id' => $cm->id, 'tab' => 'universities', 'view' => 'map']);
    
    // Localized strings
    $data->list_view_text = get_string('list_view', 'mod_dhbwio');
    $data->map_view_text = get_string('map_view', 'mod_dhbwio');
    
    // Set active view
    $data->map_active = $map_active;
    
    // Get universities list
    ob_start();
    $this->display_universities_list($dhbwio, $cm);
    $data->universities_list = ob_get_clean();
    
    // Render template
    return $this->render_from_template('mod_dhbwio/university_view', $data);
}
    
    /**
     * Render list of partner universities.
     *
     * @param object $dhbwio DHBW IO instance.
     * @param object $cm Course module.
     * @return string HTML output
     */
    public function render_universities_list($dhbwio, $cm) {
        global $DB, $OUTPUT;
        
        // Get all active universities
        $universities = $DB->get_records('dhbwio_universities', [
            'dhbwio' => $dhbwio->id,
            'active' => 1
        ], 'country, name');
        
        $countries = get_string_manager()->get_list_of_countries();
        
        // Prepare data for universities_list template
        $data = new \stdClass();
        
        $data->has_universities = !empty($universities);
        $data->no_universities_message = $OUTPUT->notification(
            get_string('no_universities', 'mod_dhbwio'), 'info');
        
        if (!empty($universities)) {
            // Group universities by country
            $countryGroups = [];
            foreach ($universities as $university) {
                $countryCode = $university->country;
                $countryName = isset($countries[$countryCode]) ? $countries[$countryCode] : $countryCode;
                
                if (!isset($countryGroups[$countryName])) {
                    $countryGroups[$countryName] = [];
                }
                $countryGroups[$countryName][] = $university;
            }
            
            // Format data for template
            $data->country_groups = [];
            foreach ($countryGroups as $countryName => $unis) {
                $countryGroup = new \stdClass();
                $countryGroup->country_name = $countryName;
                $countryGroup->universities = [];
                
                foreach ($unis as $university) {
                    $uniObj = new \stdClass();
                    $uniObj->html = $this->render_university_card($university, $cm, $countries);
                    $countryGroup->universities[] = $uniObj;
                }
                
                $data->country_groups[] = $countryGroup;
            }
        }
        
        // Wrap the output in a generalbox
        $output = $this->render_from_template('mod_dhbwio/universities_list', $data);
        return $OUTPUT->box($output, 'generalbox');
    }
    
    /**
     * Display list of partner universities.
     * This is a version of the function that directly outputs HTML (for backward compatibility)
     *
     * @param object $dhbwio DHBW IO instance.
     * @param object $cm Course module.
     */
    public function display_universities_list($dhbwio, $cm) {
        echo $this->render_universities_list($dhbwio, $cm);
    }
    
    /**
     * Render a university card
     *
     * @param object $university The university record
     * @param object $cm The course module
     * @param array $countries Optional array of country codes and names
     * @return string HTML output
     */
    public function render_university_card($university, $cm, $countries = null) {
        global $DB;
        
        // Get country list if not provided
        if ($countries === null) {
            $countries = get_string_manager()->get_list_of_countries();
        }
        
        // Prepare data for university_card template
        $data = new \stdClass();
        
        // Basic university info
        $data->name = format_string($university->name);
        $data->city = format_string($university->city);
        $data->country_name = isset($countries[$university->country]) ? 
            format_string($countries[$university->country]) : format_string($university->country);
        $data->available_slots = $university->available_slots;
        
        // URL
        $data->detail_url = (new \moodle_url('/mod/dhbwio/university.php', [
            'id' => $cm->id,
            'university' => $university->id
        ]))->out(false);
        
        // Experience reports
        $reportscount = $DB->count_records('dhbwio_experience_reports', [
            'university_id' => $university->id,
            'visible' => 1
        ]);
        $data->has_reports = ($reportscount > 0);
        $data->reports_count = $reportscount;
        
        // Labels
        $data->city_label = get_string('university_city', 'mod_dhbwio');
        $data->country_label = get_string('university_country', 'mod_dhbwio');
        $data->slots_label = get_string('university_available_slots', 'mod_dhbwio');
        $data->reports_label = get_string('reports', 'mod_dhbwio');
        $data->view_details_text = get_string('view_details', 'mod_dhbwio');
        
        // Render template
        return $this->render_from_template('mod_dhbwio/university_card', $data);
    }
}