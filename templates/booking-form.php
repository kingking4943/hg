<?php
/**
 * Template Form Prenotazione Multi-Step (Versione Finale Definitiva e Completa)
 *
 * Questo template gestisce l'intero processo di prenotazione, dalla selezione
 * delle date al pagamento finale con PayPal.
 *
 * @package ColitaliaRealEstate
 * @version 1.5.5
 */

// Impedisce l'accesso diretto al file
defined('ABSPATH') || exit;

// 1. RECUPERO DEI DATI INIZIALI
// ===================================================================

// Ottiene l'ID della proprietà. Se non è passato tramite shortcode, usa l'ID della pagina corrente.
$property_id = isset($property_id) ? $property_id : get_the_ID();
if (!$property_id) {
    echo '<p class="colitalia-error">Errore: ID della proprietà non specificato.</p>';
    return;
}

// Recupera il numero massimo di ospiti
$max_guests = get_post_meta($property_id, '_property_max_guests', true);
if (empty($max_guests) || !is_numeric($max_guests)) {
    $max_guests = 8; // Valore di fallback
}

// Recupera i servizi extra per questa proprietà dal database
global $wpdb;
$services_table = $wpdb->prefix . 'colitalia_services';
$services = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$services_table} WHERE property_id = %d AND is_active = 1 ORDER BY sort_order ASC",
        $property_id
    ),
    ARRAY_A
);

// Recupera il Client ID di PayPal in base alla modalità (Sandbox o Live)
$paypal_mode = get_option('colitalia_paypal_mode', 'sandbox');
$paypal_client_id = ($paypal_mode === 'sandbox')
    ? get_option('colitalia_paypal_client_id') // Usa le credenziali corrette per Sandbox
    : get_option('colitalia_paypal_client_id'); // Assumendo che ci sia un'opzione separata per Live o che sia la stessa

?>

<div class="colitalia-booking-form-container" data-property-id="<?php echo esc_attr($property_id); ?>" data-max-guests="<?php echo esc_attr($max_guests); ?>">
    
    <div class="booking-steps-indicator">
        <div class="step active" data-step="1">
            <div class="step-number">1</div>
            <div class="step-label">Date e Ospiti</div>
        </div>
        <div class="step" data-step="2">
            <div class="step-number">2</div>
            <div class="step-label">Servizi Extra</div>
        </div>
        <div class="step" data-step="3">
            <div class="step-number">3</div>
            <div class="step-label">Dati Personali</div>
        </div>
        <div class="step" data-step="4">
            <div class="step-number">4</div>
            <div class="step-label">Conferma</div>
        </div>
    </div>

    <form id="colitalia-booking-form" onsubmit="return false;">
        
        <?php wp_nonce_field('colitalia_booking_nonce', '_nonce'); ?>
        <input type="hidden" name="property_id" value="<?php echo esc_attr($property_id); ?>">
        
        <div class="booking-step active" data-step="1">
            <h4>Seleziona le date e il numero di ospiti</h4>
            <div class="booking-fields-grid">
                <div class="date-selection">
                    <label>Date del soggiorno *</label>
                    <div class="date-inputs">
                        <input type="date" name="date_from" min="<?php echo date('Y-m-d'); ?>" required aria-label="Data di check-in">
                        <input type="date" name="date_to" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required aria-label="Data di check-out">
                    </div>
                </div>
                <div class="guests-selection-simple">
                    <label for="guests-total">Ospiti *</label>
                    <select name="guests" id="guests-total" required>
                        <?php for ($i = 1; $i <= $max_guests; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo ($i > 1) ? 'ospiti' : 'ospite'; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="price-summary">
                <div class="availability-status" style="display: none;"></div>
            </div>
            <div class="step-actions">
                <button type="button" class="btn btn-primary next-step" disabled>Procedi →</button>
            </div>
        </div>

        <div class="booking-step" data-step="2" style="display: none;">
            <h4>Aggiungi dettagli al tuo soggiorno</h4>
            <?php if (!empty($services)): ?>
                <div class="services-grid">
                    <h5>Servizi Extra Disponibili</h5>
                    <?php foreach ($services as $service): ?>
                        <div class="service-item <?php if($service['is_mandatory']) echo 'mandatory'; ?>">
                            <label>
                                <input 
                                    type="checkbox" 
                                    name="services[]" 
                                    value="<?php echo esc_attr($service['id']); ?>"
                                    <?php if($service['is_mandatory']) echo 'checked disabled'; ?>
                                > 
                                <?php echo esc_html($service['service_name']); ?>
                                <span>(+<?php echo esc_html(number_format_i18n($service['price'], 2)); ?> €)</span>
                                <?php if($service['is_mandatory']) echo '<small class="mandatory-label">Obbligatorio</small>'; ?>
                            </label>
                            <small class="service-description"><?php echo esc_html($service['service_description']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="special-requests">
                <h5>Richieste Speciali (opzionale)</h5>
                <textarea name="special_requests" placeholder="Hai qualche richiesta particolare? Es. culla per bambini, arrivo in tarda serata, ecc."></textarea>
            </div>
            <div class="step-actions">
                <button type="button" class="btn btn-secondary prev-step">← Indietro</button>
                <button type="button" class="btn btn-primary next-step">Continua →</button>
            </div>
        </div>

        <div class="booking-step" data-step="3" style="display: none;">
            <h4>Chi prenota?</h4>
            <div class="form-row">
                <div class="form-group"><label>Nome *</label><input type="text" name="first_name" required></div>
                <div class="form-group"><label>Cognome *</label><input type="text" name="last_name" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
                
                <div class="form-group">
                    <label>Telefono *</label>
                    <div class="phone-input-group">
                        <input type="text" name="phone_prefix" class="phone-prefix" placeholder="+" required value="+">
                        <input type="tel" name="phone_number" class="phone-number" placeholder="Numero" required>
                    </div>
                </div>
                </div>
            <div class="form-group">
                <label class="consent-label">
                    <input type="checkbox" name="privacy_consent" required> 
                    Accetto il trattamento dei dati personali secondo la <a href="<?php echo get_privacy_policy_url(); ?>" target="_blank">Privacy Policy</a> *
                </label>
            </div>
            <div class="step-actions">
                <button type="button" class="btn btn-secondary prev-step">← Indietro</button>
                <button type="button" class="btn btn-primary next-step" disabled>Vai alla conferma →</button>
            </div>
        </div>
        
        <div class="booking-step" data-step="4" style="display: none;">
            <h4>Riepilogo e Pagamento</h4>
            <div id="final-summary" class="final-summary-card">
                <p class="loading-text">Caricamento riepilogo...</p>
            </div>
            
            <?php if (!empty($paypal_client_id)): ?>
                <div id="paypal-button-container" style="margin-top: 20px;"></div>
                <small class="paypal-info">Verrai reindirizzato a PayPal per completare il pagamento in modo sicuro.</small>
            <?php else: ?>
                <p class="colitalia-error">Il sistema di pagamento non è al momento configurato. Si prega di riprovare più tardi.</p>
            <?php endif; ?>

            <div class="step-actions">
                <button type="button" class="btn btn-secondary prev-step">← Modifica Dati</button>
            </div>
        </div>
    </form>
</div>

<style>
    .phone-input-group {
        display: flex;
        align-items: center;
    }
    .phone-input-group .phone-prefix {
        flex: 0 0 80px; /* Larghezza ridotta per l'input del prefisso */
        border-right: none;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        text-align: center;
    }
    .phone-input-group .phone-number {
        flex: 1 1 auto;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
</style>

<?php if (!empty($paypal_client_id)): ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo esc_attr($paypal_client_id); ?>&currency=EUR&intent=capture&disable-funding=card"></script>
<?php endif; ?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const container = $('.colitalia-booking-form-container');
    if (!container.length) return;

    const form = $('#colitalia-booking-form');
    let currentStep = 1;
    let finalAmount = 0;
    let currentBookingId = null;

    function navigateToStep(newStep) {
        if (newStep > currentStep && !validateStep(currentStep)) {
            return; // Blocca se la validazione fallisce
        }
        currentStep = newStep;

        $('.booking-step').removeClass('active').hide();
        $(`.booking-step[data-step="${currentStep}"]`).addClass('active').show();

        $('.booking-steps-indicator .step').removeClass('active completed');
        $('.booking-steps-indicator .step').each(function() {
            const stepNum = $(this).data('step');
            if (stepNum < currentStep) {
                $(this).addClass('completed').find('.step-number').html('&#10003;'); // Checkmark
            } else if (stepNum === currentStep) {
                $(this).addClass('active');
            }
        });
        
        if (currentStep === 4) generateFinalSummary();
        if (currentStep === 3) validateStep3Realtime();
    }

    function validateStep(step) {
        let isValid = true;
        $(`.booking-step[data-step="${step}"] [required]`).each(function() {
            const isCheckbox = $(this).is(':checkbox');
            const isEmpty = !$(this).val() || $(this).val().trim() === '';
            const isUnchecked = isCheckbox && !$(this).is(':checked');

            if (isEmpty || isUnchecked) {
                isValid = false;
                $(this).closest('.form-group, .date-selection, .guests-selection-simple').addClass('has-error');
            } else {
                $(this).closest('.form-group, .date-selection, .guests-selection-simple').removeClass('has-error');
            }
        });
        return isValid;
    }

    container.on('click', '.next-step', () => navigateToStep(currentStep + 1));
    container.on('click', '.prev-step', () => navigateToStep(currentStep - 1));

    let debounceTimer;
    function triggerCalculation() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(checkAvailabilityAndPrice, 500);
    }

    function checkAvailabilityAndPrice() {
        const dateFrom = form.find('[name="date_from"]').val();
        const dateTo = form.find('[name="date_to"]').val();
        const availabilityStatus = form.find('.availability-status');
        const nextButton = form.find('.booking-step[data-step="1"] .next-step');

        if (!dateFrom || !dateTo || new Date(dateTo) <= new Date(dateFrom)) {
            availabilityStatus.hide();
            nextButton.prop('disabled', true);
            return;
        }

        availabilityStatus.show().removeClass('available unavailable').addClass('checking').html('<span></span>Verifica disponibilità...');
        nextButton.prop('disabled', true);
        
        // Colleziona anche i servizi, perché potrebbero influire sul prezzo
        const services = [];
        form.find('input[name="services[]"]:checked').each(function() {
            services.push($(this).val());
        });

        $.ajax({
            url: "<?php echo admin_url('admin-ajax.php'); ?>",
            type: 'POST',
            data: form.serialize() + '&action=colitalia_check_and_price',
            success: function(response) {
                if (response.success) {
                    const pricing = response.data;
                    availabilityStatus.removeClass('checking unavailable').addClass('available').html(`&#10003; Date disponibili!<br><strong>Totale (${pricing.nights} notti): ${formatCurrency(pricing.total_price)}</strong>`);
                    nextButton.prop('disabled', false);
                } else {
                    availabilityStatus.removeClass('checking available').addClass('unavailable').text(`✕ ${response.data.message || "Date non disponibili."}`);
                }
            },
            error: function() {
                availabilityStatus.removeClass('checking available').addClass('unavailable').text("⚠️ Errore di comunicazione. Riprova.");
            }
        });
    }

    form.find('input[type="date"], select[name="guests"], input[name^="services"]').on('change', triggerCalculation);

    function validateStep3Realtime() {
        const nextButton = $(`.booking-step[data-step="3"] .next-step`);
        let isValid = true;
        $(`.booking-step[data-step="3"] [required]`).each(function() {
            if (($(this).is(':checkbox') && !$(this).is(':checked')) || (!$(this).is(':checkbox') && $(this).val().trim() === '')) {
                isValid = false;
            }
        });
        nextButton.prop('disabled', !isValid);
    }
    $(`.booking-step[data-step="3"] [required]`).on('keyup change input', validateStep3Realtime);

    function generateFinalSummary() {
        const summaryDiv = $('#final-summary');
        summaryDiv.html('<p class="loading-text">Calcolo del totale finale...</p>');

        $.ajax({
            url: "<?php echo admin_url('admin-ajax.php'); ?>",
            type: 'POST',
            data: form.serialize() + '&action=colitalia_check_and_price',
            success: function(response) {
                if (response.success) {
                    finalAmount = response.data.total_price;
                    const pricing = response.data;
                    const customerName = `${form.find('[name="first_name"]').val()} ${form.find('[name="last_name"]').val()}`;
                    
                    let summaryHTML = `
                        <h4>Riepilogo Prenotazione</h4>
                        <div class="summary-item"><span>Date:</span> <strong>${form.find('[name="date_from"]').val()} al ${form.find('[name="date_to"]').val()} (${pricing.nights} notti)</strong></div>
                        <div class="summary-item"><span>Ospiti:</span> <strong>${form.find('[name="guests"]').val()}</strong></div>
                        <div class="summary-item"><span>Nominativo:</span> <strong>${customerName}</strong></div>
                        <hr>
                        <div class="summary-total">
                            <span>Totale da Pagare:</span>
                            <strong>${formatCurrency(finalAmount)}</strong>
                        </div>
                    `;
                    summaryDiv.html(summaryHTML);
                    initPayPalButtons();
                } else {
                    summaryDiv.html('<p class="colitalia-error">Impossibile caricare il riepilogo. Torna indietro e verifica i dati.</p>');
                }
            }
        });
    }

    function initPayPalButtons() {
        if (typeof paypal === 'undefined' || !$('#paypal-button-container').length) {
            $('#paypal-button-container').html('<p class="colitalia-error">Errore: Sistema di pagamento non disponibile.</p>');
            return;
        }

        $('#paypal-button-container').empty(); // Pulisce il contenitore
        paypal.Buttons({
            createOrder: (data, actions) => {
                // Prima crea la prenotazione nel DB con stato "pending"
                return $.ajax({
                    url: "<?php echo admin_url('admin-ajax.php'); ?>",
                    type: 'POST',
                    data: form.serialize() + '&action=colitalia_create_booking'
                }).then(function(response) {
                    if (response.success) {
                        currentBookingId = response.data.booking_id;
                        // Poi crea l'ordine su PayPal
                        return actions.order.create({
                            purchase_units: [{
                                amount: {
                                    value: finalAmount.toFixed(2),
                                    currency_code: 'EUR'
                                },
                                custom_id: currentBookingId // Associa l'ID prenotazione
                            }]
                        });
                    }
                    return Promise.reject(new Error(response.data.message));
                });
            },
            onApprove: (data, actions) => {
                return actions.order.capture().then(function(details) {
                    // Se il pagamento è approvato, conferma la prenotazione nel DB
                    return $.ajax({
                        url: "<?php echo admin_url('admin-ajax.php'); ?>",
                        type: 'POST',
                        data: {
                            action: 'colitalia_confirm_payment',
                            _nonce: $('#_nonce').val(),
                            booking_id: currentBookingId,
                            paypal_order_id: details.id
                        }
                    }).done(function(response) {
                        // Reindirizza alla pagina di ringraziamento
                        window.location.href = "/pagina-di-ringraziamento/?booking_code=" + response.data.booking_code;
                    });
                });
            },
            onError: function (err) {
                alert("Si è verificato un errore con PayPal. Si prega di riprovare.");
                console.error('PayPal Error:', err);
            }
        }).render('#paypal-button-container');
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(amount);
    }
});
</script>