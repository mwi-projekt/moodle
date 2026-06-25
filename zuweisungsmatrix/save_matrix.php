<?php

require_once(__DIR__ . '/../../config.php');

require_login();

header('Content-Type: application/json');

// JSON-Daten aus dem AJAX-Request lesen.
$rawdata = file_get_contents('php://input');
$data = json_decode($rawdata);

// Prüfen, ob gültige JSON-Daten angekommen sind.
if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige JSON-Daten.',
    ]);
    exit;
}

// Persistenz (inkl. Transaktion/Rollback) liegt in der testbaren Repository-Klasse.
echo json_encode(\local_zuweisungsmatrix\matrix_repository::save($data));
