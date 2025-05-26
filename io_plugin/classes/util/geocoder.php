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
 * Geocoder utility for DHBW International Office.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dhbwio\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Geocoder utility class that interfaces with various geocoding providers.
 */
class geocoder {
    /** @var string The geocoding service provider */
    protected $provider;
    
    /** @var string API key for the geocoding service */
    protected $apikey;
    
    /** @var array Supported geocoding providers */
    protected static $supported_providers = [
        'nominatim' => 'Nominatim (OpenStreetMap)',
        'google' => 'Google Maps',
        'mapbox' => 'Mapbox'
    ];
    
    /**
     * Constructor.
     *
     * @param string $provider The geocoding provider to use
     * @param string $apikey API key for the provider (if required)
     */
    public function __construct($provider = 'nominatim', $apikey = '') {
        $this->provider = $provider;
        $this->apikey = $apikey;
    }
    
    /**
     * Get list of supported geocoding providers.
     *
     * @return array Associative array of provider => name
     */
    public static function get_providers() {
        return self::$supported_providers;
    }
    
    /**
     * Geocode an address to coordinates.
     *
     * @param string $address The address to geocode
     * @return object|false Object with latitude and longitude if successful, false otherwise
     */
    public function geocode($address) {
        // Format address for URL.
        $formatted_address = urlencode($address);
        
        // Use appropriate geocoding service.
        switch ($this->provider) {
            case 'nominatim':
                return $this->geocode_nominatim($formatted_address);
            case 'google':
                return $this->geocode_google($formatted_address);
            case 'mapbox':
                return $this->geocode_mapbox($formatted_address);
            default:
                // Default to Nominatim.
                return $this->geocode_nominatim($formatted_address);
        }
    }
    
    /**
     * Geocode with Nominatim (OpenStreetMap).
     *
     * @param string $address URL encoded address
     * @return object|false Object with coordinates if successful, false otherwise
     */
    protected function geocode_nominatim($address) {
        // Nominatim requires a user agent identifying the application.
        $options = [
            'http' => [
                'user_agent' => 'DHBW International Office Moodle Plugin',
                'timeout' => 10
            ]
        ];
        $context = stream_context_create($options);
        
        // Build the request URL.
        $url = "https://nominatim.openstreetmap.org/search?q={$address}&format=json&limit=1";
        
        // Make the request.
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new \moodle_exception('geocoding_error', 'mod_dhbwio');
        }
        
        $data = json_decode($response);
        
        // Check if we got results.
        if (empty($data) || count($data) === 0) {
            return false;
        }
        
        // Extract coordinates.
        $result = new \stdClass();
        $result->latitude = (float)$data[0]->lat;
        $result->longitude = (float)$data[0]->lon;
        
        return $result;
    }
    
    /**
     * Geocode with Google Maps API.
     *
     * @param string $address URL encoded address
     * @return object|false Object with coordinates if successful, false otherwise
     */
    protected function geocode_google($address) {
        if (empty($this->apikey)) {
            throw new \moodle_exception('geocoding_missing_api_key', 'mod_dhbwio');
        }
        
        // Build the request URL.
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$this->apikey}";
        
        // Make the request.
        $response = file_get_contents($url);
        
        if ($response === false) {
            throw new \moodle_exception('geocoding_error', 'mod_dhbwio');
        }
        
        $data = json_decode($response);
        
        // Check response status.
        if ($data->status !== 'OK') {
            throw new \moodle_exception('geocoding_api_error', 'mod_dhbwio', '', $data->status);
        }
        
        // Extract coordinates.
        $result = new \stdClass();
        $result->latitude = (float)$data->results[0]->geometry->location->lat;
        $result->longitude = (float)$data->results[0]->geometry->location->lng;
        
        return $result;
    }
    
    /**
     * Geocode with Mapbox API.
     *
     * @param string $address URL encoded address
     * @return object|false Object with coordinates if successful, false otherwise
     */
    protected function geocode_mapbox($address) {
        if (empty($this->apikey)) {
            throw new \moodle_exception('geocoding_missing_api_key', 'mod_dhbwio');
        }
        
        // Build the request URL.
        $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$address}.json?access_token={$this->apikey}&limit=1";
        
        // Make the request.
        $response = file_get_contents($url);
        
        if ($response === false) {
            throw new \moodle_exception('geocoding_error', 'mod_dhbwio');
        }
        
        $data = json_decode($response);
        
        // Check if we got results.
        if (empty($data->features) || count($data->features) === 0) {
            return false;
        }
        
        // Extract coordinates.
        $result = new \stdClass();
        $result->longitude = (float)$data->features[0]->center[0]; // Mapbox returns [lon, lat]
        $result->latitude = (float)$data->features[0]->center[1];
        
        return $result;
    }
}