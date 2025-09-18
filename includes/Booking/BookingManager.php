<?php
/**
 * Booking Manager Class (Versione Finale Definitiva e Stabile)
 * @package Colitalia_Real_Estate
 */
namespace Colitalia_Real_Estate\Booking;

defined('ABSPATH') || exit;

class BookingManager {
    
    private static $instance = null;
    private $table_bookings;
    private $table_customers;

    private function __construct() {
        global $wpdb;
        $this->table_bookings = $wpdb->prefix . 'colitalia_bookings';
        $this->table_customers = $wpdb->prefix . 'colitalia_customers';
        $this->init_hooks();
    }
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function init_hooks() {
        add_action('wp_ajax_colitalia_check_and_price', [$this, 'ajax_check_and_price']);
        add_action('wp_ajax_nopriv_colitalia_check_and_price', [$this, 'ajax_check_and_price']);
        add_action('wp_ajax_colitalia_create_booking', [$this, 'ajax_create_booking']);
        add_action('wp_ajax_nopriv_colitalia_create_booking', [$this, 'ajax_create_booking']);
        add_action('wp_ajax_colitalia_confirm_payment', [$this, 'ajax_confirm_payment']);
        add_action('wp_ajax_nopriv_colitalia_confirm_payment', [$this, 'ajax_confirm_payment']);
        add_action('wp_ajax_colitalia_cancel_booking', [$this, 'ajax_cancel_booking']);
        add_action('wp_ajax_colitalia_update_booking_status', [$this, 'ajax_update_booking_status']);
        add_action('wp_ajax_colitalia_delete_booking', [$this, 'ajax_delete_booking']);
    }
    
    public function ajax_cancel_booking() {
        check_ajax_referer('colitalia_admin_nonce', '_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Non hai i permessi per eseguire questa azione.']);
            return;
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

        if (!$booking_id) {
            wp_send_json_error(['message' => 'ID prenotazione non valido.']);
            return;
        }

        global $wpdb;
        $result = $wpdb->update(
            $this->table_bookings,
            ['status' => 'cancelled'],
            ['id' => $booking_id],
            ['%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => 'Errore durante la cancellazione della prenotazione.']);
        } else {
            wp_send_json_success(['message' => 'Prenotazione cancellata con successo.']);
        }
    }

    public function ajax_update_booking_status() {
        check_ajax_referer('colitalia_admin_nonce', '_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permessi insufficienti.']);
            return;
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
        $valid_statuses = ['pending', 'confirmed', 'paid', 'cancelled', 'completed'];

        if (!$booking_id || !in_array($new_status, $valid_statuses)) {
            wp_send_json_error(['message' => 'Dati non validi.']);
            return;
        }

        global $wpdb;
        $result = $wpdb->update(
            $this->table_bookings,
            ['status' => $new_status],
            ['id' => $booking_id],
            ['%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => 'Errore durante l\'aggiornamento dello stato.']);
        } else {
            wp_send_json_success(['message' => 'Stato aggiornato con successo.']);
        }
    }

    public function ajax_delete_booking() {
        check_ajax_referer('colitalia_admin_nonce', '_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permessi insufficienti.']);
            return;
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

        if (!$booking_id) {
            wp_send_json_error(['message' => 'ID prenotazione non valido.']);
            return;
        }

        global $wpdb;
        $result = $wpdb->delete(
            $this->table_bookings,
            ['id' => $booking_id],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => 'Errore durante l\'eliminazione della prenotazione.']);
        } else {
            wp_send_json_success(['message' => 'Prenotazione eliminata definitivamente.']);
        }
    }
    
    public function ajax_check_and_price() {
        check_ajax_referer('colitalia_booking_nonce', '_nonce');
        
        $property_id = intval($_POST['property_id']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        $guests = intval($_POST['guests']);

        if (!$this->check_availability($property_id, $date_from, $date_to)) {
            wp_send_json_error(['message' => 'Date non disponibili.']);
            return;
        }

        $pricing_details = $this->calculate_booking_price($property_id, $date_from, $date_to, $guests, isset($_POST['services']) ? array_map('intval', $_POST['services']) : []);
        if (isset($pricing_details['error'])) {
            wp_send_json_error(['message' => $pricing_details['error']]);
        } else {
            wp_send_json_success($pricing_details);
        }
    }

    public function ajax_create_booking() {
        check_ajax_referer('colitalia_booking_nonce', '_nonce');
        
        $customer_id = $this->get_or_create_customer($_POST);
        if (is_wp_error($customer_id)) {
            wp_send_json_error(['message' => $customer_id->get_error_message()]);
            return;
        }

        $pricing = $this->calculate_booking_price(intval($_POST['property_id']), $_POST['date_from'], $_POST['date_to'], intval($_POST['guests']), isset($_POST['services']) ? array_map('intval', $_POST['services']) : []);
        
        global $wpdb;
        $booking_data = [
            'property_id' => intval($_POST['property_id']),
            'customer_id' => $customer_id,
            'booking_code' => 'COL-' . strtoupper(wp_generate_password(8, false)),
            'date_from' => sanitize_text_field($_POST['date_from']),
            'date_to' => sanitize_text_field($_POST['date_to']),
            'guests' => intval($_POST['guests']),
            'total_price' => $pricing['total_price'],
            'status' => 'pending',
        ];
        
        $result = $wpdb->insert($this->table_bookings, $booking_data);

        if ($result === false) {
            wp_send_json_error(['message' => 'Errore nel salvataggio della prenotazione.']);
            return;
        }
        
        $booking_id = $wpdb->insert_id;
        wp_send_json_success(['booking_id' => $booking_id]);
    }

    public function ajax_confirm_payment() {
        check_ajax_referer('colitalia_booking_nonce', '_nonce');
        $booking_id = intval($_POST['booking_id']);
        $paypal_order_id = sanitize_text_field($_POST['paypal_order_id']);

        if (empty($booking_id)) { wp_send_json_error(['message' => 'ID prenotazione mancante.']); return; }

        global $wpdb;
        $wpdb->update($this->table_bookings,
            ['status' => 'paid', 'payment_transaction_id' => $paypal_order_id],
            ['id' => $booking_id]
        );
        
        $booking_code = $wpdb->get_var($wpdb->prepare("SELECT booking_code FROM {$this->table_bookings} WHERE id = %d", $booking_id));
        wp_send_json_success(['message' => 'Pagamento confermato!', 'booking_code' => $booking_code]);
    }
    
    private function get_or_create_customer($data) {
        global $wpdb;
        $email = sanitize_email($data['email']);
        $customer = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$this->table_customers} WHERE email = %s", $email));
        if ($customer) return $customer->id;
        
        $full_phone_number = '';
        if (!empty($data['phone_prefix']) && !empty($data['phone_number'])) {
            $prefix = preg_replace('/[^+\d]/', '', $data['phone_prefix']);
            $number = preg_replace('/[^\d]/', '', $data['phone_number']);
            $full_phone_number = sanitize_text_field($prefix . $number);
        }
        
        $result = $wpdb->insert($this->table_customers, [
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'email' => $email,
            'phone' => $full_phone_number
        ]);
        return $result ? $wpdb->insert_id : new \WP_Error('customer_error', 'Impossibile creare il cliente.');
    }

    public function check_availability($property_id, $date_from, $date_to) {
        global $wpdb;
        try {
            $start = new \DateTime($date_from); $end = new \DateTime($date_to);
            if ($start >= $end) return false;
        } catch (\Exception $e) { return false; }
        
        $query = $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_bookings} WHERE property_id = %d AND status NOT IN ('cancelled', 'refunded') AND date_from < %s AND date_to > %s", $property_id, $date_to, $date_from );
        return $wpdb->get_var($query) == 0;
    }

    public function calculate_booking_price($property_id, $date_from, $date_to, $guests, $services = []) {
        try {
            $base_daily_price = floatval(get_post_meta($property_id, '_property_daily_price', true));
            if ($base_daily_price <= 0) {
                $weekly_price = floatval(get_post_meta($property_id, '_property_weekly_price', true));
                if ($weekly_price > 0) {
                    $base_daily_price = $weekly_price / 7;
                } else {
                    return ['error' => 'Prezzo base non configurato correttamente per la proprietà.'];
                }
            }

            $rules = get_post_meta($property_id, '_seasonal_pricing_rules', true);
            if (!is_array($rules)) $rules = [];

            $start = new \DateTime($date_from);
            $end = new \DateTime($date_to);
            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($start, $interval, $end);
            
            $total_base_price = 0;
            $nights = 0;

            foreach ($period as $dt) {
                $current_date_str = $dt->format('Y-m-d');
                $price_for_this_day = $base_daily_price;

                foreach ($rules as $rule) {
                    if ($current_date_str >= $rule['start'] && $current_date_str < $rule['end']) {
                        $value = floatval($rule['value']);
                        switch ($rule['type']) {
                            case 'percentage_increase': $price_for_this_day *= (1 + $value / 100); break;
                            case 'percentage_decrease': $price_for_this_day *= (1 - $value / 100); break;
                            case 'fixed_daily': $price_for_this_day = $value; break;
                        }
                        break; 
                    }
                }
                $total_base_price += $price_for_this_day;
                $nights++;
            }
            
            if ($nights <= 0) return ['error' => 'Periodo non valido.'];
            
            $services_price = 0;
            if (!empty($services)) {
                global $wpdb;
                $services_table = $wpdb->prefix . 'colitalia_services';
                $service_ids_safe = implode(',', array_map('intval', $services));
                $service_costs = $wpdb->get_results("SELECT price FROM {$services_table} WHERE id IN ({$service_ids_safe})");
                if ($service_costs) {
                    foreach($service_costs as $cost) {
                        $services_price += floatval($cost->price);
                    }
                }
            }

            $total_price = $total_base_price + $services_price;
            
            return [
                'base_price' => round($total_base_price, 2),
                'services_price' => round($services_price, 2),
                'total_price' => round($total_price, 2),
                'nights' => $nights
            ];
            
        } catch (\Exception $e) {
            return ['error' => 'Formato data non valido.'];
        }
    }
}