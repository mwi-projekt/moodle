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
 * Geocoding functionality for DHBW International Office.
 *
 * @module      mod_dhbwio/geocoder
 * @copyright   2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {
    /**
     * Module initialization.
     */
    var init = function() {
        // Add event listener to the geocode button.
        $('#id_geocode_button').on('click', function(e) {
            e.preventDefault();
            performGeocoding();
        });
    };

    /**
     * Perform geocoding with address information.
     */
    var performGeocoding = function() {
        var address = $('#id_address').val();
        var postalCode = $('#id_postal_code').val();
        var city = $('#id_city').val();
        var country = $('#id_country').val();

        if (!city || !country) {
            displayStatus('error', M.util.get_string('geocoding_missing_fields', 'mod_dhbwio'));
            return;
        }

        // Combine address components.
        var fullAddress = [address, postalCode, city, country].filter(Boolean).join(', ');
        
        // Show loading status.
        displayStatus('loading', M.util.get_string('geocoding_in_progress', 'mod_dhbwio'));

        // Make AJAX call to server-side geocoding service.
        var promises = Ajax.call([{
            methodname: 'mod_dhbwio_geocode_address',
            args: {
                address: fullAddress
            }
        }]);

        promises[0].done(function(response) {
            if (response.success) {
                // Update form fields with coordinates.
                $('#id_latitude').val(response.latitude);
                $('#id_longitude').val(response.longitude);
                
                // Show success message.
                displayStatus('success', M.util.get_string('geocoding_success', 'mod_dhbwio'));
            } else {
                // Show error message.
                displayStatus('error', response.message);
            }
        }).fail(function(error) {
            displayStatus('error', M.util.get_string('geocoding_error', 'mod_dhbwio'));
            Notification.exception(error);
        });
    };

    /**
     * Display geocoding status message.
     * 
     * @param {string} type - The type of message (success, error, loading)
     * @param {string} message - The message to display
     */
    var displayStatus = function(type, message) {
        var $status = $('#geocode_status');
        $status.empty();

        var cssClass = '';
        switch (type) {
            case 'success':
                cssClass = 'alert alert-success';
                break;
            case 'error':
                cssClass = 'alert alert-danger';
                break;
            case 'loading':
                cssClass = 'alert alert-info';
                message += ' <div class="spinner-border spinner-border-sm" role="status"></div>';
                break;
        }

        $status.html('<div class="' + cssClass + '">' + message + '</div>');
    };

    return {
        init: init
    };
});