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
 * Privacy Subsystem implementation for mod_dhbwio.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dhbwio\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the DHBW IO module.
 */
class provider implements
    // This plugin stores personal data.
    \core_privacy\local\metadata\provider,
    
    // This plugin is a core_user_data_provider.
    \core_privacy\local\request\plugin\provider {
    
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'dhbwio_experience_reports',
            [
                'dhbwio' => 'privacy:metadata:dhbwio_experience_reports:dhbwio',
                'university_id' => 'privacy:metadata:dhbwio_experience_reports:university_id',
                'userid' => 'privacy:metadata:dhbwio_experience_reports:userid',
                'title' => 'privacy:metadata:dhbwio_experience_reports:title',
                'content' => 'privacy:metadata:dhbwio_experience_reports:content',
                'rating' => 'privacy:metadata:dhbwio_experience_reports:rating',
                'timecreated' => 'privacy:metadata:dhbwio_experience_reports:timecreated',
                'timemodified' => 'privacy:metadata:dhbwio_experience_reports:timemodified',
            ],
            'privacy:metadata:dhbwio_experience_reports'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        
        // Find all course modules with experience reports by this user.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {dhbwio} d ON d.id = cm.instance
                  JOIN {dhbwio_experience_reports} er ON er.dhbwio = d.id
                 WHERE er.userid = :userid";
        
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname'      => 'dhbwio',
            'userid'       => $userid,
        ];
        
        $contextlist->add_from_sql($sql, $params);
        
        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist.
     * User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        
        $sql = "SELECT cm.id AS cmid, 
                       er.id,
                       er.title,
                       er.content,
                       er.contentformat,
                       er.rating,
                       er.timecreated,
                       er.timemodified,
                       u.name AS university_name
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {dhbwio} d ON d.id = cm.instance
                  JOIN {dhbwio_experience_reports} er ON er.dhbwio = d.id
                  JOIN {dhbwio_universities} u ON u.id = er.university_id
                 WHERE c.id {$contextsql}
                   AND er.userid = :userid
              ORDER BY cm.id";
        
        $params = ['userid' => $user->id] + $contextparams;
        
        // Reference to export data in each context.
        $reportsbycontext = [];
        $results = $DB->get_records_sql($sql, $params);
        
        foreach ($results as $result) {
            // Create entry if not exists.
            if (!isset($reportsbycontext[$result->cmid])) {
                $reportsbycontext[$result->cmid] = [];
            }
            
            // Format content.
            $result->content = format_text($result->content, $result->contentformat);
            unset($result->contentformat);
            
            // Add report data to context.
            $reportsbycontext[$result->cmid][] = (object)[
                'title' => $result->title,
                'content' => $result->content,
                'university' => $result->university_name,
                'rating' => $result->rating,
                'timecreated' => transform::datetime($result->timecreated),
                'timemodified' => transform::datetime($result->timemodified),
            ];
        }
        
        // Export data for each context.
        foreach ($reportsbycontext as $cmid => $reports) {
            $context = \context_module::instance($cmid);
            writer::with_context($context)->export_data(
                [get_string('privacy:experiencereportspath', 'mod_dhbwio')],
                (object)['reports' => $reports]
            );
            
            // Export associated files.
            writer::with_context($context)->export_area_files(
                [get_string('privacy:experiencereportspl', 'mod_dhbwio')],
                'mod_dhbwio',
                'report_attachments',
                array_keys($reports)
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        
        if (!$context instanceof \context_module) {
            return;
        }
        
        // Get the course module.
        if (!$cm = get_coursemodule_from_id('dhbwio', $context->instanceid)) {
            return;
        }

        // Delete all experience reports.
        $DB->delete_records('dhbwio_experience_reports', ['dhbwio' => $cm->instance]);
        
        // Delete all files.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_dhbwio', 'report_attachments');
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        
        $user = $contextlist->get_user();
        $userid = $user->id;
        
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            
            // Get the course module.
            if (!$cm = get_coursemodule_from_id('dhbwio', $context->instanceid)) {
                continue;
            }

            // Get all reports for this user in this context.
            $reports = $DB->get_records('dhbwio_experience_reports', [
                'dhbwio' => $cm->instance,
                'userid' => $userid
            ]);
            
            // Delete the reports.
            $DB->delete_records('dhbwio_experience_reports', [
                'dhbwio' => $cm->instance,
                'userid' => $userid
            ]);
            
            // Delete associated files.
            $fs = get_file_storage();
            foreach ($reports as $report) {
                $fs->delete_area_files($context->id, 'mod_dhbwio', 'report_attachments', $report->id);
            }
        }
    }
}