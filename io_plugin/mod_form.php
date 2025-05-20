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
 * The main mod_dhbwio configuration form.
 *
 * @package     mod_dhbwio
 * @copyright   2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_dhbwio_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('dhbwioname', 'mod_dhbwio'), ['size' => '64']);
        
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'dhbwioname', 'mod_dhbwio');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Add custom settings.
        $mform->addElement('header', 'dhbwiosettings', get_string('dhbwiosettings', 'mod_dhbwio'));
        
        // Enable map view
        $mform->addElement('advcheckbox', 'enablemap', get_string('enable_map_view', 'mod_dhbwio'), 
                           get_string('enable_map_view_desc', 'mod_dhbwio'), ['group' => 1], [0, 1]);
        $mform->setDefault('enablemap', 1);
        
        // Enable experience reports
        $mform->addElement('advcheckbox', 'enablereports', get_string('enable_reports', 'mod_dhbwio'),
                           get_string('enable_reports_desc', 'mod_dhbwio'), ['group' => 1], [0, 1]);
        $mform->setDefault('enablereports', 1);
        
        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}