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

namespace mod_dhbwio\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class learning_agreement_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $cmid  = $this->_customdata['cmid'];

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('filemanager', 'la_file', get_string('la_file_label', 'mod_dhbwio'), null, [
            'subdirs'        => 0,
            'maxfiles'       => 1,
            'accepted_types' => ['.pdf', '.docx', '.doc'],
        ]);
        $mform->addRule('la_file', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(true, get_string('la_upload_btn', 'mod_dhbwio'));
    }
}
