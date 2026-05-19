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
 * Upgrade script for local_zuweisungsmatrix plugin
 * Automatically recreates missing or damaged tables
 *
 * @package   local_zuweisungsmatrix
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function for local_zuweisungsmatrix
 *
 * STRUCTURE MUST MATCH db/install.xml EXACTLY!
 * If install.xml changes, update this function accordingly.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_zuweisungsmatrix_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Version 2026051808: Repair/recreate missing tables
    if ($oldversion < 2026051808) {

        // === TABLE: local_matrixzuweisung_master (from install.xml) ===
        $table = new xmldb_table('local_matrixzuweisung_master');
        if (!$dbman->table_exists($table)) {
            // Fields (matching install.xml)
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, 'Primärschlüssel');
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, 'Name der Zuweisungsrunde');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, 'Erstellungszeit');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, 'Letzte Änderung');

            // Keys (matching install.xml)
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            $dbman->create_table($table);
        }

        // === TABLE: local_matrixzuweisung_details (from install.xml) ===
        $table = new xmldb_table('local_matrixzuweisung_details');
        if (!$dbman->table_exists($table)) {
            // Fields (matching install.xml)
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('masterid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, 'Referenz auf master-Tabelle');
            $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, 'ID des Studenten');
            $table->add_field('universityid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null, 'ID der Hochschule');

            // Keys (matching install.xml)
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('masterfk', XMLDB_KEY_FOREIGN, ['masterid'], 'local_matrixzuweisung_master', ['id']);

            // Indexes (matching install.xml)
            $table->add_index('studentidx', XMLDB_INDEX_NOTUNIQUE, ['studentid']);
            $table->add_index('universityidx', XMLDB_INDEX_NOTUNIQUE, ['universityid']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026051808, 'local', 'zuweisungsmatrix');
    }

    return true;
}

