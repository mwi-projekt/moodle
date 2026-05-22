<?php

namespace mod_dhbwio\local\dataform;

defined('MOODLE_INTERNAL') || die();

class status_manager {

    public static function get_status(int $statusid): ?\stdClass {
        global $DB;

        return $DB->get_record(
            'dhbwio_application_status',
            ['id' => $statusid]
        ) ?: null;
    }

    public static function get_initial_status(): \stdClass {
        global $DB;

        return $DB->get_record(
            'dhbwio_application_status',
            ['isinitial' => 1, 'active' => 1],
            '*',
            MUST_EXIST
        );
    }

    public static function get_active_statuses(): array {
        global $DB;

        return $DB->get_records(
            'dhbwio_application_status',
            ['active' => 1],
            'sortorder ASC'
        );
    }

    public static function get_options(): array {
        $statuses = self::get_active_statuses();

        $options = [];

        foreach ($statuses as $status) {
            $options[$status->id] = $status->label;
        }

        return $options;
    }

    public static function is_accepted(int $statusid): bool {
        $status = self::get_status($statusid);

        return $status && (int) $status->isaccepted === 1;
    }
}