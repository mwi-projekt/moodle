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
 * Experience report form for DHBW International Office.
 *
 * @package    mod_dhbwio
 * @copyright  2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dhbwio\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Form for creating/editing experience reports.
 */
class report_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $cmid = $this->_customdata['cmid'];
        $report = isset($this->_customdata['report']) ? $this->_customdata['report'] : null;
        $context = $this->_customdata['context'];
        $universities = $this->_customdata['universities'];

        // Add hidden fields
        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'id', $cmid);
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'action', $report ? 'update' : 'add');
        $mform->setType('action', PARAM_ALPHA);
        
        if ($report) {
            $mform->addElement('hidden', 'report', $report->id);
            $mform->setType('report', PARAM_INT);
        }

        // University selection
        $mform->addElement('select', 'university_id', get_string('university', 'mod_dhbwio'), $universities);
        $mform->addRule('university_id', null, 'required', null, 'client');
        
        // Report title
        $mform->addElement('text', 'title', get_string('report_title', 'mod_dhbwio'), ['size' => '64']);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('title', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Report content
        $editoroptions = ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $context];
        $mform->addElement('editor', 'content_editor', get_string('report_content', 'mod_dhbwio'), null, $editoroptions);
        $mform->setType('content_editor', PARAM_RAW);
        $mform->addRule('content_editor', null, 'required', null, 'client');

        // Rating
        $ratings = [
            '' => get_string('select_rating', 'mod_dhbwio'),
            '1' => '1 - ' . get_string('rating_poor', 'mod_dhbwio'),
            '2' => '2 - ' . get_string('rating_fair', 'mod_dhbwio'),
            '3' => '3 - ' . get_string('rating_good', 'mod_dhbwio'),
            '4' => '4 - ' . get_string('rating_very_good', 'mod_dhbwio'),
            '5' => '5 - ' . get_string('rating_excellent', 'mod_dhbwio')
        ];
        $mform->addElement('select', 'rating', get_string('report_rating', 'mod_dhbwio'), $ratings);
        $mform->addRule('rating', null, 'required', null, 'client');
        $mform->addHelpButton('rating', 'report_rating', 'mod_dhbwio');

        // Report attachments
        $maxbytes = get_max_upload_file_size($CFG->maxbytes);
        $mform->addElement('filemanager', 'attachments', get_string('attachments', 'mod_dhbwio'), null, 
                          ['subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => 5]);
        $mform->addHelpButton('attachments', 'attachments', 'mod_dhbwio');

        $mform->addElement('advcheckbox', 'visible', get_string('report_visible', 'mod_dhbwio'), 
                            get_string('report_visible_desc', 'mod_dhbwio'), ['group' => 1], [0, 1]);
        $mform->setDefault('visible', 1);

        // Add standard buttons
        $this->add_action_buttons();
    }

    /**
     * Validation function.
     *
     * @param array $data Form data
     * @param array $files Form files
     * @return array Validation errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate rating (between 1 and 5)
        if (!empty($data['rating']) && ($data['rating'] < 1 || $data['rating'] > 5)) {
            $errors['rating'] = get_string('invalid_rating', 'mod_dhbwio');
        }

        return $errors;
    }

    /**
     * Set form data from report record.
     *
     * @param \stdClass $report Report record
     */
    public function set_data($report) {
        if (!empty($report->content)) {
            $report->content_editor = [
                'text' => $report->content,
                'format' => $report->contentformat
            ];
        }

        parent::set_data($report);
    }
}