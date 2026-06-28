<?php

namespace mod_dhbwio\local\dataform;
use moodle_url;
use html_writer;

defined('MOODLE_INTERNAL') || die();
/**
 *Verwaltungsklasse für die Learning Agreements

 */

class la_manager
{
    //get LA by user id sort by last modified
    public static function get_la_by_userid(int $userid): ?\stdClass
    {
        global $DB;
        //$record = $DB->get_record('dhbwio_la', ['userid' => $userid], '*', IGNORE_MISSING);
        $record = $DB->get_record_sql('SELECT * FROM {dhbwio_la} WHERE userid = ? ORDER BY timemodified DESC LIMIT 1', [$userid], IGNORE_MISSING);
        return $record ?: null;
    }

    //get LA by user id return array sort by last modified
    public static function get_la_by_userid_ar(int $userid): array
    {
        global $DB;
        $record = $DB->get_record_sql('SELECT * FROM {dhbwio_la} WHERE userid = ? ORDER BY timemodified DESC LIMIT 1', [$userid],  IGNORE_MISSING);
        return $record ? [$record] : [];
    }

    //get Status of LA by la_id
    public static function get_la_status_by_la_id(int $la_id): ?\stdClass
    {
        global $DB;
        $statusid =  $DB->get_record('dhbwio_la', ['id' => $la_id], 'status', IGNORE_MISSING);
        $record =  $DB->get_record('dhbwio_la_status', ['id' => $statusid->status], '*', IGNORE_MISSING);
        return $record ?: null;
    }

    //get Status by Status Text
    public static function get_la_status_by_status_text(string $status): ?\stdClass
    {
        global $DB;
        $record =  $DB->get_record('dhbwio_la_status', ['status' => $status], '*', IGNORE_MISSING);
        return $record ?: null;
    }

    //get all LAs. Sort by last edit
    public static function get_all_las(): array
    {
        global $DB;
        $record = $DB->get_records('dhbwio_la', null, 'timemodified DESC');
        return $record ?: [];
    }

    //get if user has accepted application
    public static function has_user_accepted_app(int $userid): bool
    {
        global $DB;
        //get the latest modified application entry for the user with statusid 3 (accepted)
        $record = $DB->get_record_sql('SELECT * FROM {dhbwio_dataform_entries} WHERE userid = ? AND statusid = 3 ORDER BY timemodified DESC LIMIT 1', [$userid], IGNORE_MISSING);
        if($record){
            return true;
        } else {
            return false;
        }

    }

    //get last modified Application for User
    public static function get_last_modified_app_for_user(int $userid): ?\stdClass
    {
        global $DB;
        $record = $DB->get_record_sql('SELECT * FROM {dhbwio_dataform_entries} WHERE userid = ? ORDER BY timemodified DESC LIMIT 1', [$userid], IGNORE_MISSING);
        return $record ?: null;
    }

    //generate LA table for display in admin view
    public static function generate_la_table($laentries, $canviewallla, $cm): array
    {
        global $DB;
        $las = $laentries;
        // Loop through all LAs and add them to array
        foreach ($las as $la) {
            $contents = $DB->get_record('dhbwio_la_contents', ['la_id' => $la->id], '*', IGNORE_MISSING);
            $modules = $DB->get_records('dhbwio_la_module', ['la_contents_id' => $contents->id], 'id ASC', '*', IGNORE_MISSING);
            $user = $DB->get_record('dhbwio_la_contents', ['la_id' => $la->id], 'vorname, name', MUST_EXIST);
            $applicant = $user->vorname . ' ' . $user->name;
            $course = $DB->get_record('dhbwio_studyprograms', ['id' => $contents->studiengang], 'de_name', MUST_EXIST);
            $created = $la->timecreated;
            $modified = $la->timemodified;
            $university = $DB->get_record('dhbwio_universities', ['name' => $contents->gasthochschule], '*', MUST_EXIST);
            //später über ID aktuell noch über name
            //$university = $DB->get_record('dhbwio_universities', ['id' => $contents->gasthochschule], 'name', MUST_EXIST);
            $status = $DB ->get_record('dhbwio_la_status', ['id' => $la->status], '*', IGNORE_MISSING);

            $statusclass = match ($status->shortname){
                'erstellen'   => 'status-submitted',
                'in_ueberpruefung'   => 'status-review',
                'akzeptiert'    => 'status-approved',
                'abgelehnt'     => 'status-rejected',
                'ueberarbeitung_noetig' => 'status-review',
                default         => 'status-default',
            };

            //add non clickable button to view and edit the LA -> TODO for later
            /*
            $viewurl = new moodle_url('/mod/dhbwio/learning_agreement_edit.php', [
                'id'      => $cm->id,
                'entryid' => $entry->id,
            ]);
            $show_edit_actions = html_writer::link($viewurl, get_string('show/edit', 'dhbwio'));
            */

            $show_edit_url = new moodle_url('/mod/dhbwio/learning_agreement_formular.php', [
                'id' => $cm->id
            ]);
            $action_show_edit =  html_writer::link($show_edit_url, get_string('lashow/edit', 'dhbwio'));



            // if Manager then add all fields else show only created, modified, university, status and actions
            if($canviewallla){
                $applications[] = [
                    'applicantname' => $applicant,
                    'studyprogram' => $course->de_name,
                    'timecreated' => userdate((int)$la->timecreated, '%d.%m.%Y %H:%M'),
                    'timemodified' => userdate((int)$la->timemodified, '%d.%m.%Y %H:%M'),
                    'university' => $university->name,
                    'status' => $status->label,
                    'statusclass' => $statusclass,
                    'action' => '' ,
                ];
            } else {
                $applications[] = [
                    'timecreated' => userdate((int)$la->timecreated, '%d.%m.%Y %H:%M'),
                    'timemodified' => userdate((int)$la->timemodified, '%d.%m.%Y %H:%M'),
                    'university' => $university->name,
                    'status' => $status->label,
                    'actions' => $action_show_edit,
                ];
            }
            //var_dump($applications); //debugging


        }


        $templatecontext = [
            'title' => $canviewallla
                ? get_string('all_la_overview_title', 'dhbwio')
                : get_string('la_overview_title', 'dhbwio'),
            'isadmin' => $canviewallla,
            'haslearningagreements' => !empty($applications),
            'learningagreements' => $applications,
            'emptytext' => $canviewallla
                ? get_string('all_la_emptytext', 'dhbwio')
                : get_string('la_emptytext', 'dhbwio'),

        ];
        return $templatecontext;
    }








}