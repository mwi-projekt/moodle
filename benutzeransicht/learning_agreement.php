<?php
// Learning Agreement page for mod_benutzeransicht
require_once('../../config.php');
require_once(__DIR__ . '/locallib.php'); // locallib einbinden

global $USER, $DB;

$cmid = required_param('id', PARAM_INT);
// $entryid wird nicht mehr als Parameter erwartet, sondern hier ermittelt.
$entryid = benutzeransicht_get_la_contentid_by_userid($USER->id) ?? 0;

// Versuche zuerst benutzeransicht CM zu laden, falls das nicht geht, nutze dhbwio-Kontext
$cm = null;
if ($cmid > 0) {
    $cm = get_coursemodule_from_id('benutzeransicht', $cmid, 0, false, IGNORE_MISSING);
    if (!$cm) {
        $cm = get_coursemodule_from_id('dhbwio', $cmid, 0, false, IGNORE_MISSING);
    }
}

if (!$cm) {
    throw new moodle_exception('invalidcmid', 'error');
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url(new moodle_url('/mod/benutzeransicht/learning_agreement.php', [
    'id' => $cm->id
]));
$PAGE->set_context($context);
$PAGE->set_title('Learning Agreement');
$PAGE->set_heading(format_string($course->fullname));

// Load plugin CSS
$PAGE->requires->css('/mod/benutzeransicht/learning agreement/learning_agreement_formular.css');

// Initialize AMD JS modules
$PAGE->requires->js_call_amd('mod_benutzeransicht/learning_agreement_formular', 'init');

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
    $la_data = benutzeransicht_get_full_la_by_contentid($entryid);
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

// NEU: Werte aus DHBWIO Dataform vorbefüllen, falls es ein neuer Eintrag ist
if ($entryid == 0) {
    // Fallback: Daten aus dem Moodle-Profil des Nutzers
    $content->name = $USER->lastname;
    $content->vorname = $USER->firstname;
    $content->studienrichtung = '';

    // Letzten vorhandenen Dataform-Eintrag des Nutzers ermitteln
    $sql_entry = "SELECT id
                    FROM {dhbwio_dataform_entries}
                   WHERE userid = :userid
                ORDER BY timecreated DESC";
    $latest_events = $DB->get_records_sql($sql_entry, ['userid' => $USER->id], 0, 1);

    if (!empty($latest_events)) {
        $dhbwio_entry = reset($latest_events);

        // Verknüpfte Inhalte mit den Feldnamen abrufen
        $sql_contents = "SELECT f.name, c.content
                           FROM {dhbwio_dataform_contents} c
                           JOIN {dhbwio_dataform_fields} f ON f.id = c.fieldid
                          WHERE c.entryid = :entryid";
        $form_contents = $DB->get_records_sql_menu($sql_contents, ['entryid' => $dhbwio_entry->id]);

        foreach ($form_contents as $fieldname => $val) {
            $fieldname_lower = strtolower($fieldname);

            if (strpos($fieldname_lower, 'nachname') !== false || strpos($fieldname_lower, 'lastname') !== false) {
                if (!empty($val)) $content->name = $val;
            }
            if (strpos($fieldname_lower, 'vorname') !== false || strpos($fieldname_lower, 'firstname') !== false) {
                if (!empty($val)) $content->vorname = $val;
            }
            if (strpos($fieldname_lower, 'studienrichtung') !== false || strpos($fieldname_lower, 'studytrack') !== false) {
                if (!empty($val)) $content->studienrichtung = $val;
            }
        }
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


$templatecontext = [
    'actionurl' => (string)new moodle_url('/mod/benutzeransicht/learning_agreement_submit.php'),
    'sesskey' => sesskey(),
    'id' => $cm->id,
    'entryid' => $entryid,
    'fields' => $fields,
    'courserows' => $courserows,
    'backurl' => (string)new moodle_url('/mod/dhbwio/view.php', ['id' => $cm->id])
];

echo $OUTPUT->render_from_template('mod_benutzeransicht/learning_agreement', $templatecontext);

echo $OUTPUT->footer();
