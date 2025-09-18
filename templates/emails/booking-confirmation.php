<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferma Prenotazione - <?php echo esc_html($site_name); ?></title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 25px;
        }
        .booking-card {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
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
            width: 40%;
        }
        .detail-value {
            display: table-cell;
            padding: 8px 0;
            color: #333;
        }
        .price-summary {
            background-color: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .price-total {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 18px;
            color: #2c3e50;
            border-top: 2px solid #ddd;
            padding-top: 15px;
            margin-top: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }
        .important-info {
            background-color: #fff9c4;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .important-info h3 {
            color: #d68910;
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
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #74b9ff;
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
            <h1><?php echo esc_html($site_name); ?></h1>
            <p>Conferma della tua prenotazione</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Ciao <?php echo esc_html($customer_name); ?>! 🎉
            </div>
            
            <p>Grazie per aver scelto <strong><?php echo esc_html($site_name); ?></strong>! La tua prenotazione è stata ricevuta con successo.</p>
            
            <!-- Booking Card -->
            <div class="booking-card">
                <h2 style="margin-top: 0; color: #2c3e50;">
                    🏠 <?php echo esc_html($property_title); ?>
                </h2>
                
                <div class="booking-details">
                    <div class="detail-row">
                        <div class="detail-label">Codice Prenotazione:</div>
                        <div class="detail-value">
                            <strong><?php echo esc_html($booking_code); ?></strong>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Check-in:</div>
                        <div class="detail-value"><?php echo esc_html($check_in); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Check-out:</div>
                        <div class="detail-value"><?php echo esc_html($check_out); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Durata soggiorno:</div>
                        <div class="detail-value"><?php echo $nights; ?> <?php echo $nights == 1 ? 'notte' : 'notti'; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Numero ospiti:</div>
                        <div class="detail-value"><?php echo $guests; ?> <?php echo $guests == 1 ? 'ospite' : 'ospiti'; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Località:</div>
                        <div class="detail-value"><?php echo esc_html($property_location); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Stato:</div>
                        <div class="detail-value">
                            <span class="status-badge status-<?php echo $booking->status === 'paid' ? 'confirmed' : 'pending'; ?>">
                                <?php echo esc_html($payment_status); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Price Summary -->
            <div class="price-summary">
                <h3 style="margin-top: 0; color: #2c3e50;">Riepilogo Costi</h3>
                <div class="price-row">
                    <span>Totale soggiorno:</span>
                    <span><?php echo esc_html($total_price); ?></span>
                </div>
                <div class="price-row">
                    <span>Deposito richiesto (<?php echo get_option('colitalia_booking_deposit_percentage', 30); ?>%):</span>
                    <span><?php echo esc_html($deposit_amount); ?></span>
                </div>
                <?php if ($booking->status !== 'paid'): ?>
                <div class="price-total">
                    <span>Saldo rimanente:</span>
                    <span><?php echo colitalia_format_currency($booking->total_price - $booking->deposit_amount); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($special_requests): ?>
            <div class="booking-card">
                <h3 style="color: #2c3e50;">Richieste Speciali</h3>
                <p><?php echo nl2br(esc_html($special_requests)); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Important Info -->
            <div class="important-info">
                <h3>⚠️ Informazioni Importanti</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <?php if ($booking->status === 'pending'): ?>
                    <li><strong>Pagamento:</strong> Completa il pagamento del deposito per confermare la prenotazione.</li>
                    <?php endif; ?>
                    <li><strong>Check-in:</strong> Dalle ore 15:00</li>
                    <li><strong>Check-out:</strong> Entro le ore 10:00</li>
                    <li><strong>Documenti:</strong> Porta un documento d'identità valido</li>
                    <li><strong>Cancellazione:</strong> Gratuita fino a <?php echo get_option('colitalia_cancellation_policy_days', 7); ?> giorni prima del check-in</li>
                </ul>
            </div>
            
            <!-- CTA Button -->
            <?php if ($booking_url): ?>
            <div style="text-align: center; margin: 30px 0;">
                <a href="<?php echo esc_url($booking_url); ?>" class="cta-button">
                    Gestisci Prenotazione
                </a>
            </div>
            <?php endif; ?>
            
            <p>Se hai domande o necessiti di assistenza, non esitare a contattarci. Il nostro team è sempre disponibile per aiutarti!</p>
            
            <p style="margin-top: 30px;">
                Cordiali saluti,<br>
                <strong>Il Team di <?php echo esc_html($site_name); ?></strong>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="social-links">
                <a href="<?php echo esc_url($site_url); ?>">🌐 Sito Web</a>
                <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>">✉️ Email</a>
                <?php if (get_option('colitalia_contact_phone')): ?>
                <a href="tel:<?php echo esc_attr(get_option('colitalia_contact_phone')); ?>">📞 Telefono</a>
                <?php endif; ?>
            </div>
            
            <p>
                <?php echo esc_html($site_name); ?><br>
                <?php if (get_option('colitalia_company_address')): ?>
                    <?php echo nl2br(esc_html(get_option('colitalia_company_address'))); ?>
                <?php endif; ?>
            </p>
            
            <p style="font-size: 12px; opacity: 0.8; margin-top: 20px;">
                Questa email è stata inviata a <?php echo esc_html($booking->email); ?> perché hai effettuato una prenotazione con noi.
                Per non ricevere più queste comunicazioni, <a href="#">clicca qui</a>.
            </p>
            
            <p style="font-size: 12px; opacity: 0.6;">
                © <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. Tutti i diritti riservati.
            </p>
        </div>
    </div>
</body>
</html>
