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
 * External service for geocoding addresses in DHBW International Office.
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
use external_value;
use external_single_structure;

/**
 * External service for geocoding addresses.
 */
class geocode_address extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'address' => new external_value(PARAM_TEXT, 'Full address to geocode')
        ]);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the geocoding was successful'),
            'latitude' => new external_value(PARAM_FLOAT, 'Latitude coordinate', VALUE_OPTIONAL),
            'longitude' => new external_value(PARAM_FLOAT, 'Longitude coordinate', VALUE_OPTIONAL),
            'message' => new external_value(PARAM_TEXT, 'Status message', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Geocode the provided address using an external service.
     *
     * @param string $address Full address to geocode
     * @return array Result with coordinates or error message
     */
    public static function execute($address) {
        global $CFG;

        // Parameter validation.
        $params = self::validate_parameters(self::execute_parameters(), ['address' => $address]);
        
        // Get geocoding API configuration.
        $apikey = get_config('mod_dhbwio', 'geocoding_api_key');
        $provider = get_config('mod_dhbwio', 'geocoding_provider');
        
        // Create geocoder helper object.
        $geocoder = new \mod_dhbwio\util\geocoder($provider, $apikey);
        
        try {
            // Perform geocoding.
            $result = $geocoder->geocode($params['address']);
            
            if ($result) {
                return [
                    'success' => true,
                    'latitude' => $result->latitude,
                    'longitude' => $result->longitude,
                    'message' => get_string('geocoding_success', 'mod_dhbwio')
                ];
            } else {
                return [
                    'success' => false,
                    'message' => get_string('geocoding_no_results', 'mod_dhbwio')
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}