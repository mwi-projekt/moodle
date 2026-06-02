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

    // Prüfen, ob Zuweisungsdetails vorhanden sind.
    if (empty($data->details) || !is_array($data->details)) {
        throw new Exception('Keine Zuweisungen empfangen.');
    }

    $masterid = null;

    // === START TRANSAKTION ===
    $transaction = $DB->start_delegated_transaction();

    try {
        // === CASE 1: UPDATE einer existierenden Matrix ===
        if (!empty($data->masterid) && $data->masterid > 0) {
            $masterid = (int)$data->masterid;

            // Prüfen, ob die Matrix existiert
            $master = $DB->get_record('local_matrixzuweisung_master', ['id' => $masterid]);
            if (!$master) {
                throw new Exception('Die ausgewählte Matrix wurde nicht gefunden.');
            }

            // Alte Details löschen
            $DB->delete_records('local_matrixzuweisung_details', ['masterid' => $masterid]);

            // Master aktualisieren (nur timemodified)
            $master->timemodified = time();
            $DB->update_record('local_matrixzuweisung_master', $master);
        }
        // === CASE 2: INSERT einer neuen Matrix ===
        else {
            // Prüfen, ob ein Name für die Zuweisungsrunde vorhanden ist.
            if (empty($data->name) || trim($data->name) === '') {
                throw new Exception('Kein Name für die Zuweisungsrunde angegeben.');
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
        }

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

        // === TRANSAKTION ERFOLGREICH COMMITEN ===
        $transaction->allow_commit();

        echo json_encode([
            'success' => true,
            'message' => 'Zuweisungsmatrix wurde gespeichert.',
            'masterid' => $masterid
        ]);

    } catch (Exception $e) {
        // === TRANSAKTION ROLLBACK BEI FEHLER ===
        $transaction->rollback($e);
    }

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}