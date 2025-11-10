/**
 * Last War Server 1586 - Discord Vote Bot
 * Version: 1.0.0
 *
 * Manages council voting through Discord with cryptographic integrity
 */

require('dotenv').config();
const fs = require('fs');
const path = require('path');
const { Client, GatewayIntentBits, Partials, Collection } = require('discord.js');

// Create Discord client
const client = new Client({
  intents: [
    GatewayIntentBits.Guilds,
    GatewayIntentBits.GuildMessages,
    GatewayIntentBits.DirectMessages,
    GatewayIntentBits.MessageContent
  ],
  partials: [Partials.Channel] // Required for DMs
});

// Store commands
client.commands = new Collection();

// Load commands
const commandsPath = path.join(__dirname, 'commands');
console.log(`[INFO] Loading commands from: ${commandsPath}`);

try {
  const commandFiles = fs.readdirSync(commandsPath).filter(file => file.endsWith('.js'));
  console.log(`[INFO] Found ${commandFiles.length} command file(s)`);

  for (const file of commandFiles) {
    const filePath = path.join(commandsPath, file);
    const command = require(filePath);

    if ('data' in command && 'execute' in command) {
      client.commands.set(command.data.name, command);
      console.log(`[LOAD] Command loaded: ${command.data.name}`);
    } else {
      console.warn(`[WARN] Command at ${filePath} is missing required "data" or "execute" property`);
    }
  }
} catch (error) {
  console.error('[ERROR] Failed to load commands:', error);
  process.exit(1);
}

// Load events
const eventsPath = path.join(__dirname, 'events');
console.log(`[INFO] Loading events from: ${eventsPath}`);

try {
  const eventFiles = fs.readdirSync(eventsPath).filter(file => file.endsWith('.js'));
  console.log(`[INFO] Found ${eventFiles.length} event file(s)`);

  for (const file of eventFiles) {
    const filePath = path.join(eventsPath, file);
    const event = require(filePath);

    if (event.once) {
      client.once(event.name, (...args) => event.execute(...args, client));
    } else {
      client.on(event.name, (...args) => event.execute(...args, client));
    }
    console.log(`[LOAD] Event loaded: ${event.name}`);
  }
} catch (error) {
  console.error('[ERROR] Failed to load events:', error);
  process.exit(1);
}

// Start vote monitoring cron job
try {
  console.log('[INFO] Starting vote monitoring cron job...');
  require('./jobs/voteMonitor')(client);
  console.log('[LOAD] Vote monitor loaded');
} catch (error) {
  console.error('[ERROR] Failed to load vote monitor:', error);
  process.exit(1);
}

// Start request monitoring cron job (auto-approval after 12h)
try {
  console.log('[INFO] Starting request monitoring cron job...');
  require('./jobs/requestMonitor')(client);
  console.log('[LOAD] Request monitor loaded');
} catch (error) {
  console.error('[ERROR] Failed to load request monitor:', error);
  process.exit(1);
}

// Validate environment variables
console.log('[INFO] Validating environment variables...');
const requiredEnvVars = ['DISCORD_BOT_TOKEN', 'DISCORD_CLIENT_ID', 'DISCORD_GUILD_ID', 'VOTE_CHANNEL_ID'];
const missingVars = requiredEnvVars.filter(varName => !process.env[varName]);

if (missingVars.length > 0) {
  console.error('[ERROR] Missing required environment variables:', missingVars.join(', '));
  console.error('[ERROR] Please create a .env file based on .env.example');
  process.exit(1);
}

console.log('[INFO] All required environment variables present');
console.log('[INFO] Bot Token:', process.env.DISCORD_BOT_TOKEN ? '✓ Set' : '✗ Missing');
console.log('[INFO] Client ID:', process.env.DISCORD_CLIENT_ID ? '✓ Set' : '✗ Missing');
console.log('[INFO] Guild ID:', process.env.DISCORD_GUILD_ID ? '✓ Set' : '✗ Missing');
console.log('[INFO] Vote Channel ID:', process.env.VOTE_CHANNEL_ID ? '✓ Set' : '✗ Missing');
console.log('[INFO] Data Directory:', process.env.DATA_DIR || '../data');

// Login to Discord
console.log('[INFO] Logging in to Discord...');
client.login(process.env.DISCORD_BOT_TOKEN)
  .then(() => {
    console.log('[READY] Bot login initiated successfully');
  })
  .catch(error => {
    console.error('[ERROR] Failed to login to Discord:', error);
    console.error('[ERROR] Please check your DISCORD_BOT_TOKEN in .env file');
    process.exit(1);
  });

// Graceful shutdown
process.on('SIGINT', () => {
  console.log('[SHUTDOWN] Received SIGINT, shutting down gracefully...');
  client.destroy();
  process.exit(0);
});

process.on('SIGTERM', () => {
  console.log('[SHUTDOWN] Received SIGTERM, shutting down gracefully...');
  client.destroy();
  process.exit(0);
});

// Error handling
process.on('unhandledRejection', error => {
  console.error('[ERROR] Unhandled promise rejection:', error);
});

module.exports = client;
