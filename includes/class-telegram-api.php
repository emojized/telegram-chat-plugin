<?php
/**
 * Telegram API class for bot communication
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TelegramAPI {
    
    private $bot_token;
    private $api_url;
    
    /**
     * Constructor
     */
    public function __construct($bot_token) {
        $this->bot_token = $bot_token;
        $this->api_url = 'https://api.telegram.org/bot' . $bot_token . '/';
    }
    
    /**
     * Send message to Telegram
     */
    public function send_message($chat_id, $text, $parse_mode = 'HTML') {
        $data = array(
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => $parse_mode
        );
        
        return $this->make_request('sendMessage', $data);
    }
    
    /**
     * Send message to multiple chat IDs
     */
    public function send_message_to_multiple($chat_ids, $text, $parse_mode = 'HTML') {
        $results = array();
        
        foreach ($chat_ids as $chat_id) {
            $results[$chat_id] = $this->send_message($chat_id, $text, $parse_mode);
        }
        
        return $results;
    }
    
    /**
     * Set webhook
     */
    public function set_webhook($webhook_url, $secret_token = null) {
        $data = array(
            'url' => $webhook_url
        );
        
        if ($secret_token) {
            $data['secret_token'] = $secret_token;
        }
        
        return $this->make_request('setWebhook', $data);
    }
    
    /**
     * Delete webhook
     */
    public function delete_webhook() {
        return $this->make_request('deleteWebhook');
    }
    
    /**
     * Get webhook info
     */
    public function get_webhook_info() {
        return $this->make_request('getWebhookInfo');
    }
    
    /**
     * Get bot info
     */
    public function get_me() {
        return $this->make_request('getMe');
    }
    
    /**
     * Make API request to Telegram
     */
    private function make_request($method, $data = array()) {
        $url = $this->api_url . $method;
        
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data)
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            error_log('Telegram API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (!$decoded || !$decoded['ok']) {
            error_log('Telegram API Error: ' . $body);
            return false;
        }
        
        return $decoded['result'];
    }
    
    /**
     * Validate bot token
     */
    public function validate_token() {
        $result = $this->get_me();
        return $result !== false;
    }
    
    /**
     * Format message for Telegram admins
     */
    public static function format_admin_message($chat_id, $message, $user_info = array()) {
        $formatted = "<b>New message from website visitor</b>\n\n";
        $formatted .= "<b>Chat ID:</b> <code>$chat_id</code>\n";
        
        if (!empty($user_info['ip'])) {
            $formatted .= "<b>IP:</b> <code>{$user_info['ip']}</code>\n";
        }
        
        if (!empty($user_info['user_agent'])) {
            $formatted .= "<b>Browser:</b> <code>" . substr($user_info['user_agent'], 0, 50) . "...</code>\n";
        }
        
        if (!empty($user_info['page_url'])) {
            $formatted .= "<b>Page:</b> <a href=\"{$user_info['page_url']}\">" . parse_url($user_info['page_url'], PHP_URL_PATH) . "</a>\n";
        }
        
        $formatted .= "\n<b>Message:</b>\n" . htmlspecialchars($message);
        $formatted .= "\n\n<i>To reply, send: [ChatID: $chat_id] Your response here</i>";
        
        return $formatted;
    }
    
    /**
     * Parse admin response
     */
    public static function parse_admin_response($text) {
        if (preg_match('/\[ChatID:\s*([^\]]+)\]\s*(.*)/', $text, $matches)) {
            return array(
                'chat_id' => trim($matches[1]),
                'message' => trim($matches[2])
            );
        }
        
        return false;
    }
    
    /**
     * Get chat member info
     */
    public function get_chat_member($chat_id, $user_id) {
        $data = array(
            'chat_id' => $chat_id,
            'user_id' => $user_id
        );
        
        return $this->make_request('getChatMember', $data);
    }
    
    /**
     * Test if chat ID is valid
     */
    public function test_chat_id($chat_id) {
        $test_message = "✅ Telegram Chat Support plugin test message. Your chat ID is configured correctly!";
        return $this->send_message($chat_id, $test_message);
    }
}

