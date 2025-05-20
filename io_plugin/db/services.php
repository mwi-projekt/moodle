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
 * DHBW International Office external services definition.
 *
 * @package    mod_dhbwio
 * @category   external
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_dhbwio_get_universities' => [
        'classname'     => 'mod_dhbwio\external\get_universities',
        'methodname'    => 'execute',
        'description'   => 'Get universities data for map visualization',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/dhbwio:view'
    ],
    'mod_dhbwio_geocode_address' => [
        'classname'     => 'mod_dhbwio\external\geocode_address',
        'methodname'    => 'execute',
        'description'   => 'Geocodes an address to get latitude and longitude coordinates',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/dhbwio:manageuniversities',
    ],
];