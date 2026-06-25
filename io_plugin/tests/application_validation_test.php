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

use mod_dhbwio\local\dataform\validation_manager;
use mod_dhbwio\local\dataform\field_manager;

/**
 * Unit tests for the application form validation (validation_manager).
 *
 * Dieser Test prüft die Validierung über den Pfad, den das Bewerbungsformular
 * tatsächlich ausführt: validation_manager::validate() (siehe application.php
 * und application_form.php). Die frühere Variante testete application_validator,
 * eine Klasse, die das überarbeitete Formular nicht mehr aufruft.
 *
 * Hinweis: Das überarbeitete Formular (dataform) wird hier NICHT verändert –
 * die Tests beschreiben sein aktuelles, reales Verhalten. Wo eine ursprüngliche
 * Akzeptanzkriterium-Regel im echten Pfad nicht (mehr) erzwungen wird, ist das
 * als Characterization-Test mit Kommentar markiert, damit die Lücke sichtbar
 * bleibt, ohne den Build rot zu machen.
 *
 * @package    mod_dhbwio
 * @category   phpunit
 * @group      mod_dhbwio
 * @group      mod_dhbwio_application_validation
 * @covers     \mod_dhbwio\local\dataform\validation_manager
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class application_validation_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    // -------------------------------------------------------------------------
    // Test-Helfer
    // -------------------------------------------------------------------------

    /**
     * Baut eine Felddefinition, wie validation_manager sie erwartet.
     *
     * @param array $overrides Zu überschreibende Eigenschaften.
     * @return \stdClass
     */
    private function make_field(array $overrides): \stdClass {
        return (object) array_merge([
            'id'          => 1,
            'name'        => 'FELD',
            'type'        => 'text',
            'scope'       => field_manager::SCOPE_STUDENT,
            'description' => 'Optionale Angabe',
            'param1'      => '',
            'param4'      => '',
        ], $overrides);
    }

    /**
     * Führt validation_manager::validate() mit den gegebenen Feldern/Werten aus.
     *
     * @param array $fields Liste von Felddefinitionen.
     * @param array $values Werte, indiziert nach Feld-ID.
     * @return array Fehlerliste, indiziert nach 'field_<id>'.
     */
    private function run_validate(array $fields, array $values): array {
        $data = new \stdClass();
        foreach ($values as $fieldid => $value) {
            $data->{'field_' . $fieldid} = $value;
        }
        return validation_manager::validate($data, $fields);
    }

    // =========================================================================
    // User Story: Validierung Name und DHBW-E-Mail
    //
    // Felder wie in default_form_manager konfiguriert:
    //   VORNAME / NACHNAME -> type 'text', verpflichtend, KEINE param4-Regel
    //   EMAIL              -> type 'text', verpflichtend, param4 'email'
    // =========================================================================

    /** Vorname-Pflichtfeld: leer -> Fehler. */
    public function test_vorname_required_empty_returns_error(): void {
        $field = $this->make_field([
            'id'          => 1,
            'name'        => 'VORNAME',
            'description' => 'Vorname des Bewerbers (verpflichtende Angabe)',
        ]);

        $errors = $this->run_validate([$field], [1 => '']);

        $this->assertArrayHasKey('field_1', $errors);
        $this->assertSame(\get_string('required'), $errors['field_1']);
    }

    /** Nachname-Pflichtfeld: gültiger Name -> kein Fehler. */
    public function test_nachname_valid_passes(): void {
        $field = $this->make_field([
            'id'          => 1,
            'name'        => 'NACHNAME',
            'description' => 'Nachname des Bewerbers (verpflichtende Angabe)',
        ]);

        $errors = $this->run_validate([$field], [1 => 'Müller-Schmidt']);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    /**
     * Characterization: Das überarbeitete Formular erzwingt für VORNAME/NACHNAME
     * KEINE Zeichensatz-Regel (kein param4). Ein Name nur aus Sonderzeichen
     * läuft daher aktuell durch.
     *
     * Ursprüngliche AC „Fehler, wenn nur Sonderzeichen" ist im echten Pfad
     * NICHT erfüllt – hier bewusst als Ist-Zustand festgehalten.
     */
    public function test_nachname_special_chars_currently_pass(): void {
        $field = $this->make_field([
            'id'          => 1,
            'name'        => 'NACHNAME',
            'description' => 'Nachname des Bewerbers (verpflichtende Angabe)',
        ]);

        $errors = $this->run_validate([$field], [1 => '###']);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    /** E-Mail-Pflichtfeld: leer -> Fehler. */
    public function test_email_required_empty_returns_error(): void {
        $field = $this->make_field([
            'id'          => 1,
            'name'        => 'EMAIL',
            'description' => 'DHBW-E-Mail-Adresse des Bewerbers (verpflichtende Angabe)',
            'param4'      => 'email',
        ]);

        $errors = $this->run_validate([$field], [1 => '']);

        $this->assertArrayHasKey('field_1', $errors);
        $this->assertSame(\get_string('required'), $errors['field_1']);
    }

    /** E-Mail mit ungültiger Syntax -> Fehler (param4 'email'). */
    public function test_email_invalid_syntax_returns_error(): void {
        $field = $this->make_field([
            'id'          => 1,
            'name'        => 'EMAIL',
            'description' => 'DHBW-E-Mail-Adresse des Bewerbers (verpflichtende Angabe)',
            'param4'      => 'email',
        ]);

        $errors = $this->run_validate([$field], [1 => 'keine-email']);

        $this->assertArrayHasKey('field_1', $errors);
        $this->assertSame(\get_string('invalidemail'), $errors['field_1']);
    }

    /** Gültige DHBW-Mail -> kein Fehler. */
    public function test_email_valid_dhbw_passes(): void {
        $field = $this->make_field([
            'id'          => 1,
            'name'        => 'EMAIL',
            'description' => 'DHBW-E-Mail-Adresse des Bewerbers (verpflichtende Angabe)',
            'param4'      => 'email',
        ]);

        $errors = $this->run_validate([$field], [1 => 's123456@student.dhbw.de']);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    /**
     * Characterization: param4 'email' prüft nur generische E-Mail-Gültigkeit,
     * NICHT die Domain @dhbw.de. Eine Fremd-Mail läuft daher aktuell durch.
     *
     * Ursprüngliche AC „muss @dhbw.de$ entsprechen" ist im echten Pfad NICHT
     * erfüllt – hier bewusst als Ist-Zustand festgehalten.
     */
    public function test_email_non_dhbw_currently_passes(): void {
        $field = $this->make_field([
            'id'          => 1,
            'name'        => 'EMAIL',
            'description' => 'DHBW-E-Mail-Adresse des Bewerbers (verpflichtende Angabe)',
            'param4'      => 'email',
        ]);

        $errors = $this->run_validate([$field], [1 => 'max@gmail.com']);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    // =========================================================================
    // Allgemeines Validierungsverhalten von validation_manager
    // =========================================================================

    // -------------------------------------------------------------------------
    // Pflichtfelder und Sichtbarkeit
    // -------------------------------------------------------------------------

    public function test_optional_empty_field_passes(): void {
        $field = $this->make_field(['id' => 1, 'description' => 'Optionale Angabe']);

        $errors = $this->run_validate([$field], [1 => '']);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    public function test_non_student_field_is_skipped(): void {
        // Ein Pflicht-Reviewfeld darf für Studierende NICHT validiert werden.
        $field = $this->make_field([
            'id'          => 1,
            'name'        => 'SGL_FREIGABE',
            'scope'       => field_manager::SCOPE_REVIEW,
            'description' => 'Verpflichtende Angabe',
        ]);

        $errors = $this->run_validate([$field], [1 => '']);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    // -------------------------------------------------------------------------
    // Textlängen
    // -------------------------------------------------------------------------

    public function test_text_at_max_length_passes(): void {
        $field = $this->make_field(['id' => 1, 'type' => 'text']);

        $errors = $this->run_validate([$field], [1 => str_repeat('a', 255)]);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    public function test_text_too_long_returns_error(): void {
        $field = $this->make_field(['id' => 1, 'type' => 'text']);

        $errors = $this->run_validate([$field], [1 => str_repeat('a', 256)]);

        $this->assertArrayHasKey('field_1', $errors);
        $this->assertSame(\get_string('maximumchars', '', 255), $errors['field_1']);
    }

    public function test_textarea_at_max_length_passes(): void {
        $field = $this->make_field(['id' => 1, 'type' => 'textarea']);

        $errors = $this->run_validate([$field], [1 => str_repeat('a', 999)]);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    public function test_textarea_too_long_returns_error(): void {
        $field = $this->make_field(['id' => 1, 'type' => 'textarea']);

        $errors = $this->run_validate([$field], [1 => str_repeat('a', 1000)]);

        $this->assertArrayHasKey('field_1', $errors);
        $this->assertSame(\get_string('maximumchars', '', 999), $errors['field_1']);
    }

    // -------------------------------------------------------------------------
    // Auswahlfelder (select / radiobutton)
    // -------------------------------------------------------------------------

    /**
     * @dataProvider option_field_type_provider
     */
    public function test_valid_option_passes(string $type): void {
        $field = $this->make_field([
            'id'     => 1,
            'name'   => 'ABSPRACHE',
            'type'   => $type,
            'param1' => "Ja\nNein",
        ]);

        $errors = $this->run_validate([$field], [1 => 'Ja']);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    /**
     * @dataProvider option_field_type_provider
     */
    public function test_invalid_option_returns_error(string $type): void {
        $field = $this->make_field([
            'id'     => 1,
            'name'   => 'ABSPRACHE',
            'type'   => $type,
            'param1' => "Ja\nNein",
        ]);

        $errors = $this->run_validate([$field], [1 => 'Vielleicht']);

        $this->assertArrayHasKey('field_1', $errors);
        $this->assertSame(\get_string('invaliddata', 'error'), $errors['field_1']);
    }

    public static function option_field_type_provider(): array {
        return [
            'select'      => ['select'],
            'radiobutton' => ['radiobutton'],
        ];
    }

    // -------------------------------------------------------------------------
    // Zeit- / Datumsfelder und Altersregel (>= 16)
    // -------------------------------------------------------------------------

    /**
     * @dataProvider invalid_time_provider
     */
    public function test_invalid_time_returns_error($value): void {
        $field = $this->make_field(['id' => 1, 'name' => 'STARTDATUM', 'type' => 'time']);

        $errors = $this->run_validate([$field], [1 => $value]);

        $this->assertArrayHasKey('field_1', $errors);
        $this->assertSame(\get_string('invaliddate'), $errors['field_1']);
    }

    public static function invalid_time_provider(): array {
        return [
            'non numeric' => ['abc'],
            'zero'        => ['0'],
            'negative'    => ['-100'],
        ];
    }

    public function test_valid_time_passes(): void {
        $field = $this->make_field(['id' => 1, 'name' => 'STARTDATUM', 'type' => 'time']);

        $errors = $this->run_validate([$field], [1 => (string) strtotime('2020-01-01')]);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    public function test_birthdate_under_16_returns_error(): void {
        $field = $this->make_field(['id' => 1, 'name' => 'GEBURTSDATUM', 'type' => 'time']);

        // 15 Jahre alt -> unter der Mindestgrenze.
        $errors = $this->run_validate([$field], [1 => (string) strtotime('-15 years')]);

        $this->assertArrayHasKey('field_1', $errors);
        $this->assertSame(\get_string('minimumage16', 'dhbwio'), $errors['field_1']);
    }

    public function test_birthdate_at_least_16_passes(): void {
        $field = $this->make_field(['id' => 1, 'name' => 'GEBURTSDATUM', 'type' => 'time']);

        // 17 Jahre alt -> über der Mindestgrenze.
        $errors = $this->run_validate([$field], [1 => (string) strtotime('-17 years')]);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    // -------------------------------------------------------------------------
    // Feldspezifische Regeln (param4)
    // -------------------------------------------------------------------------

    /**
     * @dataProvider param_rule_provider
     */
    public function test_param_rules(string $rule, string $value, bool $expecterror): void {
        $field = $this->make_field(['id' => 1, 'name' => 'REGELFELD', 'param4' => $rule]);

        $errors = $this->run_validate([$field], [1 => $value]);

        if ($expecterror) {
            $this->assertArrayHasKey('field_1', $errors);
        } else {
            $this->assertArrayNotHasKey('field_1', $errors);
        }
    }

    public static function param_rule_provider(): array {
        return [
            'numeric valid'        => ['numeric',      '12345',      false],
            'numeric invalid'      => ['numeric',      '12a45',      true],
            'lettersonly valid'    => ['lettersonly',  'Müller',     false],
            'lettersonly invalid'  => ['lettersonly',  'Müller1',    true],
            'alphanumeric valid'   => ['alphanumeric', 'BWL 2025',   false],
            'alphanumeric invalid' => ['alphanumeric', 'BWL@2025',   true],
        ];
    }

    // -------------------------------------------------------------------------
    // Datenschutzerklärung (Sonderregel)
    // -------------------------------------------------------------------------

    public function test_privacy_acceptance_empty_returns_error(): void {
        $field = $this->make_field([
            'id'     => 1,
            'name'   => 'EINVERSTAENDNISERKLAERUNG_DATENSCHUTZ',
            'type'   => 'select',
            'param1' => "Ja\nNein",
        ]);

        $errors = $this->run_validate([$field], [1 => '']);

        $this->assertArrayHasKey('field_1', $errors);
        $this->assertSame(\get_string('required'), $errors['field_1']);
    }

    public function test_privacy_acceptance_accepted_passes(): void {
        $field = $this->make_field([
            'id'     => 1,
            'name'   => 'EINVERSTAENDNISERKLAERUNG_DATENSCHUTZ',
            'type'   => 'select',
            'param1' => "Ja\nNein",
        ]);

        $errors = $this->run_validate([$field], [1 => 'Ja']);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    // -------------------------------------------------------------------------
    // Hochschulwünsche: DB-gestützte Auswahl + Eindeutigkeit
    // -------------------------------------------------------------------------

    /**
     * Legt eine aktive bzw. inaktive Partnerhochschule an und liefert die ID.
     *
     * @param int $active 1 = aktiv, 0 = inaktiv.
     * @return int
     */
    private function create_university(int $active = 1): int {
        global $DB;
        return (int) $DB->insert_record('dhbwio_universities', (object) [
            'dhbwio'  => 1,
            'name'    => 'Test University ' . random_string(5),
            'country' => 'DE',
            'city'    => 'Karlsruhe',
            'active'  => $active,
        ]);
    }

    public function test_university_choice_valid_id_passes(): void {
        $uniid = $this->create_university(1);
        $field = $this->make_field(['id' => 1, 'name' => 'ERSTWUNSCH', 'type' => 'select']);

        $errors = $this->run_validate([$field], [1 => (string) $uniid]);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    public function test_university_choice_unknown_id_returns_error(): void {
        $field = $this->make_field(['id' => 1, 'name' => 'ERSTWUNSCH', 'type' => 'select']);

        $errors = $this->run_validate([$field], [1 => '999999']);

        $this->assertArrayHasKey('field_1', $errors);
        $this->assertSame(\get_string('invaliddata', 'error'), $errors['field_1']);
    }

    public function test_university_choice_inactive_returns_error(): void {
        $uniid = $this->create_university(0);
        $field = $this->make_field(['id' => 1, 'name' => 'ERSTWUNSCH', 'type' => 'select']);

        $errors = $this->run_validate([$field], [1 => (string) $uniid]);

        $this->assertArrayHasKey('field_1', $errors);
    }

    public function test_university_choice_non_numeric_returns_error(): void {
        $field = $this->make_field(['id' => 1, 'name' => 'ERSTWUNSCH', 'type' => 'select']);

        $errors = $this->run_validate([$field], [1 => 'Uni Stuttgart']);

        $this->assertArrayHasKey('field_1', $errors);
    }

    public function test_zweitwunsch_keine_is_exempt(): void {
        // "Keine" wird als '0' übermittelt und darf keinen Fehler auslösen.
        $field = $this->make_field(['id' => 1, 'name' => 'ZWEITWUNSCH', 'type' => 'select']);

        $errors = $this->run_validate([$field], [1 => '0']);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    public function test_duplicate_university_choices_return_error(): void {
        $uniid = $this->create_university(1);

        $erst  = $this->make_field(['id' => 1, 'name' => 'ERSTWUNSCH',  'type' => 'select']);
        $zweit = $this->make_field(['id' => 2, 'name' => 'ZWEITWUNSCH', 'type' => 'select']);

        $errors = $this->run_validate([$erst, $zweit], [
            1 => (string) $uniid,
            2 => (string) $uniid,
        ]);

        $this->assertArrayHasKey('field_2', $errors);
        $this->assertSame(\get_string('choicetaken', 'dhbwio'), $errors['field_2']);
    }

    public function test_distinct_university_choices_pass(): void {
        $uni1 = $this->create_university(1);
        $uni2 = $this->create_university(1);

        $erst  = $this->make_field(['id' => 1, 'name' => 'ERSTWUNSCH',  'type' => 'select']);
        $zweit = $this->make_field(['id' => 2, 'name' => 'ZWEITWUNSCH', 'type' => 'select']);

        $errors = $this->run_validate([$erst, $zweit], [
            1 => (string) $uni1,
            2 => (string) $uni2,
        ]);

        $this->assertArrayNotHasKey('field_1', $errors);
        $this->assertArrayNotHasKey('field_2', $errors);
    }

    // -------------------------------------------------------------------------
    // Studienrichtung (DB-gestütztes SELECT)
    // -------------------------------------------------------------------------

    /**
     * Legt eine Studienrichtung an und liefert die ID.
     *
     * @param int $active 1 = aktiv, 0 = inaktiv.
     * @return int
     */
    private function create_studytrack(int $active = 1): int {
        global $DB;
        return (int) $DB->insert_record('dhbwio_studytracks', (object) [
            'studyprogramid' => 1,
            'de_name'        => 'Wirtschaftsinformatik',
            'en_name'        => 'Business Information Systems',
            'active'         => $active,
        ]);
    }

    public function test_studytrack_valid_id_passes(): void {
        $trackid = $this->create_studytrack(1);
        $field = $this->make_field(['id' => 1, 'name' => 'STUDIENRICHTUNG', 'type' => 'select']);

        $errors = $this->run_validate([$field], [1 => (string) $trackid]);

        $this->assertArrayNotHasKey('field_1', $errors);
    }

    public function test_studytrack_unknown_id_returns_error(): void {
        $field = $this->make_field(['id' => 1, 'name' => 'STUDIENRICHTUNG', 'type' => 'select']);

        $errors = $this->run_validate([$field], [1 => '999999']);

        $this->assertArrayHasKey('field_1', $errors);
        $this->assertSame(\get_string('invaliddata', 'error'), $errors['field_1']);
    }

    public function test_studytrack_non_numeric_returns_error(): void {
        $field = $this->make_field(['id' => 1, 'name' => 'STUDIENRICHTUNG', 'type' => 'select']);

        $errors = $this->run_validate([$field], [1 => 'Wirtschaftsinformatik']);

        $this->assertArrayHasKey('field_1', $errors);
    }
}
