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
 * Email sent event.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dhbwio\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Email sent event class.
 */
class email_sent extends \core\event\base {
    
    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'dataform_entries';
    }
    
    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_email_sent', 'mod_dhbwio');
    }
    
    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $emailtype = isset($this->other['email_type']) ? $this->other['email_type'] : 'unknown';
        return "The user with id '{$this->userid}' sent an email of type '{$emailtype}' " .
               "to user with id '{$this->relateduserid}' for entry with id '{$this->objectid}'.";
    }
    
    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/dhbwio/view.php', ['id' => $this->contextinstanceid]);
    }
    
    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();
        
        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
        
        if (!isset($this->other['email_type'])) {
            throw new \coding_exception('The \'email_type\' value must be set in other.');
        }
    }
}