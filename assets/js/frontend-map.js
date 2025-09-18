/**
 * Colitalia Real Estate - Frontend Map Handler (OpenStreetMap Only)
 * Uses: Leaflet.js + OpenStreetMap (Completely Free)
 * Version: 2.1.0
 */

(function($) {
    'use strict';
    
    let maps = [];
    let leafletLoaded = false;
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('.colitalia-map').length > 0) {
            loadLeafletAndInitMaps();
        }
    });
    
    /**
     * Load Leaflet library and initialize maps
     */
    function loadLeafletAndInitMaps() {
        if (typeof L !== 'undefined') {
            leafletLoaded = true;
            initPropertyMaps();
        } else {
            loadLeafletLibrary(function() {
                leafletLoaded = true;
                initPropertyMaps();
            });
        }
    }
    
    /**
     * Load Leaflet library
     */
    function loadLeafletLibrary(callback) {
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
            script.onload = callback;
            document.head.appendChild(script);
        } else {
            callback();
        }
    }
    
    /**
     * Initialize all property maps on page
     */
    function initPropertyMaps() {
        $('.colitalia-map').each(function(index) {
            const mapElement = this;
            const $map = $(mapElement);
            
            const lat = parseFloat($map.data('lat'));
            const lng = parseFloat($map.data('lng'));
            const zoom = parseInt($map.data('zoom')) || 15;
            const address = $map.data('address') || '';
            const title = $map.data('title') || 'Proprietà';
            
            if (!lat || !lng) {
                console.warn('Map coordinates missing for element:', mapElement);
                return;
            }
            
            // Create unique ID if not present
            if (!mapElement.id) {
                mapElement.id = 'colitalia-map-' + index;
            }
            
            // Create map
            const map = L.map(mapElement.id).setView([lat, lng], zoom);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);
            
            // Add marker
            const marker = L.marker([lat, lng]).addTo(map);
            
            // Add popup if title or address available
            if (title || address) {
                let popupContent = '';
                if (title) popupContent += '<h4>' + title + '</h4>';
                if (address) popupContent += '<p>' + address + '</p>';
                marker.bindPopup(popupContent);
            }
            
            // Store map reference
            maps.push({
                element: mapElement,
                map: map,
                marker: marker
            });
            
            console.log('Map initialized for:', title || 'Property ' + index);
        });
    }
    
    /**
     * Initialize property list map (multiple properties)
     */
    function initPropertyListMap() {
        const listMapElement = document.getElementById('colitalia-properties-map');
        if (!listMapElement) return;
        
        const properties = window.colitaliaProperties || [];
        if (properties.length === 0) return;
        
        // Calculate center and bounds
        let bounds = L.latLngBounds();
        let validProperties = properties.filter(p => p.lat && p.lng);
        
        if (validProperties.length === 0) return;
        
        // Create map
        const map = L.map('colitalia-properties-map');
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Add markers for each property
        validProperties.forEach(function(property) {
            const lat = parseFloat(property.lat);
            const lng = parseFloat(property.lng);
            
            if (lat && lng) {
                bounds.extend([lat, lng]);
                
                const marker = L.marker([lat, lng]).addTo(map);
                
                // Create popup content
                let popupContent = '<div class="property-map-popup">';
                if (property.title) {
                    popupContent += '<h4><a href="' + (property.url || '#') + '">' + property.title + '</a></h4>';
                }
                if (property.price) {
                    popupContent += '<p class="price">' + property.price + '</p>';
                }
                if (property.excerpt) {
                    popupContent += '<p>' + property.excerpt + '</p>';
                }
                if (property.url) {
                    popupContent += '<p><a href="' + property.url + '" class="button">Vedi Dettagli</a></p>';
                }
                popupContent += '</div>';
                
                marker.bindPopup(popupContent);
                
                // Handle marker click for property list interaction
                marker.on('click', function() {
                    const propertyCard = document.querySelector('[data-property-id="' + property.id + '"]');
                    if (propertyCard) {
                        propertyCard.scrollIntoView({ behavior: 'smooth' });
                        propertyCard.classList.add('highlighted');
                        setTimeout(() => propertyCard.classList.remove('highlighted'), 2000);
                    }
                });
            }
        });
        
        // Fit map to show all markers
        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [20, 20] });
        }
        
        console.log('Property list map initialized with', validProperties.length, 'properties');
    }
    
    /**
     * Handle property card hover (highlight corresponding marker)
     */
    function setupPropertyCardInteraction() {
        $('.property-card').on('mouseenter', function() {
            const propertyId = $(this).data('property-id');
            // Find corresponding marker and highlight it
            // This would need to be implemented based on your specific needs
        });
    }
    
    /**
     * Resize all maps
     */
    function resizeAllMaps() {
        maps.forEach(function(mapObj) {
            if (mapObj.map) {
                setTimeout(function() {
                    mapObj.map.invalidateSize();
                }, 100);
            }
        });
    }
    
    // Handle window resize
    $(window).on('resize', function() {
        resizeAllMaps();
    });
    
    // Handle WordPress responsive images or content changes
    $(document).on('wp-responsive-preview', function() {
        setTimeout(resizeAllMaps, 300);
    });
    
    // Initialize property list map when available
    $(document).ready(function() {
        setTimeout(function() {
            if (leafletLoaded) {
                initPropertyListMap();
                setupPropertyCardInteraction();
            }
        }, 500);
    });
    
    // Make functions globally available
    window.colitaliaResizeAllMaps = resizeAllMaps;
    window.colitaliaInitPropertyListMap = initPropertyListMap;
    
})(jQuery);

// CSS for map popups
const mapStyles = `
<style>
.property-map-popup h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
}
.property-map-popup h4 a {
    text-decoration: none;
    color: #333;
}
.property-map-popup .price {
    font-weight: bold;
    color: #e74c3c;
    margin: 5px 0;
}
.property-map-popup .button {
    display: inline-block;
    padding: 5px 10px;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 3px;
    font-size: 12px;
}
.property-card.highlighted {
    background-color: #f8f9fa;
    border-left: 4px solid #3498db;
    transition: all 0.3s ease;
}
</style>
`;

// Add styles to head
if (!document.querySelector('#colitalia-map-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'colitalia-map-styles';
    styleElement.innerHTML = mapStyles;
    document.head.appendChild(styleElement);
}
