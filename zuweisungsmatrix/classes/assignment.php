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
 * Reine Berechnungslogik des automatischen Zuteilungsalgorithmus.
 *
 * Diese Klasse kapselt die Logik aus der DOM-gebundenen JavaScript-Funktion
 * automatischZuteilen() (scripts.js) als reine, testbare Funktion:
 *
 *   Eingabe:  Studierende mit Wunschliste + Hochschul-Kapazitäten
 *   Ausgabe:  Zuordnung Studierende => Hochschule (oder null = unzugeteilt)
 *
 * Der Algorithmus bildet pro Studierendem für jeden gültigen Wunsch ein
 * "Angebot" mit Kosten = Rang des Wunsches (0 = Erstwunsch) plus einen
 * deterministischen, sehr kleinen Loswert zur fairen Auflösung von
 * Gleichständen. Alle Angebote werden nach Kosten aufsteigend sortiert und
 * gierig vergeben, solange die Kapazität der Hochschule nicht überschritten
 * ist. So erhält jeder Studierende den günstigsten (= höchsten) noch
 * verfügbaren Wunsch.
 *
 * @package    local_zuweisungsmatrix
 * @copyright  2026, DHBW
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment {

    /** @var int Kosten einer Nicht-Zuteilung – teurer als jeder reale Wunsch-Rang. */
    private const NON_ASSIGN_COST = 1000000;

    /** @var float Skalierung des Loswerts; klein genug, um Wunsch-Ränge nie zu überholen. */
    private const TIE_SCALE = 0.000001;

    /** @var string Standard-Bewerbungsrunde; fließt in den Loswert ein. */
    public const DEFAULT_ROUND_ID = 'bewerbungsrunde-1';

    /**
     * Berechnet die Zuteilung von Studierenden auf Hochschulen.
     *
     * @param array $studenten Liste von Studierenden. Jeder Eintrag:
     *                         ['id' => string|int, 'wuensche' => string[]].
     *                         'wuensche' ist die nach Priorität geordnete Liste
     *                         von Hochschulnamen (Erstwunsch zuerst).
     * @param array $kapazitaeten Map Hochschulname => Anzahl freier Plätze (int).
     *                            Nur hier vorhandene Namen gelten als existierende
     *                            Hochschulen.
     * @param string $roundid Bezeichner der Bewerbungsrunde (für den Loswert).
     * @return array Map Studierenden-ID => Hochschulname oder null (unzugeteilt).
     */
    public static function berechne(array $studenten, array $kapazitaeten,
            string $roundid = self::DEFAULT_ROUND_ID): array {

        $angebote = [];

        foreach ($studenten as $index => $student) {
            $id = (string) ($student['id'] ?? $index);
            $lottery = self::stable_hash($id . '|' . $roundid);
            $wuensche = $student['wuensche'] ?? [];

            // Pro Wunsch ein Angebot bilden. Der Rang entspricht der Position in
            // der Wunschliste; nicht existierende Hochschulen erzeugen kein
            // Angebot, "verbrauchen" aber – wie im JS-Original – ihre Rangstelle.
            foreach (array_values($wuensche) as $rang => $hochschule) {
                if (array_key_exists($hochschule, $kapazitaeten)) {
                    $angebote[] = [
                        'student'    => $id,
                        'hochschule' => $hochschule,
                        'cost'       => $rang + $lottery * self::TIE_SCALE,
                    ];
                }
            }

            // Auffang-Angebot: keine Zuteilung. Garantiert, dass jeder
            // Studierende im Ergebnis auftaucht (als null, falls kein Wunsch
            // bedient werden konnte).
            $angebote[] = [
                'student'    => $id,
                'hochschule' => null,
                'cost'       => self::NON_ASSIGN_COST + $lottery * self::TIE_SCALE,
            ];
        }

        // Günstigste (höchste) Wünsche zuerst.
        usort($angebote, static function (array $a, array $b): int {
            return $a['cost'] <=> $b['cost'];
        });

        $zuordnung = [];
        $belegung = [];
        foreach (array_keys($kapazitaeten) as $name) {
            $belegung[$name] = 0;
        }

        foreach ($angebote as $angebot) {
            $sid = $angebot['student'];

            // Studierende, die bereits (auch als null) entschieden sind, überspringen.
            if (array_key_exists($sid, $zuordnung)) {
                continue;
            }

            // Nicht-Zuteilungs-Angebot: endgültig unzugeteilt.
            if ($angebot['hochschule'] === null) {
                $zuordnung[$sid] = null;
                continue;
            }

            // Wunsch nur vergeben, wenn die Hochschule noch Kapazität hat.
            $hochschule = $angebot['hochschule'];
            if ($belegung[$hochschule] < $kapazitaeten[$hochschule]) {
                $zuordnung[$sid] = $hochschule;
                $belegung[$hochschule]++;
            }
        }

        return $zuordnung;
    }

    /**
     * Deterministischer 32-Bit-FNV-1a-Hash, normiert auf [0, 1].
     *
     * Identische Implementierung wie stableHash() in scripts.js, damit der
     * Loswert (Tie-Break) reproduzierbar und faithful zum Frontend bleibt.
     *
     * @param string $str Eingabe (z.B. "studentid|bewerbungsrunde-1").
     * @return float Wert im Intervall [0, 1].
     */
    private static function stable_hash(string $str): float {
        $h = 2166136261;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $h ^= ord($str[$i]);
            $h &= 0xFFFFFFFF;
            // 32-Bit-Multiplikation (entspricht Math.imul in JS).
            $h = ($h * 16777619) & 0xFFFFFFFF;
        }
        return $h / 4294967295;
    }
}
