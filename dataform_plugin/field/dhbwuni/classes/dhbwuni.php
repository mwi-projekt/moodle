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
 * DataForm field class for DHBW IO university selection
 *
 * @package    dataformfield_dhbwuni
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class dataformfield_dhbwuni_dhbwuni extends mod_dataform\pluginbase\dataformfield {
    
    protected $_universities = array();
    
    /**
     * Content names this field supports
     */
    public function content_names() {
        return array('selected');
    }
    
    /**
     * Get country name from country code using Moodle's built-in function
     */
    private function get_country_name($country_code) {
        // Use Moodle's built-in country list (supports all languages)
        $countries = get_string_manager()->get_list_of_countries();
        return isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
    }
    
    /**
     * Returns list of available universities grouped by country
     */
    public function get_universities_by_country() {
        global $DB;
        
        // Get the course ID from the dataform
        $courseid = $this->df->course->id;
        
        // Simplified query without countries table
        $sql = "SELECT du.*
                FROM {dhbwio_universities} du
                JOIN {dhbwio} d ON d.id = du.dhbwio
                JOIN {course_modules} cm ON cm.instance = d.id
                JOIN {modules} m ON m.id = cm.module AND m.name = 'dhbwio'
                WHERE d.course = ? AND du.active = 1
                ORDER BY du.country, du.name";
        
        $universities = $DB->get_records_sql($sql, array($courseid));
        
        $grouped = array();
        foreach ($universities as $uni) {
            // Get country name from code
            $country_name = $this->get_country_name($uni->country);
            
            if (!isset($grouped[$country_name])) {
                $grouped[$country_name] = array();
            }
            $grouped[$country_name][] = $uni;
        }
        
        // Sort countries alphabetically
        ksort($grouped);
        
        return $grouped;
    }
    
    /**
     * Get university options as flat list (like select field)
     */
    public function universities_menu($forceget = false) {
        if (!$this->_universities || $forceget) {
            $this->_universities = array();
            $universities = $this->get_universities_by_country();
            
            foreach ($universities as $country => $unis) {
                foreach ($unis as $uni) {
                    $this->_universities[$uni->id] = $uni->name . ' (' . $uni->city . ', ' . $country . ')';
                }
            }
        }
        return $this->_universities;
    }
    
    /**
     * Get university name by ID
     */
    public function get_university_name($universityid) {
        if (empty($universityid)) {
            return '';
        }
        
        $universities = $this->universities_menu();
        return isset($universities[$universityid]) ? $universities[$universityid] : '';
    }
    
    /**
     * Format content for entry
     */
    protected function format_content($entry, array $values = null) {
        $fieldid = $this->id;
        
        // Old contents
        $oldcontents = array();
        if (isset($entry->{"c{$fieldid}_content"})) {
            $oldcontents[] = $entry->{"c{$fieldid}_content"};
        }
        
        // New contents
        $contents = array();
        $selected = null;
        
        if (!empty($values)) {
            foreach ($values as $name => $value) {
                if ($name == 'selected' && !empty($value)) {
                    $selected = (int) $value;
                }
            }
        }
        
        // Add the content
        if (!is_null($selected)) {
            $contents[] = $selected;
        }
        
        return array($contents, $oldcontents);
    }
    
    /**
     * Get SQL comparison for search
     */
    protected function get_sql_compare_text($column = 'content') {
        global $DB;
        
        $alias = $this->get_sql_alias();
        return $DB->sql_compare_text("$alias.$column", 255);
    }
    
    /**
     * Get search value by university name
     */
    public function get_search_value($value) {
        $universities = $this->universities_menu();
        
        // Search by name (case insensitive)
        foreach ($universities as $id => $name) {
            if (stripos($name, $value) !== false) {
                return $id;
            }
        }
        
        return '#'; // Not found
    }
    
    /**
     * Get search SQL
     */
    public function get_search_sql($search) {
        if (!$search) {
            return null;
        }
        
        // Convert the search value to university ID
        if (isset($search[3])) {
            $search[3] = $this->get_search_value($search[3]);
        }
        
        return parent::get_search_sql($search);
    }
    
    /**
     * Prepare import content
     */
    public function prepare_import_content($data, $importsettings, $csvrecord = null, $entryid = null) {
        // Import only from csv
        if (!$csvrecord) {
            return $data;
        }
        
        // There is only one import pattern for this field
        $importsetting = reset($importsettings);
        
        $fieldid = $this->id;
        $csvname = $importsetting['name'];
        $label = !empty($csvrecord[$csvname]) ? $csvrecord[$csvname] : null;
        
        if ($label) {
            $universities = $this->universities_menu();
            
            // Search for university by name
            foreach ($universities as $id => $name) {
                if (stripos($name, $label) !== false) {
                    $data->{"field_{$fieldid}_{$entryid}_selected"} = $id;
                    break;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Check if multiple selection is enabled
     */
    public function is_multiple() {
        return !empty($this->param1);
    }
}