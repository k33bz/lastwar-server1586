# Last War 1586 - Discord Vote Bot

Discord bot for managing council voting with cryptographic integrity.

## Features

- 🗳️ Council vote creation via `/vote create` command
- 📝 Vote request system via `/vote request` (anyone can request, president approves)
- ⏱️ Auto-approval after 12 hours if president doesn't respond
- 💬 DM-based voting system (private votes)
- 🔒 Cryptographic hash chain for vote immutability
- ⏱️ 24-hour voting periods with early close option
- 🔄 Automatic council member rotation integration
- 📊 Real-time vote status tracking
- ✅ Automatic vote finalization and result posting
- 🔐 Vote integrity verification
- 🌐 Website integration via webhooks

## Prerequisites

- Node.js 18+ (available in your cPanel Node.js section)
- Discord Bot Token
- Discord Application Client ID
- Discord Server (Guild) ID
- Vote channel ID

## Setup Instructions

### 1. Create Discord Bot

1. Go to https://discord.com/developers/applications
2. Click **"New Application"** and name it "Last War Vote Bot"
3. Go to **"Bot"** section:
   - Click **"Add Bot"**
   - Enable **"Message Content Intent"** (required for DM voting)
   - Copy the **Bot Token** (save for later)
4. Go to **"OAuth2" > "General"**:
   - Copy the **Application ID** (this is your CLIENT_ID)
5. Go to **"OAuth2" > "URL Generator"**:
   - Scopes: `bot`, `applications.commands`
   - Bot Permissions:
     - Send Messages
     - Embed Links
     - Read Message History
     - Use Slash Commands
   - Copy the generated URL and open it to invite the bot to your server

### 2. Get Discord IDs

**Server ID (Guild ID):**
1. Enable Developer Mode in Discord: Settings > Advanced > Developer Mode
2. Right-click your server name > Copy Server ID

**Channel ID (for vote posts):**
1. Right-click the channel where votes should be posted > Copy Channel ID

### 3. Configure Environment Variables

1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` and fill in your values:
   ```env
   DISCORD_BOT_TOKEN=your_bot_token_here
   DISCORD_CLIENT_ID=your_application_id_here
   DISCORD_GUILD_ID=your_server_id_here
   VOTE_CHANNEL_ID=channel_id_where_votes_are_posted
   WEBSITE_URL=https://yoursite.com
   WEBHOOK_SECRET=generate_random_secret_here
   ```

   **Note:** The president's Discord ID is automatically determined from your `alliances.json` file (R5 of the #1 ranked alliance). No manual configuration needed!

### 4. Install Dependencies

```bash
cd bot
npm install
```

### 5. Deploy Slash Commands

```bash
node deploy-commands.js
```

You should see: `Successfully deployed X guild commands`

### 6. Start the Bot

**For cPanel Node.js:**
1. Go to cPanel > **"Setup Node.js App"**
2. Click **"Create Application"**
3. Configure:
   - **Node.js version:** 18.x or 20.x
   - **Application mode:** Production
   - **Application root:** `bot`
   - **Application startup file:** `index.js`
4. Click **"Create"**
5. Run the command shown (to install dependencies)
6. Click **"Start App"**

**For local testing:**
```bash
npm start
```

You should see:
```
[READY] Bot logged in as Last War Vote Bot#1234
[READY] Bot is now online and ready to handle votes!
```

### 7. Test the Bot

In Discord:
1. Type `/vote` - you should see the slash command autocomplete
2. Try `/vote status` - should show no active votes
3. Try `/vote create` - should DM you to create a vote

## Usage

### Creating a Vote (President/Admin Only)

1. User with permissions runs `/vote create`
2. Bot DMs them with 3 questions:
   - Vote title
   - Description/synopsis
   - Category (rule change, alliance action, server event, other)
3. Bot creates vote and notifies all council members
4. Vote is posted in the configured channel

### Requesting a Vote (Anyone Can Request)

1. Any user runs `/vote request`
2. Bot DMs them with 3 questions (same as vote creation)
3. Request is sent to the president for approval
4. President receives DM with request details
5. President can:
   - **Approve:** Reply with `approve: request_id`
   - **Reject:** Reply with `reject: request_id optional reason`
6. **Auto-approval:** If president doesn't respond within 12 hours, vote is automatically created
7. Requester is notified of approval/rejection/auto-approval

### Viewing Pending Requests (President Only)

```
/vote requests
```

Shows all pending vote requests with:
- Request ID
- Title
- Requester
- Age
- Time until auto-approval

### Voting

Council members receive a DM with vote details. They reply:
```
vote: yes
vote: no
vote: abstain
```

Votes are cryptographically sealed and cannot be changed.

### Vote Finalization

Votes finalize either:
- When all 7 council members vote (early close)
- After 24 hours (time expired)

Results are automatically posted to Discord and sent to the website.

### Checking Vote Status

```
/vote status             # Show all active votes
/vote status vote_id     # Show specific vote
```

### Verifying Vote Integrity

```
/vote verify vote_id
```

Shows cryptographic verification that the vote hasn't been tampered with.

## File Structure

```
bot/
├── commands/          # Slash command handlers
│   └── vote.js       # /vote command
├── events/           # Discord event handlers
│   ├── ready.js      # Bot ready event
│   ├── interactionCreate.js  # Slash command handler
│   └── messageCreate.js      # DM voting handler
├── jobs/             # Scheduled tasks
│   └── voteMonitor.js  # Vote finalization cron
├── utils/            # Utility modules
│   ├── dataAccess.js      # File system data access
│   ├── councilUtils.js    # Council rotation logic
│   ├── voteIntegrity.js   # Cryptographic integrity
│   ├── voteManager.js     # Vote creation/finalization
│   └── webhookClient.js   # Website integration
├── index.js          # Main bot file
├── deploy-commands.js  # Command registration script
├── package.json      # Dependencies
├── .env             # Configuration (not in git)
└── README.md        # This file
```

## Data Files

The bot reads/writes these data files (shared with website):

- `../data/council.json` - President designation & roles (read)
- `../data/rotation-schedule.json` - Current council members (read)
- `../data/alliances.json` - Alliance, R5, & R4 info (read)
- `../data/discord-votes.json` - Vote records (read/write)
- `../data/discord-vote-requests.json` - Vote requests (read/write)

## President Identification

The bot reads the president designation from `council.json` (not auto-derived from rankings).

**Requirements:**
- President alliance must be set in `../data/council.json`
- President's R5 must have `discordId` field in `alliances.json`

**Example council.json:**
```json
{
  "president": {
    "alliance_tag": "UvvU",
    "appointed_date": "2025-05-19T00:00:00Z"
  }
}
```

**Example alliances.json (R5 with Discord ID):**
```json
{
  "tag": "UvvU",
  "r5": {
    "name": "쿠치나 ᓚᘏᗢ",
    "discordId": "123456789012345678"
  }
}
```

**To get Discord User ID:**
1. Enable Developer Mode in Discord (Settings > Advanced > Developer Mode)
2. Right-click your name anywhere in Discord
3. Click "Copy User ID"
4. Paste into `alliances.json`

## R4 Vote Delegation

R5s can delegate voting power to R4 officers.

**Setup:**
1. Add R4s to alliance in `alliances.json`:
   ```json
   {
     "tag": "UvvU",
     "r5": { "name": "R5Name", "discordId": "..." },
     "r4s": [
       {
         "name": "R4Name",
         "discordId": "987654321098765432",
         "canVote": true,
         "role": "Deputy"
       }
     ]
   }
   ```

2. Bot will automatically recognize R4s with `canVote: true`

**Voting Rules:**
- R5 always has priority to vote
- R4s with `canVote: true` can vote when R5 is absent
- Multiple R4s can have delegation, but only ONE vote per alliance
- R4 cannot override R5's vote

## Troubleshooting

### Bot won't start
- Check `.env` has correct token
- Verify Node.js version is 18+
- Check file permissions on data directory

### President can't approve/reject requests
- Check `council.json` has correct president alliance tag
- Ensure president's alliance R5 has `discordId` in `alliances.json`
- Verify Discord ID matches exactly (enable Developer Mode to copy User ID)

### R4 can't vote despite delegation
- Verify R4 has `canVote: true` in `alliances.json`
- Ensure R4's `discordId` is set correctly
- Check R4 is in an alliance currently on the council

### Slash commands don't appear
- Wait 5 minutes (guild commands take time)
- Re-run `node deploy-commands.js`
- Check bot has `applications.commands` scope

### DMs don't work
- Verify **Message Content Intent** is enabled
- Check user has DMs enabled for the server
- Bot needs `partials: ['CHANNEL']` in client config

### Votes not recording
- Check Discord IDs are set in `alliances.json`
- Verify R5 Discord IDs match actual user IDs
- Check bot logs for errors

## Security

- **Hash Chain:** Each vote event is cryptographically linked to prevent tampering
- **Webhook Signatures:** Website integration uses HMAC authentication
- **Immutable Votes:** Once submitted, votes cannot be changed
- **Audit Trail:** All events are timestamped and logged

## Support

For issues or questions:
- GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
- Check bot logs for error messages
- Verify all Discord IDs are correct in alliances.json
