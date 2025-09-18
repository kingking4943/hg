<?php

namespace ColitaliaRealEstate\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Widget Manager per Elementor Integration
 */
class WidgetManager {
    
    public function __construct() {
        add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets']);
        add_action('elementor/elements/categories_registered', [$this, 'add_category']);
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_styles']);
        add_action('elementor/frontend/after_register_scripts', [$this, 'enqueue_scripts']);
        add_action('elementor/preview/enqueue_styles', [$this, 'enqueue_preview_styles']);
    }
    
    /**
     * Registra i widget personalizzati
     */
    public function register_widgets() {
        // Verifica se Elementor è attivo
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        // Registra tutti i widget
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Widgets\PropertyListWidget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Widgets\BookingFormWidget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Widgets\CalendarWidget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Widgets\PriceCalculatorWidget());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Widgets\TimeshareInvestmentWidget());
    }
    
    /**
     * Aggiunge categoria personalizzata per i widget
     */
    public function add_category($elements_manager) {
        $elements_manager->add_category(
            'colitalia-real-estate',
            [
                'title' => __('Colitalia Real Estate', 'colitalia-real-estate'),
                'icon' => 'fa fa-home',
            ]
        );
    }
    
    /**
     * Enqueue styles per widgets
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'colitalia-elementor-widgets',
            COLITALIA_PLUGIN_URL . 'assets/css/elementor-widgets.css',
            [],
            COLITALIA_PLUGIN_VERSION
        );
    }
    
    /**
     * Enqueue scripts per widgets
     */
    public function enqueue_scripts() {
        wp_register_script(
            'colitalia-elementor-widgets',
            COLITALIA_PLUGIN_URL . 'assets/js/elementor-widgets.js',
            ['jquery', 'elementor-frontend'],
            COLITALIA_PLUGIN_VERSION,
            true
        );
        
        // Localizza script per AJAX
        wp_localize_script('colitalia-elementor-widgets', 'colitaliaElementor', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('colitalia_elementor_nonce'),
            'strings' => [
                'loading' => __('Caricamento...', 'colitalia-real-estate'),
                'error' => __('Errore nel caricamento', 'colitalia-real-estate'),
                'no_properties' => __('Nessuna proprietà trovata', 'colitalia-real-estate'),
                'booking_success' => __('Prenotazione inviata con successo!', 'colitalia-real-estate'),
                'booking_error' => __('Errore nell\'invio della prenotazione', 'colitalia-real-estate'),
            ]
        ]);
        
        wp_enqueue_script('colitalia-elementor-widgets');
    }
    
    /**
     * Enqueue styles per preview
     */
    public function enqueue_preview_styles() {
        $this->enqueue_styles();
    }
}
