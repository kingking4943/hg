<?php
/**
 * Map Shortcodes Handler
 * 
 * @package ColitaliaRealEstate
 * @version 1.5.1
 */

namespace Colitalia_Real_Estate\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MapShortcodes {
    
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_shortcode('colitalia_map', array($this, 'render_property_map'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }
    
    /**
     * Render property map shortcode
     */
    public function render_property_map($atts) {
        $atts = shortcode_atts(array(
            'property_id' => get_the_ID(),
            'height' => '400px',
            'show_address' => 'true'
        ), $atts, 'colitalia_map');
        
        $property_id = intval($atts['property_id']);
        $latitude = get_post_meta($property_id, '_colitalia_latitude', true);
        $longitude = get_post_meta($property_id, '_colitalia_longitude', true);
        
        if (!$latitude || !$longitude) {
            return '<p class="colitalia-no-map">' . __('Mappa non disponibile per questa proprietà.', 'colitalia-real-estate') . '</p>';
        }
        
        // Enqueue scripts
        $this->enqueue_map_scripts();
        
        // Render template
        ob_start();
        include COLITALIA_PLUGIN_PATH . 'templates/property-map.php';
        return ob_get_clean();
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        if (is_singular('proprieta') || has_shortcode(get_post()->post_content ?? '', 'colitalia_map')) {
            $this->enqueue_map_scripts();
        }
    }
    
    /**
     * Enqueue map scripts
     */
    private function enqueue_map_scripts() {
        // Google Maps API
        wp_enqueue_script(
            'google-maps-api',
            'https://maps.googleapis.com/maps/api/js?key=INSERISCI_QUI_LA_TUA_GOOGLE_MAPS_API_KEY&libraries=places',
            array(),
            null,
            true
        );
        
        // Frontend map script
        wp_enqueue_script(
            'colitalia-frontend-map',
            COLITALIA_PLUGIN_URL . 'assets/js/frontend-map.js',
            array('jquery', 'google-maps-api'),
            COLITALIA_PLUGIN_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('colitalia-frontend-map', 'COLITALIA_MAP', array(
            'plugin_url' => COLITALIA_PLUGIN_URL,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('colitalia_map_nonce')
        ));
    }
}
