<?php
/**
 * Gestore PayPal per l'integrazione con API v2
 * 
 * @package Colitalia\RealEstate\Payment
 * @since 1.0.0
 */

namespace ColitaliaRealEstate\Payment;

defined('ABSPATH') || exit;

/**
 * Classe per la gestione delle operazioni PayPal
 */
class PayPalManager {
    
    /**
     * Client ID PayPal
     * @var string
     */
    private $client_id;
    
    /**
     * Secret PayPal
     * @var string
     */
    private $client_secret;
    
    /**
     * Modalità ambiente (sandbox|live)
     * @var string
     */
    private $mode;
    
    /**
     * URL base API PayPal
     * @var string
     */
    private $api_base_url;
    
    /**
     * Token di accesso PayPal
     * @var string
     */
    private $access_token;
    
    /**
     * ID Webhook PayPal
     * @var string
     */
    private $webhook_id;
    
    /**
     * Costruttore
     * 
     * @param string $client_id Client ID PayPal
     * @param string $client_secret Secret PayPal  
     * @param string $mode Modalità (sandbox|live)
     */
    public function __construct($client_id = '', $client_secret = '', $mode = 'sandbox') {
        // Se non vengono passati parametri, usa le opzioni salvate
        $this->client_id = $client_id ?: get_option('colitalia_paypal_client_id');
        $this->client_secret = $client_secret ?: get_option('colitalia_paypal_client_secret');
        $this->mode = $mode ?: get_option('colitalia_paypal_mode', 'sandbox');
        $this->webhook_id = get_option('colitalia_paypal_webhook_id');
        
        // Imposta URL base in base alla modalità
        $this->api_base_url = ($this->mode === 'live') 
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
            
        // Mantiene compatibilità con l'implementazione esistente
        $this->api_base = $this->api_base_url;
            
        // Inizializza e mantiene i hooks esistenti
        $this->init();
    }
    
    /**
     * Inizializza PayPal Manager
     */
    public function init() {
        add_action('wp_ajax_colitalia_create_paypal_order', array($this, 'ajax_create_order'));
        add_action('wp_ajax_nopriv_colitalia_create_paypal_order', array($this, 'ajax_create_order'));
        add_action('wp_ajax_colitalia_capture_paypal_order', array($this, 'ajax_capture_order'));
        add_action('wp_ajax_nopriv_colitalia_capture_paypal_order', array($this, 'ajax_capture_order'));
        
        // Webhook endpoint
        add_rewrite_rule(
            '^colitalia-paypal-webhook/?$',
            'index.php?colitalia_paypal_webhook=1',
            'top'
        );
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_webhook'));
        
        // Inizializza token di accesso
        $this->get_access_token();
    }
    
    /**
     * Ottiene il client HTTP configurato per le API PayPal
     * 
     * @return array Headers per le richieste HTTP
     */
    private function get_api_client() {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'PayPal-Request-Id' => uniqid('paypal_', true)
        ];
        
        // Aggiunge il token Bearer se disponibile  
        if (!empty($this->access_token)) {
            $headers['Authorization'] = 'Bearer ' . $this->access_token;
        }
        
        return $headers;
    }
    
    /**
     * Ottiene access token PayPal
     */
    private function get_access_token() {
        if (!$this->client_id || !$this->client_secret) {
            $this->log_error('PayPal credentials missing');
            return false;
        }
        
        // Check cache
        $cached_token = get_transient('colitalia_paypal_token_' . $this->mode);
        if ($cached_token) {
            $this->access_token = $cached_token;
            return $cached_token;
        }
        
        $auth_url = $this->api_base_url . '/v1/oauth2/token';
        
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
            $this->log_error('PayPal auth error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['access_token'])) {
            $this->access_token = $body['access_token'];
            set_transient('colitalia_paypal_token_' . $this->mode, $body['access_token'], 50 * MINUTE_IN_SECONDS);
            return $body['access_token'];
        }
        
        $this->log_error('PayPal auth failed: ' . wp_remote_retrieve_body($response));
        return false;
    }
    
    /**
     * Crea un ordine di pagamento PayPal (versione semplificata)
     * 
     * @param float $amount Importo del pagamento
     * @param string $currency Codice valuta (EUR, USD, etc.)
     * @param string $return_url URL di ritorno dopo approvazione
     * @param string $cancel_url URL di ritorno dopo annullamento
     * @param array $additional_data Dati aggiuntivi per l'ordine
     * @return array|false Dati dell'ordine creato o false in caso di errore
     */
    public function create_order($amount = null, $currency = 'EUR', $return_url = '', $cancel_url = '', $additional_data = []) {
        // Se $amount è un array, usa la logica complessa esistente
        if (is_array($amount)) {
            return $this->create_booking_order($amount);
        }
        
        // Versione semplificata per importi diretti
        try {
            // Verifica che il token sia disponibile
            if (empty($this->access_token)) {
                if (!$this->get_access_token()) {
                    return false;
                }
            }
            
            $url = $this->api_base_url . '/v2/checkout/orders';
            
            // Struttura dell'ordine PayPal v2
            $order_data = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', '')
                        ]
                    ]
                ],
                'application_context' => [
                    'brand_name' => get_bloginfo('name'),
                    'locale' => 'it-IT',
                    'landing_page' => 'BILLING',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $return_url,
                    'cancel_url' => $cancel_url
                ]
            ];
            
            // Aggiunge dati personalizzati se forniti
            if (!empty($additional_data['description'])) {
                $order_data['purchase_units'][0]['description'] = $additional_data['description'];
            }
            
            if (!empty($additional_data['custom_id'])) {
                $order_data['purchase_units'][0]['custom_id'] = $additional_data['custom_id'];
            }
            
            $response = wp_remote_post($url, [
                'headers' => $this->get_api_client(),
                'body' => json_encode($order_data),
                'timeout' => 30,
                'sslverify' => $this->mode === 'live'
            ]);
            
            if (is_wp_error($response)) {
                $this->log_error('Errore creazione ordine PayPal: ' . $response->get_error_message());
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($response_code === 201 && isset($data['id'])) {
                // Ordine creato con successo
                $this->log_info('Ordine PayPal creato: ' . $data['id']);
                return [
                    'success' => true,
                    'order_id' => $data['id'],
                    'approval_url' => $this->extract_approval_url($data['links'] ?? []),
                    'data' => $data
                ];
            }
            
            $this->log_error('Errore creazione ordine - Code: ' . $response_code . ', Body: ' . $body);
            return false;
            
        } catch (Exception $e) {
            $this->log_error('Eccezione create_order: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea ordine PayPal (versione complessa per prenotazioni)
     */
    private function create_booking_order($booking_data) {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return array('success' => false, 'error' => 'Errore autenticazione PayPal');
        }
        
        $booking_system = new \ColitaliaRealEstate\Core\BookingSystem();
        $pricing = $booking_system->calculate_dynamic_pricing(
            $booking_data['property_id'],
            $booking_data['check_in'],
            $booking_data['check_out'],
            $booking_data['guests']
        );
        
        if (isset($pricing['error'])) {
            return array('success' => false, 'error' => $pricing['error']);
        }
        
        $property = get_post($booking_data['property_id']);
        $currency = get_option('colitalia_currency', 'EUR');
        
        $order_payload = array(
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
                        'Prenotazione %s - %s/%s',
                        $property->post_title,
                        date('d/m/Y', strtotime($booking_data['check_in'])),
                        date('d/m/Y', strtotime($booking_data['check_out']))
                    ),
                    'custom_id' => 'COLITALIA_' . ($booking_data['booking_id'] ?? uniqid()),
                    'amount' => array(
                        'currency_code' => $currency,
                        'value' => number_format($pricing['deposit_amount'], 2, '.', ''),
                        'breakdown' => array(
                            'item_total' => array(
                                'currency_code' => $currency,
                                'value' => number_format($pricing['accommodation_total'], 2, '.', '')
                            ),
                            'tax_total' => array(
                                'currency_code' => $currency,
                                'value' => number_format($pricing['total_taxes'], 2, '.', '')
                            ),
                            'handling' => array(
                                'currency_code' => $currency,
                                'value' => number_format($pricing['service_fee'] + $pricing['cleaning_fee'], 2, '.', '')
                            )
                        )
                    ),
                    'items' => array(
                        array(
                            'name' => $property->post_title,
                            'description' => sprintf(
                                'Soggiorno %d notti per %d ospiti',
                                $pricing['nights'],
                                $booking_data['guests']
                            ),
                            'unit_amount' => array(
                                'currency_code' => $currency,
                                'value' => number_format($pricing['accommodation_total'], 2, '.', '')
                            ),
                            'quantity' => '1',
                            'category' => 'DIGITAL_GOODS'
                        )
                    )
                )
            )
        );
        
        $response = wp_remote_post($this->api_base . '/v2/checkout/orders', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
                'PayPal-Request-Id' => uniqid('colitalia_', true),
                'Prefer' => 'return=representation'
            ),
            'body' => json_encode($order_payload),
            'timeout' => 30,
            'sslverify' => $this->mode === 'live'
        ));
        
        if (is_wp_error($response)) {
            colitalia_log('PayPal order creation error: ' . $response->get_error_message(), 'error');
            return array('success' => false, 'error' => 'Errore connessione PayPal');
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code === 201 && isset($body['id'])) {
            // Salva order ID nel database
            if (isset($booking_data['booking_id'])) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'colitalia_bookings',
                    array(
                        'payment_transaction_id' => $body['id'],
                        'payment_method' => 'paypal'
                    ),
                    array('id' => $booking_data['booking_id']),
                    array('%s', '%s'),
                    array('%d')
                );
            }
            
            colitalia_log('PayPal order created: ' . $body['id']);
            
            return array(
                'success' => true,
                'order_id' => $body['id'],
                'approval_url' => $this->extract_approval_url($body['links'] ?? [])
            );
        }
        
        colitalia_log('PayPal order creation failed: ' . wp_remote_retrieve_body($response), 'error');
        return array('success' => false, 'error' => 'Creazione ordine PayPal fallita');
    }
    
    /**
     * Finalizza il pagamento dopo l'approvazione dell'utente
     * 
     * @param string $order_id ID dell'ordine PayPal da finalizzare
     * @return array|false Dati del pagamento finalizzato o false in caso di errore
     */
    public function capture_payment($order_id) {
        try {
            // Verifica che il token sia disponibile
            if (empty($this->access_token)) {
                if (!$this->get_access_token()) {
                    return false;
                }
            }
            
            $url = $this->api_base_url . '/v2/checkout/orders/' . $order_id . '/capture';
            
            $response = wp_remote_post($url, [
                'headers' => $this->get_api_client(),
                'body' => json_encode([]), // Body vuoto per capture
                'timeout' => 30,
                'sslverify' => $this->mode === 'live'
            ]);
            
            if (is_wp_error($response)) {
                $this->log_error('Errore finalizzazione pagamento PayPal: ' . $response->get_error_message());
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($response_code === 201 && isset($data['status']) && $data['status'] === 'COMPLETED') {
                $capture_id = $data['purchase_units'][0]['payments']['captures'][0]['id'] ?? $order_id;
                $amount = $data['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0;
                
                // Aggiorna stato prenotazione se esiste booking
                $this->update_booking_payment_status($order_id, 'paid', $capture_id, $amount);
                
                $this->log_info('Pagamento PayPal finalizzato: ' . $order_id);
                
                return [
                    'success' => true,
                    'capture_id' => $capture_id,
                    'amount' => $amount,
                    'data' => $data
                ];
            }
            
            $this->log_error('Errore finalizzazione pagamento - Code: ' . $response_code . ', Body: ' . $body);
            return false;
            
        } catch (Exception $e) {
            $this->log_error('Eccezione capture_payment: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cattura pagamento PayPal (metodo di compatibilità)  
     */
    /**
     * Cattura pagamento PayPal (metodo di compatibilità)  
     */
    public function capture_order($order_id) {
        // Usa il nuovo metodo
        $result = $this->capture_payment($order_id);
        
        // Mantiene la struttura di risposta dell'implementazione originale
        if ($result && $result['success']) {
            return array(
                'success' => true,
                'capture_id' => $result['capture_id'],
                'amount' => $result['amount']
            );
        }
        
        return array('success' => false, 'error' => 'Cattura pagamento fallita');
    }
    
    /**
     * Ottiene i dettagli di un ordine PayPal
     * 
     * @param string $order_id ID dell'ordine
     * @return array|false Dati dell'ordine o false in caso di errore
     */
    public function get_order($order_id) {
        try {
            if (empty($this->access_token)) {
                if (!$this->get_access_token()) {
                    return false;
                }
            }
            
            $url = $this->api_base_url . '/v2/checkout/orders/' . $order_id;
            
            $response = wp_remote_get($url, [
                'headers' => $this->get_api_client(),
                'timeout' => 30,
                'sslverify' => $this->mode === 'live'
            ]);
            
            if (is_wp_error($response)) {
                $this->log_error('Errore recupero ordine PayPal: ' . $response->get_error_message());
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($response_code === 200 && isset($data['id'])) {
                return $data;
            }
            
            $this->log_error('Errore recupero ordine - Code: ' . $response_code . ', Body: ' . $body);
            return false;
            
        } catch (Exception $e) {
            $this->log_error('Eccezione get_order: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se il servizio PayPal è configurato correttamente
     * 
     * @return bool True se configurato correttamente
     */
    public function is_configured() {
        return !empty($this->client_id) && !empty($this->client_secret);
    }
    
    /**
     * Ottiene la modalità corrente
     * 
     * @return string Modalità corrente (sandbox|live)
     */
    public function get_mode() {
        return $this->mode;
    }
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return array('success' => false, 'error' => 'Errore autenticazione PayPal');
        }
        
        $response = wp_remote_post($this->api_base . '/v2/checkout/orders/' . $order_id . '/capture', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
                'PayPal-Request-Id' => uniqid('capture_', true),
                'Prefer' => 'return=representation'
            ),
            'body' => '{}',
            'timeout' => 30,
            'sslverify' => $this->mode === 'live'
        ));
        
        if (is_wp_error($response)) {
            colitalia_log('PayPal capture error: ' . $response->get_error_message(), 'error');
            return array('success' => false, 'error' => 'Errore cattura pagamento');
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code === 201 && isset($body['status']) && $body['status'] === 'COMPLETED') {
            $capture_id = $body['purchase_units'][0]['payments']['captures'][0]['id'] ?? $order_id;
            $amount = $body['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0;
            
            // Aggiorna stato prenotazione
            $this->update_booking_payment_status($order_id, 'paid', $capture_id, $amount);
            
            colitalia_log('PayPal payment captured: ' . $order_id);
            
            return array(
                'success' => true,
                'capture_id' => $capture_id,
                'amount' => $amount
            );
        }
        
        colitalia_log('PayPal capture failed: ' . wp_remote_retrieve_body($response), 'error');
        return array('success' => false, 'error' => 'Cattura pagamento fallita');
    }
    
    /**
     * Aggiorna stato pagamento prenotazione
     */
    private function update_booking_payment_status($order_id, $status, $transaction_id = null, $amount = 0) {
        global $wpdb;
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colitalia_bookings WHERE payment_transaction_id = %s",
            $order_id
        ));
        
        if ($booking) {
            $booking_system = new \ColitaliaRealEstate\Core\BookingSystem();
            $result = $booking_system->update_booking_status($booking->id, $status, $transaction_id);
            
            if ($result && $status === 'paid') {
                do_action('colitalia_booking_paid', $booking->id, $booking, array(
                    'payment_method' => 'paypal',
                    'transaction_id' => $transaction_id,
                    'amount' => $amount
                ));
            }
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Estrae URL approvazione da links PayPal
     */
    private function extract_approval_url($links) {
        foreach ($links as $link) {
            if (isset($link['rel']) && $link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }
    
    /**
     * Aggiunge query vars per webhook
     */
    public function add_query_vars($vars) {
        $vars[] = 'colitalia_paypal_webhook';
        return $vars;
    }
    
    /**
     * Gestisce webhook PayPal
     */
    public function handle_webhook() {
        if (!get_query_var('colitalia_paypal_webhook')) {
            return;
        }
        
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        if (!$data) {
            http_response_code(400);
            exit('Invalid JSON');
        }
        
        // Verifica firma webhook (opzionale ma raccomandato)
        if (!$this->verify_webhook_signature($payload)) {
            colitalia_log('Invalid PayPal webhook signature', 'warning');
        }
        
        $event_type = $data['event_type'] ?? '';
        $resource = $data['resource'] ?? array();
        
        colitalia_log('PayPal webhook received: ' . $event_type);
        
        switch ($event_type) {
            case 'CHECKOUT.ORDER.APPROVED':
                $this->handle_order_approved($resource);
                break;
                
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->handle_payment_completed($resource);
                break;
                
            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.DECLINED':
                $this->handle_payment_failed($resource);
                break;
                
            case 'PAYMENT.CAPTURE.REFUNDED':
                $this->handle_payment_refunded($resource);
                break;
        }
        
        http_response_code(200);
        exit('OK');
    }
    
    /**
     * Verifica firma webhook
     */
    private function verify_webhook_signature($payload) {
        // Implementazione base - in produzione usare verifica completa PayPal
        $headers = getallheaders();
        $paypal_auth_algo = $headers['PAYPAL-AUTH-ALGO'] ?? '';
        $paypal_transmission_id = $headers['PAYPAL-TRANSMISSION-ID'] ?? '';
        $paypal_cert_id = $headers['PAYPAL-CERT-ID'] ?? '';
        $paypal_transmission_sig = $headers['PAYPAL-TRANSMISSION-SIG'] ?? '';
        $paypal_transmission_time = $headers['PAYPAL-TRANSMISSION-TIME'] ?? '';
        
        // Per ora ritorna true, implementare verifica completa in produzione
        return true;
    }
    
    /**
     * Gestisce evento ordine approvato
     */
    private function handle_order_approved($resource) {
        $order_id = $resource['id'] ?? '';
        if ($order_id) {
            colitalia_log('PayPal order approved: ' . $order_id);
            // Opzionalmente aggiorna stato a 'approved'
        }
    }
    
    /**
     * Gestisce evento pagamento completato
     */
    private function handle_payment_completed($resource) {
        $order_id = $resource['supplementary_data']['related_ids']['order_id'] ?? '';
        $capture_id = $resource['id'] ?? '';
        $amount = $resource['amount']['value'] ?? 0;
        
        if ($order_id) {
            $this->update_booking_payment_status($order_id, 'paid', $capture_id, $amount);
        }
    }
    
    /**
     * Gestisce evento pagamento fallito
     */
    private function handle_payment_failed($resource) {
        $order_id = $resource['supplementary_data']['related_ids']['order_id'] ?? '';
        if ($order_id) {
            $this->update_booking_payment_status($order_id, 'cancelled');
        }
    }
    
    /**
     * Gestisce evento rimborso
     */
    private function handle_payment_refunded($resource) {
        $refund_amount = $resource['amount']['value'] ?? 0;
        $order_id = $resource['supplementary_data']['related_ids']['order_id'] ?? '';
        
        if ($order_id) {
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}colitalia_bookings 
                 SET special_requests = CONCAT(COALESCE(special_requests, ''), '\nRimborso PayPal: € %f del %s')
                 WHERE payment_transaction_id = %s",
                $refund_amount,
                date_i18n('d/m/Y H:i'),
                $order_id
            ));
            
            colitalia_log("PayPal refund processed: {$order_id}, Amount: {$refund_amount}");
        }
    }
    
    /**
     * AJAX create order
     */
    public function ajax_create_order() {
        check_ajax_referer('colitalia_paypal_nonce', 'nonce');
        
        $booking_data = array(
            'property_id' => intval($_POST['property_id'] ?? 0),
            'booking_id' => intval($_POST['booking_id'] ?? 0),
            'check_in' => sanitize_text_field($_POST['check_in'] ?? ''),
            'check_out' => sanitize_text_field($_POST['check_out'] ?? ''),
            'guests' => intval($_POST['guests'] ?? 1)
        );
        
        $result = $this->create_order($booking_data);
        wp_send_json($result);
    }
    
    /**
     * AJAX capture order
     */
    public function ajax_capture_order() {
        check_ajax_referer('colitalia_paypal_nonce', 'nonce');
        
        $order_id = sanitize_text_field($_POST['order_id'] ?? '');
        $result = $this->capture_order($order_id);
        wp_send_json($result);
    }
    
    /**
     * Log degli errori
     * 
     * @param string $message Messaggio di errore
     */
    private function log_error($message) {
        if (function_exists('error_log')) {
            error_log('[PayPalManager Error] ' . $message);
        }
        
        // Mantiene compatibilità con funzione esistente se disponibile
        if (function_exists('colitalia_log')) {
            colitalia_log($message, 'error');
        }
        
        // Possibile integrazione con sistema di log del plugin
        do_action('colitalia_paypal_error', $message);
    }
    
    /**
     * Log delle informazioni
     * 
     * @param string $message Messaggio informativo
     */
    private function log_info($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[PayPalManager Info] ' . $message);
        }
        
        // Mantiene compatibilità con funzione esistente se disponibile
        if (function_exists('colitalia_log')) {
            colitalia_log($message);
        }
        
        // Possibile integrazione con sistema di log del plugin
        do_action('colitalia_paypal_info', $message);
    }
}
