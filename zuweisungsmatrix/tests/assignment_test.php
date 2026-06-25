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
 * Unit tests für den automatischen Zuteilungsalgorithmus (Priorität & Kapazität).
 *
 * User Story (#106): Als Koordinator möchte ich, dass der Zuteilungsalgorithmus
 * Studierende auf ihren höchstmöglichen Wunsch verteilt, ohne Kapazitäten zu
 * überschreiten, um eine faire Vergabe sicherzustellen.
 *
 * Getestet wird die ausgelagerte, reine Funktion assignment::berechne()
 * (Eingabe: Studierende mit Wunschliste + Hochschul-Kapazitäten,
 *  Ausgabe: Zuordnung Studierende => Hochschule).
 *
 * Akzeptanzkriterien:
 *  - Bei freier Kapazität erhält jeder Studierende seinen Erstwunsch.
 *  - Ist ein Wunsch voll, rückt der Studierende auf den nächsten verfügbaren Wunsch.
 *  - Keine Hochschule wird über ihre Kapazität hinaus belegt.
 *  - Wünsche auf nicht existierende Hochschulen werden ignoriert.
 *
 * @package    local_zuweisungsmatrix
 * @category   phpunit
 * @group      local_zuweisungsmatrix
 * @group      local_zuweisungsmatrix_assignment
 * @covers     \local_zuweisungsmatrix\assignment::berechne
 * @copyright  2026, DHBW
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignment_test extends \advanced_testcase {

    /**
     * Baut einen Studierenden-Eintrag im Eingabeformat von assignment::berechne().
     *
     * @param string $id Eindeutige ID des Studierenden.
     * @param string ...$wuensche Hochschulnamen nach Priorität (Erstwunsch zuerst).
     * @return array{id: string, wuensche: string[]}
     */
    private function student(string $id, string ...$wuensche): array {
        return ['id' => $id, 'wuensche' => $wuensche];
    }

    /**
     * Zählt, wie oft jede Hochschule in einer Zuordnung belegt wurde.
     *
     * @param array $zuordnung Ergebnis von assignment::berechne().
     * @return array<string,int> Hochschulname => Anzahl Zuteilungen.
     */
    private function belegung(array $zuordnung): array {
        $counts = [];
        foreach ($zuordnung as $hochschule) {
            if ($hochschule !== null) {
                $counts[$hochschule] = ($counts[$hochschule] ?? 0) + 1;
            }
        }
        return $counts;
    }

    // -------------------------------------------------------------------------
    // AC: Bei freier Kapazität erhält jeder Studierende seinen Erstwunsch.
    // -------------------------------------------------------------------------

    public function test_erstwunsch_bei_freier_kapazitaet(): void {
        $studenten = [
            $this->student('s1', 'Lund', 'Wien'),
            $this->student('s2', 'Wien', 'Lund'),
            $this->student('s3', 'Lund', 'Wien'),
        ];
        $kapazitaeten = ['Lund' => 5, 'Wien' => 5];

        $result = assignment::berechne($studenten, $kapazitaeten);

        // Jeder bekommt exakt seinen Erstwunsch.
        $this->assertSame('Lund', $result['s1']);
        $this->assertSame('Wien', $result['s2']);
        $this->assertSame('Lund', $result['s3']);
    }

    // -------------------------------------------------------------------------
    // AC: Ist ein Wunsch voll, rückt der Studierende auf den nächsten Wunsch.
    // -------------------------------------------------------------------------

    public function test_rueckt_auf_naechsten_wunsch_wenn_erstwunsch_ohne_platz(): void {
        // 'Lund' hat keinen Platz -> der Studierende muss auf den Zweitwunsch.
        $studenten = [
            $this->student('s1', 'Lund', 'Wien'),
        ];
        $kapazitaeten = ['Lund' => 0, 'Wien' => 1];

        $result = assignment::berechne($studenten, $kapazitaeten);

        $this->assertSame('Wien', $result['s1']);
    }

    public function test_rueckt_auf_naechsten_wunsch_wenn_erstwunsch_durch_andere_voll(): void {
        // Beide wollen 'Lund' (Kapazität 1) zuerst. Genau einer bekommt 'Lund',
        // der andere rückt auf seinen jeweiligen Zweitwunsch. Wer den Zuschlag
        // erhält, entscheidet der deterministische Loswert – das Ergebnis ist
        // aber in jedem Fall AC-konform.
        $studenten = [
            $this->student('s1', 'Lund', 'Wien'),
            $this->student('s2', 'Lund', 'Paris'),
        ];
        $kapazitaeten = ['Lund' => 1, 'Wien' => 1, 'Paris' => 1];

        $result = assignment::berechne($studenten, $kapazitaeten);

        // Genau ein Studierender erhält den umkämpften Erstwunsch.
        $this->assertSame(1, $this->belegung($result)['Lund']);
        // Beide sind zugeteilt (niemand bleibt übrig).
        $this->assertNotNull($result['s1']);
        $this->assertNotNull($result['s2']);
        // Der unterlegene Studierende rückt auf seinen eigenen Zweitwunsch.
        if ($result['s1'] === 'Lund') {
            $this->assertSame('Paris', $result['s2']);
        } else {
            $this->assertSame('Wien', $result['s1']);
            $this->assertSame('Lund', $result['s2']);
        }
    }

    // -------------------------------------------------------------------------
    // AC: Keine Hochschule wird über ihre Kapazität hinaus belegt.
    // -------------------------------------------------------------------------

    public function test_kapazitaet_wird_nie_ueberschritten(): void {
        // Fünf Studierende, alle wollen ausschließlich 'Lund' (Kapazität 2).
        $studenten = [
            $this->student('s1', 'Lund'),
            $this->student('s2', 'Lund'),
            $this->student('s3', 'Lund'),
            $this->student('s4', 'Lund'),
            $this->student('s5', 'Lund'),
        ];
        $kapazitaeten = ['Lund' => 2];

        $result = assignment::berechne($studenten, $kapazitaeten);

        $belegung = $this->belegung($result);

        // Exakt die Kapazität wird vergeben – nicht mehr.
        $this->assertSame(2, $belegung['Lund']);
        $this->assertLessThanOrEqual($kapazitaeten['Lund'], $belegung['Lund']);

        // Die restlichen drei bleiben unzugeteilt (null), tauchen aber im
        // Ergebnis auf.
        $nichtzugeteilt = array_filter($result, static fn($v) => $v === null);
        $this->assertCount(3, $nichtzugeteilt);
    }

    public function test_keine_hochschule_ueber_kapazitaet_bei_mehreren(): void {
        // Drei Studierende konkurrieren um zwei knappe Hochschulen.
        $studenten = [
            $this->student('s1', 'Lund', 'Wien'),
            $this->student('s2', 'Lund', 'Wien'),
            $this->student('s3', 'Lund', 'Wien'),
        ];
        $kapazitaeten = ['Lund' => 1, 'Wien' => 1];

        $result = assignment::berechne($studenten, $kapazitaeten);

        $belegung = $this->belegung($result);

        // Keine Hochschule überschreitet ihre Kapazität.
        foreach ($kapazitaeten as $name => $cap) {
            $this->assertLessThanOrEqual($cap, $belegung[$name] ?? 0,
                "Hochschule {$name} wurde über die Kapazität belegt.");
        }
        // Zwei Plätze, drei Bewerber -> genau einer bleibt übrig.
        $this->assertCount(1, array_filter($result, static fn($v) => $v === null));
    }

    // -------------------------------------------------------------------------
    // AC: Wünsche auf nicht existierende Hochschulen werden ignoriert.
    // -------------------------------------------------------------------------

    public function test_nicht_existierende_hochschule_wird_ignoriert(): void {
        // 'Atlantis' existiert nicht (kein Kapazitäts-Eintrag) und wird
        // übersprungen; der Studierende erhält seinen nächsten gültigen Wunsch.
        $studenten = [
            $this->student('s1', 'Atlantis', 'Lund'),
        ];
        $kapazitaeten = ['Lund' => 1];

        $result = assignment::berechne($studenten, $kapazitaeten);

        $this->assertSame('Lund', $result['s1']);
        // Die Phantom-Hochschule taucht nirgends als Zuteilung auf.
        $this->assertNotContains('Atlantis', $result);
    }

    public function test_nur_nicht_existierende_wuensche_bleiben_unzugeteilt(): void {
        // Enthält die Wunschliste ausschließlich unbekannte Hochschulen, bleibt
        // der Studierende unzugeteilt (null) – kein Phantom-Platz wird erfunden.
        $studenten = [
            $this->student('s1', 'Atlantis', 'Camelot'),
        ];
        $kapazitaeten = ['Lund' => 3];

        $result = assignment::berechne($studenten, $kapazitaeten);

        $this->assertArrayHasKey('s1', $result);
        $this->assertNull($result['s1']);
    }
}
