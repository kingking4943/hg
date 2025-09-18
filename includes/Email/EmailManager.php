<?php
namespace ColitaliaRealEstate\Email;

/**
 * Email Manager Class
 * Gestisce sistema email automation con template HTML responsive
 */
class EmailManager {
    
    private $from_name;
    private $from_email;
    private $smtp_enabled;
    private $template_cache = array();
    
    public function __construct() {
        $this->from_name = get_option('colitalia_email_from_name', get_bloginfo('name'));
        $this->from_email = get_option('colitalia_email_from_email', get_option('admin_email'));
        $this->smtp_enabled = get_option('colitalia_smtp_enabled', false);
        
        $this->init();
    }
    
    /**
     * Inizializza Email Manager
     */
    public function init() {
        // Hook eventi booking
        add_action('colitalia_booking_created', array($this, 'send_booking_confirmation'), 10, 2);
        add_action('colitalia_booking_paid', array($this, 'send_payment_confirmation'), 10, 2);
        add_action('colitalia_booking_cancelled', array($this, 'send_cancellation_notification'), 10, 2);
        
        // Cron job per email promemoria
        add_action('colitalia_send_reminder_emails', array($this, 'send_arrival_reminders'));
        if (!wp_next_scheduled('colitalia_send_reminder_emails')) {
            wp_schedule_event(time(), 'daily', 'colitalia_send_reminder_emails');
        }
        
        // AJAX handlers
        add_action('wp_ajax_colitalia_test_email', array($this, 'ajax_test_email'));
        add_action('wp_ajax_colitalia_send_custom_email', array($this, 'ajax_send_custom_email'));
        
        // Configurazione SMTP
        if ($this->smtp_enabled) {
            add_action('phpmailer_init', array($this, 'configure_smtp'));
        }
        
        // Filter wp_mail
        add_filter('wp_mail_from', array($this, 'set_from_email'));
        add_filter('wp_mail_from_name', array($this, 'set_from_name'));
        add_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
    }
    
    /**
     * Invia email conferma prenotazione
     */
    public function send_booking_confirmation($booking_id, $booking) {
        if (!$booking) {
            $booking_system = new \ColitaliaRealEstate\Core\BookingSystem();
            $booking = $booking_system->get_booking($booking_id);
        }
        
        if (!$booking || !$booking->email) {
            colitalia_log('Cannot send booking confirmation: missing booking or email', 'error');
            return false;
        }
        
        $subject = sprintf(
            '[%s] Conferma Prenotazione #%s',
            get_bloginfo('name'),
            $booking->booking_code
        );
        
        $template_data = array(
            'booking' => $booking,
            'customer_name' => $booking->first_name . ' ' . $booking->last_name,
            'property_title' => $booking->property_title,
            'check_in' => date_i18n('d/m/Y', strtotime($booking->check_in)),
            'check_out' => date_i18n('d/m/Y', strtotime($booking->check_out)),
            'nights' => (new \DateTime($booking->check_in))->diff(new \DateTime($booking->check_out))->days,
            'guests' => $booking->guests,
            'total_price' => colitalia_format_currency($booking->total_price),
            'deposit_amount' => colitalia_format_currency($booking->deposit_amount),
            'booking_code' => $booking->booking_code,
            'property_location' => $booking->location,
            'special_requests' => $booking->special_requests,
            'payment_status' => $this->get_payment_status_text($booking->status),
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'booking_url' => home_url('/le-mie-prenotazioni/?booking=' . $booking->booking_code)
        );
        
        $content = $this->render_template('booking-confirmation', $template_data);
        
        $result = $this->send_email($booking->email, $subject, $content, $booking_id);
        
        if ($result) {
            $this->log_email($booking->email, $subject, 'booking_confirmation', 'sent', $booking_id, $booking->client_id);
        } else {
            $this->log_email($booking->email, $subject, 'booking_confirmation', 'failed', $booking_id, $booking->client_id);
        }
        
        return $result;
    }
    
    /**
     * Invia email conferma pagamento
     */
    public function send_payment_confirmation($booking_id, $booking, $payment_data = array()) {
        if (!$booking) {
            $booking_system = new \ColitaliaRealEstate\Core\BookingSystem();
            $booking = $booking_system->get_booking($booking_id);
        }
        
        if (!$booking || !$booking->email) {
            return false;
        }
        
        $subject = sprintf(
            '[%s] Pagamento Confermato - Prenotazione #%s',
            get_bloginfo('name'),
            $booking->booking_code
        );
        
        $template_data = array(
            'booking' => $booking,
            'customer_name' => $booking->first_name . ' ' . $booking->last_name,
            'property_title' => $booking->property_title,
            'check_in' => date_i18n('d/m/Y', strtotime($booking->check_in)),
            'check_out' => date_i18n('d/m/Y', strtotime($booking->check_out)),
            'nights' => (new \DateTime($booking->check_in))->diff(new \DateTime($booking->check_out))->days,
            'guests' => $booking->guests,
            'total_price' => colitalia_format_currency($booking->total_price),
            'deposit_amount' => colitalia_format_currency($booking->deposit_amount),
            'remaining_balance' => colitalia_format_currency($booking->total_price - $booking->deposit_amount),
            'booking_code' => $booking->booking_code,
            'payment_method' => ucfirst($booking->payment_method ?? 'PayPal'),
            'transaction_id' => $booking->payment_transaction_id,
            'payment_date' => date_i18n('d/m/Y H:i'),
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'booking_url' => home_url('/le-mie-prenotazioni/?booking=' . $booking->booking_code)
        );
        
        $content = $this->render_template('payment-confirmation', $template_data);
        
        $result = $this->send_email($booking->email, $subject, $content, $booking_id);
        
        if ($result) {
            $this->log_email($booking->email, $subject, 'payment_confirmation', 'sent', $booking_id, $booking->client_id);
        } else {
            $this->log_email($booking->email, $subject, 'payment_confirmation', 'failed', $booking_id, $booking->client_id);
        }
        
        return $result;
    }
    
    /**
     * Invia notifica cancellazione
     */
    public function send_cancellation_notification($booking_id, $booking) {
        if (!$booking) {
            $booking_system = new \ColitaliaRealEstate\Core\BookingSystem();
            $booking = $booking_system->get_booking($booking_id);
        }
        
        if (!$booking || !$booking->email) {
            return false;
        }
        
        $subject = sprintf(
            '[%s] Prenotazione Cancellata #%s',
            get_bloginfo('name'),
            $booking->booking_code
        );
        
        $template_data = array(
            'booking' => $booking,
            'customer_name' => $booking->first_name . ' ' . $booking->last_name,
            'property_title' => $booking->property_title,
            'check_in' => date_i18n('d/m/Y', strtotime($booking->check_in)),
            'check_out' => date_i18n('d/m/Y', strtotime($booking->check_out)),
            'booking_code' => $booking->booking_code,
            'cancellation_date' => date_i18n('d/m/Y H:i'),
            'refund_info' => $this->get_refund_info($booking),
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url()
        );
        
        $content = $this->render_template('booking-cancellation', $template_data);
        
        $result = $this->send_email($booking->email, $subject, $content, $booking_id);
        
        if ($result) {
            $this->log_email($booking->email, $subject, 'booking_cancellation', 'sent', $booking_id, $booking->client_id);
        }
        
        return $result;
    }
    
    /**
     * Invia promemoria pre-arrivo
     */
    public function send_arrival_reminders() {
        global $wpdb;
        
        $reminder_days = get_option('colitalia_arrival_reminder_days', 3);
        $target_date = date('Y-m-d', strtotime("+{$reminder_days} days"));
        
        $bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, c.first_name, c.last_name, c.email,
                    post.post_title as property_title, cp.location
             FROM {$wpdb->prefix}colitalia_bookings b
             LEFT JOIN {$wpdb->prefix}colitalia_clients c ON b.client_id = c.id
             LEFT JOIN {$wpdb->prefix}colitalia_properties cp ON b.property_id = cp.post_id
             LEFT JOIN {$wpdb->posts} post ON cp.post_id = post.ID
             WHERE b.status = 'paid'
             AND b.check_in = %s
             AND b.id NOT IN (
                 SELECT booking_id FROM {$wpdb->prefix}colitalia_email_logs 
                 WHERE email_type = 'arrival_reminder' 
                 AND booking_id IS NOT NULL 
                 AND DATE(sent_at) = CURDATE()
             )",
            $target_date
        ));
        
        $sent_count = 0;
        
        foreach ($bookings as $booking) {
            if ($this->send_arrival_reminder($booking)) {
                $sent_count++;
            }
        }
        
        if ($sent_count > 0) {
            colitalia_log("Sent {$sent_count} arrival reminder emails");
        }
    }
    
    /**
     * Invia singolo promemoria arrivo
     */
    private function send_arrival_reminder($booking) {
        if (!$booking->email) {
            return false;
        }
        
        $subject = sprintf(
            '[%s] Promemoria Arrivo - Prenotazione #%s',
            get_bloginfo('name'),
            $booking->booking_code
        );
        
        $template_data = array(
            'booking' => $booking,
            'customer_name' => $booking->first_name . ' ' . $booking->last_name,
            'property_title' => $booking->property_title,
            'check_in' => date_i18n('d/m/Y', strtotime($booking->check_in)),
            'check_out' => date_i18n('d/m/Y', strtotime($booking->check_out)),
            'booking_code' => $booking->booking_code,
            'property_location' => $booking->location,
            'checkin_instructions' => get_post_meta($booking->property_id, '_checkin_instructions', true),
            'contact_phone' => get_option('colitalia_contact_phone', ''),
            'emergency_contact' => get_option('colitalia_emergency_contact', ''),
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'booking_url' => home_url('/le-mie-prenotazioni/?booking=' . $booking->booking_code)
        );
        
        $content = $this->render_template('arrival-reminder', $template_data);
        
        $result = $this->send_email($booking->email, $subject, $content, $booking->id);
        
        if ($result) {
            $this->log_email($booking->email, $subject, 'arrival_reminder', 'sent', $booking->id, $booking->client_id);
        }
        
        return $result;
    }
    
    /**
     * Renderizza template email
     */
    public function render_template($template_name, $data = array()) {
        // Check cache
        $cache_key = $template_name . '_' . md5(serialize($data));
        if (isset($this->template_cache[$cache_key])) {
            return $this->template_cache[$cache_key];
        }
        
        $template_file = COLITALIA_PLUGIN_PATH . 'templates/emails/' . $template_name . '.php';
        
        if (!file_exists($template_file)) {
            colitalia_log('Email template not found: ' . $template_name, 'error');
            return $this->get_fallback_template($data);
        }
        
        // Extract data
        extract($data, EXTR_SKIP);
        
        // Start output buffering
        ob_start();
        include $template_file;
        $content = ob_get_clean();
        
        // Apply merge tags
        $content = $this->apply_merge_tags($content, $data);
        
        // Cache result
        $this->template_cache[$cache_key] = $content;
        
        return $content;
    }
    
    /**
     * Applica merge tags al contenuto
     */
    private function apply_merge_tags($content, $data) {
        $merge_tags = array(
            '{SITE_NAME}' => get_bloginfo('name'),
            '{SITE_URL}' => home_url(),
            '{CURRENT_YEAR}' => date('Y'),
            '{CURRENT_DATE}' => date_i18n('d/m/Y'),
            '{CONTACT_EMAIL}' => get_option('admin_email')
        );
        
        // Add data merge tags
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $merge_tags['{' . strtoupper($key) . '}'] = $value;
            }
        }
        
        return str_replace(array_keys($merge_tags), array_values($merge_tags), $content);
    }
    
    /**
     * Invia email
     */
    public function send_email($to, $subject, $content, $booking_id = null, $priority = 'normal') {
        // Queue system per invio massivo
        if ($priority === 'batch') {
            return $this->queue_email($to, $subject, $content, $booking_id);
        }
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>'
        );
        
        // Attachments (se necessario)
        $attachments = array();
        
        $result = wp_mail($to, $subject, $content, $headers, $attachments);
        
        if (!$result) {
            colitalia_log('Email send failed to: ' . $to . ', Subject: ' . $subject, 'error');
        }
        
        return $result;
    }
    
    /**
     * Aggiunge email alla coda
     */
    private function queue_email($to, $subject, $content, $booking_id = null) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'colitalia_email_queue',
            array(
                'recipient' => $to,
                'subject' => $subject,
                'content' => $content,
                'booking_id' => $booking_id,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s')
        );
    }
    
    /**
     * Log email inviata
     */
    private function log_email($recipient, $subject, $email_type, $status, $booking_id = null, $client_id = null) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'colitalia_email_logs',
            array(
                'recipient' => $recipient,
                'subject' => $subject,
                'email_type' => $email_type,
                'status' => $status,
                'booking_id' => $booking_id,
                'client_id' => $client_id,
                'sent_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d', '%s')
        );
    }
    
    /**
     * Configura SMTP
     */
    public function configure_smtp($phpmailer) {
        $smtp_host = get_option('colitalia_smtp_host');
        $smtp_port = get_option('colitalia_smtp_port', 587);
        $smtp_username = get_option('colitalia_smtp_username');
        $smtp_password = get_option('colitalia_smtp_password');
        $smtp_encryption = get_option('colitalia_smtp_encryption', 'tls');
        
        if ($smtp_host && $smtp_username && $smtp_password) {
            $phpmailer->isSMTP();
            $phpmailer->Host = $smtp_host;
            $phpmailer->SMTPAuth = true;
            $phpmailer->Port = $smtp_port;
            $phpmailer->Username = $smtp_username;
            $phpmailer->Password = $smtp_password;
            
            if ($smtp_encryption) {
                $phpmailer->SMTPSecure = $smtp_encryption;
            }
        }
    }
    
    /**
     * Set from email
     */
    public function set_from_email($from_email) {
        return $this->from_email;
    }
    
    /**
     * Set from name
     */
    public function set_from_name($from_name) {
        return $this->from_name;
    }
    
    /**
     * Set HTML content type
     */
    public function set_html_content_type() {
        return 'text/html';
    }
    
    /**
     * Template fallback
     */
    private function get_fallback_template($data) {
        return '<html><body><h2>Colitalia Real Estate</h2><p>Email template not found.</p></body></html>';
    }
    
    /**
     * Get payment status text
     */
    private function get_payment_status_text($status) {
        $statuses = array(
            'pending' => 'In attesa di pagamento',
            'confirmed' => 'Confermata',
            'paid' => 'Pagata',
            'cancelled' => 'Cancellata',
            'completed' => 'Completata'
        );
        
        return $statuses[$status] ?? ucfirst($status);
    }
    
    /**
     * Get refund info
     */
    private function get_refund_info($booking) {
        $check_in_date = new \DateTime($booking->check_in);
        $now = new \DateTime();
        $days_until_checkin = $now->diff($check_in_date)->days;
        
        if ($days_until_checkin >= 30) {
            return 'Rimborso completo entro 5-7 giorni lavorativi.';
        } elseif ($days_until_checkin >= 14) {
            return 'Rimborso del 75% entro 5-7 giorni lavorativi.';
        } elseif ($days_until_checkin >= 7) {
            return 'Rimborso del 50% entro 5-7 giorni lavorativi.';
        } else {
            return 'Nessun rimborso previsto per cancellazioni tardive.';
        }
    }
    
    /**
     * AJAX test email
     */
    public function ajax_test_email() {
        check_ajax_referer('colitalia_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        $email = sanitize_email($_POST['email'] ?? '');
        $template = sanitize_text_field($_POST['template'] ?? 'booking-confirmation');
        
        if (!$email) {
            wp_send_json_error('Email richiesta');
        }
        
        $test_data = array(
            'customer_name' => 'Mario Rossi',
            'property_title' => 'Villa Test',
            'check_in' => date_i18n('d/m/Y', strtotime('+30 days')),
            'check_out' => date_i18n('d/m/Y', strtotime('+37 days')),
            'nights' => 7,
            'guests' => 2,
            'total_price' => '€ 1.200,00',
            'deposit_amount' => '€ 360,00',
            'booking_code' => 'COL2025TEST123'
        );
        
        $subject = '[TEST] ' . get_bloginfo('name') . ' - Template Email';
        $content = $this->render_template($template, $test_data);
        
        $result = $this->send_email($email, $subject, $content);
        
        if ($result) {
            wp_send_json_success('Email di test inviata con successo');
        } else {
            wp_send_json_error('Errore invio email di test');
        }
    }
}
