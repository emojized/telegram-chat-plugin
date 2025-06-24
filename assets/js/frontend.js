/**
 * Telegram Chat Support - Frontend JavaScript
 */

(function($) {
    'use strict';

    class TelegramChatWidget {
        constructor() {
            this.chatId = this.generateChatId();
            this.isOpen = false;
            this.lastMessageId = 0;
            this.pollInterval = null;
            this.typingTimeout = null;
            this.isTyping = false;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.startPolling();
            this.loadChatHistory();
        }

        bindEvents() {
            // Chat button click
            $(document).on('click', '.telegram-chat-button', (e) => {
                e.preventDefault();
                this.toggleChat();
            });

            // Close button click
            $(document).on('click', '.telegram-chat-close', (e) => {
                e.preventDefault();
                this.closeChat();
            });

            // Send button click
            $(document).on('click', '.telegram-send-button', (e) => {
                e.preventDefault();
                this.sendMessage();
            });

            // Enter key in textarea
            $(document).on('keydown', '.telegram-chat-input textarea', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Auto-resize textarea
            $(document).on('input', '.telegram-chat-input textarea', (e) => {
                this.autoResizeTextarea(e.target);
            });

            // Close chat when clicking outside
            $(document).on('click', (e) => {
                if (this.isOpen && !$(e.target).closest('.telegram-chat-widget').length) {
                    this.closeChat();
                }
            });

            // Escape key to close chat
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.closeChat();
                }
            });
        }

        generateChatId() {
            // Check if we already have a chat ID in localStorage
            let chatId = localStorage.getItem('telegram_chat_id');
            if (!chatId) {
                chatId = 'chat_' + Math.random().toString(36).substr(2, 16);
                localStorage.setItem('telegram_chat_id', chatId);
            }
            return chatId;
        }

        toggleChat() {
            if (this.isOpen) {
                this.closeChat();
            } else {
                this.openChat();
            }
        }

        openChat() {
            const $widget = $('.telegram-chat-widget');
            const $window = $('.telegram-chat-window');
            const $button = $('.telegram-chat-button');

            $window.addClass('show');
            $button.addClass('chat-open');
            this.isOpen = true;

            // Focus on textarea
            setTimeout(() => {
                $('.telegram-chat-input textarea').focus();
            }, 300);

            // Scroll to bottom
            this.scrollToBottom();

            // Clear notification badge
            $('.telegram-chat-badge').hide();
        }

        closeChat() {
            const $window = $('.telegram-chat-window');
            const $button = $('.telegram-chat-button');

            $window.removeClass('show');
            $button.removeClass('chat-open');
            this.isOpen = false;
        }

        sendMessage() {
            const $textarea = $('.telegram-chat-input textarea');
            const message = $textarea.val().trim();

            if (!message) {
                return;
            }

            // Disable send button
            this.setSendButtonState(false);

            // Add message to chat immediately
            this.addMessage(message, 'user');

            // Clear textarea
            $textarea.val('').trigger('input');

            // Send to server
            this.sendMessageToServer(message);
        }

        sendMessageToServer(message) {
            $.ajax({
                url: telegramChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'telegram_chat_send_message',
                    nonce: telegramChat.nonce,
                    message: message,
                    chat_id: this.chatId
                },
                success: (response) => {
                    this.setSendButtonState(true);
                    
                    if (response.success) {
                        // Show typing indicator
                        this.showTypingIndicator();
                    } else {
                        this.showError(response.data || telegramChat.strings.error);
                    }
                },
                error: () => {
                    this.setSendButtonState(true);
                    this.showError(telegramChat.strings.error);
                }
            });
        }

        loadChatHistory() {
            this.getNewMessages();
        }

        getNewMessages() {
            $.ajax({
                url: telegramChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'telegram_chat_get_messages',
                    nonce: telegramChat.nonce,
                    chat_id: this.chatId,
                    last_message_id: this.lastMessageId
                },
                success: (response) => {
                    if (response.success && response.data.length > 0) {
                        this.hideTypingIndicator();
                        
                        response.data.forEach(message => {
                            if (message.sender === 'admin') {
                                this.addMessage(message.text, 'admin', message.timestamp);
                                
                                // Show notification if chat is closed
                                if (!this.isOpen) {
                                    this.showNotification();
                                }
                            }
                            
                            if (message.id > this.lastMessageId) {
                                this.lastMessageId = message.id;
                            }
                        });
                    }
                }
            });
        }

        startPolling() {
            // Poll for new messages every 3 seconds
            this.pollInterval = setInterval(() => {
                this.getNewMessages();
            }, 3000);
        }

        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        }

        addMessage(text, sender, timestamp = null) {
            const $messages = $('.telegram-chat-messages');
            const time = timestamp ? new Date(timestamp) : new Date();
            const timeString = time.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

            const messageHtml = `
                <div class="telegram-message ${sender}">
                    <div class="telegram-message-text">${this.escapeHtml(text)}</div>
                    <div class="telegram-message-time">${timeString}</div>
                </div>
            `;

            $messages.append(messageHtml);
            this.scrollToBottom();
        }

        showTypingIndicator() {
            if (this.isTyping) return;
            
            this.isTyping = true;
            const $messages = $('.telegram-chat-messages');
            
            const typingHtml = `
                <div class="telegram-typing-indicator show">
                    <div class="telegram-typing-dots">
                        <div class="telegram-typing-dot"></div>
                        <div class="telegram-typing-dot"></div>
                        <div class="telegram-typing-dot"></div>
                    </div>
                </div>
            `;
            
            $messages.append(typingHtml);
            this.scrollToBottom();

            // Hide typing indicator after 30 seconds
            this.typingTimeout = setTimeout(() => {
                this.hideTypingIndicator();
            }, 30000);
        }

        hideTypingIndicator() {
            $('.telegram-typing-indicator').remove();
            this.isTyping = false;
            
            if (this.typingTimeout) {
                clearTimeout(this.typingTimeout);
                this.typingTimeout = null;
            }
        }

        showNotification() {
            const $badge = $('.telegram-chat-badge');
            let count = parseInt($badge.text()) || 0;
            count++;
            $badge.text(count).show();
        }

        showError(message) {
            const $messages = $('.telegram-chat-messages');
            const errorHtml = `<div class="telegram-chat-error">${this.escapeHtml(message)}</div>`;
            $messages.append(errorHtml);
            this.scrollToBottom();

            // Remove error after 5 seconds
            setTimeout(() => {
                $('.telegram-chat-error').fadeOut(() => {
                    $('.telegram-chat-error').remove();
                });
            }, 5000);
        }

        setSendButtonState(enabled) {
            const $button = $('.telegram-send-button');
            $button.prop('disabled', !enabled);
        }

        autoResizeTextarea(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 80) + 'px';
        }

        scrollToBottom() {
            const $messages = $('.telegram-chat-messages');
            $messages.scrollTop($messages[0].scrollHeight);
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        destroy() {
            this.stopPolling();
            $(document).off('.telegram-chat');
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Only initialize if the chat widget exists
        if ($('.telegram-chat-widget').length > 0) {
            window.telegramChatWidget = new TelegramChatWidget();
        }
    });

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (window.telegramChatWidget) {
            window.telegramChatWidget.destroy();
        }
    });

})(jQuery);

