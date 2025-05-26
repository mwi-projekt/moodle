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
 * Settings for DHBW International Office plugin
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    
    // General settings heading
    $settings->add(new admin_setting_heading('mod_dhbwio/general',
        get_string('general_settings', 'mod_dhbwio'),
        get_string('general_settings_desc', 'mod_dhbwio')));
    
    // Email notification settings
    $settings->add(new admin_setting_configcheckbox(
        'mod_dhbwio/enable_email_notifications',
        get_string('enable_email_notifications', 'mod_dhbwio'),
        get_string('enable_email_notifications_desc', 'mod_dhbwio'),
        1
    ));

    // Geocoding settings
    $settings->add(new admin_setting_heading('mod_dhbwio/geocoding',
        get_string('geocoding_settings', 'mod_dhbwio'),
        get_string('geocoding_settings_desc', 'mod_dhbwio')));
    
    // Check if the geocoder class exists before using it
    if (class_exists('\mod_dhbwio\util\geocoder')) {
        $providers = \mod_dhbwio\util\geocoder::get_providers();
    } else {
        $providers = [
            'nominatim' => 'OpenStreetMap/Nominatim (Free)',
            'google' => 'Google Maps (API Key required)',
            'mapbox' => 'Mapbox (API Key required)'
        ];
    }
    
    // Geocoding Provider
    $settings->add(new admin_setting_configselect('mod_dhbwio/geocoding_provider',
        get_string('geocoding_provider', 'mod_dhbwio'),
        get_string('geocoding_provider_desc', 'mod_dhbwio'),
        'nominatim', // Default provider (OpenStreetMap/Nominatim)
        $providers));
    
    // API Key (for providers that need it)
    $settings->add(new admin_setting_configtext('mod_dhbwio/geocoding_api_key',
        get_string('geocoding_api_key', 'mod_dhbwio'),
        get_string('geocoding_api_key_desc', 'mod_dhbwio'),
        '', PARAM_TEXT));
}
