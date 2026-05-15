<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Action controller for student applications.
 *
 * Supported actions (POST only, always require sesskey):
 *   create     – Creates a draft application for the current user and redirects to myapplication tab.
 *   transition – Moves an application to a new status. Requires applicationid and to parameters.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

use mod_dhbwio\application_manager;
use mod_dhbwio\application_status;

$cmid   = required_param('cmid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);

$cm     = get_coursemodule_from_id('dhbwio', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$dhbwio = $DB->get_record('dhbwio', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// All actions require a valid session key (CSRF protection)
require_sesskey();

$returnurl_student = new moodle_url('/mod/dhbwio/view.php', ['id' => $cmid, 'tab' => 'myapplication']);
$returnurl_io      = new moodle_url('/mod/dhbwio/view.php', ['id' => $cmid, 'tab' => 'applications']);

switch ($action) {

    // -----------------------------------------------------------------------
    // create – Student starts a new application (creates a draft record)
    // -----------------------------------------------------------------------
    case 'create':
        require_capability('mod/dhbwio:apply', $context);

        application_manager::create_application($dhbwio->id, $USER->id);
        redirect($returnurl_student, get_string('application_saved', 'mod_dhbwio'), null, \core\output\notification::NOTIFY_SUCCESS);
        break;

    // -----------------------------------------------------------------------
    // transition – Change the status of an application
    // -----------------------------------------------------------------------
    case 'transition':
        $applicationid = required_param('applicationid', PARAM_INT);
        $to_status     = required_param('to', PARAM_ALPHANUMEXT);
        $content       = optional_param('content', '', PARAM_TEXT);

        $application = application_manager::get_application($applicationid);

        // Ownership check: students may only act on their own applications
        $is_io = has_capability('mod/dhbwio:reviewapplications', $context);
        if (!$is_io && $application->userid !== $USER->id) {
            throw new moodle_exception('nopermissions', 'error');
        }

        $actor = application_status::actor_for_context($context);

        // Performs the DB update + writes the audit note
        application_status::transition($applicationid, $to_status, $actor, $content);

        $statuslabel = get_string(application_status::string_key($to_status), 'mod_dhbwio');
        $message     = get_string('application_status_changed', 'mod_dhbwio', $statuslabel);

        $returnurl = $is_io ? $returnurl_io : $returnurl_student;
        redirect($returnurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
        break;

    default:
        throw new moodle_exception('invalidaction', 'error');
}
