<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Form for creating and editing Fristen (deadlines).
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dhbwio\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class frist_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $cmid  = $this->_customdata['cmid'];

        // Art der Frist
        $artoptionen = [
            'stipendium'          => get_string('frist_art_stipendium', 'mod_dhbwio'),
            'bewerbung'           => get_string('frist_art_bewerbung', 'mod_dhbwio'),
            'learning_agreement'  => get_string('frist_art_learning_agreement', 'mod_dhbwio'),
        ];
        $mform->addElement('select', 'art', get_string('frist_art', 'mod_dhbwio'), $artoptionen);
        $mform->addRule('art', get_string('required', 'mod_dhbwio'), 'required', null, 'client');
        $mform->setType('art', PARAM_ALPHA);

        // Studiengang – dynamisch aus dhbwio_studyprograms, damit Fristen und
        // Bewerbungen immer auf denselben Datenstand zugreifen.
        $studiengaenge = ['alle' => get_string('frist_alle_studiengaenge', 'mod_dhbwio')];
        $studiengaenge += self::get_studyprogram_options();
        $mform->addElement('select', 'studiengang', get_string('frist_studiengang', 'mod_dhbwio'), $studiengaenge);
        $mform->addRule('studiengang', get_string('required', 'mod_dhbwio'), 'required', null, 'client');
        $mform->setType('studiengang', PARAM_TEXT);

        // Jahrgang (Freitext)
        $mform->addElement('text', 'jahrgang', get_string('frist_jahrgang', 'mod_dhbwio'),
            ['size' => 20, 'placeholder' => 'z.B. 2023']);
        $mform->addRule('jahrgang', get_string('required', 'mod_dhbwio'), 'required', null, 'client');
        $mform->setType('jahrgang', PARAM_TEXT);

        // Datum der Frist
        $mform->addElement('date_selector', 'deadline', get_string('frist_deadline', 'mod_dhbwio'));
        $mform->addRule('deadline', get_string('required', 'mod_dhbwio'), 'required', null, 'client');

        // Kommentar (optional)
        $mform->addElement('textarea', 'kommentar', get_string('frist_kommentar', 'mod_dhbwio'),
            ['rows' => 4, 'cols' => 60, 'placeholder' => get_string('frist_kommentar_placeholder', 'mod_dhbwio')]);
        $mform->setType('kommentar', PARAM_TEXT);

        // Hidden fields
        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        if (!empty($this->_customdata['fristid'])) {
            $mform->addElement('hidden', 'fristid', $this->_customdata['fristid']);
            $mform->setType('fristid', PARAM_INT);
        }

        $this->add_action_buttons(true, get_string('frist_save', 'mod_dhbwio'));
    }

    /**
     * Liefert die aktiven Studiengänge aus dhbwio_studyprograms als ID => Label.
     *
     * @return array
     */
    private static function get_studyprogram_options(): array
    {
        global $DB;

        $options = [];
        $records = $DB->get_records('dhbwio_studyprograms', ['active' => 1], 'sortorder ASC, de_name ASC');
        $lang    = current_language();

        foreach ($records as $record) {
            $label = ($lang === 'en') ? $record->en_name : $record->de_name;
            $options[(string) $record->id] = $label;
        }

        return $options;
    }
}
