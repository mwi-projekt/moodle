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

        // DataForm Integration Settings.
        $mform->addElement('header', 'utilization_settings', get_string('utilization_settings', 'mod_dhbwio'));

		 // DataForm Activity Selection.
        $dataforms = $this->get_dataform_activities();
        $mform->addElement('select', 'dataform_id', get_string('dataform_activity', 'mod_dhbwio'), $dataforms);
        $mform->addHelpButton('dataform_id', 'dataform_activity', 'mod_dhbwio');

        // First Wish Field.
        $mform->addElement('text', 'first_wish_field', get_string('first_wish_field', 'mod_dhbwio'));
        $mform->setType('first_wish_field', PARAM_TEXT);
        $mform->setDefault('first_wish_field', 'first_wish');
        $mform->addHelpButton('first_wish_field', 'first_wish_field', 'mod_dhbwio');

        // Second Wish Field.
        $mform->addElement('text', 'second_wish_field', get_string('second_wish_field', 'mod_dhbwio'));
        $mform->setType('second_wish_field', PARAM_TEXT);
        $mform->setDefault('second_wish_field', 'second_wish');
        $mform->addHelpButton('second_wish_field', 'second_wish_field', 'mod_dhbwio');

        // Third Wish Field.
        $mform->addElement('text', 'third_wish_field', get_string('third_wish_field', 'mod_dhbwio'));
        $mform->setType('third_wish_field', PARAM_TEXT);
        $mform->setDefault('third_wish_field', 'third_wish');
        $mform->addHelpButton('third_wish_field', 'third_wish_field', 'mod_dhbwio');

        // First Wish Weight.
        $mform->addElement('text', 'first_wish_weight', get_string('first_wish_weight', 'mod_dhbwio'));
        $mform->setType('first_wish_weight', PARAM_FLOAT);
        $mform->setDefault('first_wish_weight', 100);
        $mform->addRule('first_wish_weight', null, 'required', null, 'client');
        $mform->addRule('first_wish_weight', null, 'numeric', null, 'client');
        $mform->addHelpButton('first_wish_weight', 'first_wish_weight', 'mod_dhbwio');

        // Second Wish Weight.
        $mform->addElement('text', 'second_wish_weight', get_string('second_wish_weight', 'mod_dhbwio'));
        $mform->setType('second_wish_weight', PARAM_FLOAT);
        $mform->setDefault('second_wish_weight', 30);
        $mform->addRule('second_wish_weight', null, 'required', null, 'client');
        $mform->addRule('second_wish_weight', null, 'numeric', null, 'client');
        $mform->addHelpButton('second_wish_weight', 'second_wish_weight', 'mod_dhbwio');

        // Third Wish Weight.
        $mform->addElement('text', 'third_wish_weight', get_string('third_wish_weight', 'mod_dhbwio'));
        $mform->setType('third_wish_weight', PARAM_FLOAT);
        $mform->setDefault('third_wish_weight', 0);
        $mform->addRule('third_wish_weight', null, 'required', null, 'client');
        $mform->addRule('third_wish_weight', null, 'numeric', null, 'client');
        $mform->addHelpButton('third_wish_weight', 'third_wish_weight', 'mod_dhbwio');

        // Enable Utilisation Display.
        $mform->addElement('advcheckbox', 'enable_utilisation', get_string('enable_utilisation', 'mod_dhbwio'));
        $mform->setDefault('enable_utilisation', 1);
        $mform->addHelpButton('enable_utilisation', 'enable_utilisation', 'mod_dhbwio');

        // Cache Duration.
        $cache_options = [
            300 => get_string('cache_5min', 'mod_dhbwio'),
            900 => get_string('cache_15min', 'mod_dhbwio'),
            1800 => get_string('cache_30min', 'mod_dhbwio'),
            3600 => get_string('cache_1hour', 'mod_dhbwio'),
            7200 => get_string('cache_2hours', 'mod_dhbwio'),
            86400 => get_string('cache_1day', 'mod_dhbwio')
        ];
        $mform->addElement('select', 'utilisation_cache_duration', get_string('utilisation_cache_duration', 'mod_dhbwio'), $cache_options);
        $mform->setDefault('utilisation_cache_duration', 1800);
        $mform->addHelpButton('utilisation_cache_duration', 'utilisation_cache_duration', 'mod_dhbwio');
        
        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

	/**
     * Get available DataForm activities in the course.
     */
    private function get_dataform_activities() {
        global $DB;

        $options = [0 => get_string('no_dataform_selected', 'mod_dhbwio')];

        $courseid = $this->current->course ?? 0;
        if ($courseid) {
            $dataforms = $DB->get_records_sql(
                "SELECT cm.id, df.name, cm.instance
                 FROM {course_modules} cm
                 JOIN {modules} m ON m.id = cm.module
                 JOIN {dataform} df ON df.id = cm.instance
                 WHERE cm.course = ? AND m.name = 'dataform'
                 ORDER BY df.name",
                [$courseid]
            );

            foreach ($dataforms as $dataform) {
                $options[$dataform->id] = $dataform->name;
            }
        }

        return $options;
    }

	/**
     * Form validation.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate weight percentages.
        if (isset($data['first_wish_weight']) && ($data['first_wish_weight'] < 0 || $data['first_wish_weight'] > 100)) {
            $errors['first_wish_weight'] = get_string('weight_range_error', 'mod_dhbwio');
        }

        if (isset($data['second_wish_weight']) && ($data['second_wish_weight'] < 0 || $data['second_wish_weight'] > 100)) {
            $errors['second_wish_weight'] = get_string('weight_range_error', 'mod_dhbwio');
        }

        if (isset($data['third_wish_weight']) && ($data['third_wish_weight'] < 0 || $data['third_wish_weight'] > 100)) {
            $errors['third_wish_weight'] = get_string('weight_range_error', 'mod_dhbwio');
        }

        // Validate field names.
        $field_names = ['first_wish_field', 'second_wish_field', 'third_wish_field'];
        foreach ($field_names as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $data[$field])) {
                    $errors[$field] = get_string('invalid_field_name', 'mod_dhbwio');
                }
            }
        }

        return $errors;
    }
}