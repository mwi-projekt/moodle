<?php

namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

class field_manager
{
    public const SCOPE_STUDENT = 'student';
    public const SCOPE_REVIEW = 'review';
    public const SCOPE_DEPRECATED = 'deprecated';
    public const SCOPE_INTERNAL = 'internal';
    public const GROUP_GENERAL = 'general';
    public const GROUP_PERSONAL = 'personal';
    public const GROUP_ADDRESS = 'address';
    public const GROUP_STUDY = 'study';
    public const GROUP_COMPANY = 'company';
    public const GROUP_CHOICES = 'choices';
    public const GROUP_STATEMENTS = 'statements';
    public const GROUP_DOCUMENTS = 'documents';
    public const GROUP_REVIEW = 'review';
    public const GROUP_TECHNICAL = 'technical';

    public static function get_fields(int $dataid): array
    {
        global $DB;

        return $DB->get_records(
            'dhbwio_dataform_fields',
            ['dataid' => $dataid],
            'fieldgroup ASC, sortorder ASC, id ASC'
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

    public static function get_group_titles(): array
    {
        return [
            self::GROUP_PERSONAL => 'Persönliche Daten',
            self::GROUP_ADDRESS => 'Adresse',
            self::GROUP_STUDY => 'Studium',
            self::GROUP_COMPANY => 'Unternehmen',
            self::GROUP_CHOICES => 'Wunschhochschulen',
            self::GROUP_STATEMENTS => 'Erklärungen',
            self::GROUP_DOCUMENTS => 'Dokumente',
            self::GROUP_REVIEW => 'Prüfung',
            self::GROUP_TECHNICAL => 'Technisch',
            self::GROUP_GENERAL => 'Allgemeine Angaben',
        ];
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

    public static function is_student_field(\stdClass $field): bool
    {
        return ($field->scope ?? self::SCOPE_STUDENT) === self::SCOPE_STUDENT;
    }

    public static function is_review_field(\stdClass $field): bool
    {
        return ($field->scope ?? self::SCOPE_STUDENT) === self::SCOPE_REVIEW;
    }

    public static function is_deprecated_field(\stdClass $field): bool
    {
        return ($field->scope ?? self::SCOPE_STUDENT) === self::SCOPE_DEPRECATED;
    }

    public static function is_internal_field(\stdClass $field): bool
    {
        return !self::is_student_field($field);
    }
}
