<?php

require_once(__DIR__ . '/../../config.php');

require_login();

global $DB;

header('Content-Type: application/json; charset=utf-8');

try {
    $action = optional_param('action', 'list', PARAM_ALPHA);
    $masterid = optional_param('masterid', 0, PARAM_INT);
    $search = optional_param('search', '', PARAM_TEXT);

    if ($action === 'list') {
        $sql = "
            SELECT m.id, m.name, m.timecreated, m.timemodified, COUNT(d.id) AS detailcount
            FROM {local_matrixzuweisung_master} m
            LEFT JOIN {local_matrixzuweisung_details} d ON d.masterid = m.id
            WHERE 1 = 1
        ";

        $params = [];

        if (trim($search) !== '') {
            $sql .= " AND " . $DB->sql_like('m.name', ':search', false, false);
            $params['search'] = '%' . $DB->sql_like_escape(trim($search)) . '%';
        }

        $sql .= "
            GROUP BY m.id, m.name, m.timecreated, m.timemodified
            ORDER BY m.timemodified DESC, m.id DESC
        ";

        $matrices = $DB->get_records_sql($sql, $params);
        $result = [];

        foreach ($matrices as $matrix) {
            $result[] = [
                'id' => (int) $matrix->id,
                'name' => $matrix->name,
                'timecreated' => (int) $matrix->timecreated,
                'timemodified' => (int) $matrix->timemodified,
                'detailcount' => (int) $matrix->detailcount,
            ];
        }

        echo json_encode([
            'success' => true,
            'matrices' => $result,
        ]);
        exit;
    }

    if ($action === 'load') {
        // Laden (inkl. Validierung) liegt in der testbaren Repository-Klasse.
        echo json_encode(\local_zuweisungsmatrix\matrix_repository::load($masterid));
        exit;
    }

    throw new Exception('Unbekannte Aktion.');
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

