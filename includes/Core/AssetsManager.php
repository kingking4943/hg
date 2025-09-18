<?php
/**
 * Assets Manager
 * * Handles CSS and JavaScript enqueuing for admin and frontend
 * * @package ColitaliaRealEstate
 * @subpackage Core
 */

namespace ColitaliaRealEstate\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AssetsManager
 * * Gestisce il caricamento di CSS e JavaScript per admin e frontend
 */
class AssetsManager {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook_suffix) {
        global $post_type, $pagenow;
        
        // Only load on plugin pages
        if (!$this->should_load_admin_assets($hook_suffix, $post_type, $pagenow)) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'colitalia-admin-css',
            COLITALIA_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            COLITALIA_PLUGIN_VERSION
        );
        
        // WordPress media uploader
        wp_enqueue_media();
        
        // WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // jQuery UI for sortable gallery
        wp_enqueue_script('jquery-ui-sortable');
        
        // Admin JavaScript
        wp_enqueue_script(
            'colitalia-admin-js',
            COLITALIA_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker', 'media-upload', 'thickbox', 'jquery-ui-sortable'),
            COLITALIA_PLUGIN_VERSION,
            true
        );

        // MODIFICA: Spostata qui la localizzazione dello script per risolvere l'errore
        wp_localize_script('colitalia-admin-js', 'colitaliaAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('colitalia_admin_nonce'),
            'strings' => array(
                'chooseImages' => __('Scegli immagini per la galleria', 'colitalia-real-estate'),
                'addToGallery' => __('Aggiungi alla galleria', 'colitalia-real-estate'),
                'removeImage' => __('Rimuovi immagine', 'colitalia-real-estate'),
                'confirmDelete' => __('Sei sicuro di voler eliminare questo elemento?', 'colitalia-real-estate'),
                'invalidNumber' => __('Il campo %s deve contenere un numero valido', 'colitalia-real-estate'),
                'invalidEmail' => __('Inserisci un indirizzo email valido', 'colitalia-real-estate'),
                'invalidDate' => __('Inserisci una data valida', 'colitalia-real-estate'),
                'loading' => __('Caricamento...', 'colitalia-real-estate'),
                'saved' => __('Salvato', 'colitalia-real-estate'),
                'error' => __('Errore', 'colitalia-real-estate')
            )
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Frontend CSS
        wp_enqueue_style(
            'colitalia-frontend-css',
            COLITALIA_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            COLITALIA_PLUGIN_VERSION
        );
        
        // Booking & Calendar CSS
        wp_enqueue_style(
            'colitalia-booking-css',
            COLITALIA_PLUGIN_URL . 'assets/css/booking.css',
            array(),
            COLITALIA_PLUGIN_VERSION
        );
        
        // Frontend JavaScript
        wp_enqueue_script(
            'colitalia-frontend-js',
            COLITALIA_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            COLITALIA_PLUGIN_VERSION,
            true
        );
        
        // Booking JavaScript
        wp_enqueue_script(
            'colitalia-booking-js',
            COLITALIA_PLUGIN_URL . 'assets/js/booking.js',
            array('jquery'),
            COLITALIA_PLUGIN_VERSION,
            true
        );
        
        // Calendar JavaScript  
        wp_enqueue_script(
            'colitalia-calendar-js',
            COLITALIA_PLUGIN_URL . 'assets/js/calendar.js',
            array('jquery'),
            COLITALIA_PLUGIN_VERSION,
            true
        );
        
        // FullCalendar CDN (conditional loading)
        if ($this->should_load_calendar_assets()) {
            wp_enqueue_script(
                'fullcalendar',
                'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
                array(),
                '6.1.10',
                true
            );
            
            wp_enqueue_script(
                'fullcalendar-locales',
                'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js',
                array('fullcalendar'),
                '6.1.10',
                true
            );
        }
        
        // Localize frontend scripts
        wp_localize_script('colitalia-frontend-js', 'colitaliaFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('colitalia_frontend_nonce'),
            'strings' => array(
                'loading' => __('Caricamento...', 'colitalia-real-estate'),
                'error' => __('Si è verificato un errore', 'colitalia-real-estate'),
                'noResults' => __('Nessun risultato trovato', 'colitalia-real-estate'),
                'selectDate' => __('Seleziona una data', 'colitalia-real-estate'),
                'invalidDate' => __('Data non valida', 'colitalia-real-estate'),
                'bookingSuccess' => __('Prenotazione completata con successo!', 'colitalia-real-estate'),
                'bookingError' => __('Errore durante la prenotazione', 'colitalia-real-estate')
            )
        ));
        
        // Localize booking scripts
        wp_localize_script('colitalia-booking-js', 'ColitaliaBookingAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('colitalia_booking_nonce'),
            'strings' => array(
                'loading' => __('Caricamento...', 'colitalia-real-estate'),
                'error' => __('Si è verificato un errore', 'colitalia-real-estate'),
                'success' => __('Operazione completata', 'colitalia-real-estate'),
                'confirm' => __('Sei sicuro?', 'colitalia-real-estate'),
                'booking_confirmed' => __('Prenotazione confermata!', 'colitalia-real-estate'),
                'dates_required' => __('Seleziona le date', 'colitalia-real-estate'),
                'guests_required' => __('Indica il numero di ospiti', 'colitalia-real-estate'),
                'personal_data_required' => __('Compila i dati personali', 'colitalia-real-estate'),
                'privacy_consent_required' => __('Accetta il trattamento dei dati', 'colitalia-real-estate'),
                'terms_acceptance_required' => __('Accetta i termini e condizioni', 'colitalia-real-estate'),
            )
        ));
        
        // Localize calendar scripts
        wp_localize_script('colitalia-calendar-js', 'ColitaliaCalendarAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('colitalia_calendar_nonce'),
            'locale' => get_locale(),
            'strings' => array(
                'loading' => __('Caricamento...', 'colitalia-real-estate'),
                'error' => __('Errore nel caricamento', 'colitalia-real-estate'),
                'no_events' => __('Nessun evento', 'colitalia-real-estate'),
                'available' => __('Disponibile', 'colitalia-real-estate'),
                'booked' => __('Prenotato', 'colitalia-real-estate'),
                'blocked' => __('Non disponibile', 'colitalia-real-estate'),
                'maintenance' => __('Manutenzione', 'colitalia-real-estate'),
                'checkin' => __('Check-in', 'colitalia-real-estate'),
                'checkout' => __('Check-out', 'colitalia-real-estate'),
                'price_from' => __('Da €', 'colitalia-real-estate'),
                'per_night' => __('/notte', 'colitalia-real-estate'),
                'guests' => __('ospiti', 'colitalia-real-estate'),
                'nights' => __('notti', 'colitalia-real-estate'),
                'booking_code' => __('Codice prenotazione', 'colitalia-real-estate'),
                'customer' => __('Cliente', 'colitalia-real-estate'),
                'phone' => __('Tel', 'colitalia-real-estate'),
                'notes' => __('Note', 'colitalia-real-estate')
            )
        ));
    }
    
    /**
     * Check if we should load admin assets
     */
    private function should_load_admin_assets($hook_suffix, $post_type, $pagenow) {
        // Load on property post type pages
        if ($post_type === 'proprieta') {
            return true;
        }
        
        // Load on taxonomy pages
        if ($pagenow === 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'tipo_proprieta') {
            return true;
        }
        
        // Load on plugin admin pages
        if ($hook_suffix && strpos($hook_suffix, 'colitalia') !== false) {
            return true;
        }
        
        // Load on dashboard
        if ($pagenow === 'index.php') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get asset version for cache busting
     */
    public static function get_asset_version($file_path) {
        $full_path = COLITALIA_PLUGIN_PATH . $file_path;
        
        if (file_exists($full_path)) {
            return filemtime($full_path);
        }
        
        return COLITALIA_PLUGIN_VERSION;
    }
    
    /**
     * Enqueue specific asset
     */
    public static function enqueue_asset($type, $handle, $file_path, $dependencies = array()) {
        $url = COLITALIA_PLUGIN_URL . $file_path;
        $version = self::get_asset_version($file_path);
        
        if ($type === 'style') {
            wp_enqueue_style($handle, $url, $dependencies, $version);
        } elseif ($type === 'script') {
            wp_enqueue_script($handle, $url, $dependencies, $version, true);
        }
    }
    
    /**
     * Register Google Maps API if needed
     */
    public function maybe_enqueue_google_maps() {
        $google_maps_key = get_option('colitalia_google_maps_api_key');
        
        if (!empty($google_maps_key)) {
            wp_enqueue_script(
                'google-maps-api',
                'https://maps.googleapis.com/maps/api/js?key=' . $google_maps_key . '&libraries=places',
                array(),
                null,
                true
            );
        }
    }
    
    /**
     * Add inline styles for dynamic content
     */
    public function add_inline_styles() {
        $custom_css = '';
        
        // Get property type colors
        $property_types = get_terms(array(
            'taxonomy' => 'tipo_proprieta',
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($property_types)) {
            foreach ($property_types as $type) {
                $color = get_term_meta($type->term_id, 'color', true);
                if ($color) {
                    $custom_css .= ".property-type-{$type->slug} { border-left-color: {$color}; }\n";
                    $custom_css .= ".property-type-{$type->slug} .property-type-badge { background-color: {$color}; }\n";
                }
            }
        }
        
        if (!empty($custom_css)) {
            wp_add_inline_style('colitalia-frontend-css', $custom_css);
        }
    }
    
    /**
     * Check if calendar assets should be loaded
     */
    private function should_load_calendar_assets() {
        global $post;
        
        // Load on property pages
        if (is_singular('proprieta')) {
            return true;
        }
        
        // Load if shortcode is present
        if ($post && (
            has_shortcode($post->post_content, 'colitalia_calendar') ||
            has_shortcode($post->post_content, 'colitalia_availability_calendar') ||
            has_shortcode($post->post_content, 'colitalia_booking_form')
        )) {
            return true;
        }
        
        // Load on booking/calendar admin pages
        if (is_admin() && (
            isset($_GET['page']) && (
                strpos($_GET['page'], 'colitalia-bookings') !== false ||
                strpos($_GET['page'], 'colitalia-calendar') !== false
            )
        )) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Preload critical assets
     */
    public function preload_critical_assets() {
        // Preload critical CSS
        echo '<link rel="preload" href="' . COLITALIA_PLUGIN_URL . 'assets/css/frontend.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
        
        // Preload critical JavaScript
        echo '<link rel="preload" href="' . COLITALIA_PLUGIN_URL . 'assets/js/frontend.js" as="script">' . "\n";
    }
}