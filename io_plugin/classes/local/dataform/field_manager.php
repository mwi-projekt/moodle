<?php

namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

/**
 * Verwaltungsklasse für Dataform-Felder und Felddefinitionen.
 *
 * Diese Klasse stellt zentrale Funktionen für den Zugriff auf die
 * Feldkonfiguration einer Dataform bereit. Sie ermöglicht das Laden,
 * Filtern und Auswerten von Felddefinitionen sowie die Verwaltung
 * von Feldgruppen und Sichtbarkeitsbereichen.
 *
 * Die Klasse bildet die Grundlage für die dynamische Generierung
 * von Formularen und die Verarbeitung von Bewerbungsdaten.
 *
 * Nutzen:
 * - Zentraler Zugriff auf Felddefinitionen
 * - Verwaltung von Feldgruppen und Feldtypen
 * - Unterstützung der dynamischen Formularerzeugung
 * - Einheitliche Behandlung von Sichtbarkeitsbereichen
 */
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

    /**
     * Lädt alle Felder einer Dataform.
     *
     * Die Felder werden nach Feldgruppe und Sortierreihenfolge
     * sortiert zurückgegeben.
     *
     * @param int $dataid ID der Dataform.
     * @return array Liste aller Felder.
     */
    public static function get_fields(int $dataid): array
    {
        global $DB;

        return $DB->get_records(
            'dhbwio_dataform_fields',
            ['dataid' => $dataid],
            'fieldgroup ASC, sortorder ASC, id ASC'
        );
    }
    /**
     * Lädt ein einzelnes Feld anhand seiner ID.
     *
     * @param int $fieldid ID des Feldes.
     * @return \stdClass|null Felddefinition oder null.
     */
    public static function get_field(int $fieldid): ?\stdClass
    {
        global $DB;

        return $DB->get_record(
            'dhbwio_dataform_fields',
            ['id' => $fieldid]
        ) ?: null;
    }
    /**
     * Liefert die Anzeigenamen aller Feldgruppen.
     *
     * Die Gruppen dienen der strukturierten Darstellung
     * von Formularfeldern innerhalb der Benutzeroberfläche.
     *
     * @return array Zuordnung von Gruppenschlüssel zu Anzeigename.
     */
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
    /**
     * Sucht ein Feld anhand seines technischen Namens.
     *
     * @param int $dataid ID der Dataform.
     * @param string $name Technischer Feldname.
     * @return \stdClass|null Gefundene Felddefinition oder null.
     */
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
    /**
     * Ermittelt die ID eines Feldes anhand seines Namens.
     *
     * @param int $dataid ID der Dataform.
     * @param string $name Technischer Feldname.
     * @return int|null ID des Feldes oder null.
     */
    public static function get_field_id_by_name(int $dataid, string $name): ?int
    {
        $field = self::get_field_by_name($dataid, $name);

        return $field ? (int) $field->id : null;
    }
    /**
     * Prüft, ob ein Feld innerhalb einer Dataform existiert.
     *
     * @param int $dataid ID der Dataform.
     * @param string $name Technischer Feldname.
     * @return bool True, wenn das Feld existiert.
     */
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
    /**
     * Lädt alle Felder eines bestimmten Typs.
     *
     * Beispiele für Feldtypen sind Textfelder,
     * Auswahlfelder oder Datumsfelder.
     *
     * @param int $dataid ID der Dataform.
     * @param string $type Gesuchter Feldtyp.
     * @return array Liste passender Felder.
     */
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
    /**
     * Liest die konfigurierten Auswahloptionen eines Feldes aus.
     *
     * Die Optionen werden zeilenweise aus der Feldkonfiguration
     * gelesen und als Array zurückgegeben.
     *
     * @param \stdClass $field Felddefinition.
     * @return array Verfügbare Auswahloptionen.
     */
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
    /**
     * Prüft, ob ein Feld für Studierende vorgesehen ist.
     *
     * @param \stdClass $field Felddefinition.
     * @return bool True, wenn das Feld dem Studentenbereich zugeordnet ist.
     */
    public static function is_student_field(\stdClass $field): bool
    {
        return ($field->scope ?? self::SCOPE_STUDENT) === self::SCOPE_STUDENT;
    }
    /**
     * Prüft, ob ein Feld für die Bewerbungsprüfung vorgesehen ist.
     *
     * Diese Felder werden typischerweise vom International Office
     * oder von Prüfenden bearbeitet.
     *
     * @param \stdClass $field Felddefinition.
     * @return bool True, wenn es sich um ein Prüfungsfeld handelt.
     */
    public static function is_review_field(\stdClass $field): bool
    {
        return ($field->scope ?? self::SCOPE_STUDENT) === self::SCOPE_REVIEW;
    }
    /**
     * Prüft, ob ein Feld als veraltet markiert wurde.
     *
     * Veraltete Felder werden aus Gründen der Kompatibilität
     * weiterhin gespeichert, sollen jedoch nicht mehr aktiv
     * verwendet werden.
     *
     * @param \stdClass $field Felddefinition.
     * @return bool True, wenn das Feld veraltet ist.
     */
    public static function is_deprecated_field(\stdClass $field): bool
    {
        return ($field->scope ?? self::SCOPE_STUDENT) === self::SCOPE_DEPRECATED;
    }
    /**
     * Prüft, ob ein Feld ausschließlich für interne Zwecke genutzt wird.
     *
     * Interne Felder werden nicht von Studierenden bearbeitet
     * und dienen beispielsweise technischen oder administrativen
     * Prozessen.
     *
     * @param \stdClass $field Felddefinition.
     * @return bool True, wenn es sich um ein internes Feld handelt.
     */
    public static function is_internal_field(\stdClass $field): bool
    {
        return !self::is_student_field($field);
    }
}
