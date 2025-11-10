# Discord Bot - cPanel Setup Guide

## Common Issues and Solutions

### Issue 1: Bot Won't Start - "Partials" Error
**Error:** `Cannot read property 'CHANNEL' of undefined` or similar Partials error

**Solution:** Fixed in latest version. The bot now correctly imports `Partials` from discord.js v14.

### Issue 2: Missing Environment Variables
**Error:** Bot exits immediately with "Missing required environment variables"

**Solution:**
1. Copy `.env.example` to `.env` in the bot directory
2. Fill in all required values:
   ```env
   DISCORD_BOT_TOKEN=your_bot_token_from_discord_developer_portal
   DISCORD_CLIENT_ID=your_application_id
   DISCORD_GUILD_ID=your_server_id
   VOTE_CHANNEL_ID=channel_where_vote_results_post
   WEBSITE_URL=https://www.lastwar1586.online
   WEBHOOK_SECRET=random_secret_string_for_security
   DATA_DIR=../data
   ```

3. In cPanel Node.js App settings, add these as **Environment Variables**:
   - Click "Edit" on your Node.js app
   - Scroll to "Environment variables"
   - Add each variable from your .env file

### Issue 3: File Permission Errors
**Error:** `EACCES: permission denied` or `ENOENT: no such file or directory`

**Solution:**
1. Ensure the bot directory has proper permissions:
   ```bash
   chmod 755 bot/
   chmod 644 bot/*.js
   chmod 755 bot/commands/ bot/events/ bot/jobs/ bot/utils/
   ```

2. Ensure data directory is accessible:
   ```bash
   chmod 755 data/
   chmod 644 data/*.json
   ```

### Issue 4: Module Not Found Errors
**Error:** `Cannot find module 'discord.js'` or similar

**Solution:**
1. SSH into your server or use cPanel Terminal
2. Navigate to bot directory:
   ```bash
   cd ~/public_html/bot  # Adjust path as needed
   ```
3. Install dependencies:
   ```bash
   npm install
   ```
4. Restart the app in cPanel Node.js interface

### Issue 5: Bot Token Invalid
**Error:** `Failed to login: Invalid token`

**Solution:**
1. Go to https://discord.com/developers/applications
2. Select your application
3. Go to "Bot" section
4. Click "Reset Token" and copy the new token
5. Update `DISCORD_BOT_TOKEN` in your .env file or cPanel environment variables
6. Restart the bot

### Issue 6: Message Content Intent Not Enabled
**Error:** Bot receives DMs but can't read them

**Solution:**
1. Go to https://discord.com/developers/applications
2. Select your application
3. Go to "Bot" section
4. Scroll to "Privileged Gateway Intents"
5. Enable **"Message Content Intent"**
6. Save changes
7. Restart the bot

## cPanel Node.js App Configuration

### Recommended Settings:
- **Node.js version:** 18.x or higher
- **Application mode:** Production
- **Application root:** `bot` (relative to your public_html)
- **Application startup file:** `index.js`
- **Environment variables:** Add all from .env file

### Starting the Bot:
1. In cPanel, go to "Setup Node.js App"
2. Click on your bot application
3. Click "Stop App" then "Start App" to restart
4. Check logs by clicking "Run NPM Install" which shows recent output

### Viewing Logs:
The bot outputs detailed logs to help troubleshoot:
- `[INFO]` - Informational messages
- `[LOAD]` - Successfully loaded component
- `[WARN]` - Warnings (non-critical)
- `[ERROR]` - Errors (critical)
- `[READY]` - Bot is online and ready

### Logs Location:
- cPanel shows recent logs in the Node.js App interface
- For full logs, check: `~/nodevenv/bot/*/bin/stderr.log` and `stdout.log`

## Testing the Bot

### 1. Check Bot is Online:
- Look for bot's green status in Discord server
- Bot should show as "Online"

### 2. Test Slash Command:
```
/vote request
Title: Test Vote
Description: Testing bot functionality
Category: general
```

### 3. Check Logs:
Look for these success messages:
```
[INFO] Loading commands from: /path/to/bot/commands
[INFO] Found 1 command file(s)
[LOAD] Command loaded: vote
[INFO] Loading events from: /path/to/bot/events
[INFO] Found 3 event file(s)
[LOAD] Event loaded: ready
[LOAD] Event loaded: interactionCreate
[LOAD] Event loaded: messageCreate
[LOAD] Vote monitor loaded
[LOAD] Request monitor loaded
[INFO] All required environment variables present
[INFO] Logging in to Discord...
[READY] Bot login initiated successfully
[READY] Vote bot logged in as BotName#1234
[READY] Slash commands registered
```

## Deployment Checklist

- [ ] `.env` file created with all required variables
- [ ] Dependencies installed (`npm install`)
- [ ] Slash commands deployed (`node deploy-commands.js`)
- [ ] Environment variables added in cPanel
- [ ] Bot invited to Discord server with correct permissions
- [ ] Message Content Intent enabled in Discord Developer Portal
- [ ] Data directory accessible (../data from bot folder)
- [ ] council.json exists in data directory
- [ ] alliances.json exists in data directory
- [ ] Node.js app configured in cPanel
- [ ] Bot started and showing as online in Discord

## Getting Help

If you're still having issues:

1. Check bot logs in cPanel Node.js interface
2. Verify all environment variables are set correctly
3. Ensure Discord bot token is valid
4. Confirm Message Content Intent is enabled
5. Check file permissions on bot and data directories
6. Try restarting the bot application

The bot now includes detailed startup logging to help identify issues quickly.
