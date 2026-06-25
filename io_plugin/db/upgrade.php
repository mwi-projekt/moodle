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

use mod_dhbwio\local\dataform\field_manager;

function xmldb_dhbwio_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    $newversion = 2026061702;

    // Add missing fields for DataForm integration and utilization settings
    if ($oldversion < $newversion) {

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
        //upgrade_mod_savepoint(true, $newversion, 'dhbwio');
    }

    // Add email log table
    if ($oldversion < $newversion) {

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
        //upgrade_mod_savepoint(true, $newversion, 'dhbwio');
    }

    // Add DataForm view fields for link generation
    if ($oldversion < $newversion) {

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

        //upgrade_mod_savepoint(true, $newversion, 'dhbwio');
    }

    if ($oldversion < $newversion) {

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

        //upgrade_mod_savepoint(true, $newversion, 'dhbwio');
    }
    if ($oldversion < $newversion) {

        // Define table dhbwio_application_status.
        $table = new xmldb_table('dhbwio_application_status');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('label', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('isinitial', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('isaccepted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('isrejected', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('shortname_unique', XMLDB_KEY_UNIQUE, ['shortname']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Insert default statuses.
        $now = time();

        $statuses = [
            [
                'shortname' => 'eingereicht',
                'label' => 'Eingereicht',
                'description' => 'Die Bewerbung wurde eingereicht.',
                'sortorder' => 10,
                'active' => 1,
                'isinitial' => 1,
                'isaccepted' => 0,
                'isrejected' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
            [
                'shortname' => 'in_pruefung',
                'label' => 'In Prüfung',
                'description' => 'Die Bewerbung wird geprüft.',
                'sortorder' => 20,
                'active' => 1,
                'isinitial' => 0,
                'isaccepted' => 0,
                'isrejected' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
            [
                'shortname' => 'angenommen',
                'label' => 'Angenommen',
                'description' => 'Die Bewerbung wurde angenommen.',
                'sortorder' => 30,
                'active' => 1,
                'isinitial' => 0,
                'isaccepted' => 1,
                'isrejected' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
            [
                'shortname' => 'abgelehnt',
                'label' => 'Abgelehnt',
                'description' => 'Die Bewerbung wurde abgelehnt.',
                'sortorder' => 40,
                'active' => 1,
                'isinitial' => 0,
                'isaccepted' => 0,
                'isrejected' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
        ];

        foreach ($statuses as $status) {
            if (!$DB->record_exists('dhbwio_application_status', ['shortname' => $status['shortname']])) {
                $DB->insert_record('dhbwio_application_status', (object) $status);
            }
        }

        // Add statusid to dhbwio_dataform_entries.
        $entrytable = new xmldb_table('dhbwio_dataform_entries');
        $field = new xmldb_field('statusid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'state');

        if (!$dbman->field_exists($entrytable, $field)) {
            $dbman->add_field($entrytable, $field);
        }

        // Set all existing entries to initial status.
        $initialstatus = $DB->get_record('dhbwio_application_status', ['isinitial' => 1], '*', MUST_EXIST);

        $DB->set_field(
            'dhbwio_dataform_entries',
            'statusid',
            $initialstatus->id,
            ['statusid' => 0]
        );

        // Add foreign key after data was migrated.
        $key = new xmldb_key('statusid_fk', XMLDB_KEY_FOREIGN, ['statusid'], 'dhbwio_application_status', ['id']);

        $dbman->add_key($entrytable, $key);

        //upgrade_mod_savepoint(true, $newversion, 'dhbwio');
    }

    if ($oldversion < $newversion) {

        $table = new xmldb_table('dhbwio_dataform_fields');

        $scopefield = new xmldb_field('scope', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, field_manager::SCOPE_STUDENT, 'editable');
        if (!$dbman->field_exists($table, $scopefield)) {
            $dbman->add_field($table, $scopefield);
        }

        $groupfield = new xmldb_field('fieldgroup', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, field_manager::GROUP_GENERAL, 'scope');
        if (!$dbman->field_exists($table, $groupfield)) {
            $dbman->add_field($table, $groupfield);
        }

        $sortorderfield = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'fieldgroup');
        if (!$dbman->field_exists($table, $sortorderfield)) {
            $dbman->add_field($table, $sortorderfield);
        }

        $fieldmetadata = [
            'NACHNAME' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_PERSONAL, 10],
            'VORNAME' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_PERSONAL, 20],
            'GEBURTSDATUM' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_PERSONAL, 30],
            'NATIONALITAET' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_PERSONAL, 40],
            'MUTTERSPRACHE' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_PERSONAL, 50],
            'EMAIL' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_PERSONAL, 60],

            'STUDIENGANG' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_STUDY, 10],
            'STUDIENRICHTUNG' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_STUDY, 20],
            'KURSNAME' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_STUDY, 30],
            'STUDIENGANGSLEITUNG' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_STUDY, 40],
            'ABSPRACHE_MIT_STUDIENGANGSLEITUNG' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_STUDY, 50],
            'AKTUELLES_SEMESTER' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_STUDY, 60],

            'UNTERNEHMEN' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_COMPANY, 10],
            'ANSPRECHPERSON_UNTERNEHMEN' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_COMPANY, 20],
            'ANSPRECHPERSON_EMAIL' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_COMPANY, 30],
            'ABSPRACHE_MIT_UNTERNEHMEN' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_COMPANY, 40],

            'ERSTWUNSCH' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_CHOICES, 10],
            'ZWEITWUNSCH' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_CHOICES, 20],
            'DRITTWUNSCH' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_CHOICES, 30],

            'BENACHTEILIGUNG_BILDUNGSCHANCEN' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_STATEMENTS, 10],
            'NACHRICHT' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_STATEMENTS, 20],
            'VEROEFFENTLICHUNG_MAILADRESSE_UND_BERICHT' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_STATEMENTS, 30],
            'EINVERSTAENDNISERKLAERUNG_DATENSCHUTZ' => [field_manager::SCOPE_STUDENT, field_manager::GROUP_STATEMENTS, 40],

            'KOMMENTAR_IO' => [field_manager::SCOPE_REVIEW, field_manager::GROUP_REVIEW, 10],
            'SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST' => [field_manager::SCOPE_DEPRECATED, field_manager::GROUP_TECHNICAL, 20],
            'SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT' => [field_manager::SCOPE_DEPRECATED, field_manager::GROUP_TECHNICAL, 30],
            'SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT' => [field_manager::SCOPE_DEPRECATED, field_manager::GROUP_TECHNICAL, 40],

            'STRASSE' => [field_manager::SCOPE_DEPRECATED, field_manager::GROUP_TECHNICAL, 90],
            'HAUSNUMMER' => [field_manager::SCOPE_DEPRECATED, field_manager::GROUP_TECHNICAL, 100],
            'ORT' => [field_manager::SCOPE_DEPRECATED, field_manager::GROUP_TECHNICAL, 110],
            'PLZ' => [field_manager::SCOPE_DEPRECATED, field_manager::GROUP_TECHNICAL, 120],
            'HANDYNUMMER' => [field_manager::SCOPE_DEPRECATED, field_manager::GROUP_TECHNICAL, 130],
            'DATEIEN' => [field_manager::SCOPE_DEPRECATED, field_manager::GROUP_TECHNICAL, 140],
            'ADRESSEZUSATZ' => [field_manager::SCOPE_DEPRECATED, field_manager::GROUP_TECHNICAL, 150],
            'STATUS_BEWERBUNG' => [field_manager::SCOPE_DEPRECATED, field_manager::GROUP_TECHNICAL, 160],
        ];

        foreach ($fieldmetadata as $name => [$scope, $fieldgroup, $sortorder]) {
            $DB->set_field('dhbwio_dataform_fields', 'scope', $scope, ['name' => $name]);
            $DB->set_field('dhbwio_dataform_fields', 'fieldgroup', $fieldgroup, ['name' => $name]);
            $DB->set_field('dhbwio_dataform_fields', 'sortorder', $sortorder, ['name' => $name]);
        }

        //upgrade_mod_savepoint(true, $newversion, 'dhbwio');
    }

    if ($oldversion < $newversion) {

        $table = new xmldb_table('dhbwio_studyprograms');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('de_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('en_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            $dbman->create_table($table);
        }

        $table = new xmldb_table('dhbwio_studytracks');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('studyprogramid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('de_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('en_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('studyprogramid', XMLDB_KEY_FOREIGN, ['studyprogramid'], 'dhbwio_studyprograms', ['id']);

            $dbman->create_table($table);
        }

        $table = new xmldb_table('dhbwio_electives');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('studytrackid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('de_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('en_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('studytrackid', XMLDB_KEY_FOREIGN, ['studytrackid'], 'dhbwio_studytracks', ['id']);

            $dbman->create_table($table);
        }

        $table = new xmldb_table('dhbwio_dataform_entries');
        $field = new xmldb_field('acceptedchoice', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'statusid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        //upgrade_mod_savepoint(true, $newversion, 'dhbwio');
    }
    /*
    if ($oldversion < 2026060800) {

        // Sicherheitsnetz: dhbwio_fristen Tabelle erstellen falls sie noch nicht existiert.
        $table = new xmldb_table('dhbwio_fristen');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id',           XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('dhbwio',       XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
            $table->add_field('art',          XMLDB_TYPE_CHAR,    '50',  null, XMLDB_NOTNULL, null, null);
            $table->add_field('studiengang',  XMLDB_TYPE_CHAR,    '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('jahrgang',     XMLDB_TYPE_CHAR,    '50',  null, XMLDB_NOTNULL, null, null);
            $table->add_field('deadline',     XMLDB_TYPE_INTEGER, '10',  null, null,           null, null);
            $table->add_field('kommentar',    XMLDB_TYPE_TEXT,    null,  null, null,           null, null);
            $table->add_field('authorid',     XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated',  XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary',   XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('fk_dhbwio', XMLDB_KEY_FOREIGN, ['dhbwio'],   'dhbwio', ['id']);
            $table->add_key('fk_author', XMLDB_KEY_FOREIGN, ['authorid'], 'user',   ['id']);
            $table->add_index('dhbwio_jahrgang', XMLDB_INDEX_NOTUNIQUE, ['dhbwio', 'jahrgang']);
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026060800, 'dhbwio');
    }
*/
    if ($oldversion < 2026060501) {

        // Create dhbwio_fristen table.
        $table = new xmldb_table('dhbwio_fristen');
        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('dhbwio',       XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('art',          XMLDB_TYPE_CHAR,    '50',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('studiengang',  XMLDB_TYPE_CHAR,    '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('jahrgang',     XMLDB_TYPE_CHAR,    '50',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('deadline',     XMLDB_TYPE_INTEGER, '10',  null, null,           null, null);
        $table->add_field('kommentar',    XMLDB_TYPE_TEXT,    null,  null, null,           null, null);
        $table->add_field('authorid',     XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated',  XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary',    XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_dhbwio',  XMLDB_KEY_FOREIGN, ['dhbwio'],   'dhbwio', ['id']);
        $table->add_key('fk_author',  XMLDB_KEY_FOREIGN, ['authorid'], 'user',   ['id']);

        $table->add_index('dhbwio_jahrgang', XMLDB_INDEX_NOTUNIQUE, ['dhbwio', 'jahrgang']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026060501, 'dhbwio');
    }

    if ($oldversion < 2026060502) {

        $table = new xmldb_table('dhbwio_fristen');
        if ($dbman->table_exists($table)) {
            // Change jahrgang from int to char if needed.
            $jagfield = new xmldb_field('jahrgang', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            if ($dbman->field_exists($table, $jagfield)) {
                // Drop dependent index before changing field type.
                $index = new xmldb_index('dhbwio_jahrgang', XMLDB_INDEX_NOTUNIQUE, ['dhbwio', 'jahrgang']);
                if ($dbman->index_exists($table, $index)) {
                    $dbman->drop_index($table, $index);
                }
                $dbman->change_field_type($table, $jagfield);
                // Recreate index after type change.
                if (!$dbman->index_exists($table, $index)) {
                    $dbman->add_index($table, $index);
                }
            }

            // Add deadline field if missing.
            $deadlinefield = new xmldb_field('deadline', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'jahrgang');
            if (!$dbman->field_exists($table, $deadlinefield)) {
                $dbman->add_field($table, $deadlinefield);
            }
        }

        upgrade_mod_savepoint(true, 2026060502, 'dhbwio');
    }

    if ($oldversion < 2026060503) {

        $table = new xmldb_table('dhbwio_fristen');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('deadline', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'jahrgang');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026060503, 'dhbwio');
    }

    if ($oldversion < 2026060900) {
        $table = new xmldb_table('dhbwio_learning_agreements');
        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('dhbwio',       XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid',       XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('filename',     XMLDB_TYPE_CHAR,    '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status',       XMLDB_TYPE_CHAR,    '20',  null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('comment',      XMLDB_TYPE_TEXT,    null,  null, null,          null, null);
        $table->add_field('timecreated',  XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary',   XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_dhbwio', XMLDB_KEY_FOREIGN, ['dhbwio'], 'dhbwio', ['id']);
        $table->add_key('fk_user',   XMLDB_KEY_FOREIGN, ['userid'], 'user',   ['id']);

        $table->add_index('dhbwio_user', XMLDB_INDEX_NOTUNIQUE, ['dhbwio', 'userid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026060900, 'dhbwio');
    }

    if ($oldversion < 2026061101) {
        // Add doctype field to dhbwio_learning_agreements.
        $table = new xmldb_table('dhbwio_learning_agreements');
        $field = new xmldb_field('doctype', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, 'learning_agreement', 'userid');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Insert "nachzureichen" status if it does not exist yet.
        if (!$DB->record_exists('dhbwio_application_status', ['shortname' => 'nachzureichen'])) {
            $now = time();
            $DB->insert_record('dhbwio_application_status', (object)[
                'shortname'    => 'nachzureichen',
                'label'        => 'Nachzureichen',
                'description'  => 'Unterlagen müssen nachgereicht werden.',
                'sortorder'    => 50,
                'active'       => 1,
                'isinitial'    => 0,
                'isaccepted'   => 0,
                'isrejected'   => 0,
                'timecreated'  => $now,
                'timemodified' => $now,
            ]);
        }

        upgrade_mod_savepoint(true, 2026061101, 'dhbwio');
    }

    if ($oldversion < 2026061102) {

        // Add within_deadline column to dhbwio_dataform_entries.
        $table = new xmldb_table('dhbwio_dataform_entries');
        $field = new xmldb_field('within_deadline', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'acceptedchoice');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        //upgrade_mod_savepoint(true, $newversion, 'dhbwio');
    }

    if ($oldversion < 2026060800) {

        // Sicherheitsnetz: dhbwio_fristen Tabelle erstellen falls sie noch nicht existiert.
        $table = new xmldb_table('dhbwio_fristen');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id',           XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('dhbwio',       XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
            $table->add_field('art',          XMLDB_TYPE_CHAR,    '50',  null, XMLDB_NOTNULL, null, null);
            $table->add_field('studiengang',  XMLDB_TYPE_CHAR,    '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('jahrgang',     XMLDB_TYPE_CHAR,    '50',  null, XMLDB_NOTNULL, null, null);
            $table->add_field('deadline',     XMLDB_TYPE_INTEGER, '10',  null, null,           null, null);
            $table->add_field('kommentar',    XMLDB_TYPE_TEXT,    null,  null, null,           null, null);
            $table->add_field('authorid',     XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated',  XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary',   XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('fk_dhbwio', XMLDB_KEY_FOREIGN, ['dhbwio'],   'dhbwio', ['id']);
            $table->add_key('fk_author', XMLDB_KEY_FOREIGN, ['authorid'], 'user',   ['id']);
            $table->add_index('dhbwio_jahrgang', XMLDB_INDEX_NOTUNIQUE, ['dhbwio', 'jahrgang']);
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026060800, 'dhbwio');
    }

    if ($oldversion < 2026060501) {

        // Create dhbwio_fristen table.
        $table = new xmldb_table('dhbwio_fristen');
        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('dhbwio',       XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('art',          XMLDB_TYPE_CHAR,    '50',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('studiengang',  XMLDB_TYPE_CHAR,    '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('jahrgang',     XMLDB_TYPE_CHAR,    '50',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('deadline',     XMLDB_TYPE_INTEGER, '10',  null, null,           null, null);
        $table->add_field('kommentar',    XMLDB_TYPE_TEXT,    null,  null, null,           null, null);
        $table->add_field('authorid',     XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated',  XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary',    XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_dhbwio',  XMLDB_KEY_FOREIGN, ['dhbwio'],   'dhbwio', ['id']);
        $table->add_key('fk_author',  XMLDB_KEY_FOREIGN, ['authorid'], 'user',   ['id']);

        $table->add_index('dhbwio_jahrgang', XMLDB_INDEX_NOTUNIQUE, ['dhbwio', 'jahrgang']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026060501, 'dhbwio');
    }

    if ($oldversion < 2026060502) {

        $table = new xmldb_table('dhbwio_fristen');
        if ($dbman->table_exists($table)) {
            // Change jahrgang from int to char if needed.
            $jagfield = new xmldb_field('jahrgang', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            if ($dbman->field_exists($table, $jagfield)) {
                // Drop dependent index before changing field type.
                $index = new xmldb_index('dhbwio_jahrgang', XMLDB_INDEX_NOTUNIQUE, ['dhbwio', 'jahrgang']);
                if ($dbman->index_exists($table, $index)) {
                    $dbman->drop_index($table, $index);
                }
                $dbman->change_field_type($table, $jagfield);
                // Recreate index after type change.
                if (!$dbman->index_exists($table, $index)) {
                    $dbman->add_index($table, $index);
                }
            }

            // Add deadline field if missing.
            $deadlinefield = new xmldb_field('deadline', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'jahrgang');
            if (!$dbman->field_exists($table, $deadlinefield)) {
                $dbman->add_field($table, $deadlinefield);
            }
        }

        upgrade_mod_savepoint(true, 2026060502, 'dhbwio');
    }

    if ($oldversion < 2026060503) {

        $table = new xmldb_table('dhbwio_fristen');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('deadline', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'jahrgang');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026060503, 'dhbwio');
    }

    if ($oldversion < 2026060900) {
        $table = new xmldb_table('dhbwio_learning_agreements');
        $table->add_field('id',           XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('dhbwio',       XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid',       XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('filename',     XMLDB_TYPE_CHAR,    '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status',       XMLDB_TYPE_CHAR,    '20',  null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('comment',      XMLDB_TYPE_TEXT,    null,  null, null,          null, null);
        $table->add_field('timecreated',  XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10',  null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary',   XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_dhbwio', XMLDB_KEY_FOREIGN, ['dhbwio'], 'dhbwio', ['id']);
        $table->add_key('fk_user',   XMLDB_KEY_FOREIGN, ['userid'], 'user',   ['id']);

        $table->add_index('dhbwio_user', XMLDB_INDEX_NOTUNIQUE, ['dhbwio', 'userid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026060900, 'dhbwio');
    }

    if ($oldversion < 2026061101) {
        // Add doctype field to dhbwio_learning_agreements.
        $table = new xmldb_table('dhbwio_learning_agreements');
        $field = new xmldb_field('doctype', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, 'learning_agreement', 'userid');
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Insert "nachzureichen" status if it does not exist yet.
        if (!$DB->record_exists('dhbwio_application_status', ['shortname' => 'nachzureichen'])) {
            $now = time();
            $DB->insert_record('dhbwio_application_status', (object)[
                'shortname'    => 'nachzureichen',
                'label'        => 'Nachzureichen',
                'description'  => 'Unterlagen müssen nachgereicht werden.',
                'sortorder'    => 50,
                'active'       => 1,
                'isinitial'    => 0,
                'isaccepted'   => 0,
                'isrejected'   => 0,
                'timecreated'  => $now,
                'timemodified' => $now,
            ]);
        }

        upgrade_mod_savepoint(true, 2026061101, 'dhbwio');
    }

    if ($oldversion < 2026061102) {

        // Add within_deadline column to dhbwio_dataform_entries.
        $table = new xmldb_table('dhbwio_dataform_entries');
        $field = new xmldb_field('within_deadline', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'acceptedchoice');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Populate within_deadline for all existing entries.
        $entries = $DB->get_records('dhbwio_dataform_entries', null, '', 'id, dataid, timecreated');

        foreach ($entries as $entry) {
            // Find the dhbwio instance for this entry via dataform → course → dhbwio.
            $dataform = $DB->get_record('dhbwio_dataform', ['id' => $entry->dataid], 'course');
            if (!$dataform) {
                continue;
            }
            $dhbwio = $DB->get_record('dhbwio', ['course' => $dataform->course], 'id');
            if (!$dhbwio) {
                continue;
            }

            // Find the STUDIENGANG field for this dataid.
            $studiengang_field = $DB->get_record('dhbwio_dataform_fields', ['dataid' => $entry->dataid, 'name' => 'STUDIENGANG'], 'id');
            if (!$studiengang_field) {
                continue;
            }

            // Get the studiengang value from the entry's content.
            $content = $DB->get_record('dhbwio_dataform_contents', ['entryid' => $entry->id, 'fieldid' => $studiengang_field->id], 'content');
            if (!$content || empty($content->content)) {
                continue;
            }
            $studiengang = $content->content;

            // Look up a matching Bewerbungsfrist (prefer specific studiengang over 'alle').
            $frist = $DB->get_record_select(
                'dhbwio_fristen',
                "dhbwio = :dhbwio AND art = 'bewerbung' AND studiengang = :studiengang AND deadline IS NOT NULL",
                ['dhbwio' => $dhbwio->id, 'studiengang' => $studiengang]
            );
            if (!$frist) {
                $frist = $DB->get_record_select(
                    'dhbwio_fristen',
                    "dhbwio = :dhbwio AND art = 'bewerbung' AND studiengang = 'alle' AND deadline IS NOT NULL",
                    ['dhbwio' => $dhbwio->id]
                );
            }

            if ($frist && !empty($frist->deadline)) {
                $within = ($entry->timecreated <= (int)$frist->deadline) ? 1 : 0;
                $DB->set_field('dhbwio_dataform_entries', 'within_deadline', $within, ['id' => $entry->id]);
            }
            // If no frist found, leave the default value 1 (= rechtzeitig).
        }

        upgrade_mod_savepoint(true, 2026061102, 'dhbwio');
    }



    if ($oldversion < $newversion) {

        // Create dhbwio_la
        $table = new xmldb_table('dhbwio_la');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');
        $table->add_field('application_entryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');
        $table->add_field('lasteditedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('application_entryid_fk', XMLDB_KEY_FOREIGN, array('application_entryid'), 'dhbwio_dataform_entries', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create dhbwio_la_contents
        $table = new xmldb_table('dhbwio_la_contents');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('la_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, '');
        $table->add_field('vorname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, '');
        $table->add_field('studiengang', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, '');
        $table->add_field('studienrichtung', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, '');
        $table->add_field('wahlmodul', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, '');
        $table->add_field('gasthochschule', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, '');
        $table->add_field('zeitraum_von', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('zeitraum_bis', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('la_id_fk', XMLDB_KEY_FOREIGN, array('la_id'), 'dhbwio_la', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create dhbwio_la_module
        $table = new xmldb_table('dhbwio_la_module');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('la_contents_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');
        $table->add_field('modul_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, '');
        $table->add_field('ects', XMLDB_TYPE_CHAR, '50', null, false, '');
        $table->add_field('teilpruefungsanteil', XMLDB_TYPE_CHAR, '100', null, false, '');
        $table->add_field('anrechnungs_lv', XMLDB_TYPE_CHAR, '255', null, false, '');
        $table->add_field('credits', XMLDB_TYPE_CHAR, '50', null, false, '');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('la_contents_id_fk', XMLDB_KEY_FOREIGN, array('la_contents_id'), 'dhbwio_la_contents', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Savepoint
        upgrade_mod_savepoint(true, $newversion, 'dhbwio');
    }
    if ($oldversion <= 2026061702) { // Nutze hier deine nächste Plugin-Version
        $table = new xmldb_table('dhbwio_la');
        $field = new xmldb_field('application_entryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid'); // 'userid' ist das vorherige Feld

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026061703, 'dhbwio');
    }

    if ($oldversion < 2026062501) {
        $table = new xmldb_table('dhbwio_dataform_entries');

        $field = new xmldb_field(
            'acceptedchoice',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null,
            'statusid'
        );

        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
            $dbman->change_field_notnull($table, $field);
            $dbman->change_field_default($table, $field);
        }

        $DB->execute("
        UPDATE {dhbwio_dataform_entries}
           SET acceptedchoice = NULL
         WHERE acceptedchoice = 1
           AND statusid NOT IN (3, 4)
    ");

        upgrade_mod_savepoint(true, 2026062501, 'dhbwio');
    }

    return true;
}
