<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promemoria Arrivo - <?php echo esc_html($site_name); ?></title>
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
            background: linear-gradient(135deg, #fd79a8 0%, #fdcb6e 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .header .icon {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }
        .content {
            padding: 40px 30px;
        }
        .countdown {
            background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%);
            color: white;
            text-align: center;
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
        }
        .countdown h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .countdown .days {
            font-size: 48px;
            font-weight: bold;
            display: block;
            margin: 10px 0;
        }
        .booking-summary {
            background-color: #f8f9fa;
            border-left: 4px solid #fd79a8;
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
        .checkin-info {
            background-color: #e8f5e8;
            border: 2px solid #28a745;
            padding: 20px;
            border-radius: 10px;
            margin: 25px 0;
        }
        .checkin-info h3 {
            color: #28a745;
            margin-top: 0;
        }
        .important-notes {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .important-notes h3 {
            color: #d68910;
            margin-top: 0;
        }
        .contact-card {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
            text-align: center;
        }
        .contact-card h3 {
            color: #1976d2;
            margin-top: 0;
        }
        .contact-button {
            display: inline-block;
            background-color: #2196f3;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 20px;
            margin: 5px;
            font-size: 14px;
        }
        .weather-tip {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 25px 0;
            text-align: center;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #fd79a8 0%, #fdcb6e 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(253, 121, 168, 0.4);
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
            <span class="icon">🧳</span>
            <h1>Il Tuo Viaggio Sta Arrivando!</h1>
            <p>Preparati per un soggiorno fantastico</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <p style="font-size: 18px; color: #2c3e50;">
                Ciao <?php echo esc_html($customer_name); ?>! 👋
            </p>
            
            <p>Il momento del tuo soggiorno si avvicina e non vediamo l'ora di accoglierti!</p>
            
            <!-- Countdown -->
            <div class="countdown">
                <h2>🕐 Mancano Solo</h2>
                <?php 
                $days_left = (new DateTime($check_in))->diff(new DateTime())->days;
                ?>
                <span class="days"><?php echo $days_left; ?></span>
                <p><?php echo $days_left == 1 ? 'giorno' : 'giorni'; ?> al check-in!</p>
            </div>
            
            <!-- Booking Summary -->
            <div class="booking-summary">
                <h2 style="margin-top: 0; color: #2c3e50;">
                    🏠 <?php echo esc_html($property_title); ?>
                </h2>
                
                <div class="booking-details">
                    <div class="detail-row">
                        <div class="detail-label">Codice Prenotazione:</div>
                        <div class="detail-value"><strong><?php echo esc_html($booking_code); ?></strong></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Check-in:</div>
                        <div class="detail-value"><strong><?php echo esc_html($check_in); ?></strong> (dalle ore 15:00)</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Check-out:</div>
                        <div class="detail-value"><?php echo esc_html($check_out); ?> (entro le ore 10:00)</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Località:</div>
                        <div class="detail-value">📍 <?php echo esc_html($property_location); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Check-in Instructions -->
            <?php if ($checkin_instructions): ?>
            <div class="checkin-info">
                <h3>🔑 Istruzioni Check-in</h3>
                <div style="white-space: pre-line;"><?php echo esc_html($checkin_instructions); ?></div>
            </div>
            <?php else: ?>
            <div class="checkin-info">
                <h3>🔑 Istruzioni Check-in</h3>
                <p><strong>Orario:</strong> Dalle ore 15:00 in poi</p>
                <p><strong>Documenti:</strong> Porta un documento d'identità valido</p>
                <p><strong>Arrivo:</strong> Ti invieremo le istruzioni dettagliate per l'accesso entro domani</p>
            </div>
            <?php endif; ?>
            
            <!-- Important Notes -->
            <div class="important-notes">
                <h3>📋 Checklist Pre-Arrivo</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>✅ Documento d'identità valido</li>
                    <li>✅ Conferma prenotazione (questa email)</li>
                    <li>✅ Verifica meteo per preparare bagaglio appropriato</li>
                    <li>✅ Caricabatterie per dispositivi elettronici</li>
                    <li>✅ Medicinali personali se necessari</li>
                </ul>
            </div>
            
            <!-- Weather Tip -->
            <div class="weather-tip">
                <h3>🌤️ Suggerimento Meteo</h3>
                <p>Ricordati di controllare le previsioni meteo per <?php echo esc_html($property_location); ?> nei giorni del tuo soggiorno per preparare il bagaglio perfetto!</p>
            </div>
            
            <!-- Contact Information -->
            <div class="contact-card">
                <h3>📞 Hai Bisogno di Aiuto?</h3>
                <p>Il nostro team è sempre disponibile per assisterti</p>
                
                <?php if ($contact_phone): ?>
                <a href="tel:<?php echo esc_attr($contact_phone); ?>" class="contact-button">
                    📱 Chiama: <?php echo esc_html($contact_phone); ?>
                </a>
                <?php endif; ?>
                
                <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>" class="contact-button">
                    ✉️ Email Supporto
                </a>
                
                <?php if ($emergency_contact): ?>
                <p style="margin-top: 15px; font-size: 14px;">
                    <strong>🚨 Emergenze:</strong> <a href="tel:<?php echo esc_attr($emergency_contact); ?>" style="color: #1976d2;"><?php echo esc_html($emergency_contact); ?></a>
                </p>
                <?php endif; ?>
            </div>
            
            <!-- CTA Button -->
            <?php if ($booking_url): ?>
            <div style="text-align: center; margin: 30px 0;">
                <a href="<?php echo esc_url($booking_url); ?>" class="cta-button">
                    Visualizza Dettagli Prenotazione
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Local Tips -->
            <div style="background-color: #f1f2f6; padding: 20px; border-radius: 8px; margin: 25px 0;">
                <h3 style="color: #2c3e50; margin-top: 0;">🗺️ Consigli Locali</h3>
                <p>Per rendere il tuo soggiorno ancora più speciale:</p>
                <ul>
                    <li>Esplora i mercati locali per prodotti freschi</li>
                    <li>Chiedi al nostro staff per raccomandazioni sui ristoranti</li>
                    <li>Non perderti le attrazioni nelle vicinanze</li>
                    <li>Considera di noleggiare una bicicletta per muoverti</li>
                </ul>
            </div>
            
            <p style="font-size: 16px; color: #2c3e50; margin-top: 30px;">
                <strong>Siamo emozionati di accoglierti presto! 🌟</strong>
            </p>
            
            <p>Se hai domande o esigenze particolari, non esitare a contattarci. Vogliamo che il tuo soggiorno sia perfetto!</p>
            
            <p style="margin-top: 30px;">
                Buon viaggio e a presto!<br>
                <strong>Il Team di <?php echo esc_html($site_name); ?></strong>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p style="font-size: 16px; margin-bottom: 20px;">
                <strong>Contatti Utili</strong>
            </p>
            
            <div style="margin: 20px 0;">
                <a href="<?php echo esc_url($site_url); ?>">🌐 Sito Web</a>
                <span style="margin: 0 15px;">|</span>
                <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>">✉️ Email</a>
                <?php if ($contact_phone): ?>
                <span style="margin: 0 15px;">|</span>
                <a href="tel:<?php echo esc_attr($contact_phone); ?>">📞 <?php echo esc_html($contact_phone); ?></a>
                <?php endif; ?>
            </div>
            
            <p>
                <?php echo esc_html($site_name); ?><br>
                <?php if (get_option('colitalia_company_address')): ?>
                    <?php echo nl2br(esc_html(get_option('colitalia_company_address'))); ?>
                <?php endif; ?>
            </p>
            
            <p style="font-size: 12px; opacity: 0.8; margin-top: 20px;">
                Questo è un promemoria automatico per la prenotazione <?php echo esc_html($booking_code); ?>.
            </p>
            
            <p style="font-size: 12px; opacity: 0.6;">
                © <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. Tutti i diritti riservati.
            </p>
        </div>
    </div>
</body>
</html>
