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

$currentstatus = status_manager::get_status((int) $entry->statusid);

if ($currentstatus && $currentstatus->shortname === 'eingereicht') {
    $reviewstatus = status_manager::get_status_by_shortname('in_pruefung');

    if ($reviewstatus) {
        entry_manager::update_status($entryid, (int) $reviewstatus->id);
        $entry->statusid = (int) $reviewstatus->id;
    }
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
    'firstchoice' => $getvalue('ERSTWUNSCH'),
    'secondchoice' => $getvalue('ZWEITWUNSCH'),
    'thirdchoice' => $getvalue('DRITTWUNSCH'),
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

    $accepteduniversityid = !empty($formdata->acceptedchoice)
        ? (int)$formdata->acceptedchoice
        : null;

    // entry_manager::update_accepted_university($entryid, $accepteduniversityid);
    $entry->acceptedchoice = $accepteduniversityid;
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
    'acceptedchoice' => $entry->acceptedchoice ?? '',
    'SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST' => $getvalue('SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST'),
    'SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT' => $getvalue('SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT'),
    'SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT' => $getvalue('SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT'),
]);

echo $OUTPUT->header();

echo $OUTPUT->heading('Bewerbung prüfen', 3);

$summarycontext = [
    'name' => s(trim($getvalue('VORNAME') . ' ' . $getvalue('NACHNAME'))),
    'email' => s($getvalue('EMAIL')),
    'course' => s($getvalue('KURSNAME')),
    'director' => s($getvalue('STUDIENGANGSLEITUNG')),
    'studyprogram' => s($getvalue('STUDIENGANG')),
    'firstchoice' => s($getvalue('ERSTWUNSCH')),
    'secondchoice' => s($getvalue('ZWEITWUNSCH')),
    'thirdchoice' => s($getvalue('DRITTWUNSCH')),
];

echo $OUTPUT->render_from_template(
    'mod_dhbwio/application_review_summary',
    $summarycontext
);

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
