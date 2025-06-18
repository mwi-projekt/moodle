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

            // Define world bounds to prevent excessive panning
            const worldBounds = leafletLib.latLngBounds(
                leafletLib.latLng(-85, -180), // Southwest corner
                leafletLib.latLng(85, 180)    // Northeast corner
            );

            // Create the map with enhanced options
            map = leafletLib.map('university-map', {
                center: [30, 10],
                zoom: 2,
                minZoom: 1,
                maxZoom: 15,
                maxBounds: worldBounds,
                maxBoundsViscosity: 0.8, // How much to resist dragging outside bounds
                worldCopyJump: true,     // Enable world copy jumping for markers
                zoomControl: false       // We'll add custom zoom control
            });

            // Add custom zoom control in top-right
            leafletLib.control.zoom({
                position: 'topright'
            }).addTo(map);

            // Define base layers
            const baseLayers = createBaseLayers();

            // Add default layer
            baseLayers['Streets'].addTo(map);

            // Create layer control
            const layerControl = leafletLib.control.layers(baseLayers, null, {
                position: 'topleft',
                collapsed: false
            }).addTo(map);

            console.log('Map created, adding marker layer');

            // Create a layer for markers with world wrapping support
            markersLayer = leafletLib.layerGroup().addTo(map);

            // Setup view switcher
            setupViewSwitcher();

            // Add scale control
            leafletLib.control.scale({
                position: 'bottomright',
                metric: true,
                imperial: false
            }).addTo(map);

            // Add loading control
            setupLoadingControl();

        } catch (error) {
            console.error('Error creating map:', error);
            Notification.exception({message: `${strings.mapCreationError}: ${error.message}`});
        }
    };

    /**
     * Create base layers for the map
     */
    const createBaseLayers = () => {
        return {
            'Streets': leafletLib.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 15,
                noWrap: false
            }),

            'Satellite': leafletLib.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '&copy; <a href="https://www.esri.com/">Esri</a>, Maxar, Earthstar Geographics',
                maxZoom: 15,
                noWrap: false
            }),

            'CartoDB Light': leafletLib.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                maxZoom: 15,
                noWrap: false
            }),

            'CartoDB Dark': leafletLib.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                maxZoom: 15,
                noWrap: false
            })
        };
    };

    /**
     * Setup loading control
     */
    const setupLoadingControl = () => {
        // Add loading indicator for tile loading
        let tilesLoading = 0;

        map.on('layeradd', (e) => {
            if (e.layer._url) { // This is a tile layer
                e.layer.on('loading', () => {
                    tilesLoading++;
                    $('#university-map').addClass('tiles-loading');
                });

                e.layer.on('load', () => {
                    tilesLoading--;
                    if (tilesLoading <= 0) {
                        $('#university-map').removeClass('tiles-loading');
                    }
                });
            }
        });
    };

    /**
     * Load universities data via AJAX
     */
    const loadUniversities = async(cmid) => {
        console.log('Loading universities data');

        // Show loading state
        showLoadingState(true);

        try {
            const response = await Ajax.call([{
                methodname: 'mod_dhbwio_get_universities',
                args: { cmid: cmid }
            }])[0];

            console.log('Universities data loaded:', response);
            if (response && response.universities) {
                addUniversitiesToMap(response.universities);
            } else {
                showStatusMessage('warning', 'No universities found');
            }
        } catch (error) {
            console.error('Error loading universities:', error);
            showStatusMessage('error', strings.mapLoadingError);
            Notification.exception({
                message: strings.mapLoadingError,
                error: error
            });
        } finally {
            // Hide loading state
            showLoadingState(false);
        }
    };

    /**
     * Show/hide loading state
     */
    const showLoadingState = (show) => {
        const $mapContainer = $('#university-map');
        const $loadingOverlay = $mapContainer.find('.loading-overlay');

        if (show) {
            $mapContainer.addClass('loading');
            $loadingOverlay.show();
        } else {
            $mapContainer.removeClass('loading');
            setTimeout(() => {
                $loadingOverlay.fadeOut(300);
            }, 500); // Small delay to show completion
        }
    };

    /**
     * Show status message
     */
    const showStatusMessage = (type, message) => {
        const alertClass = {
            'success': 'alert-success',
            'warning': 'alert-warning',
            'error': 'alert-danger',
            'info': 'alert-info'
        }[type] || 'alert-info';

        const icon = {
            'success': 'fa-check-circle',
            'warning': 'fa-exclamation-triangle',
            'error': 'fa-exclamation-circle',
            'info': 'fa-info-circle'
        }[type] || 'fa-info-circle';

        const $statusContainer = $('#map-status-messages');
        if ($statusContainer.length === 0) {
            // Create status container if it doesn't exist
            $('#university-map-container').prepend('<div id="map-status-messages"></div>');
        }

        const statusHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fa ${icon}" aria-hidden="true"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        $('#map-status-messages').html(statusHtml);

        // Auto-hide after 5 seconds for success/info messages
        if (type === 'success' || type === 'info') {
            setTimeout(() => {
                $('#map-status-messages .alert').fadeOut(500);
            }, 5000);
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
        const markers = [];

        // Add markers for each university
        universities.forEach((university) => {
            if (university.latitude && university.longitude) {
                // Create marker with custom icon
                const marker = createUniversityMarker(university);

                // Add to layer and tracking array
                markersLayer.addLayer(marker);
                markers.push(marker);

                // Group by country for potential clustering
                if (!countryClusters[university.country]) {
                    countryClusters[university.country] = [];
                }
                countryClusters[university.country].push(marker);
            }
        });

        // Handle world wrapping for markers
        setupMarkerWorldWrapping(markers);

        // Adjust map view to show all markers
        if (markers.length > 0) {
            fitMapToMarkers(markers);
        }

        // Update university count display
        updateUniversityCount(universities.length);
    };

    /**
     * Create a university marker with custom styling
     */
    const createUniversityMarker = (university) => {
        // Create custom icon based on availability
        const icon = createUniversityIcon(university);

        // Create marker
        const marker = leafletLib.marker([university.latitude, university.longitude], {
            icon: icon,
            title: university.name
        }).bindPopup(createPopupContent(university), {
            maxWidth: 300,
            className: 'university-popup-container'
        });

        // Add hover effects
        marker.on('mouseover', function() {
            this.openPopup();
        });

        // Store university data on marker for easy access
        marker.universityData = university;

        return marker;
    };

    /**
     * Create custom icon for university marker
     */
    const createUniversityIcon = (university) => {
        // Determine icon color based on availability
        let iconColor = '#dc3545'; // Red for no slots
        if (university.available_slots > 0) {
            iconColor = university.available_slots > 5 ? '#28a745' : '#ffc107'; // Green for many, yellow for few
        }

        // Create custom divIcon
        return leafletLib.divIcon({
            className: 'university-marker',
            html: `
                <div class="marker-pin" style="background-color: ${iconColor};">
                    <i class="fa fa-university" style="color: white; font-size: 12px;"></i>
                </div>
                <div class="marker-pulse" style="background-color: ${iconColor};"></div>
            `,
            iconSize: [30, 42],
            iconAnchor: [15, 42],
            popupAnchor: [0, -42]
        });
    };

    /**
     * Setup marker world wrapping
     */
    const setupMarkerWorldWrapping = (markers) => {
        // Handle world copy events
        map.on('worldcopyjump', () => {
            // Re-add markers to ensure they appear on all world copies
            markers.forEach(marker => {
                const latlng = marker.getLatLng();
                // Force marker to update its position on all world copies
                marker.setLatLng([latlng.lat, latlng.lng]);
            });
        });

        // Also handle zoom and pan events for marker visibility
        map.on('zoomend moveend', () => {
            // Ensure markers are visible on current view
            updateMarkerVisibility(markers);
        });
    };

    /**
     * Update marker visibility based on current map view
     */
    const updateMarkerVisibility = (markers) => {
        const bounds = map.getBounds();
        const zoom = map.getZoom();

        markers.forEach(marker => {
            const latlng = marker.getLatLng();

            // Check if marker should be visible
            if (bounds.contains(latlng)) {
                if (!markersLayer.hasLayer(marker)) {
                    markersLayer.addLayer(marker);
                }
            }
        });
    };

    /**
     * Fit map to show all markers optimally
     */
    const fitMapToMarkers = (markers) => {
        try {
            // Create bounds from all markers
            const group = leafletLib.featureGroup(markers);
            const bounds = group.getBounds();

            // Fit map to bounds with padding
            map.fitBounds(bounds, {
                padding: [20, 20],
                maxZoom: 10 // Don't zoom in too much for single markers
            });

            // If only one marker, set a reasonable zoom level
            if (markers.length === 1) {
                map.setZoom(6);
            }

        } catch (error) {
            console.error('Error fitting bounds:', error);
            // Fallback to world view
            map.setView([30, 10], 2);
        }
    };

    /**
     * Update university count display
     */
    const updateUniversityCount = (count) => {
        const $counter = $('#university-counter');
        if ($counter.length) {
            $counter.text(count);
        }

        // Update page title if needed
        if (count > 0) {
            document.title = `${count} Universities - International Office`;
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
