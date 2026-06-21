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













/**
 * Ruft die Inhalts-ID eines Learning Agreements für einen bestimmten Benutzer ab.
 *
 * @param int $userid Die ID des Benutzers.
 * @return int|null Die ID des Inhaltsdatensatzes (contentid) oder null, wenn nicht gefunden.
 */
function dhbwio_get_la_contentid_by_userid($userid) {
    global $DB;

    $la = $DB->get_record('dhbwio_la', ['userid' => $userid], 'id', IGNORE_MISSING);
    if (!$la) {
        return null;
    }

    $content = $DB->get_record('dhbwio_la_contents', ['la_id' => $la->id], 'id', IGNORE_MISSING);
    if (!$content) {
        return null;
    }

    return (int)$content->id;
}


/**
 * Erstellt ein neues Learning Agreement mit Inhalten und Modulen.
 *
 * @param int $userid
 * @param array|stdClass $content
 * @param array $modules
 * @param int $app_entryid ID der verknüpften Bewerbung
 * @return int ID des neu erstellten LA
 */
function dhbwio_create_full_la($userid, $content, $modules, $app_entryid = 0) {
    global $DB;

    // 1. Basis-Eintrag für das Learning Agreement anlegen
    $la = new stdClass();
    $la->userid = $userid;
    $la->application_entryid = $app_entryid; // Neue Zuweisung der Bewerbungs-ID
    $la->timecreated = time();
    $la->timemodified = time();
    $la->lasteditedby = $userid;

    $la_id = $DB->insert_record('dhbwio_la', $la);

    // 2. Inhalte (Metadaten) für das LA speichern
    $la_content = new stdClass();
    $la_content->la_id = $la_id;
    $la_content->name         = $content['name'] ?? '';
    $la_content->vorname      = $content['vorname'] ?? '';
    $la_content->studiengang  = $content['studiengang'] ?? '';
    $la_content->studienrichtung = $content['studienrichtung'] ?? '';
    $la_content->wahlmodul    = $content['wahlmodul'] ?? '';
    $la_content->gasthochschule = $content['gasthochschule'] ?? '';
    $la_content->zeitraum_von = $content['zeitraum_von'] ?? 0;
    $la_content->zeitraum_bis = $content['zeitraum_bis'] ?? 0;

    $content_id = $DB->insert_record('dhbwio_la_contents', $la_content);

    // 3. Module des LAs speichern
    foreach ($modules as $mod) {
        $module_record = new stdClass();
        $module_record->la_contents_id = $content_id;
        $module_record->modul_name = $mod['modul_name'];
        $module_record->ects = $mod['ects'];
        $module_record->teilpruefungsanteil = $mod['teilpruefungsanteil'];
        $module_record->anrechnungs_lv = $mod['anrechnungs_lv'];
        $module_record->credits = $mod['credits'];

        $DB->insert_record('dhbwio_la_module', $module_record);
    }

    return $la_id;
}


/**
 * Aktualisiert ein bestehendes Learning Agreement.
 *
 * @param int $contentid Die ID des zu aktualisierenden Inhaltsdatensatzes.
 * @param array $content Die neuen Inhaltsdaten.
 * @param array $modules Die neuen Moduldaten (ersetzen die alten).
 * @return void
 * @throws dml_exception
 */
function dhbwio_update_la_content_and_modules($contentid, array $content, array $modules = []) {
    global $DB, $USER;

    $transaction = $DB->start_delegated_transaction();
    $now = time();

    // 1. Hauptinhalt in 'dhbwio_la_contents' aktualisieren
    $content_record = $DB->get_record('dhbwio_la_contents', ['id' => $contentid], '*', MUST_EXIST);
    $content_record->name            = $content['name'] ?? $content_record->name;
    $content_record->vorname         = $content['vorname'] ?? $content_record->vorname;
    $content_record->studiengang     = $content['studiengang'] ?? $content_record->studiengang;
    $content_record->studienrichtung = $content['studienrichtung'] ?? $content_record->studienrichtung;
    $content_record->wahlmodul       = $content['wahlmodul'] ?? $content_record->wahlmodul;
    $content_record->gasthochschule  = $content['gasthochschule'] ?? $content_record->gasthochschule;
    $content_record->zeitraum_von    = $content['zeitraum_von'] ?? $content_record->zeitraum_von;
    $content_record->zeitraum_bis    = $content['zeitraum_bis'] ?? $content_record->zeitraum_bis;
    $DB->update_record('dhbwio_la_contents', $content_record);

    // Metadaten in 'dhbwio_la' aktualisieren
    $la_record = $DB->get_record('dhbwio_la', ['id' => $content_record->la_id], '*', MUST_EXIST);
    $la_record->timemodified = $now;
    $la_record->lasteditedby = $USER->id;
    $DB->update_record('dhbwio_la', $la_record);

    // 2. Alte Module löschen
    $DB->delete_records('dhbwio_la_module', ['la_contents_id' => $contentid]);

    // 3. Neue Module einfügen
    foreach ($modules as $module_data) {
        $module_record = (object)[
            'la_contents_id'      => $contentid,
            'modul_name'          => $module_data['modul_name'] ?? '',
            'ects'                => $module_data['ects'] ?? '',
            'teilpruefungsanteil' => $module_data['teilpruefungsanteil'] ?? '',
            'anrechnungs_lv'      => $module_data['anrechnungs_lv'] ?? '',
            'credits'             => $module_data['credits'] ?? '',
        ];
        $DB->insert_record('dhbwio_la_module', $module_record);
    }

    $transaction->allow_commit();
}

/**
 * Ruft ein vollständiges Learning Agreement anhand der Inhalts-ID ab.
 *
 * @param int $contentid Die ID des Inhaltsdatensatzes.
 * @return object|null Ein Objekt mit den LA-Daten oder null, wenn nicht gefunden.
 */
function dhbwio_get_full_la_by_contentid($contentid) {
    global $DB;

    $content = $DB->get_record('dhbwio_la_contents', ['id' => $contentid]);
    if (!$content) {
        return null;
    }

    $la = $DB->get_record('dhbwio_la', ['id' => $content->la_id]);
    $modules = $DB->get_records('dhbwio_la_module', ['la_contents_id' => $contentid], 'id ASC');

    return (object)[
        'content' => $content,
        'la'      => $la,
        'modules' => array_values($modules),
    ];
}



function dhbwio_get_full_la_by_la_id($la_id) {
    global $DB;

    $la = $DB->get_record('dhbwio_la', ['id' => $la_id]);
    if (!$la) {
        return null;
    }

    $content = $DB->get_record('dhbwio_la_contents', ['la_id' => $la_id]);
    if (!$content) {
        return null;
    }

    $modules = $DB->get_records('dhbwio_la_module', ['la_contents_id' => $content->id], 'id ASC');

    return (object)[
        'content' => $content,
        'la'      => $la,
        'modules' => array_values($modules),
    ];
}
