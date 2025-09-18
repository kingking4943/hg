<?php
/**
 * Archive Properties Template
 * @package ColitaliaRealEstate
 * @subpackage Templates
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="colitalia-properties-archive">
    
    <div class="archive-header">
        <h1 class="archive-title"><?php _e('Tutte le Proprietà', 'colitalia-real-estate'); ?></h1>
        <div class="archive-description">
            <p><?php _e('Scopri la nostra selezione di proprietà per investimenti, vacanze e vendita.', 'colitalia-real-estate'); ?></p>
        </div>
    </div>
    
    <div class="colitalia-search-form">
        <h2 class="search-form-title"><?php _e('Trova la proprietà ideale', 'colitalia-real-estate'); ?></h2>
        
        <form method="get" class="properties-search-form">
            <div class="search-filters">
                
                <div class="filter-group">
                    <label for="property_type"><?php _e('Tipo Proprietà', 'colitalia-real-estate'); ?></label>
                    <select id="property_type" name="property_type">
                        <option value=""><?php _e('Tutti i tipi', 'colitalia-real-estate'); ?></option>
                        <?php
                        $property_types = get_terms(array(
                            'taxonomy' => 'tipo_proprieta',
                            'hide_empty' => false,
                        ));
                        
                        foreach ($property_types as $type):
                            $selected = isset($_GET['property_type']) && $_GET['property_type'] === $type->slug ? 'selected' : '';
                            ?>
                            <option value="<?php echo esc_attr($type->slug); ?>" <?php echo $selected; ?>>
                                <?php echo esc_html($type->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="location"><?php _e('Posizione', 'colitalia-real-estate'); ?></label>
                    <input type="text" id="location" name="location" 
                           value="<?php echo esc_attr(isset($_GET['location']) ? $_GET['location'] : ''); ?>"
                           placeholder="<?php _e('Inserisci città o zona', 'colitalia-real-estate'); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="price_min"><?php _e('Prezzo min (€)', 'colitalia-real-estate'); ?></label>
                    <input type="number" id="price_min" name="price_min" 
                           value="<?php echo esc_attr(isset($_GET['price_min']) ? $_GET['price_min'] : ''); ?>"
                           placeholder="0">
                </div>
                
                <div class="filter-group">
                    <label for="price_max"><?php _e('Prezzo max (€)', 'colitalia-real-estate'); ?></label>
                    <input type="number" id="price_max" name="price_max" 
                           value="<?php echo esc_attr(isset($_GET['price_max']) ? $_GET['price_max'] : ''); ?>"
                           placeholder="10000">
                </div>
                
                <div class="filter-group">
                    <label for="guests"><?php _e('Ospiti', 'colitalia-real-estate'); ?></label>
                    <select id="guests" name="guests">
                        <option value=""><?php _e('Qualsiasi', 'colitalia-real-estate'); ?></option>
                        <?php for ($i = 1; $i <= 12; $i++):
                            $selected = isset($_GET['guests']) && $_GET['guests'] == $i ? 'selected' : '';
                            ?>
                            <option value="<?php echo $i; ?>" <?php echo $selected; ?>>
                                <?php echo $i; ?> <?php echo ($i == 1) ? __('ospite', 'colitalia-real-estate') : __('ospiti', 'colitalia-real-estate'); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="bedrooms"><?php _e('Camere', 'colitalia-real-estate'); ?></label>
                    <select id="bedrooms" name="bedrooms">
                        <option value=""><?php _e('Qualsiasi', 'colitalia-real-estate'); ?></option>
                        <?php for ($i = 1; $i <= 6; $i++):
                            $selected = isset($_GET['bedrooms']) && $_GET['bedrooms'] == $i ? 'selected' : '';
                            ?>
                            <option value="<?php echo $i; ?>" <?php echo $selected; ?>>
                                <?php echo $i; ?> <?php echo ($i == 1) ? __('camera', 'colitalia-real-estate') : __('camere', 'colitalia-real-estate'); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
            </div>
            
            <div class="search-actions">
                <button type="submit" class="colitalia-btn colitalia-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Cerca Proprietà', 'colitalia-real-estate'); ?>
                </button>
                
                <a href="<?php echo get_post_type_archive_link('proprieta'); ?>" class="colitalia-btn colitalia-btn-secondary">
                    <?php _e('Pulisci Filtri', 'colitalia-real-estate'); ?>
                </a>
            </div>
        </form>
    </div>
    
    <div class="search-results-summary">
        <?php
        global $wp_query;
        $total_properties = $wp_query->found_posts;
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $posts_per_page = get_query_var('posts_per_page');
        
        $showing_from = (($paged - 1) * $posts_per_page) + 1;
        $showing_to = min($paged * $posts_per_page, $total_properties);
        
        if ($total_properties > 0):
            ?>
            <p class="search-results-count">
                <?php 
                printf(
                    __('Mostrando %d-%d di %d proprietà', 'colitalia-real-estate'),
                    $showing_from,
                    $showing_to,
                    $total_properties
                ); 
                ?>
            </p>
        <?php else: ?>
            <p class="search-results-count">
                <?php _e('Nessuna proprietà trovata con i criteri selezionati.', 'colitalia-real-estate'); ?>
            </p>
        <?php endif; ?>
    </div>
    
    <div class="colitalia-properties-grid">
        <?php if (have_posts()): ?>
            <?php while (have_posts()) : the_post(); ?>
                <div class="colitalia-property-card">
                    
                    <div class="colitalia-property-image">
                        <?php if (has_post_thumbnail()): ?>
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium_large'); ?>
                            </a>
                        <?php else:
                            $gallery_images = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_gallery(get_the_ID(), 'medium_large');
                            if (!empty($gallery_images)): ?>
                                <a href="<?php the_permalink(); ?>">
                                    <img src="<?php echo esc_url($gallery_images[0]['url']); ?>" 
                                         alt="<?php echo esc_attr($gallery_images[0]['alt']); ?>">
                                </a>
                            <?php else: ?>
                                <div class="placeholder-image">
                                    <span class="dashicons dashicons-building"></span>
                                </div>
                            <?php endif;
                        endif; ?>
                        
                        <?php
                        $property_types = wp_get_post_terms(get_the_ID(), 'tipo_proprieta');
                        if (!empty($property_types)):
                            $primary_type = $property_types[0];
                            $type_color = get_term_meta($primary_type->term_id, 'color', true) ?: '#0073aa';
                            ?>
                            <div class="property-type-badge" style="background-color: <?php echo esc_attr($type_color); ?>">
                                <?php echo esc_html($primary_type->name); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        $property_id = get_the_ID();
                        $pricing_info = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_pricing($property_id);
                        $is_timeshare = has_term('multiproprieta', 'tipo_proprieta', $property_id);
                        $is_for_sale = has_term('vendita', 'tipo_proprieta', $property_id);

                        $sale_price_valid = ($is_timeshare || $is_for_sale) && !empty($pricing_info['sale_price']) && is_numeric($pricing_info['sale_price']);
                        $weekly_price_valid = !empty($pricing_info['weekly_price']) && is_numeric($pricing_info['weekly_price']);

                        if ($sale_price_valid || $weekly_price_valid) :
                        ?>
                            <div class="property-price-badge">
                                <?php
                                if ($sale_price_valid) {
                                    $label = $is_timeshare ? 'Investimento da ' : '';
                                    // *** CODICE CORRETTO CON 2 DECIMALI ***
                                    echo esc_html($label) . '€' . number_format_i18n(floatval($pricing_info['sale_price']), 2);
                                } elseif ($weekly_price_valid) {
                                    // *** CODICE CORRETTO CON 2 DECIMALI ***
                                    echo '€' . number_format_i18n(floatval($pricing_info['weekly_price']), 2) . '/sett';
                                }
                                ?>
                            </div>
                        <?php
                        endif;
                        ?>
                    </div>
                    
                    <div class="colitalia-property-content">
                        <h3 class="colitalia-property-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        
                        <?php
                        $property_info = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_info(get_the_ID());
                        if ($property_info['location']):
                            ?>
                            <div class="colitalia-property-location">
                                <?php echo esc_html($property_info['location']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="colitalia-property-features">
                            <?php if ($property_info['max_guests']): ?>
                                <div class="property-feature guests">
                                    <?php echo esc_html($property_info['max_guests']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($property_info['bedrooms']): ?>
                                <div class="property-feature bedrooms">
                                    <?php echo esc_html($property_info['bedrooms']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($property_info['bathrooms']): ?>
                                <div class="property-feature bathrooms">
                                    <?php echo esc_html($property_info['bathrooms']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($property_info['size_sqm']): ?>
                                <div class="property-feature size">
                                    <?php echo esc_html($property_info['size_sqm']); ?>m²
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (has_excerpt()): ?>
                            <div class="colitalia-property-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                        <?php elseif ($property_info['description']): ?>
                            <div class="colitalia-property-excerpt">
                                <?php echo wp_trim_words($property_info['description'], 20); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="colitalia-property-actions">
                            <a href="<?php the_permalink(); ?>" class="colitalia-btn colitalia-btn-primary">
                                <?php _e('Vedi Dettagli', 'colitalia-real-estate'); ?>
                            </a>
                            
                            <?php if (\ColitaliaRealEstate\Cpt\PropertyCpt::is_property_bookable(get_the_ID())): ?>
                                <a href="<?php the_permalink(); ?>#booking" class="colitalia-btn colitalia-btn-secondary">
                                    <?php _e('Prenota', 'colitalia-real-estate'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            <?php endwhile; ?>
            
        <?php else: ?>
            <div class="no-properties-found">
                <h3><?php _e('Nessuna proprietà trovata', 'colitalia-real-estate'); ?></h3>
                <p><?php _e('Prova a modificare i filtri di ricerca per trovare la proprietà che fa per te.', 'colitalia-real-estate'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($total_properties > $posts_per_page): ?>
        <div class="colitalia-pagination">
            <?php
            echo paginate_links(array(
                'total' => $wp_query->max_num_pages,
                'current' => $paged,
                'format' => '?paged=%#%',
                'show_all' => false,
                'end_size' => 1,
                'mid_size' => 2,
                'prev_next' => true,
                'prev_text' => __('&laquo; Precedente', 'colitalia-real-estate'),
                'next_text' => __('Successivo &raquo;', 'colitalia-real-estate'),
                'add_args' => false,
                'add_fragment' => '',
            ));
            ?>
        </div>
    <?php endif; ?>
    
</div>

<style>
.archive-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
}

.archive-title {
    font-size: 32px;
    margin: 0 0 15px;
    font-weight: 600;
}

.archive-description p {
    font-size: 18px;
    margin: 0;
    opacity: 0.9;
}

.search-actions {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-top: 20px;
    justify-content: center;
}

.search-results-summary {
    margin: 30px 0;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
    text-align: center;
}

.search-results-count {
    margin: 0;
    font-weight: 500;
    color: #666;
}

.placeholder-image {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 200px;
    background: #f0f0f0;
    color: #999;
    font-size: 48px;
}

.no-properties-found {
    text-align: center;
    padding: 60px 20px;
    background: #f9f9f9;
    border-radius: 8px;
    grid-column: 1 / -1;
}

.no-properties-found h3 {
    font-size: 24px;
    margin-bottom: 15px;
    color: #666;
}

.no-properties-found p {
    color: #999;
    font-size: 16px;
    margin: 0;
}

.colitalia-pagination {
    margin-top: 40px;
    text-align: center;
}

.colitalia-pagination a,
.colitalia-pagination span {
    display: inline-block;
    padding: 8px 16px;
    margin: 0 4px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #666;
    transition: all 0.3s ease;
}

.colitalia-pagination a:hover {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.colitalia-pagination .current {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

@media (max-width: 768px) {
    .archive-title {
        font-size: 24px;
    }
    
    .archive-description p {
        font-size: 16px;
    }
    
    .search-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-actions .colitalia-btn {
        text-align: center;
    }
}
</style>

<?php get_footer(); ?>