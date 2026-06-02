<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

use html_writer;

/**
 * Renders HTML form fields based on field definitions.
 */
class form_renderer
{
    /**
     * Renders a field as HTML.
     *
     * @param \stdClass $field Field definition.
     * @param string $value Current field value.
     * @param string $error Validation error message.
     * @return string Rendered HTML.
     */
    public static function render_field(
        \stdClass $field,
        string $value = '',
        string $error = '',
        bool $applicationaccepted = false
    ): string {
        $islockedchoicefield = $applicationaccepted && in_array($field->name, [
            'ERSTWUNSCH',
            'ZWEITWUNSCH',
            'DRITTWUNSCH',
        ], true);

        $name = 'field_' . $field->id;

        $label = $field->description ?: $field->name;
        $label = self::get_display_label($field);
        $isrequired = self::is_required($field);

        if ($isrequired) {
            $label .= ' *';
        }

        $inputclass = empty($error) ? 'form-control' : 'form-control is-invalid';
        $selectclass = empty($error) ? 'form-select' : 'form-select is-invalid';

        $html = '';

        $html .= html_writer::start_div('dhbwio-form-row');
        $html .= html_writer::start_div('row align-items-start');
        $html .= html_writer::div(
            html_writer::tag('label', s($label), [
                'for' => $name,
                'class' => 'dhbwio-form-label',
            ]),
            'col-md-3'
        );

        $html .= html_writer::start_div('col-md-6');

        switch ($field->type) {

            case 'textarea':
                $html .= html_writer::tag(
                    'textarea',
                    s($value),
                    [
                        'name' => $name,
                        'id' => $name,
                        'rows' => 3,
                        'class' => $inputclass . ' dhbwio-textarea'
                    ]
                );
                break;

            case 'select':

                if ($islockedchoicefield) {
                    $html .= html_writer::empty_tag('input', [
                        'type' => 'hidden',
                        'name' => $name,
                        'value' => $value,
                    ]);
                }
                $options = self::is_university_choice_field($field)
                    ? self::get_university_options($field->name)
                    : self::get_options_from_field($field);

                $html .= self::render_select(
                    $name,
                    $options,
                    $value,
                    $selectclass,
                    $islockedchoicefield
                );
                break;

            case 'radiobutton':
                $html .= self::render_radio_group(
                    $name,
                    self::get_options_from_field($field),
                    $value,
                    !empty($error)
                );
                break;

            case 'time':

                $datevalue = '';

                if (!empty($value) && is_numeric($value)) {
                    $datevalue = date('Y-m-d', (int)$value);
                }

                $html .= html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'date',
                        'name' => $name,
                        'id' => $name,
                        'value' => $datevalue,
                        'class' => $inputclass
                    ]
                );
                break;

            case 'text':
            default:

                $attributes = [
                    'type' => 'text',
                    'name' => $name,
                    'id' => $name,
                    'value' => $value,
                    'class' => $inputclass,
                ];

                if (($field->param4 ?? '') === 'email') {
                    $attributes['type'] = 'email';
                    $attributes['placeholder'] = 'nachname.vorname.kurs@dh-karlsruhe.de';
                }

                $html .= html_writer::empty_tag('input', $attributes);

                break;
        }

        if (!empty($error)) {
            $html .= html_writer::div(
                s($error),
                'invalid-feedback d-block'
            );
        }

        $html .= html_writer::end_div(); // col-md-8
        $html .= html_writer::end_div(); // row
        $html .= html_writer::end_div(); // dhbwio-form-row

        return $html;
    }

    /**
     * Renders a select field.
     *
     * @param string $name Input name.
     * @param array $options Available options.
     * @param string $selected Selected value.
     * @return string
     */
    private static function render_select(
        string $name,
        array $options,
        string $selected,
        string $class = 'form-select',
        bool $disabled = false
    ): string {

        $selectattributes = [
            'name' => $name,
            'id' => $name,
            'class' => $class,
        ];

        if ($disabled) {
            $selectattributes['disabled'] = 'disabled';
        }

        $html = html_writer::start_tag('select', $selectattributes);

        foreach ($options as $value => $label) {
            $optionattributes = [
                'value' => $value,
            ];

            if ((string)$value === (string)$selected) {
                $optionattributes['selected'] = 'selected';
            }

            $html .= html_writer::tag(
                'option',
                s($label),
                $optionattributes
            );
        }

        $html .= html_writer::end_tag('select');

        return $html;
    }

    /**
     * Renders a radio button group.
     *
     * @param string $name Input name.
     * @param array $options Available options.
     * @param string $selected Selected value.
     * @return string
     */
    private static function render_radio_group(
        string $name,
        array $options,
        string $selected,
        bool $haserror = false
    ): string {

        $html = '';

        $html .= html_writer::start_div($haserror ? 'dhbwio-radio-group is-invalid' : 'dhbwio-radio-group');

        foreach ($options as $value => $label) {

            $id = $name . '_' . md5($value);

            $attributes = [
                'type' => 'radio',
                'name' => $name,
                'id' => $id,
                'value' => $value,
                'class' => 'form-check-input'
            ];

            if ((string)$value === (string)$selected) {
                $attributes['checked'] = 'checked';
            }

            $html .= html_writer::start_div('form-check');

            $html .= html_writer::empty_tag(
                'input',
                $attributes
            );

            $html .= html_writer::tag(
                'label',
                s($label),
                [
                    'for' => $id,
                    'class' => 'form-check-label'
                ]
            );

            $html .= html_writer::end_div();
        }

        $html .= html_writer::end_div();

        return $html;
    }

    /**
     * Returns field options from param1.
     *
     * @param \stdClass $field Field definition.
     * @return array
     */
    private static function get_options_from_field(
        \stdClass $field
    ): array {

        $options = [];

        if (empty($field->param1)) {
            return $options;
        }

        $lines = preg_split(
            '/\r\n|\r|\n/',
            trim($field->param1)
        );

        foreach ($lines as $line) {

            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $options[$line] = $line;
        }

        return $options;
    }
    private static function is_university_choice_field(\stdClass $field): bool
    {
        return in_array($field->name, ['ERSTWUNSCH', 'ZWEITWUNSCH', 'DRITTWUNSCH'], true);
    }

    private static function get_university_options(string $fieldname): array
    {
        global $DB;

        $options = [];

        if ($fieldname === 'ZWEITWUNSCH' || $fieldname === 'DRITTWUNSCH') {
            $options['Keine'] = 'Keine';
        }

        $universities = $DB->get_records(
            'dhbwio_universities',
            ['active' => 1],
            'country ASC, name ASC'
        );

        foreach ($universities as $university) {
            $label = trim($university->country . ' - ' . $university->name);
            $options[$label] = $label;
        }

        return $options;
    }
    private static function clean_label(string $label): string
    {
        $label = str_replace('(verpflichtende Angabe)', '', $label);
        $label = str_replace('(verplichtende Angabe)', '', $label);
        $label = str_replace('(optionale Angabe)', '', $label);

        return trim($label);
    }
    private static function get_help_text(\stdClass $field): string
    {
        $description = $field->description ?? '';

        if (preg_match('/^(.*?)\((?:verpflichtende|verplichtende|optionale).*$/i', $description, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }
    private static function is_required(\stdClass $field): bool
    {
        $description = \core_text::strtolower($field->description ?? '');

        return strpos($description, 'verpflichtende angabe') !== false
            || strpos($description, 'verplichtende angabe') !== false;
    }

    // Diese Function dient als Übergang bevor tatsächliche Datenbankänderungen der Description vorgenommen werden.
    private static function get_display_label(\stdClass $field): string
    {
        $labels = [
            'NACHNAME' => 'Nachname',
            'VORNAME' => 'Vorname',
            'GEBURTSDATUM' => 'Geburtsdatum',
            'EMAIL' => 'DHBW-E-Mail-Adresse',
            'ERSTWUNSCH' => 'Erstwunsch Hochschule',
            'ZWEITWUNSCH' => 'Zweitwunsch Hochschule',
            'DRITTWUNSCH' => 'Drittwunsch Hochschule',
            'ABSPRACHE_MIT_UNTERNEHMEN' => 'Absprache mit Unternehmen',
            'ABSPRACHE_MIT_STUDIENGANGSLEITUNG' => 'Absprache mit Studiengangsleitung',
        ];

        if (isset($labels[$field->name])) {
            return $labels[$field->name];
        }

        return self::clean_label($field->description ?: $field->name);
    }
}
