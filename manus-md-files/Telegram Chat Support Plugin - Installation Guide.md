# Telegram Chat Support Plugin - Installation Guide

## Quick Start

This package contains a complete WordPress plugin that enables a chat interface connected to a Telegram bot for customer support.

## What's Included

- **telegram-chat-support-plugin.zip** - The complete WordPress plugin ready for installation
- **README.md** - User documentation with setup instructions
- **DEVELOPER_DOCS.md** - Comprehensive developer documentation
- **plugin_architecture_design.md** - Technical architecture overview

## Installation Steps

### 1. Install the Plugin

**Option A: Via WordPress Admin (Recommended)**
1. Log in to your WordPress admin panel
2. Go to Plugins > Add New
3. Click "Upload Plugin"
4. Choose the `telegram-chat-support-plugin.zip` file
5. Click "Install Now"
6. Click "Activate Plugin"

**Option B: Via FTP**
1. Extract the zip file
2. Upload the `telegram-chat-plugin` folder to `/wp-content/plugins/`
3. Go to Plugins in WordPress admin and activate "Telegram Chat Support"

### 2. Create a Telegram Bot

1. Open Telegram and search for `@BotFather`
2. Send `/newbot` command
3. Follow the instructions to create your bot
4. Copy the bot token (format: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

### 3. Get Your Telegram Chat ID

1. Search for `@userinfobot` on Telegram
2. Send any message to get your chat ID
3. Note down the chat ID (a number like `123456789`)

### 4. Configure the Plugin

1. Go to Settings > Telegram Chat in WordPress admin
2. Enter your bot token
3. Add your chat ID (one per line for multiple admins)
4. Choose position and theme
5. Click "Save Settings"
6. Click "Send Test Message" to verify setup

### 5. Test the Setup

1. Visit your website frontend
2. Look for the chat button (bottom right by default)
3. Send a test message
4. Check your Telegram for the message
5. Reply using format: `[ChatID: chat_xxx] Your response`

## Important Requirements

- **HTTPS Required**: Your website MUST use HTTPS for Telegram webhooks to work
- **PHP 7.4+**: Minimum PHP version required
- **WordPress 5.0+**: Minimum WordPress version
- **Outbound HTTPS**: Your server must allow outbound HTTPS connections

## How to Reply to Messages

When you receive a message from a website visitor in Telegram, it will look like this:

```
🔔 New message from website visitor

Chat ID: chat_abc123
IP: 192.168.1.1
Browser: Mozilla/5.0...
Page: /contact

Message:
Hello, I need help with my order.

To reply, send: [ChatID: chat_abc123] Your response here
```

To respond, send a message in this exact format:
```
[ChatID: chat_abc123] Hello! I'd be happy to help with your order. Can you provide your order number?
```

The visitor will see your response in their chat window on the website.

## Troubleshooting

### Chat widget not appearing
- Verify plugin is activated
- Check that bot token is entered
- Ensure website uses HTTPS

### Messages not reaching Telegram
- Verify bot token is correct
- Check chat IDs are valid numbers
- Test with "Send Test Message" button

### Responses not reaching website
- Ensure you're using correct reply format: `[ChatID: xxx] message`
- Check webhook status in plugin settings
- Verify website is accessible from internet

## Support

For detailed documentation, see:
- `README.md` - Complete user guide
- `DEVELOPER_DOCS.md` - Technical documentation

## Security Notes

- The plugin includes comprehensive security measures
- All inputs are sanitized and validated
- Webhook requests are verified with secret tokens
- Database queries use prepared statements

## Features

✅ Responsive chat widget
✅ Real-time message delivery
✅ Private message routing
✅ Multiple admin support
✅ Mobile-friendly design
✅ Multiple themes
✅ Security hardened
✅ Easy configuration

Your Telegram Chat Support plugin is now ready to use!

