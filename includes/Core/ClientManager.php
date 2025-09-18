<?php
namespace ColitaliaRealEstate\Core;

/**
 * Client Manager Class
 * Handles customer data, GDPR compliance, and client relationship management
 */
class ClientManager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_register_client', array($this, 'ajax_register_client'));
        add_action('wp_ajax_nopriv_register_client', array($this, 'ajax_register_client'));
        add_action('wp_ajax_update_client_profile', array($this, 'ajax_update_client_profile'));
        add_action('wp_ajax_export_client_data', array($this, 'ajax_export_client_data'));
        add_action('wp_ajax_delete_client_data', array($this, 'ajax_delete_client_data'));
        add_shortcode('colitalia_client_registration', array($this, 'render_client_registration'));
        add_shortcode('colitalia_client_profile', array($this, 'render_client_profile'));
        
        // GDPR hooks
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_data_exporter'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_data_eraser'));
        
        // Automated data cleanup
        add_action('colitalia_gdpr_data_cleanup', array($this, 'cleanup_expired_data'));
        
        // Profile completion hooks
        add_action('user_register', array($this, 'handle_user_registration'));
    }
    
    /**
     * Initialize client manager
     */
    public function init() {
        // Schedule GDPR cleanup if not already scheduled
        if (!wp_next_scheduled('colitalia_gdpr_data_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'colitalia_gdpr_data_cleanup');
        }
        
        // Add client role
        $this->add_client_role();
    }
    
    /**
     * Add client role
     */
    private function add_client_role() {
        $role = get_role('property_client');
        if (!$role) {
            add_role('property_client', __('Cliente Colitalia', COLITALIA_PLUGIN_TEXTDOMAIN), array(
                'read' => true,
                'view_own_bookings' => true,
                'manage_own_profile' => true
            ));
        }
    }
    
    /**
     * Create or update client record
     */
    public function create_or_update_client($client_data, $user_id = null) {
        global $wpdb;
        
        // Validate required fields
        $required_fields = array('first_name', 'last_name', 'email');
        foreach ($required_fields as $field) {
            if (empty($client_data[$field])) {
                return array('success' => false, 'error' => "Campo richiesto mancante: $field");
            }
        }
        
        // Validate email
        if (!is_email($client_data['email'])) {
            return array('success' => false, 'error' => 'Formato email non valido');
        }
        
        // Sanitize data
        $sanitized_data = array(
            'first_name' => sanitize_text_field($client_data['first_name']),
            'last_name' => sanitize_text_field($client_data['last_name']),
            'email' => sanitize_email($client_data['email']),
            'phone' => sanitize_text_field($client_data['phone'] ?? ''),
            'address' => sanitize_textarea_field($client_data['address'] ?? ''),
            'city' => sanitize_text_field($client_data['city'] ?? ''),
            'country' => sanitize_text_field($client_data['country'] ?? ''),
            'postal_code' => sanitize_text_field($client_data['postal_code'] ?? ''),
            'birth_date' => sanitize_text_field($client_data['birth_date'] ?? ''),
            'document_type' => sanitize_text_field($client_data['document_type'] ?? ''),
            'document_number' => sanitize_text_field($client_data['document_number'] ?? ''),
            'gdpr_consent' => isset($client_data['gdpr_consent']) ? 1 : 0,
            'marketing_consent' => isset($client_data['marketing_consent']) ? 1 : 0
        );
        
        $clients_table = $wpdb->prefix . 'colitalia_clients';
        
        // Check if client already exists
        $existing_client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $clients_table WHERE email = %s",
            $sanitized_data['email']
        ));
        
        if ($existing_client) {
            // Update existing client
            $result = $wpdb->update(
                $clients_table,
                $sanitized_data,
                array('id' => $existing_client->id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d'),
                array('%d')
            );
            
            if ($result !== false) {
                $client_id = $existing_client->id;
                
                // Link to WordPress user if provided
                if ($user_id) {
                    update_user_meta($user_id, 'colitalia_client_id', $client_id);
                }
                
                colitalia_log("Client updated: ID $client_id, Email: {$sanitized_data['email']}");
                
                return array(
                    'success' => true,
                    'client_id' => $client_id,
                    'action' => 'updated'
                );
            }
        } else {
            // Create new client
            $result = $wpdb->insert(
                $clients_table,
                $sanitized_data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d')
            );
            
            if ($result) {
                $client_id = $wpdb->insert_id;
                
                // Link to WordPress user if provided
                if ($user_id) {
                    update_user_meta($user_id, 'colitalia_client_id', $client_id);
                    
                    // Add client role to user
                    $user = new \WP_User($user_id);
                    $user->add_role('property_client');
                }
                
                // Trigger client registered action
                do_action('colitalia_client_registered', $client_id, $sanitized_data);
                
                colitalia_log("Client created: ID $client_id, Email: {$sanitized_data['email']}");
                
                return array(
                    'success' => true,
                    'client_id' => $client_id,
                    'action' => 'created'
                );
            }
        }
        
        return array('success' => false, 'error' => 'Errore durante il salvataggio dei dati cliente');
    }
    
    /**
     * Get client by ID
     */
    public function get_client($client_id) {
        global $wpdb;
        
        $clients_table = $wpdb->prefix . 'colitalia_clients';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $clients_table WHERE id = %d",
            $client_id
        ));
    }
    
    /**
     * Get client by email
     */
    public function get_client_by_email($email) {
        global $wpdb;
        
        $clients_table = $wpdb->prefix . 'colitalia_clients';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $clients_table WHERE email = %s",
            $email
        ));
    }
    
    /**
     * Get client by WordPress user ID
     */
    public function get_client_by_user_id($user_id) {
        $client_id = get_user_meta($user_id, 'colitalia_client_id', true);
        
        if ($client_id) {
            return $this->get_client($client_id);
        }
        
        // Try to find by email
        $user = get_userdata($user_id);
        if ($user) {
            return $this->get_client_by_email($user->user_email);
        }
        
        return null;
    }
    
    /**
     * Get client statistics
     */
    public function get_client_statistics($client_id) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'colitalia_bookings';
        
        $stats = array(
            'total_bookings' => 0,
            'completed_bookings' => 0,
            'cancelled_bookings' => 0,
            'total_spent' => 0,
            'average_booking_value' => 0,
            'last_booking_date' => null,
            'favorite_destinations' => array()
        );
        
        // Total bookings and spent
        $booking_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
                SUM(CASE WHEN status IN ('paid', 'completed') THEN total_price ELSE 0 END) as total_spent,
                AVG(CASE WHEN status IN ('paid', 'completed') THEN total_price ELSE NULL END) as average_booking_value,
                MAX(created_at) as last_booking_date
             FROM $bookings_table 
             WHERE client_id = %d",
            $client_id
        ));
        
        if ($booking_stats) {
            $stats = array_merge($stats, (array) $booking_stats);
        }
        
        // Favorite destinations
        $properties_table = $wpdb->prefix . 'colitalia_properties';
        $favorite_destinations = $wpdb->get_results($wpdb->prepare(
            "SELECT cp.location, COUNT(*) as booking_count
             FROM $bookings_table b
             JOIN $properties_table cp ON b.property_id = cp.post_id
             WHERE b.client_id = %d AND b.status IN ('paid', 'completed')
             GROUP BY cp.location
             ORDER BY booking_count DESC
             LIMIT 3",
            $client_id
        ));
        
        $stats['favorite_destinations'] = $favorite_destinations;
        
        return $stats;
    }
    
    /**
     * Update client consent preferences
     */
    public function update_client_consent($client_id, $gdpr_consent, $marketing_consent) {
        global $wpdb;
        
        $clients_table = $wpdb->prefix . 'colitalia_clients';
        
        $result = $wpdb->update(
            $clients_table,
            array(
                'gdpr_consent' => $gdpr_consent ? 1 : 0,
                'marketing_consent' => $marketing_consent ? 1 : 0,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $client_id),
            array('%d', '%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            colitalia_log("Client consent updated: ID $client_id, GDPR: $gdpr_consent, Marketing: $marketing_consent");
            return true;
        }
        
        return false;
    }
    
    /**
     * Export client data (GDPR compliance)
     */
    public function export_client_data($client_id) {
        global $wpdb;
        
        $client = $this->get_client($client_id);
        if (!$client) {
            return array('success' => false, 'error' => 'Cliente non trovato');
        }
        
        // Prepare export data
        $export_data = array(
            'personal_info' => array(
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'city' => $client->city,
                'country' => $client->country,
                'postal_code' => $client->postal_code,
                'birth_date' => $client->birth_date,
                'document_type' => $client->document_type,
                'document_number' => $client->document_number,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at
            ),
            'consent' => array(
                'gdpr_consent' => $client->gdpr_consent,
                'marketing_consent' => $client->marketing_consent
            ),
            'bookings' => array(),
            'statistics' => $this->get_client_statistics($client_id)
        );
        
        // Get booking history
        $bookings_table = $wpdb->prefix . 'colitalia_bookings';
        $properties_table = $wpdb->prefix . 'colitalia_properties';
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, post.post_title as property_title, cp.location
             FROM $bookings_table b
             LEFT JOIN $properties_table cp ON b.property_id = cp.post_id
             LEFT JOIN {$wpdb->posts} post ON cp.post_id = post.ID
             WHERE b.client_id = %d
             ORDER BY b.created_at DESC",
            $client_id
        ));
        
        foreach ($bookings as $booking) {
            $export_data['bookings'][] = array(
                'booking_code' => $booking->booking_code,
                'property_title' => $booking->property_title,
                'location' => $booking->location,
                'check_in' => $booking->check_in,
                'check_out' => $booking->check_out,
                'guests' => $booking->guests,
                'total_price' => $booking->total_price,
                'status' => $booking->status,
                'created_at' => $booking->created_at,
                'special_requests' => $booking->special_requests
            );
        }
        
        return array(
            'success' => true,
            'data' => $export_data
        );
    }
    
    /**
     * Delete client data (GDPR right to be forgotten)
     */
    public function delete_client_data($client_id, $anonymize_bookings = true) {
        global $wpdb;
        
        $client = $this->get_client($client_id);
        if (!$client) {
            return array('success' => false, 'error' => 'Cliente non trovato');
        }
        
        $clients_table = $wpdb->prefix . 'colitalia_clients';
        $bookings_table = $wpdb->prefix . 'colitalia_bookings';
        $email_logs_table = $wpdb->prefix . 'colitalia_email_logs';
        
        // Check for active bookings
        $active_bookings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table 
             WHERE client_id = %d 
             AND status IN ('confirmed', 'paid') 
             AND check_in > NOW()",
            $client_id
        ));
        
        if ($active_bookings > 0) {
            return array(
                'success' => false, 
                'error' => 'Impossibile eliminare: prenotazioni attive presenti'
            );
        }
        
        $wpdb->query('START TRANSACTION');
        
        try {
            if ($anonymize_bookings) {
                // Anonymize historical bookings instead of deleting
                $wpdb->update(
                    $bookings_table,
                    array('client_id' => 0), // Set to anonymous
                    array('client_id' => $client_id),
                    array('%d'),
                    array('%d')
                );
            } else {
                // Delete all bookings
                $wpdb->delete(
                    $bookings_table,
                    array('client_id' => $client_id),
                    array('%d')
                );
            }
            
            // Delete email logs
            $wpdb->delete(
                $email_logs_table,
                array('client_id' => $client_id),
                array('%d')
            );
            
            // Delete client record
            $wpdb->delete(
                $clients_table,
                array('id' => $client_id),
                array('%d')
            );
            
            // Remove WordPress user link
            $user_query = new \WP_User_Query(array(
                'meta_key' => 'colitalia_client_id',
                'meta_value' => $client_id
            ));
            
            foreach ($user_query->get_results() as $user) {
                delete_user_meta($user->ID, 'colitalia_client_id');
            }
            
            $wpdb->query('COMMIT');
            
            colitalia_log("Client data deleted: ID $client_id, Email: {$client->email}");
            
            return array('success' => true);
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            
            colitalia_log("Error deleting client data: " . $e->getMessage(), 'error');
            
            return array(
                'success' => false, 
                'error' => 'Errore durante l\'eliminazione dei dati'
            );
        }
    }
    
    /**
     * Cleanup expired data based on GDPR retention policy
     */
    public function cleanup_expired_data() {
        global $wpdb;
        
        $retention_days = get_option('colitalia_gdpr_retention_days', 365);
        $cutoff_date = date('Y-m-d', strtotime("-$retention_days days"));
        
        $clients_table = $wpdb->prefix . 'colitalia_clients';
        $bookings_table = $wpdb->prefix . 'colitalia_bookings';
        $email_logs_table = $wpdb->prefix . 'colitalia_emails_logs';
        
        // Find clients with no recent activity and no GDPR consent
        $expired_clients = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.email FROM $clients_table c
             LEFT JOIN $bookings_table b ON c.id = b.client_id
             WHERE c.gdpr_consent = 0 
             AND c.updated_at < %s
             AND (b.created_at IS NULL OR b.created_at < %s)
             GROUP BY c.id",
            $cutoff_date,
            $cutoff_date
        ));
        
        $cleaned_count = 0;
        foreach ($expired_clients as $client) {
            $result = $this->delete_client_data($client->id, true);
            if ($result['success']) {
                $cleaned_count++;
            }
        }
        
        // Cleanup old email logs
        $email_cleanup_days = get_option('colitalia_email_logs_retention_days', 90);
        $email_cutoff_date = date('Y-m-d', strtotime("-$email_cleanup_days days"));
        
        $deleted_emails = $wpdb->query($wpdb->prepare(
            "DELETE FROM $email_logs_table WHERE sent_at < %s",
            $email_cutoff_date
        ));
        
        if ($cleaned_count > 0 || $deleted_emails > 0) {
            colitalia_log("GDPR cleanup completed: $cleaned_count clients, $deleted_emails email logs");
        }
    }
    
    /**
     * Handle new user registration
     */
    public function handle_user_registration($user_id) {
        $user = get_userdata($user_id);
        
        // Create corresponding client record
        $client_data = array(
            'first_name' => $user->first_name ?: '',
            'last_name' => $user->last_name ?: '',
            'email' => $user->user_email,
            'gdpr_consent' => 1, // Assume consent during registration
            'marketing_consent' => 0
        );
        
        $result = $this->create_or_update_client($client_data, $user_id);
        
        if ($result['success']) {
            // Send welcome email
            $email_automation = new EmailAutomation();
            $email_automation->send_welcome_email($user_id, $result['client_id']);
        }
    }
    
    /**
     * Register GDPR data exporter
     */
    public function register_data_exporter($exporters) {
        $exporters['colitalia-client-data'] = array(
            'exporter_friendly_name' => __('Dati Cliente Colitalia', COLITALIA_PLUGIN_TEXTDOMAIN),
            'callback' => array($this, 'gdpr_export_client_data')
        );
        
        return $exporters;
    }
    
    /**
     * Register GDPR data eraser
     */
    public function register_data_eraser($erasers) {
        $erasers['colitalia-client-data'] = array(
            'eraser_friendly_name' => __('Dati Cliente Colitalia', COLITALIA_PLUGIN_TEXTDOMAIN),
            'callback' => array($this, 'gdpr_erase_client_data')
        );
        
        return $erasers;
    }
    
    /**
     * GDPR export callback
     */
    public function gdpr_export_client_data($email_address, $page = 1) {
        $client = $this->get_client_by_email($email_address);
        
        if (!$client) {
            return array(
                'data' => array(),
                'done' => true
            );
        }
        
        $export_result = $this->export_client_data($client->id);
        
        if (!$export_result['success']) {
            return array(
                'data' => array(),
                'done' => true
            );
        }
        
        $export_items = array();
        $data = $export_result['data'];
        
        // Personal information
        $export_items[] = array(
            'group_id' => 'colitalia_client_personal',
            'group_label' => __('Informazioni Personali', COLITALIA_PLUGIN_TEXTDOMAIN),
            'item_id' => 'client_' . $client->id,
            'data' => array(
                array(
                    'name' => __('Nome', COLITALIA_PLUGIN_TEXTDOMAIN),
                    'value' => $data['personal_info']['first_name']
                ),
                array(
                    'name' => __('Cognome', COLITALIA_PLUGIN_TEXTDOMAIN),
                    'value' => $data['personal_info']['last_name']
                ),
                array(
                    'name' => __('Email', COLITALIA_PLUGIN_TEXTDOMAIN),
                    'value' => $data['personal_info']['email']
                ),
                array(
                    'name' => __('Telefono', COLITALIA_PLUGIN_TEXTDOMAIN),
                    'value' => $data['personal_info']['phone']
                ),
                array(
                    'name' => __('Indirizzo', COLITALIA_PLUGIN_TEXTDOMAIN),
                    'value' => $data['personal_info']['address']
                )
            )
        );
        
        // Booking history
        foreach ($data['bookings'] as $booking) {
            $export_items[] = array(
                'group_id' => 'colitalia_client_bookings',
                'group_label' => __('Storico Prenotazioni', COLITALIA_PLUGIN_TEXTDOMAIN),
                'item_id' => 'booking_' . $booking['booking_code'],
                'data' => array(
                    array(
                        'name' => __('Codice Prenotazione', COLITALIA_PLUGIN_TEXTDOMAIN),
                        'value' => $booking['booking_code']
                    ),
                    array(
                        'name' => __('Proprietà', COLITALIA_PLUGIN_TEXTDOMAIN),
                        'value' => $booking['property_title']
                    ),
                    array(
                        'name' => __('Date Soggiorno', COLITALIA_PLUGIN_TEXTDOMAIN),
                        'value' => $booking['check_in'] . ' - ' . $booking['check_out']
                    ),
                    array(
                        'name' => __('Importo', COLITALIA_PLUGIN_TEXTDOMAIN),
                        'value' => colitalia_format_currency($booking['total_price'])
                    )
                )
            );
        }
        
        return array(
            'data' => $export_items,
            'done' => true
        );
    }
    
    /**
     * GDPR erase callback
     */
    public function gdpr_erase_client_data($email_address, $page = 1) {
        $client = $this->get_client_by_email($email_address);
        
        if (!$client) {
            return array(
                'items_removed' => 0,
                'items_retained' => 0,
                'messages' => array(),
                'done' => true
            );
        }
        
        $result = $this->delete_client_data($client->id, true);
        
        if ($result['success']) {
            return array(
                'items_removed' => 1,
                'items_retained' => 0,
                'messages' => array(__('Dati cliente rimossi con successo.', COLITALIA_PLUGIN_TEXTDOMAIN)),
                'done' => true
            );
        } else {
            return array(
                'items_removed' => 0,
                'items_retained' => 1,
                'messages' => array($result['error']),
                'done' => true
            );
        }
    }
    
    /**
     * AJAX handler for client registration
     */
    public function ajax_register_client() {
        check_ajax_referer('colitalia_booking_nonce', 'nonce');
        
        $client_data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'address' => sanitize_textarea_field($_POST['address'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'country' => sanitize_text_field($_POST['country'] ?? ''),
            'postal_code' => sanitize_text_field($_POST['postal_code'] ?? ''),
            'birth_date' => sanitize_text_field($_POST['birth_date'] ?? ''),
            'document_type' => sanitize_text_field($_POST['document_type'] ?? ''),
            'document_number' => sanitize_text_field($_POST['document_number'] ?? ''),
            'gdpr_consent' => isset($_POST['gdpr_consent']),
            'marketing_consent' => isset($_POST['marketing_consent'])
        );
        
        $user_id = is_user_logged_in() ? get_current_user_id() : null;
        $result = $this->create_or_update_client($client_data, $user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * AJAX handler for updating client profile
     */
    public function ajax_update_client_profile() {
        check_ajax_referer('colitalia_client_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Login richiesto');
        }
        
        $client = $this->get_client_by_user_id(get_current_user_id());
        if (!$client) {
            wp_send_json_error('Profilo cliente non trovato');
        }
        
        $client_data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'address' => sanitize_textarea_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'country' => sanitize_text_field($_POST['country']),
            'postal_code' => sanitize_text_field($_POST['postal_code']),
            'marketing_consent' => isset($_POST['marketing_consent'])
        );
        
        $result = $this->create_or_update_client($client_data);
        
        if ($result['success']) {
            wp_send_json_success('Profilo aggiornato con successo');
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * AJAX handler for exporting client data
     */
    public function ajax_export_client_data() {
        check_ajax_referer('colitalia_client_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Login richiesto');
        }
        
        $client = $this->get_client_by_user_id(get_current_user_id());
        if (!$client) {
            wp_send_json_error('Profilo cliente non trovato');
        }
        
        $result = $this->export_client_data($client->id);
        
        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * AJAX handler for deleting client data
     */
    public function ajax_delete_client_data() {
        check_ajax_referer('colitalia_client_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Login richiesto');
        }
        
        $client = $this->get_client_by_user_id(get_current_user_id());
        if (!$client) {
            wp_send_json_error('Profilo cliente non trovato');
        }
        
        $result = $this->delete_client_data($client->id, true);
        
        if ($result['success']) {
            wp_send_json_success('Dati eliminati con successo');
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Render client registration form shortcode
     */
    public function render_client_registration($atts) {
        if (is_user_logged_in()) {
            return '<p>' . __('Sei già registrato.', COLITALIA_PLUGIN_TEXTDOMAIN) . '</p>';
        }
        
        ob_start();
        ?>
        <div class="colitalia-client-registration">
            <h3><?php _e('Registrazione Cliente', COLITALIA_PLUGIN_TEXTDOMAIN); ?></h3>
            
            <form id="client-registration-form">
                <?php wp_nonce_field('colitalia_booking_nonce', 'client_nonce'); ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="reg_first_name"><?php _e('Nome *', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                        <input type="text" id="reg_first_name" name="first_name" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="reg_last_name"><?php _e('Cognome *', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                        <input type="text" id="reg_last_name" name="last_name" required />
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="reg_email"><?php _e('Email *', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                        <input type="email" id="reg_email" name="email" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="reg_phone"><?php _e('Telefono *', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                        <input type="tel" id="reg_phone" name="phone" required />
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="reg_address"><?php _e('Indirizzo', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                    <textarea id="reg_address" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="reg_city"><?php _e('Città', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                        <input type="text" id="reg_city" name="city" />
                    </div>
                    
                    <div class="form-group">
                        <label for="reg_country"><?php _e('Paese', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                        <select id="reg_country" name="country">
                            <option value="">Seleziona...</option>
                            <option value="IT">Italia</option>
                            <option value="FR">Francia</option>
                            <option value="ES">Spagna</option>
                            <option value="DE">Germania</option>
                            <option value="UK">Regno Unito</option>
                            <option value="US">Stati Uniti</option>
                        </select>
                    </div>
                </div>
                
                <div class="gdpr-section">
                    <h4><?php _e('Privacy e Consensi', COLITALIA_PLUGIN_TEXTDOMAIN); ?></h4>
                    
                    <label class="consent-checkbox">
                        <input type="checkbox" name="gdpr_consent" required />
                        <span><?php printf(
                            __('Accetto i %s e la %s *', COLITALIA_PLUGIN_TEXTDOMAIN),
                            '<a href="/termini-condizioni" target="_blank">' . __('Termini e Condizioni', COLITALIA_PLUGIN_TEXTDOMAIN) . '</a>',
                            '<a href="/privacy-policy" target="_blank">' . __('Privacy Policy', COLITALIA_PLUGIN_TEXTDOMAIN) . '</a>'
                        ); ?></span>
                    </label>
                    
                    <label class="consent-checkbox">
                        <input type="checkbox" name="marketing_consent" />
                        <span><?php _e('Desidero ricevere offerte e comunicazioni commerciali', COLITALIA_PLUGIN_TEXTDOMAIN); ?></span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <?php _e('Registrati', COLITALIA_PLUGIN_TEXTDOMAIN); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render client profile shortcode
     */
    public function render_client_profile($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Devi effettuare il login per visualizzare il profilo.', COLITALIA_PLUGIN_TEXTDOMAIN) . '</p>';
        }
        
        $client = $this->get_client_by_user_id(get_current_user_id());
        if (!$client) {
            return '<p>' . __('Profilo cliente non trovato.', COLITALIA_PLUGIN_TEXTDOMAIN) . '</p>';
        }
        
        $stats = $this->get_client_statistics($client->id);
        
        ob_start();
        ?>
        <div class="colitalia-client-profile">
            <h3><?php _e('Il Mio Profilo', COLITALIA_PLUGIN_TEXTDOMAIN); ?></h3>
            
            <div class="profile-stats">
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo intval($stats->total_bookings); ?></span>
                        <span class="stat-label"><?php _e('Prenotazioni Totali', COLITALIA_PLUGIN_TEXTDOMAIN); ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-value"><?php echo colitalia_format_currency($stats->total_spent ?: 0); ?></span>
                        <span class="stat-label"><?php _e('Totale Speso', COLITALIA_PLUGIN_TEXTDOMAIN); ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-value"><?php echo intval($stats->completed_bookings); ?></span>
                        <span class="stat-label"><?php _e('Soggiorni Completati', COLITALIA_PLUGIN_TEXTDOMAIN); ?></span>
                    </div>
                </div>
            </div>
            
            <form id="client-profile-form" class="profile-form">
                <?php wp_nonce_field('colitalia_client_nonce', 'profile_nonce'); ?>
                
                <h4><?php _e('Informazioni Personali', COLITALIA_PLUGIN_TEXTDOMAIN); ?></h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name"><?php _e('Nome', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($client->first_name); ?>" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name"><?php _e('Cognome', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($client->last_name); ?>" required />
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email"><?php _e('Email', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                        <input type="email" id="email" name="email" value="<?php echo esc_attr($client->email); ?>" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><?php _e('Telefono', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($client->phone); ?>" />
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address"><?php _e('Indirizzo', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                    <textarea id="address" name="address" rows="3"><?php echo esc_textarea($client->address); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city"><?php _e('Città', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                        <input type="text" id="city" name="city" value="<?php echo esc_attr($client->city); ?>" />
                    </div>
                    
                    <div class="form-group">
                        <label for="country"><?php _e('Paese', COLITALIA_PLUGIN_TEXTDOMAIN); ?></label>
                        <input type="text" id="country" name="country" value="<?php echo esc_attr($client->country); ?>" />
                    </div>
                </div>
                
                <div class="marketing-preferences">
                    <h4><?php _e('Preferenze Comunicazioni', COLITALIA_PLUGIN_TEXTDOMAIN); ?></h4>
                    
                    <label class="consent-checkbox">
                        <input type="checkbox" name="marketing_consent" <?php checked(1, $client->marketing_consent); ?> />
                        <span><?php _e('Desidero ricevere offerte e comunicazioni commerciali', COLITALIA_PLUGIN_TEXTDOMAIN); ?></span>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php _e('Aggiorna Profilo', COLITALIA_PLUGIN_TEXTDOMAIN); ?>
                    </button>
                    
                    <button type="button" id="export-data" class="btn btn-secondary">
                        <?php _e('Esporta I Miei Dati', COLITALIA_PLUGIN_TEXTDOMAIN); ?>
                    </button>
                    
                    <button type="button" id="delete-account" class="btn btn-danger">
                        <?php _e('Elimina Account', COLITALIA_PLUGIN_TEXTDOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}