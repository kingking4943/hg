<?php
namespace ColitaliaRealEstate\Admin;

/**
 * Settings Page Class
 * Gestisce pagina impostazioni admin per PayPal, Email e Multiproprietà
 */
class SettingsPage {
    
    private $option_group = 'colitalia_settings';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_colitalia_test_paypal_connection', array($this, 'ajax_test_paypal_connection'));
        add_action('wp_ajax_colitalia_test_smtp_connection', array($this, 'ajax_test_smtp_connection'));
        add_action('wp_ajax_colitalia_webhook_setup', array($this, 'ajax_webhook_setup'));
        add_action('wp_ajax_colitalia_test_maps_api', array($this, 'ajax_test_maps_api'));
        add_action('wp_ajax_colitalia_get_map_settings', array($this, 'ajax_get_map_settings'));
    }
    
    /**
     * Aggiunge menu admin
     */
    public function add_admin_menu() {
        // MODIFICA QUI: Spostato il menu sotto 'colitalia-dashboard'
        add_submenu_page(
            'colitalia-dashboard',
            'Impostazioni Colitalia',
            'Impostazioni',
            'manage_options',
            'colitalia-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Registra impostazioni
     */
    public function register_settings() {
        // PayPal Settings
        register_setting($this->option_group, 'colitalia_paypal_mode');
        register_setting($this->option_group, 'colitalia_paypal_client_id');
        register_setting($this->option_group, 'colitalia_paypal_client_secret');
        register_setting($this->option_group, 'colitalia_paypal_webhook_id');
        register_setting($this->option_group, 'colitalia_paypal_webhook_url');
        
        // Email Settings
        register_setting($this->option_group, 'colitalia_email_from_name');
        register_setting($this->option_group, 'colitalia_email_from_email');
        register_setting($this->option_group, 'colitalia_smtp_enabled');
        register_setting($this->option_group, 'colitalia_smtp_host');
        register_setting($this->option_group, 'colitalia_smtp_port');
        register_setting($this->option_group, 'colitalia_smtp_username');
        register_setting($this->option_group, 'colitalia_smtp_password');
        register_setting($this->option_group, 'colitalia_smtp_encryption');
        register_setting($this->option_group, 'colitalia_arrival_reminder_days');
        
        // Booking Settings
        register_setting($this->option_group, 'colitalia_currency');
        register_setting($this->option_group, 'colitalia_booking_deposit_percentage');
        register_setting($this->option_group, 'colitalia_service_fee_percentage');
        register_setting($this->option_group, 'colitalia_tourist_tax_percentage');
        register_setting($this->option_group, 'colitalia_cancellation_policy_days');
        register_setting($this->option_group, 'colitalia_booking_expiry_hours');
        register_setting($this->option_group, 'colitalia_average_occupancy_rate');
        
        // Timeshare Settings
        register_setting($this->option_group, 'colitalia_multiproperty_management_fee');
        register_setting($this->option_group, 'colitalia_max_ownership_percentage');
        register_setting($this->option_group, 'colitalia_min_investment_amount');
        register_setting($this->option_group, 'colitalia_revenue_distribution_day');
        
        // Contact Settings
        register_setting($this->option_group, 'colitalia_contact_phone');
        register_setting($this->option_group, 'colitalia_emergency_contact');
        register_setting($this->option_group, 'colitalia_company_address');
        
        // Maps Settings
        register_setting($this->option_group, 'colitalia_maps_default_zoom');
        register_setting($this->option_group, 'colitalia_maps_default_lat');
        register_setting($this->option_group, 'colitalia_maps_default_lng');
        register_setting($this->option_group, 'colitalia_maps_marker_style');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'colitalia-settings') !== false) {
            wp_enqueue_style('colitalia-admin-settings', COLITALIA_PLUGIN_URL . 'assets/css/admin-settings.css', array(), COLITALIA_PLUGIN_VERSION);
            wp_enqueue_script('colitalia-admin-settings', COLITALIA_PLUGIN_URL . 'assets/js/admin-settings.js', array('jquery'), COLITALIA_PLUGIN_VERSION, true);
            
            wp_localize_script('colitalia-admin-settings', 'colitalia_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('colitalia_admin_nonce'),
                'paypal_mode' => get_option('colitalia_paypal_mode', 'sandbox')
            ));
        }
    }
    
    /**
     * Renderizza pagina impostazioni
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Impostazioni Colitalia Real Estate</h1>
            
            <div class="nav-tab-wrapper">
                <a href="#paypal" class="nav-tab nav-tab-active" id="paypal-tab">PayPal</a>
                <a href="#email" class="nav-tab" id="email-tab">Email</a>
                <a href="#booking" class="nav-tab" id="booking-tab">Prenotazioni</a>
                <a href="#timeshare" class="nav-tab" id="timeshare-tab">Multiproprietà</a>
                <a href="#maps" class="nav-tab" id="maps-tab">Mappe</a>
                <a href="#contact" class="nav-tab" id="contact-tab">Contatti</a>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields($this->option_group); ?>
                
                <div id="paypal-settings" class="tab-content active">
                    <h2>Configurazione PayPal</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Modalità PayPal</th>
                            <td>
                                <select name="colitalia_paypal_mode" id="paypal_mode">
                                    <option value="sandbox" <?php selected(get_option('colitalia_paypal_mode'), 'sandbox'); ?>>Sandbox (Test)</option>
                                    <option value="live" <?php selected(get_option('colitalia_paypal_mode'), 'live'); ?>>Live (Produzione)</option>
                                </select>
                                <p class="description">Usa Sandbox per test, Live per produzione</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Client ID</th>
                            <td>
                                <input type="text" name="colitalia_paypal_client_id" id="paypal_client_id" 
                                       value="<?php echo esc_attr(get_option('colitalia_paypal_client_id')); ?>" 
                                       class="regular-text" />
                                <p class="description">Client ID da PayPal Developer Dashboard</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Client Secret</th>
                            <td>
                                <input type="password" name="colitalia_paypal_client_secret" id="paypal_client_secret" 
                                       value="<?php echo esc_attr(get_option('colitalia_paypal_client_secret')); ?>" 
                                       class="regular-text" />
                                <p class="description">Client Secret da PayPal Developer Dashboard</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Webhook ID</th>
                            <td>
                                <input type="text" name="colitalia_paypal_webhook_id" id="paypal_webhook_id" 
                                       value="<?php echo esc_attr(get_option('colitalia_paypal_webhook_id')); ?>" 
                                       class="regular-text" readonly />
                                <p class="description">Webhook ID (configurato automaticamente)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Webhook URL</th>
                            <td>
                                <input type="url" name="colitalia_paypal_webhook_url" id="paypal_webhook_url" 
                                       value="<?php echo esc_url(home_url('/colitalia-paypal-webhook/')); ?>" 
                                       class="regular-text" readonly />
                                <p class="description">URL webhook da configurare in PayPal</p>
                                <button type="button" id="setup_webhook" class="button">Configura Webhook</button>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="test_paypal" class="button button-secondary">Testa Connessione PayPal</button>
                        <div id="paypal_test_result"></div>
                    </p>
                </div>
                
                <div id="email-settings" class="tab-content">
                    <h2>Configurazione Email</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Nome Mittente</th>
                            <td>
                                <input type="text" name="colitalia_email_from_name" 
                                       value="<?php echo esc_attr(get_option('colitalia_email_from_name', get_bloginfo('name'))); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Email Mittente</th>
                            <td>
                                <input type="email" name="colitalia_email_from_email" 
                                       value="<?php echo esc_attr(get_option('colitalia_email_from_email', get_option('admin_email'))); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Abilita SMTP</th>
                            <td>
                                <input type="checkbox" name="colitalia_smtp_enabled" value="1" 
                                       <?php checked(get_option('colitalia_smtp_enabled'), 1); ?> id="smtp_enabled" />
                                <label for="smtp_enabled">Usa SMTP per invio email</label>
                            </td>
                        </tr>
                        <tr class="smtp-setting">
                            <th scope="row">Host SMTP</th>
                            <td>
                                <input type="text" name="colitalia_smtp_host" 
                                       value="<?php echo esc_attr(get_option('colitalia_smtp_host')); ?>" 
                                       class="regular-text" placeholder="smtp.gmail.com" />
                            </td>
                        </tr>
                        <tr class="smtp-setting">
                            <th scope="row">Porta SMTP</th>
                            <td>
                                <input type="number" name="colitalia_smtp_port" 
                                       value="<?php echo esc_attr(get_option('colitalia_smtp_port', 587)); ?>" 
                                       min="1" max="65535" />
                            </td>
                        </tr>
                        <tr class="smtp-setting">
                            <th scope="row">Username SMTP</th>
                            <td>
                                <input type="text" name="colitalia_smtp_username" 
                                       value="<?php echo esc_attr(get_option('colitalia_smtp_username')); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        <tr class="smtp-setting">
                            <th scope="row">Password SMTP</th>
                            <td>
                                <input type="password" name="colitalia_smtp_password" 
                                       value="<?php echo esc_attr(get_option('colitalia_smtp_password')); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        <tr class="smtp-setting">
                            <th scope="row">Crittografia</th>
                            <td>
                                <select name="colitalia_smtp_encryption">
                                    <option value="">Nessuna</option>
                                    <option value="tls" <?php selected(get_option('colitalia_smtp_encryption'), 'tls'); ?>>TLS</option>
                                    <option value="ssl" <?php selected(get_option('colitalia_smtp_encryption'), 'ssl'); ?>>SSL</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Giorni Promemoria Arrivo</th>
                            <td>
                                <input type="number" name="colitalia_arrival_reminder_days" 
                                       value="<?php echo esc_attr(get_option('colitalia_arrival_reminder_days', 3)); ?>" 
                                       min="1" max="30" />
                                <p class="description">Giorni prima del check-in per inviare promemoria</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="test_smtp" class="button button-secondary">Testa SMTP</button>
                        <button type="button" id="test_email_template" class="button button-secondary">Testa Template Email</button>
                        <div id="email_test_result"></div>
                    </p>
                </div>
                
                <div id="booking-settings" class="tab-content">
                    <h2>Impostazioni Prenotazioni</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Valuta</th>
                            <td>
                                <select name="colitalia_currency">
                                    <option value="EUR" <?php selected(get_option('colitalia_currency'), 'EUR'); ?>>Euro (EUR)</option>
                                    <option value="USD" <?php selected(get_option('colitalia_currency'), 'USD'); ?>>Dollar USA (USD)</option>
                                    <option value="GBP" <?php selected(get_option('colitalia_currency'), 'GBP'); ?>>Sterlina (GBP)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Percentuale Deposito</th>
                            <td>
                                <input type="number" name="colitalia_booking_deposit_percentage" 
                                       value="<?php echo esc_attr(get_option('colitalia_booking_deposit_percentage', 30)); ?>" 
                                       min="10" max="100" step="5" />
                                <span class="description">% del totale richiesta come deposito</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Commissione Servizio</th>
                            <td>
                                <input type="number" name="colitalia_service_fee_percentage" 
                                       value="<?php echo esc_attr(get_option('colitalia_service_fee_percentage', 5)); ?>" 
                                       min="0" max="20" step="0.5" />
                                <span class="description">% commissione su prenotazione</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Tassa Soggiorno</th>
                            <td>
                                <input type="number" name="colitalia_tourist_tax_percentage" 
                                       value="<?php echo esc_attr(get_option('colitalia_tourist_tax_percentage', 3)); ?>" 
                                       min="0" max="10" step="0.1" />
                                <span class="description">% tassa soggiorno</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Policy Cancellazione</th>
                            <td>
                                <input type="number" name="colitalia_cancellation_policy_days" 
                                       value="<?php echo esc_attr(get_option('colitalia_cancellation_policy_days', 7)); ?>" 
                                       min="1" max="30" />
                                <span class="description">Giorni minimi prima del check-in per cancellazione</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Scadenza Prenotazioni Pending</th>
                            <td>
                                <input type="number" name="colitalia_booking_expiry_hours" 
                                       value="<?php echo esc_attr(get_option('colitalia_booking_expiry_hours', 24)); ?>" 
                                       min="1" max="168" />
                                <span class="description">Ore dopo le quali prenotazioni pending vengono cancellate</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Tasso Occupazione Medio</th>
                            <td>
                                <input type="number" name="colitalia_average_occupancy_rate" 
                                       value="<?php echo esc_attr(get_option('colitalia_average_occupancy_rate', 75)); ?>" 
                                       min="30" max="100" />
                                <span class="description">% occupazione media per calcoli ROI</span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="timeshare-settings" class="tab-content">
                    <h2>Impostazioni Multiproprietà</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Commissione Gestione</th>
                            <td>
                                <input type="number" name="colitalia_multiproperty_management_fee" 
                                       value="<?php echo esc_attr(get_option('colitalia_multiproperty_management_fee', 10)); ?>" 
                                       min="5" max="25" step="0.5" />
                                <span class="description">% commissione gestione multiproprietà</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Max Percentuale Proprietà</th>
                            <td>
                                <input type="number" name="colitalia_max_ownership_percentage" 
                                       value="<?php echo esc_attr(get_option('colitalia_max_ownership_percentage', 25)); ?>" 
                                       min="10" max="50" step="0.1" />
                                <span class="description">% massima proprietà per singolo investitore</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Investimento Minimo</th>
                            <td>
                                <input type="number" name="colitalia_min_investment_amount" 
                                       value="<?php echo esc_attr(get_option('colitalia_min_investment_amount', 50000)); ?>" 
                                       min="10000" step="5000" />
                                <span class="description">€ investimento minimo richiesto</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Giorno Distribuzione Revenue</th>
                            <td>
                                <select name="colitalia_revenue_distribution_day">
                                    <?php for ($i = 1; $i <= 28; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php selected(get_option('colitalia_revenue_distribution_day', 15), $i); ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <span class="description">Giorno del mese per distribuzione revenue</span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="maps-settings" class="tab-content">
                    <h2>Configurazione Mappe (OpenStreetMap)</h2>
                    <p class="description">Le mappe utilizzano OpenStreetMap - completamente gratuito e senza limiti di utilizzo.</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Posizione Predefinita</th>
                            <td>
                                <label>Latitudine:</label>
                                <input type="number" name="colitalia_maps_default_lat" 
                                       value="<?php echo esc_attr(get_option('colitalia_maps_default_lat', '41.9027835')); ?>" 
                                       step="0.000001" class="small-text" />
                                <br><br>
                                <label>Longitudine:</label>
                                <input type="number" name="colitalia_maps_default_lng" 
                                       value="<?php echo esc_attr(get_option('colitalia_maps_default_lng', '12.4963655')); ?>" 
                                       step="0.000001" class="small-text" />
                                <p class="description">Posizione predefinita delle mappe (default: Roma)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Zoom Predefinito</th>
                            <td>
                                <input type="number" name="colitalia_maps_default_zoom" 
                                       value="<?php echo esc_attr(get_option('colitalia_maps_default_zoom', '6')); ?>" 
                                       min="1" max="20" />
                                <p class="description">Livello di zoom iniziale (1-20)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Stile Marker</th>
                            <td>
                                <select name="colitalia_maps_marker_style">
                                    <option value="default" <?php selected(get_option('colitalia_maps_marker_style', 'default'), 'default'); ?>>Predefinito</option>
                                    <option value="custom" <?php selected(get_option('colitalia_maps_marker_style'), 'custom'); ?>>Personalizzato</option>
                                </select>
                                <p class="description">Stile dei marker sulla mappa</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="test_maps_api" class="button button-secondary">Testa Connessione Mappa</button>
                        <div id="maps_test_result"></div>
                    </p>
                </div>
                
                <div id="contact-settings" class="tab-content">
                    <h2>Informazioni di Contatto</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Telefono Contatto</th>
                            <td>
                                <input type="tel" name="colitalia_contact_phone" 
                                       value="<?php echo esc_attr(get_option('colitalia_contact_phone')); ?>" 
                                       class="regular-text" />
                                <p class="description">Telefono principale per contatti clienti</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Contatto Emergenza</th>
                            <td>
                                <input type="tel" name="colitalia_emergency_contact" 
                                       value="<?php echo esc_attr(get_option('colitalia_emergency_contact')); ?>" 
                                       class="regular-text" />
                                <p class="description">Numero emergenza per ospiti</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Indirizzo Azienda</th>
                            <td>
                                <textarea name="colitalia_company_address" class="large-text" rows="3"><?php 
                                    echo esc_textarea(get_option('colitalia_company_address')); 
                                ?></textarea>
                                <p class="description">Indirizzo completo azienda</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <style>
            .tab-content { display: none; }
            .tab-content.active { display: block; }
            .smtp-setting { display: none; }
            .smtp-setting.show { display: table-row; }
            #paypal_test_result, #email_test_result, #maps_test_result {
                margin-top: 10px;
                padding: 10px;
                border-radius: 3px;
            }
            .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').removeClass('active');
                $(target + '-settings').addClass('active');
            });
            
            // SMTP toggle
            function toggleSmtpSettings() {
                if ($('#smtp_enabled').is(':checked')) {
                    $('.smtp-setting').addClass('show');
                } else {
                    $('.smtp-setting').removeClass('show');
                }
            }
            
            toggleSmtpSettings();
            $('#smtp_enabled').change(toggleSmtpSettings);
            
            // Test Maps (OpenStreetMap)
            $('#test_maps_api').click(function() {
                var button = $(this);
                var result = $('#maps_test_result');
                
                button.prop('disabled', true).text('Test in corso...');
                result.removeClass('success error').empty();
                
                $.post(ajaxurl, {
                    action: 'colitalia_test_maps_api',
                    nonce: '<?php echo wp_create_nonce('colitalia_admin_nonce'); ?>'
                }, function(response) {
                    button.prop('disabled', false).text('Testa Connessione Mappa');
                    
                    if (response.success) {
                        result.addClass('success').text(response.data);
                    } else {
                        result.addClass('error').text(response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX test PayPal connection
     */
    public function ajax_test_paypal_connection() {
        check_ajax_referer('colitalia_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        $paypal_manager = new \ColitaliaRealEstate\Payment\PayPalManager();
        
        // Test con dati fittizi
        $test_data = array(
            'property_id' => 1,
            'check_in' => date('Y-m-d', strtotime('+30 days')),
            'check_out' => date('Y-m-d', strtotime('+37 days')),
            'guests' => 2
        );
        
        $result = $paypal_manager->create_order($test_data);
        
        if ($result['success']) {
            wp_send_json_success('Connessione PayPal funzionante!');
        } else {
            wp_send_json_error('Errore connessione PayPal: ' . $result['error']);
        }
    }
    
    /**
     * AJAX test SMTP connection
     */
    public function ajax_test_smtp_connection() {
        check_ajax_referer('colitalia_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        $email_manager = new \ColitaliaRealEstate\Email\EmailManager();
        $admin_email = get_option('admin_email');
        
        $result = $email_manager->send_email(
            $admin_email,
            'Test SMTP - Colitalia Real Estate',
            '<h2>Test Email</h2><p>Se ricevi questa email, la configurazione SMTP funziona correttamente.</p>'
        );
        
        if ($result) {
            wp_send_json_success('Email di test inviata con successo!');
        } else {
            wp_send_json_error('Errore invio email di test');
        }
    }
    
    /**
     * AJAX webhook setup
     */
    public function ajax_webhook_setup() {
        check_ajax_referer('colitalia_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        // Implementazione setup webhook PayPal
        // In produzione implementare chiamate API PayPal per creare webhook
        
        wp_send_json_success('Webhook configurato (implementazione da completare)');
    }
    
    /**
     * AJAX test Maps API (OpenStreetMap only)
     */
    public function ajax_test_maps_api() {
        check_ajax_referer('colitalia_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        // Test OpenStreetMap (Nominatim)
        $test_url = 'https://nominatim.openstreetmap.org/search?q=Roma,Italia&format=json&limit=1';
        $response = wp_remote_get($test_url, array(
            'headers' => array(
                'User-Agent' => 'Colitalia Real Estate Plugin'
            )
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Errore di connessione: ' . $response->get_error_message());
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (is_array($data) && count($data) > 0) {
            wp_send_json_success('OpenStreetMap funzionante! Test geocoding completato con successo. ✅');
        } else {
            wp_send_json_error('Errore nel test OpenStreetMap. Verifica la connessione internet.');
        }
    }
    
    /**
     * AJAX get map settings (OpenStreetMap only)
     */
    public function ajax_get_map_settings() {
        // No nonce check needed for reading settings
        
        $settings = array(
            'provider' => 'openstreetmap',
            'default_lat' => floatval(get_option('colitalia_maps_default_lat', 41.9027835)),
            'default_lng' => floatval(get_option('colitalia_maps_default_lng', 12.4963655)),
            'default_zoom' => intval(get_option('colitalia_maps_default_zoom', 6)),
            'marker_style' => get_option('colitalia_maps_marker_style', 'default')
        );
        
        wp_send_json_success($settings);
    }
}