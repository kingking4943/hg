<?php
/**
 * Template Calendar Widget
 * 
 * @since 1.2.0
 * @package Colitalia_Real_Estate
 */

defined('ABSPATH') || exit;

// Ottieni ID proprietà
$property_id = isset($args['property_id']) ? intval($args['property_id']) : get_the_ID();
if (!$property_id) {
    echo '<p>Errore: proprietà non specificato</p>';
    return;
}

// Parametri widget
$show_prices = isset($args['show_prices']) ? (bool)$args['show_prices'] : true;
$show_legend = isset($args['show_legend']) ? (bool)$args['show_legend'] : true;
$height = isset($args['height']) ? $args['height'] : '500px';
$view = isset($args['view']) ? $args['view'] : 'dayGridMonth';
$locale = isset($args['locale']) ? $args['locale'] : 'it';

// Dati proprietà per JavaScript
$property_data = [
    'id' => $property_id,
    'title' => get_the_title($property_id),
    'max_guests' => intval(get_post_meta($property_id, '_max_guests', true) ?: 8),
    'min_nights' => intval(get_post_meta($property_id, '_min_nights', true) ?: 1),
    'base_price' => floatval(get_post_meta($property_id, '_price_per_night', true) ?: 100)
];
?>

<div class="colitalia-calendar-widget" 
     data-property-id="<?php echo esc_attr($property_id); ?>"
     data-show-prices="<?php echo esc_attr($show_prices ? '1' : '0'); ?>"
     data-height="<?php echo esc_attr($height); ?>"
     data-view="<?php echo esc_attr($view); ?>"
     data-locale="<?php echo esc_attr($locale); ?>">
    
    <!-- Header calendario -->
    <div class="calendar-header">
        <div class="calendar-title">
            <h4>Disponibilità - <?php echo esc_html($property_data['title']); ?></h4>
        </div>
        
        <div class="calendar-controls">
            <div class="view-switcher">
                <button type="button" class="view-btn active" data-view="dayGridMonth">Mese</button>
                <button type="button" class="view-btn" data-view="dayGridWeek">Settimana</button>
                <button type="button" class="view-btn" data-view="listMonth">Lista</button>
            </div>
            
            <?php if (current_user_can('edit_posts')): ?>
                <div class="admin-controls">
                    <button type="button" class="btn btn-sm btn-secondary" id="sync-calendar">
                        🔄 Sincronizza
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="manage-calendar">
                        ⚙️ Gestisci
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Calendario principale -->
    <div class="calendar-container" style="height: <?php echo esc_attr($height); ?>">
        <div id="colitalia-calendar-<?php echo esc_attr($property_id); ?>" 
             class="fullcalendar-widget"></div>
    </div>
    
    <?php if ($show_legend): ?>
        <!-- Legenda -->
        <div class="calendar-legend">
            <div class="legend-title">Legenda:</div>
            <div class="legend-items">
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
                <div class="legend-item">
                    <span class="legend-color checkin"></span>
                    <span class="legend-text">Check-in</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color checkout"></span>
                    <span class="legend-text">Check-out</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Loading state -->
    <div class="calendar-loading" style="display: none;">
        <div class="loading-spinner"></div>
        <div class="loading-text">Caricamento calendario...</div>
    </div>
    
    <!-- Error state -->
    <div class="calendar-error" style="display: none;">
        <div class="error-icon">⚠️</div>
        <div class="error-text">Errore nel caricamento del calendario</div>
        <button type="button" class="btn btn-sm btn-primary retry-calendar">Riprova</button>
    </div>
</div>

<?php if (current_user_can('edit_posts')): ?>
<!-- Modal gestione calendario (solo admin) -->
<div id="calendar-management-modal" class="colitalia-modal" style="display: none;">
    <div class="modal-backdrop"></div>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestisci Disponibilità</h5>
                <button type="button" class="modal-close" data-dismiss="modal">×</button>
            </div>
            
            <div class="modal-body">
                <div class="management-tabs">
                    <button class="tab-btn active" data-tab="bulk-update">Aggiornamento Massivo</button>
                    <button class="tab-btn" data-tab="pricing">Prezzi Stagionali</button>
                    <button class="tab-btn" data-tab="sync">Sincronizzazione</button>
                </div>
                
                <!-- Tab Aggiornamento Massivo -->
                <div class="tab-content active" id="bulk-update">
                    <form id="bulk-update-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="bulk-start-date">Data inizio</label>
                                <input type="date" id="bulk-start-date" name="start_date" required>
                            </div>
                            <div class="form-group">
                                <label for="bulk-end-date">Data fine</label>
                                <input type="date" id="bulk-end-date" name="end_date" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="bulk-status">Stato</label>
                                <select id="bulk-status" name="status" required>
                                    <option value="available">Disponibile</option>
                                    <option value="blocked">Non disponibile</option>
                                    <option value="maintenance">Manutenzione</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="bulk-price">Prezzo (€/notte)</label>
                                <input type="number" id="bulk-price" name="price" min="0" step="0.01">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="bulk-notes">Note</label>
                            <textarea id="bulk-notes" name="notes" placeholder="Note opzionali..."></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Applica Modifiche</button>
                        </div>
                    </form>
                </div>
                
                <!-- Tab Prezzi Stagionali -->
                <div class="tab-content" id="pricing">
                    <div class="pricing-seasons">
                        <div class="seasons-header">
                            <h6>Stagioni configurate</h6>
                            <button type="button" class="btn btn-sm btn-primary" id="add-season">
                                + Aggiungi Stagione
                            </button>
                        </div>
                        <div class="seasons-list">
                            <!-- Caricato dinamicamente -->
                        </div>
                    </div>
                    
                    <!-- Form nuova stagione -->
                    <div class="new-season-form" style="display: none;">
                        <form id="season-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="season-name">Nome stagione</label>
                                    <input type="text" id="season-name" name="season_name" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="season-start">Data inizio</label>
                                    <input type="date" id="season-start" name="date_from" required>
                                </div>
                                <div class="form-group">
                                    <label for="season-end">Data fine</label>
                                    <input type="date" id="season-end" name="date_to" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="season-base-price">Prezzo base (€/notte)</label>
                                    <input type="number" id="season-base-price" name="base_price" min="0" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label for="season-guest-price">Prezzo ospite extra (€/notte)</label>
                                    <input type="number" id="season-guest-price" name="price_per_guest" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="season-min-nights">Minimo notti</label>
                                    <input type="number" id="season-min-nights" name="min_nights" min="1" value="1">
                                </div>
                                <div class="form-group">
                                    <label for="season-priority">Priorità</label>
                                    <input type="number" id="season-priority" name="priority" min="1" value="1">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" id="cancel-season">Annulla</button>
                                <button type="submit" class="btn btn-primary">Salva Stagione</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tab Sincronizzazione -->
                <div class="tab-content" id="sync">
                    <div class="sync-options">
                        <div class="sync-option">
                            <h6>Calendario Airbnb</h6>
                            <div class="form-group">
                                <label for="airbnb-url">URL calendario iCal</label>
                                <input type="url" id="airbnb-url" name="airbnb_url" 
                                       value="<?php echo esc_attr(get_post_meta($property_id, '_airbnb_calendar_url', true)); ?>">
                            </div>
                            <button type="button" class="btn btn-primary sync-btn" data-platform="airbnb">
                                Sincronizza Airbnb
                            </button>
                        </div>
                        
                        <div class="sync-option">
                            <h6>Calendario Booking.com</h6>
                            <div class="form-group">
                                <label for="booking-url">URL calendario iCal</label>
                                <input type="url" id="booking-url" name="booking_url"
                                       value="<?php echo esc_attr(get_post_meta($property_id, '_booking_calendar_url', true)); ?>">
                            </div>
                            <button type="button" class="btn btn-primary sync-btn" data-platform="booking">
                                Sincronizza Booking.com
                            </button>
                        </div>
                        
                        <div class="sync-info">
                            <h6>Ultima sincronizzazione</h6>
                            <p>
                                <?php 
                                $last_sync = get_post_meta($property_id, '_last_calendar_sync', true);
                                if ($last_sync) {
                                    echo 'Ultima sincronizzazione: ' . date_i18n('d/m/Y H:i', strtotime($last_sync));
                                } else {
                                    echo 'Nessuna sincronizzazione effettuata';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tooltip per eventi calendario -->
<div id="calendar-tooltip" class="calendar-tooltip" style="display: none;">
    <div class="tooltip-content">
        <div class="tooltip-date"></div>
        <div class="tooltip-status"></div>
        <div class="tooltip-price"></div>
        <div class="tooltip-booking"></div>
        <div class="tooltip-notes"></div>
    </div>
</div>

<!-- JavaScript per inizializzazione -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Passa dati proprietà a JavaScript
    window.ColitaliaCalendarData = window.ColitaliaCalendarData || {};
    window.ColitaliaCalendarData[<?php echo json_encode($property_id); ?>] = <?php echo json_encode($property_data); ?>;
    
    // Inizializza calendario se la classe è disponibile
    if (typeof ColitaliaCalendar !== 'undefined') {
        ColitaliaCalendar.init(<?php echo json_encode($property_id); ?>);
    } else {
        console.warn('ColitaliaCalendar non caricato');
    }
});
</script>

<?php
// Enqueue assets necessari se non già fatto
if (!wp_script_is('fullcalendar', 'enqueued')) {
    wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js', [], '6.1.10', true);
}

if (!wp_script_is('fullcalendar-locales', 'enqueued')) {
    wp_enqueue_script('fullcalendar-locales', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js', ['fullcalendar'], '6.1.10', true);
}

// Script personalizzato calendario
wp_enqueue_script('colitalia-calendar');
wp_enqueue_style('colitalia-booking');

// Localizza script con dati AJAX
wp_localize_script('colitalia-calendar', 'ColitaliaCalendarAjax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('colitalia_calendar_nonce'),
    'property_id' => $property_id,
    'locale' => $locale,
    'strings' => [
        'loading' => 'Caricamento...',
        'error' => 'Errore nel caricamento',
        'no_events' => 'Nessun evento',
        'available' => 'Disponibile',
        'booked' => 'Prenotato',
        'blocked' => 'Non disponibile',
        'maintenance' => 'Manutenzione',
        'checkin' => 'Check-in',
        'checkout' => 'Check-out',
        'price_from' => 'Da €',
        'per_night' => '/notte',
        'guests' => 'ospiti',
        'nights' => 'notti',
        'booking_code' => 'Codice prenotazione',
        'customer' => 'Cliente',
        'phone' => 'Tel',
        'notes' => 'Note'
    ]
]);
?>
