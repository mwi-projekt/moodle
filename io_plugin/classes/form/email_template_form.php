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
 * Email template form for DHBW International Office.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dhbwio\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating/editing email templates.
 */
class email_template_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $cmid = $this->_customdata['cmid'];
        $template = isset($this->_customdata['template']) ? $this->_customdata['template'] : null;

        // Add hidden fields
        $mform->addElement('hidden', 'id', $cmid);
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'action', $template ? 'update' : 'add');
        $mform->setType('action', PARAM_ALPHA);
        
        if ($template) {
            $mform->addElement('hidden', 'template', $template->id);
            $mform->setType('template', PARAM_INT);
        }

        // Template name
        $mform->addElement('text', 'name', get_string('email_template_name', 'mod_dhbwio'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Template type
        $templatetypes = [
            'report_submitted' => get_string('email_template_report_submitted', 'mod_dhbwio'),
            'report_approved' => get_string('email_template_report_approved', 'mod_dhbwio')
        ];
        
        if ($template) {
            // If editing, disable type change
            $mform->addElement('static', 'type_display', get_string('email_template_type', 'mod_dhbwio'), 
                              $templatetypes[$template->type]);
            $mform->addElement('hidden', 'type', $template->type);
            $mform->setType('type', PARAM_ALPHA);
        } else {
            // If adding, allow selecting type
            $mform->addElement('select', 'type', get_string('email_template_type', 'mod_dhbwio'), $templatetypes);
            $mform->addRule('type', null, 'required', null, 'client');
        }

        // Subject
        $mform->addElement('text', 'subject', get_string('email_template_subject', 'mod_dhbwio'), ['size' => '64']);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');
        $mform->addRule('subject', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Language
        $languages = [
            'en' => get_string('en', 'core_langconfig'),
            'de' => get_string('de', 'core_langconfig')
        ];
        
        if ($template) {
            // If editing, disable language change
            $mform->addElement('static', 'lang_display', get_string('language'), $languages[$template->lang]);
            $mform->addElement('hidden', 'lang', $template->lang);
            $mform->setType('lang', PARAM_ALPHA);
        } else {
            // If adding, allow selecting language
            $mform->addElement('select', 'lang', get_string('language'), $languages);
            $mform->setDefault('lang', 'en');
            $mform->addHelpButton('lang', 'template_language', 'mod_dhbwio');
        }

        // Body - Using HTML editor
        $editoroptions = ['trusttext' => true, 'subdirs' => 0, 'maxfiles' => EDITOR_UNLIMITED_FILES, 
                          'context' => $this->_customdata['context']];
        $mform->addElement('editor', 'body_editor', get_string('email_template_body', 'mod_dhbwio'), null, $editoroptions);
        $mform->setType('body_editor', PARAM_RAW);
        $mform->addRule('body_editor', null, 'required', null, 'client');
        $mform->addHelpButton('body_editor', 'email_template_body', 'mod_dhbwio');

        // Enabled
        $mform->addElement('advcheckbox', 'enabled', get_string('email_template_enabled', 'mod_dhbwio'), 
                           get_string('email_template_enabled_desc', 'mod_dhbwio'), ['group' => 1], [0, 1]);
        $mform->setDefault('enabled', 1);

        // Add standard buttons
        $this->add_action_buttons();
    }

    /**
     * Validation function.
     *
     * @param array $data Form data
     * @param array $files Form files
     * @return array Validation errors
     */
    public function validation($data, $files) {
        global $DB;
        
        $errors = parent::validation($data, $files);

        // If adding a new template, check if the type and language already exists for this instance
        if ($data['action'] === 'add') {
            $exists = $DB->record_exists('dhbwio_email_templates', [
                'dhbwio' => $this->_customdata['dhbwio_id'],
                'type' => $data['type'],
                'lang' => $data['lang']
            ]);
            
            if ($exists) {
                $errors['type'] = get_string('template_type_lang_exists', 'mod_dhbwio');
            }
        }

        return $errors;
    }
    
    /**
     * Set form data from template record.
     *
     * @param \stdClass $template Template record
     */
    public function set_data($template) {
        // Prepare body for editor
        if (!empty($template->body)) {
            $template->body_editor = [
                'text' => $template->body,
                'format' => $template->bodyformat
            ];
        }

        parent::set_data($template);
    }
}