/**
 * Colitalia Real Estate - Admin Map Handler (OpenStreetMap Only)
 * Uses: Leaflet.js + OpenStreetMap (Completely Free)
 * Version: 2.1.0
 */

(function($) {
    'use strict';
    
    let map;
    let marker;
    let mapSettings = {};
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        loadMapSettings();
    });
    
    /**
     * Load map settings from WordPress options
     */
    function loadMapSettings() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'colitalia_get_map_settings',
                nonce: $('#colitalia_map_nonce').val() || ''
            },
            success: function(response) {
                if (response.success) {
                    mapSettings = response.data;
                    initMap();
                } else {
                    // Use defaults if settings not available
                    setDefaultSettings();
                    initMap();
                }
            },
            error: function() {
                setDefaultSettings();
                initMap();
            }
        });
    }
    
    /**
     * Set default map settings
     */
    function setDefaultSettings() {
        mapSettings = {
            provider: 'openstreetmap',
            default_lat: 41.9027835,
            default_lng: 12.4963655,
            default_zoom: 6,
            marker_style: 'default'
        };
    }
    
    /**
     * Initialize OpenStreetMap
     */
    function initMap() {
        const mapElement = document.getElementById('crem-map-container');
        if (!mapElement) {
            console.warn('Map container not found');
            return;
        }
        
        // Load Leaflet if not already loaded
        if (typeof L === 'undefined') {
            loadLeafletLibrary();
        } else {
            createLeafletMap();
        }
    }
    
    /**
     * Load Leaflet library
     */
    function loadLeafletLibrary() {
        // Add CSS if not present
        if (!document.querySelector('link[href*="leaflet"]')) {
            const cssLink = document.createElement('link');
            cssLink.rel = 'stylesheet';
            cssLink.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            cssLink.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
            cssLink.crossOrigin = '';
            document.head.appendChild(cssLink);
        }
        
        // Add JS if not present
        if (!document.querySelector('script[src*="leaflet"]')) {
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
            script.crossOrigin = '';
            script.onload = function() {
                createLeafletMap();
            };
            document.head.appendChild(script);
        } else {
            createLeafletMap();
        }
    }
    
    /**
     * Create Leaflet Map
     */
    function createLeafletMap() {
        const latField = document.getElementById('crem_property_latitude');
        const lonField = document.getElementById('crem_property_longitude');
        
        // Get coordinates from fields or use defaults
        let startLat = latField && latField.value ? parseFloat(latField.value) : mapSettings.default_lat;
        let startLon = lonField && lonField.value ? parseFloat(lonField.value) : mapSettings.default_lng;
        let startZoom = latField && latField.value ? 15 : mapSettings.default_zoom;
        
        // Create map
        map = L.map('crem-map-container').setView([startLat, startLon], startZoom);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Create marker
        marker = L.marker([startLat, startLon], { 
            draggable: true,
            title: 'Posizione Proprietà'
        }).addTo(map);
        
        // Events
        marker.on('dragend', function(e) {
            const position = marker.getLatLng();
            updateFields(position.lat, position.lng);
            reverseGeocode(position.lat, position.lng);
        });
        
        map.on('click', function(e) {
            const position = e.latlng;
            marker.setLatLng(position);
            updateFields(position.lat, position.lng);
            reverseGeocode(position.lat, position.lng);
        });
        
        console.log('OpenStreetMap initialized successfully');
        
        // Setup search functionality if search button exists
        setupAddressSearch();
    }
    
    /**
     * Setup address search functionality
     */
    function setupAddressSearch() {
        // Handle search button click
        $(document).on('click', '#crem-geocode-btn, .crem-search-address', function(e) {
            e.preventDefault();
            const addressField = document.getElementById('crem_property_address');
            if (addressField && addressField.value) {
                geocodeAddress(addressField.value);
            } else {
                alert('Inserisci un indirizzo da cercare.');
            }
        });
        
        // Handle enter key in address field
        $(document).on('keypress', '#crem_property_address', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                if (this.value) {
                    geocodeAddress(this.value);
                }
            }
        });
    }
    
    /**
     * Geocode address with OpenStreetMap
     */
    function geocodeAddress(address) {
        if (!address) return;
        
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1&countrycodes=it`;
        
        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'User-Agent': 'Colitalia Real Estate Plugin'
            },
            success: function(data) {
                if (data && data.length > 0) {
                    const result = data[0];
                    const lat = parseFloat(result.lat);
                    const lng = parseFloat(result.lon);
                    
                    // Update map
                    map.setView([lat, lng], 16);
                    marker.setLatLng([lat, lng]);
                    updateFields(lat, lng);
                    
                    // Update address field with formatted address
                    const addressField = document.getElementById('crem_property_address');
                    if (addressField) {
                        addressField.value = result.display_name;
                    }
                    
                    console.log('Address found:', result.display_name);
                } else {
                    alert('Indirizzo non trovato. Prova con un indirizzo più specifico.');
                }
            },
            error: function() {
                alert('Errore nella ricerca dell\'indirizzo. Verifica la connessione internet.');
            }
        });
    }
    
    /**
     * Reverse geocode coordinates to address
     */
    function reverseGeocode(lat, lng) {
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`;
        
        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'User-Agent': 'Colitalia Real Estate Plugin'
            },
            success: function(data) {
                if (data && data.display_name) {
                    const addressField = document.getElementById('crem_property_address');
                    if (addressField) {
                        addressField.value = data.display_name;
                    }
                }
            },
            error: function() {
                console.warn('Reverse geocoding failed');
            }
        });
    }
    
    /**
     * Update coordinate fields
     */
    function updateFields(lat, lng) {
        const latField = document.getElementById('crem_property_latitude');
        const lonField = document.getElementById('crem_property_longitude');
        
        if (latField) latField.value = lat.toFixed(8);
        if (lonField) lonField.value = lng.toFixed(8);
        
        // Trigger change events for WordPress meta saving
        $(latField).trigger('change');
        $(lonField).trigger('change');
    }
    
    /**
     * Resize map when container becomes visible
     */
    function resizeMap() {
        if (map) {
            setTimeout(function() {
                map.invalidateSize();
            }, 100);
        }
    }
    
    // Handle WordPress metabox show/hide
    $(document).on('postbox-toggled', function() {
        resizeMap();
    });
    
    // Handle WordPress screen options
    $(document).on('click', '.postbox .hndle, .postbox .handlediv', function() {
        setTimeout(resizeMap, 100);
    });
    
    // Make functions globally available
    window.colitaliaResizeMap = resizeMap;
    window.colitaliaGeocodeAddress = geocodeAddress;
    
})(jQuery);

// Auto-resize on window resize
window.addEventListener('resize', function() {
    if (window.colitaliaResizeMap) {
        window.colitaliaResizeMap();
    }
});
