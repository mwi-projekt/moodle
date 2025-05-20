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
 * @package     mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
        // Report templates
        [
            'dhbwio' => $dhbwio_id,
            'name' => 'Report Submitted',
            'type' => 'report_submitted',
            'lang' => 'en',
            'subject' => 'New experience report submission',
            'body' => "<p>Dear Administrator,</p>
<p>A new experience report has been submitted.</p>
<p><strong>Report details:</strong></p>
<ul>
    <li>Student: {STUDENT_NAME}</li>
    <li>University: {UNIVERSITY_NAME}</li>
    <li>Title: {REPORT_TITLE}</li>
    <li>Submitted on: {REPORT_DATE}</li>
</ul>
<p>Please review the report in the International Office module.</p>
<p>Best regards,<br>
System Notification</p>",
            'bodyformat' => FORMAT_HTML,
            'timemodified' => time(),
            'enabled' => 1
        ],
        [
            'dhbwio' => $dhbwio_id,
            'name' => 'Report Approved',
            'type' => 'report_approved',
            'lang' => 'en',
            'subject' => 'Your experience report has been approved',
            'body' => "<p>Dear {STUDENT_NAME},</p>
<p>Your experience report '{REPORT_TITLE}' for {UNIVERSITY_NAME} has been approved and is now visible to other students.</p>
<p>Thank you for sharing your experience!</p>
<p>Best regards,<br>
International Office</p>",
            'bodyformat' => FORMAT_HTML,
            'timemodified' => time(),
            'enabled' => 1
        ],
        
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
    <li>Semester: {SEMESTER}</li>
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
    <li>University: {APPROVED_UNIVERSITY}</li>
    <li>Semester: {SEMESTER}</li>
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
<div class=\"feedback-box\">{FEEDBACK}</div>
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
<div class=\"inquiry-box\">{INQUIRY_COMMENT}</div>
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
        // Report templates
        [
            'dhbwio' => $dhbwio_id,
            'name' => 'Erfahrungsbericht eingereicht',
            'type' => 'report_submitted',
            'lang' => 'de',
            'subject' => 'Neuer Erfahrungsbericht eingereicht',
            'body' => "<p>Sehr geehrte/r Administrator/in,</p>
<p>Ein neuer Erfahrungsbericht wurde eingereicht.</p>
<p><strong>Berichtsdetails:</strong></p>
<ul>
    <li>Student/in: {STUDENT_NAME}</li>
    <li>Hochschule: {UNIVERSITY_NAME}</li>
    <li>Titel: {REPORT_TITLE}</li>
    <li>Eingereicht am: {REPORT_DATE}</li>
</ul>
<p>Bitte überprüfen Sie den Bericht im International Office-Modul.</p>
<p>Mit freundlichen Grüßen<br>
Systembenachrichtigung</p>",
            'bodyformat' => FORMAT_HTML,
            'timemodified' => time(),
            'enabled' => 1
        ],
        [
            'dhbwio' => $dhbwio_id,
            'name' => 'Erfahrungsbericht genehmigt',
            'type' => 'report_approved',
            'lang' => 'de',
            'subject' => 'Ihr Erfahrungsbericht wurde genehmigt',
            'body' => "<p>Liebe/r {STUDENT_NAME},</p>
<p>Ihr Erfahrungsbericht '{REPORT_TITLE}' für {UNIVERSITY_NAME} wurde genehmigt und ist jetzt für andere Studenten sichtbar.</p>
<p>Vielen Dank für das Teilen Ihrer Erfahrung!</p>
<p>Mit freundlichen Grüßen<br>
International Office</p>",
            'bodyformat' => FORMAT_HTML,
            'timemodified' => time(),
            'enabled' => 1
        ],
        
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
    <li>Semester: {SEMESTER}</li>
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
    <li>Hochschule: {APPROVED_UNIVERSITY}</li>
    <li>Semester: {SEMESTER}</li>
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
<div class=\"feedback-box\">{FEEDBACK}</div>
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
<div class=\"inquiry-box\">{INQUIRY_COMMENT}</div>
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
 * Send email notification.
 *
 * @param string $type The template type.
 * @param int $dhbwio_id The dhbwio instance ID.
 * @param int $to_user_id The recipient user ID.
 * @param array $params Template parameters.
 * @param string $language Language code (optional, default: user's preferred language)
 * @return bool Success status.
 */
function dhbwio_send_email_notification($type, $dhbwio_id, $to_user_id, $params = [], $language = null) {
    global $DB;
    
    // Get user
    $user = $DB->get_record('user', ['id' => $to_user_id]);
    if (!$user) {
        return false;
    }
    
    // Determine language
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
    
    // Process template variables
    $subject = $template->subject;
    $message = $template->body;
    
    // Replace placeholders with actual values
    foreach ($params as $key => $value) {
        $subject = str_replace('{' . $key . '}', $value, $subject);
        $message = str_replace('{' . $key . '}', $value, $message);
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
        '', // Reply-to email
        '', // Reply-to name
        $template->bodyformat // Message format
    );
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