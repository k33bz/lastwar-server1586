/**
 * Deploy Commands Script
 * Registers slash commands with Discord API
 *
 * Run this once after setting up the bot:
 * node deploy-commands.js
 */

require('dotenv').config();
const fs = require('fs');
const path = require('path');
const { REST, Routes } = require('discord.js');

const commands = [];

// Load all command files
const commandsPath = path.join(__dirname, 'commands');
const commandFiles = fs.readdirSync(commandsPath).filter(file => file.endsWith('.js'));

for (const file of commandFiles) {
  const filePath = path.join(commandsPath, file);
  const command = require(filePath);

  if ('data' in command && 'execute' in command) {
    commands.push(command.data.toJSON());
    console.log(`[LOAD] Loaded command: ${command.data.name}`);
  } else {
    console.warn(`[WARN] Command at ${filePath} is missing required "data" or "execute" property`);
  }
}

// Construct REST client
const rest = new REST().setToken(process.env.DISCORD_BOT_TOKEN);

// Deploy commands
(async () => {
  try {
    console.log(`[DEPLOY] Started refreshing ${commands.length} application (/) commands.`);

    // Register commands globally or to specific guild
    let data;

    if (process.env.DISCORD_GUILD_ID) {
      // Guild-specific (instant, good for testing)
      data = await rest.put(
        Routes.applicationGuildCommands(process.env.DISCORD_CLIENT_ID, process.env.DISCORD_GUILD_ID),
        { body: commands }
      );
      console.log(`[DEPLOY] Successfully deployed ${data.length} guild commands to server ${process.env.DISCORD_GUILD_ID}`);
    } else {
      // Global (takes up to 1 hour to propagate)
      data = await rest.put(
        Routes.applicationCommands(process.env.DISCORD_CLIENT_ID),
        { body: commands }
      );
      console.log(`[DEPLOY] Successfully deployed ${data.length} global commands (may take up to 1 hour to appear)`);
    }

    console.log('[DEPLOY] Command deployment complete!');
  } catch (error) {
    console.error('[ERROR] Failed to deploy commands:', error);
  }
})();
