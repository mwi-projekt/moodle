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

/**
 * Generate DataForm overview link for email templates.
 *
 * @param int $dhbwio_id DHBW IO instance ID
 * @return string Generated URL or empty string if not configured
 */
function dhbwio_generate_dataform_overview_link($dhbwio_id) {
    global $DB, $CFG;

    $dhbwio = $DB->get_record('dhbwio', ['id' => $dhbwio_id], 'dataform_id, dataform_overview_view_id');
    
    if (!$dhbwio || !$dhbwio->dataform_id || !$dhbwio->dataform_overview_view_id) {
        return '';
    }

    // Get the course module for the DataForm
    $cm = get_coursemodule_from_instance('dataform', null, 0, false, IGNORE_MISSING);
    if (!$cm) {
        return '';
    }

    $params = [
        'id' => $dhbwio->dataform_id,
        'view' => $dhbwio->dataform_overview_view_id
    ];

    return new moodle_url('/mod/dataform/view.php', $params);
}

/**
 * Generate DataForm entry link for email templates.
 *
 * @param int $dhbwio_id DHBW IO instance ID
 * @param int $entry_id DataForm entry ID
 * @return string Generated URL or empty string if not configured
 */
function dhbwio_generate_dataform_entry_link($dhbwio_id, $entry_id) {
    global $DB, $CFG;

    if (!$entry_id) {
        return '';
    }

    $dhbwio = $DB->get_record('dhbwio', ['id' => $dhbwio_id], 'dataform_id, dataform_entry_view_id');
    
    if (!$dhbwio || !$dhbwio->dataform_id || !$dhbwio->dataform_entry_view_id) {
        return '';
    }

    $params = [
        'id' => $dhbwio->dataform_id,
        'view' => $dhbwio->dataform_entry_view_id,
        'eids' => $entry_id
    ];

    return new moodle_url('/mod/dataform/view.php', $params);
}
