# Telegram Chat Support WordPress Plugin

A WordPress plugin that provides a chat interface connected to a Telegram bot for customer support. Website visitors can ask questions and receive private answers from designated Telegram administrators.

## Features

- **Frontend Chat Widget**: A responsive chat interface positioned at the bottom right (or left) of your website
- **Telegram Bot Integration**: Seamless communication between your website and Telegram
- **Private Message Routing**: Ensures answers from Telegram admins are delivered only to the specific user who asked the question
- **Admin Management**: Backend interface to configure Telegram bot settings and manage admin chat IDs
- **Multiple Themes**: Default, dark, and blue themes for the chat widget
- **Mobile Responsive**: Works perfectly on desktop and mobile devices
- **Security**: Webhook security with secret tokens and input validation

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- HTTPS enabled website (required for Telegram webhooks)
- A Telegram bot token (obtained from @BotFather)

## Installation

1. **Download the Plugin**
   - Download the `telegram-chat-support.zip` file
   - Upload it to your WordPress site via the admin panel (Plugins > Add New > Upload Plugin)
   - Alternatively, extract the files and upload the `telegram-chat-plugin` folder to `/wp-content/plugins/`

2. **Activate the Plugin**
   - Go to Plugins in your WordPress admin panel
   - Find "Telegram Chat Support" and click "Activate"

3. **Create a Telegram Bot**
   - Open Telegram and search for @BotFather
   - Send `/newbot` command and follow the instructions
   - Choose a name and username for your bot
   - Copy the bot token provided by BotFather

4. **Get Your Telegram Chat ID**
   - Search for @userinfobot on Telegram
   - Send any message to get your chat ID
   - Note down the chat ID (it will be a number like 123456789)

5. **Configure the Plugin**
   - Go to Settings > Telegram Chat in your WordPress admin
   - Enter your bot token
   - Add your chat ID(s) (one per line)
   - Choose your preferred position and theme
   - Click "Save Settings"

6. **Test the Setup**
   - Click "Send Test Message" to verify everything is working
   - You should receive a test message in your Telegram chat

## Usage

### For Website Visitors

1. Visitors will see a chat button at the bottom of your website
2. Clicking the button opens a chat window
3. They can type their questions and send them
4. Responses from admins will appear in the chat window

### For Telegram Admins

1. When a visitor sends a message, you'll receive it in your Telegram chat
2. The message will include:
   - The visitor's question
   - A unique Chat ID
   - Visitor information (IP, browser, page URL)

3. To reply, send a message in this format:
   ```
   [ChatID: chat_abc123] Your response message here
   ```

4. The visitor will receive your response in their chat window

## Configuration Options

### Bot Settings
- **Bot Token**: Your Telegram bot token from @BotFather
- **Admin Chat IDs**: Telegram chat IDs that will receive questions (one per line)

### Appearance
- **Position**: Bottom right or bottom left
- **Theme**: Default, dark, or blue

### Advanced
- **Webhook URL**: Automatically configured (displays current webhook URL)
- **Webhook Status**: Shows if the webhook is properly configured

## Troubleshooting

### Common Issues

1. **Chat widget not appearing**
   - Make sure the plugin is activated
   - Check that you've entered a valid bot token
   - Verify your website is using HTTPS

2. **Messages not being sent to Telegram**
   - Verify your bot token is correct
   - Check that your chat IDs are valid numbers
   - Ensure your website can make outbound HTTPS requests

3. **Not receiving responses from Telegram**
   - Check that your webhook is configured (visible in plugin settings)
   - Verify you're using the correct reply format: `[ChatID: xxx] message`
   - Make sure your website is accessible from the internet

4. **Webhook errors**
   - Ensure your website uses HTTPS (required by Telegram)
   - Check that your server can receive POST requests
   - Verify there are no firewall restrictions

### Debug Mode

To enable debug logging:
1. Add this to your `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
2. Check `/wp-content/debug.log` for error messages

## Security

- All user inputs are sanitized and validated
- Webhook requests are verified with secret tokens
- CSRF protection using WordPress nonces
- SQL injection prevention using prepared statements

## Database Tables

The plugin creates two custom tables:
- `wp_telegram_chat_sessions`: Stores chat sessions
- `wp_telegram_chat_messages`: Stores message history

These tables are automatically created on activation and removed on uninstall.

## Hooks and Filters

### Actions
- `telegram_chat_message_sent`: Fired when a message is sent to Telegram
- `telegram_chat_message_received`: Fired when a response is received from Telegram

### Filters
- `telegram_chat_widget_position`: Filter the chat widget position
- `telegram_chat_message_format`: Filter the message format sent to admins

## Support

For support and feature requests, please contact the plugin developer.

## Changelog

### Version 1.0.0
- Initial release
- Frontend chat widget with responsive design
- Telegram bot integration
- Admin interface for configuration
- Message routing and user isolation
- Multiple themes and positioning options
- Security features and input validation

## License

This project is licensed under the terms of the GNU General Public License v2.0.
See the LICENSE file for details.

Disclaimer:
This software is provided "as is", without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement. In no event shall the authors or copyright holders be liable for any claim, damages or other liability, whether in an action of contract, tort or otherwise, arising from, out of or in connection with the software or the use or other dealings in the software.

## Credits

Developed by Manus AI , THIS IS WORK IN PROGRESS AND NOT SUTIABLE ON PRODUCTION SITES

