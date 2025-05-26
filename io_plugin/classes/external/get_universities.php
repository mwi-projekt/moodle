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
 * External web service to get universities data for map.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dhbwio\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;

/**
 * External service for getting universities data.
 */
class get_universities extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID')
        ]);
    }

    /**
     * Get universities data for map visualization.
     *
     * @param int $cmid Course module ID
     * @return array with status and universities data
     */
    public static function execute($cmid) {
        global $DB, $USER;

        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        $cmid = $params['cmid'];

        // Context validation
        $cm = get_coursemodule_from_id('dhbwio', $cmid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        // Check capability
        require_capability('mod/dhbwio:view', $context);

        // Get dhbwio instance
        $dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

        // Get all active universities with coordinates
        $sql = "SELECT u.*
                FROM {dhbwio_universities} u
                WHERE u.dhbwio = ? AND u.active = 1
                AND u.latitude IS NOT NULL AND u.longitude IS NOT NULL
                ORDER BY u.country, u.name";
        
        $universities = $DB->get_records_sql($sql, [$dhbwio->id]);
        
        $result = [];
        
        foreach ($universities as $university) {
            // Get experience reports count
            $reportscount = $DB->count_records('dhbwio_experience_reports', [
                'university_id' => $university->id,
                'visible' => 1
            ]);
            
            // Get country name from ISO code
            $countries = get_string_manager()->get_list_of_countries();
            $countryName = isset($countries[$university->country]) ? $countries[$university->country] : $university->country;
            
            // Prepare university data
            $universitydata = [
                'id' => $university->id,
                'name' => $university->name,
                'country' => $countryName,
                'country_code' => $university->country,
                'city' => $university->city,
                'latitude' => $university->latitude,
                'longitude' => $university->longitude,
                'available_slots' => $university->available_slots,
                'reports_count' => $reportscount,
                'detail_url' => (new \moodle_url('/mod/dhbwio/university.php', [
                    'cmid' => $cmid,
                    'university' => $university->id
                ]))->out(false)
            ];
            
            $result[] = $universitydata;
        }

        return [
            'status' => true,
            'universities' => $result
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status'),
            'universities' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'University ID'),
                    'name' => new external_value(PARAM_TEXT, 'University name'),
                    'country' => new external_value(PARAM_TEXT, 'Country name'),
                    'country_code' => new external_value(PARAM_TEXT, 'Country ISO code'),
                    'city' => new external_value(PARAM_TEXT, 'City'),
                    'latitude' => new external_value(PARAM_FLOAT, 'Latitude'),
                    'longitude' => new external_value(PARAM_FLOAT, 'Longitude'),
                    'available_slots' => new external_value(PARAM_INT, 'Available slots'),
                    'reports_count' => new external_value(PARAM_INT, 'Experience reports count'),
                    'detail_url' => new external_value(PARAM_URL, 'URL to university detail page')
                ])
            )
        ]);
    }
}