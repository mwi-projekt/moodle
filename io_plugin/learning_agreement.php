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
 * Upload or review a Learning Agreement.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

use mod_dhbwio\form\learning_agreement_form;

$cmid    = required_param('cmid', PARAM_INT);
$action  = optional_param('action', 'upload', PARAM_ALPHA);
$laid    = optional_param('laid', 0, PARAM_INT);
$doctype = optional_param('doctype', 'learning_agreement', PARAM_ALPHANUMEXT);

// Sanitise doctype to known values.
if (!in_array($doctype, ['learning_agreement', 'other_document'])) {
    $doctype = 'learning_agreement';
}

$cm     = get_coursemodule_from_id('dhbwio', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/dhbwio/styles.css');

$returnurl = new moodle_url('/mod/dhbwio/view.php', ['id' => $cmid, 'tab' => 'learningagreement']);
$PAGE->set_url('/mod/dhbwio/learning_agreement.php', ['cmid' => $cmid, 'action' => $action, 'laid' => $laid, 'doctype' => $doctype]);
$iscoordinator = has_capability('mod/dhbwio:manageuniversities', $context);

// ── COORDINATOR: Review ──────────────────────────────────────────────────────
if ($action === 'review') {
    require_capability('mod/dhbwio:manageuniversities', $context);

    $record = $DB->get_record('dhbwio_learning_agreements', ['id' => $laid, 'dhbwio' => $dhbwio->id], '*', MUST_EXIST);
    $PAGE->set_title(get_string('la_review_title', 'mod_dhbwio'));

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
        $newstatus  = required_param('la_status', PARAM_ALPHA);
        $newcomment = optional_param('la_comment', '', PARAM_TEXT);

        if (!in_array($newstatus, ['pending', 'approved', 'rejected'])) {
            throw new moodle_exception('invalidstatus', 'mod_dhbwio');
        }

        $update               = new stdClass();
        $update->id           = $record->id;
        $update->status       = $newstatus;
        $update->comment      = $newcomment;
        $update->timemodified = time();
        $DB->update_record('dhbwio_learning_agreements', $update);

        redirect($returnurl, get_string('la_review_saved', 'mod_dhbwio'));
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('la_review_title', 'mod_dhbwio'));

    $student = $DB->get_record('user', ['id' => $record->userid]);
    echo '<p><strong>' . get_string('la_col_student', 'mod_dhbwio') . ':</strong> '
        . ($student ? fullname($student) : '(unbekannt)') . '</p>';

    $fs    = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_dhbwio', 'learning_agreements', $record->id, '', false);
    if (!empty($files)) {
        $file    = reset($files);
        $fileurl = moodle_url::make_pluginfile_url(
            $context->id, 'mod_dhbwio', 'learning_agreements', $record->id,
            $file->get_filepath(), $file->get_filename()
        );
        echo '<p><strong>' . get_string('la_col_file', 'mod_dhbwio') . ':</strong> ';
        echo '<a href="' . $fileurl . '" target="_blank">' . s($file->get_filename()) . '</a></p>';
    }

    $statusoptions = [
        'pending'  => get_string('la_status_pending', 'mod_dhbwio'),
        'approved' => get_string('la_status_approved', 'mod_dhbwio'),
        'rejected' => get_string('la_status_rejected', 'mod_dhbwio'),
    ];

    echo '<form method="post" action="">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';

    echo '<div class="form-group">';
    echo '<label for="la_status"><strong>' . get_string('la_col_status', 'mod_dhbwio') . '</strong></label>';
    echo '<select name="la_status" id="la_status" class="form-control" style="max-width:250px">';
    foreach ($statusoptions as $val => $label) {
        $sel = ($record->status === $val) ? ' selected' : '';
        echo '<option value="' . $val . '"' . $sel . '>' . $label . '</option>';
    }
    echo '</select></div>';

    echo '<div class="form-group mt-2">';
    echo '<label for="la_comment"><strong>' . get_string('la_comment_label', 'mod_dhbwio') . '</strong></label>';
    echo '<textarea name="la_comment" id="la_comment" class="form-control" rows="4" style="max-width:500px">'
        . s($record->comment ?? '') . '</textarea></div>';

    echo '<div class="mt-3">';
    echo '<button type="submit" class="btn btn-primary">' . get_string('la_save_review', 'mod_dhbwio') . '</button> ';
    echo '<a href="' . $returnurl . '" class="btn btn-secondary">' . get_string('cancel', 'core') . '</a>';
    echo '</div></form>';

    echo $OUTPUT->footer();
    exit;
}

// ── STUDENT: Upload ──────────────────────────────────────────────────────────
if ($iscoordinator) {
    redirect($returnurl);
}

$uploadtitle = ($doctype === 'other_document')
    ? get_string('la_other_upload_title', 'mod_dhbwio')
    : get_string('la_upload_title', 'mod_dhbwio');

$uploadsuccess = ($doctype === 'other_document')
    ? get_string('la_other_upload_success', 'mod_dhbwio')
    : get_string('la_upload_success', 'mod_dhbwio');

$PAGE->set_title($uploadtitle);

$myrecord = $DB->get_record('dhbwio_learning_agreements', [
    'dhbwio'  => $dhbwio->id,
    'userid'  => $USER->id,
    'doctype' => $doctype,
]);

$form = new learning_agreement_form(null, ['cmid' => $cmid, 'doctype' => $doctype]);

// Pre-populate filemanager with existing file.
if ($myrecord) {
    $draftitemid = file_get_submitted_draft_itemid('la_file');
    file_prepare_draft_area(
        $draftitemid,
        $context->id,
        'mod_dhbwio',
        'learning_agreements',
        $myrecord->id,
        ['subdirs' => 0, 'maxfiles' => 1]
    );
    $form->set_data(['la_file' => $draftitemid]);
}

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    // Doctype aus dem Formular lesen (zuverlässiger als URL-Parameter)
    if (!empty($data->doctype) && in_array($data->doctype, ['learning_agreement', 'other_document'])) {
        $doctype = $data->doctype;
    }
    $now = time();

    if ($myrecord) {
        $recordid             = $myrecord->id;
        $update               = new stdClass();
        $update->id           = $myrecord->id;
        $update->status       = 'pending';
        $update->comment      = '';
        $update->timemodified = $now;
        $DB->update_record('dhbwio_learning_agreements', $update);
    } else {
        $insert               = new stdClass();
        $insert->dhbwio       = $dhbwio->id;
        $insert->userid       = $USER->id;
        $insert->doctype      = $doctype;
        $insert->filename     = '';
        $insert->status       = 'pending';
        $insert->comment      = '';
        $insert->timecreated  = $now;
        $insert->timemodified = $now;
        $recordid = $DB->insert_record('dhbwio_learning_agreements', $insert);
    }

    file_save_draft_area_files(
        $data->la_file,
        $context->id,
        'mod_dhbwio',
        'learning_agreements',
        $recordid,
        ['subdirs' => 0, 'maxfiles' => 1]
    );

    // Cache filename.
    $fs    = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_dhbwio', 'learning_agreements', $recordid, '', false);
    if (!empty($files)) {
        $file = reset($files);
        $DB->set_field('dhbwio_learning_agreements', 'filename', $file->get_filename(), ['id' => $recordid]);
    }

    redirect($returnurl, $uploadsuccess);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($uploadtitle);
$form->display();
echo $OUTPUT->footer();
