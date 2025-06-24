<?php
/**
 * Uninstall script for Telegram Chat Support plugin
 * 
 * This file is executed when the plugin is deleted from the WordPress admin.
 * It removes all plugin data including database tables and options.
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Include the database class
require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';

// Remove database tables
TelegramChatDatabase::drop_tables();

// Remove plugin options
delete_option('telegram_chat_options');

// Remove any transients
delete_transient('telegram_chat_webhook_info');

// Clean up any scheduled events (if any were added in future versions)
wp_clear_scheduled_hook('telegram_chat_cleanup_sessions');

// Remove any user meta related to the plugin (if any)
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'telegram_chat_%'");

// Log the uninstall for debugging purposes
error_log('Telegram Chat Support plugin uninstalled and data cleaned up');

