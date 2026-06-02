<?php
require_once(__DIR__ . '/../../config.php');

use mod_dhbwio\local\dataform\entry_manager;
use mod_dhbwio\local\dataform\field_manager;

$id = required_param('id', PARAM_INT);
$dataid = required_param('dataid', PARAM_INT);
$entryid = required_param('entryid', PARAM_INT);

$cm = get_coursemodule_from_id('dhbwio', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/dhbwio:viewallapplications', $context);

$PAGE->set_url('/mod/dhbwio/application_view.php', [
    'id' => $id,
    'dataid' => $dataid,
    'entryid' => $entryid,
]);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($dhbwio->name));
$PAGE->set_heading(format_string($course->fullname));

$entry = entry_manager::get_entry($entryid);

if (!$entry || (int) $entry->dataid !== $dataid) {
    throw new moodle_exception('invalidentryid', 'mod_dhbwio');
}

$fields = field_manager::get_fields($dataid);

echo $OUTPUT->header();

echo $OUTPUT->heading('Bewerbung anzeigen', 3);

$groupedfields = [];

foreach ($fields as $field) {
    if (!field_manager::is_student_field($field) && !field_manager::is_review_field($field)) {
        continue;
    }

    $group = $field->fieldgroup ?? field_manager::GROUP_GENERAL;
    $groupedfields[$group][] = $field;
}

$grouptitles = field_manager::get_group_titles();
$groups = [];

foreach ($groupedfields as $group => $groupfields) {
    $fieldscontext = [];

    foreach ($groupfields as $field) {
        $value = entry_manager::get_content_value($entryid, (int) $field->id) ?? '';

        $fieldscontext[] = [
            'label' => $field->description ?: $field->name,
            'value' => $value,
        ];
    }

    $groups[] = [
        'title' => $grouptitles[$group] ?? ucfirst($group),
        'fields' => $fieldscontext,
    ];
}

$reviewurl = new moodle_url('/mod/dhbwio/application_review.php', [
    'id' => $cm->id,
    'dataid' => $dataid,
    'entryid' => $entryid,
]);

$backurl = new moodle_url('/mod/dhbwio/view.php', [
    'id' => $cm->id,
]);

$templatecontext = [
    'groups' => $groups,
    'reviewurl' => $reviewurl->out(false),
    'backurl' => $backurl->out(false),
];

echo $OUTPUT->render_from_template('mod_dhbwio/application_view', $templatecontext);

echo $OUTPUT->footer();
