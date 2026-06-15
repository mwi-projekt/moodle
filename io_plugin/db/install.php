<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Führt die Erstinstallation des DHBWIO-Moduls durch.
 *
 * Diese Funktion wird von Moodle einmalig während der Installation
 * des Plugins aufgerufen. Dabei werden die für den Bewerbungsprozess
 * benötigten Standardstatuswerte in der Datenbank angelegt.
 *
 * Bereits vorhandene Statusdatensätze werden dabei nicht erneut
 * erstellt, sodass die Funktion mehrfach ausgeführt werden kann,
 * ohne Duplikate zu erzeugen.
 *
 * Angelegte Standardstatus:
 * - Eingereicht
 * - In Prüfung
 * - Angenommen
 * - Abgelehnt
 *
 * Der Status "Eingereicht" wird dabei als Initialstatus für neue
 * Bewerbungen definiert.
 *
 * @return void
 */
function xmldb_dhbwio_install(): void
{
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
        [
            'shortname' => 'nachzureichen',
            'label' => 'Nachzureichen',
            'description' => 'Unterlagen müssen nachgereicht werden.',
            'sortorder' => 50,
            'active' => 1,
            'isinitial' => 0,
            'isaccepted' => 0,
            'isrejected' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
    ];

    $study_programs = [
        [
            'de_name' => "Angewandte Gesundheits- und Pflegewissenschaften",
            'en_name' => "Applied Health and Nursing Sciences",
            'active' => 1,
            'sortorder' => 10,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Angewandte Hebammenwissenschaft",
            'en_name' => "Applied Midwifery Science",
            'active' => 1,
            'sortorder' => 20,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Physician Assistant / Arztassistent",
            'en_name' => "Physician Assistant",
            'active' => 1,
            'sortorder' => 30,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Elektro- und Informationstechnik",
            'en_name' => "Electrical and Information Engineering",
            'active' => 1,
            'sortorder' => 40,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Informatik",
            'en_name' => "Computer Science",
            'active' => 1,
            'sortorder' => 50,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Maschinenbau",
            'en_name' => "Mechanical Engineering",
            'active' => 1,
            'sortorder' => 60,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Mechatronik",
            'en_name' => "Mechatronics",
            'active' => 1,
            'sortorder' => 70,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Papiertechnik",
            'en_name' => "Paper Technology",
            'active' => 1,
            'sortorder' => 80,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Sicherheitswesen",
            'en_name' => "Safety Engineering",
            'active' => 1,
            'sortorder' => 90,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Sustainable Science and Technology",
            'en_name' => "Sustainable Science and Technology",
            'active' => 1,
            'sortorder' => 100,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Wirtschaftsingenieurwesen",
            'en_name' => "Industrial Engineering",
            'active' => 1,
            'sortorder' => 110,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "BWL - Bank",
            'en_name' => "Business Administration - Banking",
            'active' => 1,
            'sortorder' => 120,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "BWL - Deutsch-Franz. Management",
            'en_name' => "Business Administration - Franco-German Management",
            'active' => 1,
            'sortorder' => 130,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "BWL - Digital Business Management",
            'en_name' => "Business Administration - Digital Business Management",
            'active' => 1,
            'sortorder' => 140,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "BWL - Digital Commerce Management",
            'en_name' => "Business Administration - Digital Commerce Management",
            'active' => 1,
            'sortorder' => 150,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "BWL - Handel",
            'en_name' => "Business Administration - Retail Management",
            'active' => 1,
            'sortorder' => 160,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "BWL - Industrie",
            'en_name' => "Business Administration - Industry",
            'active' => 1,
            'sortorder' => 170,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "BWL - Versicherung",
            'en_name' => "Business Administration - Insurance",
            'active' => 1,
            'sortorder' => 180,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Data Science und Künstliche Intelligenz",
            'en_name' => "Data Science and Artificial Intelligence",
            'active' => 1,
            'sortorder' => 190,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "RSW - Steuern und Prüfungswesen",
            'en_name' => "Accounting, Taxation and Auditing",
            'active' => 1,
            'sortorder' => 200,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Unternehmertum",
            'en_name' => "Entrepreneurship",
            'active' => 1,
            'sortorder' => 210,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        [
            'de_name' => "Wirtschaftsinformatik",
            'en_name' => "Business Information Systems",
            'active' => 1,
            'sortorder' => 220,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
    ];

    foreach ($statuses as $status) {
        if (!$DB->record_exists('dhbwio_application_status', ['shortname' => $status['shortname']])) {
            $DB->insert_record('dhbwio_application_status', (object) $status);
        }
    }
    foreach ($study_programs as $program) {
        if (!$DB->record_exists('dhbwio_studyprograms', ['de_name' => $program['de_name']])) {
            $DB->insert_record('dhbwio_studyprograms', (object) $program);
        }
    }
}
