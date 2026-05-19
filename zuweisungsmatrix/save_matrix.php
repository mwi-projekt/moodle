<?php

require_once(__DIR__ . '/../../config.php');

require_login();

global $DB;

header('Content-Type: application/json');

try {
    // JSON-Daten aus dem AJAX-Request lesen.
    $rawdata = file_get_contents('php://input');
    $data = json_decode($rawdata);

    // Prüfen, ob gültige JSON-Daten angekommen sind.
    if (!$data) {
        throw new Exception('Ungültige JSON-Daten.');
    }

    // Prüfen, ob ein Name für die Zuweisungsrunde vorhanden ist.
    if (empty($data->name) || trim($data->name) === '') {
        throw new Exception('Kein Name für die Zuweisungsrunde angegeben.');
    }

    // Prüfen, ob Zuweisungsdetails vorhanden sind.
    if (empty($data->details) || !is_array($data->details)) {
        throw new Exception('Keine Zuweisungen empfangen.');
    }

    // Master-Datensatz anlegen.
    // Entspricht Tabelle: local_matrixzuweisung_master
    $master = new stdClass();
    $master->name = trim($data->name);
    $master->timecreated = time();
    $master->timemodified = time();

    $masterid = $DB->insert_record(
        'local_matrixzuweisung_master',
        $master
    );

    // Jede einzelne Student-Hochschule-Zuweisung speichern.
    // Entspricht Tabelle: local_matrixzuweisung_details
    foreach ($data->details as $entry) {

        // Ungültige Einträge überspringen.
        if (
            empty($entry->studentid) ||
            empty($entry->universityid)
        ) {
            continue;
        }

        $detail = new stdClass();
        $detail->masterid = $masterid;
        $detail->studentid = (int)$entry->studentid;
        $detail->universityid = (int)$entry->universityid;

        $DB->insert_record(
            'local_matrixzuweisung_details',
            $detail
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Zuweisungsmatrix wurde gespeichert.',
        'masterid' => $masterid
    ]);

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}