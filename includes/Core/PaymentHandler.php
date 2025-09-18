<?php
namespace ColitaliaRealEstate\Core;

/**
 * Payment Handler Class
 * Handles PayPal integration, payment processing, and webhook management
 */
class PaymentHandler {
    
    private $paypal_mode;
    private $client_id;
    private $client_secret;
    private $api_base;
    
    public function __construct() {
        $this->paypal_mode = get_option('colitalia_paypal_mode', 'sandbox');
        $this->client_id = get_option('colitalia_paypal_client_id');
        $this->client_secret = get_option('colitalia_paypal_client_secret');
        $this->api_base = $this->paypal_mode === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_paypal_scripts'));
        add_action('wp_ajax_create_paypal_order', array($this, 'ajax_create_paypal_order'));
        add_action('wp_ajax_nopriv_create_paypal_order', array($this, 'ajax_create_paypal_order'));
        add_action('wp_ajax_capture_paypal_order', array($this, 'ajax_capture_paypal_order'));
        add_action('wp_ajax_nopriv_capture_paypal_order', array($this, 'ajax_capture_paypal_order'));
        add_action('wp_ajax_process_paypal_webhook', array($this, 'handle_paypal_webhook'));
        add_action('wp_ajax_nopriv_process_paypal_webhook', array($this, 'handle_paypal_webhook'));
    }
    
    /**
     * Initialize payment handler
     */
    public function init() {
        // Register webhook endpoint
        add_rewrite_rule(
            '^colitalia-paypal-webhook/?$',
            'index.php?colitalia_paypal_webhook=1',
            'top'
        );
        
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_webhook_endpoint'));
        
        // Add payment status tracking
        add_action('colitalia_payment_completed', array($this, 'handle_payment_completed'), 10, 3);
        add_action('colitalia_payment_failed', array($this, 'handle_payment_failed'), 10, 3);
        add_action('colitalia_payment_cancelled', array($this, 'handle_payment_cancelled'), 10, 3);
    }
    
    /**
     * Add query vars for webhook
     */
    public function add_query_vars($vars) {
        $vars[] = 'colitalia_paypal_webhook';
        return $vars;
    }
    
    /**
     * Handle webhook endpoint
     */
    public function handle_webhook_endpoint() {
        if (get_query_var('colitalia_paypal_webhook')) {
            $this->handle_paypal_webhook();
            exit;
        }
    }
    
    /**
     * Get PayPal access token
     */
    private function get_access_token() {
        if (!$this->client_id || !$this->client_secret) {
            return false;
        }
        
        // Check if we have a cached token
        $cached_token = get_transient('colitalia_paypal_access_token');
        if ($cached_token) {
            return $cached_token;
        }
        
        $auth_url = $this->api_base . '/v1/oauth2/token';
        
        $headers = array(
            'Accept' => 'application/json',
            'Accept-Language' => 'en_US',
            'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret)
        );
        
        $body = 'grant_type=client_credentials';
        
        $response = wp_remote_post($auth_url, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            colitalia_log('PayPal Auth Error: ' . $response->get_error_message(), 'error');
            return false;
        }
        
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if (isset($data['access_token'])) {
            // Cache token for 50 minutes (expires in 60)
            set_transient('colitalia_paypal_access_token', $data['access_token'], 50 * MINUTE_IN_SECONDS);
            return $data['access_token'];
        }
        
        colitalia_log('PayPal Auth Failed: ' . $response_body, 'error');
        return false;
    }
    
    /**
     * Create PayPal order
     */
    public function create_paypal_order($booking_data) {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return array('success' => false, 'error' => 'Errore autenticazione PayPal');
        }
        
        $create_order_url = $this->api_base . '/v2/checkout/orders';
        
        // Get property and booking details
        $property = get_post($booking_data['property_id']);
        $property_data = colitalia_get_property_data($booking_data['property_id']);
        
        // Calculate amounts
        $booking_system = new BookingSystem();
        $pricing = $booking_system->calculate_dynamic_pricing(
            $booking_data['property_id'],
            $booking_data['check_in'],
            $booking_data['check_out'],
            $booking_data['guests']
        );
        
        if (isset($pricing['error'])) {
            return array('success' => false, 'error' => $pricing['error']);
        }
        
        // PayPal order structure
        $order_data = array(
            'intent' => 'CAPTURE',
            'application_context' => array(
                'brand_name' => get_bloginfo('name'),
                'locale' => 'it-IT',
                'landing_page' => 'BILLING',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => home_url('/prenota/?paypal_success=1&booking_id=' . ($booking_data['booking_id'] ?? '')),
                'cancel_url' => home_url('/prenota/?paypal_cancel=1')
            ),
            'purchase_units' => array(
                array(
                    'reference_id' => 'BOOKING_' . ($booking_data['booking_id'] ?? uniqid()),
                    'description' => sprintf(
                        __('Prenotazione %s dal %s al %s', COLITALIA_PLUGIN_TEXTDOMAIN),
                        $property->post_title,
                        date_i18n('d/m/Y', strtotime($booking_data['check_in'])),
                        date_i18n('d/m/Y', strtotime($booking_data['check_out']))
                    ),
                    'custom_id' => 'BOOKING_' . ($booking_data['booking_id'] ?? ''),
                    'amount' => array(
                        'currency_code' => get_option('colitalia_currency', 'EUR'),
                        'value' => number_format($pricing['deposit_amount'], 2, '.', ''),
                        'breakdown' => array(
                            'item_total' => array(
                                'currency_code' => get_option('colitalia_currency', 'EUR'),
                                'value' => number_format($pricing['accommodation_total'], 2, '.', '')
                            ),
                            'tax_total' => array(
                                'currency_code' => get_option('colitalia_currency', 'EUR'),
                                'value' => number_format($pricing['total_taxes'], 2, '.', '')
                            ),
                            'handling' => array(
                                'currency_code' => get_option('colitalia_currency', 'EUR'),
                                'value' => number_format($pricing['service_fee'] + $pricing['cleaning_fee'], 2, '.', '')
                            )
                        )
                    ),
                    'items' => array(
                        array(
                            'name' => $property->post_title,
                            'description' => sprintf(
                                __('Soggiorno dal %s al %s per %d ospiti', COLITALIA_PLUGIN_TEXTDOMAIN),
                                date_i18n('d/m/Y', strtotime($booking_data['check_in'])),
                                date_i18n('d/m/Y', strtotime($booking_data['check_out'])),
                                $booking_data['guests']
                            ),
                            'unit_amount' => array(
                                'currency_code' => get_option('colitalia_currency', 'EUR'),
                                'value' => number_format($pricing['accommodation_total'], 2, '.', '')
                            ),
                            'quantity' => '1',
                            'category' => 'DIGITAL_GOODS'
                        )
                    )
                )
            )
        );
        
        // Add services if any
        if (!empty($booking_data['services']) && $pricing['services_total'] > 0) {
            $services_item = array(
                'name' => __('Servizi Aggiuntivi', COLITALIA_PLUGIN_TEXTDOMAIN),
                'description' => implode(', ', $booking_data['services']),
                'unit_amount' => array(
                    'currency_code' => get_option('colitalia_currency', 'EUR'),
                    'value' => number_format($pricing['services_total'], 2, '.', '')
                ),
                'quantity' => '1',
                'category' => 'DIGITAL_GOODS'
            );
            
            $order_data['purchase_units'][0]['items'][] = $services_item;
        }
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
            'PayPal-Request-Id' => uniqid('colitalia_', true),
            'Prefer' => 'return=representation'
        );
        
        $response = wp_remote_post($create_order_url, array(
            'headers' => $headers,
            'body' => json_encode($order_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            colitalia_log('PayPal Order Creation Error: ' . $response->get_error_message(), 'error');
            return array('success' => false, 'error' => 'Errore di connessione PayPal');
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $order_response = json_decode($response_body, true);
        
        if ($response_code === 201 && isset($order_response['id'])) {
            // Save order ID for tracking
            if (isset($booking_data['booking_id'])) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'colitalia_bookings',
                    array('payment_transaction_id' => $order_response['id']),
                    array('id' => $booking_data['booking_id']),
                    array('%s'),
                    array('%d')
                );
            }
            
            colitalia_log("PayPal order created: {$order_response['id']}");
            
            return array(
                'success' => true,
                'order_id' => $order_response['id'],
                'approval_url' => $this->get_approval_url($order_response['links'])
            );
        }
        
        colitalia_log('PayPal Order Creation Failed: ' . $response_body, 'error');
        return array('success' => false, 'error' => 'Errore creazione ordine PayPal');
    }
    
    /**
     * Get approval URL from PayPal links
     */
    private function get_approval_url($links) {
        foreach ($links as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }
    
    /**
     * Capture PayPal order
     */
    public function capture_paypal_order($order_id) {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return array('success' => false, 'error' => 'Errore autenticazione PayPal');
        }
        
        $capture_url = $this->api_base . '/v2/checkout/orders/' . $order_id . '/capture';
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
            'PayPal-Request-Id' => uniqid('capture_', true),
            'Prefer' => 'return=representation'
        );
        
        $response = wp_remote_post($capture_url, array(
            'headers' => $headers,
            'body' => '{}',
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            colitalia_log('PayPal Capture Error: ' . $response->get_error_message(), 'error');
            return array('success' => false, 'error' => 'Errore di connessione PayPal');
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $capture_response = json_decode($response_body, true);
        
        if ($response_code === 201 && isset($capture_response['status']) && $capture_response['status'] === 'COMPLETED') {
            colitalia_log("PayPal order captured: $order_id");
            
            // Update booking status
            $this->update_booking_payment_status($order_id, 'paid', $capture_response);
            
            return array(
                'success' => true,
                'transaction_id' => $capture_response['purchase_units'][0]['payments']['captures'][0]['id'] ?? $order_id,
                'amount' => $capture_response['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0
            );
        }
        
        colitalia_log('PayPal Capture Failed: ' . $response_body, 'error');
        return array('success' => false, 'error' => 'Errore cattura pagamento PayPal');
    }
    
    /**
     * Update booking payment status
     */
    private function update_booking_payment_status($paypal_order_id, $status, $payment_data = null) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'colitalia_bookings';
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $bookings_table WHERE payment_transaction_id = %s",
            $paypal_order_id
        ));
        
        if ($booking) {
            $booking_system = new BookingSystem();
            $result = $booking_system->update_booking_status($booking->id, $status, $paypal_order_id);
            
            if ($result) {
                // Trigger payment event
                do_action('colitalia_payment_completed', $booking->id, $booking, $payment_data);
            }
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Handle PayPal webhook
     */
    public function handle_paypal_webhook() {
        // Get raw POST data
        $webhook_payload = file_get_contents('php://input');
        $webhook_data = json_decode($webhook_payload, true);
        
        if (!$webhook_data) {
            http_response_code(400);
            exit('Invalid JSON');
        }
        
        // Verify webhook signature (optional but recommended)
        if (!$this->verify_webhook_signature($webhook_payload)) {
            http_response_code(401);
            exit('Invalid signature');
        }
        
        $event_type = $webhook_data['event_type'] ?? '';
        $resource = $webhook_data['resource'] ?? array();
        
        colitalia_log("PayPal Webhook received: $event_type");
        
        switch ($event_type) {
            case 'CHECKOUT.ORDER.APPROVED':
                $this->handle_order_approved($resource);
                break;
                
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->handle_payment_captured($resource);
                break;
                
            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.DECLINED':
                $this->handle_paypal_payment_failed($resource);
                break;
                
            case 'PAYMENT.CAPTURE.REFUNDED':
                $this->handle_payment_refunded($resource);
                break;
                
            default:
                colitalia_log("Unhandled PayPal webhook event: $event_type");
        }
        
        http_response_code(200);
        exit('OK');
    }
    
    /**
     * Verify webhook signature (basic implementation)
     */
    private function verify_webhook_signature($payload) {
        // In production, implement proper webhook signature verification
        // using PayPal's webhook signature verification
        return true;
    }
    
    /**
     * Handle order approved event
     */
    private function handle_order_approved($resource) {
        $order_id = $resource['id'] ?? '';
        if ($order_id) {
            // Optionally update booking status to 'approved'
            colitalia_log("PayPal order approved: $order_id");
        }
    }
    
    /**
     * Handle payment captured event
     */
    private function handle_payment_captured($resource) {
        $order_id = $resource['supplementary_data']['related_ids']['order_id'] ?? '';
        if ($order_id) {
            $this->update_booking_payment_status($order_id, 'paid', $resource);
        }
    }
    
    /**
     * Handle PayPal payment failed event
     */
    private function handle_paypal_payment_failed($resource) {
        $order_id = $resource['supplementary_data']['related_ids']['order_id'] ?? '';
        if ($order_id) {
            $this->update_booking_payment_status($order_id, 'cancelled', $resource);
            
            global $wpdb;
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}colitalia_bookings WHERE payment_transaction_id = %s",
                $order_id
            ));
            
            if ($booking) {
                do_action('colitalia_payment_failed', $booking->id, $booking, $resource);
            }
        }
    }
    
    /**
     * Handle payment refunded event
     */
    private function handle_payment_refunded($resource) {
        $order_id = $resource['supplementary_data']['related_ids']['order_id'] ?? '';
        $refund_amount = $resource['amount']['value'] ?? 0;
        
        if ($order_id) {
            colitalia_log("PayPal refund processed: $order_id, Amount: $refund_amount");
            
            // Update booking notes with refund information
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}colitalia_bookings 
                 SET special_requests = CONCAT(COALESCE(special_requests, ''), '\\nRimborso PayPal: € %f del %s')
                 WHERE payment_transaction_id = %s",
                $refund_amount,
                date_i18n('d/m/Y H:i'),
                $order_id
            ));
        }
    }
    
    /**
     * Enqueue PayPal scripts
     */
    public function enqueue_paypal_scripts() {
        if (is_page() && has_shortcode(get_post()->post_content, 'colitalia_booking_form')) {
            if ($this->client_id) {
                $paypal_script_url = 'https://www.paypal.com/sdk/js?client-id=' . $this->client_id . 
                                   '&currency=' . get_option('colitalia_currency', 'EUR') . 
                                   '&intent=capture&locale=it_IT';
                
                wp_enqueue_script('paypal-checkout', $paypal_script_url, array(), null, true);
                
                wp_enqueue_script(
                    'colitalia-paypal',
                    COLITALIA_PLUGIN_URL . 'assets/js/paypal-integration.js',
                    array('jquery', 'paypal-checkout'),
                    COLITALIA_PLUGIN_VERSION,
                    true
                );
                
                wp_localize_script('colitalia-paypal', 'colitalia_paypal', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('colitalia_paypal_nonce'),
                    'currency' => get_option('colitalia_currency', 'EUR'),
                    'mode' => $this->paypal_mode,
                    'success_url' => home_url('/prenota/?paypal_success=1'),
                    'cancel_url' => home_url('/prenota/?paypal_cancel=1')
                ));
            }
        }
    }
    
    /**
     * AJAX handler for creating PayPal order
     */
    public function ajax_create_paypal_order() {
        check_ajax_referer('colitalia_paypal_nonce', 'nonce');
        
        $booking_data = array(
            'property_id' => intval($_POST['property_id']),
            'booking_id' => intval($_POST['booking_id']),
            'check_in' => sanitize_text_field($_POST['check_in']),
            'check_out' => sanitize_text_field($_POST['check_out']),
            'guests' => intval($_POST['guests']),
            'services' => isset($_POST['services']) ? array_map('sanitize_text_field', $_POST['services']) : array()
        );
        
        $result = $this->create_paypal_order($booking_data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * AJAX handler for capturing PayPal order
     */
    public function ajax_capture_paypal_order() {
        check_ajax_referer('colitalia_paypal_nonce', 'nonce');
        
        $order_id = sanitize_text_field($_POST['order_id']);
        
        if (!$order_id) {
            wp_send_json_error('Order ID richiesto');
        }
        
        $result = $this->capture_paypal_order($order_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Handle payment completed event
     */
    public function handle_payment_completed($booking_id, $booking, $payment_data) {
        // Send payment confirmation email
        $email_automation = new EmailAutomation();
        $email_automation->send_payment_confirmation($booking_id);
        
        // Log successful payment
        $amount = $payment_data['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 'N/A';
        colitalia_log("Payment completed for booking $booking_id, Amount: $amount");
    }
    
    /**
     * Handle payment failed event
     */
    public function handle_payment_failed($booking_id, $booking, $payment_data) {
        // Send payment failure notification
        $email_automation = new EmailAutomation();
        $email_automation->send_payment_failure_notification($booking_id);
        
        colitalia_log("Payment failed for booking $booking_id", 'error');
    }
    
    /**
     * Handle payment cancelled event
     */
    public function handle_payment_cancelled($booking_id, $booking, $payment_data) {
        // Update booking status and send notification
        colitalia_log("Payment cancelled for booking $booking_id");
    }
    
    /**
     * Process refund through PayPal
     */
    public function process_refund($transaction_id, $refund_amount, $reason = '') {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return array('success' => false, 'error' => 'Errore autenticazione PayPal');
        }
        
        $refund_url = $this->api_base . '/v2/payments/captures/' . $transaction_id . '/refund';
        
        $refund_data = array(
            'amount' => array(
                'value' => number_format($refund_amount, 2, '.', ''),
                'currency_code' => get_option('colitalia_currency', 'EUR')
            ),
            'note_to_payer' => $reason ?: __('Rimborso Colitalia', COLITALIA_PLUGIN_TEXTDOMAIN)
        );
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
            'PayPal-Request-Id' => uniqid('refund_', true),
            'Prefer' => 'return=representation'
        );
        
        $response = wp_remote_post($refund_url, array(
            'headers' => $headers,
            'body' => json_encode($refund_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            colitalia_log('PayPal Refund Error: ' . $response->get_error_message(), 'error');
            return array('success' => false, 'error' => 'Errore di connessione PayPal');
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $refund_response = json_decode($response_body, true);
        
        if ($response_code === 201 && isset($refund_response['status']) && $refund_response['status'] === 'COMPLETED') {
            colitalia_log("PayPal refund processed: {$refund_response['id']}, Amount: $refund_amount");
            
            return array(
                'success' => true,
                'refund_id' => $refund_response['id'],
                'amount' => $refund_amount
            );
        }
        
        colitalia_log('PayPal Refund Failed: ' . $response_body, 'error');
        return array('success' => false, 'error' => 'Errore elaborazione rimborso');
    }
    
    /**
     * Get payment details from PayPal
     */
    public function get_payment_details($order_id) {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return false;
        }
        
        $order_url = $this->api_base . '/v2/checkout/orders/' . $order_id;
        
        $headers = array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        );
        
        $response = wp_remote_get($order_url, array(
            'headers' => $headers,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_body = wp_remote_retrieve_body($response);
        return json_decode($response_body, true);
    }
    
    /**
     * Validate PayPal configuration
     */
    public function validate_configuration() {
        if (!$this->client_id || !$this->client_secret) {
            return array(
                'valid' => false,
                'error' => 'Credenziali PayPal non configurate'
            );
        }
        
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return array(
                'valid' => false,
                'error' => 'Impossibile ottenere token di accesso PayPal'
            );
        }
        
        return array(
            'valid' => true,
            'mode' => $this->paypal_mode
        );
    }
}