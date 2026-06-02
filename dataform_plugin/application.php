<?php
require_once(__DIR__ . '/../../config.php');

use mod_dhbwio\form\application_form;
use mod_dhbwio\local\dataform\form_renderer;
use mod_dhbwio\local\dataform\validation_manager;
use mod_dhbwio\local\dataform\dataform_manager;
use mod_dhbwio\local\dataform\entry_manager;
use mod_dhbwio\local\dataform\field_manager;
use mod_dhbwio\local\dataform\view_renderer;
use mod_dhbwio\local\dataform\status_manager;

$id = required_param('id', PARAM_INT); // course_module id.
$dataid = required_param('dataid', PARAM_INT);
$entryid = optional_param('entryid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('dhbwio', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/dhbwio/application.php', [
    'id' => $id,
    'dataid' => $dataid,
]);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($dhbwio->name));
$PAGE->set_heading(format_string($course->fullname));

$dataform = dataform_manager::get_dataform($dataid);

if (!$dataform) {
    throw new moodle_exception('invaliddataformid', 'mod_dhbwio');
}

$fields = field_manager::get_fields($dataid);

$showreviewresult = false;
$reviewcontext = [];

if ($entryid > 0) {
    $entry = entry_manager::get_entry($entryid);

    if ($entry) {
        $status = status_manager::get_status((int) $entry->statusid);

        if ($status && ((int) $status->isaccepted === 1 || (int) $status->isrejected === 1)) {
            $showreviewresult = true;

            foreach ($fields as $field) {
                if (!field_manager::is_review_field($field)) {
                    continue;
                }

                $value = entry_manager::get_content_value($entryid, (int) $field->id) ?? '';

                $reviewcontext[$field->name] = [
                    'html' => view_renderer::render_field($field, (string) $value),
                ];
            }
        }
    }
}

$entry = null;
$entrycontents = [];

if ($entryid > 0) {
    $entry = entry_manager::get_entry($entryid);

    if (!$entry || (int) $entry->dataid !== $dataid) {
        throw new moodle_exception('invalidentryid', 'mod_dhbwio');
    }

    if ((int) $entry->userid !== (int) $USER->id) {
        require_capability('mod/dhbwio:viewallapplications', $context);
    }

    $entrycontents = entry_manager::get_entry_contents($entryid);
}

$formurl = new moodle_url('/mod/dhbwio/application.php', [
    'id' => $cm->id,
    'dataid' => $dataid,
]);


// Deprecated Moodleform implementation.
// Kept temporarily during migration to Mustache-based forms.

//$mform = new application_form($formurl, [
//    'id' => $cm->id,
//    'dataid' => $dataid,
//    'entryid' => $entryid,
//    'fields' => $fields,
//]);
//if ($entryid > 0 && !empty($entrycontents)) {
//    $defaultdata = [
//        'id' => $cm->id,
//        'dataid' => $dataid,
//        'entryid' => $entryid,
//    ];
//
//    foreach ($entrycontents as $fieldid => $content) {
//        $defaultdata['field_' . $fieldid] = $content->content;
//    }
//
//    $mform->set_data($defaultdata);
//}

$errors = [];
$formvalues = [];
$contents = [];

$applicationaccepted = false;

if ($entryid > 0) {
    $entry = entry_manager::get_entry($entryid);

    if ($entry) {
        $status = status_manager::get_status((int) $entry->statusid);
        $applicationaccepted = $status && (int) $status->isaccepted === 1;
    }
}

if ($entryid > 0) {
    $contents = entry_manager::get_entry_contents($entryid);
}

$ispost = optional_param('submitbutton', '', PARAM_RAW) !== '';

if ($ispost) {
    require_sesskey();

    foreach ($fields as $field) {
        if (!field_manager::is_student_field($field)) {
            continue;
        }

        $formfieldname = 'field_' . $field->id;

        if ($field->type === 'time') {
            $datevalue = optional_param($formfieldname, '', PARAM_RAW);

            if ($datevalue !== '') {
                $timestamp = strtotime($datevalue);
                $formvalues[$formfieldname] = $timestamp ?: '';
            } else {
                $formvalues[$formfieldname] = '';
            }
        } else {
            $formvalues[$formfieldname] = optional_param($formfieldname, '', PARAM_RAW);
        }
    }

    $formdata = (object) $formvalues;
    $errors = validation_manager::validate($formdata, $fields);

    if (empty($errors)) {
        $submittedentryid = optional_param('entryid', 0, PARAM_INT);

        if ($submittedentryid > 0) {
            $entry = entry_manager::get_entry((int) $submittedentryid);

            if (!$entry || (int) $entry->dataid !== (int) $dataid) {
                throw new moodle_exception('invalidentryid', 'mod_dhbwio');
            }

            if ((int) $entry->userid !== (int) $USER->id) {
                require_capability('mod/dhbwio:viewallapplications', $context);
            }

            $entryid = (int) $submittedentryid;
            entry_manager::update_entry($entryid);
        } else {
            $entryid = entry_manager::create_entry($dataid, $USER->id);
        }

        foreach ($fields as $field) {
            if (!field_manager::is_student_field($field)) {
                continue;
            }

            $formfieldname = 'field_' . $field->id;
            $value = $formvalues[$formfieldname] ?? '';

            entry_manager::save_content($entryid, (int) $field->id, (string) $value);
        }

        redirect(
            new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id]),
            get_string('changessaved'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

$fieldcontext = [];

foreach ($fields as $field) {

    if (!field_manager::is_student_field($field)) {
        continue;
    }

    $formfieldname = 'field_' . $field->id;

    $value = '';

    // Nach Validierungsfehlern POST-Werte behalten.
    if (isset($formvalues[$formfieldname])) {

        $value = $formvalues[$formfieldname];

    } else if (!empty($contents[$field->id])) {

        $value = $contents[$field->id]->content;
    }

    $formfieldname = 'field_' . $field->id;
    $error = $errors[$formfieldname] ?? '';

    $fieldcontext[$field->name] = [
        'html' => form_renderer::render_field(
            $field,
            (string)$value,
            (string)$error,
            $applicationaccepted
        )
    ];
}

echo $OUTPUT->header();

$templatecontext = [
    'actionurl' => (new moodle_url('/mod/dhbwio/application.php', [
        'id' => $cm->id,
        'dataid' => $dataid,
        'entryid' => $entryid,
    ]))->out(false),

    'sesskey' => sesskey(),

    'id' => $cm->id,
    'dataid' => $dataid,
    'entryid' => $entryid,

    'fields' => $fieldcontext,

    'showreviewresult' => $showreviewresult,
    'reviewfields' => $reviewcontext,

    'backurl' => (new moodle_url('/mod/dhbwio/view.php', [
        'id' => $cm->id,
    ]))->out(false),
];

echo $OUTPUT->render_from_template(
    'mod_dhbwio/application_form',
    $templatecontext
);
echo $OUTPUT->footer();
