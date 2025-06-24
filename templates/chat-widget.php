<?php
/**
 * Chat widget template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('telegram_chat_options', array());
$position = $options['chat_position'] ?? 'bottom-right';
$theme = $options['chat_theme'] ?? 'default';
?>

<div class="telegram-chat-widget position-<?php echo esc_attr($position); ?> theme-<?php echo esc_attr($theme); ?>">
    <!-- Chat Button -->
    <button class="telegram-chat-button" aria-label="<?php _e('Open chat', 'telegram-chat-support'); ?>">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
        </svg>
        <span class="telegram-chat-badge" style="display: none;">0</span>
    </button>

    <!-- Chat Window -->
    <div class="telegram-chat-window">
        <!-- Header -->
        <div class="telegram-chat-header">
            <div>
                <h3><?php _e('Chat Support', 'telegram-chat-support'); ?></h3>
                <div class="status"><?php _e('We\'re here to help!', 'telegram-chat-support'); ?></div>
            </div>
            <button class="telegram-chat-close" aria-label="<?php _e('Close chat', 'telegram-chat-support'); ?>">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>

        <!-- Messages Area -->
        <div class="telegram-chat-messages">
            <div class="telegram-welcome-message">
                <?php _e('👋 Hello! How can we help you today?', 'telegram-chat-support'); ?>
            </div>
        </div>

        <!-- Input Area -->
        <div class="telegram-chat-input">
            <textarea 
                placeholder="<?php echo esc_attr(telegramChat.strings.placeholder ?? __('Type your message...', 'telegram-chat-support')); ?>"
                rows="1"
                maxlength="4000"
                aria-label="<?php _e('Type your message', 'telegram-chat-support'); ?>"
            ></textarea>
            <button class="telegram-send-button" aria-label="<?php _e('Send message', 'telegram-chat-support'); ?>">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

