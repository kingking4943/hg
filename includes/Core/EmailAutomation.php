<?php
namespace ColitaliaRealEstate\Core;

/**
 * Email Automation Class
 * Handles automated email communications, templates, and SMTP integration
 */
class EmailAutomation {
    
    private $smtp_enabled;
    private $from_name;
    private $from_email;
    
    public function __construct() {
        try {
            $this->smtp_enabled = get_option('colitalia_smtp_enabled', false);
            $this->from_name = get_option('colitalia_email_from_name', get_bloginfo('name'));
            $this->from_email = get_option('colitalia_email_from_address', get_option('admin_email'));
            
            add_action('init', array($this, 'init'));
            add_action('phpmailer_init', array($this, 'configure_smtp'));
            add_filter('wp_mail_from', array($this, 'set_from_email'));
            add_filter('wp_mail_from_name', array($this, 'set_from_name'));
            
            // Email template hooks
            add_action('colitalia_client_registered', array($this, 'send_welcome_email'), 10, 2);
            add_action('colitalia_booking_created', array($this, 'send_booking_confirmation'), 10, 1);
            add_action('colitalia_payment_completed', array($this, 'send_payment_confirmation'), 10, 1);
            add_action('colitalia_booking_cancelled', array($this, 'send_cancellation_notification'), 10, 1);
            
            // Scheduled email campaigns
            add_action('colitalia_send_newsletter', array($this, 'send_newsletter_campaign'));
            add_action('colitalia_send_booking_reminders', array($this, 'send_booking_reminders'));
            
            // Schedule events with error handling
            if (!wp_next_scheduled('colitalia_send_booking_reminders')) {
                $scheduled = wp_schedule_event(time(), 'daily', 'colitalia_send_booking_reminders');
                if ($scheduled === false) {
                    ColitaliaLogger::warning('Failed to schedule booking reminders cron event');
                }
            }
        } catch (Exception $e) {
            ColitaliaLogger::error('EmailAutomation constructor error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            // Re-throw to be handled by parent error handler, but logged first
            // throw $e; // Commentato per evitare il fatal error
        }
    }
    
    /**
     * Initialize email automation
     */
    public function init() {
        // Create email templates directory if not exists
        $templates_dir = COLITALIA_PLUGIN_PATH . 'templates/emails/';
        if (!file_exists($templates_dir)) {
            wp_mkdir_p($templates_dir);
        }
        
        // Load email queue processing
        add_action('wp_ajax_process_email_queue', array($this, 'process_email_queue'));
        add_action('wp_ajax_nopriv_process_email_queue', array($this, 'process_email_queue'));
    }
    
    /**
     * Configure SMTP if enabled
     */
    public function configure_smtp($phpmailer) {
        if (!$this->smtp_enabled) {
            return;
        }
        
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
            
            if ($smtp_encryption === 'ssl') {
                $phpmailer->SMTPSecure = 'ssl';
            } elseif ($smtp_encryption === 'tls') {
                $phpmailer->SMTPSecure = 'tls';
            }
            
            // Enable debug if in development
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $phpmailer->SMTPDebug = 1;
            }
        }
    }
    
    /**
     * Set from email address
     */
    public function set_from_email($email) {
        return $this->from_email;
    }
    
    /**
     * Set from name
     */
    public function set_from_name($name) {
        return $this->from_name;
    }
    
    /**
     * Load email template
     */
    public function load_email_template($template_name, $variables = array()) {
        $template_path = COLITALIA_PLUGIN_PATH . "templates/emails/{$template_name}.php";
        
        // Fallback to default template if custom doesn't exist
        if (!file_exists($template_path)) {
            $template_path = COLITALIA_PLUGIN_PATH . "templates/emails/default-{$template_name}.php";
        }
        
        if (!file_exists($template_path)) {
            return $this->get_default_template($template_name, $variables);
        }
        
        // Extract variables for template
        extract($variables);
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Get default email template
     */
    private function get_default_template($template_name, $variables = array()) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        
        $header = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$site_name}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2c5aa0; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; }
                .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
                .button { display: inline-block; background: #2c5aa0; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                .booking-details { background: white; padding: 20px; border-left: 4px solid #2c5aa0; margin: 20px 0; }
                .price { font-size: 24px; font-weight: bold; color: #2c5aa0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>{$site_name}</h1>
            </div>
            <div class='content'>
        ";
        
        $footer = "
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " {$site_name}. Tutti i diritti riservati.</p>
                <p><a href='{$site_url}' style='color: white;'>{$site_url}</a></p>
            </div>
        </body>
        </html>
        ";
        
        switch ($template_name) {
            case 'welcome':
                return $header . "
                <h2>Benvenuto in Colitalia!</h2>
                <p>Gentile {$variables['client_name']},</p>
                <p>Grazie per esserti registrato su Colitalia. Siamo entusiasti di averti come cliente!</p>
                <p>Con il tuo account potrai:</p>
                <ul>
                    <li>Prenotare le nostre meravigliose proprietà</li>
                    <li>Gestire le tue prenotazioni</li>
                    <li>Ricevere offerte esclusive</li>
                    <li>Esplorare opportunità di investimento</li>
                </ul>
                <p><a href='{$variables['properties_url']}' class='button'>Esplora le Nostre Proprietà</a></p>
                <p>Se hai domande, non esitare a contattarci.</p>
                <p>Cordiali saluti,<br>Il Team Colitalia</p>
                " . $footer;
                
            case 'booking-confirmation':
                return $header . "
                <h2>Conferma Prenotazione</h2>
                <p>Gentile {$variables['client_name']},</p>
                <p>La tua prenotazione è stata ricevuta con successo!</p>
                
                <div class='booking-details'>
                    <h3>Dettagli Prenotazione</h3>
                    <p><strong>Codice Prenotazione:</strong> {$variables['booking_code']}</p>
                    <p><strong>Proprietà:</strong> {$variables['property_title']}</p>
                    <p><strong>Check-in:</strong> {$variables['check_in']}</p>
                    <p><strong>Check-out:</strong> {$variables['check_out']}</p>
                    <p><strong>Ospiti:</strong> {$variables['guests']}</p>
                    <p><strong>Totale:</strong> <span class='price'>{$variables['total_price']}</span></p>
                </div>
                
                <p>Per completare la prenotazione, è necessario effettuare il pagamento della caparra.</p>
                <p><a href='{$variables['payment_url']}' class='button'>Completa il Pagamento</a></p>
                
                <p>Grazie per aver scelto Colitalia!</p>
                <p>Cordiali saluti,<br>Il Team Colitalia</p>
                " . $footer;
                
            case 'payment-confirmation':
                return $header . "
                <h2>Pagamento Confermato</h2>
                <p>Gentile {$variables['client_name']},</p>
                <p>Il pagamento per la tua prenotazione è stato elaborato con successo!</p>
                
                <div class='booking-details'>
                    <h3>Dettagli Pagamento</h3>
                    <p><strong>Codice Prenotazione:</strong> {$variables['booking_code']}</p>
                    <p><strong>Proprietà:</strong> {$variables['property_title']}</p>
                    <p><strong>Importo Pagato:</strong> <span class='price'>{$variables['amount_paid']}</span></p>
                    <p><strong>Metodo di Pagamento:</strong> {$variables['payment_method']}</p>
                </div>
                
                <p>La tua prenotazione è ora confermata. Riceverai ulteriori informazioni prima del tuo arrivo.</p>
                <p><a href='{$variables['booking_details_url']}' class='button'>Visualizza Prenotazione</a></p>
                
                <p>Non vediamo l'ora di ospitarti!</p>
                <p>Cordiali saluti,<br>Il Team Colitalia</p>
                " . $footer;
                
            case 'booking-reminder':
                return $header . "
                <h2>Promemoria Soggiorno</h2>
                <p>Gentile {$variables['client_name']},</p>
                <p>Il tuo soggiorno si avvicina! Ecco i dettagli importanti:</p>
                
                <div class='booking-details'>
                    <h3>Dettagli Soggiorno</h3>
                    <p><strong>Codice Prenotazione:</strong> {$variables['booking_code']}</p>
                    <p><strong>Proprietà:</strong> {$variables['property_title']}</p>
                    <p><strong>Check-in:</strong> {$variables['check_in']} (ore 15:00)</p>
                    <p><strong>Check-out:</strong> {$variables['check_out']} (ore 11:00)</p>
                    <p><strong>Indirizzo:</strong> {$variables['property_address']}</p>
                </div>
                
                <h3>Informazioni Utili</h3>
                <ul>
                    <li>Porta un documento d'identità valido</li>
                    <li>Il check-in è dalle 15:00 alle 20:00</li>
                    <li>Per check-in tardivi, contattaci in anticipo</li>
                </ul>
                
                <p>Per qualsiasi domanda, contattaci al: {$variables['contact_phone']}</p>
                
                <p>Buon viaggio!</p>
                <p>Cordiali saluti,<br>Il Team Colitalia</p>
                " . $footer;
                
            case 'cancellation':
                return $header . "
                <h2>Conferma Cancellazione</h2>
                <p>Gentile {$variables['client_name']},</p>
                <p>La tua prenotazione è stata cancellata come richiesto.</p>
                
                <div class='booking-details'>
                    <h3>Dettagli Cancellazione</h3>
                    <p><strong>Codice Prenotazione:</strong> {$variables['booking_code']}</p>
                    <p><strong>Proprietà:</strong> {$variables['property_title']}</p>
                    <p><strong>Importo Rimborsato:</strong> <span class='price'>{$variables['refund_amount']}</span></p>
                </div>
                
                <p>Il rimborso verrà elaborato entro 5-7 giorni lavorativi.</p>
                <p>Speriamo di poterti ospitare in futuro!</p>
                
                <p>Cordiali saluti,<br>Il Team Colitalia</p>
                " . $footer;
                
            default:
                return $header . "<h2>Comunicazione da Colitalia</h2><p>Contenuto email non disponibile.</p>" . $footer;
        }
    }
    
    /**
     * Send email with logging
     */
    public function send_email($to, $subject, $message, $headers = array(), $attachments = array(), $email_type = 'general', $booking_id = null, $client_id = null) {
        // Prepare headers
        if (empty($headers)) {
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $this->from_name . ' <' . $this->from_email . '>'
            );
        }
        
        // Send email
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);
        
        // Log email
        $this->log_email($to, $subject, $email_type, $sent ? 'sent' : 'failed', $booking_id, $client_id);
        
        if (!$sent) {
            colitalia_log("Failed to send email to $to: $subject", 'error');
        }
        
        return $sent;
    }
    
    /**
     * Log email in database
     */
    private function log_email($recipient, $subject, $email_type, $status, $booking_id = null, $client_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'colitalia_email_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'recipient' => $recipient,
                'subject' => $subject,
                'email_type' => $email_type,
                'status' => $status,
                'booking_id' => $booking_id,
                'client_id' => $client_id
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d')
        );
    }
    
    /**
     * Send welcome email to new client
     */
    public function send_welcome_email($user_id, $client_id) {
        $user = get_userdata($user_id);
        $client_manager = new ClientManager();
        $client = $client_manager->get_client($client_id);
        
        if (!$client) {
            return false;
        }
        
        $variables = array(
            'client_name' => $client->first_name . ' ' . $client->last_name,
            'properties_url' => get_post_type_archive_link('property'),
            'login_url' => wp_login_url(),
            'contact_email' => $this->from_email
        );
        
        $subject = sprintf(__('Benvenuto in %s!', COLITALIA_PLUGIN_TEXTDOMAIN), get_bloginfo('name'));
        $message = $this->load_email_template('welcome', $variables);
        
        return $this->send_email(
            $client->email,
            $subject,
            $message,
            array(),
            array(),
            'welcome',
            null,
            $client_id
        );
    }
    
    /**
     * Send booking confirmation email
     */
    public function send_booking_confirmation($booking_id) {
        $booking_system = new BookingSystem();
        $booking = $booking_system->get_booking($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        $payment_url = add_query_arg(
            array('booking_code' => $booking->booking_code, 'action' => 'pay'),
            home_url('/prenota/')
        );
        
        $variables = array(
            'client_name' => $booking->first_name . ' ' . $booking->last_name,
            'booking_code' => $booking->booking_code,
            'property_title' => $booking->property_title,
            'check_in' => date_i18n('d/m/Y', strtotime($booking->check_in)),
            'check_out' => date_i18n('d/m/Y', strtotime($booking->check_out)),
            'guests' => $booking->guests,
            'total_price' => colitalia_format_currency($booking->total_price),
            'deposit_amount' => colitalia_format_currency($booking->deposit_amount),
            'payment_url' => $payment_url,
            'contact_phone' => get_option('colitalia_contact_phone', '')
        );
        
        $subject = sprintf(__('Conferma Prenotazione %s - %s', COLITALIA_PLUGIN_TEXTDOMAIN), $booking->booking_code, $booking->property_title);
        $message = $this->load_email_template('booking-confirmation', $variables);
        
        return $this->send_email(
            $booking->email,
            $subject,
            $message,
            array(),
            array(),
            'booking_confirmation',
            $booking_id,
            $booking->client_id
        );
    }
    
    /**
     * Send payment confirmation email
     */
    public function send_payment_confirmation($booking_id) {
        $booking_system = new BookingSystem();
        $booking = $booking_system->get_booking($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        $booking_details_url = add_query_arg(
            'booking_code',
            $booking->booking_code,
            get_permalink(get_option('colitalia_page_my_bookings'))
        );
        
        $variables = array(
            'client_name' => $booking->first_name . ' ' . $booking->last_name,
            'booking_code' => $booking->booking_code,
            'property_title' => $booking->property_title,
            'amount_paid' => colitalia_format_currency($booking->deposit_amount),
            'payment_method' => 'PayPal',
            'booking_details_url' => $booking_details_url,
            'contact_phone' => get_option('colitalia_contact_phone', '')
        );
        
        $subject = sprintf(__('Pagamento Confermato - %s', COLITALIA_PLUGIN_TEXTDOMAIN), $booking->booking_code);
        $message = $this->load_email_template('payment-confirmation', $variables);
        
        return $this->send_email(
            $booking->email,
            $subject,
            $message,
            array(),
            array(),
            'payment_confirmation',
            $booking_id,
            $booking->client_id
        );
    }
    
    /**
     * Send payment failure notification
     */
    public function send_payment_failure_notification($booking_id) {
        $booking_system = new BookingSystem();
        $booking = $booking_system->get_booking($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        $payment_url = add_query_arg(
            array('booking_code' => $booking->booking_code, 'action' => 'pay'),
            home_url('/prenota/')
        );
        
        $subject = sprintf(__('Problema con il Pagamento - %s', COLITALIA_PLUGIN_TEXTDOMAIN), $booking->booking_code);
        
        $message = "
        <h2>Problema con il Pagamento</h2>
        <p>Gentile {$booking->first_name} {$booking->last_name},</p>
        <p>Si è verificato un problema durante l'elaborazione del pagamento per la prenotazione <strong>{$booking->booking_code}</strong>.</p>
        <p>La prenotazione è ancora attiva per 24 ore. Puoi ritentare il pagamento utilizzando il link seguente:</p>
        <p><a href='{$payment_url}' style='background: #2c5aa0; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;'>Riprova Pagamento</a></p>
        <p>Per assistenza, contattaci a: {$this->from_email}</p>
        <p>Cordiali saluti,<br>Il Team Colitalia</p>
        ";
        
        return $this->send_email(
            $booking->email,
            $subject,
            $message,
            array(),
            array(),
            'payment_failed',
            $booking_id,
            $booking->client_id
        );
    }
    
    /**
     * Send cancellation notification
     */
    public function send_cancellation_notification($booking_id) {
        $booking_system = new BookingSystem();
        $booking = $booking_system->get_booking($booking_id);
        
        if (!$booking) {
            return false;
        }
        
        // Calculate refund amount
        $check_in_date = new \DateTime($booking->check_in);
        $now = new \DateTime();
        $days_until_checkin = $now->diff($check_in_date)->days;
        
        $refund_percentage = $this->calculate_refund_percentage($days_until_checkin);
        $refund_amount = $booking->deposit_amount * ($refund_percentage / 100);
        
        $variables = array(
            'client_name' => $booking->first_name . ' ' . $booking->last_name,
            'booking_code' => $booking->booking_code,
            'property_title' => $booking->property_title,
            'refund_amount' => colitalia_format_currency($refund_amount),
            'refund_percentage' => $refund_percentage
        );
        
        $subject = sprintf(__('Cancellazione Prenotazione %s', COLITALIA_PLUGIN_TEXTDOMAIN), $booking->booking_code);
        $message = $this->load_email_template('cancellation', $variables);
        
        return $this->send_email(
            $booking->email,
            $subject,
            $message,
            array(),
            array(),
            'booking_cancellation',
            $booking_id,
            $booking->client_id
        );
    }
    
    /**
     * Calculate refund percentage (same logic as BookingSystem)
     */
    private function calculate_refund_percentage($days_until_checkin) {
        if ($days_until_checkin >= 30) {
            return 100;
        } elseif ($days_until_checkin >= 14) {
            return 75;
        } elseif ($days_until_checkin >= 7) {
            return 50;
        } else {
            return 0;
        }
    }
    
    /**
     * Send booking reminders
     */
    public function send_booking_reminders() {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'colitalia_bookings';
        $properties_table = $wpdb->prefix . 'colitalia_properties';
        
        // Get bookings with check-in in 3 days
        $reminder_date = date('Y-m-d', strtotime('+3 days'));
        
        $upcoming_bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, post.post_title as property_title, cp.location, 
                    CONCAT(c.first_name, ' ', c.last_name) as client_name,
                    c.email as client_email
             FROM $bookings_table b
             LEFT JOIN $properties_table cp ON b.property_id = cp.post_id
             LEFT JOIN {$wpdb->posts} post ON cp.post_id = post.ID
             LEFT JOIN {$wpdb->prefix}colitalia_clients c ON b.client_id = c.id
             WHERE b.check_in = %s 
             AND b.status IN ('paid', 'confirmed')
             AND NOT EXISTS (
                 SELECT 1 FROM {$wpdb->prefix}colitalia_email_logs el 
                 WHERE el.booking_id = b.id 
                 AND el.email_type = 'booking_reminder' 
                 AND el.sent_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
             )",
            $reminder_date
        ));
        
        $sent_count = 0;
        
        foreach ($upcoming_bookings as $booking) {
            $property_address = get_post_meta($booking->property_id, '_address', true);
            
            $variables = array(
                'client_name' => $booking->client_name,
                'booking_code' => $booking->booking_code,
                'property_title' => $booking->property_title,
                'check_in' => date_i18n('d/m/Y', strtotime($booking->check_in)),
                'check_out' => date_i18n('d/m/Y', strtotime($booking->check_out)),
                'property_address' => $property_address,
                'contact_phone' => get_option('colitalia_contact_phone', ''),
                'contact_email' => $this->from_email
            );
            
            $subject = sprintf(__('Promemoria Soggiorno - %s', COLITALIA_PLUGIN_TEXTDOMAIN), $booking->property_title);
            $message = $this->load_email_template('booking-reminder', $variables);
            
            if ($this->send_email(
                $booking->client_email,
                $subject,
                $message,
                array(),
                array(),
                'booking_reminder',
                $booking->id,
                $booking->client_id
            )) {
                $sent_count++;
            }
        }
        
        if ($sent_count > 0) {
            colitalia_log("Sent $sent_count booking reminder emails");
        }
    }
    
    /**
     * Send newsletter campaign
     */
    public function send_newsletter_campaign($newsletter_data) {
        global $wpdb;
        
        $clients_table = $wpdb->prefix . 'colitalia_clients';
        
        // Get clients who opted in for marketing
        $subscribers = $wpdb->get_results(
            "SELECT id, first_name, last_name, email 
             FROM $clients_table 
             WHERE marketing_consent = 1
             ORDER BY created_at DESC
             LIMIT 500" // Send in batches
        );
        
        $sent_count = 0;
        $subject = $newsletter_data['subject'] ?? __('Newsletter Colitalia', COLITALIA_PLUGIN_TEXTDOMAIN);
        $message = $newsletter_data['message'] ?? '';
        
        foreach ($subscribers as $client) {
            // Personalize message
            $personalized_message = str_replace(
                array('{first_name}', '{full_name}'),
                array($client->first_name, $client->first_name . ' ' . $client->last_name),
                $message
            );
            
            if ($this->send_email(
                $client->email,
                $subject,
                $personalized_message,
                array(),
                array(),
                'newsletter',
                null,
                $client->id
            )) {
                $sent_count++;
            }
            
            // Add small delay to avoid overwhelming SMTP
            usleep(100000); // 0.1 second
        }
        
        colitalia_log("Newsletter sent to $sent_count subscribers");
        
        return $sent_count;
    }
    
    /**
     * Process email queue (for batch sending)
     */
    public function process_email_queue() {
        // Implementation for processing queued emails
        // This would be used for large email campaigns
        
        check_ajax_referer('colitalia_email_queue_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        // Process up to 50 emails per batch
        $processed = $this->process_email_batch(50);
        
        wp_send_json_success(array(
            'processed' => $processed,
            'remaining' => $this->get_queue_count()
        ));
    }
    
    /**
     * Process batch of queued emails
     */
    private function process_email_batch($limit = 50) {
        // This would work with a queue table - simplified implementation
        return 0;
    }
    
    /**
     * Get email queue count
     */
    private function get_queue_count() {
        // This would count queued emails - simplified implementation
        return 0;
    }
    
    /**
     * Get email statistics
     */
    public function get_email_statistics($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'colitalia_email_logs';
        $since_date = date('Y-m-d', strtotime("-$days days"));
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_sent,
                COUNT(CASE WHEN status = 'sent' THEN 1 END) as successful,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                COUNT(CASE WHEN status = 'bounced' THEN 1 END) as bounced
             FROM $table_name 
             WHERE sent_at >= %s",
            $since_date
        ));
        
        $stats->success_rate = $stats->total_sent > 0 ? ($stats->successful / $stats->total_sent) * 100 : 0;
        
        // Get email types breakdown
        $type_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT email_type, COUNT(*) as count 
             FROM $table_name 
             WHERE sent_at >= %s 
             GROUP BY email_type 
             ORDER BY count DESC",
            $since_date
        ));
        
        $stats->by_type = $type_stats;
        
        return $stats;
    }
    
    /**
     * Test SMTP configuration
     */
    public function test_smtp_configuration() {
        $test_email = get_option('admin_email');
        $subject = __('Test Email Colitalia', COLITALIA_PLUGIN_TEXTDOMAIN);
        $message = __('Questa è una email di test per verificare la configurazione SMTP.', COLITALIA_PLUGIN_TEXTDOMAIN);
        
        $result = $this->send_email(
            $test_email,
            $subject,
            $message,
            array(),
            array(),
            'smtp_test'
        );
        
        return array(
            'success' => $result,
            'message' => $result 
                ? __('Email di test inviata con successo', COLITALIA_PLUGIN_TEXTDOMAIN)
                : __('Errore nell\'invio dell\'email di test', COLITALIA_PLUGIN_TEXTDOMAIN)
        );
    }
}