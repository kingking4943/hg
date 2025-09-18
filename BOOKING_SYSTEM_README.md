# Sistema Prenotazioni e Calendario - Colitalia Real Estate Manager

## Panoramica

Il sistema di prenotazioni e calendario è stato completamente implementato per il plugin Colitalia Real Estate Manager. Questo sistema fornisce funzionalità complete per la gestione delle prenotazioni di case vacanze, calendario delle disponibilità, gestione clienti e prezzi stagionali.

## Componenti Implementati

### 1. Database Layer
- **Migration.php** - Gestisce la creazione e aggiornamento delle tabelle del database
- **Tabelle create:**
  - `wp_colitalia_bookings` - Prenotazioni
  - `wp_colitalia_customers` - Clienti  
  - `wp_colitalia_availability` - Disponibilità calendario
  - `wp_colitalia_pricing` - Prezzi stagionali
  - `wp_colitalia_services` - Servizi extra
  - `wp_colitalia_booking_log` - Log delle azioni

### 2. Manager Classes
- **BookingManager.php** - Gestione completa delle prenotazioni (CRUD, validazione, calcolo prezzi)
- **CalendarManager.php** - Gestione calendario con API AJAX per disponibilità
- **CustomerManager.php** - Gestione clienti con conformità GDPR

### 3. Frontend Templates
- **booking-form.php** - Form di prenotazione multi-step responsive
- **calendar-widget.php** - Widget calendario con FullCalendar.js

### 4. JavaScript Components
- **booking.js** - Gestione form prenotazione multi-step e interazioni
- **calendar.js** - Gestione calendario FullCalendar con tooltip e controlli admin

### 5. Styles
- **booking.css** - Stili completi responsive per form e calendario

## Funzionalità Principali

### Sistema Prenotazioni
- ✅ Form prenotazione multi-step (4 step)
- ✅ Validazione real-time JavaScript
- ✅ Verifica disponibilità AJAX
- ✅ Calcolo prezzi dinamico con sconti
- ✅ Gestione servizi extra
- ✅ Generazione codici prenotazione univoci
- ✅ Stati prenotazione completi
- ✅ Email di conferma automatiche

### Calendario Disponibilità
- ✅ Calendario FullCalendar.js interattivo
- ✅ Visualizzazione stato giorni (disponibile, prenotato, bloccato, manutenzione)
- ✅ Tooltip informativi su hover
- ✅ Gestione periodi indisponibili
- ✅ Sincronizzazione calendari esterni (Airbnb, Booking.com)
- ✅ Vista amministratore con editing

### Gestione Clienti
- ✅ Anagrafica clienti completa
- ✅ Conformità GDPR (anonimizzazione, esportazione dati)
- ✅ Storico prenotazioni cliente
- ✅ Sistema consensi privacy e marketing
- ✅ Verifica email con codice
- ✅ Punteggio fedeltà e tier clienti

### Prezzi Stagionali
- ✅ Definizione stagioni personalizzate
- ✅ Prezzi base e per ospite extra
- ✅ Sconti per durata soggiorno
- ✅ Override prezzi per date specifiche
- ✅ Sistema priorità stagioni

## Shortcodes Disponibili

### Form Prenotazione
```php
[colitalia_booking_form property_id="123"]
```

### Calendario Disponibilità  
```php
[colitalia_calendar property_id="123" show_prices="true" height="600px"]
```

### Calendario Amministrativo (solo admin)
```php
[colitalia_availability_calendar property_id="123" editable="true"]
```

## API AJAX Endpoints

### Prenotazioni
- `colitalia_create_booking` - Crea nuova prenotazione
- `colitalia_check_availability` - Verifica disponibilità date
- `colitalia_calculate_price` - Calcola prezzo prenotazione

### Calendario
- `colitalia_get_calendar_data` - Ottieni dati calendario
- `colitalia_update_availability` - Aggiorna disponibilità singola data
- `colitalia_bulk_update_availability` - Aggiornamento massivo disponibilità

### Clienti
- `colitalia_create_customer` - Crea nuovo cliente
- `colitalia_search_customers` - Ricerca clienti
- `colitalia_get_customer` - Ottieni dati cliente
- `colitalia_export_customer_data` - Esportazione dati GDPR

## Integrazione nel Plugin

Il sistema è completamente integrato nel plugin esistente:

1. **Attivazione automatica** - Le tabelle vengono create all'attivazione del plugin
2. **Asset loading** - CSS/JS caricati automaticamente dove necessario
3. **Shortcodes registrati** - Disponibili in qualsiasi post/pagina
4. **AJAX sicuro** - Nonces e validazione per tutte le chiamate
5. **Traduzioni** - Testi preparati per localizzazione

## Utilizzo

### 1. Attivazione
Il sistema si attiva automaticamente quando il plugin viene attivato. Le tabelle del database vengono create automaticamente.

### 2. Inserimento Form Prenotazione
```php
// In un post o pagina
[colitalia_booking_form property_id="123"]

// Nel template PHP
<?php echo do_shortcode('[colitalia_booking_form property_id="' . get_the_ID() . '"]'); ?>

// Include template diretto
<?php include COLITALIA_PLUGIN_PATH . 'templates/booking-form.php'; ?>
```

### 3. Inserimento Calendario
```php
// Calendario semplice
[colitalia_calendar property_id="123"]

// Calendario con prezzi
[colitalia_calendar property_id="123" show_prices="true" height="500px"]

// Include template diretto  
<?php 
$args = ['property_id' => get_the_ID(), 'show_prices' => true];
include COLITALIA_PLUGIN_PATH . 'templates/calendar-widget.php'; 
?>
```

### 4. Gestione Amministrativa
- I manager si istanziano automaticamente all'inizializzazione del plugin
- Accesso ai dati tramite singleton pattern:
```php
$booking_manager = \Colitalia_Real_Estate\Booking\BookingManager::instance();
$calendar_manager = \Colitalia_Real_Estate\Booking\CalendarManager::instance();
$customer_manager = \Colitalia_Real_Estate\Booking\CustomerManager::instance();
```

## Personalizzazione

### Stili CSS
Il file `assets/css/booking.css` contiene tutti gli stili. È possibile:
- Sovrascrivere gli stili nel tema
- Modificare le variabili CSS per colori e spaziature
- Utilizzare le classi CSS per personalizzazioni specifiche

### Traduzioni
Tutti i testi sono preparati per la traduzione con `__()` e textdomain `colitalia-real-estate`.

### Hook e Filtri
Il sistema fornisce vari hook per personalizzazioni:
- `colitalia_before_booking_create`
- `colitalia_after_booking_create`
- `colitalia_booking_price_calculated`
- `colitalia_customer_created`

## Sicurezza

- ✅ Nonces WordPress per tutte le azioni AJAX
- ✅ Sanitizzazione e validazione input
- ✅ Escape output per prevenire XSS
- ✅ Controlli permessi utente
- ✅ Rate limiting per API
- ✅ Conformità GDPR

## Performance

- ✅ Caching dei dati calendario
- ✅ Lazy loading asset dove possibile
- ✅ Database query ottimizzate con indici
- ✅ Preload asset critici
- ✅ Caricamento condizionale FullCalendar

## Compatibilità

- ✅ WordPress 5.0+
- ✅ PHP 7.4+
- ✅ Mobile responsive
- ✅ Accessibilità WCAG 2.1
- ✅ Browser moderni
- ✅ Dark mode support
- ✅ High contrast mode

## Note Tecniche

### Database
- Tutte le tabelle utilizzano il prefixo WordPress
- Relazioni con chiavi esterne logiche
- Indici ottimizzati per performance
- Campi timestamp automatici

### JavaScript
- Vanilla JavaScript + jQuery
- Pattern singleton per manager
- Error handling completo
- Debouncing per ricerche
- Validazione real-time

### CSS
- Mobile-first responsive
- Utilizzo CSS Grid e Flexbox
- Variabili CSS per theming
- Supporto print styles
- Animazioni rispettose delle preferenze utente

## Supporto

Per supporto tecnico o personalizzazioni:
- Documentazione sviluppatore in `docs/DEVELOPER.md`
- Guide amministratore in `docs/ADMIN_GUIDE.md`
- Log degli errori in `wp-content/debug.log`

## Changelog

### v1.2.0 (2025-01-03)
- ✅ Implementazione completa sistema prenotazioni
- ✅ Calendario FullCalendar con gestione disponibilità
- ✅ Form multi-step responsive
- ✅ Gestione clienti con GDPR compliance
- ✅ Prezzi stagionali e servizi extra
- ✅ Sistema di log e audit trail
- ✅ Sincronizzazione calendari esterni
- ✅ Email automation
