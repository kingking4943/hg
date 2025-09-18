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
 * Property List Widget
 */
class PropertyListWidget extends Widget_Base {
    
    public function get_name() {
        return 'colitalia-property-list';
    }
    
    public function get_title() {
        return __('Lista Proprietà', 'colitalia-real-estate');
    }
    
    public function get_icon() {
        return 'eicon-posts-grid';
    }
    
    public function get_categories() {
        return ['colitalia-real-estate'];
    }
    
    public function get_keywords() {
        return ['proprietà', 'real estate', 'immobili', 'colitalia'];
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
            'property_type',
            [
                'label' => __('Tipo Proprietà', 'colitalia-real-estate'),
                'type' => Controls_Manager::SELECT,
                'default' => 'all',
                'options' => $this->get_property_types(),
            ]
        );
        
        $this->add_control(
            'posts_per_page',
            [
                'label' => __('Numero Elementi', 'colitalia-real-estate'),
                'type' => Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'max' => 50,
            ]
        );
        
        $this->add_control(
            'layout',
            [
                'label' => __('Layout', 'colitalia-real-estate'),
                'type' => Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => __('Griglia', 'colitalia-real-estate'),
                    'list' => __('Lista', 'colitalia-real-estate'),
                    'carousel' => __('Carosello', 'colitalia-real-estate'),
                ],
            ]
        );
        
        $this->add_control(
            'columns',
            [
                'label' => __('Colonne', 'colitalia-real-estate'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'condition' => [
                    'layout' => 'grid',
                ],
            ]
        );
        
        $this->add_control(
            'show_filters',
            [
                'label' => __('Mostra Filtri', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_pagination',
            [
                'label' => __('Mostra Paginazione', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Filter Section
        $this->start_controls_section(
            'filter_section',
            [
                'label' => __('Filtri', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'show_filters' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'filter_price',
            [
                'label' => __('Filtro Prezzo', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'filter_guests',
            [
                'label' => __('Filtro Ospiti', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'filter_dates',
            [
                'label' => __('Filtro Date Disponibilità', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'filter_location',
            [
                'label' => __('Filtro Località', 'colitalia-real-estate'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Cards
        $this->start_controls_section(
            'style_cards',
            [
                'label' => __('Cards Proprietà', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'card_background_color',
            [
                'label' => __('Colore Sfondo', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'scheme' => [
                    'type' => Scheme_Color::get_type(),
                    'value' => Scheme_Color::COLOR_4,
                ],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-property-card' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'label' => __('Bordo', 'colitalia-real-estate'),
                'selector' => '{{WRAPPER}} .colitalia-property-card',
            ]
        );
        
        $this->add_control(
            'card_border_radius',
            [
                'label' => __('Border Radius', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-property-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_box_shadow',
                'label' => __('Ombra', 'colitalia-real-estate'),
                'selector' => '{{WRAPPER}} .colitalia-property-card',
            ]
        );
        
        $this->add_control(
            'card_padding',
            [
                'label' => __('Padding', 'colitalia-real-estate'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-property-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section - Typography
        $this->start_controls_section(
            'style_typography',
            [
                'label' => __('Tipografia', 'colitalia-real-estate'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'title_heading',
            [
                'label' => __('Titolo', 'colitalia-real-estate'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Tipografia Titolo', 'colitalia-real-estate'),
                'scheme' => Scheme_Typography::TYPOGRAPHY_1,
                'selector' => '{{WRAPPER}} .colitalia-property-title',
            ]
        );
        
        $this->add_control(
            'title_color',
            [
                'label' => __('Colore Titolo', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'scheme' => [
                    'type' => Scheme_Color::get_type(),
                    'value' => Scheme_Color::COLOR_1,
                ],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-property-title' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'price_heading',
            [
                'label' => __('Prezzo', 'colitalia-real-estate'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'label' => __('Tipografia Prezzo', 'colitalia-real-estate'),
                'scheme' => Scheme_Typography::TYPOGRAPHY_2,
                'selector' => '{{WRAPPER}} .colitalia-property-price',
            ]
        );
        
        $this->add_control(
            'price_color',
            [
                'label' => __('Colore Prezzo', 'colitalia-real-estate'),
                'type' => Controls_Manager::COLOR,
                'scheme' => [
                    'type' => Scheme_Color::get_type(),
                    'value' => Scheme_Color::COLOR_2,
                ],
                'selectors' => [
                    '{{WRAPPER}} .colitalia-property-price' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $args = [
            'post_type' => 'proprieta',
            'posts_per_page' => $settings['posts_per_page'],
            'post_status' => 'publish',
        ];
        
        if ($settings['property_type'] !== 'all') {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'tipo_proprieta',
                    'field'    => 'slug',
                    'terms'    => $settings['property_type'],
                ],
            ];
        }
        
        $query = new \WP_Query($args);
        
        if ($query->have_posts()) {
            echo '<div class="colitalia-property-list-widget" data-layout="' . esc_attr($settings['layout']) . '" data-columns="' . esc_attr($settings['columns']) . '">';
            
            // Filtri
            if ($settings['show_filters'] === 'yes') {
                $this->render_filters($settings);
            }
            
            // Container properties
            $container_class = 'colitalia-properties-container';
            if ($settings['layout'] === 'grid') {
                $container_class .= ' colitalia-grid-layout columns-' . $settings['columns'];
            } elseif ($settings['layout'] === 'list') {
                $container_class .= ' colitalia-list-layout';
            } else {
                $container_class .= ' colitalia-carousel-layout';
            }
            
            echo '<div class="' . esc_attr($container_class) . '">';
            
            while ($query->have_posts()) {
                $query->the_post();
                $this->render_property_card();
            }
            
            echo '</div>';
            
            // Paginazione
            if ($settings['show_pagination'] === 'yes' && $query->max_num_pages > 1) {
                $this->render_pagination($query);
            }
            
            echo '</div>';
        } else {
            echo '<div class="colitalia-no-properties">' . __('Nessuna proprietà trovata.', 'colitalia-real-estate') . '</div>';
        }
        
        wp_reset_postdata();
    }
    
    private function render_filters($settings) {
        ?>
        <div class="colitalia-property-filters">
            <form class="colitalia-filters-form" data-target=".colitalia-properties-container">
                
                <?php if ($settings['filter_price'] === 'yes'): ?>
                <div class="filter-group">
                    <label><?php _e('Prezzo', 'colitalia-real-estate'); ?></label>
                    <div class="price-range-slider">
                        <input type="range" id="price_min" name="price_min" min="0" max="5000" value="0">
                        <input type="range" id="price_max" name="price_max" min="0" max="5000" value="5000">
                        <div class="price-display">
                            <span id="price_min_display">€0</span> - <span id="price_max_display">€5000+</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($settings['filter_guests'] === 'yes'): ?>
                <div class="filter-group">
                    <label for="guests"><?php _e('Ospiti', 'colitalia-real-estate'); ?></label>
                    <select name="guests" id="guests">
                        <option value=""><?php _e('Qualsiasi', 'colitalia-real-estate'); ?></option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5+</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <?php if ($settings['filter_dates'] === 'yes'): ?>
                <div class="filter-group">
                    <label><?php _e('Disponibilità', 'colitalia-real-estate'); ?></label>
                    <div class="date-inputs">
                        <input type="date" name="check_in" placeholder="<?php _e('Check-in', 'colitalia-real-estate'); ?>">
                        <input type="date" name="check_out" placeholder="<?php _e('Check-out', 'colitalia-real-estate'); ?>">
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($settings['filter_location'] === 'yes'): ?>
                <div class="filter-group">
                    <label for="location"><?php _e('Località', 'colitalia-real-estate'); ?></label>
                    <input type="text" name="location" id="location" placeholder="<?php _e('Inserisci località...', 'colitalia-real-estate'); ?>">
                </div>
                <?php endif; ?>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-filter"><?php _e('Filtra', 'colitalia-real-estate'); ?></button>
                    <button type="reset" class="btn-reset"><?php _e('Reset', 'colitalia-real-estate'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }
    
    private function render_property_card() {
        $property_id = get_the_ID();
        $price = get_post_meta($property_id, '_price', true);
        $max_guests = get_post_meta($property_id, '_max_guests', true);
        $bedrooms = get_post_meta($property_id, '_bedrooms', true);
        $bathrooms = get_post_meta($property_id, '_bathrooms', true);
        $location = get_post_meta($property_id, '_location', true);
        
        ?>
        <div class="colitalia-property-card" data-property-id="<?php echo esc_attr($property_id); ?>">
            <div class="property-image">
                <?php if (has_post_thumbnail()): ?>
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium_large', ['class' => 'property-thumbnail']); ?>
                    </a>
                <?php endif; ?>
                <div class="property-overlay">
                    <a href="<?php the_permalink(); ?>" class="btn-view-details"><?php _e('Vedi Dettagli', 'colitalia-real-estate'); ?></a>
                </div>
            </div>
            
            <div class="property-content">
                <h3 class="colitalia-property-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                
                <?php if ($location): ?>
                <div class="property-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo esc_html($location); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="property-features">
                    <?php if ($max_guests): ?>
                    <span class="feature">
                        <i class="fas fa-users"></i>
                        <?php printf(__('%s ospiti', 'colitalia-real-estate'), $max_guests); ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($bedrooms): ?>
                    <span class="feature">
                        <i class="fas fa-bed"></i>
                        <?php printf(__('%s camere', 'colitalia-real-estate'), $bedrooms); ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($bathrooms): ?>
                    <span class="feature">
                        <i class="fas fa-bath"></i>
                        <?php printf(__('%s bagni', 'colitalia-real-estate'), $bathrooms); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($price): ?>
                <div class="property-price">
                    <span class="colitalia-property-price">€<?php echo number_format($price, 0, ',', '.'); ?></span>
                    <span class="price-period">/<?php _e('settimana', 'colitalia-real-estate'); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="property-excerpt">
                    <?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_pagination($query) {
        $big = 999999999;
        $pagination = paginate_links([
            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format' => '?paged=%#%',
            'current' => max(1, get_query_var('paged')),
            'total' => $query->max_num_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'type' => 'list',
        ]);
        
        if ($pagination) {
            echo '<div class="colitalia-pagination">' . $pagination . '</div>';
        }
    }
    
    private function get_property_types() {
        $types = ['all' => __('Tutti i Tipi', 'colitalia-real-estate')];
        
        $terms = get_terms([
            'taxonomy' => 'tipo_proprieta',
            'hide_empty' => false,
        ]);
        
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $types[$term->slug] = $term->name;
            }
        }
        
        return $types;
    }
}
