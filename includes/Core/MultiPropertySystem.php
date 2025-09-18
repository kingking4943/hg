<?php
namespace ColitaliaRealEstate\Core;

/**
 * Multi-Property System Class
 * Handles investment calculations, profit margins, and multi-ownership management
 */
class MultiPropertySystem {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        // Hook AJAX rimossi per evitare conflitto con TimeshareManager
        // add_action('wp_ajax_calculate_investment_roi', array($this, 'ajax_calculate_roi'));
        // add_action('wp_ajax_nopriv_calculate_investment_roi', array($this, 'ajax_calculate_roi'));
        add_shortcode('colitalia_investment_calculator', array($this, 'render_investment_calculator'));
        add_shortcode('colitalia_multiproperty_dashboard', array($this, 'render_multiproperty_dashboard'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Initialize the multi-property system
     */
    public function init() {
        // Add custom user meta for investors
        add_action('show_user_profile', array($this, 'add_investor_fields'));
        add_action('edit_user_profile', array($this, 'add_investor_fields'));
        add_action('personal_options_update', array($this, 'save_investor_fields'));
        add_action('edit_user_profile_update', array($this, 'save_investor_fields'));
        
        // Add investor role
        $this->add_investor_role();
    }
    
    /**
     * Add investor role
     */
    private function add_investor_role() {
        $role = get_role('property_investor');
        if (!$role) {
            add_role('property_investor', __('Investitore Immobiliare', 'colitalia-real-estate'), array(
                'read' => true,
                'view_property_investments' => true,
                'manage_own_investments' => true
            ));
        }
    }
    
    /**
     * Add investor profile fields
     */
    public function add_investor_fields($user) {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }
        
        $is_investor = get_user_meta($user->ID, 'is_property_investor', true);
        $investment_budget = get_user_meta($user->ID, 'investment_budget', true);
        $preferred_locations = get_user_meta($user->ID, 'preferred_investment_locations', true);
        $risk_tolerance = get_user_meta($user->ID, 'risk_tolerance', true);
        
        ?>
        <h3><?php _e('Profilo Investitore', 'colitalia-real-estate'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Investitore Immobiliare', 'colitalia-real-estate'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="is_property_investor" value="1" <?php checked(1, $is_investor); ?> />
                        <?php _e('Questo utente è un investitore immobiliare', 'colitalia-real-estate'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="investment_budget"><?php _e('Budget Investimento (€)', 'colitalia-real-estate'); ?></label>
                </th>
                <td>
                    <input type="number" id="investment_budget" name="investment_budget" value="<?php echo esc_attr($investment_budget); ?>" min="0" step="1000" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="preferred_investment_locations"><?php _e('Località Preferite', 'colitalia-real-estate'); ?></label>
                </th>
                <td>
                    <textarea id="preferred_investment_locations" name="preferred_investment_locations" rows="3" cols="30"><?php echo esc_textarea($preferred_locations); ?></textarea>
                    <p class="description"><?php _e('Inserisci le località dove preferisci investire (una per riga)', 'colitalia-real-estate'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="risk_tolerance"><?php _e('Tolleranza al Rischio', 'colitalia-real-estate'); ?></label>
                </th>
                <td>
                    <select id="risk_tolerance" name="risk_tolerance">
                        <option value="low" <?php selected('low', $risk_tolerance); ?>><?php _e('Bassa', 'colitalia-real-estate'); ?></option>
                        <option value="medium" <?php selected('medium', $risk_tolerance); ?>><?php _e('Media', 'colitalia-real-estate'); ?></option>
                        <option value="high" <?php selected('high', $risk_tolerance); ?>><?php _e('Alta', 'colitalia-real-estate'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save investor profile fields
     */
    public function save_investor_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        $is_investor = isset($_POST['is_property_investor']) ? 1 : 0;
        update_user_meta($user_id, 'is_property_investor', $is_investor);
        
        if (isset($_POST['investment_budget'])) {
            update_user_meta($user_id, 'investment_budget', sanitize_text_field($_POST['investment_budget']));
        }
        
        if (isset($_POST['preferred_investment_locations'])) {
            update_user_meta($user_id, 'preferred_investment_locations', sanitize_textarea_field($_POST['preferred_investment_locations']));
        }
        
        if (isset($_POST['risk_tolerance'])) {
            update_user_meta($user_id, 'risk_tolerance', sanitize_text_field($_POST['risk_tolerance']));
        }
        
        $user = new \WP_User($user_id);
        if ($is_investor && !$user->has_cap('view_property_investments')) {
            $user->add_role('property_investor');
        } elseif (!$is_investor && $user->has_cap('view_property_investments')) {
            $user->remove_role('property_investor');
        }
    }
    
    /**
     * Calculate investment ROI for a property
     */
    public function calculate_investment_roi($property_id, $investment_amount, $ownership_percentage, $annual_rental_income = null) {
        // ===============================================================
        // BLOCCO DI CODICE CORRETTO
        // ===============================================================
        // Recupera sia i dati generali che i dati di prezzo
        $property_info = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_info($property_id);
        $pricing_info = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_pricing($property_id);

        if (!$property_info || !$pricing_info) {
            return false;
        }

        // Se il reddito da locazione non è fornito, lo stima dal prezzo settimanale
        if ($annual_rental_income === null && !empty($pricing_info['weekly_price'])) {
            $weekly_slots = get_post_meta($property_id, '_weekly_slots_total', true);
            $occupancy_rate = $this->get_estimated_occupancy_rate($property_id);
            // Usa il dato dall'array pricing_info
            $annual_rental_income = $pricing_info['weekly_price'] * $weekly_slots * ($occupancy_rate / 100);
        }
        // ===============================================================
        // FINE BLOCCO CORRETTO
        // ===============================================================

        // Get property expenses
        $management_fee_percentage = get_post_meta($property_id, '_management_fee', true) ?: 10;
        $annual_expenses = $this->calculate_annual_expenses($property_id, $annual_rental_income);
        
        // Calculate investor's share
        $investor_rental_income = $annual_rental_income * ($ownership_percentage / 100);
        $investor_expenses = $annual_expenses * ($ownership_percentage / 100);
        $net_annual_income = $investor_rental_income - $investor_expenses;
        
        // Calculate ROI metrics
        $roi_percentage = $investment_amount > 0 ? ($net_annual_income / $investment_amount) * 100 : 0;
        $payback_years = $net_annual_income > 0 ? $investment_amount / $net_annual_income : 0;
        
        // Calculate profit sharing with Colitalia
        $profit_sharing_percentage = get_post_meta($property_id, '_profit_sharing', true) ?: 70;
        $investor_profit = $net_annual_income * ($profit_sharing_percentage / 100);
        $colitalia_profit = $net_annual_income * ((100 - $profit_sharing_percentage) / 100);
        
        return array(
            'investment_amount' => $investment_amount,
            'ownership_percentage' => $ownership_percentage,
            'annual_rental_income' => $annual_rental_income,
            'investor_rental_income' => $investor_rental_income,
            'annual_expenses' => $annual_expenses,
            'investor_expenses' => $investor_expenses,
            'net_annual_income' => $net_annual_income,
            'roi_percentage' => $roi_percentage,
            'payback_years' => $payback_years,
            'investor_profit' => $investor_profit,
            'colitalia_profit' => $colitalia_profit,
            'management_fee_percentage' => $management_fee_percentage,
            'profit_sharing_percentage' => $profit_sharing_percentage
        );
    }
    
    /**
     * Calculate annual expenses for a property
     */
    private function calculate_annual_expenses($property_id, $annual_income) {
        $expenses = array();
        $management_fee_percentage = get_post_meta($property_id, '_management_fee', true) ?: 10;
        $expenses['management'] = $annual_income * ($management_fee_percentage / 100);
        $expenses['maintenance'] = $annual_income * 0.05;
        $expenses['insurance'] = $annual_income * 0.02;
        $expenses['taxes'] = $annual_income * 0.01;
        $expenses['cleaning'] = $annual_income * 0.03;
        $expenses['utilities'] = $annual_income * 0.04;
        $expenses['marketing'] = $annual_income * 0.02;
        return array_sum($expenses);
    }
    
    /**
     * Get estimated occupancy rate for a property
     */
    private function get_estimated_occupancy_rate($property_id) {
        $locations = wp_get_post_terms($property_id, 'property_location');
        $property_types = wp_get_post_terms($property_id, 'tipo_proprieta');
        $base_occupancy = 65;
        $high_demand_locations = array('roma', 'milano', 'venezia', 'firenze', 'napoli', 'costa-smeralda');
        if (!empty($locations)) {
            foreach ($locations as $location) {
                if (in_array($location->slug, $high_demand_locations)) { $base_occupancy += 15; break; }
            }
        }
        if (!empty($property_types)) {
            foreach ($property_types as $type) {
                if ($type->slug === 'casa-vacanze') { $base_occupancy += 10; } 
                elseif ($type->slug === 'multiproprietà') { $base_occupancy += 5; }
            }
        }
        global $wpdb;
        $historical_occupancy = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) * 7 / 365.0 * 100 FROM {$wpdb->prefix}colitalia_bookings WHERE property_id = %d AND status IN ('confirmed', 'paid', 'completed') AND created_at > DATE_SUB(NOW(), INTERVAL 1 YEAR)", $property_id));
        if ($historical_occupancy > 0) {
            $base_occupancy = ($historical_occupancy * 0.7) + ($base_occupancy * 0.3);
        }
        return min(95, max(30, $base_occupancy));
    }
    
    /**
     * Create or update investment record
     */
    public function create_investment($property_id, $owner_id, $investment_amount, $ownership_percentage) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'colitalia_multiproperty_investments';
        $total_weekly_slots = get_post_meta($property_id, '_weekly_slots_total', true) ?: 52;
        $weekly_slots_owned = floor($total_weekly_slots * ($ownership_percentage / 100));
        $roi_data = $this->calculate_investment_roi($property_id, $investment_amount, $ownership_percentage);
        $annual_profit_target = $roi_data ? $roi_data['investor_profit'] : 0;
        $result = $wpdb->insert($table_name, array('property_id' => $property_id, 'owner_id' => $owner_id, 'investment_amount' => $investment_amount, 'ownership_percentage' => $ownership_percentage, 'weekly_slots_owned' => $weekly_slots_owned, 'annual_profit_target' => $annual_profit_target, 'management_fee_percentage' => get_post_meta($property_id, '_management_fee', true) ?: 10), array('%d', '%d', '%f', '%f', '%d', '%f', '%f'));
        if ($result) {
            // Log the investment
            if (function_exists('colitalia_log')) { colitalia_log(sprintf('New investment created: Property %d, Owner %d, Amount: %s, Percentage: %s%%', $property_id, $owner_id, $investment_amount, $ownership_percentage)); }
            return $wpdb->insert_id;
        }
        return false;
    }
    
    /**
     * Get investments for a property
     */
    public function get_property_investments($property_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'colitalia_multiproperty_investments';
        return $wpdb->get_results($wpdb->prepare("SELECT i.*, u.display_name, u.user_email FROM $table_name i LEFT JOIN {$wpdb->users} u ON i.owner_id = u.ID WHERE i.property_id = %d ORDER BY i.ownership_percentage DESC", $property_id));
    }
    
    /**
     * Get investments for a user
     */
    public function get_user_investments($user_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT i.*, p.post_id, post.post_title, cp.location, cp.property_type FROM {$wpdb->prefix}colitalia_multiproperty_investments i LEFT JOIN {$wpdb->prefix}colitalia_properties cp ON i.property_id = cp.post_id LEFT JOIN {$wpdb->posts} post ON cp.post_id = post.ID WHERE i.owner_id = %d ORDER BY i.created_at DESC", $user_id));
    }

    /**
     * Calculate weekly cost matrix for multiproperty
     */
    public function calculate_weekly_costs($property_id, $investment_data) {
        $pricing_info = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_pricing($property_id);
        $seasonal_prices = get_post_meta($property_id, '_seasonal_prices', true);
        if (!$pricing_info || !$seasonal_prices || !is_array($seasonal_prices)) { return array(); }
        // ... (resto della logica)
    }

    /**
     * Determine which season a week belongs to
     */
    private function get_week_season($week_number, $season_calendar) {
        foreach ($season_calendar as $season => $periods) {
            foreach ($periods as $period) {
                if (is_array($period) && in_array($week_number, $period)) return $season;
            }
        }
        return 'bassa_stagione';
    }

    /**
     * Calculate weekly expenses
     */
    private function calculate_weekly_expenses($property_id, $weekly_price) {
        $cleaning_fee = get_post_meta($property_id, '_cleaning_fee', true) ?: 0;
        $estimated_expenses = $weekly_price * (0.05 + 0.08 + 0.02);
        return floatval($cleaning_fee) + $estimated_expenses;
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (is_page() && (has_shortcode(get_post()->post_content, 'colitalia_investment_calculator') || has_shortcode(get_post()->post_content, 'colitalia_multiproperty_dashboard'))) {
            wp_enqueue_style('colitalia-multiproperty', COLITALIA_PLUGIN_URL . 'assets/css/multiproperty.css', array(), COLITALIA_PLUGIN_VERSION);
            wp_enqueue_script('colitalia-multiproperty', COLITALIA_PLUGIN_URL . 'assets/js/multiproperty.js', array('jquery'), COLITALIA_PLUGIN_VERSION, true);
            wp_localize_script('colitalia-multiproperty', 'colitalia_multiproperty', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('colitalia_multiproperty_nonce')));
        }
    }
    
    /**
     * AJAX handler for ROI calculation
     */
    public function ajax_calculate_roi() {
        check_ajax_referer('colitalia_multiproperty_nonce', 'nonce');
        $property_id = intval($_POST['property_id']);
        $investment_amount = floatval($_POST['investment_amount']);
        $ownership_percentage = floatval($_POST['ownership_percentage']);
        if (!$property_id || !$investment_amount || !$ownership_percentage) { wp_send_json_error('Parametri non validi'); }
        $roi_data = $this->calculate_investment_roi($property_id, $investment_amount, $ownership_percentage);
        if ($roi_data) { wp_send_json_success($roi_data); } else { wp_send_json_error('Impossibile calcolare il ROI'); }
    }
    
    /**
     * Render investment calculator shortcode
     */
    public function render_investment_calculator($atts) {
        $atts = shortcode_atts(array('property_id' => 0), $atts, 'colitalia_investment_calculator');
        $property_id = intval($atts['property_id']);
        if (!$property_id) { return '<p>' . __('ID proprietà non specificato.', 'colitalia-real-estate') . '</p>'; }
        if (!has_term('multiproprieta', 'tipo_proprieta', $property_id)) { return '<p>Questa proprietà non è disponibile per investimenti multiproprietà.</p>'; }
        ob_start();
        $template_path = COLITALIA_PLUGIN_PATH . 'templates/timeshare/investment-calculator.php';
        if (file_exists($template_path)) {
            // Rendi $atts disponibile nel template
            include $template_path;
        } else {
            echo '<p>Template del calcolatore non trovato.</p>';
        }
        return ob_get_clean();
    }
    
    /**
     * Render multiproperty dashboard shortcode
     */
    public function render_multiproperty_dashboard($atts) {
        if (!is_user_logged_in()) { return '<p>' . __('Devi essere loggato per visualizzare il tuo portfolio.', 'colitalia-real-estate') . '</p>'; }
        // ... (resto della logica)
    }
    
    /**
     * Calculate average ROI for portfolio
     */
    private function calculate_portfolio_avg_roi($investments) {
        if (empty($investments)) { return 0; }
        $total_roi = 0; $total_weight = 0;
        foreach ($investments as $investment) {
            $roi_data = $this->calculate_investment_roi($investment->post_id, $investment->investment_amount, $investment->ownership_percentage);
            if ($roi_data) {
                $weight = $investment->investment_amount;
                $total_roi += $roi_data['roi_percentage'] * $weight;
                $total_weight += $weight;
            }
        }
        return $total_weight > 0 ? $total_roi / $total_weight : 0;
    }
}