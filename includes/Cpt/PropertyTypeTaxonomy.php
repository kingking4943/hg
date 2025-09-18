<?php
/**
 * Property Type Taxonomy
 * 
 * @package ColitaliaRealEstate
 * @subpackage Cpt
 */

namespace ColitaliaRealEstate\Cpt;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PropertyTypeTaxonomy
 * 
 * Gestisce la registrazione della tassonomia 'tipo_proprieta'
 */
class PropertyTypeTaxonomy {
    
    /**
     * Taxonomy slug
     */
    const TAXONOMY = 'tipo_proprieta';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_taxonomy'));
        add_action('created_' . self::TAXONOMY, array($this, 'save_taxonomy_custom_fields'));
        add_action('edited_' . self::TAXONOMY, array($this, 'save_taxonomy_custom_fields'));
        add_action(self::TAXONOMY . '_add_form_fields', array($this, 'add_taxonomy_custom_fields'));
        add_action(self::TAXONOMY . '_edit_form_fields', array($this, 'edit_taxonomy_custom_fields'));
        add_filter('manage_edit-' . self::TAXONOMY . '_columns', array($this, 'add_taxonomy_columns'));
        add_filter('manage_' . self::TAXONOMY . '_custom_column', array($this, 'fill_taxonomy_columns'), 10, 3);
    }
    
    /**
     * Register the custom taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'                       => __('Tipi Proprietà', 'colitalia-real-estate'),
            'singular_name'              => __('Tipo Proprietà', 'colitalia-real-estate'),
            'menu_name'                  => __('Tipi Proprietà', 'colitalia-real-estate'),
            'all_items'                  => __('Tutti i Tipi', 'colitalia-real-estate'),
            'parent_item'                => __('Tipo Padre', 'colitalia-real-estate'),
            'parent_item_colon'          => __('Tipo Padre:', 'colitalia-real-estate'),
            'new_item_name'              => __('Nuovo Tipo Proprietà', 'colitalia-real-estate'),
            'add_new_item'               => __('Aggiungi Nuovo Tipo', 'colitalia-real-estate'),
            'edit_item'                  => __('Modifica Tipo', 'colitalia-real-estate'),
            'update_item'                => __('Aggiorna Tipo', 'colitalia-real-estate'),
            'view_item'                  => __('Visualizza Tipo', 'colitalia-real-estate'),
            'separate_items_with_commas' => __('Separa i tipi con virgole', 'colitalia-real-estate'),
            'add_or_remove_items'        => __('Aggiungi o rimuovi tipi', 'colitalia-real-estate'),
            'choose_from_most_used'      => __('Scegli tra i più usati', 'colitalia-real-estate'),
            'popular_items'              => __('Tipi Popolari', 'colitalia-real-estate'),
            'search_items'               => __('Cerca Tipi', 'colitalia-real-estate'),
            'not_found'                  => __('Nessun tipo trovato', 'colitalia-real-estate'),
            'no_terms'                   => __('Nessun tipo', 'colitalia-real-estate'),
            'items_list'                 => __('Lista tipi', 'colitalia-real-estate'),
            'items_list_navigation'      => __('Navigazione lista tipi', 'colitalia-real-estate'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'show_in_rest'               => true,
            'rewrite'                    => array(
                'slug'                   => 'tipo-proprieta',
                'with_front'             => false,
                'hierarchical'           => true,
            ),
            'query_var'                  => true,
            'capabilities'               => array(
                'manage_terms'           => 'manage_categories',
                'edit_terms'             => 'manage_categories',
                'delete_terms'           => 'manage_categories',
                'assign_terms'           => 'edit_posts',
            ),
        );
        
        register_taxonomy(self::TAXONOMY, array(PropertyCpt::POST_TYPE), $args);
        
        // Create default terms
        $this->create_default_terms();
    }
    
    /**
     * Create default taxonomy terms
     */
    private function create_default_terms() {
        $default_terms = array(
            array(
                'name' => __('Multiproprietà', 'colitalia-real-estate'),
                'slug' => 'multiproprieta',
                'description' => __('Proprietà in regime di multiproprietà', 'colitalia-real-estate'),
                'meta' => array(
                    'is_bookable' => 1,
                    'is_for_sale' => 0,
                    'requires_investment_calculation' => 1,
                    'color' => '#3498db'
                )
            ),
            array(
                'name' => __('Casa Vacanze', 'colitalia-real-estate'),
                'slug' => 'casa-vacanze',
                'description' => __('Proprietà per affitti brevi e vacanze', 'colitalia-real-estate'),
                'meta' => array(
                    'is_bookable' => 1,
                    'is_for_sale' => 0,
                    'requires_investment_calculation' => 0,
                    'color' => '#e74c3c'
                )
            ),
            array(
                'name' => __('Vendita', 'colitalia-real-estate'),
                'slug' => 'vendita',
                'description' => __('Proprietà in vendita', 'colitalia-real-estate'),
                'meta' => array(
                    'is_bookable' => 0,
                    'is_for_sale' => 1,
                    'requires_investment_calculation' => 0,
                    'color' => '#27ae60'
                )
            ),
            array(
                'name' => __('Affitto Lungo Termine', 'colitalia-real-estate'),
                'slug' => 'affitto-lungo-termine',
                'description' => __('Proprietà per affitti residenziali a lungo termine', 'colitalia-real-estate'),
                'meta' => array(
                    'is_bookable' => 0,
                    'is_for_sale' => 0,
                    'requires_investment_calculation' => 0,
                    'color' => '#f39c12'
                )
            )
        );
        
        foreach ($default_terms as $term_data) {
            // Check if term already exists
            $existing_term = get_term_by('slug', $term_data['slug'], self::TAXONOMY);
            
            if (!$existing_term) {
                $term = wp_insert_term(
                    $term_data['name'],
                    self::TAXONOMY,
                    array(
                        'slug' => $term_data['slug'],
                        'description' => $term_data['description']
                    )
                );
                
                if (!is_wp_error($term) && isset($term_data['meta'])) {
                    foreach ($term_data['meta'] as $meta_key => $meta_value) {
                        add_term_meta($term['term_id'], $meta_key, $meta_value, true);
                    }
                }
            }
        }
    }
    
    /**
     * Add custom fields to taxonomy add form
     */
    public function add_taxonomy_custom_fields() {
        ?>
        <div class="form-field">
            <label for="property-type-color"><?php _e('Colore', 'colitalia-real-estate'); ?></label>
            <input type="color" id="property-type-color" name="property_type_color" value="#3498db" />
            <p class="description"><?php _e('Colore per identificare questo tipo di proprietà.', 'colitalia-real-estate'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="property-type-is-bookable">
                <input type="checkbox" id="property-type-is-bookable" name="property_type_is_bookable" value="1" />
                <?php _e('Prenotabile', 'colitalia-real-estate'); ?>
            </label>
            <p class="description"><?php _e('Le proprietà di questo tipo possono essere prenotate.', 'colitalia-real-estate'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="property-type-is-for-sale">
                <input type="checkbox" id="property-type-is-for-sale" name="property_type_is_for_sale" value="1" />
                <?php _e('In vendita', 'colitalia-real-estate'); ?>
            </label>
            <p class="description"><?php _e('Le proprietà di questo tipo sono in vendita.', 'colitalia-real-estate'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="property-type-requires-investment">
                <input type="checkbox" id="property-type-requires-investment" name="property_type_requires_investment_calculation" value="1" />
                <?php _e('Richiede calcolo investimenti', 'colitalia-real-estate'); ?>
            </label>
            <p class="description"><?php _e('Le proprietà di questo tipo richiedono calcoli di investimento.', 'colitalia-real-estate'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="property-type-commission"><?php _e('Commissione (%)', 'colitalia-real-estate'); ?></label>
            <input type="number" id="property-type-commission" name="property_type_commission_percentage" 
                   value="10" min="0" max="100" step="0.1" />
            <p class="description"><?php _e('Percentuale di commissione per questo tipo di proprietà.', 'colitalia-real-estate'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="property-type-icon"><?php _e('Icona (Dashicon)', 'colitalia-real-estate'); ?></label>
            <input type="text" id="property-type-icon" name="property_type_icon" value="dashicons-building" />
            <p class="description"><?php _e('Nome della dashicon da utilizzare (es: dashicons-building).', 'colitalia-real-estate'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Add custom fields to taxonomy edit form
     */
    public function edit_taxonomy_custom_fields($term) {
        $color = get_term_meta($term->term_id, 'color', true) ?: '#3498db';
        $is_bookable = get_term_meta($term->term_id, 'is_bookable', true);
        $is_for_sale = get_term_meta($term->term_id, 'is_for_sale', true);
        $requires_investment = get_term_meta($term->term_id, 'requires_investment_calculation', true);
        $commission = get_term_meta($term->term_id, 'commission_percentage', true) ?: '10';
        $icon = get_term_meta($term->term_id, 'icon', true) ?: 'dashicons-building';
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="property-type-color"><?php _e('Colore', 'colitalia-real-estate'); ?></label>
            </th>
            <td>
                <input type="color" id="property-type-color" name="property_type_color" 
                       value="<?php echo esc_attr($color); ?>" />
                <p class="description"><?php _e('Colore per identificare questo tipo di proprietà.', 'colitalia-real-estate'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row"><?php _e('Opzioni', 'colitalia-real-estate'); ?></th>
            <td>
                <label for="property-type-is-bookable">
                    <input type="checkbox" id="property-type-is-bookable" name="property_type_is_bookable" 
                           value="1" <?php checked($is_bookable, '1'); ?> />
                    <?php _e('Prenotabile', 'colitalia-real-estate'); ?>
                </label><br>
                
                <label for="property-type-is-for-sale">
                    <input type="checkbox" id="property-type-is-for-sale" name="property_type_is_for_sale" 
                           value="1" <?php checked($is_for_sale, '1'); ?> />
                    <?php _e('In vendita', 'colitalia-real-estate'); ?>
                </label><br>
                
                <label for="property-type-requires-investment">
                    <input type="checkbox" id="property-type-requires-investment" name="property_type_requires_investment_calculation" 
                           value="1" <?php checked($requires_investment, '1'); ?> />
                    <?php _e('Richiede calcolo investimenti', 'colitalia-real-estate'); ?>
                </label>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="property-type-commission"><?php _e('Commissione (%)', 'colitalia-real-estate'); ?></label>
            </th>
            <td>
                <input type="number" id="property-type-commission" name="property_type_commission_percentage" 
                       value="<?php echo esc_attr($commission); ?>" min="0" max="100" step="0.1" />
                <p class="description"><?php _e('Percentuale di commissione per questo tipo di proprietà.', 'colitalia-real-estate'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="property-type-icon"><?php _e('Icona (Dashicon)', 'colitalia-real-estate'); ?></label>
            </th>
            <td>
                <input type="text" id="property-type-icon" name="property_type_icon" 
                       value="<?php echo esc_attr($icon); ?>" />
                <p class="description"><?php _e('Nome della dashicon da utilizzare (es: dashicons-building).', 'colitalia-real-estate'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save taxonomy custom fields
     */
    public function save_taxonomy_custom_fields($term_id) {
        // Verify nonce if available
        if (isset($_POST['_wpnonce']) && !wp_verify_nonce($_POST['_wpnonce'], 'update-tag_' . $term_id)) {
            return;
        }
        
        $meta_fields = array(
            'property_type_color' => 'color',
            'property_type_is_bookable' => 'is_bookable',
            'property_type_is_for_sale' => 'is_for_sale',
            'property_type_requires_investment_calculation' => 'requires_investment_calculation',
            'property_type_commission_percentage' => 'commission_percentage',
            'property_type_icon' => 'icon'
        );
        
        foreach ($meta_fields as $form_field => $meta_key) {
            if (isset($_POST[$form_field])) {
                $value = sanitize_text_field($_POST[$form_field]);
                
                // Handle checkboxes
                if (in_array($meta_key, array('is_bookable', 'is_for_sale', 'requires_investment_calculation'))) {
                    $value = $value === '1' ? '1' : '0';
                }
                
                update_term_meta($term_id, $meta_key, $value);
            } else {
                // Handle unchecked checkboxes
                if (in_array($meta_key, array('is_bookable', 'is_for_sale', 'requires_investment_calculation'))) {
                    update_term_meta($term_id, $meta_key, '0');
                }
            }
        }
    }
    
    /**
     * Add custom columns to taxonomy admin
     */
    public function add_taxonomy_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'name') {
                $new_columns['color'] = __('Colore', 'colitalia-real-estate');
                $new_columns['properties'] = __('Proprietà', 'colitalia-real-estate');
                $new_columns['options'] = __('Opzioni', 'colitalia-real-estate');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Fill custom taxonomy columns
     */
    public function fill_taxonomy_columns($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'color':
                $color = get_term_meta($term_id, 'color', true) ?: '#3498db';
                $content = '<span style="display: inline-block; width: 20px; height: 20px; background-color: ' . 
                          esc_attr($color) . '; border-radius: 50%; border: 2px solid #ccc;"></span>';
                break;
                
            case 'properties':
                $term = get_term($term_id);
                $count = $term->count;
                $content = $count . ' ' . _n('proprietà', 'proprietà', $count, 'colitalia-real-estate');
                break;
                
            case 'options':
                $options = array();
                
                if (get_term_meta($term_id, 'is_bookable', true) === '1') {
                    $options[] = '<span class="dashicons dashicons-calendar-alt" title="' . 
                               esc_attr__('Prenotabile', 'colitalia-real-estate') . '"></span>';
                }
                
                if (get_term_meta($term_id, 'is_for_sale', true) === '1') {
                    $options[] = '<span class="dashicons dashicons-money-alt" title="' . 
                               esc_attr__('In vendita', 'colitalia-real-estate') . '"></span>';
                }
                
                if (get_term_meta($term_id, 'requires_investment_calculation', true) === '1') {
                    $options[] = '<span class="dashicons dashicons-chart-line" title="' . 
                               esc_attr__('Calcolo investimenti', 'colitalia-real-estate') . '"></span>';
                }
                
                $content = !empty($options) ? implode(' ', $options) : '—';
                break;
        }
        
        return $content;
    }
    
    /**
     * Get taxonomy terms with enhanced data
     */
    public static function get_enhanced_terms($args = array()) {
        $default_args = array(
            'taxonomy' => self::TAXONOMY,
            'hide_empty' => false,
        );
        
        $args = wp_parse_args($args, $default_args);
        $terms = get_terms($args);
        
        if (is_wp_error($terms)) {
            return array();
        }
        
        foreach ($terms as &$term) {
            $term->color = get_term_meta($term->term_id, 'color', true) ?: '#3498db';
            $term->is_bookable = get_term_meta($term->term_id, 'is_bookable', true) === '1';
            $term->is_for_sale = get_term_meta($term->term_id, 'is_for_sale', true) === '1';
            $term->requires_investment_calculation = get_term_meta($term->term_id, 'requires_investment_calculation', true) === '1';
            $term->commission_percentage = get_term_meta($term->term_id, 'commission_percentage', true) ?: '10';
            $term->icon = get_term_meta($term->term_id, 'icon', true) ?: 'dashicons-building';
        }
        
        return $terms;
    }
    
    /**
     * Get term by specific criteria
     */
    public static function get_terms_by_criteria($criteria = array()) {
        $all_terms = self::get_enhanced_terms();
        $filtered_terms = array();
        
        foreach ($all_terms as $term) {
            $include = true;
            
            foreach ($criteria as $key => $value) {
                if (property_exists($term, $key) && $term->$key !== $value) {
                    $include = false;
                    break;
                }
            }
            
            if ($include) {
                $filtered_terms[] = $term;
            }
        }
        
        return $filtered_terms;
    }
}
