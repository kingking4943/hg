<?php

namespace ColitaliaRealEstate\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Scheme_Color;
use Elementor\Scheme_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Booking Form Widget
 */
class BookingFormWidget extends Widget_Base {
    
    public function get_name() {
        return 'colitalia-booking-form';
    }
    
    public function get_title() {
        return __('Form Prenotazione', 'colitalia-real-estate');
    }
    
    public function get_icon() {
        return 'eicon-form-horizontal';
    }
    
    public function get_categories() {
        return ['colitalia-real-estate'];
    }
    
    public function get_keywords() {
        return ['prenotazione', 'booking', 'form', 'colitalia'];
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
                'label' => __('ID Proprietà', 'colitalia-real-estate'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => $this->get_properties_list(),
                'description' => __('Seleziona una proprietà specifica o lascia vuoto per rilevamento automatico', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'form_title',
            [
                'label' => __('Titolo Form', 'colitalia-real-estate'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Prenota Ora', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'show_calendar',
            [
                'label' => __('Mostra Calendario', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('Mostra il calendario per la selezione delle date', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'show_guest_selector',
            [
                'label' => __('Selettore Ospiti', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
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
            'redirect_after',
            [
                'label' => __('Redirect dopo Invio', 'colitalia-real-estate'),
                'type' => Controls_Manager::URL,
                'placeholder' => __('https://your-domain.com/thank-you', 'colitalia-real-estate'),
                'show_external' => true,
                'default' => [
                    'url' => '',
                    'is_external' => true,
                    'nofollow' => true,
                ],
            ]
        );
        
        $this->add_control(
            'enable_deposit',
            [
                'label' => __('Abilita Acconto', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'deposit_percentage',
            [
                'label' => __('Percentuale Acconto (%)', 'colitalia-real-estate'),
                'type' => Controls_Manager::NUMBER,
                'default' => 30,
                'min' => 10,
                'max' => 100,
                'condition' => [
                    'enable_deposit' => 'yes',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Form Fields Section
        $this->start_controls_section(
            'form_fields_section',
            [
                'label' => __('Campi Form', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'required_fields',
            [
                'label' => __('Campi Obbligatori', 'colitalia-real-estate'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'default' => ['first_name', 'last_name', 'email', 'phone'],
                'options' => [
                    'first_name' => __('Nome', 'colitalia-real-estate'),
                    'last_name' => __('Cognome', 'colitalia-real-estate'),
                    'email' => __('Email', 'colitalia-real-estate'),
                    'phone' => __('Telefono', 'colitalia-real-estate'),
                    'address' => __('Indirizzo', 'colitalia-real-estate'),
                    'city' => __('Città', 'colitalia-real-estate'),
                    'country' => __('Paese', 'colitalia-real-estate'),
                    'document_type' => __('Tipo Documento', 'colitalia-real-estate'),
                    'document_number' => __('Numero Documento', 'colitalia-real-estate'),
                ],
            ]
        );
        
        $this->add_control(
            'show_special_requests',
            [
                'label' => __('Campo Richieste Speciali', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'gdpr_text',
            [
                'label' => __('Testo Consenso GDPR', 'colitalia-real-estate'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => __('Acconsento al trattamento dei miei dati personali secondo la Privacy Policy', 'colitalia-real-estate'),
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Form
        $this->start_controls_section(
            'style_form',
            [
                'label' => __('Stile Form', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'form_background_color',
            [
                'label' => __('Colore Sfondo Form', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'scheme' => [
                    'type' => Scheme_Color::get_type(),
                    'value' => Scheme_Color::COLOR_4,
                ],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-booking-form' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'form_border',
                'label' => __('Bordo Form', 'colitalia-real-estate'),
                'selector' => '{{WRAPPER}} .colitalia-booking-form',
            ]
        );
        
        $this->add_control(
            'form_border_radius',
            [
                'label' => __('Border Radius Form', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-booking-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'form_padding',
            [
                'label' => __('Padding Form', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-booking-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Fields
        $this->start_controls_section(
            'style_fields',
            [
                'label' => __('Campi Input', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'input_typography',
                'label' => __('Tipografia Input', 'colitalia-real-estate'),
                'scheme' => Scheme_Typography::TYPOGRAPHY_3,
                'selector' => '{{WRAPPER}} .colitalia-booking-form input, {{WRAPPER}} .colitalia-booking-form select, {{WRAPPER}} .colitalia-booking-form textarea',
            ]
        );
        
        $this->add_control(
            'input_background_color',
            [
                'label' => __('Colore Sfondo Input', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .colitalia-booking-form input, {{WRAPPER}} .colitalia-booking-form select, {{WRAPPER}} .colitalia-booking-form textarea' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'input_text_color',
            [
                'label' => __('Colore Testo Input', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .colitalia-booking-form input, {{WRAPPER}} .colitalia-booking-form select, {{WRAPPER}} .colitalia-booking-form textarea' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'input_border',
                'label' => __('Bordo Input', 'colitalia-real-estate'),
                'selector' => '{{WRAPPER}} .colitalia-booking-form input, {{WRAPPER}} .colitalia-booking-form select, {{WRAPPER}} .colitalia-booking-form textarea',
            ]
        );
        
        $this->add_control(
            'input_border_radius',
            [
                'label' => __('Border Radius Input', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-booking-form input, {{WRAPPER}} .colitalia-booking-form select, {{WRAPPER}} .colitalia-booking-form textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Button
        $this->start_controls_section(
            'style_button',
            [
                'label' => __('Pulsante', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'label' => __('Tipografia Pulsante', 'colitalia-real-estate'),
                'scheme' => Scheme_Typography::TYPOGRAPHY_4,
                'selector' => '{{WRAPPER}} .colitalia-booking-submit',
            ]
        );
        
        $this->start_controls_tabs('button_style_tabs');
        
        $this->start_controls_tab(
            'button_normal_tab',
            [
                'label' => __('Normale', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'button_text_color',
            [
                'label' => __('Colore Testo', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .colitalia-booking-submit' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'button_background_color',
            [
                'label' => __('Colore Sfondo', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'scheme' => [
                    'type' => Scheme_Color::get_type(),
                    'value' => Scheme_Color::COLOR_1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-booking-submit' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->start_controls_tab(
            'button_hover_tab',
            [
                'label' => __('Hover', 'colitalia-real-estate'),
            ]
        );
        
        $this->add_control(
            'button_hover_text_color',
            [
                'label' => __('Colore Testo Hover', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .colitalia-booking-submit:hover' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'button_hover_background_color',
            [
                'label' => __('Colore Sfondo Hover', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .colitalia-booking-submit:hover' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->add_control(
            'button_padding',
            [
                'label' => __('Padding Pulsante', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-booking-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'button_border_radius',
            [
                'label' => __('Border Radius Pulsante', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-booking-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Determina property_id
        $property_id = $settings['property_id'];
        if (empty($property_id)) {
            global $post;
            if ($post && $post->post_type === 'proprieta') {
                $property_id = $post->ID;
            }
        }
        
        if (empty($property_id)) {
            if (is_user_logged_in() && current_user_can('edit_posts')) {
                echo '<div class="colitalia-elementor-notice">' . __('Seleziona una proprietà nelle impostazioni del widget o visualizza questo widget su una pagina di proprietà.', 'colitalia-real-estate') . '</div>';
            }
            return;
        }
        
        // Dati proprietà
        $property_data = $this->get_property_data($property_id);
        
        ?>
        <div class="colitalia-booking-form-widget" data-property-id="<?php echo esc_attr($property_id); ?>">
            <?php if ($settings['form_title']): ?>
            <h3 class="booking-form-title"><?php echo esc_html($settings['form_title']); ?></h3>
            <?php endif; ?>
            
            <form class="colitalia-booking-form" id="colitalia-booking-form-<?php echo esc_attr($property_id); ?>" method="post">
                <?php wp_nonce_field('colitalia_booking_nonce', 'booking_nonce'); ?>
                <input type="hidden" name="property_id" value="<?php echo esc_attr($property_id); ?>">
                <input type="hidden" name="action" value="colitalia_process_booking">
                
                <!-- Date Selection -->
                <div class="form-section booking-dates">
                    <h4><?php _e('Date Soggiorno', 'colitalia-real-estate'); ?></h4>
                    
                    <?php if ($settings['show_calendar'] === 'yes'): ?>
                    <div class="calendar-container">
                        <div id="booking-calendar-<?php echo esc_attr($property_id); ?>" class="booking-calendar"></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="date-inputs">
                        <div class="input-group">
                            <label for="check_in"><?php _e('Check-in', 'colitalia-real-estate'); ?> *</label>
                            <input type="date" id="check_in" name="check_in" required>
                        </div>
                        <div class="input-group">
                            <label for="check_out"><?php _e('Check-out', 'colitalia-real-estate'); ?> *</label>
                            <input type="date" id="check_out" name="check_out" required>
                        </div>
                    </div>
                </div>
                
                <?php if ($settings['show_guest_selector'] === 'yes'): ?>
                <!-- Guest Selection -->
                <div class="form-section guest-selection">
                    <label for="guests"><?php _e('Numero Ospiti', 'colitalia-real-estate'); ?> *</label>
                    <select id="guests" name="guests" required>
                        <?php for ($i = 1; $i <= ($property_data['max_guests'] ?: 8); $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                    <small class="guest-limit"><?php printf(__('Max %s ospiti', 'colitalia-real-estate'), $property_data['max_guests'] ?: 8); ?></small>
                </div>
                <?php endif; ?>
                
                <!-- Personal Information -->
                <div class="form-section personal-info">
                    <h4><?php _e('Dati Personali', 'colitalia-real-estate'); ?></h4>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="first_name"><?php _e('Nome', 'colitalia-real-estate'); ?><?php echo in_array('first_name', $settings['required_fields']) ? ' *' : ''; ?></label>
                            <input type="text" id="first_name" name="first_name" <?php echo in_array('first_name', $settings['required_fields']) ? 'required' : ''; ?>>
                        </div>
                        <div class="input-group">
                            <label for="last_name"><?php _e('Cognome', 'colitalia-real-estate'); ?><?php echo in_array('last_name', $settings['required_fields']) ? ' *' : ''; ?></label>
                            <input type="text" id="last_name" name="last_name" <?php echo in_array('last_name', $settings['required_fields']) ? 'required' : ''; ?>>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="input-group">
                            <label for="email"><?php _e('Email', 'colitalia-real-estate'); ?><?php echo in_array('email', $settings['required_fields']) ? ' *' : ''; ?></label>
                            <input type="email" id="email" name="email" <?php echo in_array('email', $settings['required_fields']) ? 'required' : ''; ?>>
                        </div>
                        <div class="input-group">
                            <label for="phone"><?php _e('Telefono', 'colitalia-real-estate'); ?><?php echo in_array('phone', $settings['required_fields']) ? ' *' : ''; ?></label>
                            <input type="tel" id="phone" name="phone" <?php echo in_array('phone', $settings['required_fields']) ? 'required' : ''; ?>>
                        </div>
                    </div>
                    
                    <?php if (in_array('address', $settings['required_fields']) || in_array('city', $settings['required_fields']) || in_array('country', $settings['required_fields'])): ?>
                    <div class="form-row">
                        <?php if (in_array('address', $settings['required_fields'])): ?>
                        <div class="input-group">
                            <label for="address"><?php _e('Indirizzo', 'colitalia-real-estate'); ?> *</label>
                            <input type="text" id="address" name="address" required>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (in_array('city', $settings['required_fields'])): ?>
                        <div class="input-group">
                            <label for="city"><?php _e('Città', 'colitalia-real-estate'); ?> *</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (in_array('country', $settings['required_fields'])): ?>
                        <div class="input-group">
                            <label for="country"><?php _e('Paese', 'colitalia-real-estate'); ?> *</label>
                            <select id="country" name="country" required>
                                <option value=""><?php _e('Seleziona...', 'colitalia-real-estate'); ?></option>
                                <option value="IT">Italia</option>
                                <option value="FR">Francia</option>
                                <option value="DE">Germania</option>
                                <option value="ES">Spagna</option>
                                <option value="UK">Regno Unito</option>
                                <option value="US">Stati Uniti</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (in_array('document_type', $settings['required_fields']) || in_array('document_number', $settings['required_fields'])): ?>
                    <div class="form-row">
                        <?php if (in_array('document_type', $settings['required_fields'])): ?>
                        <div class="input-group">
                            <label for="document_type"><?php _e('Tipo Documento', 'colitalia-real-estate'); ?> *</label>
                            <select id="document_type" name="document_type" required>
                                <option value=""><?php _e('Seleziona...', 'colitalia-real-estate'); ?></option>
                                <option value="passport"><?php _e('Passaporto', 'colitalia-real-estate'); ?></option>
                                <option value="id_card"><?php _e('Carta d\'Identità', 'colitalia-real-estate'); ?></option>
                                <option value="driving_license"><?php _e('Patente', 'colitalia-real-estate'); ?></option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (in_array('document_number', $settings['required_fields'])): ?>
                        <div class="input-group">
                            <label for="document_number"><?php _e('Numero Documento', 'colitalia-real-estate'); ?> *</label>
                            <input type="text" id="document_number" name="document_number" required>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($settings['show_services'] === 'yes'): ?>
                <!-- Extra Services -->
                <div class="form-section extra-services">
                    <h4><?php _e('Servizi Extra', 'colitalia-real-estate'); ?></h4>
                    <?php $this->render_extra_services($property_id); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($settings['show_special_requests'] === 'yes'): ?>
                <!-- Special Requests -->
                <div class="form-section special-requests">
                    <label for="special_requests"><?php _e('Richieste Speciali', 'colitalia-real-estate'); ?></label>
                    <textarea id="special_requests" name="special_requests" rows="3" placeholder="<?php _e('Inserisci eventuali richieste speciali...', 'colitalia-real-estate'); ?>"></textarea>
                </div>
                <?php endif; ?>
                
                <!-- Price Summary -->
                <div class="form-section price-summary">
                    <div class="price-breakdown" id="price-breakdown">
                        <div class="price-loading"><?php _e('Calcolo prezzo...', 'colitalia-real-estate'); ?></div>
                    </div>
                </div>
                
                <!-- GDPR Consent -->
                <div class="form-section gdpr-consent">
                    <label class="checkbox-label">
                        <input type="checkbox" name="gdpr_consent" required>
                        <span class="checkbox-text"><?php echo esc_html($settings['gdpr_text']); ?> *</span>
                    </label>
                </div>
                
                <!-- Submit Button -->
                <div class="form-section form-submit">
                    <button type="submit" class="colitalia-booking-submit">
                        <span class="button-text"><?php _e('Prenota Ora', 'colitalia-real-estate'); ?></span>
                        <span class="button-loading" style="display:none;"><?php _e('Elaborazione...', 'colitalia-real-estate'); ?></span>
                    </button>
                </div>
                
                <?php if (!empty($settings['redirect_after']['url'])): ?>
                <input type="hidden" name="redirect_url" value="<?php echo esc_url($settings['redirect_after']['url']); ?>">
                <?php endif; ?>
            </form>
            
            <!-- Messages -->
            <div id="booking-messages" class="booking-messages"></div>
        </div>
        <?php
        
        // Enqueue specific scripts for this form
        $this->enqueue_form_scripts();
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
            'max_guests' => get_post_meta($property_id, '_max_guests', true),
            'price' => get_post_meta($property_id, '_price', true),
            'weekly_price' => get_post_meta($property_id, '_weekly_price', true),
        ];
    }
    
    private function render_extra_services($property_id) {
        // Recupera servizi extra dalla proprietà o da impostazioni globali
        $services = get_post_meta($property_id, '_extra_services', true) ?: [];
        
        if (empty($services)) {
            // Servizi default
            $services = [
                'cleaning' => ['name' => 'Pulizia Extra', 'price' => 50],
                'linens' => ['name' => 'Biancheria', 'price' => 15],
                'parking' => ['name' => 'Parcheggio', 'price' => 10],
                'wifi' => ['name' => 'Wi-Fi', 'price' => 0],
            ];
        }
        
        foreach ($services as $key => $service) {
            ?>
            <label class="service-item">
                <input type="checkbox" name="services[]" value="<?php echo esc_attr($key); ?>" data-price="<?php echo esc_attr($service['price']); ?>">
                <span class="service-name"><?php echo esc_html($service['name']); ?></span>
                <?php if ($service['price'] > 0): ?>
                <span class="service-price">+€<?php echo number_format($service['price'], 0, ',', '.'); ?></span>
                <?php else: ?>
                <span class="service-price service-free"><?php _e('Gratis', 'colitalia-real-estate'); ?></span>
                <?php endif; ?>
            </label>
            <?php
        }
    }
    
    private function enqueue_form_scripts() {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-theme', 'https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css');
        
        // Script inline per inizializzazione
        wp_add_inline_script('colitalia-elementor-widgets', '
            jQuery(document).ready(function($) {
                if (typeof ColitaliaBookingForm !== "undefined") {
                    ColitaliaBookingForm.init();
                }
            });
        ');
    }
}
