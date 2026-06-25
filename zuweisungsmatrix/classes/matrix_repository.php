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

namespace local_zuweisungsmatrix;

defined('MOODLE_INTERNAL') || die();

/**
 * Persistenz einer Zuweisungsrunde (Speichern & Laden).
 *
 * Kapselt die zuvor in save_matrix.php / load_matrix.php prozedural enthaltene
 * Datenbanklogik als wiederverwendbare, testbare Einheit. Die AJAX-Endpunkte
 * rufen ausschließlich diese Methoden auf und reichen deren Rückgabe als JSON
 * weiter, sodass Endpunkt und Integrationstest exakt denselben Code ausführen.
 *
 * @package    local_zuweisungsmatrix
 * @copyright  2026, DHBW
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matrix_repository {

    /** @var string Master-Tabelle der Zuweisungsrunden. */
    private const TABLE_MASTER = 'local_matrixzuweisung_master';

    /** @var string Detail-Tabelle (einzelne Student-Hochschule-Zuweisungen). */
    private const TABLE_DETAILS = 'local_matrixzuweisung_details';

    /**
     * Speichert eine Zuweisungsmatrix (neu anlegen oder bestehende ersetzen).
     *
     * Ohne (gültige) masterid wird ein neuer Master samt Details angelegt. Mit
     * gültiger masterid werden die alten Details ersetzt und timemodified
     * aktualisiert. Sämtliche Schreibvorgänge laufen in einer Transaktion; bei
     * einem Fehler wird die gesamte Transaktion zurückgerollt und es verbleiben
     * keine Teil-Daten.
     *
     * @param \stdClass $data Eingabe mit Feldern:
     *                        - name (string, nur beim Neuanlegen nötig),
     *                        - masterid (int, optional; >0 = Update),
     *                        - details (array von Objekten mit studentid/universityid).
     * @return array Antwort: ['success' => bool, 'message' => string, 'masterid' => int].
     */
    public static function save(\stdClass $data): array {
        global $DB;

        try {
            // Ohne Zuweisungen gibt es nichts zu speichern.
            if (empty($data->details) || !is_array($data->details)) {
                throw new \Exception('Keine Zuweisungen empfangen.');
            }

            $masterid = null;

            // === START TRANSAKTION ===
            $transaction = $DB->start_delegated_transaction();

            try {
                // === CASE 1: UPDATE einer existierenden Matrix ===
                if (!empty($data->masterid) && $data->masterid > 0) {
                    $masterid = (int) $data->masterid;

                    $master = $DB->get_record(self::TABLE_MASTER, ['id' => $masterid]);
                    if (!$master) {
                        throw new \Exception('Die ausgewählte Matrix wurde nicht gefunden.');
                    }

                    // Alte Details ersetzen und Änderungszeit aktualisieren.
                    $DB->delete_records(self::TABLE_DETAILS, ['masterid' => $masterid]);
                    $master->timemodified = time();
                    $DB->update_record(self::TABLE_MASTER, $master);
                } else {
                    // === CASE 2: INSERT einer neuen Matrix ===
                    if (empty($data->name) || trim($data->name) === '') {
                        throw new \Exception('Kein Name für die Zuweisungsrunde angegeben.');
                    }

                    $master = new \stdClass();
                    $master->name = trim($data->name);
                    $master->timecreated = time();
                    $master->timemodified = time();

                    $masterid = $DB->insert_record(self::TABLE_MASTER, $master);
                }

                // Einzelne Student-Hochschule-Zuweisungen speichern.
                foreach ($data->details as $entry) {
                    // Ungültige Einträge überspringen.
                    if (empty($entry->studentid) || empty($entry->universityid)) {
                        continue;
                    }

                    $detail = new \stdClass();
                    $detail->masterid = $masterid;
                    $detail->studentid = (int) $entry->studentid;
                    $detail->universityid = (int) $entry->universityid;

                    $DB->insert_record(self::TABLE_DETAILS, $detail);
                }

                // === COMMIT ===
                $transaction->allow_commit();

                return [
                    'success' => true,
                    'message' => 'Zuweisungsmatrix wurde gespeichert.',
                    'masterid' => $masterid,
                ];
            } catch (\Throwable $e) {
                // === ROLLBACK – verwirft alle Schreibvorgänge dieser Transaktion. ===
                // rollback() wirft die Exception erneut; sie wird unten behandelt.
                $transaction->rollback($e);
            }
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        // Nicht erreichbar: rollback() wirft stets erneut. Dient nur der
        // Garantie eines array-Rückgabewerts.
        return ['success' => false, 'message' => 'Unbekannter Fehler beim Speichern.'];
    }

    /**
     * Lädt eine gespeicherte Zuweisungsmatrix samt aller Details.
     *
     * @param int $masterid ID des zu ladenden Masters (> 0).
     * @return array Antwort: bei Erfolg ['success' => true, 'matrix' => [...]],
     *               sonst ['success' => false, 'message' => string].
     */
    public static function load(int $masterid): array {
        global $DB;

        try {
            if ($masterid <= 0) {
                throw new \Exception('Keine gültige Matrix-ID angegeben.');
            }

            $master = $DB->get_record(self::TABLE_MASTER, ['id' => $masterid]);
            if (!$master) {
                throw new \Exception('Die ausgewählte Matrix wurde nicht gefunden.');
            }

            $details = $DB->get_records(self::TABLE_DETAILS, ['masterid' => $masterid], 'id ASC');

            $result = [];
            foreach ($details as $detail) {
                $result[] = [
                    'studentid' => (int) $detail->studentid,
                    'universityid' => (int) $detail->universityid,
                ];
            }

            return [
                'success' => true,
                'matrix' => [
                    'id' => (int) $master->id,
                    'name' => $master->name,
                    'timecreated' => (int) $master->timecreated,
                    'timemodified' => (int) $master->timemodified,
                    'details' => $result,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
