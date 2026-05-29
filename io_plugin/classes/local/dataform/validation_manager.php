<?php

namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

use mod_dhbwio\local\dataform\field_manager;

class validation_manager
{

    public static function validate(\stdClass $data, array $fields): array
    {
        $errors = [];

        foreach ($fields as $field) {
            if (!field_manager::is_student_field($field)) {
                continue;
            }

            $fieldname = self::get_form_field_name($field);
            $value = $data->{$fieldname} ?? null;
            if ($field->name === 'STATUS_BEWERBUNG') {
                continue;
            }

            if (self::is_required($field) && self::is_empty($value)) {
                $errors[$fieldname] = get_string('required');
                continue;
            }

            if (self::is_empty($value)) {
                continue;
            }

            switch ($field->type) {
                case 'text':
                    self::validate_text($field, $fieldname, $value, $errors);
                    break;

                case 'textarea':
                    self::validate_textarea($field, $fieldname, $value, $errors);
                    break;

                case 'select':
                case 'radiobutton':
                    self::validate_option($field, $fieldname, $value, $errors);
                    break;

                case 'time':
                    self::validate_time($field, $fieldname, $value, $errors);
                    break;
            }

            self::validate_param_rules($field, $fieldname, $value, $errors);
        }

        #self::validate_email_field($data, $fields, $errors);
        self::validate_privacy_acceptance($data, $fields, $errors);
        self::validate_unique_choices($data, $fields, $errors);

        return $errors;
    }

    private static function get_form_field_name(\stdClass $field): string
    {
        return 'field_' . $field->id;
    }

    private static function is_required(\stdClass $field): bool
    {
        $description = \core_text::strtolower($field->description ?? '');

        return strpos($description, 'verpflichtende angabe') !== false;
    }

    private static function is_empty($value): bool
    {
        if (is_array($value)) {
            return empty(array_filter($value, static function ($item) {
                return $item !== null && $item !== '';
            }));
        }

        return $value === null || $value === '';
    }

    private static function validate_text(\stdClass $field, string $fieldname, $value, array &$errors): void
    {
        if (\core_text::strlen((string) $value) > 255) {
            $errors[$fieldname] = get_string('maximumchars', '', 255);
        }
    }

    private static function validate_textarea(\stdClass $field, string $fieldname, $value, array &$errors): void
    {
        if (\core_text::strlen((string) $value) > 5000) {
            $errors[$fieldname] = get_string('maximumchars', '', 5000);
        }
    }

    private static function validate_option(\stdClass $field, string $fieldname, $value, array &$errors): void
    {
        if (self::is_empty($value)) {
            return;
        }

        if (self::is_university_choice_field($field)) {
            self::validate_university_option($field, $fieldname, $value, $errors);
            return;
        }

        $options = field_manager::get_field_options($field);

        if (empty($options)) {
            return;
        }

        if (!in_array((string) $value, $options, true)) {
            $errors[$fieldname] = get_string('invaliddata', 'error');
        }
    }
    private static function is_university_choice_field(\stdClass $field): bool
    {
        return in_array($field->name, ['ERSTWUNSCH', 'ZWEITWUNSCH', 'DRITTWUNSCH'], true);
    }

    private static function validate_university_option(\stdClass $field, string $fieldname, $value, array &$errors): void
    {
        global $DB;

        if (($field->name === 'ZWEITWUNSCH' || $field->name === 'DRITTWUNSCH') && $value === 'Keine') {
            return;
        }

        $exists = $DB->record_exists_select(
            'dhbwio_universities',
            "active = 1 AND " . $DB->sql_concat('country', "' - '", 'name') . " = :label",
            ['label' => (string) $value]
        );

        if (!$exists) {
            $errors[$fieldname] = get_string('invaliddata', 'error');
        }
    }

    private static function validate_time(\stdClass $field, string $fieldname, $value, array &$errors): void
    {
        if (!is_numeric($value) || (int) $value <= 0) {
            $errors[$fieldname] = get_string('invaliddate');
        }
    }

    private static function validate_email_field(\stdClass $data, array $fields, array &$errors): void
    {
        $field = self::find_field_by_name($fields, 'EMAIL');

        if (!$field) {
            return;
        }

        $fieldname = self::get_form_field_name($field);
        $value = $data->{$fieldname} ?? '';

        if (!self::is_empty($value) && !validate_email((string) $value)) {
            $errors[$fieldname] = get_string('invalidemail');
        }
    }

    private static function validate_privacy_acceptance(\stdClass $data, array $fields, array &$errors): void
    {
        $field = self::find_field_by_name($fields, 'EINVERSTAENDNISERKLAERUNG_DATENSCHUTZ');

        if (!$field) {
            return;
        }

        $fieldname = self::get_form_field_name($field);
        $value = $data->{$fieldname} ?? '';

        if (self::is_empty($value)) {
            $errors[$fieldname] = get_string('required');
            return;
        }

        $options = field_manager::get_field_options($field);

        if (!empty($options) && !in_array((string) $value, $options, true)) {
            $errors[$fieldname] = get_string('invaliddata', 'error');
        }
    }

    private static function validate_unique_choices(\stdClass $data, array $fields, array &$errors): void
    {
        $choicenames = [
            'ERSTWUNSCH',
            'ZWEITWUNSCH',
            'DRITTWUNSCH',
        ];

        $values = [];

        foreach ($choicenames as $choicename) {
            $field = self::find_field_by_name($fields, $choicename);

            if (!$field) {
                continue;
            }

            $fieldname = self::get_form_field_name($field);
            $value = $data->{$fieldname} ?? '';

            if (self::is_empty($value)) {
                continue;
            }

            if (\core_text::strtolower(trim((string) $value)) === 'keine') {
                continue;
            }

            $normalized = \core_text::strtolower(trim((string) $value));

            if (isset($values[$normalized])) {
                $errors[$fieldname] = 'Diese Auswahl wurde bereits bei einem anderen Wunsch verwendet.';
            }

            $values[$normalized] = true;
        }
    }

    private static function find_field_by_name(array $fields, string $name): ?\stdClass
    {
        foreach ($fields as $field) {
            if (($field->name ?? '') === $name) {
                return $field;
            }
        }

        return null;
    }

    private static function validate_param_rules(\stdClass $field, string $fieldname, $value, array &$errors): void
    {
        if (self::is_empty($value)) {
            return;
        }

        $rule = trim((string) ($field->param4 ?? ''));

        if ($rule === '') {
            return;
        }

        switch ($rule) {
            case 'email':
                if (!validate_email((string) $value)) {
                    $errors[$fieldname] = get_string('invalidemail');
                }
                break;

            case 'numeric':
                if (!preg_match('/^[0-9]+$/', (string) $value)) {
                    $errors[$fieldname] = 'Bitte nur Zahlen eingeben.';
                }
                break;

            case 'lettersonly':
                if (!preg_match('/^[\p{L}\s\-]+$/u', (string) $value)) {
                    $errors[$fieldname] = 'Bitte nur Buchstaben eingeben.';
                }
                break;

            case 'alphanumeric':
                if (!preg_match('/^[\p{L}\p{N}\s\-]+$/u', (string) $value)) {
                    $errors[$fieldname] = 'Bitte nur Buchstaben und Zahlen eingeben.';
                }
                break;
        }
    }
}
