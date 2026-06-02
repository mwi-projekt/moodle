<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_dhbwio_install(): void {
    global $DB;

    $now = time();

    $statuses = [
        [
            'shortname' => 'eingereicht',
            'label' => 'Eingereicht',
            'description' => 'Die Bewerbung wurde eingereicht.',
            'sortorder' => 10,
            'active' => 1,
            'isinitial' => 1,
            'isaccepted' => 0,
            'isrejected' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'shortname' => 'in_pruefung',
            'label' => 'In Prüfung',
            'description' => 'Die Bewerbung wird geprüft.',
            'sortorder' => 20,
            'active' => 1,
            'isinitial' => 0,
            'isaccepted' => 0,
            'isrejected' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'shortname' => 'angenommen',
            'label' => 'Angenommen',
            'description' => 'Die Bewerbung wurde angenommen.',
            'sortorder' => 30,
            'active' => 1,
            'isinitial' => 0,
            'isaccepted' => 1,
            'isrejected' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'shortname' => 'abgelehnt',
            'label' => 'Abgelehnt',
            'description' => 'Die Bewerbung wurde abgelehnt.',
            'sortorder' => 40,
            'active' => 1,
            'isinitial' => 0,
            'isaccepted' => 0,
            'isrejected' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
    ];

    foreach ($statuses as $status) {
        if (!$DB->record_exists('dhbwio_application_status', ['shortname' => $status['shortname']])) {
            $DB->insert_record('dhbwio_application_status', (object) $status);
        }
    }
}