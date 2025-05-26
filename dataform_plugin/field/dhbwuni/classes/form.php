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
 * Field form for DHBW IO university field
 *
 * @subpackage dhbwuni
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class dataformfield_dhbwuni_form extends mod_dataform\pluginbase\dataformfieldform {
    
    /**
     * Field specific definition
     */
    protected function field_definition() {
        global $DB;
        
        $mform =& $this->_form;
        
        // Check if there are DHBW IO instances in this course
        $courseid = $this->_field->df->course->id;
        
        try {
            $dhbwio_count = $DB->count_records_sql(
                "SELECT COUNT(*) 
                 FROM {dhbwio} d 
                 JOIN {course_modules} cm ON cm.instance = d.id 
                 JOIN {modules} m ON m.id = cm.module AND m.name = 'dhbwio'
                 WHERE d.course = ?", 
                array($courseid)
            );
            
            if ($dhbwio_count == 0) {
                $mform->addElement('static', 'no_dhbwio', 
                    get_string('no_dhbwio_instance', 'dataformfield_dhbwuni'), 
                    html_writer::div(
                        get_string('no_dhbwio_instance_desc', 'dataformfield_dhbwuni'),
                        'alert alert-warning'
                    )
                );
            } else {
                // Show available universities count
                $uni_count = $DB->count_records_sql(
                    "SELECT COUNT(*) 
                     FROM {dhbwio_universities} du
                     JOIN {dhbwio} d ON d.id = du.dhbwio
                     JOIN {course_modules} cm ON cm.instance = d.id
                     JOIN {modules} m ON m.id = cm.module AND m.name = 'dhbwio'
                     WHERE d.course = ? AND du.active = 1", 
                    array($courseid)
                );
                
                $mform->addElement('static', 'university_info', 
                    get_string('available_universities', 'dataformfield_dhbwuni'),
                    get_string('universities_count', 'dataformfield_dhbwuni', $uni_count)
                );
            }
        } catch (Exception $e) {
            // If there's an error checking universities, show a generic message
            $mform->addElement('static', 'check_error', 
                get_string('field_info', 'dataformfield_dhbwuni'),
                get_string('ensure_dhbwio_installed', 'dataformfield_dhbwuni')
            );
        }
        
        // Simple info about the field
        $mform->addElement('static', 'field_info', 
            get_string('fieldtype', 'dataformfield_dhbwuni'),
            get_string('universities_alphabetical', 'dataformfield_dhbwuni')
        );
    }
    
    /**
     * Default content definition - with error handling
     */
    public function definition_default_content() {
        $mform = &$this->_form;
        $field = &$this->_field;
        
        try {
            // Get available universities for default content
            $universities = $field->universities_menu();
            
            if (!empty($universities)) {
                $label = get_string('fielddefaultvalue', 'dataform');
                $options = array('' => get_string('choose', 'dataformfield_dhbwuni')) + $universities;
                $mform->addElement('select', 'contentdefault', $label, $options);
            } else {
                $mform->addElement('static', 'no_default', 
                    get_string('fielddefaultvalue', 'dataform'),
                    get_string('no_universities_for_default', 'dataformfield_dhbwuni')
                );
            }
        } catch (Exception $e) {
            // If there's an error loading universities, show a message
            $mform->addElement('static', 'default_error', 
                get_string('fielddefaultvalue', 'dataform'),
                'Error loading universities: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Data preprocessing
     */
    public function data_preprocessing(&$data) {
        $field = &$this->_field;
        
        // Default content
        $data->contentdefault = $field->defaultcontent;
    }
    
    /**
     * Get default content data
     */
    protected function get_data_default_content(\stdClass $data) {
        if (!empty($data->contentdefault)) {
            return $data->contentdefault;
        }
        return null;
    }
    
    /**
     * Validate default content
     */
    protected function validation_default_content(array $data) {
        $errors = array();
        
        if (!empty($data['contentdefault'])) {
            $selected = $data['contentdefault'];
            
            try {
                // Check if the selected university still exists and is active
                global $DB;
                $exists = $DB->record_exists('dhbwio_universities', array(
                    'id' => $selected,
                    'active' => 1
                ));
                
                if (!$exists) {
                    $errors['contentdefault'] = get_string('invaliddefaultvalue', 'dataformfield_dhbwuni');
                }
            } catch (Exception $e) {
                $errors['contentdefault'] = 'Error validating university: ' . $e->getMessage();
            }
        }
        
        return $errors;
    }
    
    /**
     * Additional validation for the field
     */
    public function validation($data, $files) {
        global $DB;
        
        $errors = parent::validation($data, $files);
        
        try {
            // Check if DHBW IO instance exists in course
            $courseid = $this->_field->df->course->id;
            $dhbwio_exists = $DB->record_exists_sql(
                "SELECT 1 
                 FROM {dhbwio} d 
                 JOIN {course_modules} cm ON cm.instance = d.id 
                 JOIN {modules} m ON m.id = cm.module AND m.name = 'dhbwio'
                 WHERE d.course = ?", 
                array($courseid)
            );
            
            if (!$dhbwio_exists) {
                $errors['no_dhbwio'] = get_string('no_dhbwio_instance', 'dataformfield_dhbwuni');
            }
        } catch (Exception $e) {
            $errors['validation_error'] = 'Validation error: ' . $e->getMessage();
        }
        
        return $errors;
    }
}