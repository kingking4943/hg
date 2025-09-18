<?php
namespace ColitaliaRealEstate\Core;

/**
 * Booking System Class (Versione Finale Stabile)
 * Gestisce la registrazione degli shortcode per il sistema di prenotazione.
 */
class BookingSystem {
    
    public function __construct() {
        add_shortcode('colitalia_booking_form', array($this, 'render_booking_form'));
        add_shortcode('colitalia_booking_calendar', array($this, 'render_booking_calendar'));
        
        // Le azioni AJAX ora sono gestite esclusivamente da BookingManager per chiarezza
    }

    /**
     * Renderizza lo shortcode del form di prenotazione includendo il file template corretto.
     * Questo è il metodo standard e più affidabile in WordPress.
     */
    public function render_booking_form($atts) {
        $atts = shortcode_atts(array(
            'property_id' => get_the_ID() // Imposta di default l'ID della pagina corrente
        ), $atts);

        if (empty($atts['property_id'])) {
            return '<p>Errore: ID della proprietà non specificato.</p>';
        }

        // Rende la variabile $property_id disponibile all'interno del file di template
        $property_id = intval($atts['property_id']);

        // Includi il file del template che contiene l'HTML e il JS del form
        $template_path = COLITALIA_PLUGIN_PATH . 'templates/booking-form.php';

        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        return '<p>Errore: Il file del template del form di prenotazione non è stato trovato.</p>';
    }

    /**
     * Renderizza lo shortcode del calendario
     */
    public function render_booking_calendar($atts) {
        // Logica futura per il calendario
        return "";
    }
}