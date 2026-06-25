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

/**
 * Integration tests für die Persistenz einer Zuweisungsrunde.
 *
 * User Story (#107): Als Koordinator möchte ich eine Zuweisungsmatrix benannt
 * speichern und wieder laden können, um mehrere Zuweisungsrunden zu verwalten.
 *
 * Getestet wird die echte Datenbank-Persistenz über matrix_repository::save()
 * und ::load(), die von save_matrix.php / load_matrix.php genutzt werden.
 *
 * Akzeptanzkriterien:
 *  - Speichern ohne masterid legt neuen Master + Details an; mit gültiger
 *    masterid werden alte Details ersetzt und timemodified aktualisiert.
 *  - Leerer Name bzw. ungültige masterid liefern success = false, ohne Daten
 *    zu schreiben.
 *  - Bei Fehler während des Speicherns wird die gesamte Transaktion zurückgerollt.
 *  - Laden liefert den Master mit allen Details (studentid, universityid) zurück.
 *
 * @package    local_zuweisungsmatrix
 * @category   phpunit
 * @group      local_zuweisungsmatrix
 * @group      local_zuweisungsmatrix_persistence
 * @covers     \local_zuweisungsmatrix\matrix_repository
 * @copyright  2026, DHBW
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class matrix_persistence_test extends \advanced_testcase {

    /** @var string Master-Tabelle. */
    private const TABLE_MASTER = 'local_matrixzuweisung_master';

    /** @var string Detail-Tabelle. */
    private const TABLE_DETAILS = 'local_matrixzuweisung_details';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    // -------------------------------------------------------------------------
    // Test-Helfer
    // -------------------------------------------------------------------------

    /**
     * Baut das Eingabe-Objekt, wie es save_matrix.php aus dem JSON-Request erhält.
     *
     * @param string|null $name Name der Runde (beim Neuanlegen erforderlich).
     * @param int $masterid 0 = neu anlegen, >0 = bestehende Matrix aktualisieren.
     * @param array $details Liste von [studentid, universityid]-Paaren.
     * @return \stdClass
     */
    private function make_request(?string $name, int $masterid, array $details): \stdClass {
        $request = new \stdClass();
        $request->name = $name;
        $request->masterid = $masterid;
        $request->details = array_map(static function (array $pair): \stdClass {
            $entry = new \stdClass();
            $entry->studentid = $pair[0];
            $entry->universityid = $pair[1];
            return $entry;
        }, $details);
        return $request;
    }

    /**
     * Legt direkt in der DB eine Master-Matrix mit Details an (Test-Fixture).
     *
     * @param string $name Name.
     * @param int $time Zeitstempel für timecreated/timemodified.
     * @param array $details Liste von [studentid, universityid]-Paaren.
     * @return int Master-ID.
     */
    private function seed_matrix(string $name, int $time, array $details): int {
        global $DB;

        $masterid = (int) $DB->insert_record(self::TABLE_MASTER, (object) [
            'name' => $name,
            'timecreated' => $time,
            'timemodified' => $time,
        ]);
        foreach ($details as $pair) {
            $DB->insert_record(self::TABLE_DETAILS, (object) [
                'masterid' => $masterid,
                'studentid' => $pair[0],
                'universityid' => $pair[1],
            ]);
        }
        return $masterid;
    }

    /**
     * Liest die Details einer Matrix als [studentid => universityid]-Map.
     *
     * @param int $masterid Master-ID.
     * @return array<int,int>
     */
    private function fetch_details(int $masterid): array {
        global $DB;

        $map = [];
        $records = $DB->get_records(self::TABLE_DETAILS, ['masterid' => $masterid], 'id ASC');
        foreach ($records as $record) {
            $map[(int) $record->studentid] = (int) $record->universityid;
        }
        return $map;
    }

    // -------------------------------------------------------------------------
    // AC: Speichern ohne masterid legt neuen Master + Details an.
    // -------------------------------------------------------------------------

    public function test_save_new_creates_master_and_details(): void {
        global $DB;

        $request = $this->make_request('Bewerbungsrunde 2025', 0, [[10, 1], [11, 2], [12, 1]]);

        $result = matrix_repository::save($request);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('masterid', $result);
        $masterid = (int) $result['masterid'];

        // Master korrekt angelegt.
        $master = $DB->get_record(self::TABLE_MASTER, ['id' => $masterid], '*', MUST_EXIST);
        $this->assertSame('Bewerbungsrunde 2025', $master->name);
        $this->assertGreaterThan(0, (int) $master->timecreated);
        $this->assertGreaterThanOrEqual((int) $master->timecreated, (int) $master->timemodified);

        // Alle Details gespeichert.
        $this->assertSame([10 => 1, 11 => 2, 12 => 1], $this->fetch_details($masterid));
    }

    // -------------------------------------------------------------------------
    // AC: Speichern mit gültiger masterid ersetzt Details + aktualisiert timemodified.
    // -------------------------------------------------------------------------

    public function test_save_update_replaces_details_and_updates_timemodified(): void {
        global $DB;

        $masterid = $this->seed_matrix('Alte Runde', 1000, [[1, 1], [2, 2]]);

        $before = time();
        $request = $this->make_request(null, $masterid, [[99, 3]]);

        $result = matrix_repository::save($request);

        $this->assertTrue($result['success']);
        $this->assertSame($masterid, (int) $result['masterid']);

        // Alte Details ersetzt – nur noch der neue Eintrag existiert.
        $this->assertSame([99 => 3], $this->fetch_details($masterid));

        $master = $DB->get_record(self::TABLE_MASTER, ['id' => $masterid], '*', MUST_EXIST);
        // Name und Erstellzeit bleiben unverändert.
        $this->assertSame('Alte Runde', $master->name);
        $this->assertSame(1000, (int) $master->timecreated);
        // timemodified wurde auf "jetzt" aktualisiert.
        $this->assertGreaterThanOrEqual($before, (int) $master->timemodified);
        $this->assertNotSame(1000, (int) $master->timemodified);
    }

    // -------------------------------------------------------------------------
    // AC: Leerer Name -> success = false, ohne Daten zu schreiben.
    // -------------------------------------------------------------------------

    public function test_save_with_empty_name_fails_without_writing(): void {
        global $DB;

        $request = $this->make_request('   ', 0, [[1, 1]]);

        $result = matrix_repository::save($request);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $DB->count_records(self::TABLE_MASTER));
        $this->assertSame(0, $DB->count_records(self::TABLE_DETAILS));
    }

    // -------------------------------------------------------------------------
    // AC: Ungültige (nicht existierende) masterid -> success = false, keine Writes.
    // -------------------------------------------------------------------------

    public function test_save_with_invalid_masterid_fails_without_writing(): void {
        global $DB;

        $request = $this->make_request('Egal', 999999, [[1, 1]]);

        $result = matrix_repository::save($request);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $DB->count_records(self::TABLE_MASTER));
        $this->assertSame(0, $DB->count_records(self::TABLE_DETAILS));
    }

    // -------------------------------------------------------------------------
    // AC: Fehler während des Speicherns -> gesamte Transaktion zurückgerollt.
    // -------------------------------------------------------------------------

    public function test_error_during_save_rolls_back_transaction(): void {
        global $DB;

        // Bestehende, gültige Matrix als Beweis, dass sie unangetastet bleibt.
        $existingid = $this->seed_matrix('Bestehende Runde', 500, [[7, 7]]);

        // Ein Name über die Spaltenlänge (char(255)) hinaus erzwingt einen
        // DML-Fehler beim Master-Insert -> die Transaktion muss alles verwerfen.
        $request = $this->make_request(str_repeat('x', 300), 0, [[1, 1], [2, 2]]);

        $result = matrix_repository::save($request);

        $this->assertFalse($result['success']);

        // Keine neue Matrix und keine neuen Details geschrieben ...
        $this->assertSame(1, $DB->count_records(self::TABLE_MASTER));
        $this->assertSame(1, $DB->count_records(self::TABLE_DETAILS));
        // ... und die bestehende Matrix ist unverändert.
        $this->assertTrue($DB->record_exists(self::TABLE_MASTER,
            ['id' => $existingid, 'name' => 'Bestehende Runde']));
        $this->assertSame([7 => 7], $this->fetch_details($existingid));
    }

    // -------------------------------------------------------------------------
    // AC: Laden liefert den Master mit allen Details korrekt zurück.
    // -------------------------------------------------------------------------

    public function test_load_returns_master_with_all_details(): void {
        $masterid = $this->seed_matrix('Geladene Runde', 1234, [[4, 2], [5, 3]]);
        // Fremde Matrix als Rauschen – ihre Details dürfen nicht "durchsickern".
        $this->seed_matrix('Andere Runde', 1, [[99, 99]]);

        $result = matrix_repository::load($masterid);

        $this->assertTrue($result['success']);
        $this->assertSame($masterid, $result['matrix']['id']);
        $this->assertSame('Geladene Runde', $result['matrix']['name']);
        $this->assertSame(1234, $result['matrix']['timecreated']);
        $this->assertSame(1234, $result['matrix']['timemodified']);

        // Genau die zwei eigenen Details, korrekt (Reihenfolge nach id ASC).
        $this->assertCount(2, $result['matrix']['details']);
        $this->assertSame(['studentid' => 4, 'universityid' => 2], $result['matrix']['details'][0]);
        $this->assertSame(['studentid' => 5, 'universityid' => 3], $result['matrix']['details'][1]);
    }

    public function test_load_with_invalid_masterid_fails(): void {
        // Nicht-positive ID.
        $this->assertFalse(matrix_repository::load(0)['success']);
        // Positive, aber nicht existierende ID.
        $this->assertFalse(matrix_repository::load(999999)['success']);
    }

    // -------------------------------------------------------------------------
    // Integration: Speichern und anschließendes Laden ergeben dieselben Daten.
    // -------------------------------------------------------------------------

    public function test_save_then_load_roundtrip(): void {
        $request = $this->make_request('Round-Trip', 0, [[20, 5], [21, 6], [22, 5]]);
        $saved = matrix_repository::save($request);
        $this->assertTrue($saved['success']);

        $loaded = matrix_repository::load((int) $saved['masterid']);

        $this->assertTrue($loaded['success']);
        $this->assertSame('Round-Trip', $loaded['matrix']['name']);
        $this->assertCount(3, $loaded['matrix']['details']);

        // Gespeicherte Paare exakt wieder auslesbar.
        $paare = array_map(static fn(array $d): array => [$d['studentid'], $d['universityid']],
            $loaded['matrix']['details']);
        $this->assertEqualsCanonicalizing([[20, 5], [21, 6], [22, 5]], $paare);
    }
}
