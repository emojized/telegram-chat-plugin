<?php
/**
 * Database management class for Telegram Chat Support plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TelegramChatDatabase {
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Chat sessions table
        $sessions_table = $wpdb->prefix . 'telegram_chat_sessions';
        $sessions_sql = "CREATE TABLE $sessions_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            chat_id varchar(255) NOT NULL,
            website_user_id varchar(255) DEFAULT NULL,
            telegram_admin_id varchar(255) DEFAULT NULL,
            status varchar(20) DEFAULT 'open',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY chat_id (chat_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Messages table
        $messages_table = $wpdb->prefix . 'telegram_chat_messages';
        $messages_sql = "CREATE TABLE $messages_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id bigint(20) unsigned NOT NULL,
            message_type enum('user_to_admin', 'admin_to_user') NOT NULL,
            message_text longtext NOT NULL,
            sender_id varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY message_type (message_type),
            KEY created_at (created_at),
            FOREIGN KEY (session_id) REFERENCES $sessions_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sessions_sql);
        dbDelta($messages_sql);
    }
    
    /**
     * Drop database tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $messages_table = $wpdb->prefix . 'telegram_chat_messages';
        $sessions_table = $wpdb->prefix . 'telegram_chat_sessions';
        
        $wpdb->query("DROP TABLE IF EXISTS $messages_table");
        $wpdb->query("DROP TABLE IF EXISTS $sessions_table");
    }
    
    /**
     * Create a new chat session
     */
    public static function create_session($chat_id, $website_user_id = null) {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'telegram_chat_sessions';
        
        $result = $wpdb->insert(
            $sessions_table,
            array(
                'chat_id' => $chat_id,
                'website_user_id' => $website_user_id,
                'status' => 'open'
            ),
            array('%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get session by chat ID
     */
    public static function get_session_by_chat_id($chat_id) {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'telegram_chat_sessions';
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $sessions_table WHERE chat_id = %s", $chat_id)
        );
    }
    
    /**
     * Update session
     */
    public static function update_session($session_id, $data) {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'telegram_chat_sessions';
        
        $data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            $sessions_table,
            $data,
            array('id' => $session_id),
            null,
            array('%d')
        );
    }
    
    /**
     * Add message to session
     */
    public static function add_message($session_id, $message_type, $message_text, $sender_id = null) {
        global $wpdb;
        
        $messages_table = $wpdb->prefix . 'telegram_chat_messages';
        
        $result = $wpdb->insert(
            $messages_table,
            array(
                'session_id' => $session_id,
                'message_type' => $message_type,
                'message_text' => $message_text,
                'sender_id' => $sender_id
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get messages for session
     */
    public static function get_session_messages($session_id, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $messages_table = $wpdb->prefix . 'telegram_chat_messages';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $messages_table 
                WHERE session_id = %d 
                ORDER BY created_at ASC 
                LIMIT %d OFFSET %d",
                $session_id,
                $limit,
                $offset
            )
        );
    }
    
    /**
     * Get new messages since last message ID
     */
    public static function get_new_messages($session_id, $last_message_id = 0) {
        global $wpdb;
        
        $messages_table = $wpdb->prefix . 'telegram_chat_messages';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $messages_table 
                WHERE session_id = %d AND id > %d 
                ORDER BY created_at ASC",
                $session_id,
                $last_message_id
            )
        );
    }
    
    /**
     * Clean up old sessions
     */
    public static function cleanup_old_sessions($days = 30) {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'telegram_chat_sessions';
        $messages_table = $wpdb->prefix . 'telegram_chat_messages';
        
        // Get old session IDs
        $old_sessions = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM $sessions_table 
                WHERE updated_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
        
        if (!empty($old_sessions)) {
            $session_ids = implode(',', array_map('intval', $old_sessions));
            
            // Delete messages first (due to foreign key constraint)
            $wpdb->query("DELETE FROM $messages_table WHERE session_id IN ($session_ids)");
            
            // Delete sessions
            $wpdb->query("DELETE FROM $sessions_table WHERE id IN ($session_ids)");
        }
        
        return count($old_sessions);
    }
}

