<?php
namespace mod_dhbwio\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class application_review_form extends \moodleform {

    public function definition(): void {
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

        $statusoptions = [
            'EINGEREICHT' => 'Eingereicht',
            'IN_PRUEFUNG' => 'In Prüfung',
            'ANGENOMMEN' => 'Angenommen',
            'ABGELEHNT' => 'Abgelehnt',
        ];

        $mform->addElement('select', 'STATUS_BEWERBUNG', 'Status der Bewerbung', $statusoptions);
        $mform->setType('STATUS_BEWERBUNG', PARAM_TEXT);
        $mform->addRule('STATUS_BEWERBUNG', get_string('required'), 'required', null, 'client');

        $mform->addElement('textarea', 'KOMMENTAR_IO', 'Kommentar', [
            'rows' => 4,
            'cols' => 80,
        ]);
        $mform->setType('KOMMENTAR_IO', PARAM_TEXT);

        $radiooptions = [
            $mform->createElement('radio', 'SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST', '', 'Ja', 'Ja'),
            $mform->createElement('radio', 'SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST', '', 'Nein', 'Nein'),
        ];
        $mform->addGroup($radiooptions, 'SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST_group', 'Erlaubnis zum Erstwunsch', [' '], false);

        $radiooptions = [
            $mform->createElement('radio', 'SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT', '', 'Ja', 'Ja'),
            $mform->createElement('radio', 'SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT', '', 'Nein', 'Nein'),
        ];
        $mform->addGroup($radiooptions, 'SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT_group', 'Erlaubnis zum Zweitwunsch', [' '], false);

        $radiooptions = [
            $mform->createElement('radio', 'SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT', '', 'Ja', 'Ja'),
            $mform->createElement('radio', 'SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT', '', 'Nein', 'Nein'),
        ];
        $mform->addGroup($radiooptions, 'SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT_group', 'Erlaubnis zum Drittwunsch', [' '], false);

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}