# Discord Bot Setup Guide

**Bot Name:** Server 1586 Announcer
**Application ID:** 1435336079409545256
**Status:** Configuration Phase
**Created:** 2025-11-04

---

## Bot Information

### Basic Details
- **Name:** Server 1586 Announcer
- **Description:** Official announcement bot for Last War Server 1586. Enables alliance leaders (R5) and officers (R4) to send instant, scheduled, and recurring announcements to Discord channels.
- **Tags:** announcements, scheduling, alliance, reminders, management

### Credentials (KEEP SECURE)
- **Application ID:** 1435336079409545256
- **Public Key:** c4293986c8cc2fb7a4fdd879f1d87ff4734e75d626ae4edddf5d932d4bfa0ca2
- **Bot Token:** (Generate in Bot section - see step 3 below)
- **Client Secret:** (Generate in OAuth2 section if needed)

---

## Setup Steps

### Step 1: Basic Application Configuration (COMPLETED)

✅ Application created on Discord Developer Portal
✅ Name set to "Server 1586 Announcer"
✅ Description added
✅ Tags configured
✅ Application ID and Public Key saved

**Portal URL:** https://discord.com/developers/applications/1435336079409545256

---

### Step 2: Bot Settings Configuration

1. Navigate to **Bot** section in left sidebar
2. Click **Add Bot** (if button is available)
3. Configure the following settings:

#### Authorization Flow
- ✅ **Public Bot** - ON (allow others to invite)
- ⬜ **Requires OAuth2 Code Grant** - OFF (not needed)

#### Privileged Gateway Intents
- ⬜ **Presence Intent** - OFF (not needed for announcements)
- ⬜ **Server Members Intent** - OFF (not needed for announcements)
- ✅ **Message Content Intent** - ON (needed if implementing bot commands)

**Note:** For Phase 1 (basic announcements), Message Content Intent is optional. Enable it if you plan to add bot commands like `/status` or `/schedule`.

---

### Step 3: Generate Bot Token

1. In the **Bot** section, find the **Token** area
2. Click **Reset Token** button
3. **Copy the token immediately** - it will only be shown once
4. Store the token securely in your password manager or `.env` file

**⚠️ SECURITY WARNING:**
- Never commit the bot token to git
- Never share the token publicly
- If token is compromised, regenerate immediately
- Store in `admin/.env` file (which is in .gitignore)

#### Add to admin/.env
```ini
# Discord Bot Configuration
DISCORD_BOT_TOKEN=YOUR_BOT_TOKEN_HERE
DISCORD_CLIENT_ID=1435336079409545256
DISCORD_PUBLIC_KEY=c4293986c8cc2fb7a4fdd879f1d87ff4734e75d626ae4edddf5d932d4bfa0ca2

# Feature Flags
DISCORD_ENABLED=true
DISCORD_RATE_LIMIT_ENABLED=true
DISCORD_MAX_INSTANT_PER_HOUR=10
DISCORD_MAX_SCHEDULED_PENDING=50
DISCORD_MAX_RECURRING_ACTIVE=5
```

---

### Step 4: Configure Bot Permissions

Required permissions for Server 1586 Announcer:

#### Essential Permissions (Phase 1)
- ✅ **Send Messages** - Core announcement functionality
- ✅ **Embed Links** - For rich formatted announcements
- ✅ **Read Message History** - For context

#### Recommended Permissions (Phase 2+)
- ✅ **Send Messages in Threads** - For thread announcements
- ✅ **Attach Files** - For image attachments (Phase 5)
- ✅ **Add Reactions** - For interactive announcements (optional)
- ✅ **Use External Emojis** - For custom emoji in announcements
- ✅ **Manage Webhooks** - If using webhook fallback method

#### Permission Calculation
**Permissions Integer:** `274877991936`

**Permission Breakdown:**
```
Send Messages:              2048
Embed Links:               16384
Attach Files:              32768
Read Message History:      65536
Use External Emojis:   262144000
Add Reactions:             64
Send Messages in Threads:  274877906944
Manage Webhooks:           536870912
```

---

### Step 5: Generate OAuth2 Invite URL

1. Navigate to **OAuth2 > URL Generator**
2. Select **Scopes:**
   - ✅ `bot`
3. Select **Bot Permissions:**
   - Use the permissions listed in Step 4
4. Copy the generated URL

**Generated URL Format:**
```
https://discord.com/api/oauth2/authorize?client_id=1435336079409545256&permissions=274877991936&scope=bot
```

#### Invite Bot to Your Server

1. Copy the generated OAuth2 URL
2. Paste into browser
3. Select "Last War Server 1586" from dropdown
4. Click "Authorize"
5. Complete CAPTCHA if prompted
6. Bot should now appear in your server's member list (offline until code is deployed)

---

### Step 6: Verify Bot Installation

After inviting the bot to your Discord server:

1. Check server member list - bot should appear as offline
2. Note the bot's user ID (right-click > Copy ID with Developer Mode enabled)
3. Create a test channel for bot testing (e.g., `#bot-testing`)
4. Ensure bot has permission to send messages in test channel

**Server Information:**
- **Server Name:** Last War Server 1586
- **Server Invite:** https://discord.gg/e53v2Dnp
- **Bot User ID:** (Will be assigned after invite)

---

### Step 7: Set Up Channel Configuration

Create initial channel mappings in `data/discord-channels.json`:

```json
{
  "version": "1.0.0",
  "channels": [
    {
      "id": "CHANNEL_ID_HERE",
      "name": "announcements",
      "server_id": "SERVER_ID_HERE",
      "server_name": "Last War Server 1586",
      "alliance": "*",
      "type": "global",
      "enabled": true,
      "description": "Main server announcements"
    },
    {
      "id": "CHANNEL_ID_HERE",
      "name": "nap15-announcements",
      "server_id": "SERVER_ID_HERE",
      "server_name": "Last War Server 1586",
      "alliance": "*",
      "type": "cross-alliance",
      "enabled": true,
      "description": "NAP15 alliance coordination"
    }
  ]
}
```

**To get Channel IDs:**
1. Enable Discord Developer Mode: Settings > Advanced > Developer Mode
2. Right-click on channel name
3. Click "Copy ID"

---

## Bot Architecture

### Connection Method
**Phase 1:** Use Discord Gateway (WebSocket connection) for bot connectivity

**Alternative (Phase 2+):** HTTP-based interactions via Interactions Endpoint URL

### Technology Stack
- **Language:** PHP 7.4+
- **Library:** `team-reflex/discord-php` (DiscordPHP) or `guzzlehttp/guzzle` for webhooks
- **Connection:** Gateway WebSocket (persistent connection)
- **Storage:** JSON files for configuration and history

### Bot Lifecycle
```
1. Bot starts (PHP process or daemon)
   ↓
2. Authenticates with Discord Gateway using bot token
   ↓
3. Establishes WebSocket connection
   ↓
4. Bot appears online in Discord server
   ↓
5. Listens for commands (optional for Phase 1)
   ↓
6. Sends announcements via REST API
   ↓
7. Maintains connection (reconnect on disconnect)
```

---

## Security Checklist

- [ ] Bot token stored in `.env` file (not in code)
- [ ] `.env` file added to `.gitignore`
- [ ] Public Key saved securely
- [ ] Bot permissions limited to minimum required
- [ ] Two-factor authentication enabled on Discord account
- [ ] Bot token never committed to git repository
- [ ] Bot token never shared in Discord, email, or public channels
- [ ] Admin panel access restricted (existing JWT authentication)
- [ ] Rate limiting configured to prevent abuse
- [ ] Audit logging enabled for all bot actions

---

## Environment Configuration

### Production Environment (admin/.env)
```ini
# Discord Bot Configuration
DISCORD_BOT_TOKEN=your_production_bot_token_here
DISCORD_CLIENT_ID=1435336079409545256
DISCORD_PUBLIC_KEY=c4293986c8cc2fb7a4fdd879f1d87ff4734e75d626ae4edddf5d932d4bfa0ca2

# Discord Server Configuration
DISCORD_MAIN_SERVER_ID=your_server_id_here
DISCORD_DEFAULT_CHANNEL_ID=your_default_channel_id_here

# Feature Flags
DISCORD_ENABLED=true
DISCORD_RATE_LIMIT_ENABLED=true

# Rate Limits
DISCORD_MAX_INSTANT_PER_HOUR=10
DISCORD_MAX_SCHEDULED_PENDING=50
DISCORD_MAX_RECURRING_ACTIVE=5
DISCORD_CROSS_ALLIANCE_DAILY_LIMIT=5

# Webhook Fallback (Optional)
DISCORD_WEBHOOK_URL_MAIN=
DISCORD_WEBHOOK_URL_NAP15=

# Bot Behavior
DISCORD_RETRY_ATTEMPTS=3
DISCORD_RETRY_DELAY=60
DISCORD_TIMEOUT=30
```

### Testing Environment (Optional)
Create a separate Discord bot application for testing:
- Test Bot Application ID: (create separate app)
- Test Bot Token: (different from production)
- Test Server: Create a test Discord server for development

---

## Bot Status Monitoring

### Check Bot Status
1. **Discord Developer Portal:**
   - Go to: https://discord.com/developers/applications/1435336079409545256
   - Check "Install Count" metrics

2. **Discord Server:**
   - Bot should show "Online" status when running
   - If offline, check bot process and logs

3. **Admin Panel Dashboard:**
   - Add bot status widget (Phase 1)
   - Display connection status, last heartbeat, message count

### Health Checks
- Bot connection uptime
- Message delivery success rate
- Failed message retry queue length
- Rate limit usage percentage
- Discord API latency

---

## Troubleshooting

### Bot Shows as Offline
**Causes:**
- Bot process not running
- Invalid bot token
- Network connectivity issues
- Discord API outage

**Solutions:**
1. Verify bot token in `.env`
2. Check bot process is running: `ps aux | grep discord`
3. Check Discord API status: https://discordstatus.com
4. Review error logs: `admin/logs/discord-bot.log`

### Messages Not Sending
**Causes:**
- Missing channel permissions
- Rate limit exceeded
- Invalid channel ID
- Bot not in server

**Solutions:**
1. Verify bot has "Send Messages" permission in target channel
2. Check rate limit status in admin panel
3. Verify channel ID is correct
4. Confirm bot is member of target server

### Authentication Errors
**Causes:**
- Expired or invalid bot token
- Incorrect client ID
- Bot deleted from Discord

**Solutions:**
1. Regenerate bot token in Discord Developer Portal
2. Update token in `admin/.env`
3. Restart bot process
4. Verify application still exists in Developer Portal

---

## Next Steps

### Phase 1: Foundation Implementation
1. [ ] Install PHP Discord library: `composer require team-reflex/discord-php`
2. [ ] Create `admin/discord_bot.php` - Bot client wrapper
3. [ ] Create `admin/discord_config.php` - Configuration UI
4. [ ] Test bot connection and authentication
5. [ ] Implement basic instant message sending
6. [ ] Add admin UI for sending test messages
7. [ ] Configure audit logging

### Testing Checklist
- [ ] Bot connects successfully and shows online
- [ ] Can send test message to single channel
- [ ] Message appears correctly in Discord
- [ ] Audit log captures message send event
- [ ] Error handling works (invalid channel ID)
- [ ] Bot reconnects after connection loss

---

## Resources

### Discord Developer Documentation
- **Discord Developer Portal:** https://discord.com/developers/applications
- **Discord API Docs:** https://discord.com/developers/docs/intro
- **Bot API Reference:** https://discord.com/developers/docs/resources/channel#create-message
- **Rate Limits:** https://discord.com/developers/docs/topics/rate-limits
- **Gateway Documentation:** https://discord.com/developers/docs/topics/gateway

### PHP Libraries
- **DiscordPHP:** https://github.com/discord-php/DiscordPHP
- **DiscordPHP Docs:** https://discord-php.github.io/DiscordPHP/
- **Guzzle HTTP Client:** https://docs.guzzlephp.org/

### Project Documentation
- **Feature Request:** `docs/FEATURE_REQUEST_DISCORD_BOT.md`
- **GitHub Issue:** https://github.com/k33bz/lastwar-server1586/issues/59
- **Admin Panel Docs:** `docs/admin/`

---

## Support

### Discord Bot Issues
- **GitHub Issues:** https://github.com/k33bz/lastwar-server1586/issues
- **Label:** `discord`
- **Priority:** High (v4.0.0 feature)

### Discord API Support
- **Discord Developers Server:** https://discord.gg/discord-developers
- **Discord API Status:** https://discordstatus.com

---

**Document Version:** 1.0.0
**Last Updated:** 2025-11-04
**Maintained By:** Server 1586 Development Team
