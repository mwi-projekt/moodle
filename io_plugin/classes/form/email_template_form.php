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
        global $DB;
        
        $mform = $this->_form;
        $cmid = $this->_customdata['cmid'];
        $template = isset($this->_customdata['template']) ? $this->_customdata['template'] : null;
        $context = $this->_customdata['context'];
        $dhbwio_id = $this->_customdata['dhbwio_id'];

        // Add hidden fields
        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);
        
        $mform->addElement('hidden', 'action', 'edit');
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
        $mform->addHelpButton('name', 'email_template_name', 'mod_dhbwio');

        // Template type
        $templatetypes = [
            'application_received' => get_string('template_application_received', 'mod_dhbwio'),
            'application_approved' => get_string('template_application_accepted', 'mod_dhbwio'),
            'application_rejected' => get_string('template_application_rejected', 'mod_dhbwio'),
            'application_inquiry' => get_string('template_application_inquiry', 'mod_dhbwio')
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
            $mform->addHelpButton('type', 'email_template_type', 'mod_dhbwio');
        }

        // Language
        $stringmanager = get_string_manager();
        $languageList = $stringmanager->get_list_of_languages();
        
        if ($template) {
            // If editing, disable language change
            $mform->addElement('static', 'lang_display', get_string('language'), $languageList[$template->lang]);
            $mform->addElement('hidden', 'lang', $template->lang);
            $mform->setType('lang', PARAM_ALPHA);
        } else {
            // If adding, allow selecting language
            $mform->addElement('select', 'lang', get_string('language'), $languageList);
            $mform->setDefault('lang', 'en');
            $mform->addHelpButton('lang', 'template_language', 'mod_dhbwio');
        }

        // Subject
        $mform->addElement('text', 'subject', get_string('email_template_subject', 'mod_dhbwio'), ['size' => '64']);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');
        $mform->addRule('subject', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('subject', 'email_template_subject', 'mod_dhbwio');

        // Body - Using HTML editor with optimized options
        $editoroptions = [
            'trusttext' => true,
            'subdirs' => false,
            'maxfiles' => 0, // No file uploads for email templates
            'maxbytes' => 0,
            'context' => $context,
            'noclean' => true,
            'enable_filemanagement' => false
        ];
        
        $mform->addElement('editor', 'body_editor', get_string('email_template_body', 'mod_dhbwio'), 
                          ['rows' => 15, 'cols' => 80], $editoroptions);
        $mform->setType('body_editor', PARAM_RAW);
        $mform->addRule('body_editor', null, 'required', null, 'client');
        $mform->addHelpButton('body_editor', 'email_template_body', 'mod_dhbwio');

        // Enabled
        $mform->addElement('advcheckbox', 'enabled', get_string('email_template_enabled', 'mod_dhbwio'), 
                           get_string('email_template_enabled_desc', 'mod_dhbwio'), ['group' => 1], [0, 1]);
        $mform->setDefault('enabled', 1);
        $mform->addHelpButton('enabled', 'email_template_enabled', 'mod_dhbwio');

        // Add variable help section before action buttons
        $this->add_variable_help_section($mform, $context, $dhbwio_id);

        // Add standard buttons
        $this->add_action_buttons();
    }
	
	/**
	 * Add variable help section to form.
	 *
	 * @param object $mform Form object
	 * @param object $context Context object
	 * @param int $dhbwio_id DHBW IO instance ID
	 */
	private function add_variable_help_section($mform, $context, $dhbwio_id) {
		global $DB;
		
		$mform->addElement('header', 'variablehelp', get_string('template_variables', 'mod_dhbwio'));
		$mform->setExpanded('variablehelp', false);
		
		// Standard variables - removed redundant ones
		$standardvars = [
			'STUDENT_NAME' => get_string('variable_student_name', 'mod_dhbwio'),
			'STUDENT_FIRSTNAME' => get_string('variable_student_firstname', 'mod_dhbwio'),
			'STUDENT_EMAIL' => get_string('variable_student_email', 'mod_dhbwio'),
			'SUBMISSION_DATE' => get_string('variable_application_date', 'mod_dhbwio')
			'UNIVERSITY_CHOICES' => get_string('variable_university_choices', 'mod_dhbwio')
		];
		
		$standardhtml = '<div class="alert alert-info"><h6>' . get_string('standard_variables', 'mod_dhbwio') . '</h6><ul>';
		foreach ($standardvars as $var => $desc) {
			$standardhtml .= '<li><code>{' . $var . '}</code> - ' . $desc . '</li>';
		}
		$standardhtml .= '</ul></div>';
		
		$mform->addElement('static', 'standard_variables', get_string('standard_variables', 'mod_dhbwio'), $standardhtml);
		
		// Dataform variables (if available)
		$dataformfields = dhbwio_get_dataform_fields($dhbwio_id);
		
		if (!empty($dataformfields)) {
			$dataformhtml = '<div class="alert alert-success"><h6>' . get_string('dataform_variables', 'mod_dhbwio') . '</h6>';
			
			// Add note about important dataform variables
			$dataformhtml .= '<p><strong>' . get_string('important_dataform_vars', 'mod_dhbwio') . '</strong></p>';
			$dataformhtml .= '<ul>';
			$dataformhtml .= '<li><code>{DATAFORM_STATUS_BEWERBUNG}</code> - ' . get_string('dataform_status_desc', 'mod_dhbwio') . '</li>';
			$dataformhtml .= '<li><code>{DATAFORM_ERSTWUNSCH}</code>, <code>{DATAFORM_ZWEITWUNSCH}</code>, <code>{DATAFORM_DRITTWUNSCH}</code> - ' . get_string('dataform_wishes_desc', 'mod_dhbwio') . '</li>';
			$dataformhtml .= '<li><code>{DATAFORM_KOMMENTAR_IO}</code> - ' . get_string('dataform_comment_desc', 'mod_dhbwio') . '</li>';
			$dataformhtml .= '</ul>';
			
			$dataformhtml .= '<p><strong>' . get_string('all_dataform_vars', 'mod_dhbwio') . '</strong></p>';
			$dataformhtml .= '<ul>';
			foreach ($dataformfields as $field) {
				$dataformhtml .= '<li><code>{DATAFORM_' . strtoupper($field['name']) . '}</code> - ' . $field['description'] . '</li>';
			}
			$dataformhtml .= '</ul></div>';
			
			$mform->addElement('static', 'dataform_variables', get_string('dataform_variables', 'mod_dhbwio'), $dataformhtml);
		} else {
			$nofieldshtml = '<div class="alert alert-warning"><p>' . get_string('no_dataform_fields', 'mod_dhbwio') . '</p></div>';
			$mform->addElement('static', 'no_dataform_fields', get_string('dataform_variables', 'mod_dhbwio'), $nofieldshtml);
		}
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
        if (!isset($data['template']) || empty($data['template'])) {
            $exists = $DB->record_exists('dhbwio_email_templates', [
                'dhbwio' => $this->_customdata['dhbwio_id'],
                'type' => $data['type'],
                'lang' => $data['lang']
            ]);
            
            if ($exists) {
                $errors['type'] = get_string('template_type_lang_exists', 'mod_dhbwio');
            }
        }

        // Validate that required variables are present based on template type
        $requiredvars = $this->get_required_variables_for_type($data['type']);
        if (!empty($requiredvars) && isset($data['body_editor']['text'])) {
            $body = $data['body_editor']['text'];
            $missingvars = [];
            
            foreach ($requiredvars as $var) {
                if (strpos($body, '{' . $var . '}') === false) {
                    $missingvars[] = $var;
                }
            }
            
            if (!empty($missingvars)) {
                $errors['body_editor'] = get_string('missing_required_variables', 'mod_dhbwio', implode(', ', $missingvars));
            }
        }

        return $errors;
    }
    
    /**
     * Get required variables for template type.
     *
     * @param string $type Template type
     * @return array Required variables
     */
    private function get_required_variables_for_type($type) {
		$required = [
			'application_received' => ['STUDENT_NAME'],
			'application_approved' => ['STUDENT_NAME'],
			'application_rejected' => ['STUDENT_NAME'],
			'application_inquiry' => ['STUDENT_NAME']
		];
		
		return isset($required[$type]) ? $required[$type] : [];
	}
    
    /**
     * Set form data from template record.
     *
     * @param \stdClass $template Template record
     */
    public function set_data($template) {
        if (!empty($template->body)) {
            $template->body_editor = [
                'text' => $template->body,
                'format' => isset($template->bodyformat) ? $template->bodyformat : FORMAT_HTML,
                'itemid' => 0
            ];
        }

        parent::set_data($template);
    }
}