<?php

namespace ColitaliaRealEstate\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Scheme_Color;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calendar Widget
 */
class CalendarWidget extends Widget_Base {
    
    public function get_name() {
        return 'colitalia-calendar';
    }
    
    public function get_title() {
        return __('Calendario Disponibilità', 'colitalia-real-estate');
    }
    
    public function get_icon() {
        return 'eicon-calendar';
    }
    
    public function get_categories() {
        return ['colitalia-real-estate'];
    }
    
    public function get_keywords() {
        return ['calendario', 'disponibilità', 'booking', 'colitalia'];
    }
    
    protected function _register_controls() {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Contenuto', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'property_id',
            [
                'label' => __('Proprietà', 'colitalia-real-estate'),
                'type' => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => $this->get_properties_list(),
                'description' => __('Seleziona una proprietà specifica o usa rilevamento automatico', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'view_mode',
            [
                'label' => __('Modalità Vista', 'colitalia-real-estate'),
                'type' => Controls_Manager::SELECT,
                'default' => 'month',
                'options' => [
                    'month' => __('Mensile', 'colitalia-real-estate'),
                    'week' => __('Settimanale', 'colitalia-real-estate'),
                    'list' => __('Lista Eventi', 'colitalia-real-estate'),
                ],
            ]
        );
        
        $this->add_control(
            'default_date',
            [
                'label' => __('Data Iniziale', 'colitalia-real-estate'),
                'type' => Controls_Manager::DATE_TIME,
                'default' => date('Y-m-d'),
                'picker_options' => [
                    'dateFormat' => 'Y-m-d',
                ],
            ]
        );
        
        $this->add_control(
            'show_legend',
            [
                'label' => __('Mostra Legenda', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'enable_booking',
            [
                'label' => __('Abilita Prenotazione Diretta', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'no',
                'description' => __('Permetti di cliccare sulle date disponibili per iniziare una prenotazione', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'min_stay',
            [
                'label' => __('Soggiorno Minimo (giorni)', 'colitalia-real-estate'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'min' => 1,
                'max' => 30,
            ]
        );
        
        $this->add_control(
            'max_months',
            [
                'label' => __('Mesi da Mostrare', 'colitalia-real-estate'),
                'type' => Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'max' => 24,
                'description' => __('Numero di mesi futuri da mostrare nel calendario', 'colitalia-real-estate'),
            ]
        );
        
        $this->end_controls_section();
        
        // Display Options
        $this->start_controls_section(
            'display_options',
            [
                'label' => __('Opzioni Display', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'show_prices',
            [
                'label' => __('Mostra Prezzi', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_booking_info',
            [
                'label' => __('Info Prenotazioni', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Mostra informazioni sulle prenotazioni esistenti (solo per admin)', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'show_navigation',
            [
                'label' => __('Mostra Navigazione', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_today_button',
            [
                'label' => __('Pulsante "Oggi"', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Calendar
        $this->start_controls_section(
            'style_calendar',
            [
                'label' => __('Stile Calendario', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'calendar_background',
            [
                'label' => __('Sfondo Calendario', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .colitalia-calendar' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'calendar_border_color',
            [
                'label' => __('Colore Bordo', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .colitalia-calendar, {{WRAPPER}} .calendar-day' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'calendar_padding',
            [
                'label' => __('Padding Calendario', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-calendar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'calendar_border_radius',
            [
                'label' => __('Border Radius', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-calendar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Days
        $this->start_controls_section(
            'style_days',
            [
                'label' => __('Giorni', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'available_color',
            [
                'label' => __('Colore Disponibile', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'default' => '#4CAF50',
                'selectors' => [
                    '{{WRAPPER}} .calendar-day.available' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'booked_color',
            [
                'label' => __('Colore Prenotato', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'default' => '#f44336',
                'selectors' => [
                    '{{WRAPPER}} .calendar-day.booked' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'pending_color',
            [
                'label' => __('Colore In Attesa', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'default' => '#FF9800',
                'selectors' => [
                    '{{WRAPPER}} .calendar-day.pending' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'unavailable_color',
            [
                'label' => __('Colore Non Disponibile', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'default' => '#9E9E9E',
                'selectors' => [
                    '{{WRAPPER}} .calendar-day.unavailable' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'today_color',
            [
                'label' => __('Colore Oggi', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2196F3',
                'selectors' => [
                    '{{WRAPPER}} .calendar-day.today' => 'border-color: {{VALUE}}; border-width: 3px;',
                ],
            ]
        );
        
        $this->add_control(
            'day_text_color',
            [
                'label' => __('Colore Testo Giorni', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-day' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Header
        $this->start_controls_section(
            'style_header',
            [
                'label' => __('Header Calendario', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'header_background',
            [
                'label' => __('Sfondo Header', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-header' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'header_text_color',
            [
                'label' => __('Colore Testo Header', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-header' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'nav_button_color',
            [
                'label' => __('Colore Pulsanti Navigazione', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-nav-button' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Legend
        $this->start_controls_section(
            'style_legend',
            [
                'label' => __('Legenda', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_legend' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'legend_background',
            [
                'label' => __('Sfondo Legenda', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-legend' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'legend_text_color',
            [
                'label' => __('Colore Testo Legenda', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calendar-legend' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Determina property_id
        $property_id = $settings['property_id'];
        if ($property_id === 'auto') {
            global $post;
            if ($post && $post->post_type === 'proprieta') {
                $property_id = $post->ID;
            }
        }
        
        if (empty($property_id) || $property_id === 'auto') {
            if (is_user_logged_in() && current_user_can('edit_posts')) {
                echo '<div class="colitalia-elementor-notice">' . __('Seleziona una proprietà nelle impostazioni del widget o visualizza questo widget su una pagina di proprietà.', 'colitalia-real-estate') . '</div>';
            }
            return;
        }
        
        // Configurazione calendario
        $calendar_config = [
            'property_id' => $property_id,
            'view_mode' => $settings['view_mode'],
            'default_date' => $settings['default_date'],
            'show_legend' => $settings['show_legend'],
            'enable_booking' => $settings['enable_booking'],
            'min_stay' => $settings['min_stay'],
            'max_months' => $settings['max_months'],
            'show_prices' => $settings['show_prices'],
            'show_booking_info' => $settings['show_booking_info'] && current_user_can('manage_options'),
            'show_navigation' => $settings['show_navigation'],
            'show_today_button' => $settings['show_today_button'],
        ];
        
        ?>
        <div class="colitalia-calendar-widget" data-config="<?php echo esc_attr(json_encode($calendar_config)); ?>">
            
            <?php if ($settings['show_navigation'] === 'yes'): ?>
            <!-- Calendar Header -->
            <div class="calendar-header">
                <div class="calendar-navigation">
                    <button class="calendar-nav-button prev-month" title="<?php _e('Mese precedente', 'colitalia-real-estate'); ?>">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="current-month-year">
                        <span class="current-month"></span>
                        <span class="current-year"></span>
                    </div>
                    <button class="calendar-nav-button next-month" title="<?php _e('Mese successivo', 'colitalia-real-estate'); ?>">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                
                <div class="calendar-controls">
                    <?php if ($settings['show_today_button'] === 'yes'): ?>
                    <button class="calendar-today-btn"><?php _e('Oggi', 'colitalia-real-estate'); ?></button>
                    <?php endif; ?>
                    
                    <div class="view-mode-selector">
                        <button class="view-mode-btn <?php echo $settings['view_mode'] === 'month' ? 'active' : ''; ?>" data-view="month"><?php _e('Mese', 'colitalia-real-estate'); ?></button>
                        <button class="view-mode-btn <?php echo $settings['view_mode'] === 'week' ? 'active' : ''; ?>" data-view="week"><?php _e('Settimana', 'colitalia-real-estate'); ?></button>
                        <button class="view-mode-btn <?php echo $settings['view_mode'] === 'list' ? 'active' : ''; ?>" data-view="list"><?php _e('Lista', 'colitalia-real-estate'); ?></button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Calendar Container -->
            <div class="colitalia-calendar" id="calendar-<?php echo esc_attr($property_id); ?>">
                <div class="calendar-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span><?php _e('Caricamento calendario...', 'colitalia-real-estate'); ?></span>
                </div>
            </div>
            
            <?php if ($settings['show_legend'] === 'yes'): ?>
            <!-- Calendar Legend -->
            <div class="calendar-legend">
                <div class="legend-title"><?php _e('Legenda:', 'colitalia-real-estate'); ?></div>
                <div class="legend-items">
                    <div class="legend-item">
                        <span class="legend-color available"></span>
                        <span class="legend-text"><?php _e('Disponibile', 'colitalia-real-estate'); ?></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color booked"></span>
                        <span class="legend-text"><?php _e('Prenotato', 'colitalia-real-estate'); ?></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color pending"></span>
                        <span class="legend-text"><?php _e('In Attesa', 'colitalia-real-estate'); ?></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color unavailable"></span>
                        <span class="legend-text"><?php _e('Non Disponibile', 'colitalia-real-estate'); ?></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color today"></span>
                        <span class="legend-text"><?php _e('Oggi', 'colitalia-real-estate'); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Tooltip per dettagli giorno -->
            <div id="calendar-tooltip" class="calendar-tooltip" style="display: none;">
                <div class="tooltip-content"></div>
            </div>
            
            <?php if ($settings['enable_booking'] === 'yes'): ?>
            <!-- Quick Booking Modal -->
            <div id="quick-booking-modal" class="quick-booking-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><?php _e('Prenota Date Selezionate', 'colitalia-real-estate'); ?></h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="selected-dates"></div>
                        <div class="booking-summary"></div>
                        <a href="#" class="btn-proceed-booking"><?php _e('Procedi con la Prenotazione', 'colitalia-real-estate'); ?></a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php
        // Enqueue calendar scripts
        $this->enqueue_calendar_scripts($property_id);
    }
    
    private function get_properties_list() {
        $properties = ['auto' => __('Rilevamento Automatico', 'colitalia-real-estate')];
        
        $posts = get_posts([
            'post_type' => 'proprieta',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);
        
        foreach ($posts as $post) {
            $properties[$post->ID] = $post->post_title;
        }
        
        return $properties;
    }
    
    private function enqueue_calendar_scripts($property_id) {
        // Enqueue FullCalendar se non già caricato
        if (!wp_script_is('fullcalendar', 'enqueued')) {
            wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js', [], '6.1.8', true);
        }
        
        // Localizza dati per il calendario
        wp_localize_script('colitalia-elementor-widgets', 'colitaliaCalendar_' . $property_id, [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('colitalia_calendar_nonce'),
            'property_id' => $property_id,
            'strings' => [
                'loading' => __('Caricamento...', 'colitalia-real-estate'),
                'error' => __('Errore nel caricamento del calendario', 'colitalia-real-estate'),
                'no_data' => __('Nessun dato disponibile', 'colitalia-real-estate'),
                'available' => __('Disponibile', 'colitalia-real-estate'),
                'booked' => __('Prenotato', 'colitalia-real-estate'),
                'pending' => __('In Attesa', 'colitalia-real-estate'),
                'unavailable' => __('Non Disponibile', 'colitalia-real-estate'),
                'today' => __('Oggi', 'colitalia-real-estate'),
                'months' => [
                    __('Gennaio', 'colitalia-real-estate'),
                    __('Febbraio', 'colitalia-real-estate'),
                    __('Marzo', 'colitalia-real-estate'),
                    __('Aprile', 'colitalia-real-estate'),
                    __('Maggio', 'colitalia-real-estate'),
                    __('Giugno', 'colitalia-real-estate'),
                    __('Luglio', 'colitalia-real-estate'),
                    __('Agosto', 'colitalia-real-estate'),
                    __('Settembre', 'colitalia-real-estate'),
                    __('Ottobre', 'colitalia-real-estate'),
                    __('Novembre', 'colitalia-real-estate'),
                    __('Dicembre', 'colitalia-real-estate'),
                ],
                'days_short' => [
                    __('Dom', 'colitalia-real-estate'),
                    __('Lun', 'colitalia-real-estate'),
                    __('Mar', 'colitalia-real-estate'),
                    __('Mer', 'colitalia-real-estate'),
                    __('Gio', 'colitalia-real-estate'),
                    __('Ven', 'colitalia-real-estate'),
                    __('Sab', 'colitalia-real-estate'),
                ],
            ],
        ]);
    }
}
