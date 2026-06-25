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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/dhbwio/locallib.php');

/**
 * Unit tests for editing an existing Learning Agreement.
 *
 * User Story: Als Studierender möchte ich mein bestehendes Learning Agreement
 * nachträglich anpassen, damit Änderungen an Modulen und Stammdaten übernommen
 * werden.
 *
 * Getestet wird die von Perrin geänderte Funktion
 * dhbwio_update_la_content_and_modules() (locallib.php).
 *
 * @package    mod_dhbwio
 * @category   phpunit
 * @group      mod_dhbwio
 * @group      mod_dhbwio_learning_agreement
 * @covers     ::dhbwio_update_la_content_and_modules
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class learning_agreement_update_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    // -------------------------------------------------------------------------
    // Test-Helfer
    // -------------------------------------------------------------------------

    /**
     * Liefert einen vollständigen Stammdaten-Satz für ein LA.
     *
     * @param array $overrides Zu überschreibende Felder.
     * @return array
     */
    private function sample_content(array $overrides = []): array {
        return array_merge([
            'name'            => 'Mustermann',
            'vorname'         => 'Max',
            'studiengang'     => 'Wirtschaftsinformatik',
            'studienrichtung' => 'Software Engineering',
            'wahlmodul'       => 'Künstliche Intelligenz',
            'gasthochschule'  => 'Universität Wien',
            'zeitraum_von'    => 1000,
            'zeitraum_bis'    => 2000,
        ], $overrides);
    }

    /**
     * Liefert einen vollständigen Modul-Datensatz.
     *
     * @param string $name Modulname.
     * @param array $overrides Zu überschreibende Felder.
     * @return array
     */
    private function sample_module(string $name, array $overrides = []): array {
        return array_merge([
            'modul_name'          => $name,
            'ects'                => '5',
            'teilpruefungsanteil' => '50%',
            'anrechnungs_lv'      => 'Lehrveranstaltung',
            'credits'             => '6',
        ], $overrides);
    }

    /**
     * Legt ein vollständiges LA an und liefert la_id + contentid.
     *
     * @param int $userid Eigentümer.
     * @param array $content Stammdaten.
     * @param array $modules Module.
     * @return \stdClass {laid, contentid}
     */
    private function create_la(int $userid, array $content, array $modules): \stdClass {
        $laid = dhbwio_create_full_la($userid, $content, $modules);
        $full = dhbwio_get_full_la_by_la_id($laid);
        return (object) [
            'laid'      => (int) $laid,
            'contentid' => (int) $full->content->id,
        ];
    }

    // -------------------------------------------------------------------------
    // AC: Inhalt – Geänderte Stammdaten überschreiben die alten Werte.
    // -------------------------------------------------------------------------

    public function test_update_overwrites_master_data(): void {
        $student = $this->getDataGenerator()->create_user();
        $la = $this->create_la(
            $student->id,
            $this->sample_content(),
            [$this->sample_module('Altes Modul')]
        );

        dhbwio_update_la_content_and_modules(
            $la->contentid,
            $this->sample_content([
                'name'           => 'Neumann',
                'vorname'        => 'Erika',
                'studiengang'    => 'BWL',
                'gasthochschule' => 'Universität Zürich',
            ]),
            [$this->sample_module('Neues Modul')]
        );

        $full = dhbwio_get_full_la_by_contentid($la->contentid);

        $this->assertSame('Neumann', $full->content->name);
        $this->assertSame('Erika', $full->content->vorname);
        $this->assertSame('BWL', $full->content->studiengang);
        $this->assertSame('Universität Zürich', $full->content->gasthochschule);
        // Nicht überschriebene Felder bleiben erhalten.
        $this->assertSame('Software Engineering', $full->content->studienrichtung);
    }

    // -------------------------------------------------------------------------
    // AC: Module – Alte Liste wird vollständig ersetzt, keine Waisen.
    // -------------------------------------------------------------------------

    public function test_update_replaces_all_modules(): void {
        global $DB;

        $student = $this->getDataGenerator()->create_user();
        $la = $this->create_la(
            $student->id,
            $this->sample_content(),
            [
                $this->sample_module('Modul A'),
                $this->sample_module('Modul B'),
                $this->sample_module('Modul C'),
            ]
        );

        $this->assertSame(3, $DB->count_records('dhbwio_la_module', ['la_contents_id' => $la->contentid]));

        dhbwio_update_la_content_and_modules(
            $la->contentid,
            $this->sample_content(),
            [
                $this->sample_module('Modul X'),
                $this->sample_module('Modul Y'),
            ]
        );

        $full = dhbwio_get_full_la_by_contentid($la->contentid);
        $names = array_map(static fn($m) => $m->modul_name, $full->modules);

        // Genau die neuen Module, keine verwaisten Reste.
        $this->assertCount(2, $full->modules);
        $this->assertEqualsCanonicalizing(['Modul X', 'Modul Y'], $names);
        $this->assertNotContains('Modul A', $names);
        $this->assertNotContains('Modul B', $names);
        $this->assertNotContains('Modul C', $names);

        // Auch auf DB-Ebene keine Waisen für diesen contentid.
        $this->assertSame(2, $DB->count_records('dhbwio_la_module', ['la_contents_id' => $la->contentid]));
    }

    // -------------------------------------------------------------------------
    // AC: Nachverfolgung – lasteditedby und timemodified werden aktualisiert.
    // -------------------------------------------------------------------------

    public function test_update_tracks_editor_and_timestamp(): void {
        global $DB;

        $student = $this->getDataGenerator()->create_user();
        $editor  = $this->getDataGenerator()->create_user();

        $la = $this->create_la(
            $student->id,
            $this->sample_content(),
            [$this->sample_module('Modul A')]
        );

        // Ausgangszustand künstlich „alt" setzen, damit die Aktualisierung messbar ist.
        $DB->set_field('dhbwio_la', 'timemodified', 100, ['id' => $la->laid]);
        $DB->set_field('dhbwio_la', 'lasteditedby', $student->id, ['id' => $la->laid]);

        $before = time();
        $this->setUser($editor);

        dhbwio_update_la_content_and_modules($la->contentid, $this->sample_content(), []);

        $meta = $DB->get_record('dhbwio_la', ['id' => $la->laid]);

        // Bearbeiter ist jetzt der editierende Nutzer, nicht mehr der Student.
        $this->assertSame((int) $editor->id, (int) $meta->lasteditedby);
        $this->assertNotSame((int) $student->id, (int) $meta->lasteditedby);

        // Änderungszeitpunkt wurde aktualisiert.
        $this->assertNotEquals(100, (int) $meta->timemodified);
        $this->assertGreaterThanOrEqual($before, (int) $meta->timemodified);
    }

    // -------------------------------------------------------------------------
    // AC: Fehlerhandling – Nicht existierende LA-ID ist ungültig.
    // -------------------------------------------------------------------------

    public function test_update_of_unknown_id_is_invalid(): void {
        global $DB;

        $nonexistentcontentid = 999999;
        $caught = false;

        try {
            dhbwio_update_la_content_and_modules(
                $nonexistentcontentid,
                $this->sample_content(),
                [$this->sample_module('Modul A')]
            );
        } catch (\dml_missing_record_exception $e) {
            $caught = true;
        } finally {
            // Die Funktion öffnet vor dem MUST_EXIST eine Transaktion – sauber schließen.
            if ($DB->is_transaction_started()) {
                $DB->force_transaction_rollback();
            }
        }

        $this->assertTrue($caught, 'Bearbeitung einer unbekannten LA-ID muss als ungültig fehlschlagen.');
    }
}
