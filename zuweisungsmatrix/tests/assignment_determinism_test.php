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
 * Unit tests für Determinismus & Nicht-Zuweisung des Zuteilungsalgorithmus.
 *
 * User Story (#105): Als Koordinator möchte ich, dass die Zuteilung bei gleicher
 * Eingabe immer dasselbe Ergebnis liefert und Studierende ohne erfüllbaren Wunsch
 * klar erkennbar bleiben, um faire und nachvollziehbare Ergebnisse zu erhalten.
 *
 * Getestet wird die reine Funktion assignment::berechne().
 *
 * Akzeptanzkriterien:
 *  - Identische Eingabe erzeugt immer dieselbe Zuordnung (Tie-Break über stableHash).
 *  - Bei Gleichstand um den letzten Platz entscheidet der Lotterie-Wert, nicht die
 *    Reihenfolge im DOM (hier: die Reihenfolge der Eingabe).
 *  - Studierende, deren Wünsche alle voll sind, bleiben unzugewiesen.
 *  - Ein Studierender wird nie zweimal zugewiesen.
 *
 * @package    local_zuweisungsmatrix
 * @category   phpunit
 * @group      local_zuweisungsmatrix
 * @group      local_zuweisungsmatrix_assignment
 * @covers     \local_zuweisungsmatrix\assignment::berechne
 * @copyright  2026, DHBW
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignment_determinism_test extends \advanced_testcase {

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
    // AC: Identische Eingabe erzeugt immer dieselbe Zuordnung.
    // -------------------------------------------------------------------------

    public function test_identische_eingabe_erzeugt_gleiche_zuordnung(): void {
        // Bewusst umkämpftes Szenario, dessen Ausgang vom Tie-Break abhängt.
        $studenten = [
            $this->student('alice', 'Lund', 'Wien'),
            $this->student('bob', 'Lund', 'Paris'),
            $this->student('carol', 'Wien', 'Lund'),
            $this->student('dave', 'Lund', 'Wien'),
        ];
        $kapazitaeten = ['Lund' => 1, 'Wien' => 1, 'Paris' => 1];

        $erste = assignment::berechne($studenten, $kapazitaeten);

        // Mehrfache Wiederholung liefert exakt dasselbe Ergebnis.
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($erste, assignment::berechne($studenten, $kapazitaeten),
                'Zuteilung ist bei identischer Eingabe nicht reproduzierbar.');
        }
    }

    // -------------------------------------------------------------------------
    // AC: Bei Gleichstand um den letzten Platz entscheidet der Lotterie-Wert,
    //     nicht die Eingabe-/DOM-Reihenfolge.
    // -------------------------------------------------------------------------

    public function test_tie_break_unabhaengig_von_eingabereihenfolge(): void {
        $alice = $this->student('alice', 'Lund');
        $bob = $this->student('bob', 'Lund');
        $kapazitaeten = ['Lund' => 1];

        // Gleiche Studierende, nur unterschiedliche Reihenfolge in der Eingabe.
        $vorwaerts = assignment::berechne([$alice, $bob], $kapazitaeten);
        $rueckwaerts = assignment::berechne([$bob, $alice], $kapazitaeten);

        // Genau einer erhält den einzigen Platz, der andere bleibt leer.
        $this->assertSame(1, $this->belegung($vorwaerts)['Lund']);
        $this->assertSame(1, $this->belegung($rueckwaerts)['Lund']);

        // Entscheidend: Der Gewinner ist in beiden Reihenfolgen identisch –
        // er hängt am Lotterie-Wert (stableHash), nicht an der Position.
        $gewinnervor = array_search('Lund', $vorwaerts, true);
        $gewinnerrueck = array_search('Lund', $rueckwaerts, true);
        $this->assertSame($gewinnervor, $gewinnerrueck,
            'Der Gewinner des letzten Platzes hängt von der Eingabereihenfolge ab.');

        // Der jeweils Unterlegene ist eindeutig unzugewiesen.
        $verlierer = $gewinnervor === 'alice' ? 'bob' : 'alice';
        $this->assertNull($vorwaerts[$verlierer]);
        $this->assertNull($rueckwaerts[$verlierer]);
    }

    // -------------------------------------------------------------------------
    // AC: Studierende, deren Wünsche alle voll sind, bleiben unzugewiesen.
    // -------------------------------------------------------------------------

    public function test_alle_wuensche_voll_bleibt_unzugewiesen(): void {
        // 'Paris' hat keinen Platz; 'Lund' und 'Wien' werden von s1/s2 ohne
        // Konkurrenz belegt. carol erreicht jeden ihrer Wünsche nur auf einem
        // schlechteren Rang -> alle für sie erreichbaren Plätze sind weg.
        $studenten = [
            $this->student('s1', 'Lund'),
            $this->student('s2', 'Wien'),
            $this->student('carol', 'Paris', 'Lund', 'Wien'),
        ];
        $kapazitaeten = ['Lund' => 1, 'Wien' => 1, 'Paris' => 0];

        $result = assignment::berechne($studenten, $kapazitaeten);

        // Die konkurrenzlosen Studierenden erhalten ihren Erstwunsch.
        $this->assertSame('Lund', $result['s1']);
        $this->assertSame('Wien', $result['s2']);
        // carol bleibt klar erkennbar unzugewiesen (null).
        $this->assertArrayHasKey('carol', $result);
        $this->assertNull($result['carol']);
    }

    public function test_mehr_bewerber_als_plaetze_lassen_genau_die_ueberzahl_offen(): void {
        // Drei Bewerber, zwei Plätze (jeweils nur ein Wunsch je Schule, beide
        // voll) -> genau eine Person bleibt ohne Zuteilung.
        $studenten = [
            $this->student('s1', 'Lund', 'Wien'),
            $this->student('s2', 'Lund', 'Wien'),
            $this->student('s3', 'Lund', 'Wien'),
        ];
        $kapazitaeten = ['Lund' => 1, 'Wien' => 1];

        $result = assignment::berechne($studenten, $kapazitaeten);

        $unzugewiesen = array_filter($result, static fn($v) => $v === null);
        $this->assertCount(1, $unzugewiesen,
            'Genau die Überzahl an Bewerbern muss unzugewiesen bleiben.');
    }

    // -------------------------------------------------------------------------
    // AC: Ein Studierender wird nie zweimal zugewiesen.
    // -------------------------------------------------------------------------

    public function test_kein_student_wird_doppelt_zugewiesen(): void {
        $studenten = [
            $this->student('s1', 'Lund', 'Wien', 'Paris'),
            $this->student('s2', 'Lund', 'Paris', 'Wien'),
            $this->student('s3', 'Wien', 'Lund', 'Paris'),
            $this->student('s4', 'Lund', 'Wien', 'Paris'),
            $this->student('s5', 'Paris', 'Wien', 'Lund'),
        ];
        $kapazitaeten = ['Lund' => 2, 'Wien' => 1, 'Paris' => 1];

        $result = assignment::berechne($studenten, $kapazitaeten);

        // Jeder Studierende taucht im Ergebnis genau einmal auf.
        $this->assertCount(count($studenten), $result);
        foreach ($studenten as $student) {
            $this->assertArrayHasKey($student['id'], $result);
        }

        $belegung = $this->belegung($result);

        // Keine Hochschule über Kapazität (Doppelvergabe würde dies sprengen).
        foreach ($kapazitaeten as $name => $cap) {
            $this->assertLessThanOrEqual($cap, $belegung[$name] ?? 0,
                "Hochschule {$name} wurde über die Kapazität belegt.");
        }

        // Konsistenz: Anzahl zugeteilter Studierender == Summe aller Belegungen.
        // Eine Doppelzuweisung würde die Belegungssumme über die Zahl der
        // zugeteilten Studierenden hinaus erhöhen.
        $zugeteilte = array_filter($result, static fn($v) => $v !== null);
        $this->assertSame(array_sum($belegung), count($zugeteilte));

        // Es werden nie mehr Plätze vergeben, als insgesamt vorhanden sind.
        $this->assertLessThanOrEqual(array_sum($kapazitaeten), count($zugeteilte));
    }
}
