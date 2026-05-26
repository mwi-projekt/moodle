<?php
namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

class default_form_manager {

    public static function create_default_form(int $courseid): int {
        global $DB;

        $now = time();

        $dataform = (object) [
            'course' => $courseid,
            'name' => 'Bewerbung Auslandssemester',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'timemodified' => $now,
        ];

        $dataid = $DB->insert_record('dhbwio_dataform', $dataform);

        self::create_default_fields($dataid);

        return $dataid;
    }

    private static function create_default_fields(int $dataid): void {
        global $DB;

        $fields = self::get_default_fields();

        foreach ($fields as $field) {
            $record = (object) array_merge([
                'dataid' => $dataid,
                'visible' => 2,
                'editable' => -1,
                'label' => '',
                'defaultcontentmode' => 0,
                'defaultcontent' => '',
                'param1' => '',
                'param2' => '',
                'param3' => '',
                'param4' => '',
                'param5' => '',
                'param6' => '',
                'param7' => '',
                'param8' => '',
                'param9' => '',
                'param10' => '',
            ], $field);

            $DB->insert_record('dhbwio_dataform_fields', $record);
        }
    }

    private static function get_default_fields(): array {
        return [
            [
                'type' => 'time',
                'name' => 'GEBURTSDATUM',
                'description' => 'Geburtsdatum des Bewerbers (verpflichtende Angabe)',
            ],
            [
                'type' => 'text',
                'name' => 'EMAIL',
                'description' => 'DHBW-E-Mail-Adresse des Bewerbers (verpflichtende Angabe)',
                'defaultcontent' => '@student.dhbw-karlsruhe.de',
                'param4' => 'email',
            ],
            [
                'type' => 'text',
                'name' => 'STUDIENGANGSLEITUNG',
                'description' => 'Studiengangsleitung des Bewerbers (verpflichtende Angabe)',
            ],
            [
                'type' => 'select',
                'name' => 'AKTUELLES_SEMESTER',
                'description' => 'Aktuelles Semester des Bewerbers (verpflichtende Angabe)',
                'param1' => "1. Semester\n2. Semester\n3. Semester\n4. Semester\n5. Semester\n6. Semester",
            ],
            [
                'type' => 'select',
                'name' => 'ERSTWUNSCH',
                'description' => 'Erste Wahl des Bewerbers hinsichtlich Hochschule des Auslandssemesters',
            ],
            [
                'type' => 'select',
                'name' => 'ZWEITWUNSCH',
                'description' => 'Zweite Wahl des Bewerbers hinsichtlich Hochschule des Auslandssemesters',
            ],
            [
                'type' => 'select',
                'name' => 'DRITTWUNSCH',
                'description' => 'Dritte Wahl des Bewerbers hinsichtlich Hochschule des Auslandssemesters',
            ],
            [
                'type' => 'radiobutton',
                'name' => 'ABSPRACHE_MIT_UNTERNEHMEN',
                'description' => 'Angabe, ob der Bewerber die Bewerbung mit seinem Unternehmen abgesprochen hat (verpflichtende Angabe)',
                'param1' => "Ja\nNein",
            ],
            [
                'type' => 'radiobutton',
                'name' => 'ABSPRACHE_MIT_STUDIENGANGSLEITUNG',
                'description' => 'Angabe, ob der Bewerber die Bewerbung mit seiner Studiengangsleitung abgesprochen hat (verpflichtende Angabe)',
                'param1' => "Ja\nNein",
            ],
            [
                'type' => 'textarea',
                'name' => 'BENACHTEILIGUNG_BILDUNGSCHANCEN',
                'description' => 'Einschätzung des Bewerbers hinsichtlich benachteiligter Bildungschancen (optionale Angabe)',
            ],
            [
                'type' => 'radiobutton',
                'name' => 'VEROEFFENTLICHUNG_MAILADRESSE_UND_BERICHT',
                'description' => 'Einverständnis zur Veröffentlichung von Mailadresse und Erfahrungsbericht (optionale Angabe)',
                'param1' => "Ja\nNein",
            ],
            [
                'type' => 'textarea',
                'name' => 'NACHRICHT',
                'description' => 'Nachricht des Bewerbers an das International Office (optionale Angabe)',
            ],
            [
                'type' => 'radiobutton',
                'name' => 'EINVERSTAENDNISERKLAERUNG_DATENSCHUTZ',
                'description' => 'Einverständnis mit der Datenschutzerklärung (verpflichtende Angabe)',
                'param1' => 'Ja, ich habe die Datenschutzerklärung gelesen. Mit dem Absenden des Kontaktformulars erkläre ich mich damit einverstanden, dass die von mir angegebenen Daten zweckgebunden zur Bearbeitung meiner Anfrage verwendet werden.',
            ],
            [
                'type' => 'textarea',
                'name' => 'KOMMENTAR_IO',
                'description' => 'Kommentar des IO zur Annahme bzw. Ablehnung der Bewerbung (optionale Angabe)',
            ],
            [
                'type' => 'text',
                'name' => 'VORNAME',
                'description' => 'Vorname des Bewerbers (verpflichtende Angabe)',
            ],
            [
                'type' => 'text',
                'name' => 'NACHNAME',
                'description' => 'Nachname des Bewerbers (verpflichtende Angabe)',
            ],
            [
                'type' => 'text',
                'name' => 'KURSNAME',
                'description' => 'Kurs des Bewerbers (verpflichtende Angabe)',
                'param4' => 'alphanumeric',
            ],
            [
                'type' => 'text',
                'name' => 'STUDIENRICHTUNG',
                'description' => 'Studienrichtung des Bewerbers (optionale Angabe)',
            ],
            [
                'type' => 'text',
                'name' => 'STRASSE',
                'description' => 'Straße, in der der Bewerber wohnt (verpflichtende Angabe)',
                'param4' => 'lettersonly',
            ],
            [
                'type' => 'text',
                'name' => 'HAUSNUMMER',
                'description' => 'Hausnummer der Straße, in der der Bewerber wohnt (verpflichtende Angabe)',
                'param4' => 'alphanumeric',
            ],
            [
                'type' => 'text',
                'name' => 'ORT',
                'description' => 'Ort, in dem der Bewerber wohnt (verpflichtende Angabe)',
                'param4' => 'lettersonly',
            ],
            [
                'type' => 'text',
                'name' => 'PLZ',
                'description' => 'PLZ des Orts, in dem der Bewerber wohnt',
                'param4' => 'numeric',
            ],
            [
                'type' => 'text',
                'name' => 'HANDYNUMMER',
                'description' => 'Handynummer des Bewerbers (optionale Angabe)',
                'param4' => 'numeric',
            ],
            [
                'type' => 'text',
                'name' => 'NATIONALITAET',
                'description' => 'Nationalität des Bewerbers (verpflichtende Angabe)',
                'param4' => 'lettersonly',
            ],
            [
                'type' => 'text',
                'name' => 'MUTTERSPRACHE',
                'description' => 'Muttersprache des Bewerbers (verpflichtende Angabe)',
                'param4' => 'lettersonly',
            ],
            [
                'type' => 'text',
                'name' => 'UNTERNEHMEN',
                'description' => 'Unternehmen des Bewerbers (verpflichtende Angabe)',
            ],
            [
                'type' => 'text',
                'name' => 'ANSPRECHPERSON_UNTERNEHMEN',
                'description' => 'Ansprechperson im Unternehmen des Bewerbers (verpflichtende Angabe)',
                'param4' => 'lettersonly',
            ],
            [
                'type' => 'text',
                'name' => 'ANSPRECHPERSON_EMAIL',
                'description' => 'E-Mail der Ansprechperson im Unternehmen des Bewerbers (verpflichtende Angabe)',
                'param4' => 'email',
            ],
            [
                'type' => 'text',
                'name' => 'DATEIEN',
                'description' => 'URL oder Pfad zu Bewerbungsdateien',
            ],
            [
                'type' => 'select',
                'name' => 'STUDIENGANG',
                'description' => 'Studiengang des Bewerbers',
                'param1' => "Angewandte Gesundheits- und Pflegewissenschaften\nAngewandte Hebammenwissenschaft\nPhysician Assistant / Arztassistent\nElektro- und Informationstechnik\nInformatik\nMaschinenbau\nMechatronik\nPapiertechnik\nSicherheitswesen\nSustainable Science and Technology\nWirtschaftsingenieurwesen\nBWL - Bank\nBWL - Deutsch-Franz. Management\nBWL - Digital Business Management\nBWL - Digital Commerce Management\nBWL - Handel\nBWL - Industrie\nBWL - Versicherung\nData Science und Künstliche Intelligenz\nRSW - Steuern und Prüfungswesen\nUnternehmertum\nWirtschaftsinformatik",
            ],
            [
                'type' => 'text',
                'name' => 'ADRESSEZUSATZ',
                'description' => 'Adressezusatz des Bewerbers (optionale Angabe)',
            ],
            [
                'type' => 'radiobutton',
                'name' => 'SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST',
                'description' => 'SGL-Freigabe für Erstwunsch',
                'param1' => "Ja\nNein",
            ],
            [
                'type' => 'radiobutton',
                'name' => 'SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT',
                'description' => 'SGL-Freigabe für Zweitwunsch',
                'param1' => "Ja\nNein",
            ],
            [
                'type' => 'radiobutton',
                'name' => 'SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT',
                'description' => 'SGL-Freigabe für Drittwunsch',
                'param1' => "Ja\nNein",
            ],
        ];
    }
}