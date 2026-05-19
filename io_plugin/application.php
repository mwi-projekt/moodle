<?php
require_once(__DIR__ . '/../../config.php');

use mod_dhbwio\form\application_form;
use mod_dhbwio\local\dataform\dataform_manager;
use mod_dhbwio\local\dataform\entry_manager;
use mod_dhbwio\local\dataform\field_manager;

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

$mform = new application_form($formurl, [
    'id' => $cm->id,
    'dataid' => $dataid,
    'entryid' => $entryid,
    'fields' => $fields,
]);

if ($entryid > 0 && !empty($entrycontents)) {
    $defaultdata = [
        'id' => $cm->id,
        'dataid' => $dataid,
        'entryid' => $entryid,
    ];

    foreach ($entrycontents as $fieldid => $content) {
        $defaultdata['field_' . $fieldid] = $content->content;
    }

    $mform->set_data($defaultdata);
}

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id]));
}

if ($formdata = $mform->get_data()) {
    $submittedentryid = $formdata->entryid ?? 0;

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
        $formfieldname = 'field_' . $field->id;

        if (!property_exists($formdata, $formfieldname)) {
            continue;
        }

        $value = $formdata->{$formfieldname};

        if (is_array($value)) {
            $value = json_encode($value);
        }

        entry_manager::save_content($entryid, (int) $field->id, (string) $value);
    }

    redirect(
        new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id]),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($dataform->name));

if (!empty($dataform->intro)) {
    echo format_module_intro('dhbwio', $dataform, $cm->id);
}

$mform->display();

echo $OUTPUT->footer();
