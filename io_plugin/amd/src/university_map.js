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
 * JavaScript for interactive university world map.
 *
 * @module      mod_dhbwio/university_map
 * @copyright   2025, DHBW <esc@dhbw-karlsruhe.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/str'
], function($, Ajax, Notification, Str) {
    'use strict';

    // Private variables
    let map = null;
    let markersLayer = null;
    let leafletLib = null;
    let strings = {};

    /**
     * Load required language strings
     */
    const loadStrings = async() => {
        const stringRequests = [
            {key: 'university_available_slots', component: 'mod_dhbwio'},
            {key: 'reports', component: 'mod_dhbwio'},
            {key: 'view_details', component: 'mod_dhbwio'},
            {key: 'loading_universities', component: 'mod_dhbwio'},
            {key: 'map_loading_error', component: 'mod_dhbwio'},
            {key: 'leaflet_not_loaded', component: 'mod_dhbwio'},
            {key: 'map_creation_error', component: 'mod_dhbwio'}
        ];

        try {
            const results = await Str.get_strings(stringRequests);

            strings = {
                availableSlots: results[0],
                reports: results[1],
                viewDetails: results[2],
                loadingUniversities: results[3],
                mapLoadingError: results[4],
                leafletNotLoaded: results[5],
                mapCreationError: results[6]
            };
        } catch (error) {
            // Fallback to English if string loading fails
            strings = {
                availableSlots: 'Available Slots',
                reports: 'Reports',
                viewDetails: 'View Details',
                loadingUniversities: 'Loading universities...',
                mapLoadingError: 'Error loading universities',
                leafletNotLoaded: 'Leaflet library not loaded',
                mapCreationError: 'Error creating map'
            };
            console.warn('Could not load language strings, using fallback:', error);
        }
    };

    /**
     * Initialize the map
     */
    const init = async(courseModuleId) => {
        const cmid = courseModuleId;

        // Load language strings first
        await loadStrings();

        // Wait for DOM to be ready
        $(document).ready(() => {
            if ($('#university-map').length) {
                console.log('Map container found, initializing map for CM ID:', cmid);
                // Initialize the map
                initMap();

                // After map is initialized, load universities
                if (map) {
                    loadUniversities(cmid);
                }
            } else {
                console.error('Map container not found');
            }
        });
    };

    /**
     * Initialize the Leaflet map
     */
    const initMap = () => {
        console.log('initMap called');

        // Check if Leaflet is available
        if (typeof L === 'undefined') {
            if (typeof window.L !== 'undefined') {
                leafletLib = window.L;
                console.log('Found Leaflet as window.L');
            } else {
                console.error('Leaflet library not loaded');
                Notification.exception({message: strings.leafletNotLoaded});
                return;
            }
        } else {
            leafletLib = L;
            console.log('Leaflet already available as L');
        }

        try {
            console.log('Creating map');
            // Create the map
            map = leafletLib.map('university-map').setView([30, 10], 2);

            // Add the OpenStreetMap tile layer
            leafletLib.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '&copy; Esri'
            }).addTo(map);

            console.log('Map created, adding marker layer');
            // Create a layer for markers
            markersLayer = leafletLib.layerGroup().addTo(map);

            // Setup view switcher
            setupViewSwitcher();
        } catch (error) {
            console.error('Error creating map:', error);
            Notification.exception({message: `${strings.mapCreationError}: ${error.message}`});
        }
    };

    /**
     * Load universities data via AJAX
     */
    const loadUniversities = async(cmid) => {
        console.log('Loading universities data');

        // Show loading indicator
        $('#university-map').addClass('loading');

        try {
            const response = await Ajax.call([{
                methodname: 'mod_dhbwio_get_universities',
                args: { cmid: cmid }
            }])[0];

            console.log('Universities data loaded:', response);
            if (response && response.universities) {
                addUniversitiesToMap(response.universities);
            }
            $('#university-map').removeClass('loading');
        } catch (error) {
            console.error('Error loading universities:', error);
            Notification.exception({
                message: strings.mapLoadingError,
                error: error
            });
            $('#university-map').removeClass('loading');
        }
    };

    /**
     * Add universities to the map
     */
    const addUniversitiesToMap = (universities) => {
        console.log('Adding universities to map');

        // Clear existing markers
        markersLayer.clearLayers();

        // Group universities by country for clustering
        const countryClusters = {};

        // Add markers for each university
        universities.forEach((university) => {
            if (university.latitude && university.longitude) {
                // Create marker
                const marker = leafletLib.marker([university.latitude, university.longitude])
                    .bindPopup(createPopupContent(university));

                // Add to layer
                markersLayer.addLayer(marker);

                // Group by country for clustering
                if (!countryClusters[university.country]) {
                    countryClusters[university.country] = [];
                }
                countryClusters[university.country].push(marker);
            }
        });

        // Adjust map view to show all markers
        if (markersLayer.getLayers().length > 0) {
            try {
                const bounds = leafletLib.featureGroup(markersLayer.getLayers()).getBounds();
                map.fitBounds(bounds, {
                    padding: [50, 50]
                });
            } catch (error) {
                console.error('Error fitting bounds:', error);
            }
        }
    };

    /**
     * Create popup content for university marker
     */
    const createPopupContent = (university) => {
        let content = '<div class="university-popup">';
        content += `<h4>${university.name}</h4>`;
        content += `<p><strong>${university.city}, ${university.country}</strong></p>`;

        if (university.available_slots) {
            content += `<p>${strings.availableSlots}: ${university.available_slots}</p>`;
        }

        if (university.reports_count) {
            content += `<p>${strings.reports}: ${university.reports_count}</p>`;
        }

        // Add link to university detail page
        content += `<a href="${university.detail_url}" class="btn btn-primary btn-sm mt-2">${strings.viewDetails}</a>`;
        content += '</div>';

        return content;
    };

    /**
     * Setup the view switcher between map and list views
     */
    const setupViewSwitcher = () => {
        $('.dhbwio-view-switcher a').on('click', (e) => {
            e.preventDefault();

            const $this = $(e.currentTarget);
            const view = $this.attr('href').split('view=')[1];

            // Update active class and button styles for all buttons
            $('.dhbwio-view-switcher a').removeClass('active btn-primary').addClass('btn-secondary');
            $this.removeClass('btn-secondary').addClass('active btn-primary');

            // Toggle views
            if (view === 'map') {
                $('#university-list').hide();
                $('#university-map-container').show();
                // Refresh map as it might not render correctly when hidden
                if (map) {
                    map.invalidateSize();
                }
            } else {
                $('#university-map-container').hide();
                $('#university-list').show();
            }
        });
    };

    // Return public interface (AMD-style export)
    return {
        init: init
    };
});
