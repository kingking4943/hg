<?php
/**
 * Colitalia Real Estate Plugin Uninstall
 * 
 * This file runs when the plugin is deleted from WordPress admin.
 * It cleans up all plugin data, tables, and files.
 *
 * @package ColitaliaRealEstate
 * @version 1.5.0
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load plugin constants for cleanup
define('COLITALIA_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('COLITALIA_PLUGIN_LOGS_DIR', WP_CONTENT_DIR . '/colitalia-logs/');

/**
 * Uninstall cleanup class
 */
class ColitaliaRealEstateUninstaller {
    
    /**
     * Run complete uninstall process
     */
    public static function uninstall() {
        // Verify user permissions
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Check if we should remove all data
        $remove_data = get_option('colitalia_remove_data_on_uninstall', false);
        
        self::log_uninstall_start();
        
        // Always remove plugin options
        self::remove_plugin_options();
        
        // Remove custom post types and taxonomy terms
        self::remove_custom_content();
        
        // Clear cron jobs
        self::clear_cron_jobs();
        
        if ($remove_data) {
            // Remove database tables
            self::remove_database_tables();
            
            // Remove uploaded files
            self::remove_uploaded_files();
            
            // Remove log files
            self::remove_log_files();
        }
        
        // Clear object cache
        self::clear_cache();
        
        self::log_uninstall_complete();
    }
    
    /**
     * Remove all plugin options
     */
    private static function remove_plugin_options() {
        global $wpdb;
        
        // Remove all options starting with 'colitalia_'
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'colitalia_%'");
        
        // Remove specific meta keys
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_colitalia_%'");
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'colitalia_%'");
        
        // Remove transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_colitalia_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_colitalia_%'");
    }
    
    /**
     * Remove custom post types and taxonomy data
     */
    private static function remove_custom_content() {
        global $wpdb;
        
        // Get all proprieta posts
        $property_posts = get_posts([
            'post_type' => 'proprieta',
            'numberposts' => -1,
            'post_status' => 'any'
        ]);
        
        // Delete each property post and its meta
        foreach ($property_posts as $post) {
            wp_delete_post($post->ID, true);
        }
        
        // Remove property_type taxonomy terms
        $terms = get_terms([
            'taxonomy' => 'property_type',
            'hide_empty' => false,
            'number' => 0
        ]);
        
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, 'property_type');
        }
        
        // Clean up orphaned term relationships
        $wpdb->query("DELETE tr FROM {$wpdb->term_relationships} tr 
                     LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID 
                     WHERE p.ID IS NULL");
        
        // Clean up unused terms
        $wpdb->query("DELETE tt FROM {$wpdb->term_taxonomy} tt 
                     LEFT JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id 
                     WHERE tr.object_id IS NULL");
        
        $wpdb->query("DELETE t FROM {$wpdb->terms} t 
                     LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
                     WHERE tt.term_id IS NULL");
    }
    
    /**
     * Clear all cron jobs
     */
    private static function clear_cron_jobs() {
        $cron_jobs = [
            'colitalia_daily_maintenance',
            'colitalia_cleanup_expired_bookings',
            'colitalia_send_reminder_emails'
        ];
        
        foreach ($cron_jobs as $job) {
            wp_clear_scheduled_hook($job);
        }
    }
    
    /**
     * Remove database tables
     */
    private static function remove_database_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'colitalia_properties',
            $wpdb->prefix . 'colitalia_bookings',
            $wpdb->prefix . 'colitalia_clients',
            $wpdb->prefix . 'colitalia_multiproperty_investments',
            $wpdb->prefix . 'colitalia_email_logs',
            $wpdb->prefix . 'colitalia_performance_logs'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Remove uploaded files and directories
     */
    private static function remove_uploaded_files() {
        $upload_dir = wp_upload_dir();
        $colitalia_uploads = $upload_dir['basedir'] . '/colitalia/';
        
        if (is_dir($colitalia_uploads)) {
            self::recursive_rmdir($colitalia_uploads);
        }
    }
    
    /**
     * Remove log files
     */
    private static function remove_log_files() {
        if (is_dir(COLITALIA_PLUGIN_LOGS_DIR)) {
            self::recursive_rmdir(COLITALIA_PLUGIN_LOGS_DIR);
        }
    }
    
    /**
     * Clear cache
     */
    private static function clear_cache() {
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear rewrite rules
        flush_rewrite_rules();
        
        // Clear opcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
    
    /**
     * Recursively remove directory
     */
    private static function recursive_rmdir($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::recursive_rmdir($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Log uninstall start
     */
    private static function log_uninstall_start() {
        error_log('[Colitalia Real Estate] Starting plugin uninstall process');
        
        // Create uninstall log entry
        update_option('colitalia_uninstall_started', current_time('mysql'));
    }
    
    /**
     * Log uninstall completion
     */
    private static function log_uninstall_complete() {
        error_log('[Colitalia Real Estate] Plugin uninstall process completed');
        
        // Remove the uninstall start option (cleanup)
        delete_option('colitalia_uninstall_started');
    }
}

// Run the uninstaller
ColitaliaRealEstateUninstaller::uninstall();
