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
 * University form for DHBW International Office.
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
 * Form for creating/editing university entries.
 */
class university_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $CFG, $PAGE;

        $mform = $this->_form;
        $cmid = $this->_customdata['cmid'];
        $university = isset($this->_customdata['university']) ? $this->_customdata['university'] : null;
        $context = $this->_customdata['context'];

        // Add hidden fields
        $mform->addElement('hidden', 'id', $cmid);
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'action', $university ? 'update' : 'add');
        $mform->setType('action', PARAM_ALPHA);
        
        if ($university) {
            $mform->addElement('hidden', 'university', $university->id);
            $mform->setType('university', PARAM_INT);
        }

        // --------------------------------------------------------------
        // 1. Basic information and location fieldset (merged)
        // --------------------------------------------------------------
        $mform->addElement('header', 'basicinfo', get_string('basic_information', 'mod_dhbwio'));

        // University name
        $mform->addElement('text', 'name', get_string('university_name', 'mod_dhbwio'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Website
        $mform->addElement('url', 'website', get_string('university_website', 'mod_dhbwio'), ['size' => '60'], ['usefilepicker' => false]);
        $mform->setType('website', PARAM_URL);
        $mform->addHelpButton('website', 'university_website', 'mod_dhbwio');

        // location header
        $mform->addElement('static', 'coordinates_header', '', '<h5>' . get_string('university_location', 'mod_dhbwio') . '</h5>');

        // Country - dropdown with ISO country codes
        $countries = get_string_manager()->get_list_of_countries();
        $mform->addElement('select', 'country', get_string('university_country', 'mod_dhbwio'), $countries);
        $mform->addRule('country', null, 'required', null, 'client');
        $mform->addHelpButton('country', 'country', 'mod_dhbwio');

        // City
        $mform->addElement('text', 'city', get_string('university_city', 'mod_dhbwio'), ['size' => '40']);
        $mform->setType('city', PARAM_TEXT);
        $mform->addRule('city', null, 'required', null, 'client');
        $mform->addRule('city', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');

        // Address fields for geocoding
        $mform->addElement('text', 'address', get_string('address', 'mod_dhbwio'), ['size' => '60']);
        $mform->setType('address', PARAM_TEXT);
        $mform->addRule('address', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'postal_code', get_string('postal_code', 'mod_dhbwio'), ['size' => '10']);
        $mform->setType('postal_code', PARAM_TEXT);
        $mform->addRule('postal_code', get_string('maximumchars', '', 20), 'maxlength', 20, 'client');

        // Geocoding button - custom element with AMD module
        $buttonattributes = ['id' => 'id_geocode_button'];
        $mform->addElement('button', 'geocode_button', get_string('get_coordinates', 'mod_dhbwio'), $buttonattributes);

        // Latitude and Longitude in a group
        $coordgroup = [];
        $coordgroup[] = $mform->createElement('text', 'latitude', get_string('latitude', 'mod_dhbwio'), ['size' => '15', 'id' => 'id_latitude']);
        $coordgroup[] = $mform->createElement('text', 'longitude', get_string('longitude', 'mod_dhbwio'), ['size' => '15', 'id' => 'id_longitude']);
        $mform->addGroup($coordgroup, 'coordinates_group', get_string('university_coordinates', 'mod_dhbwio'), ['&nbsp;&nbsp;'], false);
        
        $mform->setType('latitude', PARAM_FLOAT);
        $mform->setType('longitude', PARAM_FLOAT);
        $mform->addHelpButton('coordinates_group', 'latitude', 'mod_dhbwio');

        // Status message element for geocoding results
        $mform->addElement('static', 'geocode_status', '', '<div id="geocode_status"></div>');

        // --------------------------------------------------------------
        // 2. Capacity fieldset
        // --------------------------------------------------------------
        $mform->addElement('header', 'capacity', get_string('capacity_info', 'mod_dhbwio'));

        // Available slots
        $mform->addElement('text', 'available_slots', get_string('university_available_slots', 'mod_dhbwio'), ['size' => '5']);
        $mform->setType('available_slots', PARAM_INT);
        $mform->setDefault('available_slots', 0);
        $mform->addRule('available_slots', null, 'required', null, 'client');

        // Month selection for semester start and end
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = get_string('month_' . $i, 'mod_dhbwio');
        }

        // Semester start month
        $mform->addElement('select', 'semester_start', get_string('semester_start_month', 'mod_dhbwio'), $months);
        $mform->addHelpButton('semester_start', 'semester_start_month_help', 'mod_dhbwio');

        // Semester end month
        $mform->addElement('select', 'semester_end', get_string('semester_end_month', 'mod_dhbwio'), $months);
        $mform->addHelpButton('semester_end', 'semester_end_month_help', 'mod_dhbwio');

        // Semester fees
        $mform->addElement('text', 'semester_fees', get_string('semester_fees', 'mod_dhbwio'), ['size' => '10']);
        $mform->setType('semester_fees', PARAM_FLOAT);
        $mform->addHelpButton('semester_fees', 'semester_fees_help', 'mod_dhbwio');
        $mform->setDefault('semester_fees', '0.00');

        // Currency for fees
        $currencies = [
            'EUR' => get_string('currency_eur', 'mod_dhbwio'),
            'USD' => get_string('currency_usd', 'mod_dhbwio'),
            'GBP' => get_string('currency_gbp', 'mod_dhbwio'),
            'CHF' => get_string('currency_chf', 'mod_dhbwio'),
            'AUD' => get_string('currency_aud', 'mod_dhbwio'),
            'CAD' => get_string('currency_cad', 'mod_dhbwio'),
            'JPY' => get_string('currency_jpy', 'mod_dhbwio'),
            'CNY' => get_string('currency_cny', 'mod_dhbwio'),
            'SEK' => get_string('currency_sek', 'mod_dhbwio'),
            'NOK' => get_string('currency_nok', 'mod_dhbwio'),
            'DKK' => get_string('currency_dkk', 'mod_dhbwio'),
            'OTHER' => get_string('currency_other', 'mod_dhbwio')
        ];
        $mform->addElement('select', 'fee_currency', get_string('fee_currency', 'mod_dhbwio'), $currencies);
        $mform->setDefault('fee_currency', 'EUR');

        // Accommodation options
        $accommodations = [
            'dorm' => get_string('accommodation_dorm', 'mod_dhbwio'),
            'apartment' => get_string('accommodation_apartment', 'mod_dhbwio'),
            'homestay' => get_string('accommodation_homestay', 'mod_dhbwio'),
            'hotel' => get_string('accommodation_hotel', 'mod_dhbwio'),
            'airbnb' => get_string('accommodation_airbnb', 'mod_dhbwio'),
            'private' => get_string('accommodation_private', 'mod_dhbwio'),
            'various' => get_string('accommodation_various', 'mod_dhbwio'),
            'none' => get_string('accommodation_none', 'mod_dhbwio')
        ];
        $mform->addElement('select', 'accommodation_type', get_string('accommodation_type', 'mod_dhbwio'), $accommodations);
        $mform->addHelpButton('accommodation_type', 'accommodation_type_help', 'mod_dhbwio');

        // --------------------------------------------------------------
        // 3. Description fieldset
        // --------------------------------------------------------------
        $mform->addElement('header', 'descriptionfieldset', get_string('university_description', 'mod_dhbwio'));

        // Description editor
        $editoroptions = ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $context];
        $mform->addElement('editor', 'description_editor', get_string('university_description', 'mod_dhbwio'), null, $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        // Requirements
        $mform->addElement('textarea', 'requirements', get_string('university_requirements', 'mod_dhbwio'), 
                          ['rows' => 6, 'cols' => 50]);
        $mform->setType('requirements', PARAM_TEXT);
        $mform->addHelpButton('requirements', 'university_requirements', 'mod_dhbwio');

        // --------------------------------------------------------------
        // 4. Media fieldset
        // --------------------------------------------------------------
        $mform->addElement('header', 'mediafieldset', get_string('university_image', 'mod_dhbwio'));

        // University image
        $maxbytes = get_max_upload_file_size($CFG->maxbytes);
        $mform->addElement('filemanager', 'university_image', get_string('university_image', 'mod_dhbwio'), null, 
                          ['subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => 1, 
                           'accepted_types' => ['image']]);
        $mform->addHelpButton('university_image', 'university_image', 'mod_dhbwio');

        // --------------------------------------------------------------
        // 5. Status fieldset
        // --------------------------------------------------------------
        $mform->addElement('header', 'statusfieldset', get_string('status', 'mod_dhbwio'));

        // Active
        $mform->addElement('advcheckbox', 'active', get_string('university_active', 'mod_dhbwio'), 
                           get_string('university_active_desc', 'mod_dhbwio'), ['group' => 1], [0, 1]);
        $mform->setDefault('active', 1);

        // Add standard buttons
        $this->add_action_buttons();

        // Page needs the geocoder JS module
        $PAGE->requires->js_call_amd('mod_dhbwio/geocoder', 'init');
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

        // Validate latitude (between -90 and 90)
        if (!empty($data['latitude']) && ($data['latitude'] < -90 || $data['latitude'] > 90)) {
            $errors['coordinates_group'] = get_string('invalid_latitude', 'mod_dhbwio');
        }

        // Validate longitude (between -180 and 180)
        if (!empty($data['longitude']) && ($data['longitude'] < -180 || $data['longitude'] > 180)) {
            if (!isset($errors['coordinates_group'])) {
                $errors['coordinates_group'] = get_string('invalid_longitude', 'mod_dhbwio');
            }
        }

        // Validate available slots (must be positive)
        if (isset($data['available_slots']) && $data['available_slots'] < 0) {
            $errors['available_slots'] = get_string('invalid_slots', 'mod_dhbwio');
        }

        // Validate semester fees (must be non-negative)
        if (isset($data['semester_fees']) && $data['semester_fees'] < 0) {
            $errors['semester_fees'] = get_string('invalid_fees', 'mod_dhbwio');
        }

        return $errors;
    }

    /**
     * Set form data from university record.
     *
     * @param \stdClass $university University record
     */
    public function set_data($university) {
        global $DB;

        if (!empty($university->description)) {
            $university->description_editor = [
                'text' => $university->description,
                'format' => $university->descriptionformat
            ];
        }

        parent::set_data($university);
    }
}