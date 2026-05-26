<?php

namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

class field_manager
{


    private const INTERNAL_FIELDS = [
        'STATUS_BEWERBUNG',
        'KOMMENTAR_IO',
        'SGL_HOCHSCHULZIEL_ERLAUBNIS_ERST',
        'SGL_HOCHSCHULZIEL_ERLAUBNIS_ZWEIT',
        'SGL_HOCHSCHULZIEL_ERLAUBNIS_DRITT',
    ];
    public static function get_fields(int $dataid): array
    {
        global $DB;

        return $DB->get_records(
            'dhbwio_dataform_fields',
            ['dataid' => $dataid],
            'id ASC'
        );
    }

    public static function get_field(int $fieldid): ?\stdClass
    {
        global $DB;

        return $DB->get_record(
            'dhbwio_dataform_fields',
            ['id' => $fieldid]
        ) ?: null;
    }

    public static function get_field_by_name(int $dataid, string $name): ?\stdClass
    {
        global $DB;

        return $DB->get_record(
            'dhbwio_dataform_fields',
            [
                'dataid' => $dataid,
                'name' => $name,
            ]
        ) ?: null;
    }

    public static function get_field_id_by_name(int $dataid, string $name): ?int
    {
        $field = self::get_field_by_name($dataid, $name);

        return $field ? (int) $field->id : null;
    }

    public static function field_exists(int $dataid, string $name): bool
    {
        global $DB;

        return $DB->record_exists(
            'dhbwio_dataform_fields',
            [
                'dataid' => $dataid,
                'name' => $name,
            ]
        );
    }

    public static function get_fields_by_type(int $dataid, string $type): array
    {
        global $DB;

        return $DB->get_records(
            'dhbwio_dataform_fields',
            [
                'dataid' => $dataid,
                'type' => $type,
            ],
            'id ASC'
        );
    }

    public static function get_field_options(\stdClass $field): array
    {
        if (empty($field->param1)) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($field->param1));
        $options = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $options[] = $line;
        }

        return $options;
    }
    public static function is_internal_field(string $fieldname): bool
    {
        return in_array($fieldname, self::INTERNAL_FIELDS, true);
    }
}
