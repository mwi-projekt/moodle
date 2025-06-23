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
 * Library of interface functions and constants.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function dhbwio_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_dhbwio into the database.
 *
 * @param stdClass $dhbwio An object from the form.
 * @param mod_dhbwio_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function dhbwio_add_instance(stdClass $dhbwio, mod_dhbwio_mod_form $mform = null) {
    global $DB;

    $dhbwio->timecreated = time();
    $dhbwio->timemodified = time();

    $id = $DB->insert_record('dhbwio', $dhbwio);

    // Create default email templates
    dhbwio_create_default_email_templates($id);

    return $id;
}

/**
 * Updates an instance of the mod_dhbwio in the database.
 *
 * @param stdClass $dhbwio An object from the form in mod_form.php.
 * @param mod_dhbwio_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function dhbwio_update_instance(stdClass $dhbwio, mod_dhbwio_mod_form $mform = null) {
    global $DB;

    $dhbwio->timemodified = time();
    $dhbwio->id = $dhbwio->instance;

    return $DB->update_record('dhbwio', $dhbwio);
}

/**
 * Removes an instance of the mod_dhbwio from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function dhbwio_delete_instance($id) {
    global $DB;

    if (!$dhbwio = $DB->get_record('dhbwio', ['id' => $id])) {
        return false;
    }

    // Delete all associated records
    $DB->delete_records('dhbwio_universities', ['dhbwio' => $id]);
    $DB->delete_records('dhbwio_email_templates', ['dhbwio' => $id]);
    $DB->delete_records('dhbwio_experience_reports', ['dhbwio' => $id]);
    
    // Delete the main instance
    $DB->delete_records('dhbwio', ['id' => $id]);

    return true;
}

/**
 * Creates default email templates for a new instance.
 *
 * @param int $dhbwio_id The dhbwio instance ID.
 */
function dhbwio_create_default_email_templates($dhbwio_id) {
    global $DB;
    
    // English templates
    $en_templates = [
        // Application templates
        [
            'dhbwio' => $dhbwio_id,
            'name' => 'Application Received',
            'type' => 'application_received',
            'lang' => 'en',
            'subject' => 'Your application for exchange semester has been received',
            'body' => "<p>Dear {STUDENT_NAME},</p>
<p>Thank you for submitting your application for an exchange semester. Your application has been received and will be reviewed by our team.</p>
<p><strong>Application details:</strong></p>
<ul>
    <li>Submitted on: {SUBMISSION_DATE}</li>
    <li>University choices: {UNIVERSITY_CHOICES}</li>
</ul>
<p>We will inform you about the status of your application as soon as possible.</p>
<p>Best regards,<br>
International Office</p>",
            'bodyformat' => FORMAT_HTML,
            'timemodified' => time(),
            'enabled' => 1
        ],
        [
            'dhbwio' => $dhbwio_id,
            'name' => 'Application Approved',
            'type' => 'application_approved',
            'lang' => 'en',
            'subject' => 'Your application for exchange semester has been approved',
            'body' => "<p>Dear {STUDENT_NAME},</p>
<p>We are pleased to inform you that your application for an exchange semester has been approved. Congratulations!</p>
<p><strong>Details:</strong></p>
<ul>
    <li>University: {DATAFORM_KOMMENTAR_IO}</li>
</ul>
<p>Please find important information about the next steps attached. You will need to confirm your acceptance within two weeks.</p>
<p>Best regards,<br>
International Office</p>",
            'bodyformat' => FORMAT_HTML,
            'timemodified' => time(),
            'enabled' => 1
        ],
        [
            'dhbwio' => $dhbwio_id,
            'name' => 'Application Rejected',
            'type' => 'application_rejected',
            'lang' => 'en',
            'subject' => 'Status of your application for exchange semester',
            'body' => "<p>Dear {STUDENT_NAME},</p>
<p>We regret to inform you that your application for an exchange semester could not be approved at this time.</p>
<p><strong>Feedback:</strong></p>
<div class=\"feedback-box\">{DATAFORM_KOMMENTAR_IO}</div>
<p>If you would like to discuss alternative options or if you have any questions, please don't hesitate to contact us.</p>
<p>Best regards,<br>
International Office</p>",
            'bodyformat' => FORMAT_HTML,
            'timemodified' => time(),
            'enabled' => 1
        ],
        [
            'dhbwio' => $dhbwio_id,
            'name' => 'Application Inquiry',
            'type' => 'application_inquiry',
            'lang' => 'en',
            'subject' => 'Information needed for your exchange application',
            'body' => "<p>Dear {STUDENT_NAME},</p>
<p>Thank you for your application for an exchange semester. Before we can proceed with your application, we need some additional information or clarification.</p>
<p><strong>Questions/Comments from the International Office:</strong></p>
<div class=\"inquiry-box\">{DATAFORM_KOMMENTAR_IO}</div>
<p>Please respond to this email with the requested information as soon as possible. Your application process will continue once we receive your response.</p>
<p>Best regards,<br>
International Office</p>",
            'bodyformat' => FORMAT_HTML,
            'timemodified' => time(),
            'enabled' => 1
        ]
    ];
    
    // German templates
    $de_templates = [        
        // Application templates
        [
            'dhbwio' => $dhbwio_id,
            'name' => 'Bewerbung eingegangen',
            'type' => 'application_received',
            'lang' => 'de',
            'subject' => 'Ihre Bewerbung für ein Auslandssemester ist eingegangen',
            'body' => "<p>Liebe/r {STUDENT_NAME},</p>
<p>Vielen Dank für die Einreichung Ihrer Bewerbung für ein Auslandssemester. Ihre Bewerbung ist bei uns eingegangen und wird von unserem Team geprüft.</p>
<p><strong>Bewerbungsdetails:</strong></p>
<ul>
    <li>Eingereicht am: {SUBMISSION_DATE}</li>
    <li>Hochschulauswahl: {UNIVERSITY_CHOICES}</li>
</ul>
<p>Wir werden Sie so bald wie möglich über den Status Ihrer Bewerbung informieren.</p>
<p>Mit freundlichen Grüßen<br>
International Office</p>",
            'bodyformat' => FORMAT_HTML,
            'timemodified' => time(),
            'enabled' => 1
        ],
        [
            'dhbwio' => $dhbwio_id,
            'name' => 'Bewerbung angenommen',
            'type' => 'application_approved',
            'lang' => 'de',
            'subject' => 'Ihre Bewerbung für ein Auslandssemester wurde angenommen',
            'body' => "<p>Liebe/r {STUDENT_NAME},</p>
<p>Wir freuen uns, Ihnen mitteilen zu können, dass Ihre Bewerbung für ein Auslandssemester angenommen wurde. Herzlichen Glückwunsch!</p>
<p><strong>Details:</strong></p>
<ul>
    <li>Hochschule: {DATAFORM_KOMMENTAR_IO}</li>
</ul>
<p>Bitte finden Sie anbei wichtige Informationen zu den nächsten Schritten. Sie müssen Ihre Annahme innerhalb von zwei Wochen bestätigen.</p>
<p>Mit freundlichen Grüßen<br>
International Office</p>",
            'bodyformat' => FORMAT_HTML,
            'timemodified' => time(),
            'enabled' => 1
        ],
        [
            'dhbwio' => $dhbwio_id,
            'name' => 'Bewerbung abgelehnt',
            'type' => 'application_rejected',
            'lang' => 'de',
            'subject' => 'Status Ihrer Bewerbung für ein Auslandssemester',
            'body' => "<p>Liebe/r {STUDENT_NAME},</p>
<p>Leider müssen wir Ihnen mitteilen, dass Ihre Bewerbung für ein Auslandssemester zu diesem Zeitpunkt nicht angenommen werden konnte.</p>
<p><strong>Feedback:</strong></p>
<div class=\"feedback-box\">{DATAFORM_KOMMENTAR_IO}</div>
<p>Wenn Sie alternative Möglichkeiten besprechen möchten oder Fragen haben, zögern Sie bitte nicht, uns zu kontaktieren.</p>
<p>Mit freundlichen Grüßen<br>
International Office</p>",
            'bodyformat' => FORMAT_HTML,
            'timemodified' => time(),
            'enabled' => 1
        ],
        [
            'dhbwio' => $dhbwio_id,
            'name' => 'Rückfragen zur Bewerbung',
            'type' => 'application_inquiry',
            'lang' => 'de',
            'subject' => 'Informationen zu Ihrer Auslandsbewerbung benötigt',
            'body' => "<p>Liebe/r {STUDENT_NAME},</p>
<p>Vielen Dank für Ihre Bewerbung für ein Auslandssemester. Bevor wir mit Ihrer Bewerbung fortfahren können, benötigen wir einige zusätzliche Informationen oder Klarstellungen.</p>
<p><strong>Fragen/Anmerkungen des International Office:</strong></p>
<div class=\"inquiry-box\">{DATAFORM_KOMMENTAR_IO}</div>
<p>Bitte antworten Sie auf diese E-Mail mit den angeforderten Informationen so bald wie möglich. Ihr Bewerbungsprozess wird fortgesetzt, sobald wir Ihre Antwort erhalten haben.</p>
<p>Mit freundlichen Grüßen<br>
International Office</p>",
            'bodyformat' => FORMAT_HTML,
            'timemodified' => time(),
            'enabled' => 1
        ]
    ];
    
    // Insert all templates
    foreach (array_merge($en_templates, $de_templates) as $template) {
        $DB->insert_record('dhbwio_email_templates', $template);
    }
}

/**
 * Returns the lists of all browsable file areas within the given module context.
 *
 * @param stdClass $course Course object.
 * @param stdClass $cm Course module object.
 * @param stdClass $context Context object.
 * @return string[] Array of file areas.
 */
function dhbwio_get_file_areas($course, $cm, $context) {
    $areas = [];
    
    $areas['university_images'] = get_string('university_images', 'mod_dhbwio');
    $areas['report_attachments'] = get_string('report_attachments', 'mod_dhbwio');
    
    return $areas;
}

/**
 * File browsing support for mod_dhbwio file areas.
 *
 * @param file_browser $browser File browser instance.
 * @param array $areas File areas.
 * @param stdClass $course Course object.
 * @param stdClass $cm Course module object.
 * @param stdClass $context Context object.
 * @param string $filearea File area.
 * @param int $itemid Item ID.
 * @param string $filepath File path.
 * @param string $filename File name.
 * @return file_info Instance or null if not found.
 */
function dhbwio_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the mod_dhbwio file areas.
 *
 * @param stdClass $course Course object.
 * @param stdClass $cm Course module object.
 * @param stdClass $context Context object.
 * @param string $filearea File area.
 * @param array $args Extra arguments.
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 * @return bool False if file not found, does not return if found - just sends the file.
 */
function dhbwio_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    $validfileareas = ['university_images', 'report_attachments'];
    if (!in_array($filearea, $validfileareas)) {
        return false;
    }

    $itemid = (int)array_shift($args);
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_dhbwio/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Check specific file permissions if needed
    if ($filearea === 'report_attachments') {
        // Check if user is staff or the report owner
        if (!has_capability('mod/dhbwio:viewreports', $context)) {
            $report = $DB->get_record('dhbwio_experience_reports', ['id' => $itemid]);
            if (!$report || $report->userid != $USER->id) {
                return false;
            }
        }
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

/**
 * Add navigation items to the activity navigation tree.
 *
 * @param navigation_node $navref Navigation node.
 * @param stdClass $course The course.
 * @param stdClass $module The module.
 * @param cm_info $cm Course module information.
 */
function dhbwio_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    global $PAGE, $USER;
    
    if ($PAGE->context->contextlevel == CONTEXT_MODULE && $PAGE->context->instanceid == $cm->id) {
        // Main navigation nodes for all users
        $navref->add(
            get_string('nav_universities', 'mod_dhbwio'),
            new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'universities']),
            navigation_node::TYPE_SETTING
        );
        
        // Experience reports tab
        if (!empty($module->enablereports)) {
            $navref->add(
                get_string('nav_reports', 'mod_dhbwio'),
                new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'reports']),
                navigation_node::TYPE_SETTING
            );
        }
        
        // IO staff navigation nodes
        if (has_capability('mod/dhbwio:manageuniversities', $PAGE->context)) {
            $navref->add(
                get_string('nav_manageunis', 'mod_dhbwio'),
                new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'manageunis']),
                navigation_node::TYPE_SETTING
            );
        }
        
        if (has_capability('mod/dhbwio:viewreports', $PAGE->context)) {
            $navref->add(
                get_string('nav_statistics', 'mod_dhbwio'),
                new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'statistics']),
                navigation_node::TYPE_SETTING
            );
        }
        
        if (has_capability('mod/dhbwio:managetemplates', $PAGE->context)) {
            $navref->add(
                get_string('nav_emailtemplates', 'mod_dhbwio'),
                new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id, 'tab' => 'emailtemplates']),
                navigation_node::TYPE_SETTING
            );
        }
    }
}

/**
 * Add a get_coursemodule_info function in case any dhbwio type wants to add 'extra' information.
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing. See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info|false An object on information that the courses will know about.
 */
function dhbwio_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = array('id' => $coursemodule->instance);
    $fields = 'id, name, intro, introformat, enablemap, enablereports';
    if (!$dhbwio = $DB->get_record('dhbwio', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $dhbwio->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('dhbwio', $dhbwio, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    $result->customdata = array(
        'enablemap' => $dhbwio->enablemap,
        'enablereports' => $dhbwio->enablereports
    );

    return $result;
}
// Diese Funktionen müssen am Ende Ihrer lib.php hinzugefügt werden

/**
 * Get available dataform fields from linked dataform instance.
 *
 * @param int $dhbwio_id DHBW IO instance ID
 * @return array Array of dataform fields
 */
function dhbwio_get_dataform_fields($dhbwio_id) {
    global $DB;
    
    $fields = [];
    
    try {
        // Get the dhbwio instance to find the linked dataform
        $dhbwio = $DB->get_record('dhbwio', ['id' => $dhbwio_id]);
        if (!$dhbwio || empty($dhbwio->dataform_id)) {
            return $fields;
        }
        
        // Check if dataform plugin is installed
        if (!$DB->get_manager()->table_exists('dataform')) {
            return $fields;
        }
        
        // Get the dataform instance from course_module (since dataform_id is the cm id)
        $cm = $DB->get_record('course_modules', ['id' => $dhbwio->dataform_id]);
        if (!$cm) {
            return $fields;
        }
        
        // Verify this is actually a dataform module
        $module = $DB->get_record('modules', ['id' => $cm->module, 'name' => 'dataform']);
        if (!$module) {
            return $fields;
        }
        
        // Get the dataform instance
        $dataform = $DB->get_record('dataform', ['id' => $cm->instance]);
        if (!$dataform) {
            return $fields;
        }
        
        // Get fields for this specific dataform instance
        $dataformfields = $DB->get_records('dataform_fields', ['dataid' => $dataform->id], 'name');
        
        foreach ($dataformfields as $field) {
            // Skip internal fields
            if (in_array($field->type, ['_approve', '_group', '_user', '_timecreated', '_timemodified'])) {
                continue;
            }
            
            $fields[] = [
                'id' => $field->id,
                'name' => $field->name,
                'description' => !empty($field->description) ? $field->description : $field->name,
                'type' => $field->type,
                'dataform' => $dataform->name,
                'dataform_id' => $dataform->id
            ];
        }
        
    } catch (Exception $e) {
        // Dataform plugin not available or error occurred
        debugging('Error getting dataform fields: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
    
    return $fields;
}

/**
 * Get dataform entry by ID.
 *
 * @param int $entry_id Entry ID
 * @return object|false Entry object or false if not found
 */
function dhbwio_get_dataform_entry($entry_id) {
    global $DB;
    
    try {
        return $DB->get_record('dataform_entries', ['id' => $entry_id]);
    } catch (Exception $e) {
        debugging('Error getting dataform entry: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Get dataform entry data for use in email templates.
 *
 * @param int $dhbwio_id DHBW IO instance ID
 * @param int $entry_id Dataform entry ID
 * @return array Dataform field values
 */
function dhbwio_get_dataform_entry_data($dhbwio_id, $entry_id) {
    global $DB;
    
    $data = [];
    
    try {
        // Get the dhbwio instance to find the linked dataform
        $dhbwio = $DB->get_record('dhbwio', ['id' => $dhbwio_id]);
        if (!$dhbwio || empty($dhbwio->dataform_id)) {
            return $data;
        }
        
        // Check if dataform plugin is installed
        if (!$DB->get_manager()->table_exists('dataform')) {
            return $data;
        }
        
        // Get the dataform instance from course_module (since dataform_id is the cm id)
        $cm = $DB->get_record('course_modules', ['id' => $dhbwio->dataform_id]);
        if (!$cm) {
            return $data;
        }
        
        // Verify this is actually a dataform module
        $module = $DB->get_record('modules', ['id' => $cm->module, 'name' => 'dataform']);
        if (!$module) {
            return $data;
        }
        
        // Get the dataform instance
        $dataform = $DB->get_record('dataform', ['id' => $cm->instance]);
        if (!$dataform) {
            return $data;
        }
        
        // Get the specific entry
        $entry = $DB->get_record('dataform_entries', ['id' => $entry_id, 'dataid' => $dataform->id]);
        if (!$entry) {
            return $data;
        }
        
        // Get fields for this dataform
        $fields = $DB->get_records('dataform_fields', ['dataid' => $dataform->id]);
        
        foreach ($fields as $field) {
            // Skip internal fields
            if (in_array($field->type, ['_approve', '_group', '_user', '_timecreated', '_timemodified'])) {
                continue;
            }
            
            // Get content for this field and entry
            $content = $DB->get_record('dataform_contents', [
                'fieldid' => $field->id,
                'entryid' => $entry->id
            ]);
            
            if ($content) {
                $value = '';
                
                // Handle different field types
                switch ($field->type) {
                    case 'text':
                    case 'textarea':
                    case 'select':
                    case 'radiobutton':
                        $value = $content->content;
                        break;
                    case 'checkbox':
                        $value = !empty($content->content) ? get_string('yes') : get_string('no');
                        break;
                    case 'number':
                        $value = $content->content;
                        break;
                    case 'file':
                        $value = $content->content;
                        break;
                    case 'url':
                        $value = $content->content;
                        break;
                    default:
                        $value = $content->content;
                }
                
                // Store with DATAFORM_ prefix and uppercase field name
                $data['DATAFORM_' . strtoupper($field->name)] = $value;
            }
        }
        
        // Add special variables for university wishes
        $data = array_merge($data, dhbwio_get_university_wish_data($dhbwio, $entry));
        
    } catch (Exception $e) {
        debugging('Error getting dataform entry data: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
    
    return $data;
}

/**
 * Get university wish data for email templates.
 *
 * @param stdClass $dhbwio DHBW IO instance
 * @param stdClass $entry Dataform entry
 * @return array University wish data
 */
function dhbwio_get_university_wish_data($dhbwio, $entry) {
    global $DB;
    
    $data = [];
    
    try {
        // Get the dataform instance from course_module (since dataform_id is the cm id)
        $cm = $DB->get_record('course_modules', ['id' => $dhbwio->dataform_id]);
        if (!$cm) {
            return $data;
        }
        
        // Get the dataform instance
        $dataform = $DB->get_record('dataform', ['id' => $cm->instance]);
        if (!$dataform) {
            return $data;
        }
        
        // Get field mappings
        $wishfields = [
            1 => $dhbwio->first_wish_field,
            2 => $dhbwio->second_wish_field,
            3 => $dhbwio->third_wish_field
        ];
        
        $wishes = [];
        $allwishes = [];
        
        foreach ($wishfields as $priority => $fieldname) {
            if (empty($fieldname)) {
                continue;
            }
            
            // Get the field ID
            $field = $DB->get_record('dataform_fields', [
                'dataid' => $dataform->id,
                'name' => $fieldname
            ]);
            
            if (!$field) {
                continue;
            }
            
            // Get the content
            $content = $DB->get_record('dataform_contents', [
                'fieldid' => $field->id,
                'entryid' => $entry->id
            ]);
            
            if ($content && !empty($content->content)) {
                // Try to get university details
                $university = $DB->get_record('dhbwio_universities', [
                    'id' => intval($content->content),
                    'dhbwio' => $dhbwio->id
                ]);
                
                if ($university) {
                    $countries = get_string_manager()->get_list_of_countries();
                    $countryname = isset($countries[$university->country]) ? $countries[$university->country] : $university->country;
                    
                    $universitydisplay = $university->name . ' (' . $countryname . ')';
                    $wishes[$priority] = $universitydisplay;
                    $allwishes[] = $priority . '. ' . $universitydisplay;
                }
            }
        }
        
        // Set individual wish variables
        $data['FIRST_WISH'] = isset($wishes[1]) ? $wishes[1] : '';
        $data['SECOND_WISH'] = isset($wishes[2]) ? $wishes[2] : '';
        $data['THIRD_WISH'] = isset($wishes[3]) ? $wishes[3] : '';
        
        // Set combined university choices
        $data['UNIVERSITY_CHOICES'] = implode('<br>', $allwishes);
        
    } catch (Exception $e) {
        debugging('Error getting university wish data: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
    
    return $data;
}

/**
 * Get latest dataform entry for a user.
 *
 * @param int $dhbwio_id DHBW IO instance ID
 * @param int $userid User ID
 * @return object|false Latest entry or false if not found
 */
function dhbwio_get_latest_user_entry($dhbwio_id, $userid) {
    global $DB;
    
    try {
        // Get the dhbwio instance to find the linked dataform
        $dhbwio = $DB->get_record('dhbwio', ['id' => $dhbwio_id]);
        if (!$dhbwio || empty($dhbwio->dataform_id)) {
            return false;
        }
        
        // Get the dataform instance from course_module (since dataform_id is the cm id)
        $cm = $DB->get_record('course_modules', ['id' => $dhbwio->dataform_id]);
        if (!$cm) {
            return false;
        }
        
        // Get the dataform instance
        $dataform = $DB->get_record('dataform', ['id' => $cm->instance]);
        if (!$dataform) {
            return false;
        }
        
        // Get the most recent entry for this user in this dataform
        $entry = $DB->get_record_sql(
            "SELECT * FROM {dataform_entries} 
             WHERE dataid = :dataid AND userid = :userid 
             ORDER BY timecreated DESC LIMIT 1",
            ['dataid' => $dataform->id, 'userid' => $userid]
        );
        
        return $entry;
        
    } catch (Exception $e) {
        debugging('Error getting latest user entry: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Email notification function with dataform support.
 *
 * @param string $type The template type.
 * @param int $dhbwio_id The dhbwio instance ID.
 * @param int $to_user_id The recipient user ID.
 * @param array $params Template parameters.
 * @param string $language Language code (optional, default: user's preferred language)
 * @param int $entry_id Optional specific entry ID, if not provided uses latest entry for user
 * @return bool Success status.
 */
function dhbwio_send_email_notification($type, $dhbwio_id, $to_user_id, $params = [], $language = null, $entry_id = null) {
    global $DB;
    
    $user = $DB->get_record('user', ['id' => $to_user_id]);
    if (!$user) {
        return false;
    }
    
    if (!$language) {
        $language = $user->lang ?: get_config('core', 'lang');
    }
    
    // Get template in user's language
    $template = $DB->get_record('dhbwio_email_templates', [
        'dhbwio' => $dhbwio_id,
        'type' => $type,
        'lang' => $language,
        'enabled' => 1
    ]);
    
    // Fall back to English if template not available in user's language
    if (!$template && $language != 'en') {
        $template = $DB->get_record('dhbwio_email_templates', [
            'dhbwio' => $dhbwio_id,
            'type' => $type,
            'lang' => 'en',
            'enabled' => 1
        ]);
    }
    
    if (!$template) {
        return false;
    }
    
    $allparams = $params;
    
    $overviewlink = dhbwio_generate_dataform_overview_link($dhbwio_id);
    $allparams['APPLICATION_OVERVIEW_LINK'] = $overviewlink ? $overviewlink->out() : '';
    $allparams['APPLICATION_ENTRY_LINK'] = '';
    
    // Get dataform data
    if ($entry_id) {
        $dataformdata = dhbwio_get_dataform_entry_data($dhbwio_id, $entry_id);
        $entry = dhbwio_get_dataform_entry($entry_id);
        if ($entry && !isset($allparams['SUBMISSION_DATE'])) {
            $allparams['SUBMISSION_DATE'] = userdate($entry->timecreated);
        }
        
        $entrylink = dhbwio_generate_dataform_entry_link($dhbwio_id, $entry_id);
        $allparams['APPLICATION_ENTRY_LINK'] = $entrylink ? $entrylink->out() : '';
    } else {
        // Find latest entry for user and use that
        $entry = dhbwio_get_latest_user_entry($dhbwio_id, $to_user_id);
        if ($entry) {
            $dataformdata = dhbwio_get_dataform_entry_data($dhbwio_id, $entry->id);
            if (!isset($allparams['SUBMISSION_DATE'])) {
                $allparams['SUBMISSION_DATE'] = userdate($entry->timecreated);
            }
        } else {
            $dataformdata = [];
        }
    }
    
    $allparams = array_merge($allparams, $dataformdata);
    
    // Add standard user variables if not already set
    if (!isset($allparams['STUDENT_NAME'])) {
        $allparams['STUDENT_NAME'] = fullname($user);
    }
    if (!isset($allparams['STUDENT_FIRSTNAME'])) {
        $allparams['STUDENT_FIRSTNAME'] = $user->firstname;
    }
    if (!isset($allparams['STUDENT_EMAIL'])) {
        $allparams['STUDENT_EMAIL'] = $user->email;
    }
    if (!isset($allparams['SUBMISSION_DATE'])) {
        $allparams['SUBMISSION_DATE'] = userdate(time());
    }
    
    // Process template variables
    $subject = $template->subject;
    $message = $template->body;
    
    // Replace placeholders with actual values
    foreach ($allparams as $key => $value) {
        $subject = str_replace('{' . $key . '}', $value, $subject);
        $message = str_replace('{' . $key . '}', $value, $message);
    }
    
    // Log the email for debugging (optional)
    if (debugging('', DEBUG_DEVELOPER)) {
        $logdata = [
            'type' => $type,
            'to_user' => $to_user_id,
            'entry_id' => $entry_id,
            'subject' => $subject,
            'submission_date' => $allparams['SUBMISSION_DATE'],
            'variables_used' => array_keys($allparams)
        ];
        debugging('Sending email notification: ' . json_encode($logdata), DEBUG_DEVELOPER);
    }
    
    // Send email via Moodle API
    return email_to_user(
        $user, 
        core_user::get_support_user(), 
        $subject, 
        html_to_text($message), // Plain text version
        $message, // HTML version
        '', // Attachment
        '', // Attachment name
        true, // HTML format
        'internationaloffice@dhbw-karlsruhe.de', // Reply-to email
        'DHBW International Office', // Reply-to name
        $template->bodyformat // Message format
    );
}

/**
 * Get all available template variables for a dhbwio instance.
 *
 * @param int $dhbwio_id DHBW IO instance ID
 * @return array Available variables with descriptions
 */
function dhbwio_get_all_template_variables($dhbwio_id = null) {
    // Standard variables - removed redundant ones that are maintained in dataform
    $variables = [
        'STUDENT_NAME' => get_string('variable_student_name', 'mod_dhbwio'),
        'STUDENT_FIRSTNAME' => get_string('variable_student_firstname', 'mod_dhbwio'),
        'STUDENT_EMAIL' => get_string('variable_student_email', 'mod_dhbwio'),
        'SUBMISSION_DATE' => get_string('variable_application_date', 'mod_dhbwio'),
        'UNIVERSITY_CHOICES' => get_string('variable_university_choices', 'mod_dhbwio')
    ];
    
    // Add dataform variables if dhbwio instance is specified
    if ($dhbwio_id) {
        $dataformfields = dhbwio_get_dataform_fields($dhbwio_id);
        foreach ($dataformfields as $field) {
            $variables['DATAFORM_' . strtoupper($field['name'])] = $field['description'] . ' (' . $field['dataform'] . ')';
        }
    }
    
    return $variables;
}

/**
 * Send automatic email notification based on dataform entry status.
 *
 * @param int $dhbwio_id DHBW IO instance ID
 * @param int $userid User ID
 * @param string $status Status change (e.g., 'submitted', 'approved', 'rejected')
 * @param array $additional_params Additional parameters for the email
 * @param int $entry_id Optional specific entry ID, if not provided uses latest entry for user
 * @return bool Success status
 */
function dhbwio_send_automatic_notification($dhbwio_id, $userid, $status, $additional_params = [], $entry_id = null) {
    $type_mapping = [
        'eingegangen' => 'application_received',
        'angenommen' => 'application_approved',
        'abgelehnt' => 'application_rejected',
        'neueinzureichen' => 'application_inquiry'
    ];
    
    if (!isset($type_mapping[trim(strtolower($status))])) {
        debugging('Unknown status for automatic notification: ' . $status, DEBUG_DEVELOPER);
        return false;
    }
    
    $template_type = $type_mapping[trim(strtolower($status))];
    
    return dhbwio_send_email_notification($template_type, $dhbwio_id, $userid, $additional_params, null, $entry_id);
}