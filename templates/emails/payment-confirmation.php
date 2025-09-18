<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Confermato - <?php echo esc_html($site_name); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .header .checkmark {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }
        .content {
            padding: 40px 30px;
        }
        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            text-align: center;
        }
        .success-message h2 {
            margin: 0 0 10px 0;
            color: #155724;
        }
        .payment-receipt {
            background-color: #f8f9fa;
            border: 2px dashed #28a745;
            padding: 25px;
            margin: 25px 0;
            border-radius: 8px;
            text-align: center;
        }
        .receipt-title {
            color: #28a745;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .booking-details {
            display: table;
            width: 100%;
            margin: 20px 0;
        }
        .detail-row {
            display: table-row;
        }
        .detail-label {
            display: table-cell;
            font-weight: 600;
            color: #555;
            padding: 8px 15px 8px 0;
            width: 45%;
        }
        .detail-value {
            display: table-cell;
            padding: 8px 0;
            color: #333;
        }
        .payment-summary {
            background-color: #e8f5e8;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .payment-total {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 18px;
            color: #28a745;
            border-top: 2px solid #28a745;
            padding-top: 15px;
            margin-top: 15px;
        }
        .remaining-balance {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .remaining-balance h3 {
            color: #d68910;
            margin-top: 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0, 184, 148, 0.4);
            transition: all 0.3s ease;
        }
        .next-steps {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .next-steps h3 {
            color: #1976d2;
            margin-top: 0;
        }
        .footer {
            background-color: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
            font-size: 14px;
        }
        .footer a {
            color: #74b9ff;
            text-decoration: none;
        }
        
        @media (max-width: 600px) {
            .content {
                padding: 20px 15px;
            }
            .booking-details {
                display: block;
            }
            .detail-row {
                display: block;
                margin-bottom: 10px;
            }
            .detail-label {
                display: block;
                font-size: 12px;
                text-transform: uppercase;
                color: #666;
            }
            .detail-value {
                display: block;
                font-size: 16px;
                font-weight: 600;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <span class="checkmark">✅</span>
            <h1>Pagamento Confermato</h1>
            <p>La tua prenotazione è stata completata!</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="success-message">
                <h2>🎉 Perfetto, <?php echo esc_html($customer_name); ?>!</h2>
                <p>Il tuo pagamento è stato elaborato con successo. La prenotazione è ora <strong>confermata</strong>.</p>
            </div>
            
            <!-- Payment Receipt -->
            <div class="payment-receipt">
                <div class="receipt-title">📄 Ricevuta di Pagamento</div>
                
                <div class="booking-details">
                    <div class="detail-row">
                        <div class="detail-label">Numero Transazione:</div>
                        <div class="detail-value"><strong><?php echo esc_html($transaction_id); ?></strong></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Data Pagamento:</div>
                        <div class="detail-value"><?php echo esc_html($payment_date); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Metodo Pagamento:</div>
                        <div class="detail-value"><?php echo esc_html($payment_method); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Codice Prenotazione:</div>
                        <div class="detail-value"><strong><?php echo esc_html($booking_code); ?></strong></div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Summary -->
            <div class="booking-card" style="background-color: #f8f9fa; border-left: 4px solid #00b894; padding: 20px; margin: 25px 0; border-radius: 5px;">
                <h2 style="margin-top: 0; color: #2c3e50;">
                    🏠 <?php echo esc_html($property_title); ?>
                </h2>
                
                <div class="booking-details">
                    <div class="detail-row">
                        <div class="detail-label">Check-in:</div>
                        <div class="detail-value"><?php echo esc_html($check_in); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Check-out:</div>
                        <div class="detail-value"><?php echo esc_html($check_out); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Durata:</div>
                        <div class="detail-value"><?php echo $nights; ?> <?php echo $nights == 1 ? 'notte' : 'notti'; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Ospiti:</div>
                        <div class="detail-value"><?php echo $guests; ?> <?php echo $guests == 1 ? 'ospite' : 'ospiti'; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Summary -->
            <div class="payment-summary">
                <h3 style="margin-top: 0; color: #28a745;">💰 Riepilogo Pagamento</h3>
                <div class="payment-row">
                    <span>Totale prenotazione:</span>
                    <span><?php echo esc_html($total_price); ?></span>
                </div>
                <div class="payment-row">
                    <span>Deposito pagato:</span>
                    <span><?php echo esc_html($deposit_amount); ?></span>
                </div>
                <div class="payment-total">
                    <span>✅ PAGATO</span>
                    <span><?php echo esc_html($deposit_amount); ?></span>
                </div>
            </div>
            
            <!-- Remaining Balance -->
            <?php if (floatval($remaining_balance) > 0): ?>
            <div class="remaining-balance">
                <h3>⏰ Saldo Rimanente</h3>
                <p>Il saldo rimanente di <strong><?php echo esc_html($remaining_balance); ?></strong> dovrà essere pagato direttamente alla struttura al momento del check-in.</p>
                <p><small>Ti invieremo un promemoria alcuni giorni prima del tuo arrivo con tutte le informazioni necessarie.</small></p>
            </div>
            <?php endif; ?>
            
            <!-- Next Steps -->
            <div class="next-steps">
                <h3>🚀 Prossimi Passi</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>Conferma ricevuta:</strong> Conserva questa email come ricevuta</li>
                    <li><strong>Documenti:</strong> Prepara un documento d'identità valido</li>
                    <li><strong>Check-in:</strong> Ti invieremo le istruzioni dettagliate qualche giorno prima del tuo arrivo</li>
                    <li><strong>Assistenza:</strong> Il nostro team è sempre disponibile per qualsiasi domanda</li>
                </ul>
            </div>
            
            <!-- CTA Button -->
            <?php if ($booking_url): ?>
            <div style="text-align: center; margin: 30px 0;">
                <a href="<?php echo esc_url($booking_url); ?>" class="cta-button">
                    Visualizza Prenotazione Completa
                </a>
            </div>
            <?php endif; ?>
            
            <div style="border-top: 1px solid #eee; padding-top: 25px; margin-top: 30px;">
                <p>🙏 <strong>Grazie per aver scelto <?php echo esc_html($site_name); ?>!</strong></p>
                <p>Non vediamo l'ora di ospitarti. Se hai domande o richieste speciali, non esitare a contattarci.</p>
            </div>
            
            <p style="margin-top: 30px;">
                Cordiali saluti,<br>
                <strong>Il Team di <?php echo esc_html($site_name); ?></strong>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p style="font-size: 16px; margin-bottom: 20px;">
                <strong>Hai bisogno di aiuto?</strong>
            </p>
            
            <div style="margin: 20px 0;">
                <a href="<?php echo esc_url($site_url); ?>">🌐 Sito Web</a>
                <span style="margin: 0 15px;">|</span>
                <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>">✉️ Email</a>
                <?php if (get_option('colitalia_contact_phone')): ?>
                <span style="margin: 0 15px;">|</span>
                <a href="tel:<?php echo esc_attr(get_option('colitalia_contact_phone')); ?>">📞 <?php echo esc_html(get_option('colitalia_contact_phone')); ?></a>
                <?php endif; ?>
            </div>
            
            <?php if (get_option('colitalia_emergency_contact')): ?>
            <p style="background-color: #e74c3c; padding: 10px; border-radius: 5px; margin: 20px 0;">
                <strong>🚨 Emergenze:</strong> <a href="tel:<?php echo esc_attr(get_option('colitalia_emergency_contact')); ?>" style="color: white;"><?php echo esc_html(get_option('colitalia_emergency_contact')); ?></a>
            </p>
            <?php endif; ?>
            
            <p>
                <?php echo esc_html($site_name); ?><br>
                <?php if (get_option('colitalia_company_address')): ?>
                    <?php echo nl2br(esc_html(get_option('colitalia_company_address'))); ?>
                <?php endif; ?>
            </p>
            
            <p style="font-size: 12px; opacity: 0.8; margin-top: 20px;">
                Questa email di conferma è stata inviata a <?php echo esc_html($booking->email ?? $customer_email ?? ''); ?>.
                <br>Transazione ID: <?php echo esc_html($transaction_id); ?>
            </p>
            
            <p style="font-size: 12px; opacity: 0.6;">
                © <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. Tutti i diritti riservati.
            </p>
        </div>
    </div>
</body>
</html>
