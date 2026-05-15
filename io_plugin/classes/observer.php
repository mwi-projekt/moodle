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
 * Event observer for mod_dhbwio
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dhbwio;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/dhbwio/lib.php');
require_once($CFG->dirroot . '/mod/dhbwio/locallib.php');

/**
 * Event observer class.
 */
class observer {
    
    /**
     * Handle dataform entry created event.
     *
     * @param \mod_dataform\event\entry_created $event
     */
    public static function dataform_entry_created(\mod_dataform\event\entry_created $event) {
        global $DB;

        // Get the dataform instance
        $dataform_cm_id = $event->contextinstanceid;

        // Check if this dataform is linked to any dhbwio instance
        $dhbwios = $DB->get_records('dhbwio', ['dataform_id' => $dataform_cm_id]);

        if (empty($dhbwios)) {
            return; // Not linked to any dhbwio instance
        }

        foreach ($dhbwios as $dhbwio) {
            $userid  = $event->userid;
            $entryid = $event->objectid;

            // --- Automatically set application status to SUBMITTED ---
            self::auto_submit_application($dhbwio->id, $userid, $entryid);

            // Send application received email
            $entry = self::get_dataform_entry($entryid);
            $additional_params = [];
            if ($entry) {
                $additional_params['SUBMISSION_DATE'] = userdate($entry->timecreated);
            }

            $sent = dhbwio_send_email_notification(
                'application_received',
                $dhbwio->id,
                $userid,
                $additional_params,
                null,
                $entryid
            );

            if ($sent) {
                self::log_email_sent($dhbwio->id, $userid, 'application_received', $entryid, 'eingegangen');
            }
        }
    }
    
    /**
     * Handle dataform entry updated event.
     *
     * @param \mod_dataform\event\entry_updated $event
     */
    public static function dataform_entry_updated(\mod_dataform\event\entry_updated $event) {
        global $DB, $USER;

        // Get the dataform instance
        $dataform_cm_id = $event->contextinstanceid;

        // Check if this dataform is linked to any dhbwio instance
        $dhbwios = $DB->get_records('dhbwio', ['dataform_id' => $dataform_cm_id]);

        if (empty($dhbwios)) {
            return; // Not linked to any dhbwio instance
        }

        foreach ($dhbwios as $dhbwio) {
            $context = \context_module::instance($dhbwio->id);

            // Check if the user updating is IO staff
            if (!has_capability('mod/dhbwio:manageuniversities', $context, $USER->id)) {
                continue; // Not IO staff, skip
            }

            $entryid      = $event->objectid;
            $entry_userid = $event->relateduserid ?: self::get_entry_userid($entryid);

            if (!$entry_userid) {
                continue;
            }

            // Get current entry data
            $entry_data = dhbwio_get_dataform_entry_data($dhbwio->id, $entryid);
            $io_comment = $entry_data['DATAFORM_KOMMENTAR_IO'] ?? '';

            // Read DataForm status field
            $df_status = isset($entry_data['DATAFORM_STATUS_BEWERBUNG'])
                ? trim(strtolower($entry_data['DATAFORM_STATUS_BEWERBUNG']))
                : '';

            // --- Automatically update application status based on DataForm status ---
            $new_app_status = self::map_dataform_status($df_status);
            if ($new_app_status !== null) {
                self::auto_transition_application(
                    $dhbwio->id,
                    $entry_userid,
                    $new_app_status,
                    application_status::ACTOR_IO,
                    $io_comment
                );
            }

            // Send email notification
            $email_type = self::get_email_type_from_status($df_status);

            if ($email_type && $email_type !== 'application_received') {
                $entry            = self::get_dataform_entry($entryid);
                $additional_params = [];

                if ($entry) {
                    $additional_params['SUBMISSION_DATE'] = userdate($entry->timecreated);
                }

                if (in_array($email_type, ['application_inquiry', 'application_rejected']) && $io_comment) {
                    $additional_params['FEEDBACK']        = $io_comment;
                    $additional_params['INQUIRY_COMMENT'] = $io_comment;
                }

                $sent = dhbwio_send_email_notification(
                    $email_type,
                    $dhbwio->id,
                    $entry_userid,
                    $additional_params,
                    null,
                    $entryid
                );

                if ($sent) {
                    self::log_email_sent($dhbwio->id, $entry_userid, $email_type, $entryid, $df_status);
                }
            }
        }
    }
    
    /**
     * Get dataform entry data.
     *
     * @param int $entryid Entry ID
     * @return object|false Entry or false if not found
     */
    private static function get_dataform_entry($entryid) {
        global $DB;
        
        try {
            return $DB->get_record('dataform_entries', ['id' => $entryid]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get user ID from dataform entry.
     *
     * @param int $entryid Entry ID
     * @return int|false User ID or false if not found
     */
    private static function get_entry_userid($entryid) {
        global $DB;
        
        try {
            $entry = $DB->get_record('dataform_entries', ['id' => $entryid], 'userid');
            return $entry ? $entry->userid : false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Maps a DataForm status string to an application_status constant.
     * Returns null if no mapping exists (status change is ignored).
     *
     * @param string $df_status  Lowercase DataForm status value
     * @return string|null
     */
    private static function map_dataform_status(string $df_status): ?string {
        $map = [
            'eingegangen'    => application_status::SUBMITTED,
            'angenommen'     => application_status::ACCEPTED,
            'abgelehnt'      => application_status::REJECTED,
            'neueinzureichen' => application_status::NEEDS_SUPPLEMENT,
        ];
        return $map[$df_status] ?? null;
    }

    /**
     * Creates/finds the application for a student and transitions it to SUBMITTED.
     * Called automatically when a DataForm entry is created (student submits the form).
     *
     * @param int $dhbwio_id
     * @param int $userid         Student user ID
     * @param int $entryid        DataForm entry ID to link
     */
    private static function auto_submit_application(int $dhbwio_id, int $userid, int $entryid): void {
        try {
            $application = application_manager::get_application_for_user($dhbwio_id, $userid);

            if ($application === null) {
                // Student never visited "My Applications" tab — create record now
                $application = application_manager::create_application($dhbwio_id, $userid);
            }

            // Link the DataForm entry if not already set
            if (empty($application->dataform_entry_id)) {
                $application->dataform_entry_id = $entryid;
                application_manager::save_application($application);
            }

            // Transition draft → submitted (only if still in draft)
            if ($application->status === application_status::DRAFT) {
                application_status::transition(
                    $application->id,
                    application_status::SUBMITTED,
                    application_status::ACTOR_STUDENT
                );
            }
        } catch (\Throwable $e) {
            // Never break the DataForm submission itself
            debugging('mod_dhbwio: auto_submit_application failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Finds the student's application and performs a status transition.
     * Called automatically when IO changes the DataForm status field.
     *
     * @param int    $dhbwio_id
     * @param int    $userid      Student user ID
     * @param string $new_status  Target application_status constant
     * @param string $actor       application_status::ACTOR_IO or ACTOR_STUDENT
     * @param string $content     Optional justification (IO comment)
     */
    private static function auto_transition_application(
        int $dhbwio_id,
        int $userid,
        string $new_status,
        string $actor,
        string $content = ''
    ): void {
        try {
            $application = application_manager::get_application_for_user($dhbwio_id, $userid);

            if ($application === null) {
                return; // No application to update
            }

            // Only transition if the target differs and the move is allowed
            if ($application->status !== $new_status
                && application_status::is_allowed($application->status, $new_status, $actor)
            ) {
                application_status::transition($application->id, $new_status, $actor, $content);
            }
        } catch (\Throwable $e) {
            debugging('mod_dhbwio: auto_transition_application failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Map status to email template type.
     *
     * @param string $status Status from dataform
     * @return string|null Email template type or null
     */
    public static function get_email_type_from_status($status) {
        $status = trim(strtolower($status));
        
        // Common status mappings
        $status_map = [
            // Approved statuses
            'angenommen' => 'application_approved',
            
            // Rejected statuses
            'abgelehnt' => 'application_rejected',
            
            // Inquiry statuses
            'neueinzureichen' => 'application_inquiry',
            
            // Received status (usually handled on create, not update)
            'eingegangen' => 'application_received'
        ];
        
        return isset($status_map[$status]) ? $status_map[$status] : null;
    }
    
    /**
     * Log email sending for audit purposes.
     *
     * @param int $dhbwio_id DHBW IO instance ID
     * @param int $userid User ID
     * @param string $email_type Email template type
     * @param int $entryid Entry ID
     * @param string $status Current status
     */
    private static function log_email_sent($dhbwio_id, $userid, $email_type, $entryid, $status = '') {
        global $DB, $USER;
        
        // Insert into dhbwio_email_log table
        $log = new \stdClass();
        $log->dhbwio_id = $dhbwio_id;
        $log->entry_id = $entryid;
        $log->user_id = $userid;
        $log->email_type = $email_type;
        $log->status = $status;
        $log->timecreated = time();
        
        $DB->insert_record('dhbwio_email_log', $log);
        
        // Also trigger event for additional logging
        $event = \mod_dhbwio\event\email_sent::create([
            'objectid' => $entryid,
            'context' => \context_module::instance($dhbwio_id),
            'userid' => $USER->id,
            'relateduserid' => $userid,
            'other' => [
                'email_type' => $email_type
            ]
        ]);
        $event->trigger();
    }
}