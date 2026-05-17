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

    // Must end with @dhbw.de; length 5–100.
    const EMAIL_PATTERN = '/@dhbw\.de$/i';

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
}
