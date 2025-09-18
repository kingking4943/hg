<?php
/**
 * Colitalia Real Estate - Sample Configuration
 * 
 * This file contains sample configuration options for the plugin.
 * Copy the needed configurations to your wp-config.php or theme functions.php
 * 
 * @package ColitaliaRealEstate
 * @version 1.5.0
 */

// ===============================================
// BASIC PLUGIN CONFIGURATION
// ===============================================

// Plugin environment (development, staging, production)
define('COLITALIA_ENV', 'production');

// Enable debug logging (set to false in production)
define('COLITALIA_DEBUG', false);

// Custom logs directory (optional)
define('COLITALIA_PLUGIN_LOGS_DIR', WP_CONTENT_DIR . '/uploads/colitalia-logs/');

// ===============================================
// DATABASE CONFIGURATION
// ===============================================

// Custom table prefix (if different from WordPress)
// define('COLITALIA_DB_PREFIX', $wpdb->prefix . 'colitalia_');

// Enable performance monitoring
define('COLITALIA_ENABLE_PERFORMANCE_MONITORING', false);

// Database connection timeout
define('COLITALIA_DB_TIMEOUT', 30);

// ===============================================
// PAYPAL CONFIGURATION
// ===============================================

// PayPal Environment (sandbox or live)
define('COLITALIA_PAYPAL_ENVIRONMENT', 'sandbox');

// PayPal Sandbox Credentials
define('COLITALIA_PAYPAL_SANDBOX_CLIENT_ID', 'your_sandbox_client_id_here');
define('COLITALIA_PAYPAL_SANDBOX_CLIENT_SECRET', 'your_sandbox_client_secret_here');

// PayPal Live Credentials (use only in production)
// define('COLITALIA_PAYPAL_LIVE_CLIENT_ID', 'your_live_client_id_here');
// define('COLITALIA_PAYPAL_LIVE_CLIENT_SECRET', 'your_live_client_secret_here');

// PayPal Webhook URL
define('COLITALIA_PAYPAL_WEBHOOK_URL', home_url('/wp-json/colitalia/v1/paypal-webhook'));

// ===============================================
// EMAIL CONFIGURATION
// ===============================================

// SMTP Configuration
define('COLITALIA_SMTP_HOST', 'smtp.yourmailserver.com');
define('COLITALIA_SMTP_PORT', 587);
define('COLITALIA_SMTP_SECURE', 'tls'); // tls, ssl, or false
define('COLITALIA_SMTP_USERNAME', 'your-email@yourdomain.com');
define('COLITALIA_SMTP_PASSWORD', 'your-email-password');

// Email Templates Directory
define('COLITALIA_EMAIL_TEMPLATES_DIR', WP_CONTENT_DIR . '/uploads/colitalia/email-templates/');

// Default sender email
define('COLITALIA_DEFAULT_FROM_EMAIL', 'noreply@yourdomain.com');
define('COLITALIA_DEFAULT_FROM_NAME', get_bloginfo('name'));

// ===============================================
// FILE UPLOAD CONFIGURATION
// ===============================================

// Maximum file upload size (in bytes)
define('COLITALIA_MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Allowed file types
define('COLITALIA_ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf,doc,docx');

// Upload directory
define('COLITALIA_UPLOAD_DIR', wp_upload_dir()['basedir'] . '/colitalia/');

// ===============================================
// SECURITY CONFIGURATION
// ===============================================

// Enable SSL verification for API calls
define('COLITALIA_VERIFY_SSL', true);

// API rate limiting (requests per minute)
define('COLITALIA_API_RATE_LIMIT', 60);

// Session timeout (in minutes)
define('COLITALIA_SESSION_TIMEOUT', 30);

// GDPR compliance settings
define('COLITALIA_GDPR_ENABLED', true);
define('COLITALIA_DATA_RETENTION_DAYS', 365);

// ===============================================
// PERFORMANCE OPTIMIZATION
// ===============================================

// Cache settings
define('COLITALIA_ENABLE_CACHE', true);
define('COLITALIA_CACHE_EXPIRY', 12 * HOUR_IN_SECONDS);

// Database optimization
define('COLITALIA_ENABLE_DB_OPTIMIZATION', true);
define('COLITALIA_AUTO_OPTIMIZE_TABLES', false);

// Image optimization
define('COLITALIA_ENABLE_IMAGE_COMPRESSION', true);
define('COLITALIA_IMAGE_QUALITY', 85);

// ===============================================
// BOOKING SYSTEM CONFIGURATION
// ===============================================

// Default booking settings
define('COLITALIA_DEFAULT_BOOKING_DURATION', 7); // days
define('COLITALIA_MIN_BOOKING_DURATION', 1);
define('COLITALIA_MAX_BOOKING_DURATION', 365);

// Booking confirmation timeout (in hours)
define('COLITALIA_BOOKING_TIMEOUT', 24);

// Maximum guests per booking
define('COLITALIA_MAX_GUESTS', 20);

// ===============================================
// MULTI-PROPERTY SYSTEM
// ===============================================

// Enable multi-property features
define('COLITALIA_ENABLE_MULTIPROPERTY', true);

// Default management fee percentage
define('COLITALIA_DEFAULT_MANAGEMENT_FEE', 10);

// Minimum investment amount
define('COLITALIA_MIN_INVESTMENT_AMOUNT', 1000);

// ===============================================
// LOCALIZATION SETTINGS
// ===============================================

// Default currency
define('COLITALIA_DEFAULT_CURRENCY', 'EUR');

// Currency symbol position (before, after)
define('COLITALIA_CURRENCY_POSITION', 'before');

// Date format
define('COLITALIA_DATE_FORMAT', 'd/m/Y');

// Time format
define('COLITALIA_TIME_FORMAT', 'H:i');

// ===============================================
// THIRD-PARTY INTEGRATIONS
// ===============================================

// Google Maps API Key
define('COLITALIA_GOOGLE_MAPS_API_KEY', 'your_google_maps_api_key_here');

// reCAPTCHA Keys (for form protection)
define('COLITALIA_RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key_here');
define('COLITALIA_RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_key_here');

// Social Media Integration
define('COLITALIA_FACEBOOK_APP_ID', 'your_facebook_app_id_here');
define('COLITALIA_INSTAGRAM_ACCESS_TOKEN', 'your_instagram_token_here');

// ===============================================
// ADVANCED FEATURES
// ===============================================

// Enable REST API
define('COLITALIA_ENABLE_REST_API', true);

// API Authentication
define('COLITALIA_API_AUTH_METHOD', 'jwt'); // jwt, basic, or none

// Enable webhooks
define('COLITALIA_ENABLE_WEBHOOKS', true);

// Webhook secret key
define('COLITALIA_WEBHOOK_SECRET', 'your-webhook-secret-key-here');

// ===============================================
// DEVELOPMENT SETTINGS
// ===============================================

// Enable development mode (only for development)
if (defined('WP_DEBUG') && WP_DEBUG) {
    define('COLITALIA_DEV_MODE', true);
    define('COLITALIA_ENABLE_QUERY_DEBUG', true);
    define('COLITALIA_LOG_ALL_QUERIES', true);
}

// Mock payment gateway for testing
// define('COLITALIA_MOCK_PAYMENTS', true);

// Test email mode (sends all emails to admin)
// define('COLITALIA_TEST_EMAIL_MODE', true);

// ===============================================
// CUSTOM HOOKS AND FILTERS
// ===============================================

/**
 * Example: Custom property search filters
 */
/*
add_filter('colitalia_property_search_args', function($args) {
    // Modify search arguments
    return $args;
});
*/

/**
 * Example: Custom booking validation
 */
/*
add_filter('colitalia_validate_booking', function($is_valid, $booking_data) {
    // Add custom validation logic
    return $is_valid;
}, 10, 2);
*/

/**
 * Example: Custom email content
 */
/*
add_filter('colitalia_email_content', function($content, $email_type, $booking_data) {
    // Modify email content
    return $content;
}, 10, 3);
*/

/**
 * Example: Custom payment processing
 */
/*
add_action('colitalia_after_payment_success', function($payment_data) {
    // Custom actions after successful payment
});
*/

// ===============================================
// MULTISITE CONFIGURATION
// ===============================================

// Enable multisite support
// define('COLITALIA_MULTISITE_ENABLED', true);

// Network-wide settings
// define('COLITALIA_NETWORK_WIDE_SETTINGS', false);

// Per-site configuration
// define('COLITALIA_PER_SITE_CONFIG', true);

// ===============================================
// BACKUP AND RECOVERY
// ===============================================

// Auto-backup before updates
define('COLITALIA_AUTO_BACKUP', true);

// Backup retention (in days)
define('COLITALIA_BACKUP_RETENTION', 30);

// Backup location
define('COLITALIA_BACKUP_DIR', WP_CONTENT_DIR . '/uploads/colitalia-backups/');

// ===============================================
// MAINTENANCE MODE
// ===============================================

// Enable maintenance mode
// define('COLITALIA_MAINTENANCE_MODE', false);

// Maintenance message
// define('COLITALIA_MAINTENANCE_MESSAGE', 'Sistema in manutenzione. Torna presto!');

// Allowed IPs during maintenance
// define('COLITALIA_MAINTENANCE_ALLOWED_IPS', ['127.0.0.1', 'your.ip.here']);

// ===============================================
// END CONFIGURATION
// ===============================================

/**
 * Note: After making changes to this file, you may need to:
 * 1. Clear any caching plugins
 * 2. Deactivate and reactivate the plugin
 * 3. Clear browser cache
 * 4. Update plugin settings in WordPress admin
 */
