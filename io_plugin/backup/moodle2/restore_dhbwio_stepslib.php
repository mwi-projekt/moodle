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
 * Define all the restore steps that will be used by the restore_dhbwio_activity_task
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one dhbwio activity
 */
class restore_dhbwio_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('dhbwio', '/activity/dhbwio');
        $paths[] = new restore_path_element('dhbwio_university', '/activity/dhbwio/universities/university');
        $paths[] = new restore_path_element('dhbwio_email_template', '/activity/dhbwio/email_templates/email_template');
        
        if ($userinfo) {
            $paths[] = new restore_path_element('dhbwio_experience_report', '/activity/dhbwio/experience_reports/experience_report');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_dhbwio($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Handle DataForm ID mapping if DataForm is also being restored
        if (!empty($data->dataform_id)) {
            $data->dataform_id = $this->get_mappingid('course_module', $data->dataform_id);
            if (empty($data->dataform_id)) {
                // If DataForm wasn't found, reset to null
                $data->dataform_id = null;
            }
        }

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the dhbwio record
        $newitemid = $DB->insert_record('dhbwio', $data);
        
        // Immediately after inserting, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_dhbwio_university($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->dhbwio = $this->get_new_parentid('dhbwio');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('dhbwio_universities', $data);
        $this->set_mapping('dhbwio_university', $oldid, $newitemid);
    }

    protected function process_dhbwio_email_template($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->dhbwio = $this->get_new_parentid('dhbwio');
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('dhbwio_email_templates', $data);
        $this->set_mapping('dhbwio_email_template', $oldid, $newitemid);
    }

    protected function process_dhbwio_experience_report($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->dhbwio = $this->get_new_parentid('dhbwio');
        $data->university_id = $this->get_mappingid('dhbwio_university', $data->university_id);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Only restore if we have valid university and user mappings
        if ($data->university_id && $data->userid) {
            $newitemid = $DB->insert_record('dhbwio_experience_reports', $data);
            $this->set_mapping('dhbwio_experience_report', $oldid, $newitemid);
        }
    }

    protected function after_execute() {
        // Add dhbwio related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_dhbwio', 'intro', null);
        
        // Add university related files
        $this->add_related_files('mod_dhbwio', 'description', 'dhbwio_university');
        $this->add_related_files('mod_dhbwio', 'university_image', 'dhbwio_university');
        
        // Add experience report related files
        $this->add_related_files('mod_dhbwio', 'report_content', 'dhbwio_experience_report');
        $this->add_related_files('mod_dhbwio', 'report_attachment', 'dhbwio_experience_report');
    }
}