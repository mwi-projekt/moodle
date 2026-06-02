<?php

namespace mod_dhbwio\local\dataform;

use mod_dhbwio\local\dataform\field_manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Verwaltungsklasse zur Erstellung des Standard-Bewerbungsformulars.
 *
 * Diese Klasse erzeugt automatisch eine vorkonfigurierte Dataform für
 * Auslandssemesterbewerbungen. Dabei werden sowohl die Dataform selbst
 * als auch sämtliche benötigten Standardfelder angelegt.
 *
 * Die Definition der Standardfelder erfolgt zentral innerhalb dieser
 * Klasse, sodass neue Installationen oder Kurse jederzeit eine
 * einheitliche Formularstruktur erhalten.
 *
 * Nutzen:
 * - Automatische Einrichtung neuer Bewerbungsformulare
 * - Einheitliche Feldstruktur für alle Bewerbungen
 * - Zentrale Pflege der Standardkonfiguration
 * - Vereinfachung von Installation und Kursanlage
 */
class default_form_manager
{
    /**
     * Erstellt eine neue Standard-Dataform für einen Kurs.
     *
     * Legt einen neuen Dataform-Datensatz mit den vordefinierten
     * Grundeinstellungen an und erzeugt anschließend alle
     * benötigten Standardfelder.
     *
     * @param int $courseid ID des zugehörigen Moodle-Kurses.
     * @return int ID der neu angelegten Dataform.
     */
    public static function create_default_form(int $courseid): int
    {
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
    /**
     * Erstellt die Standardfelder einer Dataform.
     *
     * Die Felddefinitionen werden aus der zentralen Konfiguration geladen
     * und als Datensätze in der Datenbank gespeichert. Dabei werden
     * Standardwerte für Sichtbarkeit, Bearbeitbarkeit und Gruppierung
     * ergänzt.
     *
     * @param int $dataid ID der Dataform, für die die Felder angelegt werden.
     * @return void
     */
    private static function create_default_fields(int $dataid): void
    {
        global $DB;

        $fields = self::get_default_fields();

        foreach ($fields as $field) {
            $record = (object) array_merge([
                'dataid' => $dataid,
                'visible' => 2,
                'editable' => -1,
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => 'general',
                'sortorder' => 0,
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
    /**
     * Liefert die Definition aller Standardfelder.
     *
     * Die Methode enthält die vollständige Konfiguration der im
     * Bewerbungsprozess benötigten Felder. Dazu gehören persönliche Daten,
     * Studieninformationen, Hochschulwünsche, Einverständniserklärungen
     * sowie interne Felder für die Bearbeitung durch das International Office.
     *
     * Die zurückgegebenen Definitionen dienen als Vorlage für die
     * automatische Erstellung neuer Bewerbungsformulare.
     *
     * @return array Liste der Standardfelddefinitionen.
     */
    private static function get_default_fields(): array
    {
        return [
            [
                'type' => 'time',
                'name' => 'GEBURTSDATUM',
                'description' => 'Geburtsdatum des Bewerbers (verpflichtende Angabe)',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_PERSONAL,
                'sortorder' => 30
            ],
            [
                'type' => 'text',
                'name' => 'EMAIL',
                'description' => 'DHBW-E-Mail-Adresse des Bewerbers (verpflichtende Angabe)',
                'defaultcontent' => '@student.dhbw-karlsruhe.de',
                'param4' => 'email',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_PERSONAL,
                'sortorder' => 60
            ],
            [
                'type' => 'text',
                'name' => 'STUDIENGANGSLEITUNG',
                'description' => 'Studiengangsleitung des Bewerbers (verpflichtende Angabe)',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_STUDY,
                'sortorder' => 40
            ],
            [
                'type' => 'select',
                'name' => 'AKTUELLES_SEMESTER',
                'description' => 'Aktuelles Semester des Bewerbers (verpflichtende Angabe)',
                'param1' => "1. Semester\n2. Semester\n3. Semester\n4. Semester\n5. Semester\n6. Semester",
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_STUDY,
                'sortorder' => 60
            ],
            [
                'type' => 'select',
                'name' => 'ERSTWUNSCH',
                'description' => 'Erste Wahl des Bewerbers hinsichtlich Hochschule des Auslandssemesters',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_CHOICES,
                'sortorder' => 10
            ],
            [
                'type' => 'select',
                'name' => 'ZWEITWUNSCH',
                'description' => 'Zweite Wahl des Bewerbers hinsichtlich Hochschule des Auslandssemesters',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_CHOICES,
                'sortorder' => 20
            ],
            [
                'type' => 'select',
                'name' => 'DRITTWUNSCH',
                'description' => 'Dritte Wahl des Bewerbers hinsichtlich Hochschule des Auslandssemesters',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_CHOICES,
                'sortorder' => 30
            ],
            [
                'type' => 'radiobutton',
                'name' => 'ABSPRACHE_MIT_UNTERNEHMEN',
                'description' => 'Angabe, ob der Bewerber die Bewerbung mit seinem Unternehmen abgesprochen hat (verpflichtende Angabe)',
                'param1' => "Ja\nNein",
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_COMPANY,
                'sortorder' => 40
            ],
            [
                'type' => 'radiobutton',
                'name' => 'ABSPRACHE_MIT_STUDIENGANGSLEITUNG',
                'description' => 'Angabe, ob der Bewerber die Bewerbung mit seiner Studiengangsleitung abgesprochen hat (verpflichtende Angabe)',
                'param1' => "Ja\nNein",
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_STUDY,
                'sortorder' => 50
            ],
            [
                'type' => 'textarea',
                'name' => 'BENACHTEILIGUNG_BILDUNGSCHANCEN',
                'description' => 'Einschätzung des Bewerbers hinsichtlich benachteiligter Bildungschancen (optionale Angabe)',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_STATEMENTS,
                'sortorder' => 10
            ],
            [
                'type' => 'radiobutton',
                'name' => 'VEROEFFENTLICHUNG_MAILADRESSE_UND_BERICHT',
                'description' => 'Einverständnis zur Veröffentlichung von Mailadresse und Erfahrungsbericht (optionale Angabe)',
                'param1' => "Ja\nNein",
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_STATEMENTS,
                'sortorder' => 30
            ],
            [
                'type' => 'textarea',
                'name' => 'NACHRICHT',
                'description' => 'Nachricht des Bewerbers an das International Office (optionale Angabe)',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_STATEMENTS,
                'sortorder' => 20
            ],
            [
                'type' => 'radiobutton',
                'name' => 'EINVERSTAENDNISERKLAERUNG_DATENSCHUTZ',
                'description' => 'Einverständnis mit der Datenschutzerklärung (verpflichtende Angabe)',
                'param1' => 'Ja, ich habe die Datenschutzerklärung gelesen. Mit dem Absenden des Kontaktformulars erkläre ich mich damit einverstanden, dass die von mir angegebenen Daten zweckgebunden zur Bearbeitung meiner Anfrage verwendet werden.',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_STATEMENTS,
                'sortorder' => 40
            ],
            [
                'type' => 'textarea',
                'name' => 'KOMMENTAR_IO',
                'description' => 'Kommentar des IO zur Annahme bzw. Ablehnung der Bewerbung (optionale Angabe)',
                'scope' => field_manager::SCOPE_REVIEW,
                'fieldgroup' => field_manager::GROUP_REVIEW,
                'sortorder' => 10,
            ],
            [
                'type' => 'text',
                'name' => 'VORNAME',
                'description' => 'Vorname des Bewerbers (verpflichtende Angabe)',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_PERSONAL,
                'sortorder' => 20
            ],
            [
                'type' => 'text',
                'name' => 'NACHNAME',
                'description' => 'Nachname des Bewerbers (verpflichtende Angabe)',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_PERSONAL,
                'sortorder' => 10
            ],
            [
                'type' => 'text',
                'name' => 'KURSNAME',
                'description' => 'Kurs des Bewerbers (verpflichtende Angabe)',
                'param4' => 'alphanumeric',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_STUDY,
                'sortorder' => 30
            ],
            [
                'type' => 'text',
                'name' => 'STUDIENRICHTUNG',
                'description' => 'Studienrichtung des Bewerbers (optionale Angabe)',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_STUDY,
                'sortorder' => 20
            ],
            [
                'type' => 'text',
                'name' => 'STRASSE',
                'description' => 'Straße, in der der Bewerber wohnt (verpflichtende Angabe)',
                'param4' => 'lettersonly',
                'scope' => field_manager::SCOPE_DEPRECATED,
                'fieldgroup' => field_manager::GROUP_TECHNICAL,
                'sortorder' => 90
            ],
            [
                'type' => 'text',
                'name' => 'HAUSNUMMER',
                'description' => 'Hausnummer der Straße, in der der Bewerber wohnt (verpflichtende Angabe)',
                'param4' => 'alphanumeric',
                'scope' => field_manager::SCOPE_DEPRECATED,
                'fieldgroup' => field_manager::GROUP_TECHNICAL,
                'sortorder' => 100
            ],
            [
                'type' => 'text',
                'name' => 'ORT',
                'description' => 'Ort, in dem der Bewerber wohnt (verpflichtende Angabe)',
                'param4' => 'lettersonly',
                'scope' => field_manager::SCOPE_DEPRECATED,
                'fieldgroup' => field_manager::GROUP_TECHNICAL,
                'sortorder' => 110
            ],
            [
                'type' => 'text',
                'name' => 'PLZ',
                'description' => 'PLZ des Orts, in dem der Bewerber wohnt',
                'param4' => 'numeric',
                'scope' => field_manager::SCOPE_DEPRECATED,
                'fieldgroup' => field_manager::GROUP_TECHNICAL,
                'sortorder' => 120
            ],
            [
                'type' => 'text',
                'name' => 'HANDYNUMMER',
                'description' => 'Handynummer des Bewerbers (optionale Angabe)',
                'param4' => 'numeric',
                'scope' => field_manager::SCOPE_DEPRECATED,
                'fieldgroup' => field_manager::GROUP_TECHNICAL,
                'sortorder' => 130
            ],
            [
                'type' => 'text',
                'name' => 'NATIONALITAET',
                'description' => 'Nationalität des Bewerbers (verpflichtende Angabe)',
                'param4' => 'lettersonly',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_PERSONAL,
                'sortorder' => 40
            ],
            [
                'type' => 'text',
                'name' => 'MUTTERSPRACHE',
                'description' => 'Muttersprache des Bewerbers (verpflichtende Angabe)',
                'param4' => 'lettersonly',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_PERSONAL,
                'sortorder' => 50
            ],
            [
                'type' => 'text',
                'name' => 'UNTERNEHMEN',
                'description' => 'Unternehmen des Bewerbers (verpflichtende Angabe)',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_COMPANY,
                'sortorder' => 10
            ],
            [
                'type' => 'text',
                'name' => 'ANSPRECHPERSON_UNTERNEHMEN',
                'description' => 'Ansprechperson im Unternehmen des Bewerbers (verpflichtende Angabe)',
                'param4' => 'lettersonly',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_COMPANY,
                'sortorder' => 20
            ],
            [
                'type' => 'text',
                'name' => 'ANSPRECHPERSON_EMAIL',
                'description' => 'E-Mail der Ansprechperson im Unternehmen des Bewerbers (verpflichtende Angabe)',
                'param4' => 'email',
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_COMPANY,
                'sortorder' => 30
            ],
            [
                'type' => 'text',
                'name' => 'DATEIEN',
                'description' => 'URL oder Pfad zu Bewerbungsdateien',
                'scope' => field_manager::SCOPE_DEPRECATED,
                'fieldgroup' => field_manager::GROUP_TECHNICAL,
                'sortorder' => 130
            ],
            [
                'type' => 'select',
                'name' => 'STUDIENGANG',
                'description' => 'Studiengang des Bewerbers',
                'param1' => "Angewandte Gesundheits- und Pflegewissenschaften\nAngewandte Hebammenwissenschaft\nPhysician Assistant / Arztassistent\nElektro- und Informationstechnik\nInformatik\nMaschinenbau\nMechatronik\nPapiertechnik\nSicherheitswesen\nSustainable Science and Technology\nWirtschaftsingenieurwesen\nBWL - Bank\nBWL - Deutsch-Franz. Management\nBWL - Digital Business Management\nBWL - Digital Commerce Management\nBWL - Handel\nBWL - Industrie\nBWL - Versicherung\nData Science und Künstliche Intelligenz\nRSW - Steuern und Prüfungswesen\nUnternehmertum\nWirtschaftsinformatik",
                'scope' => field_manager::SCOPE_STUDENT,
                'fieldgroup' => field_manager::GROUP_STUDY,
                'sortorder' => 10
            ],
            [
                'type' => 'text',
                'name' => 'ADRESSEZUSATZ',
                'description' => 'Adressezusatz des Bewerbers (optionale Angabe)',
                'scope' => field_manager::SCOPE_DEPRECATED,
                'fieldgroup' => field_manager::GROUP_TECHNICAL,
                'sortorder' => 140
            ],
            [
                'type' => 'radiobutton',
                'name' => 'SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST',
                'description' => 'SGL-Freigabe für Erstwunsch',
                'param1' => "Ja\nNein",
                'scope' => field_manager::SCOPE_REVIEW,
                'fieldgroup' => field_manager::GROUP_REVIEW,
                'sortorder' => 20
            ],
            [
                'type' => 'radiobutton',
                'name' => 'SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT',
                'description' => 'SGL-Freigabe für Zweitwunsch',
                'param1' => "Ja\nNein",
                'scope' => field_manager::SCOPE_REVIEW,
                'fieldgroup' => field_manager::GROUP_REVIEW,
                'sortorder' => 30
            ],
            [
                'type' => 'radiobutton',
                'name' => 'SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT',
                'description' => 'SGL-Freigabe für Drittwunsch',
                'param1' => "Ja\nNein",
                'scope' => field_manager::SCOPE_REVIEW,
                'fieldgroup' => field_manager::GROUP_REVIEW,
                'sortorder' => 40
            ],
        ];
    }
}
