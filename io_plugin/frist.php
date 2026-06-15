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
 * Create / edit / delete a Frist (deadline).
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

use mod_dhbwio\form\frist_form;
use mod_dhbwio\local\dataform\entry_manager;
use mod_dhbwio\local\dataform\dataform_manager;

$cmid    = required_param('cmid', PARAM_INT);
$action  = optional_param('action', 'add', PARAM_ALPHA);
$fristid = optional_param('fristid', 0, PARAM_INT);

$cm     = get_coursemodule_from_id('dhbwio', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/dhbwio:manageuniversities', $context);

$PAGE->set_url('/mod/dhbwio/frist.php', ['cmid' => $cmid, 'action' => $action, 'fristid' => $fristid]);
$PAGE->set_title(get_string('frist_manage', 'mod_dhbwio'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/dhbwio/styles.css');

$returnurl = new moodle_url('/mod/dhbwio/view.php', ['id' => $cmid, 'tab' => 'fristen']);

// Handle delete
if ($action === 'delete') {
    require_sesskey();
    if ($fristid) {
        $DB->delete_records('dhbwio_fristen', ['id' => $fristid, 'dhbwio' => $dhbwio->id]);
    }
    redirect($returnurl, get_string('frist_deleted', 'mod_dhbwio'));
}

// Load existing record for editing
$frist = null;
if ($fristid) {
    $frist = $DB->get_record('dhbwio_fristen', ['id' => $fristid, 'dhbwio' => $dhbwio->id], '*', MUST_EXIST);
}

$form = new frist_form(null, ['cmid' => $cmid, 'fristid' => $fristid]);

if ($frist) {
    $form->set_data($frist);
}

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    $now = time();

    if (!empty($data->fristid)) {
        // Update
        $record              = new stdClass();
        $record->id          = (int) $data->fristid;
        $record->art          = $data->art;
        $record->studiengang  = $data->studiengang;
        $record->jahrgang     = trim($data->jahrgang);
        $record->deadline     = (int) $data->deadline;
        $record->kommentar    = $data->kommentar ?? '';
        $record->timemodified = $now;
        $DB->update_record('dhbwio_fristen', $record);
    } else {
        // Insert
        $record              = new stdClass();
        $record->dhbwio      = $dhbwio->id;
        $record->art         = $data->art;
        $record->studiengang = $data->studiengang;
        $record->jahrgang    = trim($data->jahrgang);
        $record->deadline    = (int) $data->deadline;
        $record->kommentar   = $data->kommentar ?? '';
        $record->authorid    = $USER->id;
        $record->timecreated  = $now;
        $record->timemodified = $now;
        $DB->insert_record('dhbwio_fristen', $record);
    }

    // Alle bestehenden Bewerbungen neu prüfen.
    try {
        $dataform = dataform_manager::get_course_dataform((int) $course->id);
        $entries  = entry_manager::get_entries((int) $dataform->id);
        foreach ($entries as $entry) {
            entry_manager::update_within_deadline((int) $entry->id, (int) $dhbwio->id);
        }
    } catch (Exception $e) {
        // Kein Dataform vorhanden – kein Problem, einfach überspringen.
    }

    redirect($returnurl, get_string('frist_saved', 'mod_dhbwio'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($fristid ? get_string('frist_edit', 'mod_dhbwio') : get_string('frist_add', 'mod_dhbwio'));
$form->display();
echo $OUTPUT->footer();
