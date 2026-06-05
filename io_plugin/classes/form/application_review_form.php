<?php

namespace mod_dhbwio\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use mod_dhbwio\local\dataform\field_manager;
use mod_dhbwio\local\dataform\status_manager;

/**
 * Formular zur fachlichen Prüfung und Bearbeitung einer Bewerbung.
 *
 * Diese Klasse stellt das Formular für Mitarbeitende der International
 * Office Verwaltung bereit. Über das Formular können Bewerbungen
 * bewertet, kommentiert und hinsichtlich ihrer Hochschulwünsche
 * freigegeben oder abgelehnt werden.
 *
 * Zusätzlich ermöglicht das Formular die Vergabe eines Bearbeitungs-
 * status, um den Fortschritt der Bewerbung nachvollziehbar abzubilden.
 *
 * Nutzen:
 * - Zentrale Bearbeitung eingegangener Bewerbungen
 * - Verwaltung des Bewerbungsstatus
 * - Dokumentation von Rückmeldungen und Kommentaren
 * - Freigabe oder Ablehnung von Hochschulwünschen
 */
class application_review_form extends \moodleform
{
    /**
     * Erstellt das Prüfungsformular für eine Bewerbung.
     *
     * Die Methode erzeugt alle für die Bearbeitung benötigten
     * Formularelemente. Dazu gehören technische Identifikatoren,
     * die Auswahl des Bewerbungsstatus, ein Kommentarfeld sowie
     * die Freigabeoptionen für Erst-, Zweit- und Drittwunsch.
     *
     * Die verfügbaren Statuswerte werden zentral über den
     * Status Manager geladen.
     *
     * @return void
     */

    public function definition(): void
    {
        $mform = $this->_form;

        $id = $this->_customdata['id'] ?? 0;
        $dataid = $this->_customdata['dataid'] ?? 0;
        $entryid = $this->_customdata['entryid'] ?? 0;

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'dataid', $dataid);
        $mform->setType('dataid', PARAM_INT);

        $mform->addElement('hidden', 'entryid', $entryid);
        $mform->setType('entryid', PARAM_INT);

        $statusoptions = status_manager::get_options();

        $mform->addElement('select', 'statusid', 'Status der Bewerbung', $statusoptions);
        $acceptedoptions = $this->get_accepted_choice_options();

        $mform->addElement(
            'select',
            'acceptedchoice',
            'Angenommen für',
            $acceptedoptions
        );
        $mform->setType('acceptedchoice', PARAM_ALPHA);

        $mform->setType('statusid', PARAM_INT);
        $mform->addRule('statusid', get_string('required'), 'required', null, 'client');

        $fields = $this->_customdata['fields'] ?? [];

        foreach ($fields as $field) {
            if (!field_manager::is_review_field($field)) {
                continue;
            }

            $this->add_review_field($field);
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Adds a review field to the application review form.
     *
     * Creates the appropriate Moodle form element based on the configured
     * field type. Supported field types include textarea, radiobutton,
     * select and text fields. The method is used to dynamically generate
     * review fields for International Office staff.
     *
     * @param \stdClass $field Field definition record.
     * @return void
     */
    private function add_review_field(\stdClass $field): void
    {
        $mform = $this->_form;

        $name = $field->name;
        $label = $this->get_display_label($field);

        switch ($field->type) {
            case 'textarea':
                $mform->addElement('textarea', $name, $label, [
                    'rows' => 4,
                    'cols' => 80,
                ]);
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

            case 'select':
                $options = $this->get_options_from_field($field);
                $mform->addElement('select', $name, $label, $options);
                $mform->setType($name, PARAM_TEXT);
                break;

            case 'text':
            default:
                $mform->addElement('text', $name, $label);
                $mform->setType($name, PARAM_TEXT);
                break;
        }
    }
    // Diese Function dient als Übergang bevor tatsächliche Datenbankänderungen der Description vorgenommen werden.
    private function get_display_label(\stdClass $field): string
    {
        $labels = [
            'KOMMENTAR_IO' => 'Kommentar des International Office',
            'SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST' => 'Freigabe Erstwunsch',
            'SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT' => 'Freigabe Zweitwunsch',
            'SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT' => 'Freigabe Drittwunsch',
        ];

        return $labels[$field->name] ?? $field->description ?: $field->name;
    }
    /**
     * Extracts selectable options from a field definition.
     *
     * Reads the field configuration stored in param1 and converts the
     * line-separated values into an associative array that can be used
     * by Moodle form elements such as select boxes and radio button groups.
     *
     * @param \stdClass $field Field definition record.
     * @return array Available options in key-value format.
     */
    private function get_options_from_field(\stdClass $field): array
    {
        $options = [];

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
    private function get_accepted_choice_options(): array
    {
        $firstchoice = $this->_customdata['firstchoice'] ?? '';
        $secondchoice = $this->_customdata['secondchoice'] ?? '';
        $thirdchoice = $this->_customdata['thirdchoice'] ?? '';

        return [
            '' => 'Keine Auswahl',
            'first' => 'Erstwunsch' . ($firstchoice !== '' ? ' – ' . $firstchoice : ''),
            'second' => 'Zweitwunsch' . ($secondchoice !== '' ? ' – ' . $secondchoice : ''),
            'third' => 'Drittwunsch' . ($thirdchoice !== '' ? ' – ' . $thirdchoice : ''),
        ];
    }
}
