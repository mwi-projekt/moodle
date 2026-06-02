<?php

namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

/**
 * Zentrale Verwaltungsklasse für Dataform-Instanzen.
 *
 * Diese Klasse kapselt sämtliche Datenbankzugriffe auf die
 * DHBWIO-Dataforms und stellt Methoden zum Erstellen, Lesen,
 * Aktualisieren und Löschen von Dataform-Datensätzen bereit.
 *
 * Durch die zentrale Bündelung der Datenzugriffe wird die
 * Geschäftslogik von direkten Datenbankoperationen getrennt
 * und die Wartbarkeit des Systems verbessert.
 *
 * Nutzen:
 * - Einheitlicher Zugriff auf Dataform-Daten
 * - Zentrale CRUD-Operationen (Create, Read, Update, Delete)
 * - Vermeidung redundanter Datenbankzugriffe
 * - Grundlage für die weitere Integration der Dataform-Funktionalität
 */
class dataform_manager
{

    /**
     * Lädt eine Dataform anhand ihrer ID.
     *
     * @param int $dataid ID der Dataform.
     * @return \stdClass|null Dataform-Datensatz oder null, falls nicht vorhanden.
     */
    public static function get_dataform(int $dataid): ?\stdClass
    {
        global $DB;

        return $DB->get_record(
            'dhbwio_dataform',
            ['id' => $dataid]
        ) ?: null;
    }
    /**
     * Lädt alle Dataforms eines Kurses.
     *
     * Die Dataforms werden nach ihrer ID sortiert zurückgegeben.
     *
     * @param int $courseid ID des Moodle-Kurses.
     * @return array Liste der Dataforms des Kurses.
     */
    public static function get_dataforms_by_course(int $courseid): array
    {
        global $DB;

        return $DB->get_records(
            'dhbwio_dataform',
            ['course' => $courseid],
            'id ASC'
        );
    }
    /**
     * Erstellt eine neue Dataform.
     *
     * Legt einen neuen Dataform-Datensatz für den angegebenen Kurs an
     * und speichert die grundlegenden Metadaten.
     *
     * @param int $courseid ID des zugehörigen Kurses.
     * @param string $name Anzeigename der Dataform.
     * @param string $intro Einleitungstext der Dataform.
     * @return int ID der neu angelegten Dataform.
     */
    public static function create_dataform(int $courseid, string $name, string $intro = ''): int
    {
        global $DB;

        $now = time();

        $record = (object) [
            'course' => $courseid,
            'name' => $name,
            'intro' => $intro,
            'introformat' => FORMAT_HTML,
            'timemodified' => $now,
        ];

        return $DB->insert_record('dhbwio_dataform', $record);
    }
    /**
     * Aktualisiert eine bestehende Dataform.
     *
     * Es werden ausschließlich freigegebene Felder übernommen.
     * Nicht vorhandene Dataforms führen zu einer Exception.
     *
     * @param int $dataid ID der zu aktualisierenden Dataform.
     * @param array $data Zu aktualisierende Felder und Werte.
     * @throws \moodle_exception Wenn die Dataform nicht existiert.
     * @return void
     */
    public static function update_dataform(int $dataid, array $data): void
    {
        global $DB;

        $existing = self::get_dataform($dataid);

        if (!$existing) {
            throw new \moodle_exception('invaliddataformid', 'mod_dhbwio');
        }

        $allowedfields = [
            'name',
            'intro',
            'introformat',
            'inlineview',
            'embedded',
            'timeavailable',
            'timedue',
            'timeinterval',
            'intervalcount',
            'grade',
            'gradeitems',
            'entrytypes',
            'maxentries',
            'entriesrequired',
            'individualized',
            'grouped',
            'anonymous',
            'timelimit',
            'css',
            'cssincludes',
            'js',
            'jsincludes',
            'defaultview',
            'defaultfilter',
            'completionentries',
            'completionspecificgrade',
        ];

        foreach ($allowedfields as $field) {
            if (array_key_exists($field, $data)) {
                $existing->{$field} = $data[$field];
            }
        }

        $existing->timemodified = time();

        $DB->update_record('dhbwio_dataform', $existing);
    }
    /**
     * Löscht eine Dataform einschließlich aller abhängigen Datensätze.
     *
     * Vor dem Entfernen der Dataform werden sämtliche zugehörigen
     * Inhalte, Einträge, Felder, Ansichten und Filter gelöscht.
     *
     * @param int $dataid ID der zu löschenden Dataform.
     * @return void
     */
    public static function delete_dataform(int $dataid): void
    {
        global $DB;

        $DB->delete_records('dhbwio_dataform_contents', ['dataid' => $dataid]);
        $DB->delete_records('dhbwio_dataform_entries', ['dataid' => $dataid]);
        $DB->delete_records('dhbwio_dataform_fields', ['dataid' => $dataid]);
        $DB->delete_records('dhbwio_dataform_views', ['dataid' => $dataid]);
        $DB->delete_records('dhbwio_dataform_filters', ['dataid' => $dataid]);
        $DB->delete_records('dhbwio_dataform', ['id' => $dataid]);
    }
    /**
     * Prüft, ob eine Dataform existiert.
     *
     * @param int $dataid ID der zu prüfenden Dataform.
     * @return bool True, wenn die Dataform existiert, sonst false.
     */
    public static function exists(int $dataid): bool
    {
        global $DB;

        return $DB->record_exists('dhbwio_dataform', ['id' => $dataid]);
    }
        /**
     * Gibt die dataform zum Kurs wieder.
     *
     * @param int $courseid ID der zu prüfenden Dataform.
     * @return \stdClass Dataform record.
     * @throws \moodle_exception falls kein dataform für diesen Kurs existiert.
     */
    public static function get_course_dataform(int $courseid): \stdClass {
    global $DB;

    $dataforms = $DB->get_records(
        'dhbwio_dataform',
        ['course' => $courseid],
        'id ASC',
        '*',
        0,
        1
    );

    $dataform = reset($dataforms);

    if (!$dataform) {
        throw new \moodle_exception('missingdataform', 'mod_dhbwio');
    }

    return $dataform;
}
}
