# Guida Sviluppatore - Colitalia Real Estate Manager

## Indice
1. [Architettura Plugin](#architettura-plugin)
2. [Struttura Directory](#struttura-directory)
3. [Database Schema](#database-schema)
4. [Hook e Filter](#hook-e-filter)
5. [API Endpoints](#api-endpoints)
6. [Customizzazione Template](#customizzazione-template)
7. [Estensioni e Modifiche](#estensioni-e-modifiche)
8. [Sviluppo Add-on](#sviluppo-add-on)

## Architettura Plugin

### Design Pattern
Il plugin segue l'architettura **MVC (Model-View-Controller)** con elementi del pattern **Observer** per gli hook.

```
Colitalia_Real_Estate/
├── Models/          # Gestione dati e database
├── Views/           # Template e output
├── Controllers/     # Logica business
├── Services/        # Servizi esterni (PayPal, Email)
├── Helpers/         # Utility e funzioni ausiliarie
└── Assets/          # CSS, JS, immagini
```

### Inizializzazione Plugin

```php
<?php
// File principale: colitalia-real-estate.php

class Colitalia_Real_Estate_Main {
    
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
    }
    
    public function init() {
        // Registra Custom Post Types
        $this->register_post_types();
        
        // Inizializza controller
        new Colitalia_Property_Controller();
        new Colitalia_Booking_Controller();
        new Colitalia_Payment_Controller();
    }
}

// Avvia plugin
Colitalia_Real_Estate_Main::instance();
```

### Class Autoloader

```php
<?php
// includes/class-autoloader.php

class Colitalia_Autoloader {
    
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    public static function autoload($class_name) {
        // Converte nome classe in path file
        if (strpos($class_name, 'Colitalia_') !== 0) {
            return;
        }
        
        $class_file = str_replace('Colitalia_', '', $class_name);
        $class_file = str_replace('_', '-', strtolower($class_file));
        
        $paths = [
            'models/' . $class_file . '.php',
            'controllers/' . $class_file . '.php',
            'services/' . $class_file . '.php',
            'helpers/' . $class_file . '.php'
        ];
        
        foreach ($paths as $path) {
            $file = COLITALIA_PATH . 'includes/' . $path;
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
}
```

## Struttura Directory

```
colitalia-real-estate/
├── colitalia-real-estate.php          # File principale plugin
├── uninstall.php                      # Script disinstallazione
├── readme.txt                         # Descrizione WordPress.org
│
├── includes/                          # Core PHP classes
│   ├── class-autoloader.php          # Autoloader classi
│   ├── class-activator.php           # Attivazione plugin
│   ├── class-deactivator.php         # Disattivazione plugin
│   │
│   ├── models/                        # Modelli dati
│   │   ├── class-property.php
│   │   ├── class-booking.php
│   │   ├── class-customer.php
│   │   └── class-payment.php
│   │
│   ├── controllers/                   # Controller logica
│   │   ├── class-property-controller.php
│   │   ├── class-booking-controller.php
│   │   ├── class-admin-controller.php
│   │   └── class-frontend-controller.php
│   │
│   ├── services/                      # Servizi esterni
│   │   ├── class-paypal-service.php
│   │   ├── class-email-service.php
│   │   └── class-calendar-service.php
│   │
│   ├── helpers/                       # Utility functions
│   │   ├── class-date-helper.php
│   │   ├── class-price-helper.php
│   │   └── class-validation-helper.php
│   │
│   └── widgets/                       # Widget Elementor
│       ├── class-property-search.php
│       ├── class-property-grid.php
│       └── class-booking-form.php
│
├── templates/                         # Template frontend
│   ├── single-property.php
│   ├── archive-property.php
│   ├── booking-form.php
│   └── partials/
│       ├── property-card.php
│       ├── property-gallery.php
│       └── booking-calendar.php
│
├── admin/                             # Interface amministrativa
│   ├── css/
│   ├── js/
│   ├── images/
│   └── partials/
│       ├── dashboard.php
│       ├── properties.php
│       └── settings.php
│
├── public/                            # Assets pubblici
│   ├── css/
│   │   ├── colitalia-public.css
│   │   └── colitalia-responsive.css
│   ├── js/
│   │   ├── colitalia-public.js
│   │   ├── booking-calendar.js
│   │   └── payment-form.js
│   └── images/
│
├── languages/                         # File traduzioni
│   ├── colitalia-real-estate.pot
│   ├── colitalia-real-estate-it_IT.po
│   └── colitalia-real-estate-en_US.po
│
└── docs/                             # Documentazione
    ├── README.md
    ├── ADMIN_GUIDE.md
    ├── DEVELOPER.md
    └── CHANGELOG.md
```

## Database Schema

### Tabelle Principali

#### colitalia_properties
```sql
CREATE TABLE `colitalia_properties` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `address` text NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `bedrooms` int(11) DEFAULT 0,
  `bathrooms` int(11) DEFAULT 0,
  `max_guests` int(11) DEFAULT 1,
  `area_size` decimal(8,2) DEFAULT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `cleaning_fee` decimal(10,2) DEFAULT 0,
  `security_deposit` decimal(10,2) DEFAULT 0,
  `tax_rate` decimal(5,2) DEFAULT 0,
  `min_stay` int(11) DEFAULT 1,
  `max_stay` int(11) DEFAULT 365,
  `check_in_time` time DEFAULT '15:00:00',
  `check_out_time` time DEFAULT '11:00:00',
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_status` (`status`),
  KEY `idx_location` (`latitude`,`longitude`)
);
```

#### colitalia_bookings
```sql
CREATE TABLE `colitalia_bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `booking_code` varchar(20) UNIQUE NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests` int(11) NOT NULL,
  `total_nights` int(11) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `cleaning_fee` decimal(10,2) DEFAULT 0,
  `tax_amount` decimal(10,2) DEFAULT 0,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','refunded','failed') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_transaction_id` varchar(100) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_booking_code` (`booking_code`),
  KEY `idx_property_id` (`property_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_dates` (`check_in`,`check_out`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_booking_property` FOREIGN KEY (`property_id`) REFERENCES `colitalia_properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_customer` FOREIGN KEY (`customer_id`) REFERENCES `colitalia_customers` (`id`) ON DELETE CASCADE
);
```

#### colitalia_customers
```sql
CREATE TABLE `colitalia_customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `document_type` varchar(50) DEFAULT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `total_bookings` int(11) DEFAULT 0,
  `total_spent` decimal(10,2) DEFAULT 0,
  `customer_type` enum('individual','corporate') DEFAULT 'individual',
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_customer_type` (`customer_type`)
);
```

#### colitalia_seasonal_pricing
```sql
CREATE TABLE `colitalia_seasonal_pricing` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint(20) unsigned NOT NULL,
  `season_name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `min_stay` int(11) DEFAULT 1,
  `changeover_day` varchar(20) DEFAULT 'any',
  `priority` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_property_id` (`property_id`),
  KEY `idx_dates` (`start_date`,`end_date`),
  KEY `idx_priority` (`priority`),
  CONSTRAINT `fk_seasonal_property` FOREIGN KEY (`property_id`) REFERENCES `colitalia_properties` (`id`) ON DELETE CASCADE
);
```

### Relazioni Database

```
Properties (1) ←→ (N) Bookings
Customers (1) ←→ (N) Bookings  
Properties (1) ←→ (N) Seasonal_Pricing
Properties (1) ←→ (N) Property_Amenities
Bookings (1) ←→ (N) Payments
```

## Hook e Filter

### Action Hooks

#### Plugin Lifecycle
```php
// Attivazione plugin
do_action('colitalia_plugin_activated');

// Disattivazione plugin
do_action('colitalia_plugin_deactivated');

// Inizializzazione plugin
do_action('colitalia_init');
```

#### Property Hooks
```php
// Prima della creazione proprietà
do_action('colitalia_before_property_create', $property_data);

// Dopo la creazione proprietà
do_action('colitalia_after_property_create', $property_id, $property_data);

// Prima dell'aggiornamento proprietà
do_action('colitalia_before_property_update', $property_id, $property_data);

// Dopo l'aggiornamento proprietà
do_action('colitalia_after_property_update', $property_id, $property_data);

// Prima dell'eliminazione proprietà
do_action('colitalia_before_property_delete', $property_id);

// Dopo l'eliminazione proprietà
do_action('colitalia_after_property_delete', $property_id);
```

#### Booking Hooks
```php
// Prima della creazione prenotazione
do_action('colitalia_before_booking_create', $booking_data);

// Dopo la creazione prenotazione
do_action('colitalia_after_booking_create', $booking_id, $booking_data);

// Cambio stato prenotazione
do_action('colitalia_booking_status_changed', $booking_id, $old_status, $new_status);

// Prenotazione confermata
do_action('colitalia_booking_confirmed', $booking_id);

// Prenotazione annullata
do_action('colitalia_booking_cancelled', $booking_id, $reason);

// Check-in cliente
do_action('colitalia_customer_checked_in', $booking_id);

// Check-out cliente
do_action('colitalia_customer_checked_out', $booking_id);
```

#### Payment Hooks
```php
// Prima del pagamento
do_action('colitalia_before_payment', $payment_data);

// Pagamento completato
do_action('colitalia_payment_completed', $payment_id, $booking_id);

// Pagamento fallito
do_action('colitalia_payment_failed', $payment_data, $error);

// Rimborso elaborato
do_action('colitalia_payment_refunded', $payment_id, $amount);
```

#### Email Hooks
```php
// Prima dell'invio email
do_action('colitalia_before_send_email', $email_type, $recipient, $data);

// Dopo l'invio email
do_action('colitalia_after_send_email', $email_type, $recipient, $success);

// Email fallita
do_action('colitalia_email_failed', $email_type, $recipient, $error);
```

### Filter Hooks

#### Property Filters
```php
// Modifica dati proprietà prima del salvataggio
$property_data = apply_filters('colitalia_property_data_before_save', $property_data);

// Modifica prezzo calcolato
$price = apply_filters('colitalia_property_calculated_price', $price, $property_id, $dates);

// Modifica query ricerca proprietà
$query_args = apply_filters('colitalia_property_search_args', $query_args, $search_params);

// Personalizza template proprietà
$template = apply_filters('colitalia_property_template', $template, $property_id);
```

#### Booking Filters
```php
// Modifica calcolo totale prenotazione
$total = apply_filters('colitalia_booking_total_calculation', $total, $booking_data);

// Personalizza validazione prenotazione
$is_valid = apply_filters('colitalia_booking_validation', $is_valid, $booking_data);

// Modifica dati prenotazione
$booking_data = apply_filters('colitalia_booking_data', $booking_data);
```

#### Payment Filters
```php
// Gateway di pagamento disponibili
$gateways = apply_filters('colitalia_payment_gateways', $gateways);

// Personalizza redirect dopo pagamento
$redirect_url = apply_filters('colitalia_payment_redirect_url', $redirect_url, $booking_id);

// Modifica commissioni pagamento
$fees = apply_filters('colitalia_payment_fees', $fees, $amount, $gateway);
```

### Esempio Utilizzo Hook

```php
<?php
// functions.php del tema o plugin personalizzato

// Aggiunge commissione booking del 5%
add_filter('colitalia_booking_total_calculation', function($total, $booking_data) {
    $commission = $total * 0.05;
    return $total + $commission;
}, 10, 2);

// Invia notifica SMS dopo conferma prenotazione
add_action('colitalia_booking_confirmed', function($booking_id) {
    $booking = new Colitalia_Booking($booking_id);
    $customer = $booking->get_customer();
    
    if ($customer->get_phone()) {
        // Integra con servizio SMS
        send_sms($customer->get_phone(), 'Prenotazione confermata!');
    }
});

// Personalizza template single property
add_filter('colitalia_property_template', function($template, $property_id) {
    $property = new Colitalia_Property($property_id);
    
    if ($property->get_category() === 'luxury') {
        return 'single-property-luxury.php';
    }
    
    return $template;
}, 10, 2);
```

## API Endpoints

### REST API Base

Namespace: `colitalia/v1`  
Base URL: `https://yoursite.com/wp-json/colitalia/v1/`

### Properties Endpoints

#### GET /properties
```php
// Lista tutte le proprietà
GET /wp-json/colitalia/v1/properties

// Parametri query:
// - per_page: numero risultati (default: 10, max: 100)
// - page: pagina corrente
// - category: categoria proprietà
// - location: città/regione
// - min_price: prezzo minimo per notte
// - max_price: prezzo massimo per notte
// - guests: numero ospiti
// - amenities[]: array di servizi richiesti

// Esempio:
GET /wp-json/colitalia/v1/properties?per_page=6&category=villa&guests=4&amenities[]=wifi&amenities[]=pool

// Risposta:
{
  "properties": [
    {
      "id": 123,
      "title": "Villa Mare Blu",
      "slug": "villa-mare-blu",
      "address": "Via Roma 1, 12345 Città",
      "latitude": 41.9028,
      "longitude": 12.4964,
      "bedrooms": 3,
      "bathrooms": 2,
      "max_guests": 6,
      "area_size": 150.00,
      "price_per_night": 120.00,
      "currency": "EUR",
      "images": [
        {
          "id": 456,
          "url": "https://example.com/wp-content/uploads/villa1.jpg",
          "thumbnail": "https://example.com/wp-content/uploads/villa1-300x200.jpg",
          "alt": "Vista frontale villa"
        }
      ],
      "amenities": [
        {
          "id": 1,
          "name": "WiFi Gratuito",
          "icon": "wifi"
        },
        {
          "id": 2,
          "name": "Piscina",
          "icon": "swimming-pool"
        }
      ],
      "availability": {
        "next_available": "2025-10-15",
        "min_stay": 3,
        "max_stay": 14
      },
      "links": {
        "self": "/wp-json/colitalia/v1/properties/123",
        "booking": "/wp-json/colitalia/v1/properties/123/book"
      }
    }
  ],
  "meta": {
    "total": 45,
    "total_pages": 8,
    "current_page": 1,
    "per_page": 6
  }
}
```

#### GET /properties/{id}
```php
// Dettagli singola proprietà
GET /wp-json/colitalia/v1/properties/123

// Risposta:
{
  "id": 123,
  "title": "Villa Mare Blu",
  "description": "Splendida villa fronte mare...",
  "full_address": {
    "street": "Via Roma 1",
    "city": "Città",
    "postal_code": "12345",
    "country": "Italia",
    "latitude": 41.9028,
    "longitude": 12.4964
  },
  "specifications": {
    "bedrooms": 3,
    "bathrooms": 2,
    "max_guests": 6,
    "area_size": 150.00,
    "floor": 1,
    "elevator": false
  },
  "pricing": {
    "base_price": 120.00,
    "cleaning_fee": 50.00,
    "security_deposit": 200.00,
    "tax_rate": 10.00,
    "currency": "EUR"
  },
  "rules": {
    "min_stay": 3,
    "max_stay": 14,
    "check_in_time": "15:00",
    "check_out_time": "11:00",
    "changeover_day": "saturday"
  },
  "seasonal_pricing": [
    {
      "season_name": "Alta Stagione",
      "start_date": "2025-06-01",
      "end_date": "2025-09-30",
      "price_per_night": 180.00,
      "min_stay": 7
    }
  ]
}
```

#### GET /properties/{id}/availability
```php
// Controlla disponibilità proprietà
GET /wp-json/colitalia/v1/properties/123/availability?start_date=2025-10-15&end_date=2025-10-22

// Risposta:
{
  "property_id": 123,
  "start_date": "2025-10-15",
  "end_date": "2025-10-22",
  "nights": 7,
  "available": true,
  "pricing": {
    "base_price_per_night": 120.00,
    "seasonal_price_per_night": 150.00,
    "total_nights_cost": 1050.00,
    "cleaning_fee": 50.00,
    "tax_amount": 110.00,
    "total_amount": 1210.00,
    "currency": "EUR"
  },
  "blocked_dates": [],
  "min_stay_met": true,
  "max_stay_respected": true
}
```

### Bookings Endpoints

#### POST /bookings
```php
// Crea nuova prenotazione
POST /wp-json/colitalia/v1/bookings

// Body:
{
  "property_id": 123,
  "check_in": "2025-10-15",
  "check_out": "2025-10-22",
  "guests": 4,
  "customer": {
    "first_name": "Mario",
    "last_name": "Rossi",
    "email": "mario.rossi@email.com",
    "phone": "+39 123 456 7890",
    "address": "Via Verdi 5, Milano"
  },
  "special_requests": "Arrivo previsto alle 18:00",
  "payment_method": "paypal"
}

// Risposta:
{
  "booking_id": 789,
  "booking_code": "COL789123",
  "status": "pending",
  "total_amount": 1210.00,
  "currency": "EUR",
  "payment_url": "https://paypal.com/checkout?token=abc123",
  "expires_at": "2025-10-10T15:30:00Z"
}
```

#### GET /bookings/{id}
```php
// Dettagli prenotazione
GET /wp-json/colitalia/v1/bookings/789

// Headers richiesti:
// Authorization: Bearer {token} oppure
// X-Booking-Code: COL789123

// Risposta:
{
  "id": 789,
  "booking_code": "COL789123",
  "property": {
    "id": 123,
    "title": "Villa Mare Blu",
    "address": "Via Roma 1, Città"
  },
  "customer": {
    "first_name": "Mario",
    "last_name": "Rossi",
    "email": "mario.rossi@email.com"
  },
  "dates": {
    "check_in": "2025-10-15",
    "check_out": "2025-10-22",
    "nights": 7
  },
  "guests": 4,
  "amount": {
    "subtotal": 1050.00,
    "cleaning_fee": 50.00,
    "taxes": 110.00,
    "total": 1210.00,
    "currency": "EUR"
  },
  "status": "confirmed",
  "payment_status": "paid",
  "created_at": "2025-10-08T10:30:00Z"
}
```

#### PUT /bookings/{id}/cancel
```php
// Annulla prenotazione
PUT /wp-json/colitalia/v1/bookings/789/cancel

// Body:
{
  "reason": "Impossibilità a viaggiare"
}

// Risposta:
{
  "booking_id": 789,
  "status": "cancelled",
  "refund_amount": 605.00,
  "refund_processed": true,
  "cancelled_at": "2025-10-10T14:22:00Z"
}
```

### Webhook Endpoints

#### POST /webhook/paypal
```php
// Webhook PayPal per notifiche pagamento
POST /wp-json/colitalia/v1/webhook/paypal

// Headers PayPal:
// PAYPAL-TRANSMISSION-ID: xxx
// PAYPAL-CERT-ID: xxx
// PAYPAL-AUTH-ALGO: SHA256withRSA
// PAYPAL-TRANSMISSION-SIG: xxx

// Body (esempio payment completed):
{
  "id": "WH-xxx",
  "event_version": "1.0",
  "create_time": "2025-10-08T10:30:00Z",
  "resource_type": "checkout-order",
  "event_type": "CHECKOUT.ORDER.APPROVED",
  "resource": {
    "id": "ORDER-123",
    "status": "APPROVED",
    "purchase_units": [
      {
        "custom_id": "COL789123",
        "amount": {
          "value": "1210.00",
          "currency_code": "EUR"
        }
      }
    ]
  }
}
```

### Autenticazione API

#### JWT Token Authentication
```php
// Richiesta token (admin/customer)
POST /wp-json/colitalia/v1/auth/token

// Body:
{
  "username": "admin",
  "password": "password"
}

// Risposta:
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "username": "admin",
    "roles": ["administrator"]
  }
}

// Uso token nelle richieste:
// Header: Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

#### API Key Authentication
```php
// Genera API key in Impostazioni > API
// Header: X-API-Key: colitalia_live_sk_1234567890abcdef
```

## Customizzazione Template

### Hierarchy Template

```
1. theme/colitalia-templates/[template].php
2. theme/colitalia/[template].php  
3. plugin/templates/[template].php (fallback)
```

### Template Disponibili

```php
// Template principali
single-property.php          // Singola proprietà
archive-property.php         // Archivio proprietà
booking-form.php            // Form prenotazione
booking-confirmation.php    // Conferma prenotazione

// Template parziali
partials/property-card.php       // Card proprietà in lista
partials/property-gallery.php    // Gallery immagini
partials/property-amenities.php  // Lista servizi
partials/booking-calendar.php    // Calendario prenotazioni
partials/price-calculator.php    // Calcolatore prezzi

// Email templates
email/booking-confirmation.php   // Conferma prenotazione
email/booking-reminder.php       // Promemoria soggiorno
email/booking-cancelled.php      // Prenotazione annullata
```

### Personalizzazione Template

#### 1. Copia Template
```bash
# Crea directory nel tema
mkdir -p wp-content/themes/tuo-tema/colitalia-templates/

# Copia template da personalizzare
cp wp-content/plugins/colitalia-real-estate/templates/single-property.php wp-content/themes/tuo-tema/colitalia-templates/
```

#### 2. Modifica Template
```php
<?php
// wp-content/themes/tuo-tema/colitalia-templates/single-property.php

get_header();

$property = new Colitalia_Property(get_the_ID());
$images = $property->get_gallery();
$amenities = $property->get_amenities();
$pricing = $property->get_pricing();
?>

<div class="colitalia-single-property custom-style">
    
    <!-- Hero Section Personalizzata -->
    <div class="property-hero">
        <?php if (!empty($images)): ?>
            <div class="property-gallery-slider">
                <?php foreach ($images as $image): ?>
                    <div class="slide">
                        <img src="<?php echo esc_url($image['url']); ?>" 
                             alt="<?php echo esc_attr($image['alt']); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="property-header">
            <h1 class="property-title"><?php the_title(); ?></h1>
            <div class="property-location">
                <i class="icon-location"></i>
                <?php echo esc_html($property->get_address()); ?>
            </div>
            <div class="property-price">
                Da <strong>€<?php echo number_format($pricing['base_price'], 0, ',', '.'); ?></strong>/notte
            </div>
        </div>
    </div>
    
    <!-- Content Layout -->
    <div class="property-content-wrapper">
        <div class="property-main-content">
            
            <!-- Descrizione -->
            <section class="property-description">
                <h2>Descrizione</h2>
                <div class="content">
                    <?php the_content(); ?>
                </div>
            </section>
            
            <!-- Caratteristiche -->
            <section class="property-features">
                <h2>Caratteristiche</h2>
                <div class="features-grid">
                    <div class="feature">
                        <i class="icon-bed"></i>
                        <span><?php echo $property->get_bedrooms(); ?> Camere</span>
                    </div>
                    <div class="feature">
                        <i class="icon-bath"></i>
                        <span><?php echo $property->get_bathrooms(); ?> Bagni</span>
                    </div>
                    <div class="feature">
                        <i class="icon-users"></i>
                        <span>Max <?php echo $property->get_max_guests(); ?> Ospiti</span>
                    </div>
                    <?php if ($property->get_area_size()): ?>
                    <div class="feature">
                        <i class="icon-expand"></i>
                        <span><?php echo $property->get_area_size(); ?>m²</span>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Servizi -->
            <?php if (!empty($amenities)): ?>
            <section class="property-amenities">
                <h2>Servizi Inclusi</h2>
                <div class="amenities-grid">
                    <?php foreach ($amenities as $amenity): ?>
                        <div class="amenity-item">
                            <i class="icon-<?php echo esc_attr($amenity['icon']); ?>"></i>
                            <span><?php echo esc_html($amenity['name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            
        </div>
        
        <!-- Sidebar Booking -->
        <div class="property-sidebar">
            <?php 
            // Include il form di prenotazione
            colitalia_get_template_part('booking-form', [
                'property_id' => get_the_ID()
            ]); 
            ?>
        </div>
    </div>
    
</div>

<?php
// Hook per contenuto aggiuntivo
do_action('colitalia_after_single_property_content', get_the_ID());

get_footer();
```

### Template Functions

```php
<?php
// functions.php

/**
 * Carica template personalizzato
 */
function colitalia_get_template_part($template, $args = []) {
    extract($args);
    
    $template_file = $template . '.php';
    
    // Cerca nel tema
    $theme_template = locate_template([
        'colitalia-templates/' . $template_file,
        'colitalia/' . $template_file
    ]);
    
    if ($theme_template) {
        include $theme_template;
    } else {
        // Fallback al plugin
        $plugin_template = COLITALIA_PATH . 'templates/' . $template_file;
        if (file_exists($plugin_template)) {
            include $plugin_template;
        }
    }
}

/**
 * Helper per formattazione prezzo
 */
function colitalia_format_price($amount, $currency = 'EUR') {
    $symbols = [
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£'
    ];
    
    $symbol = $symbols[$currency] ?? $currency;
    
    return $symbol . number_format($amount, 0, ',', '.');
}

/**
 * Ottieni URL prenotazione
 */
function colitalia_get_booking_url($property_id, $args = []) {
    $base_url = home_url('/prenotazione/');
    $args['property'] = $property_id;
    
    return add_query_arg($args, $base_url);
}
```

## Estensioni e Modifiche

### Creare Add-on Plugin

```php
<?php
/**
 * Plugin Name: Colitalia SMS Notifications
 * Description: Aggiunge notifiche SMS al plugin Colitalia Real Estate
 * Version: 1.0.0
 * Requires: Colitalia Real Estate Manager
 */

// Controlla se plugin base è attivo
if (!class_exists('Colitalia_Real_Estate_Main')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>'
           . 'Colitalia SMS Notifications richiede il plugin Colitalia Real Estate Manager.'
           . '</p></div>';
    });
    return;
}

class Colitalia_SMS_Extension {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Hook per invio SMS alla conferma prenotazione
        add_action('colitalia_booking_confirmed', [$this, 'send_booking_sms']);
        
        // Hook per promemoria check-in
        add_action('colitalia_checkin_reminder', [$this, 'send_checkin_reminder']);
        
        // Aggiunge campo telefono in configurazione
        add_filter('colitalia_admin_settings_fields', [$this, 'add_sms_settings']);
    }
    
    /**
     * Invia SMS conferma prenotazione
     */
    public function send_booking_sms($booking_id) {
        $booking = new Colitalia_Booking($booking_id);
        $customer = $booking->get_customer();
        
        if (!$customer->get_phone()) {
            return;
        }
        
        $message = sprintf(
            "Ciao %s! La tua prenotazione %s è confermata. Check-in: %s alle %s. Info: %s",
            $customer->get_first_name(),
            $booking->get_booking_code(),
            $booking->get_check_in_date('d/m/Y'),
            $booking->get_property()->get_check_in_time(),
            home_url('/la-tua-prenotazione/?code=' . $booking->get_booking_code())
        );
        
        $this->send_sms($customer->get_phone(), $message);
    }
    
    /**
     * Integrazione con servizio SMS (esempio)
     */
    private function send_sms($phone, $message) {
        $api_key = get_option('colitalia_sms_api_key');
        $sender = get_option('colitalia_sms_sender', 'Colitalia');
        
        if (!$api_key) {
            error_log('Colitalia SMS: API key non configurata');
            return false;
        }
        
        $response = wp_remote_post('https://api.smsservice.com/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'to' => $phone,
                'from' => $sender,
                'message' => $message
            ])
        ]);
        
        if (is_wp_error($response)) {
            error_log('Colitalia SMS Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return isset($body['success']) && $body['success'];
    }
    
    /**
     * Aggiunge campi SMS nelle impostazioni
     */
    public function add_sms_settings($fields) {
        $fields['sms'] = [
            'title' => 'Notifiche SMS',
            'fields' => [
                'colitalia_sms_api_key' => [
                    'label' => 'API Key SMS',
                    'type' => 'text',
                    'description' => 'Chiave API del servizio SMS'
                ],
                'colitalia_sms_sender' => [
                    'label' => 'Mittente SMS',
                    'type' => 'text',
                    'default' => 'Colitalia',
                    'description' => 'Nome mittente SMS (max 11 caratteri)'
                ],
                'colitalia_sms_enabled' => [
                    'label' => 'Abilita SMS',
                    'type' => 'checkbox',
                    'description' => 'Attiva invio automatico SMS'
                ]
            ]
        ];
        
        return $fields;
    }
}

// Inizializza estensione
new Colitalia_SMS_Extension();
```

## Sviluppo Add-on

### Struttura Add-on

```
colitalia-addon-example/
├── colitalia-addon-example.php     # File principale
├── includes/                       # Classi PHP
│   ├── class-addon-main.php
│   └── class-integration.php
├── assets/                         # CSS/JS specifici
│   ├── css/addon-styles.css
│   └── js/addon-scripts.js
├── templates/                      # Template aggiuntivi
│   └── addon-feature.php
└── languages/                      # Traduzioni
    └── addon-it_IT.po
```

### Checklist Sviluppo

- ✅ **Dependency Check**: Verifica plugin base attivo
- ✅ **Namespace**: Usa prefisso unico per evitare conflitti
- ✅ **Hooks**: Utilizza hook esistenti quando possibile
- ✅ **Database**: Segui naming convention tabelle
- ✅ **Security**: Sanitizza input e valida permessi
- ✅ **i18n**: Internazionalizza stringhe
- ✅ **Documentation**: Documenta API e hook custom

---

**Versione**: 1.0  
**Ultimo Aggiornamento**: Settembre 2025