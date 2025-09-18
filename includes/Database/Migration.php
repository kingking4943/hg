<?php
/**
 * Database Migration Class (Versione Finale Stabile)
 * Gestisce la creazione e l'aggiornamento di TUTTE le tabelle del database.
 * @package Colitalia_Real_Estate
 */

namespace Colitalia_Real_Estate\Database;

defined('ABSPATH') || exit;

class Migration {
    
    public static function init() {
        // L'attivazione è gestita dal file principale del plugin
        register_activation_hook(COLITALIA_REAL_ESTATE_PLUGIN_FILE, [self::class, 'activate']);
    }
    
    public static function activate() {
        self::create_tables();
    }
    
    public static function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // --- Tabella Clienti ---
        $table_customers = $wpdb->prefix . 'colitalia_customers';
        $sql_customers = "CREATE TABLE {$table_customers} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            first_name varchar(50) NOT NULL,
            last_name varchar(50) NOT NULL,
            phone varchar(30) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        
        // --- Tabella Prenotazioni (CON AGGIUNTA COLONNA booking_type) ---
        $table_bookings = $wpdb->prefix . 'colitalia_bookings';
        $sql_bookings = "CREATE TABLE {$table_bookings} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            property_id bigint(20) NOT NULL,
            customer_id bigint(20) NOT NULL,
            booking_code varchar(20) NOT NULL,
            date_from date NOT NULL,
            date_to date NOT NULL,
            guests tinyint(2) NOT NULL DEFAULT 1,
            total_price decimal(10,2) NOT NULL DEFAULT 0.00,
            status enum('pending','confirmed','paid','cancelled','completed') NOT NULL DEFAULT 'pending',
            booking_type enum('rental', 'owner_stay', 'maintenance') NOT NULL DEFAULT 'rental',
            special_requests text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY booking_code (booking_code),
            KEY property_id (property_id),
            KEY customer_id (customer_id),
            KEY date_from (date_from),
            KEY date_to (date_to)
        ) $charset_collate;";
        
        // --- Tabella Servizi Extra ---
        $table_services = $wpdb->prefix . 'colitalia_services';
        $sql_services = "CREATE TABLE {$table_services} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            property_id bigint(20) NOT NULL,
            service_name varchar(100) NOT NULL,
            service_description text,
            service_type enum('once','per_night','per_guest','per_booking') NOT NULL DEFAULT 'once',
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            is_mandatory tinyint(1) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order tinyint(2) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY property_id (property_id)
        ) $charset_collate;";
        
        dbDelta($sql_customers);
        dbDelta($sql_bookings);
        dbDelta($sql_services);
    }
}