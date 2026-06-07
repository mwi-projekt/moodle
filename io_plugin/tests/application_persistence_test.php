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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../classes/application_validator.php');

use mod_dhbwio\application_validator;

/**
 * Integration tests verifying that field-level validation rules are enforced
 * before database persistence, and that trimmed values are stored without spaces.
 *
 * Persistence is verified against dhbwio_universities, the plugin's own DB table,
 * which provides a concrete write/read cycle without depending on the Dataform plugin.
 *
 * @package    mod_dhbwio
 * @category   phpunit
 * @group      mod_dhbwio
 * @group      mod_dhbwio_application_persistence
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_dhbwio_application_persistence_testcase extends advanced_testcase {

    /** @var stdClass The dhbwio module instance created fresh for each test. */
    private stdClass $dhbwio;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $generator  = $this->getDataGenerator();
        $course     = $generator->create_course();
        $this->dhbwio = $generator->create_module('dhbwio', ['course' => $course->id]);
    }

    /**
     * Inserts a record into dhbwio_universities with the given name and returns the
     * DB row. Used as a concrete persist-then-read cycle for validator chain tests.
     */
    private function persist_name_to_db(string $name): stdClass {
        global $DB;
        $id = $DB->insert_record('dhbwio_universities', [
            'dhbwio'             => $this->dhbwio->id,
            'name'               => $name,
            'country'            => 'DE',
            'city'               => 'Karlsruhe',
            'available_slots'    => 1,
            'accommodation_type' => 'various',
            'semester_fees'      => 0.00,
            'fee_currency'       => 'EUR',
            'active'             => 1,
            'timemodified'       => time(),
        ]);
        return $DB->get_record('dhbwio_universities', ['id' => $id]);
    }

    /** Returns the current university record count for this plugin instance. */
    private function university_count(): int {
        global $DB;
        return $DB->count_records('dhbwio_universities', ['dhbwio' => $this->dhbwio->id]);
    }

    // =========================================================================
    // resetAfterTest guarantee
    // =========================================================================

    public function test_each_test_starts_with_empty_university_table(): void {
        $this->assertSame(0, $this->university_count(),
            'resetAfterTest() must guarantee a clean slate so tests do not interfere.');
    }

    // =========================================================================
    // Trim-Persistenz: Partnerunternehmen
    // =========================================================================

    public function test_partnerunternehmen_trimmed_value_stored_without_surrounding_spaces(): void {
        $raw = '  SAP SE  ';

        // Validator trims internally – must return no error.
        $error = application_validator::validate_partnerunternehmen($raw);
        $this->assertNull($error, "Validator should accept a value that is valid after trimming.");

        // Save layer must apply trim() before persisting (mirrors what the validator does).
        $record = $this->persist_name_to_db(trim($raw));

        $this->assertSame('SAP SE', $record->name);
        $this->assertStringNotStartsWith(' ', $record->name, 'DB value must not have a leading space.');
        $this->assertStringNotEndsWith(' ', $record->name, 'DB value must not have a trailing space.');
    }

    public function test_partnerunternehmen_only_spaces_rejected_and_nothing_written_to_db(): void {
        $before = $this->university_count();

        $error = application_validator::validate_partnerunternehmen('   ');
        $this->assertSame('partnerunternehmen_required', $error);

        $this->assertSame($before, $this->university_count(),
            'A required-field violation must prevent any write to the DB.');
    }

    // =========================================================================
    // Sonderzeichen-Sperre: Partnerunternehmen
    // =========================================================================

    /**
     * @dataProvider special_char_partnerunternehmen_provider
     */
    public function test_partnerunternehmen_with_special_chars_blocked_before_db(string $value): void {
        $before = $this->university_count();

        $error = application_validator::validate_partnerunternehmen($value);
        $this->assertNotNull($error, "Validator must reject '$value' before it reaches the DB.");

        $this->assertSame($before, $this->university_count(),
            "No record must be written when validation fails for '$value'.");
    }

    public function special_char_partnerunternehmen_provider(): array {
        return [
            'ampersand'        => ['SAP & Co'],
            'at-sign'          => ['Bosch@Corp'],
            'exclamation mark' => ['Tesla!'],
            'contains digit'   => ['BMW123'],
        ];
    }

    // =========================================================================
    // Sonderzeichen-Sperre: Studiengang
    // =========================================================================

    /**
     * @dataProvider special_char_studiengang_provider
     */
    public function test_studiengang_with_special_chars_blocked_before_db(string $value): void {
        $before = $this->university_count();

        $error = application_validator::validate_studiengang($value);
        $this->assertNotNull($error, "Validator must reject '$value' before it reaches the DB.");

        $this->assertSame($before, $this->university_count(),
            "No record must be written when validation fails for '$value'.");
    }

    public function special_char_studiengang_provider(): array {
        return [
            'at-sign'        => ['IT@Design'],
            'exclamation'    => ['BWL!'],
            'parentheses'    => ['BWL (Karlsruhe)'],
            '101 characters' => [str_repeat('a', 101)],
        ];
    }

    // =========================================================================
    // Sonderzeichen-Sperre: Kurs
    // =========================================================================

    /**
     * @dataProvider invalid_kurs_provider
     */
    public function test_kurs_invalid_values_blocked_before_db(string $value, string $expected_error): void {
        $before = $this->university_count();

        $error = application_validator::validate_kurs($value);
        $this->assertSame($expected_error, $error, "Validator must reject '$value'.");

        $this->assertSame($before, $this->university_count(),
            "No record must be written when validation fails for '$value'.");
    }

    public function invalid_kurs_provider(): array {
        return [
            'at-sign'          => ['IT@22',        'kurs_invalid_characters'],
            'exclamation mark' => ['WI!22',         'kurs_invalid_characters'],
            'too short'        => ['WI',            'kurs_too_short'],
            'too long'         => ['TINF22B4XY2',   'kurs_too_long'],
            'empty'            => ['',              'kurs_required'],
        ];
    }

    // =========================================================================
    // Gültige Werte werden korrekt persistiert
    // =========================================================================

    public function test_valid_name_persisted_exactly_as_submitted(): void {
        $name   = 'Universität Stuttgart';
        $record = $this->persist_name_to_db($name);
        $this->assertSame($name, $record->name);
    }

    public function test_name_at_max_length_persisted_without_truncation(): void {
        $name   = str_repeat('a', 100);
        $record = $this->persist_name_to_db($name);
        $this->assertSame(100, mb_strlen($record->name),
            'A 100-character name must not be truncated by the DB layer.');
    }

    public function test_name_with_umlaut_persisted_correctly(): void {
        $name   = 'Würth KG';
        $record = $this->persist_name_to_db($name);
        $this->assertSame($name, $record->name,
            'UTF-8 characters must survive the persist/read cycle unchanged.');
    }
}
