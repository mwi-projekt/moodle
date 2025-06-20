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
            // Send application received email
            $userid = $event->userid;
            $entryid = $event->objectid;
            
            // Get entry data to check status
            $entry_data = dhbwio_get_dataform_entry_data($dhbwio->id, $entryid);
            
            // Send application received notification
            dhbwio_send_email_notification(
                'application_received',
                $dhbwio->id,
                $userid,
                [],
                null,
                $entryid
            );
            
            // Log the email sending
            self::log_email_sent($dhbwio->id, $userid, 'application_received', $entryid);
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
                continue; // Not IO staff, skip email sending
            }
            
            $entryid = $event->objectid;
            $entry_userid = $event->relateduserid ? $event->relateduserid : self::get_entry_userid($entryid);
            
            if (!$entry_userid) {
                continue;
            }
            
            // Get current entry data
            $entry_data = dhbwio_get_dataform_entry_data($dhbwio->id, $entryid);
            
            // Check the status field to determine which email to send
            $status = isset($entry_data['DATAFORM_STATUS_BEWERBUNG']) ? 
                      strtolower($entry_data['DATAFORM_STATUS_BEWERBUNG']) : '';
            
            // Map status to email template type
            $email_type = self::get_email_type_from_status($status);
            
            if ($email_type && $email_type !== 'application_received') {
                // Don't send received email on update, only on create
                
                // Prepare additional parameters based on email type
                $additional_params = [];
                
                if ($email_type === 'application_inquiry' || $email_type === 'application_rejected') {
                    // Use IO comment as feedback/inquiry comment
                    if (isset($entry_data['DATAFORM_KOMMENTAR_IO'])) {
                        $additional_params['FEEDBACK'] = $entry_data['DATAFORM_KOMMENTAR_IO'];
                        $additional_params['INQUIRY_COMMENT'] = $entry_data['DATAFORM_KOMMENTAR_IO'];
                    }
                }
                
                // Send the email
                $sent = dhbwio_send_email_notification(
                    $email_type,
                    $dhbwio->id,
                    $entry_userid,
                    $additional_params,
                    null,
                    $entryid
                );
                
                if ($sent) {
                    // Log the email sending
                    self::log_email_sent($dhbwio->id, $entry_userid, $email_type, $entryid);
                }
            }
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
     * Map status to email template type.
     *
     * @param string $status Status from dataform
     * @return string|null Email template type or null
     */
    private static function get_email_type_from_status($status) {
        $status = trim(strtolower($status));
        
        // Common status mappings
        $status_map = [
            // Approved statuses
            'angenommen' => 'application_approved',
            'approved' => 'application_approved',
            'accepted' => 'application_approved',
            'genehmigt' => 'application_approved',
            'bestätigt' => 'application_approved',
            
            // Rejected statuses
            'abgelehnt' => 'application_rejected',
            'rejected' => 'application_rejected',
            'declined' => 'application_rejected',
            'nicht genehmigt' => 'application_rejected',
            
            // Inquiry statuses
            'rückfrage' => 'application_inquiry',
            'inquiry' => 'application_inquiry',
            'nachfrage' => 'application_inquiry',
            'pending' => 'application_inquiry',
            'in bearbeitung' => 'application_inquiry',
            'weitere informationen benötigt' => 'application_inquiry',
            
            // Received status (usually handled on create, not update)
            'eingegangen' => 'application_received',
            'received' => 'application_received',
            'neu' => 'application_received',
            'new' => 'application_received'
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
     */
    private static function log_email_sent($dhbwio_id, $userid, $email_type, $entryid) {
        global $DB, $USER;
        
        // You can create a log table if needed, for now just use Moodle's logging
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