<?php
require_once('../../config.php');
require_login();
require_once($CFG->libdir . '/excellib.class.php');

// Matrixdaten vom Browser empfangen
$matrixdata = json_decode(optional_param('matrixdata', '', PARAM_RAW), true);

// Hochschulnamen + Platzanzahl direkt aus DB laden
$hochschulen = $DB->get_records_sql("
    SELECT name, available_slots
    FROM {dhbwio_universities}
    WHERE active = 1
    ORDER BY name
");

// Spaltennamen und verfügbare Plätze extrahieren
$hochschulnamen = [];
$platzanzahl = [];
foreach ($hochschulen as $h) {
    $hochschulnamen[] = $h->name;
    $platzanzahl[] = (int)$h->available_slots;
}

// Excel-Datei vorbereiten
$filename = "zuweisungsmatrix.xlsx";
$workbook = new MoodleExcelWorkbook("-");
$workbook->send($filename);
$worksheet = $workbook->add_worksheet('Zuweisung');

// Kopfzeile schreiben
$worksheet->write_string(0, 0, "Platz");
foreach ($hochschulnamen as $i => $name) {
    $worksheet->write_string(0, $i + 1, $name);
}

// Inhalt schreiben
foreach ($matrixdata as $r => $row) {
    $worksheet->write_string($r + 1, 0, "Platz " . ($r + 1));
    foreach ($hochschulnamen as $c => $hochschule) {
        $value = $row[$c] ?? '';

        // Zelle ist über der Slot-Grenze dieser Hochschule
        if ($r >= $platzanzahl[$c]) {
            $worksheet->write_string($r + 1, $c + 1, '-');
        } else {
            $worksheet->write_string($r + 1, $c + 1, $value);
        }
    }
}

// Datei schließen & ausgeben
$workbook->close();
exit;

