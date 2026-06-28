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
/**
 * Validates applicant input for the Auslandssemester application form.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class application_validator {

    // Allows letters (incl. unicode/umlauts), hyphens, and spaces; min 1, max 100.
    const NAME_PATTERN = '/^[\p{L}\- ]{1,100}$/u';

    // Must end with @dhbw.de or any subdomain thereof (e.g. @student.dhbw.de); length 5–100.
    const EMAIL_PATTERN = '/@([a-z0-9-]+\.)*dhbw\.de$/i';

    // Letters (unicode), hyphens, spaces only — no digits or special chars.
    const NAME_CHARS_PATTERN = '/^[\p{L}\- ]+$/u';

    // No special chars: letters, digits, hyphens, spaces allowed.
    const NO_SPECIAL_CHARS_PATTERN = '/^[\p{L}0-9\- ]*$/u';

    // Standard e-mail address pattern.
    const STANDARD_EMAIL_PATTERN = '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/';

    /**
     * Validates a first or last name.
     *
     * @param string $value
     * @return string|null  Error message, or null if valid.
     */
    public static function validate_name(string $value): ?string {
        $len = mb_strlen($value);

        if ($len === 0) {
            return 'name_required';
        }
        if ($len > 100) {
            return 'name_too_long';
        }
        if (!preg_match(self::NAME_PATTERN, $value)) {
            return 'name_invalid_characters';
        }

        return null;
    }

    /**
     * Validates a DHBW e-mail address.
     *
     * @param string $value
     * @return string|null  Error message, or null if valid.
     */
    public static function validate_dhbw_email(string $value): ?string {
        $len = mb_strlen($value);

        if ($len === 0) {
            return 'email_required';
        }
        if ($len < 5) {
            return 'email_too_short';
        }
        if ($len > 100) {
            return 'email_too_long';
        }
        if (!preg_match(self::EMAIL_PATTERN, $value)) {
            return 'email_not_dhbw';
        }

        return null;
    }

    /**
     * Validates a birthdate given as separate day/month/year strings.
     *
     * @param string $day
     * @param string $month
     * @param string $year
     * @return string|null  Error message, or null if valid.
     */
    public static function validate_birthdate(string $day, string $month, string $year): ?string {
        if (!ctype_digit($day) || ($d = (int)$day) < 1 || $d > 31) {
            return 'birthdate_day_invalid';
        }
        if (!ctype_digit($month) || ($m = (int)$month) < 1 || $m > 12) {
            return 'birthdate_month_invalid';
        }
        if (!ctype_digit($year) || ($y = (int)$year) < 1900 || $y > 2006) {
            return 'birthdate_year_invalid';
        }
        if (!checkdate($m, $d, $y)) {
            return 'birthdate_invalid_date';
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // Study information
    // -------------------------------------------------------------------------

    /**
     * Validates Studiengang/Richtung (0–100 chars, no special characters).
     *
     * @param string $value
     * @return string|null
     */
    public static function validate_studiengang(string $value): ?string {
        if (mb_strlen($value) > 100) {
            return 'studiengang_too_long';
        }
        if (mb_strlen($value) > 0 && !preg_match(self::NO_SPECIAL_CHARS_PATTERN, $value)) {
            return 'studiengang_invalid_characters';
        }
        return null;
    }

    /**
     * Validates Kurs (3–10 chars, no special characters).
     *
     * @param string $value
     * @return string|null
     */
    public static function validate_kurs(string $value): ?string {
        $len = mb_strlen($value);
        if ($len === 0) {
            return 'kurs_required';
        }
        if ($len < 3) {
            return 'kurs_too_short';
        }
        if ($len > 10) {
            return 'kurs_too_long';
        }
        if (!preg_match(self::NO_SPECIAL_CHARS_PATTERN, $value)) {
            return 'kurs_invalid_characters';
        }
        return null;
    }

    /**
     * Validates Semester dropdown selection (must be one of '1'–'6').
     *
     * @param string $value
     * @return string|null
     */
    public static function validate_semester(string $value): ?string {
        if (!in_array($value, ['1', '2', '3', '4', '5', '6'], true)) {
            return 'semester_invalid';
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // Responsibilities and approvals
    // -------------------------------------------------------------------------

    /**
     * Validates Studiengangsleitung (1–100 chars, letters/hyphens/spaces only).
     *
     * @param string $value
     * @return string|null
     */
    public static function validate_studiengangsleitung(string $value): ?string {
        $len = mb_strlen($value);
        if ($len === 0) {
            return 'studiengangsleitung_required';
        }
        if ($len > 100) {
            return 'studiengangsleitung_too_long';
        }
        if (!preg_match(self::NAME_CHARS_PATTERN, $value)) {
            return 'studiengangsleitung_invalid_characters';
        }
        return null;
    }

    /**
     * Validates Partnerunternehmen name (1–150 chars after trim, letters/hyphens/spaces only).
     *
     * @param string $value
     * @return string|null
     */
    public static function validate_partnerunternehmen(string $value): ?string {
        $value = trim($value);
        $len = mb_strlen($value);
        if ($len === 0) {
            return 'partnerunternehmen_required';
        }
        if ($len > 150) {
            return 'partnerunternehmen_too_long';
        }
        if (!preg_match(self::NAME_CHARS_PATTERN, $value)) {
            return 'partnerunternehmen_invalid_characters';
        }
        return null;
    }

    /**
     * Validates Ansprechperson name (1–100 chars, any characters allowed).
     *
     * @param string $value
     * @return string|null
     */
    public static function validate_ansprechperson(string $value): ?string {
        $len = mb_strlen($value);
        if ($len === 0) {
            return 'ansprechperson_required';
        }
        if ($len > 100) {
            return 'ansprechperson_too_long';
        }
        return null;
    }

    /**
     * Validates optional e-mail of Ansprechperson (must match standard e-mail pattern when non-empty).
     *
     * @param string $value
     * @return string|null
     */
    public static function validate_email_ansprechperson(string $value): ?string {
        if (mb_strlen($value) === 0) {
            return null;
        }
        if (!preg_match(self::STANDARD_EMAIL_PATTERN, $value)) {
            return 'email_ansprechperson_invalid';
        }
        return null;
    }

    /**
     * Validates an Absprache radio button (must be 'ja' or 'nein', case-insensitive).
     *
     * @param string $value
     * @return string|null
     */
    public static function validate_absprache(string $value): ?string {
        if (!in_array(strtolower($value), ['ja', 'nein'], true)) {
            return 'absprache_required';
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // Wish list
    // -------------------------------------------------------------------------

    /**
     * Validates Erstwunsch (required, no special characters).
     *
     * @param string $value
     * @return string|null
     */
    public static function validate_erstwunsch(string $value): ?string {
        if (mb_strlen($value) === 0) {
            return 'erstwunsch_required';
        }
        if (!preg_match(self::NO_SPECIAL_CHARS_PATTERN, $value)) {
            return 'erstwunsch_invalid_characters';
        }
        return null;
    }

    /**
     * Duplicate check: Zweitwunsch must not equal Erstwunsch (empty and 'Keine' are exempt).
     *
     * @param string $zweit
     * @param string $erst
     * @return string|null
     */
    public static function validate_zweitwunsch_duplicate(string $zweit, string $erst): ?string {
        if ($zweit !== '' && $zweit !== 'Keine' && $zweit === $erst) {
            return 'wunsch_duplicate';
        }
        return null;
    }

    /**
     * Duplicate check: Drittwunsch must not equal Erstwunsch or Zweitwunsch (empty and 'Keine' are exempt).
     *
     * @param string $dritt
     * @param string $erst
     * @param string $zweit
     * @return string|null
     */
    public static function validate_drittwunsch_duplicate(string $dritt, string $erst, string $zweit): ?string {
        if ($dritt !== '' && $dritt !== 'Keine' && ($dritt === $erst || $dritt === $zweit)) {
            return 'wunsch_duplicate';
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // Disadvantaged groups and free text
    // -------------------------------------------------------------------------

    /**
     * Validates the disadvantaged-group text field (required when checkbox is true, max 500 chars).
     *
     * @param bool   $checked
     * @param string $text
     * @return string|null
     */
    public static function validate_benachteiligte_gruppe(bool $checked, string $text): ?string {
        $len = mb_strlen($text);
        if ($checked && $len === 0) {
            return 'benachteiligte_gruppe_text_required';
        }
        if ($len > 500) {
            return 'benachteiligte_gruppe_text_too_long';
        }
        return null;
    }

    /**
     * Validates optional Nachricht (max 2500 chars).
     *
     * @param string $value
     * @return string|null
     */
    public static function validate_nachricht(string $value): ?string {
        if (mb_strlen($value) > 2500) {
            return 'nachricht_too_long';
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // Legal confirmations
    // -------------------------------------------------------------------------

    /**
     * Validates Datenschutz checkbox (must be true).
     *
     * @param bool $value
     * @return string|null
     */
    public static function validate_datenschutz(bool $value): ?string {
        if (!$value) {
            return 'datenschutz_required';
        }
        return null;
    }
}
