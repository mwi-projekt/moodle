<?php

namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

use mod_dhbwio\local\dataform\field_manager;

/**
 * Zentrale Validierungsklasse für Bewerbungsformulare.
 *
 * Diese Klasse führt sämtliche fachlichen und technischen Prüfungen
 * der vom Bewerber eingegebenen Daten durch. Die Validierungsregeln
 * orientieren sich an den konfigurierten Felddefinitionen und werden
 * unabhängig von der Formularimplementierung ausgeführt.
 *
 * Neben allgemeinen Prüfungen wie Pflichtfeldern, Textlängen und
 * Auswahlwerten werden auch projektspezifische Regeln, beispielsweise
 * für Hochschulwünsche oder Datenschutzerklärungen, berücksichtigt.
 *
 * Nutzen:
 * - Zentrale Verwaltung aller Validierungsregeln
 * - Einheitliche Prüfung von Bewerbungsdaten
 * - Trennung von Formular- und Validierungslogik
 * - Einfache Erweiterbarkeit um zusätzliche Regeln
 */
class validation_manager
{
    /**
     * Führt die vollständige Validierung eines Bewerbungsformulars durch.
     *
     * Alle für Studierende sichtbaren Felder werden anhand ihrer
     * Felddefinition geprüft. Zusätzlich werden spezielle
     * Geschäftsregeln wie Datenschutzbestätigungen oder die
     * Eindeutigkeit von Hochschulwünschen kontrolliert.
     *
     * @param \stdClass $data Übermittelte Formulardaten.
     * @param array $fields Verfügbare Felddefinitionen.
     * @return array Liste gefundener Validierungsfehler.
     */
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
    /**
     * Ermittelt den technischen Namen eines Formularfeldes.
     *
     * Der Name wird auf Basis der Feld-ID erzeugt und dient
     * der Zuordnung zwischen Formularwerten und Felddefinitionen.
     *
     * @param \stdClass $field Felddefinition.
     * @return string Technischer Formularname.
     */
    private static function get_form_field_name(\stdClass $field): string
    {
        return 'field_' . $field->id;
    }
    /**
     * Prüft, ob ein Feld als Pflichtfeld definiert ist.
     *
     * Die Information wird aus der Feldbeschreibung ermittelt.
     *
     * @param \stdClass $field Felddefinition.
     * @return bool True, wenn das Feld verpflichtend ist.
     */
    private static function is_required(\stdClass $field): bool
    {
        $description = \core_text::strtolower($field->description ?? '');

        return strpos($description, 'verpflichtende angabe') !== false;
    }
    /**
     * Prüft, ob ein übergebener Wert leer ist.
     *
     * Unterstützt sowohl skalare Werte als auch Arrays.
     *
     * @param mixed $value Zu prüfender Wert.
     * @return bool True, wenn kein gültiger Inhalt vorhanden ist.
     */
    private static function is_empty($value): bool
    {
        if (is_array($value)) {
            return empty(array_filter($value, static function ($item) {
                return $item !== null && $item !== '';
            }));
        }

        return $value === null || $value === '';
    }
    /**
     * Validiert ein Textfeld.
     *
     * Die maximale Länge eines Textfeldes beträgt 255 Zeichen.
     *
     * @param \stdClass $field Felddefinition.
     * @param string $fieldname Formularfeldname.
     * @param mixed $value Zu prüfender Wert.
     * @param array $errors Fehlerliste.
     * @return void
     */
    private static function validate_text(\stdClass $field, string $fieldname, $value, array &$errors): void
    {
        if (\core_text::strlen((string) $value) > 255) {
            $errors[$fieldname] = get_string('maximumchars', '', 255);
        }
    }
    /**
     * Validiert ein mehrzeiliges Textfeld.
     *
     * Die maximale Länge eines Textbereichs beträgt 5000 Zeichen.
     *
     * @param \stdClass $field Felddefinition.
     * @param string $fieldname Formularfeldname.
     * @param mixed $value Zu prüfender Wert.
     * @param array $errors Fehlerliste.
     * @return void
     */
    private static function validate_textarea(\stdClass $field, string $fieldname, $value, array &$errors): void
    {
        if (\core_text::strlen((string) $value) > 5000) {
            $errors[$fieldname] = get_string('maximumchars', '', 5000);
        }
    }
    /**
     * Prüft die Gültigkeit eines Auswahlwertes.
     *
     * Für Standard-Auswahlfelder wird geprüft, ob der gewählte
     * Wert in den konfigurierten Optionen enthalten ist.
     * Hochschulwünsche werden gesondert behandelt.
     *
     * @param \stdClass $field Felddefinition.
     * @param string $fieldname Formularfeldname.
     * @param mixed $value Ausgewählter Wert.
     * @param array $errors Fehlerliste.
     * @return void
     */
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
    /**
     * Prüft, ob ein Feld einen Hochschulwunsch repräsentiert.
     *
     * Unterstützt Erst-, Zweit- und Drittwunsch.
     *
     * @param \stdClass $field Felddefinition.
     * @return bool True bei einem Wunschfeld.
     */
    private static function is_university_choice_field(\stdClass $field): bool
    {
        return in_array($field->name, ['ERSTWUNSCH', 'ZWEITWUNSCH', 'DRITTWUNSCH'], true);
    }
    /**
     * Validiert die Auswahl einer Partnerhochschule.
     *
     * Es wird geprüft, ob die ausgewählte Hochschule als aktive
     * Hochschule im System vorhanden ist.
     *
     * @param \stdClass $field Felddefinition.
     * @param string $fieldname Formularfeldname.
     * @param mixed $value Gewählter Wert.
     * @param array $errors Fehlerliste.
     * @return void
     */
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
    /**
     * Prüft die Gültigkeit eines Datums- bzw. Zeitwertes.
     *
     * Der Wert muss numerisch sein und einen gültigen
     * Zeitstempel repräsentieren.
     *
     * @param \stdClass $field Felddefinition.
     * @param string $fieldname Formularfeldname.
     * @param mixed $value Zu prüfender Wert.
     * @param array $errors Fehlerliste.
     * @return void
     */
    private static function validate_time(\stdClass $field, string $fieldname, $value, array &$errors): void
    {
        if (!is_numeric($value) || (int) $value <= 0) {
            $errors[$fieldname] = get_string('invaliddate');
        }
    }
    /**
     * Validiert die E-Mail-Adresse eines Bewerbers.
     *
     * Die Methode prüft, ob das Feld EMAIL vorhanden ist und
     * eine syntaktisch gültige E-Mail-Adresse enthält.
     *
     * @param \stdClass $data Formulardaten.
     * @param array $fields Felddefinitionen.
     * @param array $errors Fehlerliste.
     * @return void
     */
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
    /**
     * Prüft die Zustimmung zur Datenschutzerklärung.
     *
     * Die Zustimmung ist verpflichtend und muss einer
     * gültigen Auswahloption entsprechen.
     *
     * @param \stdClass $data Formulardaten.
     * @param array $fields Felddefinitionen.
     * @param array $errors Fehlerliste.
     * @return void
     */
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
    /**
     * Prüft die Eindeutigkeit der Hochschulwünsche.
     *
     * Eine Hochschule darf innerhalb einer Bewerbung nur
     * einmal ausgewählt werden. Mehrfachnennungen werden
     * als Fehler markiert.
     *
     * @param \stdClass $data Formulardaten.
     * @param array $fields Felddefinitionen.
     * @param array $errors Fehlerliste.
     * @return void
     */
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
    /**
     * Sucht ein Feld anhand seines technischen Namens.
     *
     * @param array $fields Liste aller Felddefinitionen.
     * @param string $name Gesuchter Feldname.
     * @return \stdClass|null Gefundenes Feld oder null.
     */
    private static function find_field_by_name(array $fields, string $name): ?\stdClass
    {
        foreach ($fields as $field) {
            if (($field->name ?? '') === $name) {
                return $field;
            }
        }

        return null;
    }
    /**
     * Führt feldspezifische Validierungsregeln aus.
     *
     * Unterstützt aktuell unter anderem:
     * - E-Mail-Adressen
     * - Numerische Werte
     * - Nur Buchstaben
     * - Alphanumerische Werte
     *
     * Die Regel wird über die Feldkonfiguration definiert.
     *
     * @param \stdClass $field Felddefinition.
     * @param string $fieldname Formularfeldname.
     * @param mixed $value Zu prüfender Wert.
     * @param array $errors Fehlerliste.
     * @return void
     */
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
