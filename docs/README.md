# Colitalia Real Estate Manager

## Descrizione

Colitalia Real Estate Manager è un plugin WordPress completo per la gestione di proprietà immobiliari, prenotazioni e pagamenti. Progettato specificamente per agenzie immobiliari, bed & breakfast, case vacanza e strutture ricettive.

### Funzionalità Principali

- 🏠 **Gestione Proprietà**: Catalogo completo con foto, descrizioni, prezzi e disponibilità
- 📅 **Sistema Prenotazioni**: Calendario avanzato con gestione periodi e tariffe stagionali
- 💳 **Integrazione PayPal**: Pagamenti sicuri con supporto sandbox e live
- 📧 **Notifiche Email**: Sistema automatico di conferme e promemoria
- 📊 **Dashboard Analytics**: Statistiche dettagliate su prenotazioni e guadagni
- 🎨 **Integrazione Elementor**: Widget personalizzati per il frontend
- 🌐 **Multilingua**: Supporto completo per siti multilingua
- 📱 **Responsive Design**: Interfaccia ottimizzata per mobile e desktop

## Requisiti Sistema

### Requisiti Minimi
- WordPress 5.8 o superiore
- PHP 7.4 o superiore
- MySQL 5.7 o superiore
- Almeno 64MB di memoria PHP
- Supporto cURL per integrazioni esterne

### Plugin Raccomandati
- Elementor (per widget frontend)
- WP Mail SMTP (per invio email affidabile)
- Yoast SEO (per ottimizzazione SEO proprietà)

## Installazione

### Metodo 1: Installazione da Admin WordPress

1. Accedi al pannello amministrativo WordPress
2. Vai su **Plugin > Aggiungi nuovo**
3. Carica il file `colitalia-real-estate.zip`
4. Clicca su **Installa ora**
5. Attiva il plugin

### Metodo 2: Installazione via FTP

1. Decomprimi il file `colitalia-real-estate.zip`
2. Carica la cartella `colitalia-real-estate` in `/wp-content/plugins/`
3. Accedi al pannello admin e attiva il plugin

### Post-Installazione

1. Vai su **Colitalia RE > Impostazioni**
2. Completa la configurazione guidata
3. Configura PayPal e SMTP
4. Crea le tue prime proprietà

## Configurazione PayPal

### Modalità Sandbox (Test)

1. Crea account sviluppatore su [PayPal Developer](https://developer.paypal.com/)
2. Ottieni le credenziali Sandbox:
   - Client ID
   - Client Secret
3. In **Colitalia RE > Impostazioni > PayPal**:
   - Seleziona "Modalità Sandbox"
   - Inserisci Client ID e Secret
   - Testa con transazione di prova

### Modalità Live (Produzione)

1. Crea app business su PayPal
2. Ottieni credenziali Live:
   - Client ID Live
   - Client Secret Live
3. In **Colitalia RE > Impostazioni > PayPal**:
   - Seleziona "Modalità Live"
   - Inserisci credenziali live
   - Configura webhook URL

```php
// Webhook URL per notifiche PayPal
https://tuosito.com/wp-json/colitalia/v1/paypal-webhook
```

## Setup SMTP Email

### Configurazione SMTP

1. Vai su **Colitalia RE > Impostazioni > Email**
2. Abilita "Usa SMTP personalizzato"
3. Configura i parametri:

```
Server SMTP: smtp.tuoprovider.com
Porta: 587 (TLS) o 465 (SSL)
Username: tuoemail@dominio.com
Password: la_tua_password
Crittografia: TLS/SSL
```

### Provider Raccomandati
- **Gmail**: smtp.gmail.com, porta 587
- **Outlook**: smtp-mail.outlook.com, porta 587
- **SendGrid**: smtp.sendgrid.net, porta 587
- **Mailgun**: smtp.mailgun.org, porta 587

### Test Email

1. Salva le impostazioni SMTP
2. Clicca su "Invia Email di Test"
3. Verifica ricezione nell'inbox

## Configurazione Iniziale Proprietà

### 1. Categorie Proprietà

1. Vai su **Proprietà > Categorie**
2. Crea categorie base:
   - Casa Vacanza
   - Appartamento
   - Villa
   - B&B
   - Hotel

### 2. Servizi e Amenities

1. Vai su **Proprietà > Servizi**
2. Aggiungi servizi comuni:
   - WiFi Gratuito
   - Parcheggio
   - Aria Condizionata
   - Piscina
   - Colazione Inclusa

### 3. Prima Proprietà

1. Vai su **Proprietà > Aggiungi Nuova**
2. Compila i campi richiesti:
   - Titolo e Descrizione
   - Indirizzo completo
   - Prezzi base e stagionali
   - Foto gallery (minimo 5 foto)
   - Servizi disponibili

### 4. Calendario e Disponibilità

1. Accedi alla proprietà creata
2. Vai alla sezione "Calendario"
3. Imposta:
   - Periodi alta/bassa stagione
   - Prezzi per periodo
   - Giorni di blocco
   - Durata minima soggiorno

## Integrazione Elementor

### Widget Disponibili

#### Property Search
```php
// Widget ricerca proprietà
[colitalia_search]
```

#### Property Grid
```php
// Griglia proprietà
[colitalia_grid category="casa-vacanza" limit="6"]
```

#### Property Details
```php
// Dettagli singola proprietà
[colitalia_details id="123"]
```

#### Booking Form
```php
// Form prenotazione
[colitalia_booking property_id="123"]
```

### Personalizzazione Template

1. Copia i template da `/plugins/colitalia-real-estate/templates/`
2. Incolla in `/themes/tuo-theme/colitalia-templates/`
3. Personalizza secondo le tue esigenze

## FAQ Common Issues

### Q: Le email non vengono inviate
**A**: Verifica la configurazione SMTP. La maggior parte degli hosting condivisi blocca la funzione mail() di PHP. Usa SMTP o un servizio esterno come SendGrid.

### Q: PayPal non funziona
**A**: Controlla:
- Credenziali corrette (Client ID/Secret)
- Modalità coerente (Sandbox vs Live)
- Webhook URL configurato
- Certificato SSL attivo sul sito

### Q: Le proprietà non appaiono nel frontend
**A**: Verifica:
- Proprietà pubblicate (non bozze)
- Shortcode corretto nelle pagine
- Cache del sito svuotata
- Conflitti con altri plugin

### Q: Errore "Memory limit exceeded"
**A**: Aumenta il memory_limit PHP:
```php
ini_set('memory_limit', '256M');
```
Oppure contatta l'hosting per aumentarlo.

### Q: Calendario non si carica
**A**: Possibili cause:
- Conflitti JavaScript
- jQuery non caricato
- Errori console browser
- Plugin di cache aggressive

### Q: Prezzi stagionali non applicati
**A**: Controlla:
- Date periodo configurate correttamente
- Sovrapposizioni tra periodi
- Fuso orario WordPress
- Cache del plugin svuotata

## Screenshots

### 1. Dashboard Principale
![Dashboard](screenshots/dashboard.png)
*Vista generale con statistiche e prenotazioni recenti*

### 2. Gestione Proprietà
![Properties](screenshots/properties.png)
*Elenco proprietà con filtri e azioni rapide*

### 3. Calendario Prenotazioni
![Calendar](screenshots/calendar.png)
*Calendario interattivo con disponibilità e prezzi*

### 4. Form Prenotazione
![Booking](screenshots/booking-form.png)
*Form di prenotazione frontend con PayPal*

### 5. Impostazioni Plugin
![Settings](screenshots/settings.png)
*Pannello impostazioni con configurazioni*

## Supporto

- **Email**: supporto@colitalia.com
- **Documentazione**: [Guida Completa](ADMIN_GUIDE.md)
- **Forum**: [Community WordPress](https://wordpress.org/support/plugin/colitalia-real-estate)
- **Bug Report**: [GitHub Issues](https://github.com/colitalia/real-estate-plugin/issues)

## License

Questo plugin è rilasciato sotto licenza GPL v2. Vedi [LICENSE](LICENSE) per dettagli.

---

**Versione**: 1.0.0  
**Ultimo aggiornamento**: Settembre 2025  
**Compatibilità**: WordPress 5.8+, PHP 7.4+