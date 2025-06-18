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
 * Define all the backup steps that will be used by the backup_dhbwio_activity_task
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete dhbwio structure for backup, with file and id annotations
 */
class backup_dhbwio_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $dhbwio = new backup_nested_element('dhbwio', array('id'), array(
            'name', 'intro', 'introformat', 'timecreated', 'timemodified',
            'enablemap', 'enablereports', 'dataform_id', 'first_wish_field',
            'second_wish_field', 'third_wish_field', 'first_wish_weight',
            'second_wish_weight', 'third_wish_weight', 'enable_utilisation',
            'utilisation_cache_duration'
        ));

        $universities = new backup_nested_element('universities');
        $university = new backup_nested_element('university', array('id'), array(
            'name', 'country', 'city', 'address', 'postal_code',
            'latitude', 'longitude', 'website', 'description', 'descriptionformat',
            'requirements', 'available_slots', 'semester_start', 'semester_end',
            'semester_fees', 'fee_currency', 'accommodation_type', 'active',
            'timemodified'
        ));

        $email_templates = new backup_nested_element('email_templates');
        $email_template = new backup_nested_element('email_template', array('id'), array(
            'name', 'type', 'lang', 'subject', 'body', 'bodyformat',
            'timemodified', 'enabled'
        ));

        $experience_reports = new backup_nested_element('experience_reports');
        $experience_report = new backup_nested_element('experience_report', array('id'), array(
            'university_id', 'userid', 'title', 'content', 'contentformat',
            'rating', 'timemodified', 'timecreated', 'visible'
        ));

        // Build the tree
        $dhbwio->add_child($universities);
        $universities->add_child($university);

        $dhbwio->add_child($email_templates);
        $email_templates->add_child($email_template);

        $dhbwio->add_child($experience_reports);
        $experience_reports->add_child($experience_report);

        // Define sources
        $dhbwio->set_source_table('dhbwio', array('id' => backup::VAR_ACTIVITYID));

        $university->set_source_table('dhbwio_universities', array('dhbwio' => backup::VAR_PARENTID));

        $email_template->set_source_table('dhbwio_email_templates', array('dhbwio' => backup::VAR_PARENTID));

        // Experience reports are user data - only include if userinfo is enabled
        if ($userinfo) {
            $experience_report->set_source_table('dhbwio_experience_reports', array('dhbwio' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $experience_report->annotate_ids('user', 'userid');
        $experience_report->annotate_ids('dhbwio_university', 'university_id');

        // Define file annotations
        $dhbwio->annotate_files('mod_dhbwio', 'intro', null); // This file area hasn't itemid
        $university->annotate_files('mod_dhbwio', 'description', 'id');
        $university->annotate_files('mod_dhbwio', 'university_image', 'id');
        $experience_report->annotate_files('mod_dhbwio', 'report_content', 'id');
        $experience_report->annotate_files('mod_dhbwio', 'report_attachment', 'id');

        // Return the root element (dhbwio), wrapped into standard activity structure
        return $this->prepare_activity_structure($dhbwio);
    }
}