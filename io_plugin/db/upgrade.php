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
 * This file keeps track of upgrades to the dhbwio module
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_dhbwio_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Add missing fields for DataForm integration and utilization settings
    if ($oldversion < 2025052601) {

        // Define table dhbwio to be updated
        $table = new xmldb_table('dhbwio');

        // Add dataform_id field
        $field = new xmldb_field('dataform_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'enablereports');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add field mapping fields
        $field = new xmldb_field('first_wish_field', XMLDB_TYPE_CHAR, '100', null, null, null, 'first_wish', 'dataform_id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('second_wish_field', XMLDB_TYPE_CHAR, '100', null, null, null, 'second_wish', 'first_wish_field');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('third_wish_field', XMLDB_TYPE_CHAR, '100', null, null, null, 'third_wish', 'second_wish_field');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add weight fields
        $field = new xmldb_field('first_wish_weight', XMLDB_TYPE_NUMBER, '5,2', null, XMLDB_NOTNULL, null, '100.00', 'third_wish_field');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('second_wish_weight', XMLDB_TYPE_NUMBER, '5,2', null, XMLDB_NOTNULL, null, '30.00', 'first_wish_weight');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('third_wish_weight', XMLDB_TYPE_NUMBER, '5,2', null, XMLDB_NOTNULL, null, '0.00', 'second_wish_weight');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add utilization settings
        $field = new xmldb_field('enable_utilisation', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'third_wish_weight');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('utilisation_cache_duration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1800', 'enable_utilisation');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // dhbwio savepoint reached
        upgrade_mod_savepoint(true, 2025052601, 'dhbwio');
    }

	// Add email log table
    if ($oldversion < 2025062100) {
        
        // Define table dhbwio_email_log to be created
        $table = new xmldb_table('dhbwio_email_log');

        // Adding fields to table dhbwio_email_log
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('dhbwio_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('entry_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email_type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table dhbwio_email_log
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('dhbwio_id', XMLDB_KEY_FOREIGN, ['dhbwio_id'], 'dhbwio', ['id']);
        $table->add_key('user_id', XMLDB_KEY_FOREIGN, ['user_id'], 'user', ['id']);

        // Adding indexes to table dhbwio_email_log
        $table->add_index('entry_id', XMLDB_INDEX_NOTUNIQUE, ['entry_id']);
        $table->add_index('dhbwio_entry', XMLDB_INDEX_NOTUNIQUE, ['dhbwio_id', 'entry_id']);

        // Conditionally launch create table for dhbwio_email_log
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Update version number
        upgrade_mod_savepoint(true, 2025062100, 'dhbwio');
    }

    return true;
}