<?php
/**
 * Template Calcolatore Investimento Multiproprietà (Versione Finale Definitiva)
 * Questo template è stato corretto per risolvere i warning PHP, i conflitti JS
 * e per integrare correttamente la logica AJAX e gli stili.
 *
 * @package ColitaliaRealEstate
 * @version 1.5.5
 */

// Impedisce l'accesso diretto al file
if (!defined('ABSPATH')) {
    exit;
}

// Verifica che l'ID della proprietà sia stato passato tramite lo shortcode
if (!isset($atts['property_id'])) {
    return '<p class="colitalia-error">Errore: ID proprietà non specificato.</p>';
}

$property_id = intval($atts['property_id']);
$property = get_post($property_id);

// Utilizza le funzioni helper del plugin per recuperare i dati in modo sicuro come array
$property_info = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_info($property_id);
$pricing_info = \ColitaliaRealEstate\Cpt\PropertyCpt::get_property_pricing($property_id);

if (!$property || !$property_info || !$pricing_info) {
    return '<p class="colitalia-error">Errore: Proprietà non trovata o dati mancanti.</p>';
}

// Verifica che la proprietà sia effettivamente una multiproprietà
if (!has_term('multiproprieta', 'tipo_proprieta', $property_id)) {
    return '<p>Questa proprietà non è disponibile per investimenti multiproprietà.</p>';
}

// Calcola i dati degli investimenti esistenti
$timeshare_manager = new \ColitaliaRealEstate\Timeshare\TimeshareManager();
$existing_investments = $timeshare_manager->get_property_investments($property_id);
$total_owned_percentage = 0;
foreach ($existing_investments as $inv) {
    if (isset($inv->ownership_percentage) && is_numeric($inv->ownership_percentage)) {
        $total_owned_percentage += $inv->ownership_percentage;
    }
}
$available_percentage = 100 - $total_owned_percentage;

// Imposta i parametri della proprietà con valori di fallback per evitare errori
$weekly_price = !empty($pricing_info['weekly_price']) ? floatval($pricing_info['weekly_price']) : 0;
$min_investment = get_post_meta($property_id, '_min_investment_amount', true) ?: 50000;
$max_ownership = min(get_post_meta($property_id, '_max_ownership_percentage', true) ?: 25, $available_percentage);
?>

<div id="colitalia-investment-calculator-<?php echo esc_attr($property_id); ?>" class="investment-calculator-container">
    <div class="calculator-header">
        <h2>🏠 Calcolatore Investimento Multiproprietà</h2>
        <p class="property-title"><?php echo esc_html($property->post_title); ?></p>
        <?php if (!empty($property_info['location'])): ?>
            <p class="property-location">📍 <?php echo esc_html($property_info['location']); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="availability-status">
        <div class="availability-bar">
            <div class="owned-portion" style="width: <?php echo esc_attr($total_owned_percentage); ?>%;"></div>
            <div class="available-portion" style="width: <?php echo esc_attr($available_percentage); ?>%;"></div>
        </div>
        <div class="availability-info">
            <span class="owned-info">🔒 Posseduto: <?php echo number_format($total_owned_percentage, 1); ?>%</span>
            <span class="available-info">✅ Disponibile: <?php echo number_format($available_percentage, 1); ?>%</span>
        </div>
    </div>
    
    <?php if ($available_percentage > 0): ?>
    <div class="calculator-form">
        <div class="input-group">
            <label for="investment_amount">💰 Importo Investimento (€)</label>
            <input type="number" id="investment_amount" min="<?php echo esc_attr($min_investment); ?>" step="5000" value="<?php echo esc_attr($min_investment); ?>">
            <small>Minimo: €<?php echo number_format($min_investment, 0, ',', '.'); ?></small>
        </div>
        <div class="input-group">
            <label for="ownership_weeks">📅 Settimane di Proprietà</label>
            <select id="ownership_weeks">
                <?php for ($weeks = 1; $weeks <= 8; $weeks++): ?>
                    <option value="<?php echo $weeks; ?>" <?php selected($weeks, 4); ?>>
                        <?php echo $weeks; ?> settiman<?php echo $weeks == 1 ? 'a' : 'e'; ?> (<?php echo number_format(($weeks / 52) * 100, 1); ?>%)
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="input-group">
            <label>🏖️ Prezzo Settimanale Medio</label>
            <div class="readonly-field">€<?php echo number_format($weekly_price, 0, ',', '.'); ?></div>
        </div>
        <button type="button" id="calculate-roi" class="calculate-button">📊 Calcola ROI</button>
    </div>
    <?php endif; ?>
    
    <div id="calculator-results" class="calculator-results" style="display: none;">
        </div>
    
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    const container = $('#colitalia-investment-calculator-<?php echo esc_attr($property_id); ?>');
    if (!container.length) return;

    // ===============================================================
    // CORREZIONE CHIAVE: Definiamo le variabili AJAX direttamente qui
    // ===============================================================
    const colitalia_ajax = {
        ajax_url: "<?php echo admin_url('admin-ajax.php'); ?>",
        nonce: "<?php echo wp_create_nonce('colitalia_investment_nonce'); ?>"
    };
    
    let roiChart = null;

    const calculator = {
        propertyId: <?php echo $property_id; ?>,
        
        init: function() {
            container.find('#calculate-roi').on('click', this.calculateROI.bind(this));
        },
        
        calculateROI: function() {
            const investmentAmount = parseFloat(container.find('#investment_amount').val()) || 0;
            const ownershipWeeks = parseInt(container.find('#ownership_weeks').val()) || 1;

            if (investmentAmount < <?php echo $min_investment; ?>) {
                alert('L\'importo minimo di investimento è €<?php echo number_format($min_investment, 0, ",", "."); ?>');
                return;
            }

            const button = container.find('#calculate-roi');
            button.html('⏳ Calcolando...').prop('disabled', true);

            $.ajax({
                url: colitalia_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'calculate_investment_roi',
                    nonce: colitalia_ajax.nonce,
                    property_id: this.propertyId,
                    investment_amount: investmentAmount,
                    ownership_weeks: ownershipWeeks
                },
                success: this.displayResults.bind(this),
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown);
                    alert('Errore nel calcolo. Riprova. Controlla la console del browser per dettagli.');
                },
                complete: function() {
                    button.html('📊 Calcola ROI').prop('disabled', false);
                }
            });
        },
        
        displayResults: function(response) {
            const resultsContainer = container.find('#calculator-results');
            if (!response.success) {
                alert('Errore: ' + (response.data.message || response.data));
                resultsContainer.hide();
                return;
            }
            
            const data = response.data;
            const resultsHtml = `
                <div class="results-header"><h3>📈 Risultati Investimento</h3></div>
                <div class="results-grid">
                    <div class="result-card summary-card">
                        <h4>💎 Riepilogo</h4>
                        <div class="result-item"><span class="label">Investimento:</span><span class="value">€${this.formatNumber(data.investment_amount)}</span></div>
                        <div class="result-item"><span class="label">% Proprietà:</span><span class="value">${data.ownership_percentage.toFixed(1)}%</span></div>
                        <div class="result-item highlight"><span class="label">ROI Annuale:</span><span class="value">${data.annual_roi.toFixed(1)}%</span></div>
                        <div class="result-item highlight"><span class="label">Revenue Annuale:</span><span class="value">€${this.formatNumber(data.owner_annual_revenue)}</span></div>
                    </div>
                    <div class="result-card">
                        <h4>💼 Dettagli</h4>
                        <div class="result-item"><span class="label">Revenue Mensile:</span><span class="value">€${this.formatNumber(data.monthly_revenue)}</span></div>
                        <div class="result-item"><span class="label">Tempo Recupero:</span><span class="value">${data.payback_years.toFixed(1)} anni</span></div>
                    </div>
                </div>
                <div class="projection-section">
                    <h4>📊 Proiezioni a 10 Anni</h4>
                    <div class="projection-chart"><canvas id="roi-chart-<?php echo esc_attr($property_id); ?>" width="400" height="200"></canvas></div>
                </div>
            `;
            resultsContainer.html(resultsHtml).slideDown();
            this.generateChart(data.projections);
        },
        
        generateChart: function(projections) {
            const canvasId = 'roi-chart-<?php echo esc_attr($property_id); ?>';
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            if (roiChart) {
                roiChart.destroy();
            }

            const labels = Object.keys(projections).map(year => `Anno ${year}`);
            const revenueData = Object.values(projections).map(p => p.annual_revenue);
            const valueData = Object.values(projections).map(p => p.property_value);

            roiChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue Annuale',
                        data: revenueData,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        yAxisID: 'y',
                    }, {
                        label: 'Valore Proprietà',
                        data: valueData,
                        borderColor: 'rgb(153, 102, 255)',
                        tension: 0.1,
                        yAxisID: 'y1',
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: { display: true, text: 'Revenue (€)' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: { display: true, text: 'Valore Proprietà (€)' },
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });
        },
        
        formatNumber: function(num) {
            return new Intl.NumberFormat('it-IT', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
        }
    };
    
    calculator.init();
});
</script>

<style>
/* Ripristino degli stili originali e corretti */
.investment-calculator-container { max-width: 800px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.calculator-header { text-align: center; margin-bottom: 25px; }
.calculator-header h2 { margin: 0 0 10px 0; font-size: 1.8rem; color: #1e3a56; }
.property-title { font-size: 1.2rem; font-weight: 600; color: #34495e; }
.property-location { color: #7f8c8d; }
.availability-status { margin-bottom: 25px; }
.availability-bar { height: 12px; background-color: #e9ecef; border-radius: 6px; overflow: hidden; display: flex; }
.owned-portion { background-color: #e74c3c; }
.available-portion { background-color: #2ecc71; }
.availability-info { display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.85rem; color: #555; }
.calculator-form { background-color: #f8f9fa; padding: 25px; border-radius: 12px; border: 1px solid #dee2e6; }
.input-group { margin-bottom: 18px; }
.input-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #34495e; font-size: 0.9rem; }
.input-group input, .input-group select { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 8px; font-size: 1rem; transition: border-color 0.2s, box-shadow 0.2s; }
.input-group input:focus, .input-group select:focus { outline: none; border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
.readonly-field { padding: 12px; background-color: #e9ecef; border-radius: 8px; font-size: 1rem; font-weight: 600; color: #495057; }
.calculate-button { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
.calculate-button:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
.calculate-button:disabled { background: #bdc3c7; cursor: not-allowed; }
.calculator-results { margin-top: 30px; }
.results-header h3 { text-align: center; color: #27ae60; }
.results-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
.result-card { background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #2ecc71; }
.result-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ecf0f1; font-size: 0.95rem; }
.result-item:last-child { border: none; }
.result-item .value { font-weight: 600; }
.result-item.highlight { font-size: 1.1rem; font-weight: bold; color: #2c3e50; }
.projection-section { margin-top: 30px; }
@media (max-width: 600px) { .results-grid { grid-template-columns: 1fr; } }
</style>