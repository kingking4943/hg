<?php

namespace ColitaliaRealEstate\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Scheme_Color;
use Elementor\Scheme_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Price Calculator Widget
 */
class PriceCalculatorWidget extends Widget_Base {
    
    public function get_name() {
        return 'colitalia-price-calculator';
    }
    
    public function get_title() {
        return __('Calcolatore Prezzi', 'colitalia-real-estate');
    }
    
    public function get_icon() {
        return 'eicon-price-table';
    }
    
    public function get_categories() {
        return ['colitalia-real-estate'];
    }
    
    public function get_keywords() {
        return ['prezzo', 'calcolatore', 'costo', 'colitalia'];
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
            'calculator_title',
            [
                'label' => __('Titolo Calcolatore', 'colitalia-real-estate'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Calcola il Costo del Soggiorno', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'default_guests',
            [
                'label' => __('Ospiti Default', 'colitalia-real-estate'),
                'type' => Controls_Manager::NUMBER,
                'default' => 2,
                'min' => 1,
                'max' => 20,
            ]
        );
        
        $this->add_control(
            'show_services',
            [
                'label' => __('Mostra Servizi Extra', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_seasonal_rates',
            [
                'label' => __('Mostra Tariffe Stagionali', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_discount_info',
            [
                'label' => __('Info Sconti', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Mostra informazioni su sconti disponibili', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'enable_instant_booking',
            [
                'label' => __('Pulsante Prenota Subito', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'currency_symbol',
            [
                'label' => __('Simbolo Valuta', 'colitalia-real-estate'),
                'type' => Controls_Manager::TEXT,
                'default' => '€',
            ]
        );
        
        $this->end_controls_section();
        
        // Price Display Options
        $this->start_controls_section(
            'price_display_section',
            [
                'label' => __('Visualizzazione Prezzi', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'show_price_breakdown',
            [
                'label' => __('Breakdown Prezzi', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Mostra dettaglio costi per notte/servizi/tasse', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'show_total_savings',
            [
                'label' => __('Mostra Risparmi', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_price_per_person',
            [
                'label' => __('Prezzo per Persona', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'no',
            ]
        );
        
        $this->add_control(
            'price_format',
            [
                'label' => __('Formato Prezzo', 'colitalia-real-estate'),
                'type' => Controls_Manager::SELECT,
                'default' => 'total',
                'options' => [
                    'total' => __('Totale', 'colitalia-real-estate'),
                    'per_night' => __('Per Notte', 'colitalia-real-estate'),
                    'per_week' => __('Per Settimana', 'colitalia-real-estate'),
                    'both' => __('Totale e Per Notte', 'colitalia-real-estate'),
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Calculator
        $this->start_controls_section(
            'style_calculator',
            [
                'label' => __('Stile Calcolatore', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'calculator_background',
            [
                'label' => __('Sfondo Calcolatore', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .colitalia-price-calculator' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'calculator_border',
                'label' => __('Bordo', 'colitalia-real-estate'),
                'selector' => '{{WRAPPER}} .colitalia-price-calculator',
            ]
        );
        
        $this->add_control(
            'calculator_border_radius',
            [
                'label' => __('Border Radius', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-price-calculator' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'calculator_padding',
            [
                'label' => __('Padding', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-price-calculator' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Price Display
        $this->start_controls_section(
            'style_prices',
            [
                'label' => __('Visualizzazione Prezzi', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'total_price_heading',
            [
                'label' => __('Prezzo Totale', 'colitalia-real-estate'),
                'type' => Controls_Manager::HEADING,
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'total_price_typography',
                'label' => __('Tipografia Prezzo Totale', 'colitalia-real-estate'),
                'scheme' => Scheme_Typography::TYPOGRAPHY_1,
                'selector' => '{{WRAPPER}} .total-price',
            ]
        );
        
        $this->add_control(
            'total_price_color',
            [
                'label' => __('Colore Prezzo Totale', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'scheme' => [
                    'type' => Scheme_Color::get_type(),
                    'value' => Scheme_Color::COLOR_1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .total-price' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'breakdown_heading',
            [
                'label' => __('Breakdown Prezzi', 'colitalia-real-estate'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'breakdown_typography',
                'label' => __('Tipografia Breakdown', 'colitalia-real-estate'),
                'scheme' => Scheme_Typography::TYPOGRAPHY_3,
                'selector' => '{{WRAPPER}} .price-breakdown',
            ]
        );
        
        $this->add_control(
            'breakdown_color',
            [
                'label' => __('Colore Breakdown', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .price-breakdown' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'savings_color',
            [
                'label' => __('Colore Risparmi', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'default' => '#4CAF50',
                'selectors' => [
                    '{{WRAPPER}} .savings-info' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Form Elements
        $this->start_controls_section(
            'style_form_elements',
            [
                'label' => __('Elementi Form', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'input_typography',
                'label' => __('Tipografia Input', 'colitalia-real-estate'),
                'scheme' => Scheme_Typography::TYPOGRAPHY_3,
                'selector' => '{{WRAPPER}} .calculator-input, {{WRAPPER}} .calculator-select',
            ]
        );
        
        $this->add_control(
            'input_background',
            [
                'label' => __('Sfondo Input', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calculator-input, {{WRAPPER}} .calculator-select' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'input_border_color',
            [
                'label' => __('Colore Bordo Input', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calculator-input, {{WRAPPER}} .calculator-select' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Booking Button
        $this->start_controls_section(
            'style_booking_button',
            [
                'label' => __('Pulsante Prenotazione', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'enable_instant_booking' => 'yes',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'booking_button_typography',
                'label' => __('Tipografia Pulsante', 'colitalia-real-estate'),
                'scheme' => Scheme_Typography::TYPOGRAPHY_4,
                'selector' => '{{WRAPPER}} .calculator-book-now',
            ]
        );
        
        $this->start_controls_tabs('booking_button_tabs');
        
        $this->start_controls_tab(
            'booking_button_normal',
            [
                'label' => __('Normale', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'booking_button_text_color',
            [
                'label' => __('Colore Testo', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .calculator-book-now' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'booking_button_bg_color',
            [
                'label' => __('Colore Sfondo', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'scheme' => [
                    'type' => Scheme_Color::get_type(),
                    'value' => Scheme_Color::COLOR_1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .calculator-book-now' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->start_controls_tab(
            'booking_button_hover',
            [
                'label' => __('Hover', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'booking_button_hover_text_color',
            [
                'label' => __('Colore Testo Hover', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calculator-book-now:hover' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'booking_button_hover_bg_color',
            [
                'label' => __('Colore Sfondo Hover', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .calculator-book-now:hover' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->add_control(
            'booking_button_padding',
            [
                'label' => __('Padding Pulsante', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .calculator-book-now' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'booking_button_border_radius',
            [
                'label' => __('Border Radius', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .calculator-book-now' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
        
        // Dati proprietà
        $property_data = $this->get_property_data($property_id);
        
        ?>
        <div class="colitalia-price-calculator-widget" data-property-id="<?php echo esc_attr($property_id); ?>">
            
            <?php if ($settings['calculator_title']): ?>
            <h3 class="calculator-title"><?php echo esc_html($settings['calculator_title']); ?></h3>
            <?php endif; ?>
            
            <div class="colitalia-price-calculator">
                <!-- Date Selection -->
                <div class="calculator-section date-selection">
                    <div class="section-title"><?php _e('Seleziona le Date', 'colitalia-real-estate'); ?></div>
                    
                    <div class="date-inputs">
                        <div class="input-group">
                            <label for="calc-check-in"><?php _e('Check-in', 'colitalia-real-estate'); ?></label>
                            <input type="date" id="calc-check-in" class="calculator-input" name="check_in">
                        </div>
                        <div class="input-group">
                            <label for="calc-check-out"><?php _e('Check-out', 'colitalia-real-estate'); ?></label>
                            <input type="date" id="calc-check-out" class="calculator-input" name="check_out">
                        </div>
                    </div>
                    
                    <div class="nights-display">
                        <span class="nights-count">0</span> <?php _e('notti', 'colitalia-real-estate'); ?>
                    </div>
                </div>
                
                <!-- Guests Selection -->
                <div class="calculator-section guests-selection">
                    <div class="section-title"><?php _e('Ospiti', 'colitalia-real-estate'); ?></div>
                    
                    <div class="guests-selector">
                        <button type="button" class="guests-btn decrease" data-action="decrease">-</button>
                        <span class="guests-count"><?php echo esc_html($settings['default_guests']); ?></span>
                        <button type="button" class="guests-btn increase" data-action="increase">+</button>
                    </div>
                    
                    <small class="guests-limit">
                        <?php printf(__('Max %s ospiti', 'colitalia-real-estate'), $property_data['max_guests'] ?: 8); ?>
                    </small>
                </div>
                
                <?php if ($settings['show_services'] === 'yes'): ?>
                <!-- Extra Services -->
                <div class="calculator-section services-selection">
                    <div class="section-title"><?php _e('Servizi Extra', 'colitalia-real-estate'); ?></div>
                    
                    <div class="services-list">
                        <?php $this->render_services($property_id); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($settings['show_seasonal_rates'] === 'yes'): ?>
                <!-- Seasonal Info -->
                <div class="calculator-section seasonal-info">
                    <div class="season-notice" id="season-notice" style="display: none;">
                        <i class="fas fa-info-circle"></i>
                        <span class="season-text"></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Price Summary -->
                <div class="calculator-section price-summary">
                    
                    <?php if ($settings['show_price_breakdown'] === 'yes'): ?>
                    <div class="price-breakdown">
                        <div class="breakdown-item">
                            <span class="item-description"><?php _e('Soggiorno base', 'colitalia-real-estate'); ?></span>
                            <span class="item-price" id="base-price"><?php echo $settings['currency_symbol']; ?>0</span>
                        </div>
                        
                        <div class="breakdown-item services-cost" style="display: none;">
                            <span class="item-description"><?php _e('Servizi extra', 'colitalia-real-estate'); ?></span>
                            <span class="item-price" id="services-price"><?php echo $settings['currency_symbol']; ?>0</span>
                        </div>
                        
                        <div class="breakdown-item cleaning-fee" style="display: none;">
                            <span class="item-description"><?php _e('Pulizia finale', 'colitalia-real-estate'); ?></span>
                            <span class="item-price" id="cleaning-price"><?php echo $settings['currency_symbol']; ?>0</span>
                        </div>
                        
                        <div class="breakdown-item taxes" style="display: none;">
                            <span class="item-description"><?php _e('Tasse', 'colitalia-real-estate'); ?></span>
                            <span class="item-price" id="taxes-price"><?php echo $settings['currency_symbol']; ?>0</span>
                        </div>
                        
                        <?php if ($settings['show_total_savings'] === 'yes'): ?>
                        <div class="breakdown-item savings-item" id="savings-item" style="display: none;">
                            <span class="item-description savings-info"><?php _e('Sconto applicato', 'colitalia-real-estate'); ?></span>
                            <span class="item-price savings-info" id="savings-amount">-<?php echo $settings['currency_symbol']; ?>0</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="breakdown-separator"></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="total-price-container">
                        <?php if (in_array($settings['price_format'], ['both', 'per_night'])): ?>
                        <div class="per-night-price">
                            <span class="price-label"><?php _e('Per notte:', 'colitalia-real-estate'); ?></span>
                            <span class="price-value" id="per-night-price"><?php echo $settings['currency_symbol']; ?>0</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (in_array($settings['price_format'], ['total', 'both'])): ?>
                        <div class="total-price">
                            <span class="price-label"><?php _e('Totale:', 'colitalia-real-estate'); ?></span>
                            <span class="price-value" id="total-price"><?php echo $settings['currency_symbol']; ?>0</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($settings['show_price_per_person'] === 'yes'): ?>
                        <div class="per-person-price">
                            <span class="price-label"><?php _e('Per persona:', 'colitalia-real-estate'); ?></span>
                            <span class="price-value" id="per-person-price"><?php echo $settings['currency_symbol']; ?>0</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($settings['show_discount_info'] === 'yes'): ?>
                <!-- Discount Info -->
                <div class="calculator-section discount-info">
                    <div class="discount-notices" id="discount-notices"></div>
                </div>
                <?php endif; ?>
                
                <?php if ($settings['enable_instant_booking'] === 'yes'): ?>
                <!-- Booking Button -->
                <div class="calculator-section booking-action">
                    <button type="button" class="calculator-book-now" id="book-now-btn" disabled>
                        <i class="fas fa-calendar-check"></i>
                        <?php _e('Prenota Subito', 'colitalia-real-estate'); ?>
                    </button>
                    
                    <div class="booking-notice">
                        <small><?php _e('Seleziona le date per procedere con la prenotazione', 'colitalia-real-estate'); ?></small>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Loading Overlay -->
                <div class="calculator-loading" id="calculator-loading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span><?php _e('Calcolo in corso...', 'colitalia-real-estate'); ?></span>
                </div>
            </div>
        </div>
        
        <?php
        // Enqueue calculator scripts
        $this->enqueue_calculator_scripts($property_id, $settings);
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
    
    private function get_property_data($property_id) {
        return [
            'max_guests' => get_post_meta($property_id, '_max_guests', true) ?: 8,
            'base_price' => get_post_meta($property_id, '_price', true) ?: 0,
            'weekly_price' => get_post_meta($property_id, '_weekly_price', true) ?: 0,
            'cleaning_fee' => get_post_meta($property_id, '_cleaning_fee', true) ?: 0,
            'tax_rate' => get_post_meta($property_id, '_tax_rate', true) ?: 0,
            'seasonal_rates' => get_post_meta($property_id, '_seasonal_rates', true) ?: [],
        ];
    }
    
    private function render_services($property_id) {
        // Recupera servizi dalla proprietà
        $services = get_post_meta($property_id, '_extra_services', true) ?: [];
        
        if (empty($services)) {
            // Servizi default
            $services = [
                'cleaning_extra' => ['name' => 'Pulizia Extra', 'price' => 50, 'type' => 'once'],
                'linens' => ['name' => 'Biancheria', 'price' => 15, 'type' => 'per_person'],
                'parking' => ['name' => 'Parcheggio', 'price' => 10, 'type' => 'per_night'],
                'wifi' => ['name' => 'Wi-Fi Premium', 'price' => 5, 'type' => 'per_night'],
                'breakfast' => ['name' => 'Colazione', 'price' => 12, 'type' => 'per_person_per_night'],
            ];
        }
        
        foreach ($services as $key => $service) {
            $price_text = '';
            switch ($service['type']) {
                case 'once':
                    $price_text = '+€' . $service['price'];
                    break;
                case 'per_person':
                    $price_text = '+€' . $service['price'] . ' a persona';
                    break;
                case 'per_night':
                    $price_text = '+€' . $service['price'] . ' a notte';
                    break;
                case 'per_person_per_night':
                    $price_text = '+€' . $service['price'] . ' a persona/notte';
                    break;
            }
            
            ?>
            <label class="service-option">
                <input type="checkbox" 
                       name="services[]" 
                       value="<?php echo esc_attr($key); ?>" 
                       data-price="<?php echo esc_attr($service['price']); ?>" 
                       data-type="<?php echo esc_attr($service['type']); ?>">
                <span class="service-name"><?php echo esc_html($service['name']); ?></span>
                <span class="service-price"><?php echo esc_html($price_text); ?></span>
            </label>
            <?php
        }
    }
    
    private function enqueue_calculator_scripts($property_id, $settings) {
        // Localizza dati per il calcolatore
        wp_localize_script('colitalia-elementor-widgets', 'colitaliaCalculator_' . $property_id, [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('colitalia_calculator_nonce'),
            'property_id' => $property_id,
            'settings' => $settings,
            'property_data' => $this->get_property_data($property_id),
            'strings' => [
                'calculating' => __('Calcolo in corso...', 'colitalia-real-estate'),
                'error' => __('Errore nel calcolo', 'colitalia-real-estate'),
                'select_dates' => __('Seleziona le date', 'colitalia-real-estate'),
                'invalid_dates' => __('Date non valide', 'colitalia-real-estate'),
                'night' => __('notte', 'colitalia-real-estate'),
                'nights' => __('notti', 'colitalia-real-estate'),
                'guest' => __('ospite', 'colitalia-real-estate'),
                'guests' => __('ospiti', 'colitalia-real-estate'),
                'weekly_discount' => __('Sconto soggiorno settimanale (-10%)', 'colitalia-real-estate'),
                'monthly_discount' => __('Sconto soggiorno mensile (-20%)', 'colitalia-real-estate'),
                'early_booking' => __('Sconto prenotazione anticipata (-5%)', 'colitalia-real-estate'),
                'last_minute' => __('Offerta last minute (-15%)', 'colitalia-real-estate'),
                'high_season' => __('Alta stagione (+20%)', 'colitalia-real-estate'),
                'low_season' => __('Bassa stagione (-15%)', 'colitalia-real-estate'),
            ],
        ]);
    }
}
