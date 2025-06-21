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