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
}
