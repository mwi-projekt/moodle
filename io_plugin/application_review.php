<?php
require_once(__DIR__ . '/../../config.php');

use mod_dhbwio\form\application_review_form;
use mod_dhbwio\local\dataform\entry_manager;
use mod_dhbwio\local\dataform\field_manager;
use mod_dhbwio\local\dataform\status_manager;

$id = required_param('id', PARAM_INT);
$dataid = required_param('dataid', PARAM_INT);
$entryid = required_param('entryid', PARAM_INT);

$cm = get_coursemodule_from_id('dhbwio', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/dhbwio:viewallapplications', $context);

$PAGE->set_url('/mod/dhbwio/application_review.php', [
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

$getvalue = static function (string $fieldname) use ($dataid, $entryid): string {
    $field = field_manager::get_field_by_name($dataid, $fieldname);

    if (!$field) {
        return '';
    }

    return entry_manager::get_content_value($entryid, (int) $field->id) ?? '';
};

$formurl = new moodle_url('/mod/dhbwio/application_review.php', [
    'id' => $cm->id,
    'dataid' => $dataid,
    'entryid' => $entryid,
]);

$mform = new application_review_form($formurl, [
    'id' => $cm->id,
    'dataid' => $dataid,
    'entryid' => $entryid,
    'fields' => $fields,
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id]));
}

if ($formdata = $mform->get_data()) {
    if (!empty($formdata->statusid)) {
        $entry->statusid = (int) $formdata->statusid;
    }

    $reviewfields = [
        'KOMMENTAR_IO',
        'SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST',
        'SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT',
        'SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT',
    ];

    foreach ($reviewfields as $fieldname) {
        $field = field_manager::get_field_by_name($dataid, $fieldname);

        if (!$field) {
            continue;
        }

        $value = $formdata->{$fieldname} ?? '';

        entry_manager::save_content($entryid, (int) $field->id, (string) $value);
    }

    $entry->timemodified = time();

    $DB->update_record('dhbwio_dataform_entries', $entry);

    redirect(
        new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id]),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$mform->set_data([
    'id' => $cm->id,
    'dataid' => $dataid,
    'entryid' => $entryid,
    'statusid' => $entry->statusid,
    'KOMMENTAR_IO' => $getvalue('KOMMENTAR_IO'),
    'SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST' => $getvalue('SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST'),
    'SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT' => $getvalue('SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT'),
    'SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT' => $getvalue('SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT'),
]);

echo $OUTPUT->header();

echo html_writer::link(
    new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id]),
    'Zurück zur Liste'
);

echo $OUTPUT->heading('Bewerbung prüfen', 3);

$table = new html_table();
$table->head = ['Feld', 'Wert'];
$table->data = [
    ['Name', s(trim($getvalue('VORNAME') . ' ' . $getvalue('NACHNAME')))],
    ['E-Mail', s($getvalue('EMAIL'))],
    ['Kurs', s($getvalue('KURSNAME'))],
    ['Studiengang', s($getvalue('STUDIENGANG'))],
    ['Erstwunsch', s($getvalue('ERSTWUNSCH'))],
    ['Zweitwunsch', s($getvalue('ZWEITWUNSCH'))],
    ['Drittwunsch', s($getvalue('DRITTWUNSCH'))],
];

echo html_writer::table($table);

$mform->display();

$viewurl = new moodle_url('/mod/dhbwio/application_view.php', [
    'id' => $cm->id,
    'dataid' => $dataid,
    'entryid' => $entryid,
]);

echo html_writer::empty_tag('br');
echo html_writer::link($viewurl, 'Zurück zur Anzeige', [
    'class' => 'btn btn-secondary',
]);

echo $OUTPUT->footer();
