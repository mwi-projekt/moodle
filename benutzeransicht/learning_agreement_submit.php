<?php
require_once('../../config.php');

require_sesskey();

$cmid = required_param('id', PARAM_INT);
$entryid = optional_param('entryid', 0, PARAM_INT);

// CM laden
$cm = get_coursemodule_from_id('benutzeransicht', $cmid, 0, false, IGNORE_MISSING);
if (!$cm) {
    $cm = get_coursemodule_from_id('dhbwio', $cmid, 0, false, IGNORE_MISSING);
}
if (!$cm) {
    throw new moodle_exception('invalidcmid', 'error');
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// ------------------------------
// Allgemeine Formularfelder
// ------------------------------
$nachname = optional_param('field_NACHNAME', '', PARAM_TEXT);
$vorname = optional_param('field_VORNAME', '', PARAM_TEXT);
$zeitraum_von_str = optional_param('field_Zeitraum_von', '', PARAM_TEXT);
$zeitraum_bis_str = optional_param('field_Zeitraum_bis', '', PARAM_TEXT);
$studienrichtung = optional_param('field_Studienrichtung', '', PARAM_TEXT);
$wahlmodul = optional_param('field_wahlmodul', '', PARAM_TEXT);
$gasthochschule = optional_param('field_Gasthochschule', '', PARAM_TEXT);

// ------------------------------
// Dynamische Kurszeilen einlesen (angepasst, um Fehler zu vermeiden)
// ------------------------------
$coursesraw = isset($_POST['course']) && is_array($_POST['course']) ? $_POST['course'] : [];
$courses = [];

if (!empty($coursesraw)) {
    ksort($coursesraw);

    foreach ($coursesraw as $row) {
        // Manuelle Bereinigung der Eingaben
        $modul_name = clean_param($row['modul_name'] ?? '', PARAM_TEXT);
        $ects = clean_param($row['ects'] ?? '', PARAM_TEXT);
        $anteil = clean_param($row['anteil'] ?? '', PARAM_TEXT);
        $partnerhochschule_value = clean_param($row['partnerhochschule_value'] ?? '', PARAM_TEXT);
        $credits = clean_param($row['credits'] ?? '', PARAM_TEXT);

        // Leere Zeilen ignorieren
        if (
            $modul_name === '' && $ects === '' && $anteil === '' &&
            $partnerhochschule_value === '' && $credits === ''
        ) {
            continue;
        }

        $courses[] = [
            'modul_name' => $modul_name,
            'ects' => $ects,
            'anteil' => $anteil,
            'partnerhochschule_value' => $partnerhochschule_value,
            'credits' => $credits,
        ];
    }
}

// ------------------------------
// Speichern
// ------------------------------
require_once(__DIR__ . '/locallib.php');

global $USER, $DB;

$content = [
    'name' => $nachname,
    'vorname' => $vorname,
    'studiengang' => '', // Platzhalter, falls benötigt
    'studienrichtung' => $studienrichtung,
    'wahlmodul' => $wahlmodul,
    'gasthochschule' => $gasthochschule,
    'zeitraum_von' => !empty($zeitraum_von_str) ? strtotime($zeitraum_von_str) : 0,
    'zeitraum_bis' => !empty($zeitraum_bis_str) ? strtotime($zeitraum_bis_str) : 0,
];

$modules = [];
foreach ($courses as $c) {
    $modules[] = [
        'modul_name' => $c['modul_name'],
        'ects' => $c['ects'],
        'teilpruefungsanteil' => $c['anteil'],
        'anrechnungs_lv' => $c['partnerhochschule_value'],
        'credits' => $c['credits']
    ];
}

// Prüfen, ob der Benutzer bereits ein Learning Agreement hat.
$existing_la = $DB->get_record('benutzeransicht_la', ['userid' => $USER->id], 'id', IGNORE_MISSING);

if ($existing_la) {
    // Wenn ein LA existiert, die zugehörige Content-ID holen.
    $existing_content = $DB->get_record('benutzeransicht_la_contents', ['la_id' => $existing_la->id], 'id', IGNORE_MISSING);
    if ($existing_content) {
        // Bestehendes LA aktualisieren.
        benutzeransicht_update_la_content_and_modules($existing_content->id, $content, $modules);
    } else {
        // Sollte nicht vorkommen, aber als Fallback ein neues LA erstellen.
        benutzeransicht_create_full_la($USER->id, $content, $modules);
    }
} else {
    // Wenn kein LA existiert, ein neues erstellen.
    benutzeransicht_create_full_la($USER->id, $content, $modules);
}

// ------------------------------
// Weiterleitung
// ------------------------------
// Weiterleitung zur Übersichtsseite mod/dhbwio/view.php
$redirecturl = new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id]);

redirect($redirecturl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
