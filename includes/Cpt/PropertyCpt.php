<?php
/**
 * Property Custom Post Type (Versione Finale Definitiva e Completa)
 * @package ColitaliaRealEstate
 * @subpackage Cpt
 */

namespace ColitaliaRealEstate\Cpt;

defined('ABSPATH') || exit;

class PropertyCpt {
    
    const POST_TYPE = 'proprieta';
    
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array($this, 'add_admin_columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array($this, 'fill_admin_columns'), 10, 2);
        add_filter('single_template', array($this, 'load_single_template'));
        add_filter('archive_template', array($this, 'load_archive_template'));
        
        // Hook per aggiungere l'azione "Duplica"
        add_filter('post_row_actions', array($this, 'add_duplicate_action'), 10, 2);
        // Hook per gestire la logica della duplicazione
        add_action('admin_post_colitalia_duplicate_property', array($this, 'handle_duplicate_property'));
    }
    
    public function register_post_type() {
        $labels = [
            'name'                  => __('Proprietà', 'colitalia-real-estate'),
            'singular_name'         => __('Proprietà', 'colitalia-real-estate'),
            'menu_name'             => __('Tutte le Proprietà', 'colitalia-real-estate'),
            'all_items'             => __('Tutte le Proprietà', 'colitalia-real-estate'),
            'add_new'               => __('Aggiungi Nuova', 'colitalia-real-estate'),
            'add_new_item'          => __('Aggiungi Nuova Proprietà', 'colitalia-real-estate'),
        ];
        $args = [
            'labels'                => $labels,
            'supports'              => ['title', 'editor', 'thumbnail', 'excerpt'],
            'taxonomies'            => ['tipo_proprieta'],
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'colitalia-dashboard',
            'menu_icon'             => 'dashicons-building',
            'has_archive'           => 'proprieta',
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => ['slug' => 'proprieta', 'with_front' => false],
        ];
        register_post_type(self::POST_TYPE, $args);
    }
    
    public function add_meta_boxes() {
        add_meta_box('property_details_mb', __('Dettagli Proprietà', 'colitalia-real-estate'), array($this, 'render_details_mb'), self::POST_TYPE, 'normal', 'high');
        add_meta_box('property_seasonal_pricing_mb', __('Prezzi Stagionali', 'colitalia-real-estate'), array($this, 'render_seasonal_pricing_mb'), self::POST_TYPE, 'normal', 'high');
        add_meta_box('property_gallery_mb', __('Galleria Immagini', 'colitalia-real-estate'), array($this, 'render_gallery_mb'), self::POST_TYPE, 'normal', 'default');
        add_meta_box('property_location_mb', __('Posizione', 'colitalia-real-estate'), array($this, 'render_location_mb'), self::POST_TYPE, 'side', 'default');
    }

    public function render_details_mb($post) {
        wp_nonce_field('colitalia_save_meta', 'colitalia_nonce');
        ?>
        <table class="form-table">
            <tr>
                <th><label for="_property_price"><?php _e('Prezzo Vendita (€)', 'colitalia-real-estate'); ?></label></th>
                <td><input type="number" step="0.01" id="_property_price" name="_property_price" value="<?php echo esc_attr(get_post_meta($post->ID, '_property_price', true)); ?>" class="regular-text" placeholder="Es: 250000.50" /></td>
            </tr>
            <tr>
                <th><label for="_property_daily_price"><?php _e('Prezzo Giornaliero (€)', 'colitalia-real-estate'); ?></label></th>
                <td><input type="number" step="0.01" id="_property_daily_price" name="_property_daily_price" value="<?php echo esc_attr(get_post_meta($post->ID, '_property_daily_price', true)); ?>" class="regular-text" placeholder="Prezzo base per notte" /></td>
            </tr>
            <tr>
                <th><label for="_property_weekly_price"><?php _e('Prezzo Affitto Settimanale (€)', 'colitalia-real-estate'); ?></label></th>
                <td><input type="number" step="0.01" id="_property_weekly_price" name="_property_weekly_price" value="<?php echo esc_attr(get_post_meta($post->ID, '_property_weekly_price', true)); ?>" class="regular-text" placeholder="Es: 950.00" /></td>
            </tr>
            <tr><th><label for="_property_max_guests"><?php _e('Max Ospiti', 'colitalia-real-estate'); ?></label></th><td><input type="number" id="_property_max_guests" name="_property_max_guests" value="<?php echo esc_attr(get_post_meta($post->ID, '_property_max_guests', true)); ?>" /></td></tr>
            <tr><th><label for="_property_bedrooms"><?php _e('Camere', 'colitalia-real-estate'); ?></label></th><td><input type="number" id="_property_bedrooms" name="_property_bedrooms" value="<?php echo esc_attr(get_post_meta($post->ID, '_property_bedrooms', true)); ?>" /></td></tr>
            <tr><th><label for="_property_bathrooms"><?php _e('Bagni', 'colitalia-real-estate'); ?></label></th><td><input type="number" id="_property_bathrooms" name="_property_bathrooms" value="<?php echo esc_attr(get_post_meta($post->ID, '_property_bathrooms', true)); ?>" step="0.5" /></td></tr>
            <tr><th><label for="_property_size_sqm"><?php _e('Superficie (mq)', 'colitalia-real-estate'); ?></label></th><td><input type="number" id="_property_size_sqm" name="_property_size_sqm" value="<?php echo esc_attr(get_post_meta($post->ID, '_property_size_sqm', true)); ?>" /></td></tr>
            <tr><th><label for="_property_features"><?php _e('Caratteristiche (una per riga)', 'colitalia-real-estate'); ?></label></th><td><textarea id="_property_features" name="_property_features" rows="5" style="width:100%;"><?php echo esc_textarea(get_post_meta($post->ID, '_property_features', true)); ?></textarea></td></tr>
        </table>
        <?php
    }

    public function render_seasonal_pricing_mb($post) {
        $rules = get_post_meta($post->ID, '_seasonal_pricing_rules', true);
        if (empty($rules)) {
            $rules = [];
        }
        ?>
        <div id="seasonal-pricing-container">
            <p class="description">Aggiungi regole per modificare il prezzo base in determinati periodi (es. alta stagione, festività). Le regole vengono applicate in ordine.</p>
            <div id="seasonal-rules-wrapper">
                <?php foreach ($rules as $i => $rule) : ?>
                    <div class="seasonal-rule">
                        <h4 class="rule-handle">Regola #<?php echo $i + 1; ?> <button type="button" class="remove-rule button button-link-delete">Rimuovi</button></h4>
                        <div class="rule-content">
                            <input type="text" name="seasonal_rules[<?php echo $i; ?>][name]" value="<?php echo esc_attr($rule['name']); ?>" placeholder="Nome regola (es. Alta Stagione Agosto)">
                            <input type="date" name="seasonal_rules[<?php echo $i; ?>][start]" value="<?php echo esc_attr($rule['start']); ?>" title="Data Inizio">
                            <input type="date" name="seasonal_rules[<?php echo $i; ?>][end]" value="<?php echo esc_attr($rule['end']); ?>" title="Data Fine">
                            <select name="seasonal_rules[<?php echo $i; ?>][type]">
                                <option value="percentage_increase" <?php selected($rule['type'], 'percentage_increase'); ?>>Aumento %</option>
                                <option value="percentage_decrease" <?php selected($rule['type'], 'percentage_decrease'); ?>>Sconto %</option>
                                <option value="fixed_daily" <?php selected($rule['type'], 'fixed_daily'); ?>>Nuovo Prezzo Giornaliero (€)</option>
                            </select>
                            <input type="number" step="0.01" name="seasonal_rules[<?php echo $i; ?>][value]" value="<?php echo esc_attr($rule['value']); ?>" placeholder="Valore">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-seasonal-rule" class="button">Aggiungi Regola di Prezzo</button>
        </div>

        <script>
            jQuery(document).ready(function($) {
                var wrapper = $('#seasonal-rules-wrapper');

                $('#add-seasonal-rule').on('click', function() {
                    var ruleCount = wrapper.find('.seasonal-rule').length;
                    var newRule = `
                        <div class="seasonal-rule">
                            <h4 class="rule-handle">Nuova Regola <button type="button" class="remove-rule button button-link-delete">Rimuovi</button></h4>
                            <div class="rule-content">
                                <input type="text" name="seasonal_rules[${ruleCount}][name]" placeholder="Nome regola (es. Alta Stagione Agosto)">
                                <input type="date" name="seasonal_rules[${ruleCount}][start]" title="Data Inizio">
                                <input type="date" name="seasonal_rules[${ruleCount}][end]" title="Data Fine">
                                <select name="seasonal_rules[${ruleCount}][type]">
                                    <option value="percentage_increase">Aumento %</option>
                                    <option value="percentage_decrease">Sconto %</option>
                                    <option value="fixed_daily">Nuovo Prezzo Giornaliero (€)</option>
                                </select>
                                <input type="number" step="0.01" name="seasonal_rules[${ruleCount}][value]" placeholder="Valore">
                            </div>
                        </div>`;
                    wrapper.append(newRule);
                });

                wrapper.on('click', '.remove-rule', function() {
                    $(this).closest('.seasonal-rule').remove();
                });
            });
        </script>
        <style>
            #seasonal-pricing-container .seasonal-rule { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; background: #f9f9f9; }
            #seasonal-pricing-container .rule-handle { margin: 0 0 10px; padding: 0; cursor: move; }
            #seasonal-pricing-container .remove-rule { float: right; }
            #seasonal-pricing-container .rule-content input, #seasonal-pricing-container .rule-content select { margin-right: 5px; margin-bottom: 5px; }
        </style>
        <?php
    }

    public function render_location_mb($post) {
        ?>
         <p>
            <label for="_property_location"><?php _e('Indirizzo Completo', 'colitalia-real-estate'); ?></label><br>
            <input type="text" id="_property_location" name="_property_location" value="<?php echo esc_attr(get_post_meta($post->ID, '_property_location', true)); ?>" style="width:100%;" />
        </p>
         <p>
            <label for="_property_latitude"><?php _e('Latitudine', 'colitalia-real-estate'); ?></label><br>
            <input type="text" id="_property_latitude" name="_property_latitude" value="<?php echo esc_attr(get_post_meta($post->ID, '_property_latitude', true)); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="_property_longitude"><?php _e('Longitudine', 'colitalia-real-estate'); ?></label><br>
            <input type="text" id="_property_longitude" name="_property_longitude" value="<?php echo esc_attr(get_post_meta($post->ID, '_property_longitude', true)); ?>" style="width:100%;" />
        </p>
        <?php
    }
    
    public function render_gallery_mb($post) {
        $gallery_ids_string = get_post_meta($post->ID, '_property_gallery_ids', true);
        $gallery_ids = !empty($gallery_ids_string) ? explode(',', $gallery_ids_string) : [];
        ?>
        <div id="property-gallery-container">
            <div id="property-gallery-preview">
                <?php
                if (!empty($gallery_ids)) {
                    foreach ($gallery_ids as $image_id) {
                        if (empty($image_id) || !is_numeric($image_id)) {
                            continue;
                        }
                        $thumbnail_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                        if ($thumbnail_url) {
                            echo '<div class="gallery-image" data-image-id="' . esc_attr($image_id) . '">';
                            echo '<img src="' . esc_url($thumbnail_url) . '" alt="" />';
                            echo '<button type="button" class="remove-image" title="Rimuovi immagine">×</button>';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>
            <p class="add-gallery-buttons">
                <button type="button" id="add-gallery-images" class="button">
                    <span class="dashicons dashicons-format-gallery"></span>
                    <?php _e('Aggiungi/Modifica Immagini', 'colitalia-real-estate'); ?>
                </button>
            </p>
            <input type="hidden" id="property_gallery_ids" name="_property_gallery_ids" value="<?php echo esc_attr($gallery_ids_string); ?>" />
        </div>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (!isset($_POST['colitalia_nonce']) || !wp_verify_nonce($_POST['colitalia_nonce'], 'colitalia_save_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $meta_keys = ['_property_price', '_property_daily_price', '_property_weekly_price', '_property_max_guests', '_property_bedrooms', '_property_bathrooms', '_property_size_sqm', '_property_features', '_property_location', '_property_latitude', '_property_longitude', '_property_gallery_ids'];
        foreach ($meta_keys as $key) {
             if (isset($_POST[$key])) {
                // Sostituisci la virgola con il punto per i campi numerici prima di salvare
                if (in_array($key, ['_property_price', '_property_daily_price', '_property_weekly_price'])) {
                    $value = str_replace(',', '.', $_POST[$key]);
                    update_post_meta($post_id, $key, sanitize_text_field($value));
                } elseif ($key === '_property_features') {
                    update_post_meta($post_id, $key, sanitize_textarea_field($_POST[$key]));
                } else {
                    update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
                }
            }
        }
        
        // SALVATAGGIO REGOLE PREZZI STAGIONALI
        if (isset($_POST['seasonal_rules'])) {
            $sanitized_rules = [];
            foreach ($_POST['seasonal_rules'] as $rule) {
                if (!empty($rule['start']) && !empty($rule['end']) && !empty($rule['value'])) {
                    $sanitized_rules[] = [
                        'name'  => sanitize_text_field($rule['name']),
                        'start' => sanitize_text_field($rule['start']),
                        'end'   => sanitize_text_field($rule['end']),
                        'type'  => sanitize_text_field($rule['type']),
                        'value' => sanitize_text_field(str_replace(',', '.', $rule['value'])),
                    ];
                }
            }
            update_post_meta($post_id, '_seasonal_pricing_rules', $sanitized_rules);
        } else {
            delete_post_meta($post_id, '_seasonal_pricing_rules');
        }
    }
    
    public function add_admin_columns($columns) {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['property_type'] = __('Tipo', 'colitalia-real-estate');
        $new_columns['property_price'] = __('Prezzo', 'colitalia-real-estate');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }
    
    public function fill_admin_columns($column, $post_id) {
        switch ($column) {
            case 'property_type':
                $terms = get_the_terms($post_id, 'tipo_proprieta');
                if (!empty($terms) && !is_wp_error($terms)) {
                    $term_names = wp_list_pluck($terms, 'name');
                    echo implode(', ', $term_names);
                } else {
                    echo '—';
                }
                break;

            case 'property_price':
                $sale_price = get_post_meta($post_id, '_property_price', true);
                $weekly_price = get_post_meta($post_id, '_property_weekly_price', true);
                $daily_price = get_post_meta($post_id, '_property_daily_price', true);
                
                $price_output = '';
                
                if (!empty($sale_price) && is_numeric($sale_price)) {
                    $price_output = '€' . number_format_i18n(floatval($sale_price), 2) . '<br><small>Vendita</small>';
                } elseif (!empty($weekly_price) && is_numeric($weekly_price)) {
                    $price_output = '€' . number_format_i18n(floatval($weekly_price), 2) . '<br><small>/ sett.</small>';
                } elseif (!empty($daily_price) && is_numeric($daily_price)) {
                    $price_output = '€' . number_format_i18n(floatval($daily_price), 2) . '<br><small>/ giorno</small>';
                }

                echo $price_output ?: '—';
                break;
        }
    }
    
    public function add_duplicate_action($actions, $post) {
        if ($post->post_type == self::POST_TYPE && current_user_can('edit_posts')) {
            $url = wp_nonce_url(
                admin_url('admin-post.php?action=colitalia_duplicate_property&post=' . $post->ID),
                'colitalia_duplicate_nonce',
                'nonce'
            );
            $actions['duplicate'] = '<a href="' . esc_url($url) . '" title="Duplica questa proprietà">Duplica</a>';
        }
        return $actions;
    }

    public function handle_duplicate_property() {
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'colitalia_duplicate_nonce')) {
            wp_die('Richiesta non valida.');
        }
        if (!current_user_can('edit_posts')) {
            wp_die('Non hai i permessi per eseguire questa azione.');
        }
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        if (!$post_id) {
            wp_die('ID proprietà non fornito.');
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_die('Proprietà originale non trovata.');
        }

        $new_post_args = array(
            'post_title'   => $post->post_title . ' (Copia)',
            'post_content' => $post->post_content,
            'post_status'  => 'draft',
            'post_type'    => $post->post_type,
            'post_author'  => get_current_user_id(),
            'post_excerpt' => $post->post_excerpt,
        );

        $new_post_id = wp_insert_post($new_post_args);

        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
        }

        $post_meta_infos = get_post_meta($post_id);
        if (!empty($post_meta_infos)) {
            foreach ($post_meta_infos as $meta_key => $meta_values) {
                if ('_edit_lock' == $meta_key || '_edit_last' == $meta_key) {
                    continue;
                }
                foreach ($meta_values as $meta_value) {
                    add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
                }
            }
        }

        wp_redirect(admin_url('edit.php?post_type=' . self::POST_TYPE));
        exit;
    }

    public function load_single_template($template) {
        if (is_singular(self::POST_TYPE)) {
            $plugin_template = COLITALIA_PLUGIN_PATH . 'templates/single-proprieta.php';
            if (file_exists($plugin_template)) return $plugin_template;
        }
        return $template;
    }

    public function load_archive_template($template) {
        if (is_post_type_archive(self::POST_TYPE)) {
            $plugin_template = COLITALIA_PLUGIN_PATH . 'templates/archive-proprieta.php';
            if (file_exists($plugin_template)) return $plugin_template;
        }
        return $template;
    }
    
    // --- FUNZIONI HELPER STATICHE ---
    public static function get_property_info($property_id) {
        return [
            'max_guests'    => get_post_meta($property_id, '_property_max_guests', true),
            'bedrooms'      => get_post_meta($property_id, '_property_bedrooms', true),
            'bathrooms'     => get_post_meta($property_id, '_property_bathrooms', true),
            'size_sqm'      => get_post_meta($property_id, '_property_size_sqm', true),
            'location'      => get_post_meta($property_id, '_property_location', true),
            'latitude'      => get_post_meta($property_id, '_property_latitude', true),
            'longitude'     => get_post_meta($property_id, '_property_longitude', true),
            'features'      => get_post_meta($property_id, '_property_features', true),
        ];
    }
    
    public static function get_property_pricing($property_id) {
        return [
            'sale_price'    => get_post_meta($property_id, '_property_price', true),
            'daily_price'   => get_post_meta($property_id, '_property_daily_price', true),
            'weekly_price'  => get_post_meta($property_id, '_property_weekly_price', true),
        ];
    }
    
    public static function get_property_gallery($property_id, $size = 'large') {
        $gallery_ids_str = get_post_meta($property_id, '_property_gallery_ids', true);
        $gallery_ids = !empty($gallery_ids_str) ? explode(',', $gallery_ids_str) : [];
        $gallery_images = [];
        foreach ($gallery_ids as $image_id) {
            $image_id = intval($image_id);
            if ($image_id > 0) {
                 $image_data = wp_get_attachment_image_src($image_id, $size);
                if ($image_data) {
                    $gallery_images[] = [
                        'id'    => $image_id, 'url'   => $image_data[0],
                        'alt'   => get_post_meta($image_id, '_wp_attachment_image_alt', true)
                    ];
                }
            }
        }
        return $gallery_images;
    }

    public static function is_property_bookable($property_id) {
        $terms = get_the_terms($property_id, 'tipo_proprieta');
        if (empty($terms) || is_wp_error($terms)) {
            return false;
        }

        foreach ($terms as $term) {
            $is_bookable = get_term_meta($term->term_id, 'is_bookable', true);
            if ($is_bookable === '1') {
                return true;
            }
        }

        return false;
    }
}