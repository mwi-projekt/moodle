<?php
require_once(__DIR__ . '/../../config.php');

use mod_dhbwio\local\dataform\entry_manager;
use mod_dhbwio\local\dataform\field_manager;
use mod_dhbwio\local\dataform\view_renderer;
use mod_dhbwio\local\dataform\status_manager;

$index = 0;
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

$statusrecord = status_manager::get_status((int) $entry->statusid);
$status = $statusrecord ? $statusrecord->label : '-';

$statusclass = match (mb_strtolower($status)) {
    'eingereicht' => 'status-submitted',
    'in prüfung' => 'status-review',
    'angenommen' => 'status-approved',
    'abgelehnt' => 'status-rejected',
    default => 'status-default',
};

$fields = field_manager::get_fields($dataid);

$showreviewresult = false;
$reviewcontext = [];

if ($statusrecord &&
    ((int)$statusrecord->isaccepted === 1 ||
     (int)$statusrecord->isrejected === 1)) {

    $showreviewresult = true;

    foreach ($fields as $field) {
        if (!field_manager::is_review_field($field)) {
            continue;
        }

        $value = entry_manager::get_content_value(
            $entryid,
            (int)$field->id
        ) ?? '';

        $reviewcontext[$field->name] = [
            'html' => view_renderer::render_field(
                $field,
                (string)$value
            ),
        ];
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading('Bewerbung anzeigen', 3);

$fieldcontext = [];

foreach ($fields as $field) {
    if (!field_manager::is_student_field($field)) {
        continue;
    }

    $value = entry_manager::get_content_value($entryid, (int) $field->id) ?? '';

    $fieldcontext[$field->name] = [
        'html' => view_renderer::render_field(
            $field,
            (string)$value,
            $index % 2 === 0
        ),
    ];

    $index++;
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
    'fields' => $fieldcontext,
    'reviewurl' => $reviewurl->out(false),
    'backurl' => $backurl->out(false),
    'status' => s($status),
    'statusclass' => $statusclass,
    'showreviewresult' => $showreviewresult,
    'reviewfields' => $reviewcontext,
];

echo $OUTPUT->render_from_template('mod_dhbwio/application_view', $templatecontext);

echo $OUTPUT->footer();
