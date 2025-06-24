# Telegram Chat Support Plugin - Developer Documentation

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [File Structure](#file-structure)
3. [Database Schema](#database-schema)
4. [API Reference](#api-reference)
5. [Hooks and Filters](#hooks-and-filters)
6. [Security Considerations](#security-considerations)
7. [Testing](#testing)
8. [Deployment](#deployment)
9. [Troubleshooting](#troubleshooting)

## Architecture Overview

The Telegram Chat Support plugin follows a modular architecture designed for maintainability, security, and extensibility. The plugin consists of several key components that work together to provide seamless communication between website visitors and Telegram administrators.

### Core Components

1. **Main Plugin Class (`TelegramChatSupport`)**: The central orchestrator that initializes all components, handles WordPress hooks, and manages the plugin lifecycle.

2. **Database Layer (`TelegramChatDatabase`)**: Manages all database operations including table creation, session management, and message storage.

3. **Telegram API Integration (`TelegramAPI`)**: Handles all communication with the Telegram Bot API, including sending messages and managing webhooks.

4. **Session Management (`TelegramChatSession`)**: Manages individual chat sessions, linking website users to their conversations and handling message routing.

5. **Admin Interface (`TelegramChatAdmin`)**: Provides the WordPress backend interface for plugin configuration and management.

6. **Frontend Components**: JavaScript and CSS files that power the chat widget interface.

### Data Flow

The plugin follows this data flow for message handling:

1. **User Sends Message**: Website visitor types a message in the chat widget
2. **AJAX Processing**: Frontend JavaScript sends the message via AJAX to WordPress
3. **Session Creation**: Backend creates or retrieves the user's chat session
4. **Telegram Delivery**: Message is formatted and sent to configured Telegram admins
5. **Admin Response**: Telegram admin replies using the specified format
6. **Webhook Processing**: Telegram sends the response to the plugin's webhook endpoint
7. **Message Routing**: Plugin parses the response and routes it to the correct user session
8. **Frontend Update**: User's chat interface is updated with the admin's response

## File Structure

```
telegram-chat-plugin/
├── telegram-chat-support.php          # Main plugin file
├── uninstall.php                      # Uninstall cleanup script
├── README.md                          # User documentation
├── test-plugin.php                    # Testing script
├── admin/
│   └── class-admin.php               # Admin interface class
├── includes/
│   ├── class-database.php            # Database management
│   ├── class-telegram-api.php        # Telegram API wrapper
│   └── class-chat-session.php        # Session management
├── assets/
│   ├── css/
│   │   ├── frontend.css              # Frontend styles
│   │   └── admin.css                 # Admin styles
│   ├── js/
│   │   ├── frontend.js               # Frontend JavaScript
│   │   └── admin.js                  # Admin JavaScript
│   └── images/                       # Plugin images (if any)
└── templates/
    └── chat-widget.php               # Chat widget template
```

## Database Schema

The plugin creates two custom database tables to manage chat sessions and messages.

### Sessions Table (`wp_telegram_chat_sessions`)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint(20) unsigned | Primary key, auto-increment |
| `chat_id` | varchar(255) | Unique chat identifier |
| `website_user_id` | varchar(255) | Website user identifier (guest hash or user ID) |
| `telegram_admin_id` | varchar(255) | Telegram admin chat ID handling this session |
| `status` | varchar(20) | Session status (open, pending_admin_response, active, closed) |
| `created_at` | datetime | Session creation timestamp |
| `updated_at` | datetime | Last update timestamp |

### Messages Table (`wp_telegram_chat_messages`)

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint(20) unsigned | Primary key, auto-increment |
| `session_id` | bigint(20) unsigned | Foreign key to sessions table |
| `message_type` | enum | Type of message (user_to_admin, admin_to_user) |
| `message_text` | longtext | Message content |
| `sender_id` | varchar(255) | Sender identifier |
| `created_at` | datetime | Message timestamp |

## API Reference

### TelegramAPI Class

#### Constructor
```php
public function __construct($bot_token)
```
Initializes the API client with the provided bot token.

#### send_message()
```php
public function send_message($chat_id, $text, $parse_mode = 'HTML')
```
Sends a message to a specific Telegram chat.

**Parameters:**
- `$chat_id` (string): Telegram chat ID
- `$text` (string): Message text
- `$parse_mode` (string): Message parsing mode (HTML, Markdown, or null)

**Returns:** Array with API response or false on failure

#### send_message_to_multiple()
```php
public function send_message_to_multiple($chat_ids, $text, $parse_mode = 'HTML')
```
Sends a message to multiple Telegram chats.

#### set_webhook()
```php
public function set_webhook($webhook_url, $secret_token = null)
```
Configures the Telegram webhook URL.

#### format_admin_message()
```php
public static function format_admin_message($chat_id, $message, $user_info = array())
```
Formats a message for sending to Telegram admins with user context.

#### parse_admin_response()
```php
public static function parse_admin_response($text)
```
Parses admin responses to extract chat ID and message content.

### TelegramChatSession Class

#### Constructor
```php
public function __construct($chat_id)
```
Creates or loads a chat session for the given chat ID.

#### send_message_to_telegram()
```php
public function send_message_to_telegram($message)
```
Sends a user message to Telegram admins.

#### receive_message_from_telegram()
```php
public function receive_message_from_telegram($message, $admin_chat_id)
```
Processes an admin response from Telegram.

#### get_new_messages()
```php
public function get_new_messages($last_message_id = 0)
```
Retrieves new messages for the session since the specified message ID.

#### generate_chat_id()
```php
public static function generate_chat_id()
```
Generates a unique chat ID for new sessions.

### TelegramChatDatabase Class

#### create_tables()
```php
public static function create_tables()
```
Creates the required database tables.

#### create_session()
```php
public static function create_session($chat_id, $website_user_id = null)
```
Creates a new chat session record.

#### add_message()
```php
public static function add_message($session_id, $message_type, $message_text, $sender_id = null)
```
Adds a message to the database.

#### cleanup_old_sessions()
```php
public static function cleanup_old_sessions($days = 30)
```
Removes old chat sessions and their messages.

## Hooks and Filters

### Action Hooks

#### telegram_chat_message_sent
Fired when a message is successfully sent to Telegram.

```php
do_action('telegram_chat_message_sent', $chat_id, $message, $admin_chat_ids);
```

**Parameters:**
- `$chat_id` (string): The chat session ID
- `$message` (string): The message content
- `$admin_chat_ids` (array): Array of admin chat IDs that received the message

#### telegram_chat_message_received
Fired when a response is received from Telegram.

```php
do_action('telegram_chat_message_received', $chat_id, $message, $admin_chat_id);
```

**Parameters:**
- `$chat_id` (string): The chat session ID
- `$message` (string): The response message
- `$admin_chat_id` (string): The admin chat ID that sent the response

### Filter Hooks

#### telegram_chat_widget_position
Filters the chat widget position.

```php
$position = apply_filters('telegram_chat_widget_position', $position, $options);
```

#### telegram_chat_message_format
Filters the message format sent to admins.

```php
$formatted_message = apply_filters('telegram_chat_message_format', $formatted_message, $chat_id, $message, $user_info);
```

#### telegram_chat_admin_chat_ids
Filters the list of admin chat IDs.

```php
$admin_chat_ids = apply_filters('telegram_chat_admin_chat_ids', $admin_chat_ids, $chat_id);
```

### Usage Examples

```php
// Add custom information to admin messages
add_filter('telegram_chat_message_format', function($formatted_message, $chat_id, $message, $user_info) {
    $formatted_message .= "\n\n<b>Custom Info:</b> " . get_custom_user_data();
    return $formatted_message;
}, 10, 4);

// Log all sent messages
add_action('telegram_chat_message_sent', function($chat_id, $message, $admin_chat_ids) {
    error_log("Message sent to Telegram: Chat ID $chat_id, Message: $message");
});

// Modify widget position based on user role
add_filter('telegram_chat_widget_position', function($position, $options) {
    if (current_user_can('administrator')) {
        return 'bottom-left'; // Admins see widget on left
    }
    return $position;
}, 10, 2);
```

## Security Considerations

### Input Validation and Sanitization

All user inputs are properly validated and sanitized using WordPress functions:

```php
// Text field sanitization
$clean_input = sanitize_text_field($_POST['input']);

// Textarea sanitization
$clean_textarea = sanitize_textarea_field($_POST['textarea']);

// HTML attribute escaping
echo '<div data-value="' . esc_attr($value) . '">';

// HTML content escaping
echo '<p>' . esc_html($content) . '</p>';
```

### CSRF Protection

All AJAX requests use WordPress nonces for CSRF protection:

```php
// Creating nonce
wp_nonce_field('telegram_chat_settings', 'telegram_chat_nonce');

// Verifying nonce
if (!wp_verify_nonce($_POST['telegram_chat_nonce'], 'telegram_chat_settings')) {
    wp_die('Security check failed');
}
```

### Webhook Security

Telegram webhooks are secured using secret tokens:

```php
// Setting webhook with secret
$telegram_api->set_webhook($webhook_url, $secret_token);

// Verifying webhook requests
$received_token = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
if ($received_token !== $secret_token) {
    http_response_code(403);
    exit;
}
```

### SQL Injection Prevention

All database queries use prepared statements:

```php
$wpdb->get_row(
    $wpdb->prepare("SELECT * FROM $table WHERE chat_id = %s", $chat_id)
);
```

### Capability Checks

Admin functions require appropriate WordPress capabilities:

```php
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
}
```

## Testing

### Unit Testing

The plugin includes a comprehensive test suite in `test-plugin.php`. To run tests:

```bash
php test-plugin.php
```

### Test Coverage

The test suite covers:
- Telegram API message formatting
- Response parsing
- Chat ID generation
- Security function validation
- Input sanitization

### Manual Testing Checklist

1. **Plugin Installation**
   - [ ] Plugin activates without errors
   - [ ] Database tables are created
   - [ ] Default options are set

2. **Admin Interface**
   - [ ] Settings page loads correctly
   - [ ] Bot token validation works
   - [ ] Chat ID formatting works
   - [ ] Test message functionality works

3. **Frontend Widget**
   - [ ] Chat widget appears on frontend
   - [ ] Widget opens and closes correctly
   - [ ] Messages can be sent
   - [ ] Responsive design works on mobile

4. **Telegram Integration**
   - [ ] Messages are sent to Telegram
   - [ ] Webhook receives responses
   - [ ] Message routing works correctly
   - [ ] User isolation is maintained

5. **Security**
   - [ ] CSRF protection works
   - [ ] Input sanitization prevents XSS
   - [ ] Webhook security validates tokens
   - [ ] SQL injection prevention works

## Deployment

### Production Deployment

1. **Server Requirements**
   - PHP 7.4 or higher
   - WordPress 5.0 or higher
   - HTTPS enabled (required for Telegram webhooks)
   - Outbound HTTPS connections allowed

2. **Installation Steps**
   ```bash
   # Upload plugin files
   wp plugin install telegram-chat-support.zip
   
   # Activate plugin
   wp plugin activate telegram-chat-support
   
   # Configure settings via admin panel
   ```

3. **Configuration**
   - Set up Telegram bot via @BotFather
   - Configure bot token in plugin settings
   - Add admin chat IDs
   - Test webhook connectivity

### Performance Optimization

1. **Database Optimization**
   ```php
   // Add to wp-config.php for better performance
   define('WP_CACHE', true);
   
   // Schedule cleanup of old sessions
   wp_schedule_event(time(), 'daily', 'telegram_chat_cleanup_sessions');
   ```

2. **Caching Considerations**
   - Exclude AJAX endpoints from caching
   - Ensure webhook URLs are not cached
   - Consider object caching for session data

### Monitoring

1. **Error Logging**
   ```php
   // Enable WordPress debug logging
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **Webhook Monitoring**
   - Monitor webhook response times
   - Check for failed webhook deliveries
   - Set up alerts for webhook errors

## Troubleshooting

### Common Issues

#### Chat Widget Not Appearing

**Symptoms:** The chat widget doesn't show on the frontend.

**Possible Causes:**
- Plugin not activated
- Bot token not configured
- JavaScript errors
- Theme conflicts

**Solutions:**
```php
// Check if plugin is active
if (!is_plugin_active('telegram-chat-support/telegram-chat-support.php')) {
    // Activate plugin
}

// Check for JavaScript errors in browser console
// Verify bot token in plugin settings
// Test with default theme
```

#### Messages Not Reaching Telegram

**Symptoms:** User messages don't appear in Telegram chats.

**Possible Causes:**
- Invalid bot token
- Incorrect chat IDs
- Network connectivity issues
- API rate limiting

**Solutions:**
```php
// Validate bot token
$api = new TelegramAPI($bot_token);
$result = $api->get_me();
if (!$result) {
    // Token is invalid
}

// Test chat IDs
foreach ($admin_chat_ids as $chat_id) {
    $result = $api->test_chat_id($chat_id);
    if (!$result) {
        // Chat ID is invalid or bot is blocked
    }
}
```

#### Webhook Not Working

**Symptoms:** Admin responses don't reach website users.

**Possible Causes:**
- HTTPS not configured
- Webhook URL not accessible
- Invalid secret token
- Server firewall blocking requests

**Solutions:**
```php
// Check webhook status
$webhook_info = $api->get_webhook_info();
if (!empty($webhook_info['last_error_message'])) {
    error_log('Webhook error: ' . $webhook_info['last_error_message']);
}

// Test webhook URL accessibility
$response = wp_remote_get($webhook_url);
if (is_wp_error($response)) {
    // URL not accessible
}
```

### Debug Mode

Enable debug mode for detailed logging:

```php
// Add to wp-config.php
define('TELEGRAM_CHAT_DEBUG', true);

// This will log all webhook requests and responses
```

### Performance Issues

#### High Database Load

**Symptoms:** Slow page loading, database timeouts.

**Solutions:**
```php
// Add database indexes
ALTER TABLE wp_telegram_chat_sessions ADD INDEX idx_chat_id (chat_id);
ALTER TABLE wp_telegram_chat_messages ADD INDEX idx_session_created (session_id, created_at);

// Implement session cleanup
TelegramChatDatabase::cleanup_old_sessions(7); // Keep only 7 days
```

#### Memory Usage

**Symptoms:** PHP memory limit errors.

**Solutions:**
```php
// Optimize message polling
// Reduce polling frequency
// Implement pagination for message history
// Use transients for caching
```

### Support and Maintenance

#### Regular Maintenance Tasks

1. **Database Cleanup**
   ```php
   // Schedule weekly cleanup
   wp_schedule_event(time(), 'weekly', 'telegram_chat_cleanup');
   ```

2. **Log Rotation**
   ```bash
   # Rotate debug logs
   logrotate /path/to/wordpress/wp-content/debug.log
   ```

3. **Security Updates**
   - Monitor WordPress security updates
   - Update plugin dependencies
   - Review webhook security logs

#### Backup Considerations

```sql
-- Backup chat data
CREATE TABLE backup_telegram_chat_sessions AS SELECT * FROM wp_telegram_chat_sessions;
CREATE TABLE backup_telegram_chat_messages AS SELECT * FROM wp_telegram_chat_messages;
```

This developer documentation provides comprehensive guidance for understanding, extending, and maintaining the Telegram Chat Support plugin. For additional support or feature requests, please refer to the plugin's support channels.

