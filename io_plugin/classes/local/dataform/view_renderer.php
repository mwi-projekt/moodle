<?php

namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

use html_writer;

/**
 * Renders read-only field values for application views.
 */
class view_renderer
{

    /**
     * Renders a field value as read-only HTML.
     *
     * @param \stdClass $field Field definition record.
     * @param string $value Stored field value.
     * @return string Rendered HTML.
     */
    public static function render_field(
        \stdClass $field,
        string $value = '',
        bool $alternate = false
    ): string {
        $label = self::get_display_label($field);
        $value = trim($value) !== '' ? $value : '-';
        if ($field->type === 'time' && is_numeric($value)) {
            $value = userdate((int) $value, get_string('strftimedatefullshort'));
        }
        if (self::is_university_choice_field($field) && $value !== '-' && is_numeric($value)) {
            $value = self::get_university_label((int) $value);
        }
        $rowclass = $alternate
            ? 'dhbwio-view-row dhbwio-view-row-alt row'
            : 'dhbwio-view-row row';

        $html = html_writer::start_div($rowclass);

        $html .= html_writer::div(
            html_writer::tag('strong', s($label)),
            'col-md-3 dhbwio-view-label'
        );

        $html .= html_writer::div(
            s($value),
            'col-md-8 dhbwio-view-value'
        );

        $html .= html_writer::end_div();

        return $html;
    }

    /**
     * Returns a cleaned display label for a field.
     *
     * @param \stdClass $field Field definition record.
     * @return string Display label.
     */
    private static function get_display_label(\stdClass $field): string
    {
        $labels = [
            'NACHNAME' => 'Nachname',
            'VORNAME' => 'Vorname',
            'GEBURTSDATUM' => 'Geburtsdatum',
            'NATIONALITAET' => 'Nationalität',
            'MUTTERSPRACHE' => 'Muttersprache',
            'EMAIL' => 'DHBW-E-Mail-Adresse',

            'STUDIENGANG' => 'Studiengang',
            'STUDIENRICHTUNG' => 'Studienrichtung',
            'KURSNAME' => 'Kurs',
            'STUDIENGANGSLEITUNG' => 'Studiengangsleitung',
            'ABSPRACHE_MIT_STUDIENGANGSLEITUNG' => 'Absprache mit Studiengangsleitung',
            'AKTUELLES_SEMESTER' => 'Aktuelles Semester',

            'UNTERNEHMEN' => 'Unternehmen',
            'ANSPRECHPERSON_UNTERNEHMEN' => 'Ansprechperson im Unternehmen',
            'ANSPRECHPERSON_EMAIL' => 'E-Mail der Ansprechperson',
            'ABSPRACHE_MIT_UNTERNEHMEN' => 'Absprache mit Unternehmen',

            'ERSTWUNSCH' => 'Erstwunsch Hochschule',
            'ZWEITWUNSCH' => 'Zweitwunsch Hochschule',
            'DRITTWUNSCH' => 'Drittwunsch Hochschule',

            'BENACHTEILIGUNG_BILDUNGSCHANCEN' => 'Benachteiligung Bildungschancen',
            'NACHRICHT' => 'Nachricht an das International Office',
            'VEROEFFENTLICHUNG_MAILADRESSE_UND_BERICHT' => 'Veröffentlichung Mailadresse und Bericht',
            'EINVERSTAENDNISERKLAERUNG_DATENSCHUTZ' => 'Einverständniserklärung Datenschutz',
        ];

        if (isset($labels[$field->name])) {
            return $labels[$field->name];
        }

        return self::clean_label($field->description ?: $field->name);
    }

    /**
     * Removes technical markers from a label.
     *
     * @param string $label Raw label.
     * @return string Cleaned label.
     */
    private static function clean_label(string $label): string
    {
        $label = str_replace('(verpflichtende Angabe)', '', $label);
        $label = str_replace('(verplichtende Angabe)', '', $label);
        $label = str_replace('(optionale Angabe)', '', $label);

        return trim($label);
    }
        private static function is_university_choice_field(\stdClass $field): bool
    {
        return in_array($field->name, [
            'ERSTWUNSCH',
            'ZWEITWUNSCH',
            'DRITTWUNSCH',
        ], true);
    }

    private static function get_university_label(int $universityid): string
    {
        global $DB;

        $university = $DB->get_record(
            'dhbwio_universities',
            ['id' => $universityid],
            '*',
            IGNORE_MISSING
        );

        if (!$university) {
            return '-';
        }

        return trim($university->name);
    }
}
