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
 * Version information for DHBW IO university field
 *
 * @subpackage dhbwuni
 * @copyright  2025, DHBW
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'dataformfield_dhbwuni';
$plugin->version = 2025052601;
$plugin->requires = 2022041900;
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = 'v1.0.0';
$plugin->dependencies = array(
    'mod_dataform' => 2017051501,
    'mod_dhbwio' => 2025052601
);