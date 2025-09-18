/**
 * PayPal Checkout Integration JavaScript
 * Gestisce integrazione PayPal Checkout Button con validazione e sicurezza
 */

(function($) {
    'use strict';
    
    const ColitaliaPayPal = {
        
        // Configuration
        config: {
            currency: colitalia_paypal.currency || 'EUR',
            locale: 'it_IT',
            debug: colitalia_paypal.mode === 'sandbox'
        },
        
        // Current booking data
        bookingData: {},
        
        // PayPal buttons instance
        paypalButtons: null,
        
        /**
         * Initialize PayPal integration
         */
        init: function() {
            this.bindEvents();
            this.loadBookingData();
            
            // Initialize PayPal buttons if container exists
            if ($('#paypal-button-container').length) {
                this.initPayPalButtons();
            }
            
            this.log('PayPal integration initialized');
        },
        
        /**
         * Bind DOM events
         */
        bindEvents: function() {
            // Booking form submission
            $(document).on('submit', '.colitalia-booking-form', this.handleBookingSubmit.bind(this));
            
            // Booking data changes
            $(document).on('change', '.booking-input', this.updateBookingData.bind(this));
            
            // PayPal payment success/cancel handlers
            $(document).on('colitalia:paypal:success', this.handlePaymentSuccess.bind(this));
            $(document).on('colitalia:paypal:cancel', this.handlePaymentCancel.bind(this));
            $(document).on('colitalia:paypal:error', this.handlePaymentError.bind(this));
        },
        
        /**
         * Load booking data from form or URL parameters
         */
        loadBookingData: function() {
            // Get data from form if exists
            const form = $('.colitalia-booking-form');
            if (form.length) {
                this.bookingData = {
                    property_id: form.find('[name="property_id"]').val(),
                    check_in: form.find('[name="check_in"]').val(),
                    check_out: form.find('[name="check_out"]').val(),
                    guests: form.find('[name="guests"]').val() || 1,
                    booking_id: form.find('[name="booking_id"]').val()
                };
            }
            
            // Get data from URL parameters (for payment page)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('booking_id')) {
                this.bookingData.booking_id = urlParams.get('booking_id');
            }
        },
        
        /**
         * Initialize PayPal Checkout buttons
         */
        initPayPalButtons: function() {
            const self = this;
            
            if (typeof paypal === 'undefined') {
                this.log('PayPal SDK not loaded', 'error');
                this.showError('Errore caricamento PayPal. Ricarica la pagina.');
                return;
            }
            
            this.paypalButtons = paypal.Buttons({
                
                // Payment method styling
                style: {
                    layout: 'vertical',
                    color: 'gold',
                    shape: 'rect',
                    label: 'paypal',
                    height: 50
                },
                
                // Create order function
                createOrder: function(data, actions) {
                    return self.createPayPalOrder();
                },
                
                // Order approval function  
                onApprove: function(data, actions) {
                    return self.capturePayPalOrder(data.orderID);
                },
                
                // Payment cancellation
                onCancel: function(data) {
                    self.log('PayPal payment cancelled');
                    self.handlePaymentCancel(data);
                },
                
                // Error handling
                onError: function(err) {
                    self.log('PayPal error: ' + JSON.stringify(err), 'error');
                    self.handlePaymentError(err);
                }
            });
            
            // Render buttons
            this.paypalButtons.render('#paypal-button-container')
                .then(() => {
                    this.log('PayPal buttons rendered successfully');
                    this.hideLoader();
                })
                .catch((err) => {
                    this.log('PayPal buttons render failed: ' + err, 'error');
                    this.showError('Errore inizializzazione PayPal');
                });
        },
        
        /**
         * Create PayPal order via AJAX
         */
        createPayPalOrder: function() {
            const self = this;
            
            return new Promise((resolve, reject) => {
                // Validate booking data
                if (!self.validateBookingData()) {
                    reject(new Error('Dati prenotazione non validi'));
                    return;
                }
                
                // Show processing state
                self.showProcessing('Creazione ordine PayPal...');
                
                $.ajax({
                    url: colitalia_paypal.ajax_url,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'colitalia_create_paypal_order',
                        nonce: colitalia_paypal.nonce,
                        ...self.bookingData
                    },
                    timeout: 30000
                })
                .done(function(response) {
                    if (response.success && response.data.order_id) {
                        self.log('PayPal order created: ' + response.data.order_id);
                        resolve(response.data.order_id);
                    } else {
                        const error = response.data?.error || 'Errore creazione ordine';
                        self.log('Order creation failed: ' + error, 'error');
                        reject(new Error(error));
                    }
                })
                .fail(function(xhr, status, error) {
                    const errorMsg = 'Errore connessione: ' + error;
                    self.log(errorMsg, 'error');
                    reject(new Error(errorMsg));
                })
                .always(function() {
                    self.hideProcessing();
                });
            });
        },
        
        /**
         * Capture PayPal order via AJAX
         */
        capturePayPalOrder: function(orderID) {
            const self = this;
            
            return new Promise((resolve, reject) => {
                // Show processing state
                self.showProcessing('Completamento pagamento...');
                
                $.ajax({
                    url: colitalia_paypal.ajax_url,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'colitalia_capture_paypal_order',
                        nonce: colitalia_paypal.nonce,
                        order_id: orderID
                    },
                    timeout: 30000
                })
                .done(function(response) {
                    if (response.success) {
                        self.log('PayPal payment captured successfully');
                        
                        // Trigger success event
                        $(document).trigger('colitalia:paypal:success', [response.data]);
                        
                        // Redirect to success page
                        self.redirectToSuccess(orderID);
                        
                        resolve(response.data);
                    } else {
                        const error = response.data?.error || 'Errore cattura pagamento';
                        self.log('Payment capture failed: ' + error, 'error');
                        reject(new Error(error));
                    }
                })
                .fail(function(xhr, status, error) {
                    const errorMsg = 'Errore completamento pagamento: ' + error;
                    self.log(errorMsg, 'error');
                    reject(new Error(errorMsg));
                })
                .always(function() {
                    self.hideProcessing();
                });
            });
        },
        
        /**
         * Handle booking form submission
         */
        handleBookingSubmit: function(e) {
            const form = $(e.target);
            
            // If PayPal payment selected, prevent normal submission
            if (form.find('[name="payment_method"]:checked').val() === 'paypal') {
                e.preventDefault();
                
                // Update booking data from form
                this.updateBookingData();
                
                // Show PayPal buttons if hidden
                $('#paypal-button-container').slideDown();
                
                // Scroll to PayPal buttons
                $('html, body').animate({
                    scrollTop: $('#paypal-button-container').offset().top - 50
                }, 500);
            }
        },
        
        /**
         * Update booking data from form inputs
         */
        updateBookingData: function() {
            const form = $('.colitalia-booking-form');
            if (!form.length) return;
            
            // Extract form data
            const formData = {
                property_id: form.find('[name="property_id"]').val(),
                check_in: form.find('[name="check_in"]').val(),
                check_out: form.find('[name="check_out"]').val(),
                guests: parseInt(form.find('[name="guests"]').val()) || 1,
                booking_id: form.find('[name="booking_id"]').val(),
                client_id: form.find('[name="client_id"]').val()
            };
            
            // Update internal data
            Object.assign(this.bookingData, formData);
            
            // Recalculate pricing if needed
            this.updatePricing();
        },
        
        /**
         * Update pricing display
         */
        updatePricing: function() {
            if (!this.bookingData.property_id || !this.bookingData.check_in || !this.bookingData.check_out) {
                return;
            }
            
            $.ajax({
                url: colitalia_paypal.ajax_url,
                method: 'POST',
                data: {
                    action: 'get_dynamic_pricing',
                    nonce: colitalia_paypal.nonce,
                    property_id: this.bookingData.property_id,
                    check_in: this.bookingData.check_in,
                    check_out: this.bookingData.check_out,
                    guests: this.bookingData.guests
                }
            })
            .done(function(response) {
                if (response.success && response.data) {
                    // Update pricing display
                    $('.total-price').text(self.formatCurrency(response.data.grand_total));
                    $('.deposit-amount').text(self.formatCurrency(response.data.deposit_amount));
                }
            });
        },
        
        /**
         * Validate booking data
         */
        validateBookingData: function() {
            const required = ['property_id', 'check_in', 'check_out', 'guests'];
            
            for (let field of required) {
                if (!this.bookingData[field]) {
                    this.showError(`Campo richiesto mancante: ${field}`);
                    return false;
                }
            }
            
            // Validate dates
            const checkIn = new Date(this.bookingData.check_in);
            const checkOut = new Date(this.bookingData.check_out);
            const today = new Date();
            
            if (checkIn < today) {
                this.showError('La data di check-in non può essere nel passato');
                return false;
            }
            
            if (checkOut <= checkIn) {
                this.showError('La data di check-out deve essere successiva al check-in');
                return false;
            }
            
            return true;
        },
        
        /**
         * Handle payment success
         */
        handlePaymentSuccess: function(event, paymentData) {
            this.log('Payment successful');
            
            // Show success message
            this.showSuccess('Pagamento completato con successo!');
            
            // Hide PayPal buttons
            $('#paypal-button-container').slideUp();
            
            // Update booking status display
            $('.booking-status').removeClass('pending').addClass('paid').text('PAGATO');
        },
        
        /**
         * Handle payment cancellation
         */
        handlePaymentCancel: function(data) {
            this.log('Payment cancelled by user');
            
            this.showWarning('Pagamento annullato. Puoi riprovare quando vuoi.');
            
            // Re-enable form if needed
            $('.colitalia-booking-form').find(':input').prop('disabled', false);
        },
        
        /**
         * Handle payment error
         */
        handlePaymentError: function(error) {
            this.log('Payment error: ' + JSON.stringify(error), 'error');
            
            let errorMessage = 'Si è verificato un errore durante il pagamento.';
            
            if (error && error.message) {
                errorMessage += ' Dettagli: ' + error.message;
            }
            
            this.showError(errorMessage);
            
            // Re-enable form
            $('.colitalia-booking-form').find(':input').prop('disabled', false);
        },
        
        /**
         * Redirect to success page
         */
        redirectToSuccess: function(orderID) {
            const successUrl = new URL(window.location.origin + '/prenota/');
            successUrl.searchParams.set('paypal_success', '1');
            successUrl.searchParams.set('order_id', orderID);
            
            if (this.bookingData.booking_id) {
                successUrl.searchParams.set('booking_id', this.bookingData.booking_id);
            }
            
            setTimeout(() => {
                window.location.href = successUrl.toString();
            }, 2000);
        },
        
        /**
         * Show processing state
         */
        showProcessing: function(message) {
            message = message || 'Elaborazione in corso...';
            
            // Disable form inputs
            $('.colitalia-booking-form').find(':input').prop('disabled', true);
            
            // Show processing overlay
            if (!$('#paypal-processing').length) {
                $('body').append(`
                    <div id="paypal-processing" class="paypal-overlay">
                        <div class="processing-content">
                            <div class="spinner"></div>
                            <p>${message}</p>
                        </div>
                    </div>
                `);
            }
            
            $('#paypal-processing').show();
        },
        
        /**
         * Hide processing state
         */
        hideProcessing: function() {
            $('#paypal-processing').hide();
            $('.colitalia-booking-form').find(':input').prop('disabled', false);
        },
        
        /**
         * Show loader
         */
        showLoader: function() {
            $('#paypal-button-container').html('<div class="paypal-loader">Caricamento PayPal...</div>');
        },
        
        /**
         * Hide loader
         */
        hideLoader: function() {
            $('.paypal-loader').remove();
        },
        
        /**
         * Show success message
         */
        showSuccess: function(message) {
            this.showMessage(message, 'success');
        },
        
        /**
         * Show warning message
         */
        showWarning: function(message) {
            this.showMessage(message, 'warning');
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            this.showMessage(message, 'error');
        },
        
        /**
         * Show message with type
         */
        showMessage: function(message, type) {
            type = type || 'info';
            
            // Remove existing messages
            $('.paypal-message').remove();
            
            // Create message element
            const messageEl = $(`
                <div class="paypal-message paypal-message--${type}">
                    <span class="message-text">${message}</span>
                    <button class="message-close">&times;</button>
                </div>
            `);
            
            // Add to page
            $('#paypal-button-container').before(messageEl);
            
            // Auto-remove after delay (except for errors)
            if (type !== 'error') {
                setTimeout(() => {
                    messageEl.fadeOut(() => messageEl.remove());
                }, 5000);
            }
            
            // Handle close button
            messageEl.find('.message-close').on('click', () => {
                messageEl.fadeOut(() => messageEl.remove());
            });
        },
        
        /**
         * Format currency
         */
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('it-IT', {
                style: 'currency',
                currency: this.config.currency
            }).format(amount);
        },
        
        /**
         * Debug logging
         */
        log: function(message, level) {
            if (!this.config.debug) return;
            
            level = level || 'info';
            const timestamp = new Date().toISOString();
            
            console.log(`[ColitaliaPayPal ${level.toUpperCase()}] ${timestamp}: ${message}`);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        ColitaliaPayPal.init();
    });
    
    // Expose to global scope for debugging
    window.ColitaliaPayPal = ColitaliaPayPal;
    
})(jQuery);
