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
 * Scheduled task for processing email notifications.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dhbwio\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/dhbwio/lib.php');

/**
 * Task to process email notifications for status changes.
 */
class process_email_notifications extends \core\task\scheduled_task {
    
    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_process_email_notifications', 'mod_dhbwio');
    }
    
    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        
        // Get all dhbwio instances
        $dhbwios = $DB->get_records('dhbwio');
        
        foreach ($dhbwios as $dhbwio) {
            if (empty($dhbwio->dataform_id)) {
                continue; // No linked dataform
            }
            
            // Get the dataform instance
            $cm = $DB->get_record('course_modules', ['id' => $dhbwio->dataform_id]);
            if (!$cm) {
                continue;
            }
            
            $dataform = $DB->get_record('dataform', ['id' => $cm->instance]);
            if (!$dataform) {
                continue;
            }
            
            // Process entries that haven't been notified yet
            $this->process_dataform_entries($dhbwio, $dataform);
        }
    }
    
    /**
     * Process dataform entries for a specific dhbwio instance.
     *
     * @param stdClass $dhbwio DHBW IO instance
     * @param stdClass $dataform Dataform instance
     */
    private function process_dataform_entries($dhbwio, $dataform) {
        global $DB;
        
        $logtable = 'dhbwio_email_log';
        
        $entries = $DB->get_records('dataform_entries', ['dataid' => $dataform->id]);
        
        foreach ($entries as $entry) {
            // Check if we've already sent notifications for this entry's current state
            $lastlog = $DB->get_record_sql(
                "SELECT * FROM {{$logtable}} 
                 WHERE dhbwio_id = :dhbwio AND entry_id = :entry 
                 ORDER BY timecreated DESC LIMIT 1",
                ['dhbwio' => $dhbwio->id, 'entry' => $entry->id]
            );
            
            // Get current entry data
            $entry_data = dhbwio_get_dataform_entry_data($dhbwio->id, $entry->id);
            $current_status = isset($entry_data['DATAFORM_STATUS_BEWERBUNG']) ? 
                            strtolower($entry_data['DATAFORM_STATUS_BEWERBUNG']) : '';
            
            // Determine if we need to send an email
            $should_send = false;
            $email_type = null;
            
            if (!$lastlog) {
                // Never sent any email for this entry
                if ($entry->timecreated > time() - 86400) { // Created in last 24 hours
                    $email_type = 'application_received';
                    $should_send = true;
                }
            } else {
                // Check if status has changed
                if ($lastlog->status !== $current_status) {
                    $email_type = \mod_dhbwio\observer::get_email_type_from_status($current_status);
                    if ($email_type && $email_type !== $lastlog->email_type) {
                        $should_send = true;
                    }
                }
            }
            
            if ($should_send && $email_type) {
                $additional_params = [];
                
                $additional_params['SUBMISSION_DATE'] = userdate($entry->timecreated);
                
                if ($email_type === 'application_inquiry' || $email_type === 'application_rejected') {
                    if (isset($entry_data['DATAFORM_KOMMENTAR_IO'])) {
                        $additional_params['FEEDBACK'] = $entry_data['DATAFORM_KOMMENTAR_IO'];
                        $additional_params['INQUIRY_COMMENT'] = $entry_data['DATAFORM_KOMMENTAR_IO'];
                    }
                }
                
                // Send the email
                $sent = dhbwio_send_email_notification(
                    $email_type,
                    $dhbwio->id,
                    $entry->userid,
                    $additional_params,
                    null,
                    $entry->id
                );
                
                if ($sent) {
                    $log = new \stdClass();
                    $log->dhbwio_id = $dhbwio->id;
                    $log->entry_id = $entry->id;
                    $log->user_id = $entry->userid;
                    $log->email_type = $email_type;
                    $log->status = $current_status;
                    $log->timecreated = time();
                    
                    $DB->insert_record($logtable, $log);
                    
                    mtrace("Sent {$email_type} email for entry {$entry->id} to user {$entry->userid}");
                }
            }
        }
    }
}