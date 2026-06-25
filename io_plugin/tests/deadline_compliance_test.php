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

namespace mod_dhbwio;

use mod_dhbwio\local\dataform\entry_manager;

/**
 * Unit tests for the automatic application deadline check.
 *
 * User Story: Als International Office möchte ich, dass Bewerbungen automatisch
 * auf Fristeinhaltung geprüft werden, damit verspätete Einträge erkennbar sind.
 *
 * Getestet wird das Feature der Gruppe von Silas:
 * entry_manager::compute_within_deadline() liefert 1 (innerhalb der Frist /
 * keine Einschränkung) oder 0 (verspätet).
 *
 * @package    mod_dhbwio
 * @category   phpunit
 * @group      mod_dhbwio
 * @group      mod_dhbwio_deadline
 * @covers     \mod_dhbwio\local\dataform\entry_manager::compute_within_deadline
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class deadline_compliance_test extends \advanced_testcase {

    /** @var int Gemeinsame dataform-ID für Einträge und Felder. */
    private int $dataid = 4242;

    /** @var int dhbwio-Instanz-ID, auf die sich die Fristen beziehen. */
    private int $dhbwioid = 777;

    /** @var array<string,int> Feld-IDs (STUDIENGANG, KURSNAME). */
    private array $fields = [];

    /** Feste Zeitstempel für deterministische Vergleiche. */
    private const DEADLINE_FUTURE = 2000000000; // ~2033
    private const DEADLINE_PAST   = 1000000000; // ~2001
    private const ENTRY_TIME      = 1500000000; // dazwischen

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->fields = $this->create_fields($this->dataid);
    }

    // -------------------------------------------------------------------------
    // Test-Helfer
    // -------------------------------------------------------------------------

    /**
     * Legt die für die Prüfung nötigen Formularfelder STUDIENGANG und KURSNAME an.
     *
     * @param int $dataid dataform-ID.
     * @return array<string,int> Feldname => Feld-ID.
     */
    private function create_fields(int $dataid): array {
        global $DB;

        $names = ['STUDIENGANG', 'KURSNAME'];
        $ids = [];
        foreach ($names as $name) {
            $ids[$name] = (int) $DB->insert_record('dhbwio_dataform_fields', (object) [
                'dataid'      => $dataid,
                'type'        => 'text',
                'name'        => $name,
                'description' => '',
            ]);
        }
        return $ids;
    }

    /**
     * Legt einen Bewerbungseintrag mit Studiengang- und Kursnamen-Inhalt an.
     *
     * @param string|null $studiengang Inhalt des STUDIENGANG-Feldes (null = kein Inhalt).
     * @param string|null $kursname Inhalt des KURSNAME-Feldes (null = kein Inhalt).
     * @param int $timecreated Einreichzeitpunkt.
     * @return int Entry-ID.
     */
    private function create_entry(?string $studiengang, ?string $kursname, int $timecreated): int {
        global $DB;

        $entryid = (int) $DB->insert_record('dhbwio_dataform_entries', (object) [
            'dataid'      => $this->dataid,
            'timecreated' => $timecreated,
        ]);

        if ($studiengang !== null) {
            $DB->insert_record('dhbwio_dataform_contents', (object) [
                'fieldid' => $this->fields['STUDIENGANG'],
                'entryid' => $entryid,
                'content' => $studiengang,
            ]);
        }
        if ($kursname !== null) {
            $DB->insert_record('dhbwio_dataform_contents', (object) [
                'fieldid' => $this->fields['KURSNAME'],
                'entryid' => $entryid,
                'content' => $kursname,
            ]);
        }

        return $entryid;
    }

    /**
     * Legt eine Bewerbungsfrist an.
     *
     * @param string $studiengang Studiengang oder 'alle'.
     * @param string $jahrgang Jahrgang (z.B. '2023').
     * @param int|null $deadline Unix-Timestamp der Deadline.
     * @return int Frist-ID.
     */
    private function create_frist(string $studiengang, string $jahrgang, ?int $deadline): int {
        global $DB;

        return (int) $DB->insert_record('dhbwio_fristen', (object) [
            'dhbwio'      => $this->dhbwioid,
            'art'         => 'bewerbung',
            'studiengang' => $studiengang,
            'jahrgang'    => $jahrgang,
            'deadline'    => $deadline,
            'authorid'    => 2,
            'timecreated' => time(),
        ]);
    }

    /**
     * Kurzform für den Aufruf der zu testenden Funktion.
     */
    private function compute(int $entryid): int {
        return entry_manager::compute_within_deadline($entryid, $this->dhbwioid);
    }

    // -------------------------------------------------------------------------
    // AC: Bewertung – vor Deadline = innerhalb, nach Deadline = verspätet.
    // -------------------------------------------------------------------------

    public function test_entry_before_deadline_is_within(): void {
        $this->create_frist('WWI', '2023', self::DEADLINE_FUTURE);
        $entryid = $this->create_entry('WWI', 'WWI23B2', self::ENTRY_TIME);

        $this->assertSame(1, $this->compute($entryid));
    }

    public function test_entry_after_deadline_is_marked_late(): void {
        // Deadline in der Vergangenheit, Eintrag danach -> verspätet.
        // Dass die Frist überhaupt greift, beweist zugleich die korrekte
        // Jahrgangs-Extraktion (2023) und Studiengang-Zuordnung (WWI).
        $this->create_frist('WWI', '2023', self::DEADLINE_PAST);
        $entryid = $this->create_entry('WWI', 'WWI23B2', self::ENTRY_TIME);

        $this->assertSame(0, $this->compute($entryid));
    }

    // -------------------------------------------------------------------------
    // AC: Jahrgang – wird exakt aus dem Kurskürzel extrahiert.
    // -------------------------------------------------------------------------

    public function test_frist_for_different_jahrgang_does_not_apply(): void {
        // Frist gilt für 2022; "WWI23B2" -> 2023 passt NICHT -> keine Einschränkung.
        $this->create_frist('WWI', '2022', self::DEADLINE_PAST);
        $entryid = $this->create_entry('WWI', 'WWI23B2', self::ENTRY_TIME);

        $this->assertSame(1, $this->compute($entryid));
    }

    public function test_invalid_kursname_is_not_late(): void {
        // Kürzel ohne extrahierbaren Jahrgang -> kein falscher Verspätungs-Flag,
        // obwohl eine vergangene Frist existiert.
        $this->create_frist('WWI', '2023', self::DEADLINE_PAST);
        $entryid = $this->create_entry('WWI', 'OHNEZAHL', self::ENTRY_TIME);

        $this->assertSame(1, $this->compute($entryid));
    }

    // -------------------------------------------------------------------------
    // AC: Zuordnung – Fallback "alle" und Vorrang des spezifischen Studiengangs.
    // -------------------------------------------------------------------------

    public function test_fallback_alle_applies_when_no_specific_frist(): void {
        // Keine WWI-Frist, nur 'alle' (vergangen) -> greift -> verspätet.
        $this->create_frist('alle', '2023', self::DEADLINE_PAST);
        $entryid = $this->create_entry('WWI', 'WWI23B2', self::ENTRY_TIME);

        $this->assertSame(0, $this->compute($entryid));
    }

    public function test_specific_studiengang_takes_precedence_over_alle(): void {
        // 'alle' wäre vergangen (verspätet), spezifische WWI-Frist ist aber
        // in der Zukunft -> spezifische hat Vorrang -> innerhalb der Frist.
        $this->create_frist('alle', '2023', self::DEADLINE_PAST);
        $this->create_frist('WWI', '2023', self::DEADLINE_FUTURE);
        $entryid = $this->create_entry('WWI', 'WWI23B2', self::ENTRY_TIME);

        $this->assertSame(1, $this->compute($entryid));
    }

    // -------------------------------------------------------------------------
    // AC: Fehlerhandling – ohne passende Frist nicht fälschlich als verspätet.
    // -------------------------------------------------------------------------

    public function test_no_matching_frist_is_not_late(): void {
        // Gar keine Frist angelegt.
        $entryid = $this->create_entry('WWI', 'WWI23B2', self::ENTRY_TIME);

        $this->assertSame(1, $this->compute($entryid));
    }

    public function test_nonexistent_entry_is_not_late(): void {
        $this->create_frist('WWI', '2023', self::DEADLINE_PAST);

        $this->assertSame(1, $this->compute(999999));
    }

    public function test_missing_studiengang_content_is_not_late(): void {
        // Kein Studiengang-Inhalt -> keine Zuordnung möglich -> nicht verspätet,
        // trotz vergangener Frist.
        $this->create_frist('alle', '2023', self::DEADLINE_PAST);
        $entryid = $this->create_entry(null, 'WWI23B2', self::ENTRY_TIME);

        $this->assertSame(1, $this->compute($entryid));
    }
}
