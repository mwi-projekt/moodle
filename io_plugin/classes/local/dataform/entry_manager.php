<?php

namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();
/**
 * Verwaltungsklasse für Bewerbungs- und Dataform-Einträge.
 *
 * Diese Klasse kapselt sämtliche Operationen auf Einträgen einer
 * Dataform. Sie ermöglicht das Anlegen, Aktualisieren, Auslesen
 * und Löschen von Bewerbungen sowie die Verwaltung der zugehörigen
 * Feldinhalte.
 *
 * Ein Eintrag repräsentiert dabei eine vollständige Bewerbung eines
 * Studierenden innerhalb einer Dataform. Die eigentlichen
 * Formularwerte werden getrennt in den Content-Datensätzen gespeichert.
 *
 * Nutzen:
 * - Zentrale Verwaltung von Bewerbungen
 * - Einheitlicher Zugriff auf Eintragsdaten
 * - Trennung von Einträgen und Feldinhalten
 * - Vereinfachung der Datenhaltung und Wartung
 */
class entry_manager
{
    /**
     * Erstellt einen neuen Eintrag für eine Dataform.
     *
     * Beim Anlegen wird automatisch der initiale Bewerbungsstatus
     * gesetzt und die Zeitstempel für Erstellung und Änderung
     * gespeichert.
     *
     * @param int $dataid ID der zugehörigen Dataform.
     * @param int $userid ID des Bewerbers.
     * @param int $groupid Optionale Gruppen-ID.
     * @return int ID des neu angelegten Eintrags.
     */
    public static function create_entry(int $dataid, int $userid, int $groupid = 0): int
    {
        global $DB;

        $now = time();

        $initialstatus = status_manager::get_initial_status();

        $entry = (object) [
            'dataid' => $dataid,
            'userid' => $userid,
            'groupid' => $groupid,
            'timecreated' => $now,
            'timemodified' => $now,
            'state' => 0,
            'statusid' => $initialstatus->id,
        ];

        return $DB->insert_record('dhbwio_dataform_entries', $entry);
    }
    /**
     * Speichert den Inhalt eines Feldes innerhalb eines Eintrags.
     *
     * Existiert bereits ein Datensatz für die Kombination aus
     * Eintrag und Feld, wird dieser aktualisiert. Andernfalls
     * wird ein neuer Inhaltsdatensatz angelegt.
     *
     * @param int $entryid ID des Eintrags.
     * @param int $fieldid ID des Feldes.
     * @param string $content Zu speichernder Inhalt.
     * @return int ID des gespeicherten Inhaltsdatensatzes.
     */
    public static function save_content(int $entryid, int $fieldid, string $content): int
    {
        global $DB;

        $existing = $DB->get_record('dhbwio_dataform_contents', [
            'entryid' => $entryid,
            'fieldid' => $fieldid,
        ]);

        $record = (object) [
            'entryid' => $entryid,
            'fieldid' => $fieldid,
            'content' => $content,
            'content1' => null,
            'content2' => null,
            'content3' => null,
            'content4' => null,
        ];

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('dhbwio_dataform_contents', $record);
            return $existing->id;
        }

        return $DB->insert_record('dhbwio_dataform_contents', $record);
    }
    /**
     * Lädt einen einzelnen Eintrag anhand seiner ID.
     *
     * @param int $entryid ID des Eintrags.
     * @return \stdClass|null Eintragsdatensatz oder null.
     */
    public static function get_entry(int $entryid): ?\stdClass
    {
        global $DB;

        return $DB->get_record('dhbwio_dataform_entries', ['id' => $entryid]) ?: null;
    }
    /**
     * Lädt alle Feldinhalte eines Eintrags.
     *
     * Die Inhalte werden nach Feld-ID zurückgegeben und enthalten
     * die gespeicherten Werte der einzelnen Formularfelder.
     *
     * @param int $entryid ID des Eintrags.
     * @return array Liste der Feldinhalte.
     */
    public static function get_entry_contents(int $entryid): array
    {
        global $DB;

        return $DB->get_records('dhbwio_dataform_contents', ['entryid' => $entryid], '', 'fieldid, content, content1, content2, content3, content4');
    }
    /**
     * Löscht einen Eintrag einschließlich aller zugehörigen Inhalte.
     *
     * Vor dem Entfernen des Eintrags werden sämtliche verknüpften
     * Inhaltsdatensätze gelöscht.
     *
     * @param int $entryid ID des zu löschenden Eintrags.
     * @return void
     */
    public static function delete_entry(int $entryid): void
    {
        global $DB;

        $DB->delete_records('dhbwio_dataform_contents', ['entryid' => $entryid]);
        $DB->delete_records('dhbwio_dataform_entries', ['id' => $entryid]);
    }
    /**
     * Lädt alle Einträge eines Benutzers innerhalb einer Dataform.
     *
     * Die Ergebnisse werden nach Erstellungszeitpunkt absteigend
     * sortiert zurückgegeben.
     *
     * @param int $dataid ID der Dataform.
     * @param int $userid ID des Benutzers.
     * @return array Liste der Bewerbungen des Benutzers.
     */
    public static function get_user_entries(int $dataid, int $userid): array
    {
        global $DB;

        return $DB->get_records(
            'dhbwio_dataform_entries',
            [
                'dataid' => $dataid,
                'userid' => $userid,
            ],
            'timecreated DESC'
        );
    }
    /**
     * Lädt alle Einträge einer Dataform.
     *
     * Die Ergebnisse werden nach Erstellungszeitpunkt absteigend
     * sortiert zurückgegeben.
     *
     * @param int $dataid ID der Dataform.
     * @return array Liste aller Einträge.
     */
    public static function get_entries(int $dataid): array
    {
        global $DB;

        return $DB->get_records(
            'dhbwio_dataform_entries',
            ['dataid' => $dataid],
            'timecreated DESC'
        );
    }
    /**
     * Liest den Inhalt eines bestimmten Feldes aus.
     *
     * Die Methode dient als vereinfachter Zugriff auf einzelne
     * Feldwerte innerhalb eines Eintrags.
     *
     * @param int $entryid ID des Eintrags.
     * @param int $fieldid ID des Feldes.
     * @return string|null Gespeicherter Feldwert oder null.
     */
    public static function get_content_value(int $entryid, int $fieldid): ?string
    {
        global $DB;

        $record = $DB->get_record(
            'dhbwio_dataform_contents',
            [
                'entryid' => $entryid,
                'fieldid' => $fieldid,
            ],
            'content'
        );

        return $record ? $record->content : null;
    }
    /**
     * Aktualisiert die Änderungszeit eines Eintrags.
     *
     * Die Methode wird verwendet, um nach Änderungen an den
     * Feldinhalten den Zeitstempel des Eintrags zu aktualisieren.
     *
     * @param int $entryid ID des Eintrags.
     * @throws \moodle_exception Wenn der Eintrag nicht existiert.
     * @return void
     */
    public static function update_entry(int $entryid): void
    {
        global $DB;

        $entry = self::get_entry($entryid);

        if (!$entry) {
            throw new \moodle_exception('invalidentryid', 'mod_dhbwio');
        }

        $entry->timemodified = time();

        $DB->update_record('dhbwio_dataform_entries', $entry);
    }
    public static function update_accepted_university(int $entryid, ?int $universityid): void
    {
        global $DB;

        $record = (object) [
            'id' => $entryid,
            'acceptedchoice' => $universityid,
            'timemodified' => time(),
        ];

        $DB->update_record('dhbwio_dataform_entries', $record);
    }
    public static function get_accepted_choice_label(\stdClass $entry, callable $getvalue): string
    {
        if (empty($entry->acceptedchoice)) {
            return '-';
        }

        return match ($entry->acceptedchoice) {
            'first' => 'Erstwunsch – ' . $getvalue('ERSTWUNSCH'),
            'second' => 'Zweitwunsch – ' . $getvalue('ZWEITWUNSCH'),
            'third' => 'Drittwunsch – ' . $getvalue('DRITTWUNSCH'),
            default => '-',
        };
    }
    public static function get_university_label(int $universityid): string
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

        return trim($university->country . ' - ' . $university->name);
    }
    public static function get_accepted_university_label(\stdClass $entry, callable $getvalue): string
    {
        if (empty($entry->acceptedchoice)) {
            return '-';
        }

        $acceptedid = (int)$entry->acceptedchoice;
        $universitylabel = self::get_university_label($acceptedid);

        $firstchoice = (int)$getvalue('ERSTWUNSCH');
        $secondchoice = (int)$getvalue('ZWEITWUNSCH');
        $thirdchoice = (int)$getvalue('DRITTWUNSCH');

        if ($acceptedid === $firstchoice) {
            return 'Erstwunsch – ' . $universitylabel;
        }

        if ($acceptedid === $secondchoice) {
            return 'Zweitwunsch – ' . $universitylabel;
        }

        if ($acceptedid === $thirdchoice) {
            return 'Drittwunsch – ' . $universitylabel;
        }

        return $universitylabel;
    }
    public static function get_status_by_shortname(string $shortname): ?\stdClass
    {
        global $DB;

        return $DB->get_record(
            'dhbwio_application_status',
            ['shortname' => $shortname, 'active' => 1],
            '*',
            IGNORE_MISSING
        ) ?: null;
    }
    public static function update_status(int $entryid, int $statusid): void
    {
        global $DB;

        $record = (object) [
            'id' => $entryid,
            'statusid' => $statusid,
            'timemodified' => time(),
        ];

        $DB->update_record('dhbwio_dataform_entries', $record);
    }

    /**
     * Berechnet, ob eine Bewerbung innerhalb der Frist eingereicht wurde.
     *
     * Gibt 1 zurück, wenn keine passende Bewerbungsfrist existiert oder
     * die Bewerbung vor dem Fristende eingereicht wurde. Gibt 0 zurück,
     * wenn die Frist überschritten wurde.
     *
     * @param int $entryid ID des Eintrags.
     * @param int $dhbwio_id ID der dhbwio-Modulinstanz.
     * @return int 1 = rechtzeitig, 0 = zu spät.
     */
    public static function compute_within_deadline(int $entryid, int $dhbwio_id): int
    {
        global $DB;

        $entry = self::get_entry($entryid);
        if (!$entry) {
            return 1;
        }

        // 1. Studiengang aus dem Eintrag lesen.
        $studiengang_field = $DB->get_record('dhbwio_dataform_fields', ['dataid' => $entry->dataid, 'name' => 'STUDIENGANG'], 'id');
        if (!$studiengang_field) {
            return 1;
        }
        $sg_content = $DB->get_record('dhbwio_dataform_contents', ['entryid' => $entryid, 'fieldid' => $studiengang_field->id], 'content');
        if (!$sg_content || empty($sg_content->content)) {
            return 1;
        }
        $studiengang = trim($sg_content->content);

        // 2. Jahrgang aus Kürzel extrahieren (z.B. "WWI23B2" → "2023", "TINF22B3" → "2022").
        $kursname_field = $DB->get_record('dhbwio_dataform_fields', ['dataid' => $entry->dataid, 'name' => 'KURSNAME'], 'id');
        if (!$kursname_field) {
            return 1;
        }
        $kurs_content = $DB->get_record('dhbwio_dataform_contents', ['entryid' => $entryid, 'fieldid' => $kursname_field->id], 'content');
        if (!$kurs_content || empty($kurs_content->content)) {
            return 1;
        }
        // Erste 2-stellige Zahl nach führenden Buchstaben extrahieren: "WWI23B2" → "23"
        if (!preg_match('/^[A-Za-z]+(\d{2})/i', trim($kurs_content->content), $matches)) {
            return 1;
        }
        $jahrgang = '20' . $matches[1]; // "23" → "2023"

        // 3. Passende Frist suchen: art = 'bewerbung', Studiengang + Jahrgang müssen übereinstimmen.
        //    Spezifischer Studiengang hat Vorrang vor 'alle'.
        $frist = $DB->get_record_select(
            'dhbwio_fristen',
            "dhbwio = :dhbwio AND art = 'bewerbung' AND studiengang = :studiengang AND jahrgang = :jahrgang AND deadline IS NOT NULL",
            ['dhbwio' => $dhbwio_id, 'studiengang' => $studiengang, 'jahrgang' => $jahrgang]
        );
        if (!$frist) {
            // Fallback: Frist gilt für alle Studiengänge dieses Jahrgangs.
            $frist = $DB->get_record_select(
                'dhbwio_fristen',
                "dhbwio = :dhbwio AND art = 'bewerbung' AND studiengang = 'alle' AND jahrgang = :jahrgang AND deadline IS NOT NULL",
                ['dhbwio' => $dhbwio_id, 'jahrgang' => $jahrgang]
            );
        }

        if (!$frist || empty($frist->deadline)) {
            return 1; // Keine passende Frist → keine Einschränkung
        }

        // 4. Prüfen ob Bewerbung vor Fristablauf eingereicht wurde.
        return ($entry->timecreated <= (int)$frist->deadline) ? 1 : 0;
    }

    /**
     * Setzt das within_deadline-Flag für einen Eintrag.
     *
     * @param int $entryid ID des Eintrags.
     * @param int $dhbwio_id ID der dhbwio-Modulinstanz.
     * @return void
     */
    public static function update_within_deadline(int $entryid, int $dhbwio_id): void
    {
        global $DB;

        $within = self::compute_within_deadline($entryid, $dhbwio_id);
        $DB->set_field('dhbwio_dataform_entries', 'within_deadline', $within, ['id' => $entryid]);
    }
}
