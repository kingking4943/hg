<?php
namespace ColitaliaRealEstate\Webhooks;

/**
 * PayPal Webhook Handler Class
 * Gestisce webhook PayPal con signature verification e security compliance
 */
class PayPalWebhook {
    
    private $webhook_id;
    private $client_id;
    private $client_secret;
    private $mode;
    private $api_base;
    
    public function __construct() {
        $this->webhook_id = get_option('colitalia_paypal_webhook_id');
        $this->client_id = get_option('colitalia_paypal_client_id');
        $this->client_secret = get_option('colitalia_paypal_client_secret');
        $this->mode = get_option('colitalia_paypal_mode', 'sandbox');
        $this->api_base = $this->mode === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
        
        $this->init();
    }
    
    /**
     * Inizializza webhook handler
     */
    public function init() {
        // Registra endpoint webhook
        add_action('init', array($this, 'register_webhook_endpoint'));
        add_action('template_redirect', array($this, 'handle_webhook_request'));
        
        // Registra eventi webhook
        add_action('wp_ajax_nopriv_colitalia_paypal_webhook', array($this, 'process_webhook'));
        add_action('wp_ajax_colitalia_paypal_webhook', array($this, 'process_webhook'));
    }
    
    /**
     * Registra endpoint webhook
     */
    public function register_webhook_endpoint() {
        add_rewrite_rule(
            '^colitalia/webhook/paypal/?$',
            'index.php?colitalia_paypal_webhook=1',
            'top'
        );
        
        add_filter('query_vars', function($vars) {
            $vars[] = 'colitalia_paypal_webhook';
            return $vars;
        });
    }
    
    /**
     * Gestisce richieste webhook
     */
    public function handle_webhook_request() {
        if (get_query_var('colitalia_paypal_webhook')) {
            $this->process_webhook();
        }
    }
    
    /**
     * Processa webhook PayPal
     */
    public function process_webhook() {
        // Log richiesta
        $this->log_webhook_request();
        
        // Ottieni raw payload
        $raw_payload = file_get_contents('php://input');
        $headers = $this->get_request_headers();
        
        if (empty($raw_payload)) {
            $this->send_response(400, 'Empty payload');
            return;
        }
        
        // Decodifica JSON
        $webhook_data = json_decode($raw_payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->send_response(400, 'Invalid JSON');
            return;
        }
        
        // Verifica firma webhook
        if (!$this->verify_webhook_signature($raw_payload, $headers)) {
            colitalia_log('PayPal webhook signature verification failed', 'error');
            $this->send_response(401, 'Invalid signature');
            return;
        }
        
        // Processa evento
        $this->handle_webhook_event($webhook_data);
        
        // Invia risposta di successo
        $this->send_response(200, 'OK');
    }
    
    /**
     * Verifica firma webhook PayPal
     */
    private function verify_webhook_signature($payload, $headers) {
        if (!$this->webhook_id) {
            colitalia_log('PayPal webhook ID not configured', 'warning');
            return true; // Skip verification se non configurato
        }
        
        $required_headers = array(
            'PAYPAL-TRANSMISSION-ID',
            'PAYPAL-CERT-ID', 
            'PAYPAL-AUTH-ALGO',
            'PAYPAL-TRANSMISSION-SIG',
            'PAYPAL-TRANSMISSION-TIME'
        );
        
        // Verifica presenza headers richiesti
        foreach ($required_headers as $header) {
            if (empty($headers[$header])) {
                colitalia_log("Missing PayPal webhook header: $header", 'error');
                return false;
            }
        }
        
        // Costruisce richiesta di verifica
        $verification_data = array(
            'transmission_id' => $headers['PAYPAL-TRANSMISSION-ID'],
            'cert_id' => $headers['PAYPAL-CERT-ID'],
            'auth_algo' => $headers['PAYPAL-AUTH-ALGO'],
            'transmission_sig' => $headers['PAYPAL-TRANSMISSION-SIG'],
            'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'],
            'webhook_id' => $this->webhook_id,
            'webhook_event' => json_decode($payload, true)
        );
        
        return $this->call_paypal_verification_api($verification_data);
    }
    
    /**
     * Chiama API PayPal per verifica firma
     */
    private function call_paypal_verification_api($verification_data) {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            colitalia_log('Cannot get PayPal access token for webhook verification', 'error');
            return false;
        }
        
        $verify_url = $this->api_base . '/v1/notifications/verify-webhook-signature';
        
        $response = wp_remote_post($verify_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token
            ),
            'body' => json_encode($verification_data),
            'timeout' => 30,
            'sslverify' => $this->mode === 'live'
        ));
        
        if (is_wp_error($response)) {
            colitalia_log('PayPal webhook verification API error: ' . $response->get_error_message(), 'error');
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200 && isset($body['verification_status'])) {
            $verified = $body['verification_status'] === 'SUCCESS';
            if (!$verified) {
                colitalia_log('PayPal webhook signature verification failed: ' . json_encode($body), 'error');
            }
            return $verified;
        }
        
        colitalia_log('PayPal webhook verification API unexpected response: ' . wp_remote_retrieve_body($response), 'error');
        return false;
    }
    
    /**
     * Ottiene access token PayPal
     */
    private function get_access_token() {
        if (!$this->client_id || !$this->client_secret) {
            return false;
        }
        
        // Check cache
        $cached_token = get_transient('colitalia_paypal_webhook_token_' . $this->mode);
        if ($cached_token) {
            return $cached_token;
        }
        
        $auth_url = $this->api_base . '/v1/oauth2/token';
        
        $response = wp_remote_post($auth_url, array(
            'headers' => array(
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret)
            ),
            'body' => 'grant_type=client_credentials',
            'timeout' => 30,
            'sslverify' => $this->mode === 'live'
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['access_token'])) {
            set_transient('colitalia_paypal_webhook_token_' . $this->mode, $body['access_token'], 50 * MINUTE_IN_SECONDS);
            return $body['access_token'];
        }
        
        return false;
    }
    
    /**
     * Gestisce eventi webhook
     */
    private function handle_webhook_event($webhook_data) {
        $event_type = $webhook_data['event_type'] ?? '';
        $resource = $webhook_data['resource'] ?? array();
        $event_id = $webhook_data['id'] ?? uniqid();
        
        // Verifica eventi duplicati
        if ($this->is_duplicate_event($event_id)) {
            colitalia_log("Duplicate PayPal webhook event: $event_id", 'info');
            return;
        }
        
        // Log evento
        $this->log_webhook_event($event_type, $event_id, $resource);
        
        // Processa in base al tipo di evento
        switch ($event_type) {
            case 'CHECKOUT.ORDER.APPROVED':
                $this->handle_order_approved($resource);
                break;
                
            case 'CHECKOUT.ORDER.COMPLETED':
                $this->handle_order_completed($resource);
                break;
                
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->handle_payment_captured($resource);
                break;
                
            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.DECLINED':
            case 'PAYMENT.CAPTURE.FAILED':
                $this->handle_payment_failed($resource);
                break;
                
            case 'PAYMENT.CAPTURE.REFUNDED':
                $this->handle_payment_refunded($resource);
                break;
                
            case 'PAYMENT.CAPTURE.REVERSED':
                $this->handle_payment_reversed($resource);
                break;
                
            case 'CHECKOUT.ORDER.VOIDED':
                $this->handle_order_voided($resource);
                break;
                
            default:
                colitalia_log("Unhandled PayPal webhook event: $event_type", 'info');
                break;
        }
        
        // Marca evento come processato
        $this->mark_event_processed($event_id);
    }
    
    /**
     * Gestisce ordine approvato
     */
    private function handle_order_approved($resource) {
        $order_id = $resource['id'] ?? '';
        if (!$order_id) return;
        
        colitalia_log("PayPal order approved: $order_id");
        
        // Aggiorna stato booking se esiste
        $this->update_booking_status($order_id, 'confirmed');
        
        // Trigger custom action
        do_action('colitalia_paypal_order_approved', $order_id, $resource);
    }
    
    /**
     * Gestisce ordine completato
     */
    private function handle_order_completed($resource) {
        $order_id = $resource['id'] ?? '';
        if (!$order_id) return;
        
        colitalia_log("PayPal order completed: $order_id");
        
        $this->update_booking_status($order_id, 'paid');
        
        do_action('colitalia_paypal_order_completed', $order_id, $resource);
    }
    
    /**
     * Gestisce pagamento catturato
     */
    private function handle_payment_captured($resource) {
        $capture_id = $resource['id'] ?? '';
        $order_id = $this->extract_order_id_from_resource($resource);
        $amount = $resource['amount']['value'] ?? 0;
        
        if (!$order_id) return;
        
        colitalia_log("PayPal payment captured: $capture_id for order: $order_id, amount: $amount");
        
        $this->update_booking_status($order_id, 'paid', $capture_id);
        
        // Trigger evento pagamento completato
        do_action('colitalia_paypal_payment_captured', $order_id, $capture_id, $amount, $resource);
    }
    
    /**
     * Gestisce pagamento fallito
     */
    private function handle_payment_failed($resource) {
        $order_id = $this->extract_order_id_from_resource($resource);
        $reason = $resource['reason_code'] ?? 'Unknown';
        
        if (!$order_id) return;
        
        colitalia_log("PayPal payment failed for order: $order_id, reason: $reason", 'error');
        
        $this->update_booking_status($order_id, 'cancelled');
        
        do_action('colitalia_paypal_payment_failed', $order_id, $reason, $resource);
    }
    
    /**
     * Gestisce rimborso
     */
    private function handle_payment_refunded($resource) {
        $refund_id = $resource['id'] ?? '';
        $refund_amount = $resource['amount']['value'] ?? 0;
        $order_id = $this->extract_order_id_from_resource($resource);
        
        if (!$order_id) return;
        
        colitalia_log("PayPal refund processed: $refund_id for order: $order_id, amount: $refund_amount");
        
        // Aggiorna note prenotazione
        $this->add_refund_note($order_id, $refund_amount, $refund_id);
        
        do_action('colitalia_paypal_payment_refunded', $order_id, $refund_id, $refund_amount, $resource);
    }
    
    /**
     * Gestisce storno pagamento
     */
    private function handle_payment_reversed($resource) {
        $order_id = $this->extract_order_id_from_resource($resource);
        $reason = $resource['reason_code'] ?? 'Chargeback';
        
        if (!$order_id) return;
        
        colitalia_log("PayPal payment reversed for order: $order_id, reason: $reason", 'warning');
        
        $this->update_booking_status($order_id, 'cancelled');
        $this->add_booking_note($order_id, "Pagamento stornato - Motivo: $reason");
        
        do_action('colitalia_paypal_payment_reversed', $order_id, $reason, $resource);
    }
    
    /**
     * Gestisce ordine annullato
     */
    private function handle_order_voided($resource) {
        $order_id = $resource['id'] ?? '';
        if (!$order_id) return;
        
        colitalia_log("PayPal order voided: $order_id");
        
        $this->update_booking_status($order_id, 'cancelled');
        
        do_action('colitalia_paypal_order_voided', $order_id, $resource);
    }
    
    /**
     * Aggiorna stato booking
     */
    private function update_booking_status($paypal_order_id, $status, $transaction_id = null) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colitalia_bookings WHERE payment_transaction_id = %s",
            $paypal_order_id
        ));
        
        if (!$booking) {
            colitalia_log("No booking found for PayPal order: $paypal_order_id", 'warning');
            return false;
        }
        
        $booking_system = new \ColitaliaRealEstate\Core\BookingSystem();
        
        $update_data = array('status' => $status);
        if ($transaction_id) {
            $update_data['payment_transaction_id'] = $transaction_id;
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'colitalia_bookings',
            $update_data,
            array('id' => $booking->id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Trigger status change events
            do_action('colitalia_booking_status_changed', $booking->id, $status, $booking);
            if ($status === 'paid') {
                do_action('colitalia_booking_paid', $booking->id, $booking);
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Aggiunge nota rimborso
     */
    private function add_refund_note($order_id, $amount, $refund_id) {
        global $wpdb;
        
        $refund_note = sprintf(
            "Rimborso PayPal: €%s (ID: %s) del %s",
            number_format($amount, 2),
            $refund_id,
            date_i18n('d/m/Y H:i')
        );
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}colitalia_bookings 
             SET special_requests = CONCAT(COALESCE(special_requests, ''), '\n\n', %s)
             WHERE payment_transaction_id = %s",
            $refund_note,
            $order_id
        ));
    }
    
    /**
     * Aggiunge nota booking
     */
    private function add_booking_note($order_id, $note) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}colitalia_bookings 
             SET special_requests = CONCAT(COALESCE(special_requests, ''), '\n\n', %s)
             WHERE payment_transaction_id = %s",
            $note . ' - ' . date_i18n('d/m/Y H:i'),
            $order_id
        ));
    }
    
    /**
     * Estrae Order ID da resource
     */
    private function extract_order_id_from_resource($resource) {
        // Vari modi per estrarre order ID
        if (isset($resource['supplementary_data']['related_ids']['order_id'])) {
            return $resource['supplementary_data']['related_ids']['order_id'];
        }
        
        if (isset($resource['custom_id']) && strpos($resource['custom_id'], 'COLITALIA_') === 0) {
            // Estrae da custom_id se disponibile
            return $resource['custom_id'];
        }
        
        return null;
    }
    
    /**
     * Verifica evento duplicato
     */
    private function is_duplicate_event($event_id) {
        global $wpdb;
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}options 
             WHERE option_name = %s",
            'colitalia_webhook_processed_' . $event_id
        ));
        
        return $exists > 0;
    }
    
    /**
     * Marca evento come processato
     */
    private function mark_event_processed($event_id) {
        // Salva per 7 giorni per evitare duplicati
        set_transient('colitalia_webhook_processed_' . $event_id, time(), 7 * DAY_IN_SECONDS);
    }
    
    /**
     * Log richiesta webhook
     */
    private function log_webhook_request() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $headers = $this->get_request_headers();
            $payload = file_get_contents('php://input');
            
            colitalia_log('PayPal Webhook Request - Headers: ' . json_encode($headers));
            colitalia_log('PayPal Webhook Request - Payload: ' . substr($payload, 0, 500));
        }
    }
    
    /**
     * Log evento webhook
     */
    private function log_webhook_event($event_type, $event_id, $resource) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'colitalia_webhook_logs',
            array(
                'event_type' => $event_type,
                'event_id' => $event_id,
                'resource_data' => json_encode($resource),
                'processed_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Ottiene headers richiesta
     */
    private function get_request_headers() {
        $headers = array();
        
        if (function_exists('getallheaders')) {
            $all_headers = getallheaders();
            foreach ($all_headers as $key => $value) {
                $headers[strtoupper(str_replace('-', '_', $key))] = $value;
            }
        } else {
            // Fallback per server che non supportano getallheaders
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $header_key = str_replace('HTTP_', '', $key);
                    $headers[$header_key] = $value;
                }
            }
        }
        
        return $headers;
    }
    
    /**
     * Invia risposta HTTP
     */
    private function send_response($status_code, $message) {
        http_response_code($status_code);
        header('Content-Type: application/json');
        
        echo json_encode(array(
            'status' => $status_code,
            'message' => $message,
            'timestamp' => time()
        ));
        
        exit;
    }
}
