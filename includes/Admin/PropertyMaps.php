<?php
/**
 * Property Maps Admin Handler
 * 
 * @package ColitaliaRealEstate
 * @version 1.5.1
 */

namespace Colitalia_Real_Estate\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PropertyMaps {
    
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_map_metabox'));
        add_action('save_post', array($this, 'save_map_data'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add map metabox to property edit screen
     */
    public function add_map_metabox() {
        add_meta_box(
            'colitalia-property-map',
            __('Mappa Proprietà', 'colitalia-real-estate'),
            array($this, 'render_map_metabox'),
            'proprieta',
            'normal',
            'high'
        );
    }
    
    /**
     * Render map metabox
     */
    public function render_map_metabox($post) {
        wp_nonce_field('colitalia_map_nonce', 'colitalia_map_nonce_field');
        
        $latitude = get_post_meta($post->ID, '_colitalia_latitude', true);
        $longitude = get_post_meta($post->ID, '_colitalia_longitude', true);
        $address = get_post_meta($post->ID, '_colitalia_address', true);
        $zoom = get_post_meta($post->ID, '_colitalia_map_zoom', true) ?: 15;
        
        ?>
        <div class="colitalia-map-container">
            <div class="colitalia-map-fields">
                <p>
                    <label for="colitalia_address"><strong><?php _e('Indirizzo:', 'colitalia-real-estate'); ?></strong></label><br>
                    <input type="text" id="colitalia_address" name="colitalia_address" value="<?php echo esc_attr($address); ?>" class="large-text" placeholder="<?php _e('Inserisci indirizzo...', 'colitalia-real-estate'); ?>" />
                    <button type="button" id="colitalia-geocode-btn" class="button"><?php _e('Trova su Mappa', 'colitalia-real-estate'); ?></button>
                </p>
                
                <div class="colitalia-coordinates">
                    <div class="coordinate-field">
                        <label for="colitalia_latitude"><?php _e('Latitudine:', 'colitalia-real-estate'); ?></label>
                        <input type="text" id="colitalia_latitude" name="colitalia_latitude" value="<?php echo esc_attr($latitude); ?>" class="regular-text" readonly />
                    </div>
                    <div class="coordinate-field">
                        <label for="colitalia_longitude"><?php _e('Longitudine:', 'colitalia-real-estate'); ?></label>
                        <input type="text" id="colitalia_longitude" name="colitalia_longitude" value="<?php echo esc_attr($longitude); ?>" class="regular-text" readonly />
                    </div>
                    <div class="coordinate-field">
                        <label for="colitalia_map_zoom"><?php _e('Zoom:', 'colitalia-real-estate'); ?></label>
                        <input type="number" id="colitalia_map_zoom" name="colitalia_map_zoom" value="<?php echo esc_attr($zoom); ?>" min="1" max="20" class="small-text" />
                    </div>
                </div>
            </div>
            
            <div id="colitalia-admin-map" style="height: 400px; width: 100%; margin-top: 15px; border: 1px solid #ddd;"></div>
            
            <p class="description">
                <?php _e('Trascina il marker per regolare la posizione precisa. Le coordinate si aggiorneranno automaticamente.', 'colitalia-real-estate'); ?>
            </p>
        </div>
        
        <style>
            .colitalia-map-container { margin: 15px 0; }
            .colitalia-map-fields { margin-bottom: 15px; }
            .colitalia-coordinates { display: flex; gap: 15px; margin-top: 10px; }
            .coordinate-field { flex: 1; }
            .coordinate-field label { display: block; font-weight: 600; margin-bottom: 5px; }
            #colitalia-geocode-btn { margin-left: 10px; }
            #colitalia-admin-map { border-radius: 4px; }
        </style>
        <?php
    }
    
    /**
     * Save map data
     */
    public function save_map_data($post_id) {
        // Check nonce
        if (!isset($_POST['colitalia_map_nonce_field']) || !wp_verify_nonce($_POST['colitalia_map_nonce_field'], 'colitalia_map_nonce')) {
            return;
        }
        
        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save data
        if (isset($_POST['colitalia_address'])) {
            update_post_meta($post_id, '_colitalia_address', sanitize_text_field($_POST['colitalia_address']));
        }
        
        if (isset($_POST['colitalia_latitude'])) {
            update_post_meta($post_id, '_colitalia_latitude', sanitize_text_field($_POST['colitalia_latitude']));
        }
        
        if (isset($_POST['colitalia_longitude'])) {
            update_post_meta($post_id, '_colitalia_longitude', sanitize_text_field($_POST['colitalia_longitude']));
        }
        
        if (isset($_POST['colitalia_map_zoom'])) {
            update_post_meta($post_id, '_colitalia_map_zoom', absint($_POST['colitalia_map_zoom']));
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        if ($post_type !== 'proprieta') {
            return;
        }
        
        // Google Maps API
        wp_enqueue_script(
            'google-maps-api',
            'https://maps.googleapis.com/maps/api/js?key=INSERISCI_QUI_LA_TUA_GOOGLE_MAPS_API_KEY&libraries=places',
            array(),
            null,
            true
        );
        
        // Admin map script
        wp_enqueue_script(
            'colitalia-admin-map',
            COLITALIA_PLUGIN_URL . 'assets/js/admin-map.js',
            array('jquery', 'google-maps-api'),
            COLITALIA_PLUGIN_VERSION,
            true
        );
    }
}