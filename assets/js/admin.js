/**
 * Telegram Chat Support - Admin JavaScript
 */

(function($) {
    'use strict';

    class TelegramChatAdmin {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.validateBotToken();
        }

        bindEvents() {
            // Bot token validation on blur
            $('#bot_token').on('blur', () => {
                this.validateBotToken();
            });

            // Test message button
            $('#test_message').on('click', (e) => {
                e.preventDefault();
                this.sendTestMessage();
            });

            // Auto-format chat IDs
            $('#admin_chat_ids').on('blur', () => {
                this.formatChatIds();
            });

            // Form validation before submit
            $('form').on('submit', (e) => {
                if (!this.validateForm()) {
                    e.preventDefault();
                }
            });

            // Copy webhook URL
            $(document).on('click', '.copy-webhook-url', (e) => {
                e.preventDefault();
                this.copyWebhookUrl();
            });
        }

        validateBotToken() {
            const token = $('#bot_token').val().trim();
            const $indicator = $('#bot-token-indicator');

            // Remove existing indicator
            $indicator.remove();

            if (!token) {
                return;
            }

            // Basic format validation
            const tokenPattern = /^\d+:[A-Za-z0-9_-]+$/;
            if (!tokenPattern.test(token)) {
                this.showTokenStatus('error', 'Invalid token format');
                return;
            }

            this.showTokenStatus('validating', 'Validating token...');

            // Validate with Telegram API (simplified check)
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'telegram_validate_token',
                    nonce: $('#telegram_chat_nonce').val(),
                    token: token
                },
                success: (response) => {
                    if (response.success) {
                        this.showTokenStatus('success', 'Token is valid');
                    } else {
                        this.showTokenStatus('error', response.data || 'Token validation failed');
                    }
                },
                error: () => {
                    this.showTokenStatus('error', 'Unable to validate token');
                }
            });
        }

        showTokenStatus(type, message) {
            const $tokenField = $('#bot_token');
            const $indicator = $('<div id="bot-token-indicator" class="telegram-status-indicator ' + type + '">' + message + '</div>');
            
            $tokenField.after($indicator);

            if (type === 'validating') {
                $indicator.prepend('<span class="telegram-loading"></span>');
            }
        }

        formatChatIds() {
            const $textarea = $('#admin_chat_ids');
            const value = $textarea.val();
            
            if (!value.trim()) {
                return;
            }

            // Split by lines and clean up
            const lines = value.split('\n');
            const cleanedLines = lines
                .map(line => line.trim())
                .filter(line => line.length > 0)
                .filter(line => /^-?\d+$/.test(line)); // Only numeric chat IDs

            $textarea.val(cleanedLines.join('\n'));

            // Show validation message
            const $validation = $('#chat-ids-validation');
            $validation.remove();

            if (cleanedLines.length !== lines.filter(l => l.trim()).length) {
                const $message = $('<div id="chat-ids-validation" class="telegram-notice notice-warning">Some invalid chat IDs were removed. Chat IDs must be numeric.</div>');
                $textarea.after($message);
                
                setTimeout(() => {
                    $message.fadeOut();
                }, 5000);
            }
        }

        validateForm() {
            const token = $('#bot_token').val().trim();
            const chatIds = $('#admin_chat_ids').val().trim();

            if (!token) {
                alert('Please enter a bot token.');
                $('#bot_token').focus();
                return false;
            }

            if (!chatIds) {
                alert('Please enter at least one admin chat ID.');
                $('#admin_chat_ids').focus();
                return false;
            }

            // Validate token format
            const tokenPattern = /^\d+:[A-Za-z0-9_-]+$/;
            if (!tokenPattern.test(token)) {
                alert('Bot token format is invalid.');
                $('#bot_token').focus();
                return false;
            }

            // Validate chat IDs
            const lines = chatIds.split('\n').filter(line => line.trim());
            const invalidIds = lines.filter(line => !/^-?\d+$/.test(line.trim()));
            
            if (invalidIds.length > 0) {
                alert('Some chat IDs are invalid. Chat IDs must be numeric.');
                $('#admin_chat_ids').focus();
                return false;
            }

            return true;
        }

        sendTestMessage() {
            const $button = $('#test_message');
            const originalText = $button.val();
            
            $button.prop('disabled', true).val('Sending...');

            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: $('form').serialize() + '&test_message=1',
                success: (response) => {
                    // The response will be a redirect, so we'll handle it via page reload
                    window.location.reload();
                },
                error: () => {
                    alert('Error sending test message. Please try again.');
                    $button.prop('disabled', false).val(originalText);
                }
            });
        }

        copyWebhookUrl() {
            const url = $('.webhook-url-display').text();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    this.showCopySuccess();
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.showCopySuccess();
            }
        }

        showCopySuccess() {
            const $message = $('<div class="telegram-notice notice-success">Webhook URL copied to clipboard!</div>');
            $('.webhook-url-display').after($message);
            
            setTimeout(() => {
                $message.fadeOut(() => {
                    $message.remove();
                });
            }, 3000);
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('#telegram_chat_nonce').length > 0) {
            new TelegramChatAdmin();
        }
    });

    // Add AJAX handler for token validation
    if (typeof ajaxurl !== 'undefined') {
        // This would need to be implemented in the main plugin file
        // as an AJAX handler for token validation
    }

})(jQuery);

