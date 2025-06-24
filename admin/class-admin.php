<?php
/**
 * Admin interface class for plugin settings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TelegramChatAdmin {
    
    /**
     * Render settings page
     */
    public static function render_settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['telegram_chat_nonce'], 'telegram_chat_settings')) {
            self::save_settings();
        }
        
        // Handle test message
        if (isset($_POST['test_message']) && wp_verify_nonce($_POST['telegram_chat_nonce'], 'telegram_chat_settings')) {
            self::send_test_message();
        }
        
        $options = get_option('telegram_chat_options', array());
        
        ?>
        <div class="wrap">
            <h1><?php _e('Telegram Chat Settings', 'telegram-chat-support'); ?></h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully!', 'telegram-chat-support'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['test-sent'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Test message sent successfully!', 'telegram-chat-support'); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['test-error'])): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php _e('Error sending test message. Please check your settings.', 'telegram-chat-support'); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('telegram_chat_settings', 'telegram_chat_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="bot_token"><?php _e('Telegram Bot Token', 'telegram-chat-support'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="bot_token" name="bot_token" value="<?php echo esc_attr($options['bot_token'] ?? ''); ?>" class="regular-text" />
                            <p class="description">
                                <?php _e('Get your bot token from @BotFather on Telegram.', 'telegram-chat-support'); ?>
                                <a href="https://core.telegram.org/bots#6-botfather" target="_blank"><?php _e('Learn how', 'telegram-chat-support'); ?></a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="admin_chat_ids"><?php _e('Admin Chat IDs', 'telegram-chat-support'); ?></label>
                        </th>
                        <td>
                            <textarea id="admin_chat_ids" name="admin_chat_ids" rows="5" class="large-text"><?php echo esc_textarea(implode("\n", $options['admin_chat_ids'] ?? array())); ?></textarea>
                            <p class="description">
                                <?php _e('Enter one Telegram chat ID per line. These are the admins who will receive questions from website visitors.', 'telegram-chat-support'); ?>
                                <br>
                                <?php _e('To get your chat ID, send a message to @userinfobot on Telegram.', 'telegram-chat-support'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="chat_position"><?php _e('Chat Widget Position', 'telegram-chat-support'); ?></label>
                        </th>
                        <td>
                            <select id="chat_position" name="chat_position">
                                <option value="bottom-right" <?php selected($options['chat_position'] ?? 'bottom-right', 'bottom-right'); ?>><?php _e('Bottom Right', 'telegram-chat-support'); ?></option>
                                <option value="bottom-left" <?php selected($options['chat_position'] ?? 'bottom-right', 'bottom-left'); ?>><?php _e('Bottom Left', 'telegram-chat-support'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="chat_theme"><?php _e('Chat Theme', 'telegram-chat-support'); ?></label>
                        </th>
                        <td>
                            <select id="chat_theme" name="chat_theme">
                                <option value="default" <?php selected($options['chat_theme'] ?? 'default', 'default'); ?>><?php _e('Default', 'telegram-chat-support'); ?></option>
                                <option value="dark" <?php selected($options['chat_theme'] ?? 'default', 'dark'); ?>><?php _e('Dark', 'telegram-chat-support'); ?></option>
                                <option value="blue" <?php selected($options['chat_theme'] ?? 'default', 'blue'); ?>><?php _e('Blue', 'telegram-chat-support'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Webhook Configuration', 'telegram-chat-support'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Webhook URL', 'telegram-chat-support'); ?></th>
                        <td>
                            <code><?php echo esc_html(admin_url('admin-ajax.php?action=telegram_webhook')); ?></code>
                            <p class="description">
                                <?php _e('This URL will be automatically configured when you save your bot token.', 'telegram-chat-support'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Webhook Status', 'telegram-chat-support'); ?></th>
                        <td>
                            <?php self::display_webhook_status($options); ?>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Settings', 'telegram-chat-support'); ?>" />
                    
                    <?php if (!empty($options['bot_token']) && !empty($options['admin_chat_ids'])): ?>
                        <input type="submit" name="test_message" id="test_message" class="button-secondary" value="<?php _e('Send Test Message', 'telegram-chat-support'); ?>" style="margin-left: 10px;" />
                    <?php endif; ?>
                </p>
            </form>
            
            <h2><?php _e('Usage Instructions', 'telegram-chat-support'); ?></h2>
            <div class="card">
                <h3><?php _e('Setup Steps:', 'telegram-chat-support'); ?></h3>
                <ol>
                    <li><?php _e('Create a Telegram bot by messaging @BotFather and get your bot token.', 'telegram-chat-support'); ?></li>
                    <li><?php _e('Get your Telegram chat ID by messaging @userinfobot.', 'telegram-chat-support'); ?></li>
                    <li><?php _e('Enter your bot token and admin chat IDs above and save settings.', 'telegram-chat-support'); ?></li>
                    <li><?php _e('Send a test message to verify everything is working.', 'telegram-chat-support'); ?></li>
                </ol>
                
                <h3><?php _e('How to Reply to Messages:', 'telegram-chat-support'); ?></h3>
                <p><?php _e('When you receive a message from a website visitor, reply in this format:', 'telegram-chat-support'); ?></p>
                <code>[ChatID: chat_abc123] Your response message here</code>
                <p><?php _e('The ChatID will be provided in each message you receive.', 'telegram-chat-support'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private static function save_settings() {
        $bot_token = sanitize_text_field($_POST['bot_token']);
        $admin_chat_ids_raw = sanitize_textarea_field($_POST['admin_chat_ids']);
        $chat_position = sanitize_text_field($_POST['chat_position']);
        $chat_theme = sanitize_text_field($_POST['chat_theme']);
        
        // Process admin chat IDs
        $admin_chat_ids = array();
        if (!empty($admin_chat_ids_raw)) {
            $lines = explode("\n", $admin_chat_ids_raw);
            foreach ($lines as $line) {
                $chat_id = trim($line);
                if (!empty($chat_id)) {
                    $admin_chat_ids[] = $chat_id;
                }
            }
        }
        
        $options = get_option('telegram_chat_options', array());
        $options['bot_token'] = $bot_token;
        $options['admin_chat_ids'] = $admin_chat_ids;
        $options['chat_position'] = $chat_position;
        $options['chat_theme'] = $chat_theme;
        
        update_option('telegram_chat_options', $options);
        
        // Set up webhook if bot token is provided
        if (!empty($bot_token)) {
            self::setup_webhook($bot_token, $options['webhook_secret'] ?? '');
        }
        
        wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
        exit;
    }
    
    /**
     * Send test message
     */
    private static function send_test_message() {
        $options = get_option('telegram_chat_options', array());
        $bot_token = $options['bot_token'] ?? '';
        $admin_chat_ids = $options['admin_chat_ids'] ?? array();
        
        if (empty($bot_token) || empty($admin_chat_ids)) {
            wp_redirect(add_query_arg('test-error', 'true', wp_get_referer()));
            exit;
        }
        
        $telegram_api = new TelegramAPI($bot_token);
        $test_message = "🧪 <b>Test Message</b>\n\nThis is a test message from your WordPress Telegram Chat Support plugin. If you receive this message, your configuration is working correctly!\n\n<i>Sent at: " . current_time('Y-m-d H:i:s') . "</i>";
        
        $success = false;
        foreach ($admin_chat_ids as $chat_id) {
            $result = $telegram_api->send_message($chat_id, $test_message);
            if ($result !== false) {
                $success = true;
            }
        }
        
        if ($success) {
            wp_redirect(add_query_arg('test-sent', 'true', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('test-error', 'true', wp_get_referer()));
        }
        exit;
    }
    
    /**
     * Setup webhook
     */
    private static function setup_webhook($bot_token, $secret_token) {
        $telegram_api = new TelegramAPI($bot_token);
        $webhook_url = admin_url('admin-ajax.php?action=telegram_webhook');
        
        $result = $telegram_api->set_webhook($webhook_url, $secret_token);
        
        if ($result) {
            set_transient('telegram_chat_webhook_info', $result, HOUR_IN_SECONDS);
        }
        
        return $result;
    }
    
    /**
     * Display webhook status
     */
    private static function display_webhook_status($options) {
        if (empty($options['bot_token'])) {
            echo '<span class="description">' . __('Enter bot token to check webhook status.', 'telegram-chat-support') . '</span>';
            return;
        }
        
        $telegram_api = new TelegramAPI($options['bot_token']);
        $webhook_info = $telegram_api->get_webhook_info();
        
        if ($webhook_info) {
            if (!empty($webhook_info['url'])) {
                echo '<span style="color: green;">✅ ' . __('Webhook is configured', 'telegram-chat-support') . '</span>';
                echo '<br><small>' . esc_html($webhook_info['url']) . '</small>';
                
                if (isset($webhook_info['pending_update_count']) && $webhook_info['pending_update_count'] > 0) {
                    echo '<br><small style="color: orange;">' . sprintf(__('Pending updates: %d', 'telegram-chat-support'), $webhook_info['pending_update_count']) . '</small>';
                }
            } else {
                echo '<span style="color: red;">❌ ' . __('Webhook not configured', 'telegram-chat-support') . '</span>';
            }
            
            if (!empty($webhook_info['last_error_message'])) {
                echo '<br><small style="color: red;">' . __('Last error:', 'telegram-chat-support') . ' ' . esc_html($webhook_info['last_error_message']) . '</small>';
            }
        } else {
            echo '<span style="color: red;">❌ ' . __('Unable to check webhook status', 'telegram-chat-support') . '</span>';
        }
    }
}

