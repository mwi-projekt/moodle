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
    'use strict';

    // Private variables
    let strings = {};
    let isInitialized = false;

    /**
     * Load required language strings
     */
    const loadStrings = async() => {
        const stringRequests = [
            {key: 'geocoding_missing_fields', component: 'mod_dhbwio'},
            {key: 'geocoding_in_progress', component: 'mod_dhbwio'},
            {key: 'geocoding_success', component: 'mod_dhbwio'},
            {key: 'geocoding_error', component: 'mod_dhbwio'},
            {key: 'geocoding_no_results', component: 'mod_dhbwio'},
            {key: 'geocoding_invalid_response', component: 'mod_dhbwio'}
        ];

        try {
            const results = await Str.get_strings(stringRequests);
            strings = {
                missingFields: results[0],
                inProgress: results[1],
                success: results[2],
                error: results[3],
                noResults: results[4],
                invalidResponse: results[5]
            };
        } catch (error) {
            // Fallback to English if string loading fails
            strings = {
                missingFields: 'Please provide at least city and country.',
                inProgress: 'Geocoding in progress...',
                success: 'Coordinates found successfully!',
                error: 'Error occurred during geocoding.',
                noResults: 'No coordinates found for the given address.',
                invalidResponse: 'Invalid response from geocoding service.'
            };
            console.warn('Could not load language strings, using fallback:', error);
        }
    };

    /**
     * Module initialization.
     */
    const init = async() => {
        if (isInitialized) {
            return;
        }

        // Load language strings first
        await loadStrings();

        // Add event listener to the geocode button
        $('#id_geocode_button').on('click', handleGeocodeClick);

        // Add real-time validation
        $('#id_city, #id_country').on('input', validateRequiredFields);

        isInitialized = true;
    };

    /**
     * Handle geocode button click
     */
    const handleGeocodeClick = (e) => {
        e.preventDefault();
        performGeocoding();
    };

    /**
     * Validate required fields and enable/disable geocode button
     */
    const validateRequiredFields = () => {
        const city = $('#id_city').val().trim();
        const country = $('#id_country').val().trim();
        const $button = $('#id_geocode_button');

        if (city && country) {
            $button.prop('disabled', false);
        } else {
            $button.prop('disabled', true);
        }
    };

    /**
     * Perform geocoding with address information.
     */
    const performGeocoding = async() => {
        const address = $('#id_address').val().trim();
        const postalCode = $('#id_postal_code').val().trim();
        const city = $('#id_city').val().trim();
        const country = $('#id_country').val().trim();

        // Validate required fields
        if (!city || !country) {
            displayStatus('error', strings.missingFields);
            return;
        }

        // Combine address components
        const addressComponents = [address, postalCode, city, country].filter(Boolean);
        const fullAddress = addressComponents.join(', ');

        // Disable button during geocoding
        const $button = $('#id_geocode_button');
        const originalText = $button.text();
        $button.prop('disabled', true);

        // Show loading status
        displayStatus('loading', strings.inProgress);

        try {
            const response = await Ajax.call([{
                methodname: 'mod_dhbwio_geocode_address',
                args: {
                    address: fullAddress
                }
            }])[0];

            handleGeocodeResponse(response);

        } catch (error) {
            console.error('Geocoding error:', error);
            displayStatus('error', strings.error);
            Notification.exception(error);
        } finally {
            // Re-enable button
            $button.prop('disabled', false).text(originalText);
        }
    };

    /**
     * Handle geocoding response
     */
    const handleGeocodeResponse = (response) => {
        if (!response) {
            displayStatus('error', strings.invalidResponse);
            return;
        }

        if (response.success) {
            // Validate coordinates
            const lat = parseFloat(response.latitude);
            const lng = parseFloat(response.longitude);

            if (isNaN(lat) || isNaN(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                displayStatus('error', strings.invalidResponse);
                return;
            }

            // Update form fields with coordinates
            $('#id_latitude').val(lat.toFixed(6));
            $('#id_longitude').val(lng.toFixed(6));

            // Trigger change events for form validation
            $('#id_latitude, #id_longitude').trigger('change');

            // Show success message with coordinates
            const successMessage = `${strings.success} (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
            displayStatus('success', successMessage);

            // Optional: Show preview map if available
            showCoordinatePreview(lat, lng);

        } else {
            const errorMessage = response.message || strings.noResults;
            displayStatus('error', errorMessage);
        }
    };

    /**
     * Display geocoding status message.
     *
     * @param {string} type - The type of message (success, error, loading)
     * @param {string} message - The message to display
     */
    const displayStatus = (type, message) => {
        const $status = $('#geocode_status');
        $status.empty();

        let cssClass = 'alert';
        let icon = '';

        switch (type) {
            case 'success':
                cssClass += ' alert-success';
                icon = '<i class="fa fa-check-circle" aria-hidden="true"></i> ';
                break;
            case 'error':
                cssClass += ' alert-danger';
                icon = '<i class="fa fa-exclamation-circle" aria-hidden="true"></i> ';
                break;
            case 'loading':
                cssClass += ' alert-info';
                icon = '<div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>';
                break;
        }

        const statusHtml = `
            <div class="${cssClass} mt-2" role="alert">
                ${icon}${message}
            </div>
        `;

        $status.html(statusHtml);
    };

    /**
     * Show a small preview of the coordinates (optional enhancement)
     */
    const showCoordinatePreview = (lat, lng) => {
        const $preview = $('#coordinate_preview');
        if ($preview.length) {
            // Only show if preview container exists
            const mapsUrl = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}&zoom=15`;
            const previewHtml = `
                <div class="mt-2">
                    <small class="text-muted">
                        <a href="${mapsUrl}" target="_blank" rel="noopener">
                            <i class="fa fa-external-link" aria-hidden="true"></i>
                            View on map
                        </a>
                    </small>
                </div>
            `;
            $preview.html(previewHtml);
        }
    };

    /**
     * Clear geocoding results
     */
    const clearResults = () => {
        $('#id_latitude, #id_longitude').val('');
        $('#geocode_status').empty();
        $('#coordinate_preview').empty();
    };

    /**
     * Get current coordinates from form
     */
    const getCurrentCoordinates = () => {
        const lat = parseFloat($('#id_latitude').val());
        const lng = parseFloat($('#id_longitude').val());

        if (isNaN(lat) || isNaN(lng)) {
            return null;
        }

        return {latitude: lat, longitude: lng};
    };

    // Public API
    return {
        init: init,
        performGeocoding: performGeocoding,
        clearResults: clearResults,
        getCurrentCoordinates: getCurrentCoordinates
    };
});
