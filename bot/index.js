/**
 * Last War Server 1586 - Discord Vote Bot
 * Version: 1.0.0
 *
 * Manages council voting through Discord with cryptographic integrity
 */

require('dotenv').config();
const fs = require('fs');
const path = require('path');
const { Client, GatewayIntentBits, Collection } = require('discord.js');

// Create Discord client
const client = new Client({
  intents: [
    GatewayIntentBits.Guilds,
    GatewayIntentBits.GuildMessages,
    GatewayIntentBits.DirectMessages,
    GatewayIntentBits.MessageContent
  ],
  partials: ['CHANNEL'] // Required for DMs
});

// Store commands
client.commands = new Collection();

// Load commands
const commandsPath = path.join(__dirname, 'commands');
const commandFiles = fs.readdirSync(commandsPath).filter(file => file.endsWith('.js'));

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

// Load events
const eventsPath = path.join(__dirname, 'events');
const eventFiles = fs.readdirSync(eventsPath).filter(file => file.endsWith('.js'));

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

// Start vote monitoring cron job
require('./jobs/voteMonitor')(client);

// Start request monitoring cron job (auto-approval after 12h)
require('./jobs/requestMonitor')(client);

// Login to Discord
client.login(process.env.DISCORD_BOT_TOKEN)
  .then(() => {
    console.log('[READY] Bot is starting up...');
  })
  .catch(error => {
    console.error('[ERROR] Failed to login:', error);
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
