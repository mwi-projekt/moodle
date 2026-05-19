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
            ORDER BY m.timecreated DESC, m.id DESC
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
        if ($masterid <= 0) {
            throw new Exception('Keine gültige Matrix-ID angegeben.');
        }

        $master = $DB->get_record('local_matrixzuweisung_master', ['id' => $masterid]);
        if (!$master) {
            throw new Exception('Die ausgewählte Matrix wurde nicht gefunden.');
        }
        $details = $DB->get_records('local_matrixzuweisung_details', ['masterid' => $masterid], 'id ASC');

        $result = [];
        foreach ($details as $detail) {
            $result[] = [
                'studentid' => (int) $detail->studentid,
                'universityid' => (int) $detail->universityid,
            ];
        }

        echo json_encode([
            'success' => true,
            'matrix' => [
                'id' => (int) $master->id,
                'name' => $master->name,
                'timecreated' => (int) $master->timecreated,
                'timemodified' => (int) $master->timemodified,
                'details' => $result,
            ],
        ]);
        exit;
    }

    throw new Exception('Unbekannte Aktion.');
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

