<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_benutzeransicht_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // New version for the LA tables
    $newversion = 2026060517;

    if ($oldversion < $newversion) {

        // Create benutzeransicht_la
        $table = new xmldb_table('benutzeransicht_la');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');
        $table->add_field('lasteditedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create benutzeransicht_la_contents
        $table = new xmldb_table('benutzeransicht_la_contents');
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
        $table->add_key('la_id_fk', XMLDB_KEY_FOREIGN, array('la_id'), 'benutzeransicht_la', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create benutzeransicht_la_module
        $table = new xmldb_table('benutzeransicht_la_module');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('la_contents_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, '0');
        $table->add_field('modul_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, '');
        $table->add_field('ects', XMLDB_TYPE_CHAR, '50', null, false, '');
        $table->add_field('teilpruefungsanteil', XMLDB_TYPE_CHAR, '100', null, false, '');
        $table->add_field('anrechnungs_lv', XMLDB_TYPE_CHAR, '255', null, false, '');
        $table->add_field('credits', XMLDB_TYPE_CHAR, '50', null, false, '');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('la_contents_id_fk', XMLDB_KEY_FOREIGN, array('la_contents_id'), 'benutzeransicht_la_contents', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Savepoint
        upgrade_plugin_savepoint(true, $newversion, 'mod', 'benutzeransicht');
    }

    return true;
}