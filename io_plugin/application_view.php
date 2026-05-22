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

echo html_writer::link(
    new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id]),
    'Zurück zur Liste'
);

echo $OUTPUT->heading('Bewerbung anzeigen', 3);

$table = new html_table();
$table->head = ['Feld', 'Wert'];

foreach ($fields as $field) {
    if ($field->type === 'file') {
        continue;
    }

    $value = entry_manager::get_content_value($entryid, (int) $field->id) ?? '';

    $table->data[] = [
        s($field->name),
        s($value),
    ];
}

echo html_writer::table($table);

$reviewurl = new moodle_url('/mod/dhbwio/application_review.php', [
    'id' => $cm->id,
    'dataid' => $dataid,
    'entryid' => $entryid,
]);

echo html_writer::link($reviewurl, 'Bewerbung prüfen', [
    'class' => 'btn btn-primary',
]);

echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');

$backurl = new moodle_url('/mod/dhbwio/view.php', [
    'id' => $cm->id,
]);

echo html_writer::link($backurl, 'Zurück zur Übersicht', [
    'class' => 'btn btn-secondary',
]);

echo $OUTPUT->footer();