# Discord Bot Troubleshooting Guide

## Issue: "Invalid bot token or connection error"

### Symptoms
- Test script shows: ❌ Bot token is INVALID
- Error: "Invalid bot token or connection error"
- Bot connection test fails

### Common Causes

#### 1. Token Needs to be Regenerated
Discord bot tokens expire or get invalidated when:
- You clicked "Regenerate" in Developer Portal
- Token was exposed publicly (Discord auto-revokes)
- Bot application was deleted and recreated

**Solution:**
1. Go to Discord Developer Portal: https://discord.com/developers/applications/1435336079409545256
2. Click **Bot** in left sidebar
3. Click **Reset Token** button
4. **Copy the new token immediately** (only shown once!)
5. Update `admin/.env`:
   ```ini
   DISCORD_BOT_TOKEN=paste_new_token_here
   ```
6. Save and retest: `php admin/test_discord_bot.php`

#### 2. Token Has Extra Spaces
Sometimes copying/pasting adds spaces.

**Solution:**
Check `admin/.env` line 25:
```ini
# Bad (has spaces)
DISCORD_BOT_TOKEN= your_bot_token_here

# Good (no spaces)
DISCORD_BOT_TOKEN=your_bot_token_here
```

#### 3. Bot Application Doesn't Exist
If you see "Unknown Application" in Developer Portal, the bot was deleted.

**Solution:**
You'll need to recreate the bot:
1. Create new application
2. Go to Bot section → Add Bot
3. Copy new Application ID and Bot Token
4. Update both in `admin/.env`
5. Re-invite bot to server with new OAuth2 URL

#### 4. Connection/Network Issue
Firewall or network blocking Discord API.

**Solution:**
Test connectivity:
```bash
curl https://discord.com/api/v10/users/@me -H "Authorization: Bot YOUR_TOKEN_HERE"
```

If this fails, check:
- Firewall settings
- Proxy configuration
- Internet connection
- DNS resolution

---

## Issue: "Bot lacks permission to send messages"

### Symptoms
- Error: "Bot lacks permission to send messages in this channel"
- HTTP 403 Forbidden error

### Solutions

#### 1. Check Channel Permissions
1. Go to Discord channel settings
2. Go to **Permissions** tab
3. Find your bot role
4. Ensure these are enabled:
   - ✅ View Channel
   - ✅ Send Messages
   - ✅ Embed Links

#### 2. Check Role Hierarchy
1. Go to Server Settings → Roles
2. Ensure bot role is above @everyone
3. Bot cannot send to channels where it's restricted

#### 3. Check Server Permissions
1. Server Settings → Roles → [Bot Role]
2. Ensure these are enabled:
   - ✅ Send Messages
   - ✅ Embed Links
   - ✅ Use External Emojis (optional)

---

## Issue: "Channel not found"

### Symptoms
- Error: "Channel not found or bot is not in the server"
- HTTP 404 error

### Solutions

#### 1. Verify Channel ID
Channel ID must be 18-20 digits.

**Get correct channel ID:**
1. Enable Developer Mode in Discord (Settings → Advanced)
2. Right-click on channel name
3. Click "Copy ID"
4. Paste in alliance edit form

#### 2. Verify Bot is in Server
1. Check Discord server member list
2. Search for "Server 1586 Announcer"
3. If not found, re-invite bot using OAuth2 URL

#### 3. Verify Channel Exists
- Channel may have been deleted
- Channel may be in different server
- Double-check you copied the right ID

---

## Issue: "Rate limit exceeded"

### Symptoms
- Error: "Rate limit exceeded after X retries"
- HTTP 429 error
- Messages not sending

### Solutions

#### 1. Wait for Rate Limit Reset
Rate limits reset after 1 hour.

Current limits:
- 10 instant messages per hour per user
- Admin has no limits

#### 2. Check Rate Limit Status
View `admin/discord_rate_limits.json` to see current usage.

#### 3. Adjust Rate Limits
Edit `admin/.env`:
```ini
DISCORD_MAX_INSTANT_PER_HOUR=20  # Increase limit
```

---

## Issue: Messages not appearing in Discord

### Symptoms
- Success message shows in admin panel
- Message ID returned
- But message doesn't appear in Discord

### Solutions

#### 1. Check You're Looking at Right Channel
- Verify channel ID matches
- Check you're in correct server
- Scroll to bottom of channel

#### 2. Check Message Wasn't Deleted
- Bot might have sent it
- Someone with permissions deleted it immediately
- Check audit log in Discord

#### 3. Check Discord Status
Discord might be having issues:
- Check: https://discordstatus.com
- Wait and retry

---

## Issue: Can't access Discord Announcements page

### Symptoms
- "Access denied" error
- Redirected to dashboard
- Page won't load

### Solutions

#### 1. Verify User Role
You need at least R4 role to access.

Check your user in `admin/users.json`:
```json
{
  "email": "your@email.com",
  "alliances": ["UvvU"],
  "roles": ["r5"]  // Must have r5, r4, or admin
}
```

#### 2. Verify Discord is Enabled
Check `admin/.env`:
```ini
DISCORD_ENABLED=true  # Must be true
```

#### 3. Check Session
- Log out and log back in
- Clear browser cache
- Check JWT token hasn't expired

---

## Issue: Channels not showing in announcements page

### Symptoms
- "No channels configured" message
- Channels configured but not showing
- Empty channel list

### Solutions

#### 1. Verify Channels Are Enabled
In Alliance Edit page, check:
- ✅ "Enabled" checkbox is checked
- Channel ID is valid (18-20 digits)
- Channel name is filled in

#### 2. Verify Alliance Assignment
User must have access to alliance:
- Check `admin/users.json`
- Verify "alliances" array includes your alliance tag
- Admin (alliance: "*") sees all channels

#### 3. Check Data Files
**Alliance channels** in `data/alliances.json`:
```json
{
  "tag": "UvvU",
  "discord": {
    "channels": [
      {
        "id": "1234567890123456789",
        "name": "announcements",
        "enabled": true  // Must be true
      }
    ]
  }
}
```

**Global channels** in `data/discord-channels.json`:
```json
{
  "global_channels": [
    {
      "id": "9876543210987654321",
      "enabled": true  // Must be true
    }
  ]
}
```

---

## Testing Checklist

Use this checklist to verify your setup:

### Bot Configuration
- [ ] Bot token configured in `admin/.env`
- [ ] `DISCORD_ENABLED=true` in `.env`
- [ ] Bot exists in Discord Developer Portal
- [ ] Run `php admin/test_discord_bot.php` - passes all checks

### Bot Permissions
- [ ] Bot invited to Discord server
- [ ] Bot appears in member list
- [ ] Bot has "Send Messages" permission
- [ ] Bot has "Embed Links" permission
- [ ] Bot role is above @everyone

### Channel Configuration
- [ ] At least one channel configured
- [ ] Channel ID is 18-20 digits
- [ ] Channel is marked as "enabled"
- [ ] Channel exists in Discord
- [ ] Bot can see the channel

### User Access
- [ ] User has r5, r4, or admin role
- [ ] User assigned to correct alliance
- [ ] User can access `admin/discord_announcements.php`
- [ ] User sees configured channels in list

### Message Sending
- [ ] Can select channels
- [ ] Can compose message
- [ ] Click "Send Announcement" works
- [ ] Message appears in Discord
- [ ] Message recorded in history

---

## Getting Help

If you're still having issues:

1. **Check logs:**
   - PHP error log
   - Browser console (F12)
   - `admin/audit_log.json`

2. **Verify environment:**
   ```bash
   php -v  # PHP version
   cd admin && composer install  # Dependencies
   cat admin/.env | grep DISCORD  # Configuration
   ```

3. **Test API directly:**
   ```bash
   # Test bot token (replace YOUR_TOKEN)
   curl https://discord.com/api/v10/users/@me \
     -H "Authorization: Bot YOUR_TOKEN"
   ```

4. **Create GitHub Issue:**
   - https://github.com/k33bz/lastwar-server1586/issues
   - Label: `discord`
   - Include error messages and logs

5. **Check Discord API Status:**
   - https://discordstatus.com

---

## Common Error Codes

| Code | Meaning | Solution |
|------|---------|----------|
| 400 | Bad Request | Invalid message format or data |
| 401 | Unauthorized | Invalid bot token |
| 403 | Forbidden | Bot lacks permissions |
| 404 | Not Found | Channel/server doesn't exist |
| 429 | Rate Limited | Wait and retry |
| 500 | Server Error | Discord API issue, try again later |

---

## Quick Fixes

### Reset Everything
```bash
# 1. Regenerate bot token in Discord Portal
# 2. Update admin/.env with new token
# 3. Clear rate limits
rm admin/discord_rate_limits.json
# 4. Test connection
php admin/test_discord_bot.php
```

### Verify Configuration
```bash
# Check .env file
cat admin/.env | grep -i discord

# Check alliances have channels
cat data/alliances.json | grep -A5 '"channels"'

# Check global channels
cat data/discord-channels.json
```

### Test Manually
```php
<?php
// test_manual.php
require_once 'admin/config.php';
require_once 'admin/discord_webhook.php';

$channel_id = 'YOUR_CHANNEL_ID_HERE';
$message = create_simple_announcement('Test message');
$result = send_discord_message($channel_id, $message);

var_dump($result);
?>
```

---

**Still stuck?** Create an issue on GitHub with:
- Error message
- What you tried
- PHP version
- Environment (local/server)
