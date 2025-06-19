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

/**
 * English strings for DHBW IO university field
 *
 * @subpackage dhbwuni
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'DHBW IO University Selection';
$string['fieldtypelabel'] = 'DHBW IO University';

// Field configuration
$string['allow_multiple_selections'] = 'Allow multiple university selections';
$string['allow_multiple_selections_help'] = 'Allow users to select multiple universities (e.g., for ranking preferences)';
$string['available_universities'] = 'Available Universities';
$string['universities_count'] = '{$a} universities available for selection';
$string['grouping'] = 'Country Grouping';
$string['universities_grouped_by_country'] = 'Universities will be automatically grouped by country in the dropdown';

// Display strings
$string['choose'] = 'Select a university...';
$string['not_selected'] = 'No university selected';
$string['any'] = 'Any university';
$string['none'] = 'None';

// Error messages
$string['fieldrequired'] = 'Please select a university';
$string['invaliduniversity'] = 'The selected university is not valid or no longer available';
$string['no_universities_available'] = 'No universities are available for selection. Please add universities to the DHBW International Office activity first.';
$string['no_dhbwio_instance'] = 'No DHBW International Office activity found in this course. Please add one before using this field type.';
$string['no_dhbwio_instance_desc'] = 'This field type requires a DHBW International Office activity in the same course to function properly.';
$string['invaliddefaultvalue'] = 'The selected default university is not valid or no longer available';
$string['no_universities_for_default'] = 'No universities available for default selection';

// Help strings
$string['dhbwuni_help'] = 'This field allows students to select from universities configured in the DHBW International Office activity. Universities are automatically grouped by country for better organization.';

// Privacy
$string['privacy:metadata'] = 'The DHBW IO University field plugin stores the selected university ID as part of the dataform entry.';
$string['privacy:metadata:fieldid'] = 'The ID of the field instance';
$string['privacy:metadata:entryid'] = 'The ID of the dataform entry';
$string['privacy:metadata:content'] = 'The selected university ID';
$string['privacy:metadata:timecreated'] = 'The time when the selection was made';
$string['privacy:metadata:timemodified'] = 'The time when the selection was last modified';