<?php
/**
 * Calendar Manager Class per Colitalia Real Estate Manager
 * 
 * Gestisce il calendario delle disponibilità e le API AJAX
 * 
 * @since 1.2.0
 * @package Colitalia_Real_Estate
 */

namespace Colitalia_Real_Estate\Booking;

defined('ABSPATH') || exit;

class CalendarManager {
    
    /**
     * Istanza singleton
     */
    private static $instance = null;
    
    /**
     * Tabelle database
     */
    private $table_availability;
    private $table_bookings;
    private $table_pricing;
    
    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        
        $this->table_availability = $wpdb->prefix . 'colitalia_availability';
        $this->table_bookings = $wpdb->prefix . 'colitalia_bookings';
        $this->table_pricing = $wpdb->prefix . 'colitalia_pricing';
        
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
        add_action('wp_ajax_colitalia_get_calendar_data', [$this, 'ajax_get_calendar_data']);
        add_action('wp_ajax_nopriv_colitalia_get_calendar_data', [$this, 'ajax_get_calendar_data']);
        add_action('wp_ajax_colitalia_update_availability', [$this, 'ajax_update_availability']);
        add_action('wp_ajax_colitalia_bulk_update_availability', [$this, 'ajax_bulk_update_availability']);
        add_action('wp_ajax_colitalia_get_property_calendar', [$this, 'ajax_get_property_calendar']);
        add_action('wp_ajax_nopriv_colitalia_get_property_calendar', [$this, 'ajax_get_property_calendar']);
        
        // Shortcode per calendario frontend
        add_shortcode('colitalia_calendar', [$this, 'calendar_shortcode']);
        add_shortcode('colitalia_availability_calendar', [$this, 'availability_calendar_shortcode']);
    }
    
    /**
     * Ottiene dati calendario per una proprietà
     */
    public function get_calendar_data($property_id, $start_date, $end_date) {
        global $wpdb;
        
        // Genera tutti i giorni nel range
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
        
        $calendar_data = [];
        
        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            $calendar_data[$date_str] = [
                'date' => $date_str,
                'status' => 'available',
                'price' => null,
                'min_nights' => 1,
                'max_guests' => null,
                'booking_id' => null,
                'booking_code' => null,
                'customer_name' => null,
                'is_checkin' => false,
                'is_checkout' => false,
                'notes' => null
            ];
        }
        
        // Ottieni dati disponibilità espliciti
        $availability_query = "
            SELECT date, status, price, min_nights, max_guests, notes
            FROM {$this->table_availability}
            WHERE property_id = %d 
            AND date BETWEEN %s AND %s
        ";
        
        $availability_results = $wpdb->get_results($wpdb->prepare(
            $availability_query, 
            $property_id, 
            $start_date, 
            $end_date
        ), ARRAY_A);
        
        foreach ($availability_results as $row) {
            if (isset($calendar_data[$row['date']])) {
                $calendar_data[$row['date']] = array_merge($calendar_data[$row['date']], [
                    'status' => $row['status'],
                    'price' => $row['price'],
                    'min_nights' => $row['min_nights'],
                    'max_guests' => $row['max_guests'],
                    'notes' => $row['notes']
                ]);
            }
        }
        
        // Ottieni prenotazioni
        $bookings_query = "
            SELECT b.id, b.booking_code, b.date_from, b.date_to, b.status, b.guests,
                   c.first_name, c.last_name, c.email, c.phone
            FROM {$this->table_bookings} b
            LEFT JOIN " . $wpdb->prefix . "colitalia_customers c ON b.customer_id = c.id
            WHERE b.property_id = %d 
            AND b.status NOT IN ('cancelled')
            AND ((b.date_from <= %s AND b.date_to > %s) 
                 OR (b.date_from < %s AND b.date_to >= %s)
                 OR (b.date_from >= %s AND b.date_to <= %s))
        ";
        
        $bookings_results = $wpdb->get_results($wpdb->prepare(
            $bookings_query,
            $property_id,
            $end_date, $start_date,
            $end_date, $start_date,
            $start_date, $end_date
        ), ARRAY_A);
        
        foreach ($bookings_results as $booking) {
            $booking_start = new \DateTime($booking['date_from']);
            $booking_end = new \DateTime($booking['date_to']);
            $booking_period = new \DatePeriod($booking_start, new \DateInterval('P1D'), $booking_end);
            
            foreach ($booking_period as $booking_date) {
                $booking_date_str = $booking_date->format('Y-m-d');
                
                if (isset($calendar_data[$booking_date_str])) {
                    $calendar_data[$booking_date_str] = array_merge($calendar_data[$booking_date_str], [
                        'status' => 'booked',
                        'booking_id' => $booking['id'],
                        'booking_code' => $booking['booking_code'],
                        'customer_name' => $booking['first_name'] . ' ' . $booking['last_name'],
                        'customer_email' => $booking['email'],
                        'customer_phone' => $booking['phone'],
                        'guests' => $booking['guests'],
                        'is_checkin' => ($booking_date_str === $booking['date_from']),
                        'is_checkout' => ($booking_date_str === date('Y-m-d', strtotime($booking['date_to'] . ' -1 day')))
                    ]);
                }
            }
        }
        
        // Ottieni prezzi stagionali per date senza prezzo specifico
        $pricing_query = "
            SELECT date_from, date_to, base_price, price_per_guest, min_nights, max_guests
            FROM {$this->table_pricing}
            WHERE property_id = %d 
            AND is_active = 1
            AND ((date_from <= %s AND date_to >= %s))
            ORDER BY priority DESC
        ";
        
        $pricing_results = $wpdb->get_results($wpdb->prepare(
            $pricing_query,
            $property_id,
            $end_date, $start_date
        ), ARRAY_A);
        
        foreach ($pricing_results as $pricing) {
            $pricing_start = new \DateTime($pricing['date_from']);
            $pricing_end = new \DateTime($pricing['date_to']);
            $pricing_period = new \DatePeriod($pricing_start, new \DateInterval('P1D'), $pricing_end->add(new \DateInterval('P1D')));
            
            foreach ($pricing_period as $pricing_date) {
                $pricing_date_str = $pricing_date->format('Y-m-d');
                
                if (isset($calendar_data[$pricing_date_str]) && is_null($calendar_data[$pricing_date_str]['price'])) {
                    $calendar_data[$pricing_date_str]['price'] = $pricing['base_price'];
                    $calendar_data[$pricing_date_str]['price_per_guest'] = $pricing['price_per_guest'];
                    if (!$calendar_data[$pricing_date_str]['max_guests']) {
                        $calendar_data[$pricing_date_str]['max_guests'] = $pricing['max_guests'];
                    }
                }
            }
        }
        
        return array_values($calendar_data);
    }
    
    /**
     * Aggiorna disponibilità per una data specifica
     */
    public function update_availability($property_id, $date, $data) {
        global $wpdb;
        
        $update_data = [
            'property_id' => $property_id,
            'date' => $date
        ];
        
        $allowed_fields = ['status', 'price', 'min_nights', 'max_guests', 'notes'];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }
        
        $result = $wpdb->replace($this->table_availability, $update_data);
        
        return $result !== false;
    }
    
    /**
     * Aggiorna disponibilità in blocco per un range di date
     */
    public function bulk_update_availability($property_id, $start_date, $end_date, $data) {
        global $wpdb;
        
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->add(new \DateInterval('P1D')));
        
        $success_count = 0;
        
        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            if ($this->update_availability($property_id, $date_str, $data)) {
                $success_count++;
            }
        }
        
        return $success_count;
    }
    
    /**
     * Blocca date per manutenzione
     */
    public function block_dates_for_maintenance($property_id, $start_date, $end_date, $notes = '') {
        return $this->bulk_update_availability($property_id, $start_date, $end_date, [
            'status' => 'maintenance',
            'notes' => $notes
        ]);
    }
    
    /**
     * Ottiene periodi disponibili per prenotazione
     */
    public function get_available_periods($property_id, $start_date = null, $end_date = null, $min_nights = 1) {
        $start_date = $start_date ?: date('Y-m-d');
        $end_date = $end_date ?: date('Y-m-d', strtotime('+1 year'));
        
        $calendar_data = $this->get_calendar_data($property_id, $start_date, $end_date);
        $available_periods = [];
        $current_period = null;
        
        foreach ($calendar_data as $day) {
            if ($day['status'] === 'available') {
                if (!$current_period) {
                    $current_period = [
                        'start' => $day['date'],
                        'end' => $day['date'],
                        'nights' => 1
                    ];
                } else {
                    $current_period['end'] = $day['date'];
                    $current_period['nights']++;
                }
            } else {
                if ($current_period && $current_period['nights'] >= $min_nights) {
                    $available_periods[] = $current_period;
                }
                $current_period = null;
            }
        }
        
        // Aggiungi ultimo periodo se valido
        if ($current_period && $current_period['nights'] >= $min_nights) {
            $available_periods[] = $current_period;
        }
        
        return $available_periods;
    }
    
    /**
     * Sincronizzazione con sistemi esterni (placeholder)
     */
    public function sync_external_calendar($property_id, $external_calendar_url = null, $platform = 'airbnb') {
        // Placeholder per sincronizzazione con calendari esterni
        // Implementare logica per importare da Airbnb, Booking.com, etc.
        
        if (!$external_calendar_url) {
            return false;
        }
        
        // Esempio di implementazione base
        $calendar_data = wp_remote_get($external_calendar_url);
        
        if (is_wp_error($calendar_data)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($calendar_data);
        
        // Parse iCal format (esempio base)
        $lines = explode("\n", $body);
        $events = [];
        $current_event = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (strpos($line, 'BEGIN:VEVENT') === 0) {
                $current_event = [];
            } elseif (strpos($line, 'END:VEVENT') === 0) {
                if (!empty($current_event)) {
                    $events[] = $current_event;
                }
            } elseif (strpos($line, 'DTSTART:') === 0) {
                $current_event['start'] = substr($line, 8);
            } elseif (strpos($line, 'DTEND:') === 0) {
                $current_event['end'] = substr($line, 6);
            } elseif (strpos($line, 'SUMMARY:') === 0) {
                $current_event['summary'] = substr($line, 8);
            }
        }
        
        // Blocca le date importate
        foreach ($events as $event) {
            if (isset($event['start']) && isset($event['end'])) {
                $start_date = date('Y-m-d', strtotime($event['start']));
                $end_date = date('Y-m-d', strtotime($event['end']));
                
                $this->bulk_update_availability($property_id, $start_date, $end_date, [
                    'status' => 'blocked',
                    'notes' => 'Importato da ' . $platform . ': ' . ($event['summary'] ?? 'Prenotazione esterna')
                ]);
            }
        }
        
        // Aggiorna timestamp ultimo sync
        update_post_meta($property_id, '_last_calendar_sync', current_time('mysql'));
        update_post_meta($property_id, '_external_calendar_url', $external_calendar_url);
        
        return true;
    }
    
    /**
     * Shortcode calendario frontend
     */
    public function calendar_shortcode($atts) {
        $atts = shortcode_atts([
            'property_id' => get_the_ID(),
            'view' => 'month',
            'height' => '600px',
            'show_prices' => true,
            'allow_booking' => true
        ], $atts);
        
        if (!$atts['property_id']) {
            return '<p>ID proprietà non specificato</p>';
        }
        
        // Enqueue assets
        wp_enqueue_script('colitalia-calendar');
        wp_enqueue_style('colitalia-booking');
        
        ob_start();
        ?>
        <div class="colitalia-calendar-container" 
             data-property-id="<?php echo esc_attr($atts['property_id']); ?>"
             data-view="<?php echo esc_attr($atts['view']); ?>"
             data-show-prices="<?php echo esc_attr($atts['show_prices']); ?>"
             data-allow-booking="<?php echo esc_attr($atts['allow_booking']); ?>">
            
            <div id="colitalia-calendar-<?php echo esc_attr($atts['property_id']); ?>" 
                 style="height: <?php echo esc_attr($atts['height']); ?>"></div>
            
            <div class="colitalia-calendar-legend">
                <div class="legend-item">
                    <span class="legend-color available"></span>
                    <span class="legend-text">Disponibile</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color booked"></span>
                    <span class="legend-text">Prenotato</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color blocked"></span>
                    <span class="legend-text">Non disponibile</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color maintenance"></span>
                    <span class="legend-text">Manutenzione</span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode calendario disponibilità (admin)
     */
    public function availability_calendar_shortcode($atts) {
        if (!current_user_can('edit_posts')) {
            return '<p>Non hai i permessi per visualizzare questo contenuto</p>';
        }
        
        $atts = shortcode_atts([
            'property_id' => get_the_ID(),
            'view' => 'month',
            'height' => '600px',
            'editable' => true
        ], $atts);
        
        ob_start();
        ?>
        <div class="colitalia-admin-calendar-container" 
             data-property-id="<?php echo esc_attr($atts['property_id']); ?>"
             data-editable="<?php echo esc_attr($atts['editable']); ?>">
            
            <div class="calendar-toolbar">
                <button type="button" class="button" id="bulk-available">Rendi Disponibili</button>
                <button type="button" class="button" id="bulk-blocked">Blocca Date</button>
                <button type="button" class="button" id="bulk-maintenance">Manutenzione</button>
                <button type="button" class="button secondary" id="sync-external">Sincronizza</button>
            </div>
            
            <div id="colitalia-admin-calendar-<?php echo esc_attr($atts['property_id']); ?>" 
                 style="height: <?php echo esc_attr($atts['height']); ?>"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX: Ottieni dati calendario
     */
    public function ajax_get_calendar_data() {
        check_ajax_referer('colitalia_calendar_nonce', 'nonce');
        
        $property_id = intval($_GET['property_id']);
        $start_date = sanitize_text_field($_GET['start']);
        $end_date = sanitize_text_field($_GET['end']);
        
        if (!$property_id) {
            wp_send_json_error('Property ID richiesto');
        }
        
        $calendar_data = $this->get_calendar_data($property_id, $start_date, $end_date);
        
        // Converti in formato FullCalendar
        $events = [];
        foreach ($calendar_data as $day) {
            $event = [
                'title' => '',
                'start' => $day['date'],
                'allDay' => true,
                'classNames' => ['calendar-day', 'status-' . $day['status']],
                'extendedProps' => $day
            ];
            
            // Titolo basato su stato
            switch ($day['status']) {
                case 'booked':
                    if ($day['is_checkin']) {
                        $event['title'] = '📥 ' . $day['customer_name'];
                    } elseif ($day['is_checkout']) {
                        $event['title'] = '📤 ' . $day['customer_name'];
                    } else {
                        $event['title'] = $day['customer_name'];
                    }
                    break;
                case 'blocked':
                case 'maintenance':
                    $event['title'] = 'Non disponibile';
                    break;
                default:
                    if ($day['price']) {
                        $event['title'] = '€' . number_format($day['price'], 0);
                    }
            }
            
            $events[] = $event;
        }
        
        wp_send_json_success($events);
    }
    
    /**
     * AJAX: Aggiorna disponibilità
     */
    public function ajax_update_availability() {
        check_ajax_referer('colitalia_calendar_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        $property_id = intval($_POST['property_id']);
        $date = sanitize_text_field($_POST['date']);
        $data = [
            'status' => sanitize_text_field($_POST['status'] ?? 'available'),
            'price' => floatval($_POST['price'] ?? 0) ?: null,
            'min_nights' => intval($_POST['min_nights'] ?? 1),
            'max_guests' => intval($_POST['max_guests'] ?? 0) ?: null,
            'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
        ];
        
        $result = $this->update_availability($property_id, $date, $data);
        
        if ($result) {
            wp_send_json_success('Disponibilità aggiornata');
        } else {
            wp_send_json_error('Errore durante l\'aggiornamento');
        }
    }
    
    /**
     * AJAX: Aggiorna disponibilità in blocco
     */
    public function ajax_bulk_update_availability() {
        check_ajax_referer('colitalia_calendar_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permessi insufficienti');
        }
        
        $property_id = intval($_POST['property_id']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $data = [
            'status' => sanitize_text_field($_POST['status'] ?? 'available'),
            'price' => floatval($_POST['price'] ?? 0) ?: null,
            'min_nights' => intval($_POST['min_nights'] ?? 1),
            'max_guests' => intval($_POST['max_guests'] ?? 0) ?: null,
            'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
        ];
        
        $updated_count = $this->bulk_update_availability($property_id, $start_date, $end_date, $data);
        
        wp_send_json_success([
            'message' => "Aggiornate $updated_count date",
            'count' => $updated_count
        ]);
    }
    
    /**
     * AJAX: Ottieni calendario proprietà (frontend)
     */
    public function ajax_get_property_calendar() {
        check_ajax_referer('colitalia_booking_nonce', 'nonce');
        
        $property_id = intval($_GET['property_id']);
        $start_date = sanitize_text_field($_GET['start'] ?? date('Y-m-01'));
        $end_date = sanitize_text_field($_GET['end'] ?? date('Y-m-t', strtotime('+2 months')));
        
        $calendar_data = $this->get_calendar_data($property_id, $start_date, $end_date);
        
        // Filtra solo dati necessari per frontend
        $frontend_data = array_map(function($day) {
            return [
                'date' => $day['date'],
                'status' => $day['status'],
                'price' => $day['price'],
                'min_nights' => $day['min_nights'],
                'available' => $day['status'] === 'available'
            ];
        }, $calendar_data);
        
        wp_send_json_success($frontend_data);
    }
}
