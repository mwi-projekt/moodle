<?php

namespace mod_dhbwio\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use mod_dhbwio\local\dataform\validation_manager;
use mod_dhbwio\local\dataform\field_manager;

/**
 * Formular zur Erfassung und Bearbeitung von Bewerbungsdaten.
 *
 * Diese Klasse erzeugt dynamisch ein Moodle-Formular auf Basis der
 * im DHBWIO-System konfigurierten Dataform-Felder. Die Felder werden
 * nach ihrer Feldgruppe strukturiert dargestellt und abhängig von ihrer
 * Konfiguration automatisch als Text-, Auswahl-, Datums- oder andere
 * Eingabeelemente gerendert.
 *
 * Zusätzlich übernimmt die Klasse die Validierung der Benutzereingaben
 * über den zentralen Validation Manager.
 *
 * Nutzen:
 * - Zentrale Generierung von Bewerbungsformularen
 * - Dynamische Anpassung an konfigurierte Datenfelder
 * - Einheitliche Validierung von Benutzereingaben
 * - Trennung von Formularlogik und Datenmodell
 */

class application_form extends \moodleform
{
    /**
     * Erstellt die Struktur des Bewerbungsformulars.
     *
     * Lädt die konfigurierten Felder aus den übergebenen Formulardaten,
     * gruppiert diese nach ihrer Feldgruppe und erzeugt die entsprechenden
     * Eingabeelemente. Abschließend werden die Standard-Aktionsbuttons
     * hinzugefügt.
     *
     * @return void
     */
    public function definition(): void
    {
        $mform = $this->_form;

        $id = $this->_customdata['id'] ?? 0;
        $dataid = $this->_customdata['dataid'] ?? 0;
        $fields = $this->_customdata['fields'] ?? [];

        $mform->addElement('hidden', 'dataid', $dataid);
        $mform->setType('dataid', PARAM_INT);
        $entryid = $this->_customdata['entryid'] ?? 0;

        $mform->addElement('hidden', 'entryid', $entryid);
        $mform->setType('entryid', PARAM_INT);

        $currentgroup = null;

        foreach ($fields as $field) {
            if (!field_manager::is_student_field($field)) {
                continue;
            }

            $fieldgroup = $field->fieldgroup ?? field_manager::GROUP_GENERAL;

            if ($fieldgroup !== $currentgroup) {
                $this->add_group_header($fieldgroup);
                $currentgroup = $fieldgroup;
            }

            $this->add_field($field);
        }

        $this->add_action_buttons(true, get_string('submit'));
    }
    /**
     * Fügt eine Überschrift für eine Feldgruppe hinzu.
     *
     * Die Methode sorgt für eine übersichtliche Darstellung des Formulars,
     * indem zusammengehörige Felder unter einer gemeinsamen Überschrift
     * zusammengefasst werden.
     *
     * @param string $fieldgroup Technischer Name der Feldgruppe.
     * @return void
     */
    private function add_group_header(string $fieldgroup): void
    {
        $mform = $this->_form;

        $titles = field_manager::get_group_titles();

        $title = $titles[$fieldgroup] ?? ucfirst($fieldgroup);

        $mform->addElement('header', 'group_' . $fieldgroup, $title);
    }

    /**
     * Erzeugt ein Formularfeld anhand seiner Konfiguration.
     *
     * Abhängig vom Feldtyp wird automatisch das passende Moodle-
     * Formularelement erstellt. Unterstützt werden unter anderem
     * Textfelder, Textbereiche, Auswahlfelder, Radiobuttons und Datumsfelder.
     *
     * Nicht für Studierende freigegebene Felder werden ignoriert.
     *
     * @param \stdClass $field Felddefinition aus dem Datenmodell.
     * @return void
     */
    private function add_field(\stdClass $field): void
    {
        $mform = $this->_form;

        $name = 'field_' . $field->id;
        $label = $field->name;

        if (!field_manager::is_student_field($field)) {
            return;
        }

        if (!empty($field->description)) {
            $label .= ' - ' . strip_tags($field->description);
        }

        switch ($field->type) {
            case 'text':
                $mform->addElement('text', $name, $label);
                $mform->setType($name, PARAM_TEXT);
                break;

            case 'textarea':
                $mform->addElement('textarea', $name, $label, [
                    'rows' => 5,
                    'cols' => 60,
                ]);
                $mform->setType($name, PARAM_TEXT);
                break;

            case 'select':

                if (in_array($field->name, ['ERSTWUNSCH', 'ZWEITWUNSCH', 'DRITTWUNSCH'], true)) {
                    $options = $this->get_university_options($field->name);
                } else {
                    $options = $this->get_options_from_field($field);
                }

                $mform->addElement('select', $name, $label, $options);
                $mform->setType($name, PARAM_TEXT);

                break;

            case 'radiobutton':
                $options = $this->get_options_from_field($field);
                $radioarray = [];

                foreach ($options as $value => $text) {
                    $radioarray[] = $mform->createElement('radio', $name, '', $text, $value);
                }

                $mform->addGroup($radioarray, $name . '_group', $label, [' '], false);
                break;

            case 'time':
                $mform->addElement('date_selector', $name, $label);
                $mform->setType($name, PARAM_INT);
                break;

            case 'entrystate':
                // Wird vorerst intern verwaltet, nicht als normales Formularfeld angezeigt.
                $mform->addElement('hidden', $name, 0);
                $mform->setType($name, PARAM_INT);
                break;

            case 'file':
                // File-Handling bearbeitet Team-Learning Agreement.
                break;

            default:
                // Fallback: unbekannte Felder als Textfeld anzeigen.
                $mform->addElement('text', $name, $label);
                $mform->setType($name, PARAM_TEXT);
                break;
        }
    }
    /**
     * Ermittelt die Auswahloptionen eines konfigurierten Auswahlfeldes.
     *
     * Die Optionen werden aus der Feldkonfiguration gelesen und als
     * Schlüssel-Wert-Paare für Moodle-Auswahlelemente aufbereitet.
     *
     * @param \stdClass $field Felddefinition mit den konfigurierten Optionen.
     * @return array Verfügbare Auswahloptionen.
     */
    private function get_options_from_field(\stdClass $field): array
    {
        $options = ['' => get_string('choosedots')];

        if (empty($field->param1)) {
            return $options;
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($field->param1));

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $options[$line] = $line;
        }

        return $options;
    }

    /**
     * Lädt die verfügbaren Partnerhochschulen für Wunschauswahlen.
     *
     * Die Methode liest alle aktiven Hochschulen aus der Datenbank aus
     * und bereitet sie für die Auswahlfelder Erst-, Zweit- und Drittwunsch
     * auf.
     *
     * Für Zweit- und Drittwünsche wird zusätzlich die Option "Keine"
     * bereitgestellt.
     *
     * @param string $fieldname Name des Wunschfeldes.
     * @return array Liste der auswählbaren Hochschulen.
     */
    private function get_university_options(string $fieldname): array
    {
        global $DB;

        $options = [];

        if ($fieldname === 'ZWEITWUNSCH' || $fieldname === 'DRITTWUNSCH') {
            $options['Keine'] = 'Keine';
        }

        $universities = $DB->get_records(
            'dhbwio_universities',
            ['active' => 1],
            'country ASC, name ASC'
        );

        foreach ($universities as $university) {
            $label = trim($university->country . ' - ' . $university->name);
            $options[$label] = $label;
        }

        return $options;
    }

    /**
     * Führt die Validierung der Formulardaten durch.
     *
     * Zunächst wird die Standardvalidierung von Moodle ausgeführt.
     * Anschließend werden projektspezifische Prüfungen über den
     * Validation Manager ergänzt.
     *
     * Alle gefundenen Fehler werden zusammengeführt und an Moodle
     * zurückgegeben.
     *
     * @param array $data Übermittelte Formulardaten.
     * @param array $files Hochgeladene Dateien.
     * @return array Liste der Validierungsfehler.
     */
    public function validation($data, $files): array
    {
        $errors = parent::validation($data, $files);

        debugging(
            'PARENT ERRORS: ' . print_r($errors, true),
            DEBUG_DEVELOPER
        );

        $fields = $this->_customdata['fields'] ?? [];
        $customerrors = validation_manager::validate((object) $data, $fields);

        debugging(
            'CUSTOM ERRORS: ' . print_r($customerrors, true),
            DEBUG_DEVELOPER
        );
        //if (!empty($errors)) {
        //    debugging('DHBWIO form validation errors: ' . print_r($errors, true), DEBUG_DEVELOPER);
        //}

        return array_merge($errors, $customerrors);
    }
}
