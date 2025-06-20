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
 * Restore task for dhbwio activity module
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/dhbwio/backup/moodle2/restore_dhbwio_stepslib.php');

/**
 * dhbwio restore task that provides all the settings and steps to perform one complete restore of the activity
 */
class restore_dhbwio_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // dhbwio only has one step
        $this->add_step(new restore_dhbwio_activity_structure_step('dhbwio_structure', 'dhbwio.xml'));
    }

    /**
     * Define the contents in the activity that must be processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('dhbwio', array('intro'), 'dhbwio');
        $contents[] = new restore_decode_content('dhbwio_universities', array('description'), 'dhbwio_university');
        $contents[] = new restore_decode_content('dhbwio_experience_reports', array('content'), 'dhbwio_experience_report');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('DHBWIOVIEWBYID', '/mod/dhbwio/view.php?id=$1', 'course_module');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied by the restore_logs_processor when restoring logs. 
     * It must return one array of restore_log_rule objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('dhbwio', 'add', 'view.php?id={course_module}', '{dhbwio}');
        $rules[] = new restore_log_rule('dhbwio', 'update', 'view.php?id={course_module}', '{dhbwio}');
        $rules[] = new restore_log_rule('dhbwio', 'view', 'view.php?id={course_module}', '{dhbwio}');

        return $rules;
    }
}