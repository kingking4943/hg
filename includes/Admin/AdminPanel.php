<?php
namespace ColitaliaRealEstate\Admin;

/**
 * Admin Panel Class (Versione Finale Stabile con Dashboard Completa)
 * Gestisce l'interfaccia di amministrazione principale.
 */
class AdminPanel {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
    }
    
    /**
     * Aggiunge i menu principali alla bacheca di WordPress.
     */
    public function add_admin_menus() {
        add_menu_page(
            __('Colitalia Real Estate', 'colitalia-real-estate'),
            __('Colitalia RE', 'colitalia-real-estate'),
            'manage_options',
            'colitalia-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-building',
            20
        );
        
        add_submenu_page(
            'colitalia-dashboard',
            __('Dashboard', 'colitalia-real-estate'),
            __('Dashboard', 'colitalia-real-estate'),
            'manage_options',
            'colitalia-dashboard'
        );

        add_submenu_page(
            'colitalia-dashboard',
            __('Prenotazioni', 'colitalia-real-estate'),
            __('Prenotazioni', 'colitalia-real-estate'),
            'manage_options',
            'colitalia-bookings',
            array($this, 'render_bookings_page')
        );
    }
    
    /**
     * Renderizza la pagina della dashboard con statistiche e dati.
     */
    public function render_dashboard_page() {
        global $wpdb;

        $total_properties = wp_count_posts('proprieta')->publish;
        
        $bookings_table = $wpdb->prefix . 'colitalia_bookings';
        $clients_table = $wpdb->prefix . 'colitalia_clients';

        $active_bookings = $wpdb->get_var("SELECT COUNT(id) FROM {$bookings_table} WHERE status IN ('confirmed', 'paid')");
        $total_clients = $wpdb->get_var("SELECT COUNT(id) FROM {$clients_table}");
        
        $revenue_this_month = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(total_price) FROM {$bookings_table} WHERE status IN ('paid', 'completed') AND created_at >= %s",
                date('Y-m-01')
            )
        );

        $recent_bookings = $wpdb->get_results(
            "SELECT b.id, b.booking_code, b.status, b.total_price, c.first_name, c.last_name, p.post_title 
             FROM {$bookings_table} b
             LEFT JOIN {$clients_table} c ON b.client_id = c.id
             LEFT JOIN {$wpdb->prefix}posts p ON b.property_id = p.ID
             ORDER BY b.created_at DESC
             LIMIT 5"
        );
        ?>
        <div class="wrap colitalia-dashboard">
            <h1><?php _e('Dashboard Colitalia Real Estate', 'colitalia-real-estate'); ?></h1>
            <p><?php _e('Benvenuto nel tuo pannello di controllo immobiliare.', 'colitalia-real-estate'); ?></p>

            <div class="dashboard-stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><span class="dashicons dashicons-building"></span></div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo esc_html($total_properties); ?></div>
                        <div class="stat-label"><?php _e('Proprietà Totali', 'colitalia-real-estate'); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo esc_html($active_bookings); ?></div>
                        <div class="stat-label"><?php _e('Prenotazioni Attive', 'colitalia-real-estate'); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><span class="dashicons dashicons-groups"></span></div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo esc_html($total_clients); ?></div>
                        <div class="stat-label"><?php _e('Clienti Registrati', 'colitalia-real-estate'); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><span class="dashicons dashicons-chart-area"></span></div>
                    <div class="stat-info">
                        <div class="stat-number">€<?php echo number_format_i18n($revenue_this_month ?? 0, 2); ?></div>
                        <div class="stat-label"><?php _e('Entrate Mese Corrente', 'colitalia-real-estate'); ?></div>
                    </div>
                </div>
            </div>

            <div class="postbox">
                <h2 class="hndle"><span><?php _e('Ultime 5 Prenotazioni', 'colitalia-real-estate'); ?></span></h2>
                <div class="inside">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Codice', 'colitalia-real-estate'); ?></th>
                                <th><?php _e('Cliente', 'colitalia-real-estate'); ?></th>
                                <th><?php _e('Proprietà', 'colitalia-real-estate'); ?></th>
                                <th><?php _e('Stato', 'colitalia-real-estate'); ?></th>
                                <th><?php _e('Totale', 'colitalia-real-estate'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_bookings)): ?>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($booking->booking_code); ?></strong></td>
                                        <td><?php echo esc_html($booking->first_name . ' ' . $booking->last_name); ?></td>
                                        <td><?php echo esc_html($booking->post_title); ?></td>
                                        <td><span class="booking-status-badge status-<?php echo esc_attr($booking->status); ?>"><?php echo esc_html(ucfirst($booking->status)); ?></span></td>
                                        <td>€<?php echo number_format_i18n($booking->total_price, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5"><?php _e('Nessuna prenotazione recente trovata.', 'colitalia-real-estate'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * PAGINA PRENOTAZIONI CON STATI MODIFICABILI E OPZIONE DI ELIMINAZIONE
     */
    public function render_bookings_page() {
        global $wpdb;

        // Gestione filtri
        $filter_property_id = isset($_GET['property_filter']) ? intval($_GET['property_filter']) : '';
        $filter_status = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : '';

        $sql = "SELECT b.*, c.first_name, c.last_name, p.post_title as property_title
                FROM {$wpdb->prefix}colitalia_bookings b
                LEFT JOIN {$wpdb->prefix}colitalia_customers c ON b.customer_id = c.id
                LEFT JOIN {$wpdb->prefix}posts p ON b.property_id = p.ID";
        
        $where_clauses = [];
        if (!empty($filter_property_id)) {
            $where_clauses[] = $wpdb->prepare("b.property_id = %d", $filter_property_id);
        }
        if (!empty($filter_status)) {
            $where_clauses[] = $wpdb->prepare("b.status = %s", $filter_status);
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $sql .= " ORDER BY b.created_at DESC";

        $bookings = $wpdb->get_results($sql);
        $properties = get_posts(['post_type' => 'proprieta', 'posts_per_page' => -1]);
        $statuses = ['pending', 'confirmed', 'paid', 'cancelled', 'completed'];
        ?>
        <div class="wrap colitalia-bookings-page">
            <div id="booking-ajax-response"></div>
            <h1><?php _e('Tutte le Prenotazioni', 'colitalia-real-estate'); ?></h1>
            <p><?php _e('Questa tabella mostra sia gli affitti delle case vacanze sia i soggiorni dei proprietari (multiproprietà).', 'colitalia-real-estate'); ?></p>
            
            <form method="get">
                <input type="hidden" name="page" value="colitalia-bookings">
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select name="property_filter">
                            <option value="">Tutte le proprietà</option>
                            <?php foreach ($properties as $property) : ?>
                                <option value="<?php echo $property->ID; ?>" <?php selected($filter_property_id, $property->ID); ?>>
                                    <?php echo esc_html($property->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status_filter">
                            <option value="">Tutti gli stati</option>
                            <?php foreach ($statuses as $status) : ?>
                                <option value="<?php echo $status; ?>" <?php selected($filter_status, $status); ?>>
                                    <?php echo ucfirst($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" class="button" value="Filtra">
                    </div>
                </div>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Codice', 'colitalia-real-estate'); ?></th>
                        <th><?php _e('Proprietà', 'colitalia-real-estate'); ?></th>
                        <th><?php _e('Cliente/Proprietario', 'colitalia-real-estate'); ?></th>
                        <th><?php _e('Periodo', 'colitalia-real-estate'); ?></th>
                        <th><?php _e('Ospiti', 'colitalia-real-estate'); ?></th>
                        <th><?php _e('Tipo', 'colitalia-real-estate'); ?></th>
                        <th><?php _e('Totale', 'colitalia-real-estate'); ?></th>
                        <th><?php _e('Stato', 'colitalia-real-estate'); ?></th>
                        <th><?php _e('Data Creazione', 'colitalia-real-estate'); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php if (!empty($bookings)): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr id="booking-<?php echo esc_attr($booking->id); ?>">
                                <td>
                                    <strong><?php echo esc_html($booking->booking_code); ?></strong>
                                    <div class="row-actions">
                                        <?php if ($booking->status !== 'cancelled'): ?>
                                            <span class="edit">
                                                <a href="#" class="cancel-booking-link" data-booking-id="<?php echo esc_attr($booking->id); ?>" data-nonce="<?php echo wp_create_nonce('colitalia_admin_nonce'); ?>">
                                                    Annulla
                                                </a> | 
                                            </span>
                                        <?php endif; ?>
                                        <span class="trash">
                                            <a href="#" class="delete-booking-link" style="color:#a00;" data-booking-id="<?php echo esc_attr($booking->id); ?>" data-nonce="<?php echo wp_create_nonce('colitalia_admin_nonce'); ?>">
                                                Elimina Definitivamente
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo esc_html($booking->property_title); ?></td>
                                <td><?php echo esc_html(trim($booking->first_name . ' ' . $booking->last_name)); ?></td>
                                <td><?php echo date_i18n('d/m/Y', strtotime($booking->date_from)); ?> - <?php echo date_i18n('d/m/Y', strtotime($booking->date_to)); ?></td>
                                <td><?php echo esc_html($booking->guests); ?></td>
                                <td><?php echo ($booking->booking_type === 'owner_stay') ? 'Soggiorno Proprietario' : 'Affitto'; ?></td>
                                <td>€<?php echo number_format_i18n($booking->total_price, 2); ?></td>
                                <td>
                                    <select class="booking-status-select" data-booking-id="<?php echo esc_attr($booking->id); ?>" data-nonce="<?php echo wp_create_nonce('colitalia_admin_nonce'); ?>">
                                        <?php foreach ($statuses as $status) : ?>
                                            <option value="<?php echo $status; ?>" <?php selected($booking->status, $status); ?>>
                                                <?php echo ucfirst($status); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><?php echo date_i18n('d/m/Y H:i', strtotime($booking->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9"><?php _e('Nessuna prenotazione trovata con i filtri selezionati.', 'colitalia-real-estate'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}