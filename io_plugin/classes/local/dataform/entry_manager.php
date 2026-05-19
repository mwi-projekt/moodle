<?php

namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

class entry_manager
{

    public static function create_entry(int $dataid, int $userid, int $groupid = 0): int
    {
        global $DB;

        $now = time();

        $entry = (object) [
            'dataid' => $dataid,
            'userid' => $userid,
            'groupid' => $groupid,
            'timecreated' => $now,
            'timemodified' => $now,
            'state' => 0,
        ];

        return $DB->insert_record('dhbwio_dataform_entries', $entry);
    }

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

    public static function get_entry(int $entryid): ?\stdClass
    {
        global $DB;

        return $DB->get_record('dhbwio_dataform_entries', ['id' => $entryid]) ?: null;
    }

    public static function get_entry_contents(int $entryid): array
    {
        global $DB;

        return $DB->get_records('dhbwio_dataform_contents', ['entryid' => $entryid], '', 'fieldid, content, content1, content2, content3, content4');
    }

    public static function delete_entry(int $entryid): void
    {
        global $DB;

        $DB->delete_records('dhbwio_dataform_contents', ['entryid' => $entryid]);
        $DB->delete_records('dhbwio_dataform_entries', ['id' => $entryid]);
    }
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

    public static function get_entries(int $dataid): array
    {
        global $DB;

        return $DB->get_records(
            'dhbwio_dataform_entries',
            ['dataid' => $dataid],
            'timecreated DESC'
        );
    }

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
}
