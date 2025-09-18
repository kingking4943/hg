/**
 * Colitalia Booking JavaScript (v1.5.1 - Correzione Nonce AJAX)
 * * Gestisce il form di prenotazione multi-step e le interazioni utente
 * * @since 1.2.0
 * @package Colitalia_Real_Estate
 */

(function($) {
    'use strict';
    
    // Oggetto principale
    window.ColitaliaBooking = {
        
        currentStep: 1,
        totalSteps: 4,
        propertyId: null,
        formData: {},
        $container: null,
        $form: null,
        $steps: null,
        $stepIndicators: null,
        availabilityCache: {},
        priceCache: {},
        
        /**
         * Inizializzazione
         */
        init: function() {
            this.$container = $('.colitalia-booking-form-container');
            if (!this.$container.length) return;
            
            this.propertyId = this.$container.data('property-id');
            this.$form = $('#colitalia-booking-form');
            this.$steps = $('.booking-step');
            this.$stepIndicators = $('.booking-steps-indicator .step');
            
            this.bindEvents();
            this.initDatePickers();
            this.initGuestsCounters();
            
            this.updateStepDisplay();
            this.validateCurrentStep();
        },
        
        /**
         * Bind eventi
         */
        bindEvents: function() {
            var self = this;
            
            $(document).on('click', '.next-step', function(e) { e.preventDefault(); if (self.validateCurrentStep()) self.nextStep(); });
            $(document).on('click', '.prev-step', function(e) { e.preventDefault(); self.prevStep(); });
            $(document).on('change', '#checkin-date, #checkout-date', self.checkAvailability.bind(self));
            $(document).on('change input', '[name="guests"], [name="adults"], [name="children"]', self.checkAvailability.bind(self));
            this.$form.on('submit', self.submitBooking.bind(self));
        },
        
        initDatePickers: function() { /* ... Logica Datepicker ... */ },
        initGuestsCounters: function() { /* ... Logica Contatori Ospiti ... */ },
        
        /**
         * Controlla disponibilità e prezzo
         */
        checkAvailability: function() {
            var self = this;
            var checkinDate = $('#checkin-date').val();
            var checkoutDate = $('#checkout-date').val();
            var guests = parseInt($('#guests-total').val()) || 1;
            
            if (!checkinDate || !checkoutDate) return;

            $.ajax({
                url: ColitaliaBookingAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'colitalia_check_availability',
                    booking_nonce: ColitaliaBookingAjax.nonce, // <-- CORREZIONE QUI
                    property_id: this.propertyId,
                    date_from: checkinDate,
                    date_to: checkoutDate,
                    guests: guests
                },
                success: function(response) {
                    if (response.success) {
                        // Se disponibile, calcola il prezzo
                        self.calculatePrice(checkinDate, checkoutDate, guests);
                    } else {
                        // Gestisci errore disponibilità
                    }
                },
                error: function() {
                    // Gestisci errore AJAX
                }
            });
        },

        /**
         * Calcola prezzo
         */
        calculatePrice: function(checkinDate, checkoutDate, guests) {
            var self = this;
             $.ajax({
                url: ColitaliaBookingAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'colitalia_calculate_price',
                    booking_nonce: ColitaliaBookingAjax.nonce, // <-- CORREZIONE QUI
                    property_id: this.propertyId,
                    date_from: checkinDate,
                    date_to: checkoutDate,
                    guests: guests,
                    services: [] // Aggiungi logica servizi se necessario
                },
                success: function(response) {
                    if (response.success) {
                        self.updatePriceDisplay(response.data);
                    } else {
                       // Gestisci errore calcolo prezzo
                    }
                },
                error: function() {
                    // Gestisci errore AJAX
                }
            });
        },
        
        updatePriceDisplay: function(pricing) {
            if (!pricing || typeof pricing.total_price === 'undefined') {
                console.error("Dati di prezzo non validi ricevuti dal server.");
                return;
            }
            // Logica per mostrare il prezzo, es:
            const priceHTML = `<strong>Totale: €${pricing.total_price.toFixed(2)}</strong> (${pricing.nights} notti)`;
            $('.price-details').html(priceHTML).show();
            $('.next-step').prop('disabled', false);
        },

        // --- Altre funzioni del form (navigazione, validazione, etc.) ---
        validateCurrentStep: function() { return true; /* Semplificato */ },
        nextStep: function() { /* ... */ },
        prevStep: function() { /* ... */ },
        updateStepDisplay: function() { /* ... */ },
        submitBooking: function() { /* ... */ }
    };
    
    $(document).ready(function() {
        if ($('.colitalia-booking-form-container').length) {
            ColitaliaBooking.init();
        }
    });
    
})(jQuery);