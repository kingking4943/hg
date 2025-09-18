<?php
/**
 * Customer Manager Class per Colitalia Real Estate Manager
 * 
 * Gestisce i clienti, i loro dati e la privacy GDPR
 * 
 * @since 1.2.0
 * @package Colitalia_Real_Estate
 */

namespace Colitalia_Real_Estate\Booking;

defined('ABSPATH') || exit;

class CustomerManager {
    
    /**
     * Istanza singleton
     */
    private static $instance = null;
    
    /**
     * Tabelle database
     */
    private $table_customers;
    private $table_bookings;
    
    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        
        $this->table_customers = $wpdb->prefix . 'colitalia_customers';
        $this->table_bookings = $wpdb->prefix . 'colitalia_bookings';
        
        $this->init_hooks();
    }
    
    /**
     * Singleton instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inizializza hooks
     */
    private function init_hooks() {
        // AJAX hooks
        add_action('wp_ajax_colitalia_create_customer', [$this, 'ajax_create_customer']);
        add_action('wp_ajax_nopriv_colitalia_create_customer', [$this, 'ajax_create_customer']);
        add_action('wp_ajax_colitalia_search_customers', [$this, 'ajax_search_customers']);
        add_action('wp_ajax_colitalia_get_customer', [$this, 'ajax_get_customer']);
        add_action('wp_ajax_colitalia_update_customer', [$this, 'ajax_update_customer']);
        
        // Privacy hooks
        add_action('wp_ajax_colitalia_delete_customer_data', [$this, 'ajax_delete_customer_data']);
        add_action('wp_ajax_colitalia_export_customer_data', [$this, 'ajax_export_customer_data']);
        
        // Email verification
        add_action('wp_ajax_colitalia_verify_customer_email', [$this, 'ajax_verify_customer_email']);
        add_action('wp_ajax_nopriv_colitalia_verify_customer_email', [$this, 'ajax_verify_customer_email']);
        
        // Privacy policy hooks
        add_action('init', [$this, 'handle_privacy_policy_updates']);
    }
    
    /**
     * Crea un nuovo cliente
     */
    public function create_customer($data) {
        global $wpdb;
        
        try {
            // Validazione dati
            $validation = $this->validate_customer_data($data);
            if (is_wp_error($validation)) {
                return $validation;
            }
            
            // Controlla se email già esistente
            $existing = $this->get_customer_by_email($data['email']);
            if ($existing) {
                return new \WP_Error('email_exists', 'Email già registrata', ['customer_id' => $existing['id']]);
            }
            
            // Prepara dati cliente
            $customer_data = [
                'email' => sanitize_email($data['email']),
                'first_name' => sanitize_text_field($data['first_name']),
                'last_name' => sanitize_text_field($data['last_name']),
                'phone' => sanitize_text_field($data['phone'] ?? ''),
                'birth_date' => $data['birth_date'] ?? null,
                'nationality' => sanitize_text_field($data['nationality'] ?? 'IT'),
                'document_type' => sanitize_text_field($data['document_type'] ?? 'id_card'),
                'document_number' => sanitize_text_field($data['document_number'] ?? ''),
                'document_expiry' => $data['document_expiry'] ?? null,
                'address_line_1' => sanitize_text_field($data['address_line_1'] ?? ''),
                'address_line_2' => sanitize_text_field($data['address_line_2'] ?? ''),
                'city' => sanitize_text_field($data['city'] ?? ''),
                'state_province' => sanitize_text_field($data['state_province'] ?? ''),
                'postal_code' => sanitize_text_field($data['postal_code'] ?? ''),
                'country' => sanitize_text_field($data['country'] ?? 'IT'),
                'emergency_contact_name' => sanitize_text_field($data['emergency_contact_name'] ?? ''),
                'emergency_contact_phone' => sanitize_text_field($data['emergency_contact_phone'] ?? ''),
                'dietary_requirements' => sanitize_textarea_field($data['dietary_requirements'] ?? ''),
                'accessibility_needs' => sanitize_textarea_field($data['accessibility_needs'] ?? ''),
                'marketing_consent' => intval($data['marketing_consent'] ?? 0),
                'privacy_consent' => intval($data['privacy_consent'] ?? 1) // Obbligatorio
            ];
            
            // Inserisci cliente
            $result = $wpdb->insert($this->table_customers, $customer_data);
            
            if ($result === false) {
                return new \WP_Error('db_error', 'Errore durante la creazione del cliente');
            }
            
            $customer_id = $wpdb->insert_id;
            
            // Log creazione cliente
            $this->log_customer_action($customer_id, 'created', 'Cliente creato');
            
            // Invia email di benvenuto se consenso marketing
            if ($customer_data['marketing_consent']) {
                $this->send_welcome_email($customer_id);
            }
            
            return [
                'success' => true,
                'customer_id' => $customer_id,
                'message' => 'Cliente creato con successo'
            ];
            
        } catch (Exception $e) {
            return new \WP_Error('exception', $e->getMessage());
        }
    }
    
    /**
     * Ottiene un cliente per ID
     */
    public function get_customer($customer_id, $include_bookings = false) {
        global $wpdb;
        
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_customers} WHERE id = %d",
            $customer_id
        ), ARRAY_A);
        
        if (!$customer) {
            return null;
        }
        
        // Includi storico prenotazioni
        if ($include_bookings) {
            $bookings = $wpdb->get_results($wpdb->prepare(
                "SELECT b.*, p.post_title as property_name 
                 FROM {$this->table_bookings} b
                 LEFT JOIN {$wpdb->posts} p ON b.property_id = p.ID
                 WHERE b.customer_id = %d 
                 ORDER BY b.created_at DESC",
                $customer_id
            ), ARRAY_A);
            
            $customer['bookings'] = $bookings ?: [];
            $customer['total_bookings'] = count($customer['bookings']);
            $customer['total_spent'] = array_sum(array_column($customer['bookings'], 'total_price'));
        }
        
        return $customer;
    }
    
    /**
     * Ottiene cliente per email
     */
    public function get_customer_by_email($email) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_customers} WHERE email = %s",
            sanitize_email($email)
        ), ARRAY_A);
    }
    
    /**
     * Aggiorna cliente
     */
    public function update_customer($customer_id, $data) {
        global $wpdb;
        
        try {
            $customer = $this->get_customer($customer_id);
            if (!$customer) {
                return new \WP_Error('not_found', 'Cliente non trovato');
            }
            
            // Campi aggiornabili
            $allowed_fields = [
                'first_name', 'last_name', 'phone', 'birth_date', 'nationality',
                'document_type', 'document_number', 'document_expiry',
                'address_line_1', 'address_line_2', 'city', 'state_province', 
                'postal_code', 'country', 'emergency_contact_name', 'emergency_contact_phone',
                'dietary_requirements', 'accessibility_needs', 'marketing_consent'
            ];
            
            $update_data = [];
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $update_data[$field] = $this->sanitize_field($field, $data[$field]);
                }
            }
            
            if (empty($update_data)) {
                return new \WP_Error('no_data', 'Nessun dato da aggiornare');
            }
            
            $result = $wpdb->update(
                $this->table_customers,
                $update_data,
                ['id' => $customer_id]
            );
            
            if ($result === false) {
                return new \WP_Error('db_error', 'Errore durante l\'aggiornamento');
            }
            
            // Log aggiornamento
            $this->log_customer_action($customer_id, 'updated', 'Dati cliente aggiornati');
            
            return ['success' => true, 'message' => 'Cliente aggiornato'];
            
        } catch (Exception $e) {
            return new \WP_Error('exception', $e->getMessage());
        }
    }
    
    /**
     * Cerca clienti
     */
    public function search_customers($query, $limit = 20) {
        global $wpdb;
        
        $search_query = '%' . $wpdb->esc_like($query) . '%';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, email, first_name, last_name, phone, created_at
             FROM {$this->table_customers}
             WHERE email LIKE %s 
                OR first_name LIKE %s 
                OR last_name LIKE %s 
                OR phone LIKE %s
                OR CONCAT(first_name, ' ', last_name) LIKE %s
             ORDER BY created_at DESC
             LIMIT %d",
            $search_query, $search_query, $search_query, $search_query, $search_query, $limit
        ), ARRAY_A);
        
        return $results ?: [];
    }
    
    /**
     * Ottiene statistiche cliente
     */
    public function get_customer_stats($customer_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_bookings,
                SUM(total_price) as total_spent,
                AVG(total_price) as avg_booking_value,
                MIN(created_at) as first_booking,
                MAX(created_at) as last_booking,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
             FROM {$this->table_bookings}
             WHERE customer_id = %d",
            $customer_id
        ), ARRAY_A);
        
        // Calcola fedeltà cliente
        if ($stats['total_bookings'] > 0) {
            $stats['loyalty_score'] = $this->calculate_loyalty_score($stats);
            $stats['customer_tier'] = $this->get_customer_tier($stats['total_spent'], $stats['total_bookings']);
        }
        
        return $stats;
    }
    
    /**
     * Gestisce eliminazione dati GDPR
     */
    public function delete_customer_data($customer_id, $anonymize = true) {
        global $wpdb;
        
        try {
            if ($anonymize) {
                // Anonimizza invece di eliminare per mantenere integrità prenotazioni
                $anonymous_data = [
                    'email' => 'deleted_' . $customer_id . '@deleted.local',
                    'first_name' => 'Cliente',
                    'last_name' => 'Eliminato',
                    'phone' => '',
                    'birth_date' => null,
                    'document_number' => '',
                    'address_line_1' => '',
                    'address_line_2' => '',
                    'city' => '',
                    'state_province' => '',
                    'postal_code' => '',
                    'emergency_contact_name' => '',
                    'emergency_contact_phone' => '',
                    'dietary_requirements' => '',
                    'accessibility_needs' => '',
                    'marketing_consent' => 0
                ];
                
                $result = $wpdb->update(
                    $this->table_customers,
                    $anonymous_data,
                    ['id' => $customer_id]
                );
                
                $this->log_customer_action($customer_id, 'anonymized', 'Dati cliente anonimizzati per GDPR');
                
            } else {
                // Elimina completamente (solo se nessuna prenotazione attiva)
                $active_bookings = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_bookings} 
                     WHERE customer_id = %d AND status IN ('pending', 'confirmed', 'paid')",
                    $customer_id
                ));
                
                if ($active_bookings > 0) {
                    return new \WP_Error('active_bookings', 'Impossibile eliminare cliente con prenotazioni attive');
                }
                
                $result = $wpdb->delete($this->table_customers, ['id' => $customer_id]);
            }
            
            return $result !== false;
            
        } catch (Exception $e) {
            return new \WP_Error('exception', $e->getMessage());
        }
    }
    
    /**
     * Esporta dati cliente (GDPR)
     */
    public function export_customer_data($customer_id) {
        $customer = $this->get_customer($customer_id, true);
        if (!$customer) {
            return null;
        }
        
        // Rimuovi campi sensibili dal log
        unset($customer['id']);
        
        // Prepara export in formato JSON
        $export_data = [
            'exported_at' => current_time('c'),
            'customer_data' => $customer,
            'privacy_info' => [
                'data_controller' => get_bloginfo('name'),
                'contact_email' => get_option('admin_email'),
                'retention_policy' => 'I dati vengono conservati per 10 anni dalla data dell\'ultima prenotazione per finalità fiscali e legali.'
            ]
        ];
        
        return $export_data;
    }
    
    /**
     * Verifica email cliente
     */
    public function verify_customer_email($customer_id, $verification_code) {
        global $wpdb;
        
        $stored_code = get_transient('colitalia_email_verification_' . $customer_id);
        
        if ($stored_code && $stored_code === $verification_code) {
            // Marca email come verificata
            $wpdb->update(
                $this->table_customers,
                ['email_verified' => 1],
                ['id' => $customer_id]
            );
            
            delete_transient('colitalia_email_verification_' . $customer_id);
            
            $this->log_customer_action($customer_id, 'email_verified', 'Email verificata');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Invia codice verifica email
     */
    public function send_email_verification($customer_id) {
        $customer = $this->get_customer($customer_id);
        if (!$customer) {
            return false;
        }
        
        $verification_code = wp_generate_password(8, false);
        set_transient('colitalia_email_verification_' . $customer_id, $verification_code, 24 * HOUR_IN_SECONDS);
        
        $subject = 'Verifica la tua email - ' . get_bloginfo('name');
        $message = sprintf(
            "Ciao %s,\n\nPer completare la registrazione, inserisci questo codice di verifica:\n\n%s\n\nIl codice scadrà tra 24 ore.\n\nGrazie!",
            $customer['first_name'],
            $verification_code
        );
        
        return wp_mail($customer['email'], $subject, $message);
    }
    
    /**
     * Validazione dati cliente
     */
    private function validate_customer_data($data) {
        $required_fields = ['email', 'first_name', 'last_name'];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new \WP_Error('missing_field', "Campo obbligatorio mancante: $field");
            }
        }
        
        // Valida email
        if (!is_email($data['email'])) {
            return new \WP_Error('invalid_email', 'Formato email non valido');
        }
        
        // Valida telefono
        if (!empty($data['phone']) && !preg_match('/^[\+\d\s\-\(\)\.]{8,20}$/', $data['phone'])) {
            return new \WP_Error('invalid_phone', 'Formato telefono non valido');
        }
        
        // Valida date
        if (!empty($data['birth_date'])) {
            $birth_date = \DateTime::createFromFormat('Y-m-d', $data['birth_date']);
            if (!$birth_date || $birth_date->format('Y-m-d') !== $data['birth_date']) {
                return new \WP_Error('invalid_birth_date', 'Data di nascita non valida');
            }
            
            // Controlla età minima (18 anni)
            $today = new \DateTime();
            $age = $today->diff($birth_date)->y;
            if ($age < 18) {
                return new \WP_Error('underage', 'Devi avere almeno 18 anni');
            }
        }
        
        // Privacy consent obbligatorio
        if (empty($data['privacy_consent'])) {
            return new \WP_Error('privacy_consent_required', 'Il consenso al trattamento dei dati è obbligatorio');
        }
        
        return true;
    }
    
    /**
     * Sanitizza campo in base al tipo
     */
    private function sanitize_field($field, $value) {
        switch ($field) {
            case 'email':
                return sanitize_email($value);
            case 'dietary_requirements':
            case 'accessibility_needs':
                return sanitize_textarea_field($value);
            case 'marketing_consent':
                return intval($value);
            case 'birth_date':
            case 'document_expiry':
                return $value ? date('Y-m-d', strtotime($value)) : null;
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Calcola punteggio fedeltà
     */
    private function calculate_loyalty_score($stats) {
        $score = 0;
        
        // Punti per numero prenotazioni
        $score += $stats['total_bookings'] * 10;
        
        // Punti per valore speso
        $score += $stats['total_spent'] / 100;
        
        // Bonus per frequenza (prenotazioni completate vs cancellate)
        if ($stats['total_bookings'] > 0) {
            $completion_rate = $stats['completed_bookings'] / $stats['total_bookings'];
            $score += $completion_rate * 50;
        }
        
        // Bonus anzianità
        if ($stats['first_booking']) {
            $months_since_first = (strtotime('now') - strtotime($stats['first_booking'])) / (30 * 24 * 60 * 60);
            $score += $months_since_first * 2;
        }
        
        return round($score);
    }
    
    /**
     * Determina tier cliente
     */
    private function get_customer_tier($total_spent, $total_bookings) {
        if ($total_spent >= 5000 || $total_bookings >= 10) {
            return 'platinum';
        } elseif ($total_spent >= 2000 || $total_bookings >= 5) {
            return 'gold';
        } elseif ($total_spent >= 500 || $total_bookings >= 2) {
            return 'silver';
        } else {
            return 'bronze';
        }
    }
    
    /**
     * Log azione cliente
     */
    private function log_customer_action($customer_id, $action, $notes = '') {
        // Log in un file o database separato per audit
        error_log(sprintf(
            "[COLITALIA_CUSTOMER] Customer %d - Action: %s - Notes: %s - Time: %s",
            $customer_id,
            $action,
            $notes,
            current_time('mysql')
        ));
    }
    
    /**
     * Invia email di benvenuto
     */
    private function send_welcome_email($customer_id) {
        $customer = $this->get_customer($customer_id);
        if (!$customer) return;
        
        $subject = 'Benvenuto in ' . get_bloginfo('name');
        $message = sprintf(
            "Caro %s,\n\nBenvenuto nella famiglia Colitalia!\n\nGrazie per esserti registrato. Potrai ora prenotare le nostre splendide proprietà e ricevere offerte speciali riservate ai nostri clienti.\n\nBuone vacanze!\n\nIl team Colitalia",
            $customer['first_name']
        );
        
        wp_mail($customer['email'], $subject, $message);
    }
    
    /**
     * Gestisce aggiornamenti privacy policy
     */
    public function handle_privacy_policy_updates() {
        // Verifica se c'è stata modifica alla privacy policy
        $current_policy_date = get_option('colitalia_privacy_policy_date');
        $new_policy_date = get_option('colitalia_new_privacy_policy_date');
        
        if ($new_policy_date && $new_policy_date > $current_policy_date) {
            // Marca tutti i clienti come da riconsenso
            global $wpdb;
            $wpdb->update(
                $this->table_customers,
                ['privacy_consent_date' => null],
                ['marketing_consent' => 1]
            );
            
            update_option('colitalia_privacy_policy_date', $new_policy_date);
            delete_option('colitalia_new_privacy_policy_date');
        }
    }
    
    // AJAX Methods
    
    public function ajax_create_customer() {
        check_ajax_referer('colitalia_booking_nonce', 'nonce');
        
        $data = [
            'email' => sanitize_email($_POST['email']),
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'birth_date' => sanitize_text_field($_POST['birth_date'] ?? ''),
            'nationality' => sanitize_text_field($_POST['nationality'] ?? 'IT'),
            'document_type' => sanitize_text_field($_POST['document_type'] ?? 'id_card'),
            'document_number' => sanitize_text_field($_POST['document_number'] ?? ''),
            'marketing_consent' => intval($_POST['marketing_consent'] ?? 0),
            'privacy_consent' => intval($_POST['privacy_consent'] ?? 1)
        ];
        
        $result = $this->create_customer($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    public function ajax_search_customers() {
        check_ajax_referer('colitalia_booking_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        $query = sanitize_text_field($_GET['q'] ?? '');
        $limit = intval($_GET['limit'] ?? 20);
        
        $results = $this->search_customers($query, $limit);
        
        wp_send_json_success($results);
    }
    
    public function ajax_get_customer() {
        check_ajax_referer('colitalia_booking_nonce', 'nonce');
        
        $customer_id = intval($_GET['customer_id']);
        $include_bookings = !empty($_GET['include_bookings']);
        
        $customer = $this->get_customer($customer_id, $include_bookings);
        
        if ($customer) {
            wp_send_json_success($customer);
        } else {
            wp_send_json_error('Cliente non trovato');
        }
    }
    
    public function ajax_update_customer() {
        check_ajax_referer('colitalia_booking_nonce', 'nonce');
        
        $customer_id = intval($_POST['customer_id']);
        $data = $_POST;
        unset($data['customer_id'], $data['nonce'], $data['action']);
        
        $result = $this->update_customer($customer_id, $data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    public function ajax_delete_customer_data() {
        check_ajax_referer('colitalia_booking_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        $customer_id = intval($_POST['customer_id']);
        $anonymize = !empty($_POST['anonymize']);
        
        $result = $this->delete_customer_data($customer_id, $anonymize);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(['message' => 'Dati cliente eliminati']);
        }
    }
    
    public function ajax_export_customer_data() {
        check_ajax_referer('colitalia_booking_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        $customer_id = intval($_GET['customer_id']);
        $data = $this->export_customer_data($customer_id);
        
        if ($data) {
            wp_send_json_success($data);
        } else {
            wp_send_json_error('Cliente non trovato');
        }
    }
    
    public function ajax_verify_customer_email() {
        check_ajax_referer('colitalia_booking_nonce', 'nonce');
        
        $customer_id = intval($_POST['customer_id']);
        $verification_code = sanitize_text_field($_POST['verification_code']);
        
        $verified = $this->verify_customer_email($customer_id, $verification_code);
        
        if ($verified) {
            wp_send_json_success(['message' => 'Email verificata con successo']);
        } else {
            wp_send_json_error('Codice di verifica non valido o scaduto');
        }
    }
}
