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
    if ($oldversion < 2025062000) {
        
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
        upgrade_mod_savepoint(true, 2025062000, 'dhbwio');
    }

    // Add DataForm view fields for link generation
    if ($oldversion < 2025062300) {

        // Define field dataform_overview_view_id to be added to dhbwio.
        $table = new xmldb_table('dhbwio');
        $field = new xmldb_field('dataform_overview_view_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'dataform_id');

        // Conditionally launch add field dataform_overview_view_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field dataform_entry_view_id to be added to dhbwio.
        $field = new xmldb_field('dataform_entry_view_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'dataform_overview_view_id');

        // Conditionally launch add field dataform_entry_view_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025062300, 'dhbwio');
    }
        
    if ($oldversion < 2026051900) {

        // Define table dhbwio_dataform.
        $table = new xmldb_table('dhbwio_dataform');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, true, true, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, true, false, null);
        $table->add_field('intro', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('introformat', XMLDB_TYPE_INTEGER, '4', null, true, false, '0');
        $table->add_field('inlineview', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('embedded', XMLDB_TYPE_INTEGER, '2', null, true, false, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('timeavailable', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('timedue', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('timeinterval', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('intervalcount', XMLDB_TYPE_INTEGER, '10', null, true, false, '1');
        $table->add_field('grade', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('gradeitems', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('entrytypes', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('maxentries', XMLDB_TYPE_INTEGER, '8', null, true, false, '0');
        $table->add_field('entriesrequired', XMLDB_TYPE_INTEGER, '8', null, true, false, '0');
        $table->add_field('individualized', XMLDB_TYPE_INTEGER, '4', null, true, false, '0');
        $table->add_field('grouped', XMLDB_TYPE_INTEGER, '4', null, true, false, '0');
        $table->add_field('anonymous', XMLDB_TYPE_INTEGER, '1', null, true, false, '0');
        $table->add_field('timelimit', XMLDB_TYPE_INTEGER, '10', null, true, false, '-1');
        $table->add_field('css', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('cssincludes', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('js', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('jsincludes', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('defaultview', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('defaultfilter', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('completionentries', XMLDB_TYPE_INTEGER, '9', null, true, false, '0');
        $table->add_field('completionspecificgrade', XMLDB_TYPE_INTEGER, '9', null, true, false, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table dhbwio_dataform_fields.
        $table = new xmldb_table('dhbwio_dataform_fields');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, true, true, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('type', XMLDB_TYPE_CHAR, '255', null, true, false, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, true, false, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, true, false, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '4', null, true, false, '2');
        $table->add_field('editable', XMLDB_TYPE_INTEGER, '4', null, true, false, '1');
        $table->add_field('label', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('defaultcontentmode', XMLDB_TYPE_INTEGER, '4', null, true, false, '0');
        $table->add_field('defaultcontent', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param1', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param2', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param3', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param4', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param5', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param6', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param7', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param8', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param9', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param10', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('dataid', XMLDB_KEY_FOREIGN, array('dataid'), 'dhbwio_dataform', array('id'));
        $table->add_index('type-dataid', XMLDB_INDEX_NOTUNIQUE, array('type', 'dataid'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table dhbwio_dataform_views.
        $table = new xmldb_table('dhbwio_dataform_views');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, true, true, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('type', XMLDB_TYPE_CHAR, '255', null, true, false, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, true, false, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, true, false, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, true, false, '0');
        $table->add_field('entrytype', XMLDB_TYPE_CHAR, '32', null, false, false, null);
        $table->add_field('perpage', XMLDB_TYPE_INTEGER, '8', null, true, false, '0');
        $table->add_field('groupby', XMLDB_TYPE_CHAR, '64', null, false, false, null);
        $table->add_field('filterid', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('patterns', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('submission', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('section', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param1', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param2', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param3', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param4', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param5', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param6', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param7', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param8', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param9', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('param10', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('dataid', XMLDB_KEY_FOREIGN, array('dataid'), 'dhbwio_dataform', array('id'));
        $table->add_index('type-dataid', XMLDB_INDEX_NOTUNIQUE, array('type', 'dataid'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table dhbwio_dataform_filters.
        $table = new xmldb_table('dhbwio_dataform_filters');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, true, true, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, true, false, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, true, false, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '4', null, true, false, '1');
        $table->add_field('entrytype', XMLDB_TYPE_CHAR, '32', null, false, false, null);
        $table->add_field('perpage', XMLDB_TYPE_INTEGER, '4', null, true, false, '10');
        $table->add_field('selection', XMLDB_TYPE_INTEGER, '4', null, true, false, '0');
        $table->add_field('groupby', XMLDB_TYPE_CHAR, '64', null, false, false, null);
        $table->add_field('search', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('customsort', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('customsearch', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('dataid', XMLDB_KEY_FOREIGN, array('dataid'), 'dhbwio_dataform', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table dhbwio_dataform_entries.
        $table = new xmldb_table('dhbwio_dataform_entries');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, true, true, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '32', null, false, false, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('state', XMLDB_TYPE_INTEGER, '4', null, true, false, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('dataid', XMLDB_KEY_FOREIGN, array('dataid'), 'dhbwio_dataform', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table dhbwio_dataform_contents.
        $table = new xmldb_table('dhbwio_dataform_contents');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, true, true, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('entryid', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('content1', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('content2', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('content3', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_field('content4', XMLDB_TYPE_TEXT, null, null, false, false, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('entryid', XMLDB_KEY_FOREIGN, array('entryid'), 'dhbwio_dataform_entries', array('id'));
        $table->add_key('fieldid', XMLDB_KEY_FOREIGN, array('fieldid'), 'dhbwio_dataform_fields', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026051900, 'dhbwio');
    }

    return true;
}