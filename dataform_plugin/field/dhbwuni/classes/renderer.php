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
 * Renderer for DHBW IO university field
 *
 * @package    dataformfield_dhbwuni
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class dataformfield_dhbwuni_renderer extends mod_dataform\pluginbase\dataformfieldrenderer {
    
    /**
     * Handle pattern replacements
     */
    protected function replacements(array $patterns, $entry, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->name;
        $edit = !empty($options['edit']);
        
        $replacements = array_fill_keys(array_keys($patterns), '');
        
        $editdisplayed = false;
        foreach ($patterns as $pattern => $cleanpattern) {
            // Edit
            if ($edit && !$editdisplayed && !$this->is_noedit($pattern)) {
                $params = array('required' => $this->is_required($pattern));
                $replacements[$pattern] = array(array($this, 'display_edit'), array($entry, $params));
                $editdisplayed = true;
                continue;
            }
            
            // Browse
            if ($cleanpattern == "[[$fieldname:key]]") {
                $replacements[$pattern] = $this->display_browse($entry, array('key' => true));
            } else {
                $replacements[$pattern] = $this->display_browse($entry);
            }
        }
        
        return $replacements;
    }
    
    /**
     * Display edit form
     */
    public function display_edit(&$mform, $entry, array $options = null) {
        $field = $this->_field;
        $fieldid = $field->id;
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_{$entryid}_selected";
        $required = !empty($options['required']);
        
        // Get current selection
        $selected = !empty($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : 0;
        
        // Check for default value
        if (!$selected && !empty($field->defaultcontent)) {
            $selected = $field->defaultcontent;
        }
        
        // Get universities (now as flat list)
        $universities = $field->get_universities_by_country();
        
        if (empty($universities)) {
            // Show warning if no universities available
            $mform->addElement('static', $fieldname, null, 
                html_writer::div(
                    get_string('no_universities_available', 'dataformfield_dhbwuni'),
                    'alert alert-warning'
                )
            );
            return;
        }
        
        // Create the select element
        list($elem, $separators) = $this->render($mform, $fieldname, $universities, $selected, $required);
        
        // Add element directly to form - no groups
        $mform->addElement($elem);
        
        // Required validation
        if ($required) {
            $mform->addRule($fieldname, null, 'required', null, 'client');
        }
    }
    
    /**
     * Display browse/view mode
     */
    public function display_browse($entry, $params = null) {
        $field = $this->_field;
        $fieldid = $field->id;
        
        if (isset($entry->{"c{$fieldid}_content"})) {
            $selected = (int) $entry->{"c{$fieldid}_content"};
            
            if (!empty($params['key'])) {
                return $selected ? $selected : '0';
            }
            
            if ($selected) {
                return $field->get_university_name($selected);
            }
        }
        
        return '';
    }
    
    /**
     * Render the select element with flat options
     */
    protected function render(&$mform, $fieldname, $universities, $selected, $required = false) {
        // Build flat options array
        $options = array();
        
        // Add empty option
        $options[0] = get_string('choose', 'dataformfield_dhbwuni');
        
        // Flatten universities array and add country to name
        $flat_universities = array();
        foreach ($universities as $country => $unis) {
            foreach ($unis as $uni) {
                $flat_universities[] = (object) array(
                    'id' => $uni->id,
                    'name' => $uni->name,
                    'country' => $country
                );
            }
        }
        
        // Sort alphabetically by university name
        usort($flat_universities, function($a, $b) {
            return strcmp($a->name, $b->name);
        });
        
        // Build options
        foreach ($flat_universities as $uni) {
            $options[$uni->id] = $uni->name . ' (' . $uni->country . ')';
        }
        
        // Create simple select element without extra wrappers
        $select = &$mform->createElement('select', $fieldname, null, $options);
        
        // Set selected value
        if ($selected) {
            $select->setSelected($selected);
        }
        
        return array($select, null);
    }
    
    /**
     * Set required validation
     */
    protected function set_required(&$mform, $fieldname, $selected) {
        $mform->addRule("{$fieldname}_selected", null, 'required', null, 'client');
    }
    
    /**
     * Get pattern import settings
     */
    public function get_pattern_import_settings(&$mform, $patternname, $header) {
        $field = $this->_field;
        $fieldid = $field->id;
        $fieldname = $field->name;
        
        // Only base pattern can be imported
        if ($patternname != $fieldname) {
            return array(array(), array());
        }
        
        return parent::get_pattern_import_settings($mform, $patternname, $header);
    }
    
    /**
     * Array of patterns this field supports
     */
    protected function patterns() {
        $fieldname = $this->_field->name;
        
        $patterns = parent::patterns();
        $patterns["[[$fieldname]]"] = array(true, $fieldname);
        $patterns["[[$fieldname:key]]"] = array(false);
        
        return $patterns;
    }
}