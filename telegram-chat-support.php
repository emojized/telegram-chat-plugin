<?php
/**
 * Plugin Name: Telegram Chat Support
 * Plugin URI: https://example.com/telegram-chat-plugin
 * Description: A WordPress plugin that provides a chat interface connected to a Telegram bot for customer support. Website visitors can ask questions and receive private answers from designated Telegram administrators.
 * Version: 1.0.0
 * Author: Manus AI
 * Author URI: https://manus.ai
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: telegram-chat-support
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TELEGRAM_CHAT_PLUGIN_VERSION', '1.0.0');
define('TELEGRAM_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TELEGRAM_CHAT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TELEGRAM_CHAT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class TelegramChatSupport {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('TelegramChatSupport', 'uninstall'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hooks for frontend chat
        add_action('wp_ajax_telegram_chat_send_message', array($this, 'handle_send_message'));
        add_action('wp_ajax_nopriv_telegram_chat_send_message', array($this, 'handle_send_message'));
        add_action('wp_ajax_telegram_chat_get_messages', array($this, 'handle_get_messages'));
        add_action('wp_ajax_nopriv_telegram_chat_get_messages', array($this, 'handle_get_messages'));
        
        // Webhook endpoint for Telegram
        add_action('wp_ajax_telegram_webhook', array($this, 'handle_telegram_webhook'));
        add_action('wp_ajax_nopriv_telegram_webhook', array($this, 'handle_telegram_webhook'));
        
        // Add chat widget to frontend
        add_action('wp_footer', array($this, 'add_chat_widget'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once TELEGRAM_CHAT_PLUGIN_PATH . 'includes/class-telegram-api.php';
        require_once TELEGRAM_CHAT_PLUGIN_PATH . 'includes/class-chat-session.php';
        require_once TELEGRAM_CHAT_PLUGIN_PATH . 'includes/class-database.php';
        require_once TELEGRAM_CHAT_PLUGIN_PATH . 'admin/class-admin.php';
    }
    
    /**
     * Plugin initialization
     */
    public function init() {
        // Initialize database tables
        TelegramChatDatabase::create_tables();
        
        // Load text domain for translations
        load_plugin_textdomain('telegram-chat-support', false, dirname(TELEGRAM_CHAT_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        TelegramChatDatabase::create_tables();
        
        // Set default options
        $default_options = array(
            'bot_token' => '',
            'admin_chat_ids' => array(),
            'webhook_secret' => wp_generate_password(32, false),
            'chat_position' => 'bottom-right',
            'chat_theme' => 'default'
        );
        
        add_option('telegram_chat_options', $default_options);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Remove webhook from Telegram
        $options = get_option('telegram_chat_options', array());
        if (!empty($options['bot_token'])) {
            $telegram_api = new TelegramAPI($options['bot_token']);
            $telegram_api->delete_webhook();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove database tables
        TelegramChatDatabase::drop_tables();
        
        // Remove options
        delete_option('telegram_chat_options');
        
        // Remove any transients
        delete_transient('telegram_chat_webhook_info');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Telegram Chat Settings', 'telegram-chat-support'),
            __('Telegram Chat', 'telegram-chat-support'),
            'manage_options',
            'telegram-chat-settings',
            array('TelegramChatAdmin', 'render_settings_page')
        );
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'telegram-chat-frontend',
            TELEGRAM_CHAT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            TELEGRAM_CHAT_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'telegram-chat-frontend',
            TELEGRAM_CHAT_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            TELEGRAM_CHAT_PLUGIN_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('telegram-chat-frontend', 'telegramChat', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('telegram_chat_nonce'),
            'strings' => array(
                'placeholder' => __('Type your message...', 'telegram-chat-support'),
                'send' => __('Send', 'telegram-chat-support'),
                'connecting' => __('Connecting...', 'telegram-chat-support'),
                'error' => __('Error sending message. Please try again.', 'telegram-chat-support')
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_telegram-chat-settings' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'telegram-chat-admin',
            TELEGRAM_CHAT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            TELEGRAM_CHAT_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'telegram-chat-admin',
            TELEGRAM_CHAT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            TELEGRAM_CHAT_PLUGIN_VERSION,
            true
        );
    }
    
    /**
     * Add chat widget to frontend
     */
    public function add_chat_widget() {
        $options = get_option('telegram_chat_options', array());
        
        // Only show if bot token is configured
        if (empty($options['bot_token'])) {
            return;
        }
        
        include TELEGRAM_CHAT_PLUGIN_PATH . 'templates/chat-widget.php';
    }
    
    /**
     * Handle send message AJAX request
     */
    public function handle_send_message() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'telegram_chat_nonce')) {
            wp_die(__('Security check failed', 'telegram-chat-support'));
        }
        
        $message = sanitize_textarea_field($_POST['message']);
        $chat_id = sanitize_text_field($_POST['chat_id']);
        
        if (empty($message)) {
            wp_send_json_error(__('Message cannot be empty', 'telegram-chat-support'));
        }
        
        // Create or get chat session
        $session = new TelegramChatSession($chat_id);
        $result = $session->send_message_to_telegram($message);
        
        if ($result) {
            wp_send_json_success(__('Message sent successfully', 'telegram-chat-support'));
        } else {
            wp_send_json_error(__('Failed to send message', 'telegram-chat-support'));
        }
    }
    
    /**
     * Handle get messages AJAX request
     */
    public function handle_get_messages() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'telegram_chat_nonce')) {
            wp_die(__('Security check failed', 'telegram-chat-support'));
        }
        
        $chat_id = sanitize_text_field($_POST['chat_id']);
        $last_message_id = intval($_POST['last_message_id']);
        
        $session = new TelegramChatSession($chat_id);
        $messages = $session->get_new_messages($last_message_id);
        
        wp_send_json_success($messages);
    }
    
    /**
     * Handle Telegram webhook
     */
    public function handle_telegram_webhook() {
        // Log webhook call for debugging
        error_log('Telegram webhook called: ' . $_SERVER['REQUEST_METHOD']);
        
        // Get raw POST data
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);
        
        // Log the update for debugging
        error_log('Telegram webhook data: ' . $input);
        
        if (!$update) {
            error_log('Telegram webhook: Invalid JSON data');
            http_response_code(400);
            exit;
        }
        
        // Verify webhook secret if configured
        $options = get_option('telegram_chat_options', array());
        $secret_token = $options['webhook_secret'] ?? '';
        
        if (!empty($secret_token)) {
            $received_token = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
            if ($received_token !== $secret_token) {
                error_log('Telegram webhook: Invalid secret token');
                http_response_code(403);
                exit;
            }
        }
        
        // Process the update
        $this->process_telegram_update($update);
        
        http_response_code(200);
        exit;
    }
    
    /**
     * Process Telegram update
     */
    private function process_telegram_update($update) {
        // Log the update for debugging
        error_log('Processing Telegram update: ' . json_encode($update));
        
        if (!isset($update['message'])) {
            error_log('No message in update');
            return;
        }
        
        $message = $update['message'];
        $text = $message['text'] ?? '';
        $from_chat_id = $message['chat']['id'];
        $from_user = $message['from'] ?? array();
        
        error_log("Received message from chat ID $from_chat_id: $text");
        
        // Extract chat ID from message text using the format [ChatID: xxx]
        if (preg_match('/\[ChatID:\s*([^\]]+)\]\s*(.*)/', $text, $matches)) {
            $website_chat_id = trim($matches[1]);
            $response_text = trim($matches[2]);
            
            error_log("Parsed chat ID: $website_chat_id, Response: $response_text");
            
            if (empty($response_text)) {
                error_log('Empty response text, ignoring message');
                return;
            }
            
            // Find the session and send response to website user
            $session = new TelegramChatSession($website_chat_id);
            $result = $session->receive_message_from_telegram($response_text, $from_chat_id);
            
            if ($result) {
                error_log("Successfully processed response for chat ID: $website_chat_id");
            } else {
                error_log("Failed to process response for chat ID: $website_chat_id");
            }
        } else {
            error_log('Message does not contain valid ChatID format, ignoring');
        }
    }
}

// Initialize the plugin
TelegramChatSupport::get_instance();

