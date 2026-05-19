<?php
namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

class dataform_manager {

    public static function get_dataform(int $dataid): ?\stdClass {
        global $DB;

        return $DB->get_record(
            'dhbwio_dataform',
            ['id' => $dataid]
        ) ?: null;
    }

public static function get_dataforms_by_course(int $courseid): array {
    global $DB;

    return $DB->get_records(
        'dhbwio_dataform',
        ['course' => $courseid],
        'id ASC'
    );
}

public static function create_dataform(int $courseid, string $name, string $intro = ''): int {
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

    public static function update_dataform(int $dataid, array $data): void {
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

    public static function delete_dataform(int $dataid): void {
        global $DB;

        $DB->delete_records('dhbwio_dataform_contents', ['dataid' => $dataid]);
        $DB->delete_records('dhbwio_dataform_entries', ['dataid' => $dataid]);
        $DB->delete_records('dhbwio_dataform_fields', ['dataid' => $dataid]);
        $DB->delete_records('dhbwio_dataform_views', ['dataid' => $dataid]);
        $DB->delete_records('dhbwio_dataform_filters', ['dataid' => $dataid]);
        $DB->delete_records('dhbwio_dataform', ['id' => $dataid]);
    }

    public static function exists(int $dataid): bool {
        global $DB;

        return $DB->record_exists('dhbwio_dataform', ['id' => $dataid]);
    }
}