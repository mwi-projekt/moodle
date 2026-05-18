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
 * Unit tests for applicant name and DHBW e-mail validation.
 *
 * User Story: Als Studierender möchte ich meinen Namen und meine DHBW-Mailadresse
 * eingeben, damit meine Bewerbung eindeutig zugeordnet werden kann.
 *
 * @package    mod_dhbwio
 * @category   phpunit
 * @group      mod_dhbwio
 * @group      mod_dhbwio_application_validation
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_dhbwio_application_validation_testcase extends advanced_testcase {

    // -------------------------------------------------------------------------
    // Name validation
    // -------------------------------------------------------------------------

    /**
     * @dataProvider valid_names_provider
     */
    public function test_valid_name_passes(string $name): void {
        $this->assertNull(
            application_validator::validate_name($name),
            "Expected '$name' to be a valid name."
        );
    }

    public function valid_names_provider(): array {
        return [
            'simple ascii'           => ['Mueller'],
            'german umlaut'          => ['Müller'],
            'double umlaut'          => ['Öztürk'],
            'hyphenated'             => ['Hans-Peter'],
            'with space'             => ['Anna Maria'],
            'single character'       => ['A'],
            'exactly 100 characters' => [str_repeat('a', 100)],
            'french accent'          => ['Héloïse'],
            'polish characters'      => ['Łukasz'],
        ];
    }

    /**
     * @dataProvider invalid_names_provider
     */
    public function test_invalid_name_returns_error(string $name, string $expected_error): void {
        $this->assertSame(
            $expected_error,
            application_validator::validate_name($name),
            "Expected error '$expected_error' for name '$name'."
        );
    }

    public function invalid_names_provider(): array {
        return [
            'empty string'             => ['',                    'name_required'],
            '101 characters'           => [str_repeat('a', 101), 'name_too_long'],
            'only special chars'       => ['###',                 'name_invalid_characters'],
            'only digits'              => ['12345',               'name_invalid_characters'],
            'special chars with text'  => ['Müller!',            'name_invalid_characters'],
            'at-sign'                  => ['user@test',           'name_invalid_characters'],
            'leading digit'            => ['1Mueller',            'name_invalid_characters'],
        ];
    }

    // -------------------------------------------------------------------------
    // DHBW e-mail validation
    // -------------------------------------------------------------------------

    /**
     * @dataProvider valid_emails_provider
     */
    public function test_valid_dhbw_email_passes(string $email): void {
        $this->assertNull(
            application_validator::validate_dhbw_email($email),
            "Expected '$email' to be a valid DHBW e-mail."
        );
    }

    public function valid_emails_provider(): array {
        return [
            'standard student mail'   => ['s123456@student.dhbw.de'],
            'short but valid (9 ch)'  => ['a@dhbw.de'],
            'subdomain'               => ['max.muster@ka.dhbw.de'],
            'exactly 100 chars'       => [str_repeat('x', 92) . '@dhbw.de'],  // 92 + 8 = 100
        ];
    }

    /**
     * @dataProvider invalid_emails_provider
     */
    public function test_invalid_dhbw_email_returns_error(string $email, string $expected_error): void {
        $this->assertSame(
            $expected_error,
            application_validator::validate_dhbw_email($email),
            "Expected error '$expected_error' for e-mail '$email'."
        );
    }

    public function invalid_emails_provider(): array {
        return [
            'empty string'             => ['',                          'email_required'],
            '4 characters'             => ['a@b.',                      'email_too_short'],
            '101 characters'           => [str_repeat('x', 93) . '@dhbw.de', 'email_too_long'],  // 93 + 8 = 101
            'no dhbw domain'           => ['max@gmail.com',             'email_not_dhbw'],
            'dhbw in local part only'  => ['dhbw@gmail.com',            'email_not_dhbw'],
            'wrong tld'                => ['max@dhbw.com',              'email_not_dhbw'],
            'missing @'                => ['maxdhbw.de',                'email_not_dhbw'],
        ];
    }

    // -------------------------------------------------------------------------
    // Birthdate validation
    // -------------------------------------------------------------------------

    /**
     * Valid birthdates must return null (no error).
     *
     * @dataProvider valid_birthdates_provider
     */
    public function test_valid_birthdate_passes(string $day, string $month, string $year): void {
        $this->assertNull(
            application_validator::validate_birthdate($day, $month, $year),
            "Expected $day.$month.$year to be a valid birthdate."
        );
    }

    public function valid_birthdates_provider(): array {
        return [
            'standard date'              => ['15', '6',  '1995'],
            'first day of year'          => ['1',  '1',  '1990'],
            'last day of year'           => ['31', '12', '2000'],
            'minimum year boundary'      => ['1',  '1',  '1900'],
            'maximum year boundary'      => ['31', '12', '2006'],
            'february in leap year'      => ['29', '2',  '2000'],
            'february last day non-leap' => ['28', '2',  '1999'],
            'thirty-day month'           => ['30', '4',  '2001'],
            'leading-zero day'           => ['05', '3',  '2002'],
        ];
    }

    /**
     * An invalid day must return 'birthdate_day_invalid'.
     *
     * @dataProvider invalid_day_provider
     */
    public function test_invalid_day_returns_error(string $day, string $month, string $year): void {
        $this->assertSame(
            'birthdate_day_invalid',
            application_validator::validate_birthdate($day, $month, $year),
            "Expected birthdate_day_invalid for day='$day'."
        );
    }

    public function invalid_day_provider(): array {
        return [
            'day zero'        => ['0',   '6', '2000'],
            'day 32'          => ['32',  '6', '2000'],
            'day negative'    => ['-1',  '6', '2000'],
            'day non-integer' => ['abc', '6', '2000'],
            'day float'       => ['1.5', '6', '2000'],
            'day empty'       => ['',    '6', '2000'],
        ];
    }

    /**
     * An invalid month must return 'birthdate_month_invalid'.
     *
     * @dataProvider invalid_month_provider
     */
    public function test_invalid_month_returns_error(string $day, string $month, string $year): void {
        $this->assertSame(
            'birthdate_month_invalid',
            application_validator::validate_birthdate($day, $month, $year),
            "Expected birthdate_month_invalid for month='$month'."
        );
    }

    public function invalid_month_provider(): array {
        return [
            'month zero'        => ['15', '0',   '2000'],
            'month 13'          => ['15', '13',  '2000'],
            'month negative'    => ['15', '-1',  '2000'],
            'month non-integer' => ['15', 'abc', '2000'],
            'month empty'       => ['15', '',    '2000'],
        ];
    }

    /**
     * An invalid year must return 'birthdate_year_invalid'.
     *
     * @dataProvider invalid_year_provider
     */
    public function test_invalid_year_returns_error(string $day, string $month, string $year): void {
        $this->assertSame(
            'birthdate_year_invalid',
            application_validator::validate_birthdate($day, $month, $year),
            "Expected birthdate_year_invalid for year='$year'."
        );
    }

    public function invalid_year_provider(): array {
        return [
            'year before 1900'  => ['15', '6', '1899'],
            'year after 2006'   => ['15', '6', '2007'],
            'year zero'         => ['15', '6', '0'],
            'year non-integer'  => ['15', '6', 'abc'],
            'year empty'        => ['15', '6', ''],
        ];
    }

    /**
     * A syntactically plausible but calendar-impossible date must return 'birthdate_invalid_date'.
     *
     * @dataProvider invalid_date_combination_provider
     */
    public function test_invalid_date_combination_returns_error(string $day, string $month, string $year): void {
        $this->assertSame(
            'birthdate_invalid_date',
            application_validator::validate_birthdate($day, $month, $year),
            "Expected birthdate_invalid_date for $day.$month.$year."
        );
    }

    public function invalid_date_combination_provider(): array {
        return [
            'april has no 31st'          => ['31', '4',  '2000'],
            'june has no 31st'           => ['31', '6',  '2000'],
            'november has no 31st'       => ['31', '11', '2000'],
            'february 29 in non-leap'    => ['29', '2',  '2001'],
            'february 30 in leap year'   => ['30', '2',  '2000'],
        ];
    }

    // =========================================================================
    // Studieninformationen
    // =========================================================================

    // -------------------------------------------------------------------------
    // Studiengang
    // -------------------------------------------------------------------------

    /**
     * @dataProvider valid_studiengang_provider
     */
    public function test_valid_studiengang_passes(string $value): void {
        $this->assertNull(
            application_validator::validate_studiengang($value),
            "Expected '$value' to be a valid Studiengang."
        );
    }

    public function valid_studiengang_provider(): array {
        return [
            'empty string (optional)'  => [''],
            'simple name'              => ['Wirtschaftsinformatik'],
            'with space'               => ['BWL Wirtschaft'],
            'with digits'              => ['BWL 2025'],
            'with hyphen'              => ['Maschinenbau-Konstruktion'],
            'with umlaut'              => ['Bürokommunikation'],
            'exactly 100 chars'        => [str_repeat('a', 100)],
        ];
    }

    /**
     * @dataProvider invalid_studiengang_provider
     */
    public function test_invalid_studiengang_returns_error(string $value, string $expected_error): void {
        $this->assertSame(
            $expected_error,
            application_validator::validate_studiengang($value),
            "Expected error '$expected_error' for Studiengang '$value'."
        );
    }

    public function invalid_studiengang_provider(): array {
        return [
            '101 characters'          => [str_repeat('a', 101), 'studiengang_too_long'],
            'at-sign'                 => ['IT@Design',           'studiengang_invalid_characters'],
            'exclamation mark'        => ['BWL!',                'studiengang_invalid_characters'],
            'parentheses'             => ['BWL (Karlsruhe)',     'studiengang_invalid_characters'],
        ];
    }

    // -------------------------------------------------------------------------
    // Kurs
    // -------------------------------------------------------------------------

    /**
     * @dataProvider valid_kurs_provider
     */
    public function test_valid_kurs_passes(string $value): void {
        $this->assertNull(
            application_validator::validate_kurs($value),
            "Expected '$value' to be a valid Kurs."
        );
    }

    public function valid_kurs_provider(): array {
        return [
            'typical course code'    => ['TINF22B4'],
            'short (3 chars)'        => ['BWL'],
            'exactly 10 chars'       => ['TINF22B4XY'],
            'with hyphen'            => ['WI-22B'],
            'with umlaut'            => ['TÜV22'],
        ];
    }

    /**
     * @dataProvider invalid_kurs_provider
     */
    public function test_invalid_kurs_returns_error(string $value, string $expected_error): void {
        $this->assertSame(
            $expected_error,
            application_validator::validate_kurs($value),
            "Expected error '$expected_error' for Kurs '$value'."
        );
    }

    public function invalid_kurs_provider(): array {
        return [
            'empty string'       => ['',              'kurs_required'],
            'two chars'          => ['WI',            'kurs_too_short'],
            '11 chars'           => ['TINF22B4XY2',   'kurs_too_long'],
            'at-sign'            => ['IT@22',          'kurs_invalid_characters'],
            'exclamation mark'   => ['WI!22',          'kurs_invalid_characters'],
        ];
    }

    // -------------------------------------------------------------------------
    // Semester
    // -------------------------------------------------------------------------

    /**
     * @dataProvider valid_semester_provider
     */
    public function test_valid_semester_passes(string $value): void {
        $this->assertNull(
            application_validator::validate_semester($value),
            "Expected '$value' to be a valid Semester."
        );
    }

    public function valid_semester_provider(): array {
        return [
            'first semester'  => ['1'],
            'third semester'  => ['3'],
            'sixth semester'  => ['6'],
        ];
    }

    /**
     * @dataProvider invalid_semester_provider
     */
    public function test_invalid_semester_returns_error(string $value): void {
        $this->assertSame(
            'semester_invalid',
            application_validator::validate_semester($value),
            "Expected semester_invalid for '$value'."
        );
    }

    public function invalid_semester_provider(): array {
        return [
            'empty string (Auswählen...)'  => [''],
            'zero'                         => ['0'],
            'seven'                        => ['7'],
            'non-integer'                  => ['abc'],
            'negative'                     => ['-1'],
        ];
    }

    // =========================================================================
    // Zuständigkeiten und Freigaben
    // =========================================================================

    // -------------------------------------------------------------------------
    // Studiengangsleitung
    // -------------------------------------------------------------------------

    /**
     * @dataProvider valid_studiengangsleitung_provider
     */
    public function test_valid_studiengangsleitung_passes(string $value): void {
        $this->assertNull(
            application_validator::validate_studiengangsleitung($value),
            "Expected '$value' to be valid."
        );
    }

    public function valid_studiengangsleitung_provider(): array {
        return [
            'simple name'          => ['Schmidt'],
            'with space'           => ['Dr Schmidt'],
            'with hyphen'          => ['Müller-Schmidt'],
            'with umlaut'          => ['Schönberger'],
            'exactly 100 chars'    => [str_repeat('a', 100)],
        ];
    }

    /**
     * @dataProvider invalid_studiengangsleitung_provider
     */
    public function test_invalid_studiengangsleitung_returns_error(string $value, string $expected_error): void {
        $this->assertSame(
            $expected_error,
            application_validator::validate_studiengangsleitung($value),
            "Expected error '$expected_error' for '$value'."
        );
    }

    public function invalid_studiengangsleitung_provider(): array {
        return [
            'empty string'       => ['',              'studiengangsleitung_required'],
            '101 chars'          => [str_repeat('a', 101), 'studiengangsleitung_too_long'],
            'digit in name'      => ['Meier1',         'studiengangsleitung_invalid_characters'],
            'special char'       => ['Müller!',        'studiengangsleitung_invalid_characters'],
            'at-sign'            => ['user@dhbw',      'studiengangsleitung_invalid_characters'],
        ];
    }

    // -------------------------------------------------------------------------
    // Partnerunternehmen
    // -------------------------------------------------------------------------

    /**
     * @dataProvider valid_partnerunternehmen_provider
     */
    public function test_valid_partnerunternehmen_passes(string $value): void {
        $this->assertNull(
            application_validator::validate_partnerunternehmen($value),
            "Expected '$value' to be valid."
        );
    }

    public function valid_partnerunternehmen_provider(): array {
        return [
            'simple name'               => ['Bosch GmbH'],
            'with hyphen'               => ['Daimler-Benz'],
            'with umlaut'               => ['Würth KG'],
            'leading/trailing spaces'   => ['  SAP SE  '],
            'exactly 150 chars'         => [str_repeat('a', 150)],
        ];
    }

    /**
     * @dataProvider invalid_partnerunternehmen_provider
     */
    public function test_invalid_partnerunternehmen_returns_error(string $value, string $expected_error): void {
        $this->assertSame(
            $expected_error,
            application_validator::validate_partnerunternehmen($value),
            "Expected error '$expected_error' for '$value'."
        );
    }

    public function invalid_partnerunternehmen_provider(): array {
        return [
            'empty string'         => ['',                   'partnerunternehmen_required'],
            'only spaces'          => ['   ',                'partnerunternehmen_required'],
            '151 chars after trim' => [str_repeat('a', 151), 'partnerunternehmen_too_long'],
            'contains digit'       => ['Bosch123',           'partnerunternehmen_invalid_characters'],
            'special char'         => ['SAP & Co',           'partnerunternehmen_invalid_characters'],
        ];
    }

    // -------------------------------------------------------------------------
    // Ansprechperson
    // -------------------------------------------------------------------------

    /**
     * @dataProvider valid_ansprechperson_provider
     */
    public function test_valid_ansprechperson_passes(string $value): void {
        $this->assertNull(
            application_validator::validate_ansprechperson($value),
            "Expected '$value' to be valid."
        );
    }

    public function valid_ansprechperson_provider(): array {
        return [
            'simple name'           => ['Max Mustermann'],
            'with special chars'    => ['Dr. Müller-Lüdenscheid'],
            'single character'      => ['X'],
            'exactly 100 chars'     => [str_repeat('a', 100)],
        ];
    }

    /**
     * @dataProvider invalid_ansprechperson_provider
     */
    public function test_invalid_ansprechperson_returns_error(string $value, string $expected_error): void {
        $this->assertSame(
            $expected_error,
            application_validator::validate_ansprechperson($value),
            "Expected error '$expected_error' for '$value'."
        );
    }

    public function invalid_ansprechperson_provider(): array {
        return [
            'empty string'  => ['',               'ansprechperson_required'],
            '101 chars'     => [str_repeat('a', 101), 'ansprechperson_too_long'],
        ];
    }

    // -------------------------------------------------------------------------
    // E-Mail Ansprechperson (optional)
    // -------------------------------------------------------------------------

    /**
     * @dataProvider valid_email_ansprechperson_provider
     */
    public function test_valid_email_ansprechperson_passes(string $value): void {
        $this->assertNull(
            application_validator::validate_email_ansprechperson($value),
            "Expected '$value' to be valid."
        );
    }

    public function valid_email_ansprechperson_provider(): array {
        return [
            'empty (optional)'         => [''],
            'standard gmail'           => ['max@gmail.com'],
            'company e-mail'           => ['kontakt@bosch.de'],
            'subdomain'                => ['hr@mail.company.org'],
            'with plus sign'           => ['max+work@example.com'],
        ];
    }

    /**
     * @dataProvider invalid_email_ansprechperson_provider
     */
    public function test_invalid_email_ansprechperson_returns_error(string $value): void {
        $this->assertSame(
            'email_ansprechperson_invalid',
            application_validator::validate_email_ansprechperson($value),
            "Expected email_ansprechperson_invalid for '$value'."
        );
    }

    public function invalid_email_ansprechperson_provider(): array {
        return [
            'no at-sign'          => ['notanemail'],
            'no domain'           => ['user@'],
            'no tld'              => ['user@domain'],
            'spaces'              => ['user @domain.de'],
        ];
    }

    // -------------------------------------------------------------------------
    // Absprache (radio button)
    // -------------------------------------------------------------------------

    /**
     * @dataProvider valid_absprache_provider
     */
    public function test_valid_absprache_passes(string $value): void {
        $this->assertNull(
            application_validator::validate_absprache($value),
            "Expected '$value' to be valid."
        );
    }

    public function valid_absprache_provider(): array {
        return [
            'nein lowercase'  => ['nein'],
            'ja lowercase'    => ['ja'],
            'nein uppercase'  => ['Nein'],
            'ja uppercase'    => ['Ja'],
        ];
    }

    /**
     * @dataProvider invalid_absprache_provider
     */
    public function test_invalid_absprache_returns_error(string $value): void {
        $this->assertSame(
            'absprache_required',
            application_validator::validate_absprache($value),
            "Expected absprache_required for '$value'."
        );
    }

    public function invalid_absprache_provider(): array {
        return [
            'empty string'  => [''],
            'other value'   => ['vielleicht'],
            'digit'         => ['1'],
        ];
    }

    // =========================================================================
    // Wunschliste der Zielhochschulen
    // =========================================================================

    // -------------------------------------------------------------------------
    // Erstwunsch
    // -------------------------------------------------------------------------

    /**
     * @dataProvider valid_erstwunsch_provider
     */
    public function test_valid_erstwunsch_passes(string $value): void {
        $this->assertNull(
            application_validator::validate_erstwunsch($value),
            "Expected '$value' to be valid."
        );
    }

    public function valid_erstwunsch_provider(): array {
        return [
            'university name'       => ['Universität Stuttgart'],
            'with hyphen'           => ['Karlsruher Institut fuer Technologie'],
            'foreign university'    => ['MIT Cambridge'],
            'with digits'           => ['TU Berlin 2'],
        ];
    }

    /**
     * @dataProvider invalid_erstwunsch_provider
     */
    public function test_invalid_erstwunsch_returns_error(string $value, string $expected_error): void {
        $this->assertSame(
            $expected_error,
            application_validator::validate_erstwunsch($value),
            "Expected error '$expected_error' for '$value'."
        );
    }

    public function invalid_erstwunsch_provider(): array {
        return [
            'empty string'    => ['',                    'erstwunsch_required'],
            'at-sign'         => ['Uni@Stuttgart',        'erstwunsch_invalid_characters'],
            'ampersand'       => ['Uni & Hochschule',     'erstwunsch_invalid_characters'],
        ];
    }

    // -------------------------------------------------------------------------
    // Duplicate checks
    // -------------------------------------------------------------------------

    public function test_zweitwunsch_duplicate_returns_error(): void {
        $this->assertSame(
            'wunsch_duplicate',
            application_validator::validate_zweitwunsch_duplicate('Uni Stuttgart', 'Uni Stuttgart')
        );
    }

    public function test_zweitwunsch_no_duplicate_passes(): void {
        $this->assertNull(application_validator::validate_zweitwunsch_duplicate('Uni Mannheim', 'Uni Stuttgart'));
    }

    public function test_zweitwunsch_empty_exempt_from_duplicate(): void {
        $this->assertNull(application_validator::validate_zweitwunsch_duplicate('', 'Uni Stuttgart'));
    }

    public function test_zweitwunsch_keine_exempt_from_duplicate(): void {
        $this->assertNull(application_validator::validate_zweitwunsch_duplicate('Keine', 'Uni Stuttgart'));
    }

    public function test_drittwunsch_duplicate_with_erst_returns_error(): void {
        $this->assertSame(
            'wunsch_duplicate',
            application_validator::validate_drittwunsch_duplicate('Uni Stuttgart', 'Uni Stuttgart', 'Uni Mannheim')
        );
    }

    public function test_drittwunsch_duplicate_with_zweit_returns_error(): void {
        $this->assertSame(
            'wunsch_duplicate',
            application_validator::validate_drittwunsch_duplicate('Uni Mannheim', 'Uni Stuttgart', 'Uni Mannheim')
        );
    }

    public function test_drittwunsch_no_duplicate_passes(): void {
        $this->assertNull(
            application_validator::validate_drittwunsch_duplicate('Uni Berlin', 'Uni Stuttgart', 'Uni Mannheim')
        );
    }

    public function test_drittwunsch_empty_exempt_from_duplicate(): void {
        $this->assertNull(
            application_validator::validate_drittwunsch_duplicate('', 'Uni Stuttgart', 'Uni Mannheim')
        );
    }

    public function test_drittwunsch_keine_exempt_from_duplicate(): void {
        $this->assertNull(
            application_validator::validate_drittwunsch_duplicate('Keine', 'Uni Stuttgart', 'Keine')
        );
    }

    // =========================================================================
    // Benachteiligte Gruppen und Freitext
    // =========================================================================

    // -------------------------------------------------------------------------
    // Benachteiligte Gruppe
    // -------------------------------------------------------------------------

    /**
     * @dataProvider valid_benachteiligte_gruppe_provider
     */
    public function test_valid_benachteiligte_gruppe_passes(bool $checked, string $text): void {
        $this->assertNull(
            application_validator::validate_benachteiligte_gruppe($checked, $text),
            "Expected no error for checked=$checked, text='$text'."
        );
    }

    public function valid_benachteiligte_gruppe_provider(): array {
        return [
            'checked with text'         => [true,  'I have a chronic illness.'],
            'checked with 500 chars'    => [true,  str_repeat('a', 500)],
            'not checked, empty text'   => [false, ''],
            'not checked, text present' => [false, 'some text'],
        ];
    }

    /**
     * @dataProvider invalid_benachteiligte_gruppe_provider
     */
    public function test_invalid_benachteiligte_gruppe_returns_error(
        bool $checked, string $text, string $expected_error
    ): void {
        $this->assertSame(
            $expected_error,
            application_validator::validate_benachteiligte_gruppe($checked, $text),
            "Expected error '$expected_error'."
        );
    }

    public function invalid_benachteiligte_gruppe_provider(): array {
        return [
            'checked but empty text'        => [true,  '',                   'benachteiligte_gruppe_text_required'],
            'text exceeds 500 chars'        => [true,  str_repeat('a', 501), 'benachteiligte_gruppe_text_too_long'],
            'unchecked but text too long'   => [false, str_repeat('a', 501), 'benachteiligte_gruppe_text_too_long'],
        ];
    }

    // -------------------------------------------------------------------------
    // Nachricht
    // -------------------------------------------------------------------------

    public function test_valid_nachricht_passes(): void {
        $this->assertNull(application_validator::validate_nachricht(''));
        $this->assertNull(application_validator::validate_nachricht('Hello!'));
        $this->assertNull(application_validator::validate_nachricht(str_repeat('a', 2500)));
    }

    public function test_nachricht_too_long_returns_error(): void {
        $this->assertSame(
            'nachricht_too_long',
            application_validator::validate_nachricht(str_repeat('a', 2501))
        );
    }

    // =========================================================================
    // Rechtliche Bestätigungen
    // =========================================================================

    public function test_datenschutz_true_passes(): void {
        $this->assertNull(application_validator::validate_datenschutz(true));
    }

    public function test_datenschutz_false_returns_error(): void {
        $this->assertSame(
            'datenschutz_required',
            application_validator::validate_datenschutz(false)
        );
    }
}
