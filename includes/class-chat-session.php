<?php
/**
 * Chat session management class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TelegramChatSession {
    
    private $chat_id;
    private $session_data;
    private $session_id;
    
    /**
     * Constructor
     */
    public function __construct($chat_id) {
        $this->chat_id = $chat_id;
        $this->load_or_create_session();
    }
    
    /**
     * Load existing session or create new one
     */
    private function load_or_create_session() {
        $this->session_data = TelegramChatDatabase::get_session_by_chat_id($this->chat_id);
        
        if (!$this->session_data) {
            // Create new session
            $website_user_id = $this->get_website_user_id();
            $this->session_id = TelegramChatDatabase::create_session($this->chat_id, $website_user_id);
            
            if ($this->session_id) {
                $this->session_data = TelegramChatDatabase::get_session_by_chat_id($this->chat_id);
            }
        } else {
            $this->session_id = $this->session_data->id;
        }
    }
    
    /**
     * Get website user ID (IP + User Agent hash for guests)
     */
    private function get_website_user_id() {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }
        
        $ip = $this->get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return 'guest_' . md5($ip . $user_agent);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Send message to Telegram admins
     */
    public function send_message_to_telegram($message) {
        if (!$this->session_id) {
            return false;
        }
        
        // Store message in database
        $message_id = TelegramChatDatabase::add_message(
            $this->session_id,
            'user_to_admin',
            $message,
            $this->get_website_user_id()
        );
        
        if (!$message_id) {
            return false;
        }
        
        // Get plugin options
        $options = get_option('telegram_chat_options', array());
        $bot_token = $options['bot_token'] ?? '';
        $admin_chat_ids = $options['admin_chat_ids'] ?? array();
        
        if (empty($bot_token) || empty($admin_chat_ids)) {
            return false;
        }
        
        // Prepare user info
        $user_info = array(
            'ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'page_url' => $_SERVER['HTTP_REFERER'] ?? ''
        );
        
        // Send to Telegram admins
        $telegram_api = new TelegramAPI($bot_token);
        $formatted_message = TelegramAPI::format_admin_message($this->chat_id, $message, $user_info);
        
        $results = $telegram_api->send_message_to_multiple($admin_chat_ids, $formatted_message);
        
        // Check if at least one message was sent successfully
        foreach ($results as $result) {
            if ($result !== false) {
                // Update session status
                TelegramChatDatabase::update_session($this->session_id, array(
                    'status' => 'pending_admin_response'
                ));
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Receive message from Telegram admin
     */
    public function receive_message_from_telegram($message, $admin_chat_id) {
        if (!$this->session_id) {
            return false;
        }
        
        // Store message in database
        $message_id = TelegramChatDatabase::add_message(
            $this->session_id,
            'admin_to_user',
            $message,
            $admin_chat_id
        );
        
        if ($message_id) {
            // Update session with admin info
            TelegramChatDatabase::update_session($this->session_id, array(
                'telegram_admin_id' => $admin_chat_id,
                'status' => 'active'
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get new messages for this session
     */
    public function get_new_messages($last_message_id = 0) {
        if (!$this->session_id) {
            return array();
        }
        
        $messages = TelegramChatDatabase::get_new_messages($this->session_id, $last_message_id);
        
        // Format messages for frontend
        $formatted_messages = array();
        foreach ($messages as $message) {
            $formatted_messages[] = array(
                'id' => $message->id,
                'type' => $message->message_type,
                'text' => $message->message_text,
                'timestamp' => $message->created_at,
                'sender' => $message->message_type === 'admin_to_user' ? 'admin' : 'user'
            );
        }
        
        return $formatted_messages;
    }
    
    /**
     * Get all messages for this session
     */
    public function get_all_messages($limit = 50) {
        if (!$this->session_id) {
            return array();
        }
        
        $messages = TelegramChatDatabase::get_session_messages($this->session_id, $limit);
        
        // Format messages for frontend
        $formatted_messages = array();
        foreach ($messages as $message) {
            $formatted_messages[] = array(
                'id' => $message->id,
                'type' => $message->message_type,
                'text' => $message->message_text,
                'timestamp' => $message->created_at,
                'sender' => $message->message_type === 'admin_to_user' ? 'admin' : 'user'
            );
        }
        
        return $formatted_messages;
    }
    
    /**
     * Close session
     */
    public function close_session() {
        if ($this->session_id) {
            return TelegramChatDatabase::update_session($this->session_id, array(
                'status' => 'closed'
            ));
        }
        
        return false;
    }
    
    /**
     * Get session status
     */
    public function get_status() {
        return $this->session_data->status ?? 'unknown';
    }
    
    /**
     * Generate unique chat ID
     */
    public static function generate_chat_id() {
        return 'chat_' . wp_generate_password(16, false);
    }
}

