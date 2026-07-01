<?php
// Learning Agreement page for mod_dhbwio
require_once('../../config.php');
require_once(__DIR__ . '/locallib.php');

use mod_dhbwio\local\dataform\la_manager;

global $USER, $DB;

$cmid = required_param('id', PARAM_INT);
$cm = null;
if ($cmid > 0) {
    $cm = get_coursemodule_from_id('dhbwio', $cmid, 0, false, IGNORE_MISSING);
}
if (!$cm) {
    throw new moodle_exception('invalidcmid', 'error');
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);


$PAGE->set_url(new moodle_url('/mod/dhbwio/learning_agreement.php', ['id' => $cm->id]));
$PAGE->set_context($context);
$PAGE->set_title('Learning Agreement');


$entryid = 0;
$app_entryid = 0;
//schauen ob der Nutzer bereits ein Learning Agreement hat, wenn ja, dann die ID laden, wenn nein, dann die letzte Bewerbung laden
if(la_manager::get_la_by_userid($USER->id)){
    $la_record = la_manager::get_la_by_userid($USER->id);
    $entryid = $la_record->id;
    $app_entryid = $la_record->app_entryid;
} else {
    $app_record = la_manager::get_last_modified_app_for_user($USER->id);
    $app_entryid = $app_record->id ?? 0;
}



$PAGE->set_heading(format_string($course->fullname));

// Load plugin CSS
$PAGE->requires->css('/mod/dhbwio/learning agreement/learning_agreement_formular.css');

// Initialize AMD JS modules
$PAGE->requires->js_call_amd('mod_dhbwio/learning_agreement_formular', 'init');

echo $OUTPUT->header();

// ------------------------------
// Helper functions
// ------------------------------
function _bv_render_input($name, $value = '', $type = 'text', $placeholder = '') {
    $placeholderattr = $placeholder !== '' ? ' placeholder="' . s($placeholder) . '"' : '';
    return '<input type="' . s($type) . '" name="field_' . s($name) . '" id="field_' . s($name) . '" value="' . s($value) . '" class="form-control"' . $placeholderattr . '>';
}

function _bv_render_date_range($namefrom, $nameto, $valuefrom = '', $valueto = '') {
    $valuefrom_date = !empty($valuefrom) ? date('Y-m-d', $valuefrom) : '';
    $valueto_date = !empty($valueto) ? date('Y-m-d', $valueto) : '';
    return '
        <div class="la-date-range">
            <input type="date" name="field_' . s($namefrom) . '" id="field_' . s($namefrom) . '" value="' . s($valuefrom_date) . '" class="form-control la-date-input">
            <span class="la-date-separator">bis</span>
            <input type="date" name="field_' . s($nameto) . '" id="field_' . s($nameto) . '" value="' . s($valueto_date) . '" class="form-control la-date-input">
        </div>
    ';
}

function _bv_render_select($name, $options = [], $value = '') {
    $html = '<select name="field_' . s($name) . '" id="field_' . s($name) . '" class="form-control">';
    $html .= '<option value="">-- Bitte wählen --</option>';

    foreach ($options as $key => $label) {
        $selected = ((string)$value === (string)$key) ? ' selected' : '';
        $html .= '<option value="' . s($key) . '"' . $selected . '>' . s($label) . '</option>';
    }

    $html .= '</select>';
    return $html;
}

// ------------------------------
// Load entry data if entryid is available
// ------------------------------
$la_data = null;
if ($entryid > 0) {
    // Hier wird das bestehende LA basierend auf der gefundenen ID geladen
    $la_data = dhbwio_get_full_la_by_la_id($entryid);
}

// Studienrichtungen aus der Datenbank laden
$studienrichtung_options = [];
$studytracks = $DB->get_records('dhbwio_studytracks', ['active' => 1], 'sortorder ASC');
$track_name_to_id = [];

foreach ($studytracks as $track) {
    $studienrichtung_options[$track->de_name] = $track->de_name;
    $track_name_to_id[$track->de_name] = $track->id;
}

$content = $la_data->content ?? new stdClass();

// Werte aus der spezifischen Bewerbung vorbefüllen, falls es ein neues LA ist
if ($entryid == 0) {
    // Die genaue Bewerbung (app_entryid) aus Dataform abrufen
    $app_entry_record = $DB->get_record_sql('SELECT * FROM {dhbwio_dataform_entries} WHERE id = ? ORDER BY timemodified DESC LIMIT 1', [$app_entryid], IGNORE_MISSING);
    if(!$app_entry_record || $app_entryid == 0) {
            throw new moodle_exception($app_entryid, 'dhbwio');
        }

    $name = $DB->get_field('dhbwio_dataform_contents', 'content', ['entryid' => $app_entry_record->id, 'fieldid' => 16], IGNORE_MISSING);
    $vorname = $DB->get_field('dhbwio_dataform_contents', 'content', ['entryid' => $app_entry_record->id, 'fieldid' => 15], IGNORE_MISSING);
    $studiengang = $DB->get_field('dhbwio_dataform_contents', 'content', ['entryid' => $app_entry_record->id, 'fieldid' => 30], IGNORE_MISSING);
    $studienrichtungname = $DB->get_field('dhbwio_dataform_contents', 'content', ['entryid' => $app_entry_record->id, 'fieldid' => 18], IGNORE_MISSING);
    $gasthochschulid = $DB->get_field('dhbwio_dataform_entries', 'acceptedchoice', ['id' => $app_entry_record->id], IGNORE_MISSING);
    $gasthochschule = $DB->get_field('dhbwio_universities', 'name', ['id' => $gasthochschulid], IGNORE_MISSING);

    if(!$name || !$vorname || !$studiengang || !$studienrichtungname || !$gasthochschule) {
        throw new moodle_exception($$app_entry_record->id, 'dhbwio');
    }

    //studienrichtung auf der Bewerbung aktuell noch per Textfeld gesetzt daher nach studienrichtungsname in der Tabelle dhbwio_studytracks suchen und die ID zurückgeben
    //Wenn Studienrichtung numerischer Wert ist, dann direkt die ID zurückgeben
    if(is_numeric($studienrichtungname)) {
        $studienrichtung = (int)$studienrichtungname;
        $studienrichtungname = $DB->get_field('dhbwio_studytracks', 'de_name', ['id' => $studienrichtung], IGNORE_MISSING);
    } else {
        $studienrichtung = $DB->get_field('dhbwio_studytracks', 'id', ['de_name' => $studienrichtungname], IGNORE_MISSING);
    }


    $content->name = $name;
    $content->vorname = $vorname;
    $content->studiengang = $studiengang;
    $content->studienrichtung = $studienrichtungname;
    $content->gasthochschule = $gasthochschule;

    if(!$content->name || !$content->vorname || !$content->studiengang || !$content->studienrichtung || !$content->gasthochschule) {
        throw new moodle_exception($content->name, 'dhbwio');
    }

}


// Wahlmodule basierend auf der (vor-)gewählten Studienrichtung ermitteln
$wahlmodul_options = [];
$all_electives = $DB->get_records('dhbwio_electives', ['active' => 1], 'sortorder ASC');
$electives_by_track = [];

foreach ($all_electives as $el) {
    $electives_by_track[$el->studytrackid][] = $el->de_name;
}

$selected_track_name = $content->studienrichtung ?? '';
$selected_track_id = $track_name_to_id[$selected_track_name] ?? null;

// Initiales Dropdown befüllen (nur passende Module oder alle, falls nichts gewählt)
if ($selected_track_id && isset($electives_by_track[$selected_track_id])) {
    foreach ($electives_by_track[$selected_track_id] as $mod_name) {
        $wahlmodul_options[$mod_name] = $mod_name;
    }
} else {
    foreach ($all_electives as $el) {
        $wahlmodul_options[$el->de_name] = $el->de_name;
    }
}

// Dynamisches Nachladen der Wahlmodule bei Änderung der Studienrichtung per JS
$js_code = '
    var electivesByTrack = ' . json_encode($electives_by_track) . ';
    var trackNameToId = ' . json_encode($track_name_to_id) . ';

    var trackSelect = document.getElementById("field_Studienrichtung");
    var moduleSelect = document.getElementById("field_wahlmodul");

    if (trackSelect && moduleSelect) {
        trackSelect.addEventListener("change", function(e) {
            moduleSelect.innerHTML = "<option value=\'\'>-- Bitte wählen --</option>";
            var trackId = trackNameToId[e.target.value];

            if (trackId && electivesByTrack[trackId]) {
                electivesByTrack[trackId].forEach(function(mod) {
                    var opt = document.createElement("option");
                    opt.value = mod;
                    opt.textContent = mod;
                    moduleSelect.appendChild(opt);
                });
            }
        });
    }
';
$PAGE->requires->js_init_code($js_code);


$fields = [
    'NACHNAME' => ['html' => _bv_render_input('NACHNAME', $content->name ?? '')],
    'VORNAME' => ['html' => _bv_render_input('VORNAME', $content->vorname ?? '')],
    'Zeitraum' => ['html' => _bv_render_date_range('Zeitraum_von', 'Zeitraum_bis', $content->zeitraum_von ?? 0, $content->zeitraum_bis ?? 0)],
    'Studienrichtung' => ['html' => _bv_render_select('Studienrichtung', $studienrichtung_options, $content->studienrichtung ?? '')],
    'wahlmodul' => ['html' => _bv_render_select('wahlmodul', $wahlmodul_options, $content->wahlmodul ?? '')],
    'Gasthochschule' => ['html' => _bv_render_input('Gasthochschule', $content->gasthochschule ?? '')],
];

$courserows = [];
if (!empty($la_data->modules)) {
    foreach ($la_data->modules as $i => $module) {
        $courserows[] = [
            'row_number' => $i + 1,
            'modul_name' => $module->modul_name,
            'ects' => $module->ects,
            'anteil' => $module->teilpruefungsanteil,
            'partnerhochschule_value' => $module->anrechnungs_lv,
            'credits' => $module->credits
        ];
    }
}

// Wenn keine Module geladen wurden, zwei leere Zeilen hinzufügen
if (empty($courserows)) {
    $courserows[] = ['row_number' => 1, 'modul_name' => '', 'ects' => '', 'anteil' => '', 'partnerhochschule_value' => '', 'credits' => ''];
    $courserows[] = ['row_number' => 2, 'modul_name' => '', 'ects' => '', 'anteil' => '', 'partnerhochschule_value' => '', 'credits' => ''];
}
// Back URL to the learning agreement overview page on the learningagreement tab
// URL should be like http://localhost/mod/dhbwio/view.php?id=25&tab=learningagreement
$backurl ='/mod/dhbwio/view.php?id=' . $cm->id . '&tab=learningagreement';
$templatecontext = [
    'actionurl' => (string)new moodle_url('/mod/dhbwio/learning_agreement_submit.php'),
    'sesskey' => sesskey(),
    'id' => $cm->id,
    'entryid' => $entryid, // Interne LA ID
    'app_entryid' => $app_entryid, // Übermitteln, damit submit.php sie mitspeichern kann
    'studiengang' => $content->studiengang ?? '',
    'fields' => $fields,
    'courserows' => $courserows,
    'backurl' => (string)str_replace('&amp;', '&',new moodle_url($backurl)),
];

echo $OUTPUT->render_from_template('mod_dhbwio/learning_agreement_formular', $templatecontext);

echo $OUTPUT->footer();
