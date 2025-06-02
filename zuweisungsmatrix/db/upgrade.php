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

function xmldb_zuweisungsmatrix_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    /*if ($oldversion < 2025052501) {

        // Tabelle definieren
        $table = new xmldb_table('local_zuweisungsmatrix');

        // Altes Feld definieren (wie es aktuell in der DB heißt)
        $oldfield = new xmldb_field('column', XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'row');

        // Neues Feld definieren (nur für Umbenennung)
        if ($dbman->field_exists($table, $oldfield)) {
            $dbman->rename_field($table, $oldfield, 'col');
        }

        // Upgrade-Savepoint setzen
        upgrade_plugin_savepoint(true, 2025052501, 'local', 'zuweisungsmatrix');
    }*/

    return true;
}
