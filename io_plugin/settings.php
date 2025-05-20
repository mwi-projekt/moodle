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

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('mod_dhbwio_settings', 
        get_string('pluginname', 'mod_dhbwio')));
    
    $settings = new admin_settingpage('mod_dhbwio_general', 
        get_string('general_settings', 'mod_dhbwio'));
    $ADMIN->add('mod_dhbwio_settings', $settings);
    
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
    
    // Geocoding Provider
    $providers = \mod_dhbwio\util\geocoder::get_providers();
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
    
    // Email templates page link
    $ADMIN->add('mod_dhbwio_settings', new admin_externalpage(
        'mod_dhbwio_email_templates',
        get_string('email_templates', 'mod_dhbwio'),
        new moodle_url('/local/dhbwio/email_templates.php')
    ));
    
    // University management page link
    $ADMIN->add('mod_dhbwio_settings', new admin_externalpage(
        'mod_dhbwio_university_admin',
        get_string('university_management', 'mod_dhbwio'),
        new moodle_url('/local/dhbwio/university.php')
    ));
    
    // Reports page link
    $ADMIN->add('mod_dhbwio_settings', new admin_externalpage(
        'mod_dhbwio_reports',
        get_string('reports', 'mod_dhbwio'),
        new moodle_url('/local/dhbwio/reports.php')
    ));
}