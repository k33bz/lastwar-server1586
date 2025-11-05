# Discord Bot Phase 1 - Getting Started

**Status:** ✅ Implemented
**Version:** 1.0.0
**Date:** 2025-11-04
**Issue:** #59

---

## What's Included in Phase 1

Phase 1 provides the foundation for Discord announcements:

### ✅ Features Implemented
- **Instant Messaging** - Send announcements immediately to Discord channels
- **Multi-Channel Support** - Send to multiple channels simultaneously
- **Rich Embeds** - Optional formatted messages with colors and titles
- **Role-Based Access** - R5, R4, and Admin can send messages
- **Channel Permissions** - Users can only send to their alliance channels
- **Rate Limiting** - 10 instant messages per hour per user
- **Audit Logging** - All announcements tracked in audit log
- **Message History** - View history of sent messages
- **Bot Configuration** - Admin page for testing and setup

### 📝 Files Created

#### Backend
- `admin/discord_webhook.php` - Discord API integration
- `admin/discord_api.php` - REST API endpoints
- `admin/discord_announcements.php` - User interface for sending messages
- `admin/discord_config.php` - Admin configuration page

#### Data Files
- `data/discord-channels.json` - Channel configuration
- `data/discord-history.json` - Message history
- `data/discord-templates.json` - Message templates (Phase 3)

#### Configuration
- Updated `admin/.env.example` with Discord variables
- Updated `admin/config.php` with Discord constants
- Updated `admin/composer.json` with Guzzle HTTP client

---

## Quick Start Guide

### Step 1: Configure Bot Token

1. Go to Discord Developer Portal: https://discord.com/developers/applications/1435336079409545256
2. Navigate to **Bot** section
3. Click **Reset Token** and copy the token
4. Open `admin/.env` file
5. Set the Discord configuration:

```ini
# Discord Bot Configuration
DISCORD_BOT_TOKEN=your_actual_bot_token_here
DISCORD_CLIENT_ID=1435336079409545256
DISCORD_PUBLIC_KEY=c4293986c8cc2fb7a4fdd879f1d87ff4734e75d626ae4edddf5d932d4bfa0ca2

# Enable Discord
DISCORD_ENABLED=true
DISCORD_RATE_LIMIT_ENABLED=true
```

### Step 2: Invite Bot to Server

1. Go to **OAuth2 > URL Generator** in Discord Developer Portal
2. Select scope: `bot`
3. Select permissions:
   - Send Messages
   - Embed Links
   - Read Message History
4. Copy the generated URL
5. Paste in browser and invite to your Discord server

### Step 3: Get Channel IDs

1. Enable Discord Developer Mode:
   - Discord Settings > Advanced > Developer Mode: ON
2. Right-click on channel name
3. Click "Copy ID"
4. Save the channel ID for next step

### Step 4: Configure Channels

Edit `data/discord-channels.json`:

```json
{
  "version": "1.0.0",
  "last_updated": "2025-11-04T00:00:00Z",
  "channels": [
    {
      "id": "YOUR_CHANNEL_ID_HERE",
      "name": "announcements",
      "server_id": "YOUR_SERVER_ID_HERE",
      "server_name": "Last War Server 1586",
      "alliance": "UvvU",
      "type": "alliance",
      "enabled": true,
      "description": "UvvU alliance announcements",
      "created_at": "2025-11-04T00:00:00Z",
      "created_by": "admin"
    }
  ]
}
```

**Alliance Field:**
- Use alliance tag (e.g., "UvvU", "ORCE") for alliance-specific channels
- Use `"*"` for global channels accessible by all

**Type Field:**
- `"alliance"` - Alliance-specific channel
- `"global"` - Server-wide announcements
- `"cross-alliance"` - NAP15 coordination

### Step 5: Test Connection

1. Log in to admin panel
2. Navigate to **Discord Configuration** page
3. Enter a channel ID in the test field
4. Click "Test Connection"
5. Check Discord for test message

✅ **Success!** If you see "Connection successful", the bot is working!

### Step 6: Send Your First Announcement

1. Navigate to **Discord Announcements** page
2. Select channels to target
3. Compose your message
4. Optional: Enable rich embed and choose color
5. Click "Send Announcement"
6. Check Discord to see your message!

---

## User Access Control

### Permission Matrix

| Role  | Access                  | Channels          | Rate Limit    |
|-------|-------------------------|-------------------|---------------|
| Admin | Full access             | All channels      | No limit      |
| R5    | Send announcements      | Own alliances     | 10/hour       |
| R4    | Send announcements      | Own alliances     | 10/hour       |
| APE   | No Discord access       | N/A               | N/A           |

### Alliance Access

Users can only send to channels matching their alliance assignments:

- If user has alliance `"UvvU"`, they can send to channels with `alliance: "UvvU"` or `alliance: "*"`
- If user has alliance `"*"` (admin), they can send to all channels
- Cross-alliance permissions can be configured per user (Phase 3)

---

## Features & Limitations

### ✅ What Works Now (Phase 1)

- Send instant messages to Discord
- Multi-channel targeting
- Rich embed formatting
- Role-based permissions
- Rate limiting
- Audit logging
- Message history
- Channel access control

### ⏳ Coming in Future Phases

**Phase 2: Scheduling**
- Schedule messages for future dates/times
- Timezone support
- Edit/delete scheduled messages

**Phase 3: Templates & Multi-Target**
- Message template system
- Cross-alliance messaging
- Variable substitution
- Quick select groups

**Phase 4: Recurring Messages**
- Daily/weekly recurring announcements
- Auto-repeat for reminders
- Pause/resume functionality

**Phase 5: Advanced Features**
- Advanced embed formatting
- Image attachments
- Cross-server messaging
- Analytics dashboard

---

## Common Use Cases

### Alliance Event Reminder

```
Channel: #alliance-announcements
Message:
🏰 Kingdom Event starting in 1 hour!

Remember to:
- Join your assigned rally
- Follow R4 instructions
- Use your boosts wisely

Good luck everyone! 💪
```

### Daily Reset Notice

```
Channel: #reminders
Use Embed: ✓
Title: Daily Reset in 30 Minutes
Message:
⏰ Don't forget to:
- Claim your daily rewards
- Use your stamina
- Complete alliance tasks
- Check shop for resets
```

### Cross-Alliance Coordination

```
Channels: #nap15-announcements (select multiple alliance channels)
Message:
📢 NAP15 Meeting Tonight at 8 PM EST

Topics:
- SVS strategy discussion
- Resource sharing coordination
- New alliance applications

See you there!
```

---

## Troubleshooting

### Bot Shows as Offline
**Problem:** Bot appears offline in Discord

**Solution:**
- Verify bot token is correct in `admin/.env`
- Bot doesn't need to be "online" - it uses REST API, not WebSocket connection
- As long as test connection works, the bot is functional

### "Bot lacks permission to send messages"
**Problem:** Error when sending message

**Solution:**
1. Check bot has "Send Messages" permission in channel
2. Verify bot role is above @everyone role
3. Check channel-specific permissions for bot

### "Channel not found"
**Problem:** Error about channel not existing

**Solution:**
- Verify channel ID is correct (18-20 digits)
- Ensure bot is in the Discord server
- Check channel hasn't been deleted

### "Rate limit exceeded"
**Problem:** Can't send more messages

**Solution:**
- Wait 1 hour (rate limit resets)
- Admin can send unlimited messages
- Check `admin/discord_rate_limits.json` for current usage

### "Discord integration is disabled"
**Problem:** Can't access Discord features

**Solution:**
- Set `DISCORD_ENABLED=true` in `admin/.env`
- Restart PHP if using PHP-FPM
- Clear any server cache

---

## API Endpoints

For developers integrating with the system:

### Send Instant Message
```
POST /admin/discord_api.php
action=send_instant
channel_ids=["channel_id_1","channel_id_2"]
message=Your message here
use_embed=false
csrf_token=token
```

### Get Available Channels
```
GET /admin/discord_api.php?action=get_channels
```

### Get Message History
```
GET /admin/discord_api.php?action=get_history&limit=50&offset=0
```

### Test Connection
```
POST /admin/discord_api.php
action=test_connection
channel_id=channel_id_here
csrf_token=token
```

---

## Configuration Files

### Environment Variables (admin/.env)
```ini
DISCORD_BOT_TOKEN=bot_token_here
DISCORD_CLIENT_ID=1435336079409545256
DISCORD_ENABLED=true
DISCORD_RATE_LIMIT_ENABLED=true
DISCORD_MAX_INSTANT_PER_HOUR=10
```

### Channel Configuration (data/discord-channels.json)
```json
{
  "version": "1.0.0",
  "channels": [...]
}
```

### Rate Limits (auto-generated)
`admin/discord_rate_limits.json` - Tracks user rate limit usage

### Message History (auto-generated)
`data/discord-history.json` - Stores last 1000 messages sent

---

## Security Considerations

### Access Control
- JWT authentication required for all endpoints
- CSRF token protection on all POST requests
- Role-based permissions enforced
- Channel access validated per user

### Rate Limiting
- 10 instant messages per hour per user
- Prevents spam and abuse
- Configurable limits in `.env`

### Audit Logging
- All messages logged with user, timestamp, channels
- Admin can review in audit log
- Failed attempts also logged

### Data Privacy
- Bot token never exposed in UI or logs
- Channel IDs validated before use
- Message content stored temporarily (1000 messages max)

---

## Support & Documentation

### Documentation
- Full Feature Spec: `docs/FEATURE_REQUEST_DISCORD_BOT.md`
- Bot Setup Guide: `docs/discord-announcements/BOT-SETUP.md`
- This README: `docs/discord-announcements/PHASE1-README.md`

### GitHub
- Issue #59: https://github.com/k33bz/lastwar-server1586/issues/59
- Milestone: v4.0.0 - Advanced Features

### Discord Resources
- Discord Developer Portal: https://discord.com/developers/applications
- Discord API Docs: https://discord.com/developers/docs/intro
- Discord Status: https://discordstatus.com

---

## Version History

### v1.0.0 (2025-11-04) - Phase 1 Release
- Initial implementation
- Instant messaging functionality
- Basic UI for sending announcements
- Admin configuration page
- Channel management
- Rate limiting
- Audit logging
- Message history

---

## Next Steps

After completing Phase 1 setup:

1. **Configure all alliance channels** in `discord-channels.json`
2. **Train R5/R4 users** on how to send announcements
3. **Monitor usage** in message history and audit logs
4. **Provide feedback** for Phase 2 features
5. **Test edge cases** and report any issues

---

**Questions or Issues?**

Create a GitHub issue with label `discord` or contact the admin team.

🎉 **Congratulations!** You're ready to use Discord announcements!
