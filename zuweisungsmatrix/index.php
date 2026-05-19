<?php
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/zuweisungsmatrix:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/zuweisungsmatrix/index.php', ['courseid' => $courseid]));
$PAGE->set_title('Zuweisung der Bewerbenden');
$PAGE->set_heading($course->fullname);

$PAGE->requires->css(new moodle_url('/local/zuweisungsmatrix/styles.css', ['v' => time()]));
$PAGE->requires->js(new moodle_url('/local/zuweisungsmatrix/scripts.js', ['v' => time()]));

echo $OUTPUT->header();

// === HARTKODIERTE HOCHSCHULEN ===
/*$hochschulen = [
    ['name' => 'Bulgarien', 'plätze' => 5],
    ['name' => 'Costa Rica', 'plätze' => 5],
    ['name' => 'Finnland (Savonia)', 'plätze' => 4],
    ['name' => 'Finnland (South-Eastern)', 'plätze' => 4],
    ['name' => 'Großbritannien', 'plätze' => 5],
    ['name' => 'Südafrika', 'plätze' => 5],
    ['name' => 'Taiwan', 'plätze' => 5],
    ['name' => 'USA', 'plätze' => 3],
];
*/

// Abruf der Hoschulen über dhbwio-Plugin
$hochschulen_raw = $DB->get_records_sql("
    SELECT id, name, available_slots
    FROM {dhbwio_universities}
    WHERE active = 1
    ORDER BY name
");

$hochschulen = [];
foreach ($hochschulen_raw as $record) {
    $hochschulen[] = [
        'id' => (int) $record->id,
        'name' => $record->name,
        'plätze' => (int) $record->available_slots
    ];
}

$entriesql = "
    SELECT e.id AS entryid,
           MAX(CASE WHEN f.name = 'VORNAME' THEN c.content ELSE NULL END) AS vorname,
           MAX(CASE WHEN f.name = 'NACHNAME' THEN c.content ELSE NULL END) AS nachname,
           MAX(CASE WHEN f.name = 'ERSTWUNSCH' THEN uniw1.name END) AS Erstwunsch,
           MAX(CASE WHEN f.name = 'ZWEITWUNSCH' THEN uniw2.name END) AS Zweitwunsch,
           MAX(CASE WHEN f.name = 'DRITTWUNSCH' THEN uniw3.name END) AS Drittwunsch
    FROM {dataform_entries} e
    JOIN {dataform_contents} c ON c.entryid = e.id
    JOIN {dataform_fields} f ON f.id = c.fieldid
    LEFT JOIN {dhbwio_universities} uniw1 
        ON uniw1.id = c.content AND f.name = 'ERSTWUNSCH'
    LEFT JOIN {dhbwio_universities} uniw2 
        ON uniw2.id = c.content AND f.name = 'ZWEITWUNSCH'
    LEFT JOIN {dhbwio_universities} uniw3 
        ON uniw3.id = c.content AND f.name = 'DRITTWUNSCH'
    WHERE e.state <> 3
    GROUP BY e.id
";

$studenten = $DB->get_records_sql($entriesql);
// Bestehende Zuweisungen abrufen
//$zuweisungen = $DB->get_records('local_matrixzuweisung', null, '', 'studentid, hochschule');

?>

<!--Bewerberlist-->
<div style="display: flex; gap: 20px;">
    <div style="width: 220px;">
        <h3>Bewerber</h3>
        <div id="studentList" ondrop="drop(event)" ondragover="allowDrop(event)"
            ondragenter="this.classList.add('over')" ondragleave="this.classList.remove('over')">
            <div id="emptyInfo" style="display: none; margin-top: 10px; color: gray;">
                &#x2139; <em>Hier können erneut Bewerbende platziert werden</em>
            </div>
            <?php
            $idx = 0;
            foreach ($studenten as $s) {
                $id = "student-" . (int)$s->entryid;

                echo "<div class='student' id='$id' draggable='true'
                    data-studentid='" . (int)$s->entryid . "'
                    ondragstart=\"event.dataTransfer.setData('text/plain', '$id')\">
                    <strong>" . htmlspecialchars($s->vorname . ' ' . $s->nachname) . "</strong><br>
                    <div class='wuensche'>
                    <small>
                    1. " . htmlspecialchars($s->erstwunsch) . "<br>
                    2. " . htmlspecialchars($s->zweitwunsch) . "<br>
                    3. " . htmlspecialchars($s->drittwunsch) . "
                    </small>
                    </div>
                    </div>";
            }
            ?>
        </div>
    </div>

    <!--Zuweisungsmatrix-->
    <div style="overflow-x: auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
            <h3 style="margin: 0;">Zuweisung</h3>
            <label style="font-weight: normal;">
                <input type="checkbox" id="showWishes" checked>
                Wünsche anzeigen
            </label>
        </div>
        <table id="matrix" border="1" cellspacing="0" cellpadding="10" style="border-collapse: collapse;">
            <thead>
                <tr>
                    <th></th>
                    <?php foreach ($hochschulen as $h): ?>
                        <th><?= htmlspecialchars($h['name']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $maxRows = max(array_column($hochschulen, 'plätze'));
                for ($i = 0; $i < $maxRows; $i++): ?>
                    <tr>
                        <td>Platz <?= $i + 1 ?></td>
                        <?php foreach ($hochschulen as $h): ?>
                            <?php if ($i < $h['plätze']): ?>
                                <td class="drop-cell"
                                    data-universityid="<?= (int)$h['id'] ?>"
                                    ondragover="allowDrop(event)"
                                    ondragleave="this.classList.remove('over')"
                                    ondrop="drop(event)"
                                    ondragenter="this.classList.add('over')"></td>
                            <?php else: ?>
                                <td class="drop-cell disabled"></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<!--Buttons: Reset, Zuweiungs, Export-->
<div id="buttonRow" style="margin-top: 20px; display: flex; gap: 12px;">
    <button id="submitBtn" class="red-button red-button-primary" onclick="automatischZuteilen()">Zuteilung</button>
    <button id="saveBtn" type="button" class="red-button red-button-secondary" onclick="saveMatrixToDatabase()">Speichern</button>
    <button id="openBtnInline" type="button" class="red-button red-button-secondary" onclick="openSavedMatrix()">Öffnen</button>
    <form id="exportForm" method="post" action="export.php" style="display:inline;">
        <input type="hidden" name="matrixdata" id="matrixdata">
        <input type="hidden" name="hochschulnamen" id="hochschulnamen">
        <button id="exportBtn" type="submit" class="red-button red-button-tertiary" onclick="prepareExport()">Export</button>
    </form>
    <button id="resetBtn" class="red-button red-button-tertiary" onclick="resetZuweisung()">Reset</button>
</div>

<div id="matrixOpenModal" class="matrix-modal" hidden>
    <div class="matrix-modal-content" role="dialog" aria-modal="true" aria-labelledby="matrixOpenTitle">
        <div class="matrix-modal-header">
            <h3 id="matrixOpenTitle">Gespeicherte Matrix öffnen</h3>
            <button type="button" class="matrix-modal-close" onclick="closeMatrixOpenModal()" aria-label="Dialog schließen">×</button>
        </div>

        <label for="matrixSearchInput" class="matrix-modal-label">Nach Name suchen</label>
        <input id="matrixSearchInput" type="text" class="matrix-search-input" placeholder="z. B. Sommersemester 2026">

        <label for="matrixSelect" class="matrix-modal-label">Matrix auswählen</label>
        <select id="matrixSelect" class="matrix-select" size="10"></select>

        <div id="matrixOpenStatus" class="matrix-modal-status"></div>

        <div class="matrix-modal-actions">
            <button type="button" class="red-button" onclick="confirmOpenSelectedMatrix()">Öffnen</button>
            <button type="button" class="red-button" onclick="closeMatrixOpenModal()">Abbrechen</button>
        </div>
    </div>
</div>


<?php
echo $OUTPUT->footer();