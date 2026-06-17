<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Ruft die Inhalts-ID eines Learning Agreements für einen bestimmten Benutzer ab.
 *
 * @param int $userid Die ID des Benutzers.
 * @return int|null Die ID des Inhaltsdatensatzes (contentid) oder null, wenn nicht gefunden.
 */
function benutzeransicht_get_la_contentid_by_userid($userid) {
    global $DB;

    $la = $DB->get_record('benutzeransicht_la', ['userid' => $userid], 'id', IGNORE_MISSING);
    if (!$la) {
        return null;
    }

    $content = $DB->get_record('benutzeransicht_la_contents', ['la_id' => $la->id], 'id', IGNORE_MISSING);
    if (!$content) {
        return null;
    }

    return (int)$content->id;
}


/**
 * Erstellt ein vollständiges Learning Agreement (Metadaten, Inhalt und Module) in einer einzigen Transaktion.
 *
 * @param int $userid Die ID des Benutzers, der das LA erstellt.
 * @param array $content Ein assoziatives Array mit den Inhaltsdaten des LA.
 * @param array $modules Ein Array von assoziativen Arrays, die die einzelnen Module repräsentieren.
 * @return int Die ID des neu erstellten Inhaltsdatensatzes (benutzeransicht_la_contents.id).
 * @throws dml_exception
 */
function benutzeransicht_create_full_la($userid, array $content, array $modules = []) {
    global $DB, $USER;

    $transaction = $DB->start_delegated_transaction();
    $now = time();

    // 1. Metadaten in 'benutzeransicht_la' einfügen
    $la_record = (object)[
        'userid'       => $userid,
        'timecreated'  => $now,
        'timemodified' => $now,
        'lasteditedby' => $USER->id,
    ];
    $la_id = $DB->insert_record('benutzeransicht_la', $la_record);

    // 2. Hauptinhalt in 'benutzeransicht_la_contents' einfügen
    $content_record = (object)[
        'la_id'           => $la_id,
        'name'            => $content['name'] ?? '',
        'vorname'         => $content['vorname'] ?? '',
        'studiengang'     => $content['studiengang'] ?? '',
        'studienrichtung' => $content['studienrichtung'] ?? '',
        'wahlmodul'       => $content['wahlmodul'] ?? '',
        'gasthochschule'  => $content['gasthochschule'] ?? '',
        'zeitraum_von'    => $content['zeitraum_von'] ?? 0,
        'zeitraum_bis'    => $content['zeitraum_bis'] ?? 0,
    ];
    $content_id = $DB->insert_record('benutzeransicht_la_contents', $content_record);

    // 3. Zugehörige Module in 'benutzeransicht_la_module' einfügen
    foreach ($modules as $module_data) {
        $module_record = (object)[
            'la_contents_id'      => $content_id,
            'modul_name'          => $module_data['modul_name'] ?? '',
            'ects'                => $module_data['ects'] ?? '',
            'teilpruefungsanteil' => $module_data['teilpruefungsanteil'] ?? '',
            'anrechnungs_lv'      => $module_data['anrechnungs_lv'] ?? '',
            'credits'             => $module_data['credits'] ?? '',
        ];
        $DB->insert_record('benutzeransicht_la_module', $module_record);
    }

    $transaction->allow_commit();

    return $content_id;
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
function benutzeransicht_update_la_content_and_modules($contentid, array $content, array $modules = []) {
    global $DB, $USER;

    $transaction = $DB->start_delegated_transaction();
    $now = time();

    // 1. Hauptinhalt in 'benutzeransicht_la_contents' aktualisieren
    $content_record = $DB->get_record('benutzeransicht_la_contents', ['id' => $contentid], '*', MUST_EXIST);
    $content_record->name            = $content['name'] ?? $content_record->name;
    $content_record->vorname         = $content['vorname'] ?? $content_record->vorname;
    $content_record->studiengang     = $content['studiengang'] ?? $content_record->studiengang;
    $content_record->studienrichtung = $content['studienrichtung'] ?? $content_record->studienrichtung;
    $content_record->wahlmodul       = $content['wahlmodul'] ?? $content_record->wahlmodul;
    $content_record->gasthochschule  = $content['gasthochschule'] ?? $content_record->gasthochschule;
    $content_record->zeitraum_von    = $content['zeitraum_von'] ?? $content_record->zeitraum_von;
    $content_record->zeitraum_bis    = $content['zeitraum_bis'] ?? $content_record->zeitraum_bis;
    $DB->update_record('benutzeransicht_la_contents', $content_record);

    // Metadaten in 'benutzeransicht_la' aktualisieren
    $la_record = $DB->get_record('benutzeransicht_la', ['id' => $content_record->la_id], '*', MUST_EXIST);
    $la_record->timemodified = $now;
    $la_record->lasteditedby = $USER->id;
    $DB->update_record('benutzeransicht_la', $la_record);

    // 2. Alte Module löschen
    $DB->delete_records('benutzeransicht_la_module', ['la_contents_id' => $contentid]);

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
        $DB->insert_record('benutzeransicht_la_module', $module_record);
    }

    $transaction->allow_commit();
}

/**
 * Ruft ein vollständiges Learning Agreement anhand der Inhalts-ID ab.
 *
 * @param int $contentid Die ID des Inhaltsdatensatzes.
 * @return object|null Ein Objekt mit den LA-Daten oder null, wenn nicht gefunden.
 */
function benutzeransicht_get_full_la_by_contentid($contentid) {
    global $DB;

    $content = $DB->get_record('benutzeransicht_la_contents', ['id' => $contentid]);
    if (!$content) {
        return null;
    }

    $la = $DB->get_record('benutzeransicht_la', ['id' => $content->la_id]);
    $modules = $DB->get_records('benutzeransicht_la_module', ['la_contents_id' => $contentid], 'id ASC');

    return (object)[
        'content' => $content,
        'la'      => $la,
        'modules' => array_values($modules),
    ];
}
