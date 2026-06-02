<?php

namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

/**
 * Verwaltungsklasse für Bewerbungsstatus.
 *
 * Diese Klasse stellt zentrale Funktionen zur Verwaltung und
 * Auswertung von Bewerbungsstatus bereit. Sie ermöglicht das
 * Laden einzelner Statuswerte, das Ermitteln des Initialstatus
 * sowie die Bereitstellung aktiver Statusoptionen für Formulare.
 *
 * Die Statuswerte bilden den Bearbeitungsfortschritt einer
 * Bewerbung ab und werden im gesamten Bewerbungsprozess genutzt.
 *
 * Nutzen:
 * - Zentrale Verwaltung von Bewerbungsstatus
 * - Einheitliche Statusabfragen im System
 * - Bereitstellung von Formularoptionen
 * - Unterstützung des Bewerbungs-Workflows
 */
class status_manager
{

    /**
     * Lädt einen Bewerbungsstatus anhand seiner ID.
     *
     * @param int $statusid ID des Status.
     * @return \stdClass|null Statusdatensatz oder null.
     */
    public static function get_status(int $statusid): ?\stdClass
    {
        global $DB;

        return $DB->get_record(
            'dhbwio_application_status',
            ['id' => $statusid]
        ) ?: null;
    }
    /**
     * Ermittelt den initialen Status einer neuen Bewerbung.
     *
     * Der Initialstatus wird automatisch beim Anlegen einer
     * Bewerbung vergeben und kennzeichnet den Einstiegspunkt
     * im Bewerbungsprozess.
     *
     * @return \stdClass Initialer Statusdatensatz.
     * @throws \dml_missing_record_exception Wenn kein Initialstatus existiert.
     */
    public static function get_initial_status(): \stdClass
    {
        global $DB;

        return $DB->get_record(
            'dhbwio_application_status',
            ['isinitial' => 1, 'active' => 1],
            '*',
            MUST_EXIST
        );
    }
    /**
     * Lädt alle aktiven Bewerbungsstatus.
     *
     * Die Statuswerte werden entsprechend ihrer konfigurierten
     * Sortierreihenfolge zurückgegeben.
     *
     * @return array Liste aller aktiven Status.
     */
    public static function get_active_statuses(): array
    {
        global $DB;

        return $DB->get_records(
            'dhbwio_application_status',
            ['active' => 1],
            'sortorder ASC'
        );
    }
    /**
     * Erstellt eine Auswahlliste aktiver Statuswerte.
     *
     * Die Methode bereitet die Statusdaten für die Verwendung
     * in Moodle-Auswahlfeldern auf.
     *
     * Rückgabeformat:
     * [Status-ID => Statusbezeichnung]
     *
     * @return array Verfügbare Statusoptionen.
     */
    public static function get_options(): array
    {
        $statuses = self::get_active_statuses();

        $options = [];

        foreach ($statuses as $status) {
            $options[$status->id] = $status->label;
        }

        return $options;
    }
    /**
     * Prüft, ob ein Status als akzeptiert markiert ist.
     *
     * Diese Information kann genutzt werden, um Bewerbungen
     * automatisch als angenommen oder erfolgreich bewertet
     * zu behandeln.
     *
     * @param int $statusid ID des zu prüfenden Status.
     * @return bool True, wenn der Status als akzeptiert definiert ist.
     */
    public static function is_accepted(int $statusid): bool
    {
        $status = self::get_status($statusid);

        return $status && (int) $status->isaccepted === 1;
    }
}
