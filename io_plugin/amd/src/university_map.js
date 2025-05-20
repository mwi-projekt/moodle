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

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], 
function($, Ajax, Notification, Str) {
    
    // Private variables - must use var for mutable variables
    var map = null;
    var markersLayer = null;
    var leafletLib = null;
    
    /**
     * Initialize the map
     * 
     * @method init
     * @param {int} courseModuleId The course module ID
     */
    var init = function(courseModuleId) {
        var cmid = courseModuleId;
        
        // Wait for DOM to be ready
        $(document).ready(function() {
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
    var initMap = function() {
        console.log('initMap called');
        
        // Check if Leaflet is available
        if (typeof L === 'undefined') {
            if (typeof window.L !== 'undefined') {
                leafletLib = window.L;
                console.log('Found Leaflet as window.L');
            } else {
                console.error('Leaflet library not loaded');
                Notification.exception({message: 'Leaflet library not loaded'});
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
            }).addTo(map);
            
            console.log('Map created, adding marker layer');
            // Create a layer for markers
            markersLayer = leafletLib.layerGroup().addTo(map);
            
            // Setup view switcher
            setupViewSwitcher();
        } catch (error) {
            console.error('Error creating map:', error);
            Notification.exception({message: 'Error creating map: ' + error.message});
        }
    };
    
    /**
     * Load universities data via AJAX
     */
    var loadUniversities = function(cmid) {
        console.log('Loading universities data');
        
        // Show loading indicator
        $('#university-map').addClass('loading');
        
        // Call the web service to get universities
        Ajax.call([{
            methodname: 'mod_dhbwio_get_universities',
            args: { cmid: cmid },
            done: function(response) {
                console.log('Universities data loaded:', response);
                if (response && response.universities) {
                    addUniversitiesToMap(response.universities);
                }
                $('#university-map').removeClass('loading');
            },
            fail: function(error) {
                console.error('Error loading universities:', error);
                Notification.exception(error);
                $('#university-map').removeClass('loading');
            }
        }]);
    };
    
    /**
     * Add universities to the map
     * 
     * @param {Array} universities Array of university objects
     */
    var addUniversitiesToMap = function(universities) {
        console.log('Adding universities to map');
        
        // Clear existing markers
        markersLayer.clearLayers();
        
        // Group universities by country for clustering
        var countryClusters = {};
        
        // Add markers for each university
        universities.forEach(function(university) {
            if (university.latitude && university.longitude) {
                // Create marker
                var marker = leafletLib.marker([university.latitude, university.longitude])
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
                var bounds = leafletLib.featureGroup(markersLayer.getLayers()).getBounds();
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
     * 
     * @param {Object} university University data
     * @returns {String} HTML content for popup
     */
    var createPopupContent = function(university) {
        var content = '<div class="university-popup">';
        content += '<h4>' + university.name + '</h4>';
        content += '<p><strong>' + university.city + ', ' + university.country + '</strong></p>';
        
        if (university.available_slots) {
            content += '<p>Available Slots: ' + university.available_slots + '</p>';
        }
        
        if (university.reports_count) {
            content += '<p>Experience Reports: ' + university.reports_count + '</p>';
        }
        
        // Add link to university detail page
        content += '<a href="' + university.detail_url + '" class="btn btn-primary btn-sm mt-2">View Details</a>';
        content += '</div>';
        
        return content;
    };
    
    /**
     * Setup the view switcher between map and list views
     */
    var setupViewSwitcher = function() {
        $('.dhbwio-view-switcher a').on('click', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var view = $this.attr('href').split('view=')[1];
            
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
    
    return {
        init: init
    };
});