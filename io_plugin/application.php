<?php
require_once(__DIR__ . '/../../config.php');

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
$showreviewresult = false;
$reviewcontext = [];
$entry = null;
$contents = [];
$status = null;
$errors = [];
$formvalues = [];
$applicationaccepted = false;
$acceptedchoicelabel = '';
$fieldcontext = [];

if (!$dataform) {
    throw new moodle_exception('invaliddataformid', 'mod_dhbwio');
}
$fields = field_manager::get_fields($dataid);

if ($entryid > 0) {
    $entry = entry_manager::get_entry($entryid);
    /* "Gibt es diese Application-ID?" Schutz vor application.php?entryid=xxxx
    *   sowie Prüfung ob die Application zu dieser Instanz des Dataform gehört.
    */
    if (!$entry || (int) $entry->dataid !== $dataid) {
        throw new moodle_exception('invalidentryid', 'mod_dhbwio');
    }
    // Fremde Bewerbungen dürfen hier nur von Nutzern mit viewallapplications angezeigt werden.
    // Das Speichern fremder Bewerbungen wird im Submit-Block separat verhindert.
    if ((int) $entry->userid !== (int) $USER->id) {
        require_capability('mod/dhbwio:viewallapplications', $context);
    }

    $contents = entry_manager::get_entry_contents($entryid);
}

if ($entry) {
    $status = status_manager::get_status((int)$entry->statusid);
}

/*
 * Lädt bei einer bestehenden Bewerbung die Review-Ergebnisse.
 *
 * Wenn die Bewerbung bereits einen finalen Status hat
 * (angenommen oder abgelehnt), werden die Review-Felder des
 * International Office ausgelesen und als read-only HTML für
 * die Anzeige im Application-Template vorbereitet.
 */

if ($entry) {
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

// Statusinformationen und Inhalte einer vorhandenen Bewerbung vorbereiten
if ($entryid > 0) {
    if ($entry) {
        $applicationaccepted = $status && (int) $status->isaccepted === 1;
    }
}


$submitted = optional_param('submitbutton', '', PARAM_RAW) !== '';

// Verarbeitet abgesendete Bewerbungsformulare: Werte einlesen,
// validieren, Entry erstellen/aktualisieren und Inhalte speichern.
// #TODO Kann perspektivisch zur besseren Übersichtlichkeit ausgelagert werden.
if ($submitted) {
    require_sesskey();

    foreach ($fields as $field) {
        // Nur Felder des student-scope dürfen abgeschickt werden
        if (!field_manager::is_student_field($field)) {
            continue;
        }

        $formfieldname = 'field_' . $field->id;

        // Sonderprüfung für Datumswerte
        if ($field->type === 'time') {
            $datevalue = optional_param($formfieldname, '', PARAM_RAW);

            // Convert zu Unix-Timestamp
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

    // Umwandlung der $formvalues zu (object)
    $formdata = (object) $formvalues;
    // Prüfung des $formdata durch validation_manager
    $errors = validation_manager::validate($formdata, $fields);

    // Speichern erfolgt nur bei leerem $error-Array
    if (empty($errors)) {
        $submittedentryid = optional_param('entryid', 0, PARAM_INT);

        // UPDATE
        if ($submittedentryid > 0) {
            $entry = entry_manager::get_entry((int) $submittedentryid);

            if (!$entry || (int) $entry->dataid !== (int) $dataid) {
                throw new moodle_exception('invalidentryid', 'mod_dhbwio');
            }

            if ((int)$entry->userid !== (int)$USER->id) {
                throw new moodle_exception('nopermission');
            }

            $entryid = (int) $submittedentryid;
            entry_manager::update_entry($entryid);
        }
        // NEW ENTRY
        else {
            $entryid = entry_manager::create_entry($dataid, $USER->id);
        }

        // Feldinhalte Speichern
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

// Mustache-Template-Aufbau
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

    // vorhandenen Error-Text abgreifen
    $error = $errors[$formfieldname] ?? '';
    $fieldcontext[$field->name] = [
        'html' => form_renderer::render_field(
            $field,
            (string)$value,
            (string)$error,
            $applicationaccepted,
            (int)$dhbwio->id
        )
    ];
}

echo $OUTPUT->header();

if ($entryid > 0 && $entry) {
    // #TODO Diese Hilfsfunktion sollte in Zukunft ausgelagert werden.
    $getvalue = static function (string $fieldname) use ($dataid, $entryid): string {
        $field = field_manager::get_field_by_name($dataid, $fieldname);

        if (!$field) {
            return '';
        }

        return entry_manager::get_content_value($entryid, (int)$field->id) ?? '';
    };

    $acceptedchoicelabel = entry_manager::get_accepted_choice_label($entry, $getvalue);
}

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

    'acceptedchoicelabel' => s($acceptedchoicelabel),

    'backurl' => (new moodle_url('/mod/dhbwio/view.php', [
        'id' => $cm->id,
    ]))->out(false),
];

echo $OUTPUT->render_from_template(
    'mod_dhbwio/application_form',
    $templatecontext
);
echo $OUTPUT->footer();
