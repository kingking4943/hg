<?php
namespace ColitaliaRealEstate\Timeshare;

/**
 * Timeshare Manager Class
 * Gestisce sistema multiproprietà con calcolo investimenti e ROI
 */
class TimeshareManager {
    
    public function __construct() {
        $this->init();
    }
    
    /**
     * Inizializza Timeshare Manager
     */
    public function init() {
        add_action('init', array($this, 'register_hooks'));
        add_shortcode('colitalia_investment_calculator', array($this, 'render_investment_calculator'));
        add_shortcode('colitalia_owner_dashboard', array($this, 'render_owner_dashboard'));
        
        // AJAX handlers
        add_action('wp_ajax_calculate_investment_roi', array($this, 'ajax_calculate_roi'));
        add_action('wp_ajax_nopriv_calculate_investment_roi', array($this, 'ajax_calculate_roi'));
        add_action('wp_ajax_get_ownership_report', array($this, 'ajax_get_ownership_report'));
        add_action('wp_ajax_authorize_rental', array($this, 'ajax_authorize_rental'));
        
        // Admin hooks
        add_action('add_meta_boxes', array($this, 'add_property_metaboxes'));
        add_action('save_post', array($this, 'save_timeshare_meta'));
    }
    
    /**
     * Registra hooks
     */
    public function register_hooks() {
        // Hook per gestione revenue
        add_action('colitalia_booking_completed', array($this, 'distribute_revenue'), 10, 2);
        add_action('colitalia_monthly_revenue_calculation', array($this, 'calculate_monthly_revenues'));
        
        // Cron job per calcoli mensili
        if (!wp_next_scheduled('colitalia_monthly_revenue_calculation')) {
            wp_schedule_event(strtotime('first day of next month'), 'monthly', 'colitalia_monthly_revenue_calculation');
        }
    }
    
    /**
     * Calcola ROI investimento
     */
    public function calculate_investment_roi($property_id, $investment_amount, $ownership_weeks = 4) {
        $property_data = colitalia_get_property_data($property_id);
        if (!$property_data) {
            return array('error' => 'Proprietà non trovata');
        }
        
        // Dati base
        $weekly_rental_price = $property_data->weekly_price;
        $annual_weeks = 52;
        $occupancy_rate = get_option('colitalia_average_occupancy_rate', 75) / 100;
        $management_fee_rate = get_option('colitalia_multiproperty_management_fee', 10) / 100;
        $maintenance_cost_annual = get_post_meta($property_id, '_annual_maintenance_cost', true) ?: ($weekly_rental_price * 2);
        
        // Calcoli revenue
        $gross_annual_revenue = $weekly_rental_price * $annual_weeks * $occupancy_rate;
        $management_fees = $gross_annual_revenue * $management_fee_rate;
        $net_annual_revenue = $gross_annual_revenue - $management_fees - $maintenance_cost_annual;
        
        // Quota del proprietario
        $ownership_percentage = ($ownership_weeks / $annual_weeks) * 100;
        $owner_annual_revenue = $net_annual_revenue * ($ownership_percentage / 100);
        
        // ROI Calculations
        $annual_roi = ($owner_annual_revenue / $investment_amount) * 100;
        $payback_years = $investment_amount / $owner_annual_revenue;
        
        // Proiezioni
        $projections = array();
        for ($year = 1; $year <= 10; $year++) {
            $annual_appreciation = 1.03; // 3% apprezzamento annuo
            $revenue_growth = 1.02; // 2% crescita revenue annua
            
            $projected_revenue = $owner_annual_revenue * pow($revenue_growth, $year);
            $projected_property_value = $investment_amount * pow($annual_appreciation, $year);
            $cumulative_revenue = 0;
            
            for ($y = 1; $y <= $year; $y++) {
                $cumulative_revenue += $owner_annual_revenue * pow($revenue_growth, $y);
            }
            
            $total_return = $cumulative_revenue + ($projected_property_value - $investment_amount);
            $total_roi = (($total_return / $investment_amount) - 1) * 100;
            
            $projections[$year] = array(
                'year' => $year,
                'annual_revenue' => $projected_revenue,
                'cumulative_revenue' => $cumulative_revenue,
                'property_value' => $projected_property_value,
                'total_return' => $total_return,
                'total_roi' => $total_roi
            );
        }
        
        return array(
            'investment_amount' => $investment_amount,
            'ownership_weeks' => $ownership_weeks,
            'ownership_percentage' => $ownership_percentage,
            'weekly_rental_price' => $weekly_rental_price,
            'occupancy_rate' => $occupancy_rate * 100,
            'gross_annual_revenue' => $gross_annual_revenue,
            'management_fees' => $management_fees,
            'maintenance_costs' => $maintenance_cost_annual,
            'net_annual_revenue' => $net_annual_revenue,
            'owner_annual_revenue' => $owner_annual_revenue,
            'annual_roi' => $annual_roi,
            'payback_years' => $payback_years,
            'monthly_revenue' => $owner_annual_revenue / 12,
            'projections' => $projections
        );
    }
    
    /**
     * Crea investimento multiproprietà
     */
    public function create_investment($property_id, $owner_id, $investment_data) {
        global $wpdb;
        
        // Valida dati
        $required_fields = array('investment_amount', 'ownership_percentage', 'weekly_slots_owned');
        foreach ($required_fields as $field) {
            if (empty($investment_data[$field])) {
                return array('success' => false, 'error' => "Campo richiesto: $field");
            }
        }
        
        // Verifica se la proprietà è abilitata per multiproprietà
        $is_multiproperty = get_post_meta($property_id, '_is_multiproperty', true);
        if (!$is_multiproperty) {
            return array('success' => false, 'error' => 'Proprietà non abilitata per multiproprietà');
        }
        
        // Verifica disponibilità slot
        $total_owned_percentage = $this->get_total_owned_percentage($property_id);
        $available_percentage = 100 - $total_owned_percentage;
        
        if ($investment_data['ownership_percentage'] > $available_percentage) {
            return array('success' => false, 'error' => 'Percentuale di proprietà non disponibile');
        }
        
        // Calcola ROI target
        $roi_calculation = $this->calculate_investment_roi(
            $property_id,
            $investment_data['investment_amount'],
            $investment_data['weekly_slots_owned']
        );
        
        $investments_table = $wpdb->prefix . 'colitalia_multiproperty_investments';
        
        $result = $wpdb->insert(
            $investments_table,
            array(
                'property_id' => $property_id,
                'owner_id' => $owner_id,
                'investment_amount' => floatval($investment_data['investment_amount']),
                'ownership_percentage' => floatval($investment_data['ownership_percentage']),
                'weekly_slots_owned' => intval($investment_data['weekly_slots_owned']),
                'annual_profit_target' => $roi_calculation['owner_annual_revenue'],
                'management_fee_percentage' => floatval($investment_data['management_fee_percentage'] ?? 10)
            ),
            array('%d', '%d', '%f', '%f', '%d', '%f', '%f')
        );
        
        if ($result) {
            $investment_id = $wpdb->insert_id;
            
            // Aggiorna meta proprietà
            $this->update_property_investment_meta($property_id);
            
            // Trigger action
            do_action('colitalia_investment_created', $investment_id, $investment_data);
            
            return array(
                'success' => true,
                'investment_id' => $investment_id,
                'roi_data' => $roi_calculation
            );
        }
        
        return array('success' => false, 'error' => 'Errore creazione investimento');
    }
    
    /**
     * Ottiene investimenti di una proprietà
     */
    public function get_property_investments($property_id) {
        global $wpdb;
        
        $investments_table = $wpdb->prefix . 'colitalia_multiproperty_investments';
        $users_table = $wpdb->users;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, u.display_name as owner_name, u.user_email as owner_email
             FROM $investments_table i
             LEFT JOIN $users_table u ON i.owner_id = u.ID
             WHERE i.property_id = %d
             ORDER BY i.ownership_percentage DESC",
            $property_id
        ));
    }
    
    /**
     * Ottiene investimenti di un proprietario
     */
    public function get_owner_investments($owner_id) {
        global $wpdb;
        
        $investments_table = $wpdb->prefix . 'colitalia_multiproperty_investments';
        $properties_table = $wpdb->prefix . 'colitalia_properties';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, p.location, post.post_title as property_title,
                    post.guid as property_url
             FROM $investments_table i
             LEFT JOIN $properties_table p ON i.property_id = p.post_id
             LEFT JOIN {$wpdb->posts} post ON p.post_id = post.ID
             WHERE i.owner_id = %d
             ORDER BY i.created_at DESC",
            $owner_id
        ));
    }
    
    /**
     * Distribuisce revenue ai proprietari
     */
    public function distribute_revenue($booking_id, $booking) {
        $property_id = $booking->property_id;
        $investments = $this->get_property_investments($property_id);
        
        if (empty($investments)) {
            return; // Nessun investimento attivo
        }
        
        $booking_revenue = $booking->total_price;
        $management_fee_rate = get_option('colitalia_multiproperty_management_fee', 10) / 100;
        $net_revenue = $booking_revenue * (1 - $management_fee_rate);
        
        global $wpdb;
        $revenue_table = $wpdb->prefix . 'colitalia_revenue_distribution';
        
        foreach ($investments as $investment) {
            $owner_share = $net_revenue * ($investment->ownership_percentage / 100);
            
            $wpdb->insert(
                $revenue_table,
                array(
                    'booking_id' => $booking_id,
                    'property_id' => $property_id,
                    'investment_id' => $investment->id,
                    'owner_id' => $investment->owner_id,
                    'gross_revenue' => $booking_revenue,
                    'owner_percentage' => $investment->ownership_percentage,
                    'owner_share' => $owner_share,
                    'distribution_date' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%d', '%f', '%f', '%f', '%s')
            );
        }
        
        colitalia_log("Revenue distributed for booking {$booking_id}: €{$net_revenue}");
    }
    
    /**
     * Calcola revenue mensili
     */
    public function calculate_monthly_revenues() {
        global $wpdb;
        
        $revenue_table = $wpdb->prefix . 'colitalia_revenue_distribution';
        $investments_table = $wpdb->prefix . 'colitalia_multiproperty_investments';
        
        // Calcola per il mese precedente
        $last_month = date('Y-m', strtotime('-1 month'));
        
        $monthly_revenues = $wpdb->get_results($wpdb->prepare(
            "SELECT investment_id, owner_id, property_id,
                    SUM(owner_share) as total_revenue,
                    COUNT(booking_id) as bookings_count
             FROM $revenue_table
             WHERE DATE_FORMAT(distribution_date, '%%Y-%%m') = %s
             GROUP BY investment_id, owner_id, property_id",
            $last_month
        ));
        
        foreach ($monthly_revenues as $revenue) {
            // Invia report mensile via email
            $this->send_monthly_revenue_report($revenue, $last_month);
        }
        
        colitalia_log("Monthly revenue calculation completed for {$last_month}");
    }
    
    /**
     * Invia report mensile revenue
     */
    private function send_monthly_revenue_report($revenue_data, $month) {
        $owner = get_user_by('id', $revenue_data->owner_id);
        if (!$owner) return;
        
        $property = get_post($revenue_data->property_id);
        
        $email_manager = new \ColitaliaRealEstate\Email\EmailManager();
        $subject = sprintf(
            '[%s] Report Mensile Investimento - %s',
            get_bloginfo('name'),
            date_i18n('F Y', strtotime($month . '-01'))
        );
        
        $template_data = array(
            'owner_name' => $owner->display_name,
            'property_title' => $property->post_title,
            'month' => date_i18n('F Y', strtotime($month . '-01')),
            'total_revenue' => colitalia_format_currency($revenue_data->total_revenue),
            'bookings_count' => $revenue_data->bookings_count,
            'property_url' => get_permalink($property->ID)
        );
        
        $content = $email_manager->render_template('monthly-revenue-report', $template_data);
        $email_manager->send_email($owner->user_email, $subject, $content);
    }
    
    /**
     * Autorizza affitto Colitalia
     */
    public function authorize_rental($investment_id, $weeks_to_authorize) {
        global $wpdb;
        
        $investment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colitalia_multiproperty_investments WHERE id = %d",
            $investment_id
        ));
        
        if (!$investment) {
            return array('success' => false, 'error' => 'Investimento non trovato');
        }
        
        if ($weeks_to_authorize > $investment->weekly_slots_owned) {
            return array('success' => false, 'error' => 'Settimane eccedenti la proprietà');
        }
        
        // Salva autorizzazione
        $authorization_table = $wpdb->prefix . 'colitalia_rental_authorizations';
        
        $result = $wpdb->insert(
            $authorization_table,
            array(
                'investment_id' => $investment_id,
                'owner_id' => $investment->owner_id,
                'property_id' => $investment->property_id,
                'weeks_authorized' => $weeks_to_authorize,
                'authorization_period' => date('Y'),
                'status' => 'active',
                'authorized_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result) {
            return array(
                'success' => true,
                'authorization_id' => $wpdb->insert_id
            );
        }
        
        return array('success' => false, 'error' => 'Errore autorizzazione');
    }
    
    /**
     * Ottiene percentuale totale posseduta
     */
    private function get_total_owned_percentage($property_id) {
        global $wpdb;
        
        return (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(ownership_percentage), 0)
             FROM {$wpdb->prefix}colitalia_multiproperty_investments
             WHERE property_id = %d",
            $property_id
        ));
    }
    
    /**
     * Aggiorna meta investimenti proprietà
     */
    private function update_property_investment_meta($property_id) {
        $total_owned = $this->get_total_owned_percentage($property_id);
        $available_percentage = 100 - $total_owned;
        
        update_post_meta($property_id, '_investment_owned_percentage', $total_owned);
        update_post_meta($property_id, '_investment_available_percentage', $available_percentage);
        update_post_meta($property_id, '_investment_last_update', current_time('mysql'));
    }
    
    /**
     * Aggiunge metaboxes proprietà
     */
    public function add_property_metaboxes() {
        add_meta_box(
            'colitalia-timeshare-settings',
            'Impostazioni Multiproprietà',
            array($this, 'render_timeshare_metabox'),
            'proprieta',
            'normal',
            'high'
        );
    }
    
    /**
     * Renderizza metabox multiproprietà
     */
    public function render_timeshare_metabox($post) {
        wp_nonce_field('colitalia_timeshare_meta', 'colitalia_timeshare_nonce');
        
        $is_multiproperty = get_post_meta($post->ID, '_is_multiproperty', true);
        $max_investors = get_post_meta($post->ID, '_max_investors', true) ?: 10;
        $min_investment = get_post_meta($post->ID, '_min_investment_amount', true) ?: 50000;
        $max_ownership = get_post_meta($post->ID, '_max_ownership_percentage', true) ?: 25;
        $annual_maintenance = get_post_meta($post->ID, '_annual_maintenance_cost', true) ?: 2000;
        
        $investments = $this->get_property_investments($post->ID);
        $total_owned = $this->get_total_owned_percentage($post->ID);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="is_multiproperty">Abilita Multiproprietà</label></th>
                <td>
                    <input type="checkbox" id="is_multiproperty" name="is_multiproperty" value="1" 
                           <?php checked($is_multiproperty, 1); ?> />
                    <p class="description">Abilita questa proprietà per investimenti multiproprietà</p>
                </td>
            </tr>
            <tr>
                <th><label for="max_investors">Max Investitori</label></th>
                <td>
                    <input type="number" id="max_investors" name="max_investors" 
                           value="<?php echo esc_attr($max_investors); ?>" min="1" max="50" />
                </td>
            </tr>
            <tr>
                <th><label for="min_investment_amount">Investimento Minimo</label></th>
                <td>
                    <input type="number" id="min_investment_amount" name="min_investment_amount" 
                           value="<?php echo esc_attr($min_investment); ?>" min="200" step="1" />
                    <span class="description">€</span>
                </td>
            </tr>
            <tr>
                <th><label for="max_ownership_percentage">Max % Proprietà</label></th>
                <td>
                    <input type="number" id="max_ownership_percentage" name="max_ownership_percentage" 
                           value="<?php echo esc_attr($max_ownership); ?>" min="1" max="50" step="0.1" />
                    <span class="description">%</span>
                </td>
            </tr>
            <tr>
                <th><label for="annual_maintenance_cost">Costo Manutenzione Annua</label></th>
                <td>
                    <input type="number" id="annual_maintenance_cost" name="annual_maintenance_cost" 
                           value="<?php echo esc_attr($annual_maintenance); ?>" min="0" step="100" />
                    <span class="description">€</span>
                </td>
            </tr>
        </table>
        
        <?php if (!empty($investments)): ?>
        <h4>Investitori Attuali (<?php echo number_format($total_owned, 1); ?>% posseduto)</h4>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Investitore</th>
                    <th>Investimento</th>
                    <th>% Proprietà</th>
                    <th>Settimane</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($investments as $inv): ?>
                <tr>
                    <td><?php echo esc_html($inv->owner_name); ?></td>
                    <td><?php echo colitalia_format_currency($inv->investment_amount); ?></td>
                    <td><?php echo number_format($inv->ownership_percentage, 1); ?>%</td>
                    <td><?php echo $inv->weekly_slots_owned; ?></td>
                    <td><?php echo date_i18n('d/m/Y', strtotime($inv->created_at)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Salva meta multiproprietà
     */
    public function save_timeshare_meta($post_id) {
        if (!isset($_POST['colitalia_timeshare_nonce']) || 
            !wp_verify_nonce($_POST['colitalia_timeshare_nonce'], 'colitalia_timeshare_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = array(
            'is_multiproperty' => 'boolean',
            'max_investors' => 'integer',
            'min_investment_amount' => 'float',
            'max_ownership_percentage' => 'float',
            'annual_maintenance_cost' => 'float'
        );
        
        foreach ($fields as $field => $type) {
            $value = $_POST[$field] ?? '';
            
            switch ($type) {
                case 'boolean':
                    $value = !empty($value) ? 1 : 0;
                    break;
                case 'integer':
                    $value = intval($value);
                    break;
                case 'float':
                    $value = floatval($value);
                    break;
            }
            
            update_post_meta($post_id, '_' . $field, $value);
        }
    }
    
    /**
     * Renderizza calcolatore investimenti
     */
    public function render_investment_calculator($atts) {
        $atts = shortcode_atts(array(
            'property_id' => get_the_ID()
        ), $atts);
        
        if (!$atts['property_id']) {
            return '<p>Proprietà non specificata.</p>';
        }
        
        ob_start();
        include COLITALIA_PLUGIN_PATH . 'templates/timeshare/investment-calculator.php';
        return ob_get_clean();
    }
    
    /**
     * Renderizza dashboard proprietario
     */
    public function render_owner_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>Devi essere loggato per vedere il dashboard.</p>';
        }
        
        $owner_id = get_current_user_id();
        $investments = $this->get_owner_investments($owner_id);
        
        ob_start();
        ?>
        <div id="colitalia-owner-dashboard">
            <h3>I Miei Investimenti</h3>
            
            <?php if (empty($investments)): ?>
                <p>Non hai ancora investimenti attivi.</p>
            <?php else: ?>
                <div class="investments-grid">
                    <?php foreach ($investments as $investment): ?>
                        <div class="investment-card">
                            <h4><?php echo esc_html($investment->property_title); ?></h4>
                            <p><strong>Investimento:</strong> <?php echo colitalia_format_currency($investment->investment_amount); ?></p>
                            <p><strong>Proprietà:</strong> <?php echo number_format($investment->ownership_percentage, 1); ?>%</p>
                            <p><strong>Settimane:</strong> <?php echo $investment->weekly_slots_owned; ?></p>
                            <p><strong>Target Annuo:</strong> <?php echo colitalia_format_currency($investment->annual_profit_target); ?></p>
                            <a href="#" class="button view-details" data-investment="<?php echo $investment->id; ?>">
                                Dettagli Revenue
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX calcola ROI
     */
    public function ajax_calculate_roi() {
        check_ajax_referer('colitalia_investment_nonce', 'nonce');
        
        $property_id = intval($_POST['property_id'] ?? 0);
        $investment_amount = floatval($_POST['investment_amount'] ?? 0);
        $ownership_weeks = intval($_POST['ownership_weeks'] ?? 1);
        
        if (!$property_id || !$investment_amount) {
            wp_send_json_error('Parametri mancanti');
        }
        
        $roi_data = $this->calculate_investment_roi($property_id, $investment_amount, $ownership_weeks);
        
        if (isset($roi_data['error'])) {
            wp_send_json_error($roi_data['error']);
        }
        
        wp_send_json_success($roi_data);
    }
    
    /**
     * AJAX ottieni report proprietà
     */
    public function ajax_get_ownership_report() {
        check_ajax_referer('colitalia_investment_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Login richiesto');
        }
        
        $investment_id = intval($_POST['investment_id'] ?? 0);
        $owner_id = get_current_user_id();
        
        global $wpdb;
        $revenue_table = $wpdb->prefix . 'colitalia_revenue_distribution';
        
        $revenues = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE_FORMAT(distribution_date, '%%Y-%%m') as month,
                    SUM(owner_share) as total_revenue,
                    COUNT(booking_id) as bookings_count
             FROM $revenue_table
             WHERE investment_id = %d AND owner_id = %d
             GROUP BY DATE_FORMAT(distribution_date, '%%Y-%%m')
             ORDER BY month DESC
             LIMIT 12",
            $investment_id,
            $owner_id
        ));
        
        wp_send_json_success($revenues);
    }
    
    /**
     * AJAX autorizza affitto
     */
    public function ajax_authorize_rental() {
        check_ajax_referer('colitalia_investment_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Login richiesto');
        }
        
        $investment_id = intval($_POST['investment_id'] ?? 0);
        $weeks = intval($_POST['weeks'] ?? 0);
        
        $result = $this->authorize_rental($investment_id, $weeks);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
}