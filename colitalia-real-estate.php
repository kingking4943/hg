<?php
/**
 * Plugin Name: Colitalia Real Estate Manager
 * Plugin URI: https://colitalia.com/plugin
 * Description: Plugin completo per la gestione immobiliare con multiproprietà, prenotazioni case vacanze, integrazione PayPal, email automation e sistema mappe Google Maps integrato. Include sistema prenotazioni avanzato, gestione clienti, analytics e integrazione Elementor.
 * Version: 1.5.1
 * Author: Colitalia Team
 * Author URI: https://colitalia.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: colitalia-real-estate
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Network: false
 * Update URI: https://colitalia.com/plugin-updates/
 *
 * @package ColitaliaRealEstate
 * @version 1.5.1
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('COLITALIA_PLUGIN_VERSION', '1.5.1');
define('COLITALIA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('COLITALIA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('COLITALIA_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('COLITALIA_PLUGIN_TEXTDOMAIN', 'colitalia-real-estate');
define('COLITALIA_REAL_ESTATE_PLUGIN_FILE', __FILE__);
define('COLITALIA_PLUGIN_LOGS_DIR', WP_CONTENT_DIR . '/colitalia-logs/');
define('COLITALIA_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

// PSR-4 Autoloader
spl_autoload_register(function ($class) {
    $namespace_map = [
        'ColitaliaRealEstate\\' => 'includes/',
        'Colitalia_Real_Estate\\' => 'includes/'
    ];

    foreach ($namespace_map as $namespace => $base_dir_name) {
        if (strncmp($namespace, $class, strlen($namespace)) === 0) {
            $relative_class = substr($class, strlen($namespace));
            $file = COLITALIA_PLUGIN_PATH . $base_dir_name . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});

/**
 * Centralized Logging System
 */
if (!class_exists('ColitaliaLogger')) {
    class ColitaliaLogger {
        public static function init() {
            if (!file_exists(COLITALIA_PLUGIN_LOGS_DIR)) { @wp_mkdir_p(COLITALIA_PLUGIN_LOGS_DIR); }
            $htaccess_file = COLITALIA_PLUGIN_LOGS_DIR . '.htaccess';
            if (!file_exists($htaccess_file)) { @file_put_contents($htaccess_file, "deny from all\n"); }
        }
        public static function log($level, $message, $context = []) {
            $log_file = COLITALIA_PLUGIN_LOGS_DIR . 'colitalia-' . date('Y-m-d') . '.log';
            $log_entry = sprintf("[%s] [%s] %s %s\n", date('Y-m-d H:i:s'), $level, $message, !empty($context) ? json_encode($context) : '');
            @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        }
        public static function info($message, $context = []) { self::log('INFO', $message, $context); }
        public static function error($message, $context = []) { self::log('ERROR', $message, $context); }
    }
}

/**
 * Main plugin class
 */
class ColitaliaRealEstatePlugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        ColitaliaLogger::init();
        add_action('plugins_loaded', array($this, 'init'), 10);
        register_activation_hook(COLITALIA_REAL_ESTATE_PLUGIN_FILE, array($this, 'activate'));
    }

    public function init() {
        try {
            // Lista di tutti i componenti da inizializzare
            $components = [
                \ColitaliaRealEstate\Cpt\PropertyCpt::class,
                \ColitaliaRealEstate\Cpt\PropertyTypeTaxonomy::class,
                \ColitaliaRealEstate\Core\AssetsManager::class,
                \ColitaliaRealEstate\Core\PropertyManager::class,
                \ColitaliaRealEstate\Core\BookingSystem::class,
                \ColitaliaRealEstate\Core\ClientManager::class,
                \ColitaliaRealEstate\Core\MultiPropertySystem::class,
                \ColitaliaRealEstate\Core\PaymentHandler::class,
                \ColitaliaRealEstate\Core\EmailAutomation::class,
                \ColitaliaRealEstate\Timeshare\TimeshareManager::class,
                \ColitaliaRealEstate\Admin\AdminPanel::class,
                \ColitaliaRealEstate\Admin\SettingsPage::class,
                \Colitalia_Real_Estate\Booking\BookingManager::class,
                \Colitalia_Real_Estate\Booking\CalendarManager::class,
                \Colitalia_Real_Estate\Booking\CustomerManager::class,
                \Colitalia_Real_Estate\Admin\PropertyMaps::class,
                \Colitalia_Real_Estate\Core\MapShortcodes::class,
            ];

            foreach ($components as $component) {
                 if (class_exists($component)) {
                    if (method_exists($component, 'instance')) {
                        $component::instance();
                    } else {
                        new $component();
                    }
                }
            }
            
            ColitaliaLogger::info('Plugin fully initialized', ['version' => COLITALIA_PLUGIN_VERSION]);

        } catch (\Throwable $e) {
            ColitaliaLogger::error('Plugin initialization failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>Colitalia Plugin Error: ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }
    
    public function activate() {
        \Colitalia_Real_Estate\Database\Migration::create_tables();
        flush_rewrite_rules();
        ColitaliaLogger::info('Plugin activated successfully');
    }
}

// Initialize the plugin
if (!defined('COLITALIA_PLUGIN_INITIALIZED')) {
    define('COLITALIA_PLUGIN_INITIALIZED', true);
    ColitaliaRealEstatePlugin::get_instance();
}

// Global helper functions
if (!function_exists('colitalia_get_property_data')) {
    function colitalia_get_property_data($property_id) {
        return \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_info($property_id);
    }
}
if (!function_exists('colitalia_format_currency')) {
    function colitalia_format_currency($amount, $currency = 'EUR') {
        return '€' . number_format_i18n($amount, 0);
    }
}

// =========================================================================
// FUNZIONALITÀ SHORTCODE [colitalia_grid] - CORRETTA E COMPLETA
// =========================================================================
function colitalia_register_property_grid_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => 'all',
        'limit'    => 6,
        'columns'  => 3,
    ), $atts, 'colitalia_grid');

    $args = array(
        'post_type'      => 'proprieta',
        'post_status'    => 'publish',
        'posts_per_page' => intval($atts['limit']),
        'no_found_rows'  => true,
    );

    if ($atts['category'] !== 'all' && !empty($atts['category'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'tipo_proprieta',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($atts['category']),
            ),
        );
    }

    $query = new WP_Query($args);
    ob_start();

    if ($query->have_posts()) {
        $grid_classes = 'colitalia-properties-grid';
        $column_class = 'columns-' . intval($atts['columns']);
        echo '<div class="' . esc_attr($grid_classes) . ' ' . esc_attr($column_class) . '">';

        while ($query->have_posts()) {
            $query->the_post();
            $property_id = get_the_ID();
            $property_info = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_info($property_id);
            $pricing_info = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_pricing($property_id);
            $is_timeshare = has_term('multiproprieta', 'tipo_proprieta', $property_id);
            $is_for_sale = has_term('vendita', 'tipo_proprieta', $property_id);
            ?>
            <div class="colitalia-property-card">
                <div class="colitalia-property-image">
                    <a href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium_large'); ?>
                        <?php else: ?>
                            <div style="height:200px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#999;">Nessuna immagine</div>
                        <?php endif; ?>
                    </a>
                    <?php
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
                    <h3 class="colitalia-property-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <?php if (!empty($property_info['location'])): ?>
                        <div class="colitalia-property-location"><?php echo esc_html($property_info['location']); ?></div>
                    <?php endif; ?>
                    <div class="colitalia-property-features">
                        <?php if (!empty($property_info['max_guests'])): ?><div class="property-feature guests"><?php echo esc_html($property_info['max_guests']); ?></div><?php endif; ?>
                        <?php if (!empty($property_info['bedrooms'])): ?><div class="property-feature bedrooms"><?php echo esc_html($property_info['bedrooms']); ?></div><?php endif; ?>
                        <?php if (!empty($property_info['bathrooms'])): ?><div class="property-feature bathrooms"><?php echo esc_html($property_info['bathrooms']); ?></div><?php endif; ?>
                    </div>
                    <div class="colitalia-property-excerpt"><?php the_excerpt(); ?></div>
                    <div class="colitalia-property-actions">
                        <a href="<?php the_permalink(); ?>" class="colitalia-btn colitalia-btn-primary"><?php _e('Vedi Dettagli', 'colitalia-real-estate'); ?></a>
                    </div>
                </div>
            </div>
            <?php
        }
        echo '</div>';
    } else {
        echo '<p>Nessuna proprietà trovata per questa selezione.</p>';
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('colitalia_grid', 'colitalia_register_property_grid_shortcode');

function colitalia_shortcode_grid_styles() {
    $custom_css = "
    .colitalia-properties-grid { display: grid; gap: 20px; }
    .colitalia-properties-grid.columns-1 { grid-template-columns: 1fr; }
    .colitalia-properties-grid.columns-2 { grid-template-columns: repeat(2, 1fr); }
    .colitalia-properties-grid.columns-3 { grid-template-columns: repeat(3, 1fr); }
    .colitalia-properties-grid.columns-4 { grid-template-columns: repeat(4, 1fr); }
    @media (max-width: 768px) { .colitalia-properties-grid.columns-3, .colitalia-properties-grid.columns-4 { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 480px) { .colitalia-properties-grid.columns-2, .colitalia-properties-grid.columns-3, .colitalia-properties-grid.columns-4 { grid-template-columns: 1fr; } }
    ";
    if (function_exists('wp_add_inline_style')) {
        wp_add_inline_style('colitalia-frontend-css', $custom_css);
    }
}
add_action('wp_enqueue_scripts', 'colitalia_shortcode_grid_styles');