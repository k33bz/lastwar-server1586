/**
 * Ready Event
 * Fires when bot successfully connects to Discord
 */

module.exports = {
  name: 'ready',
  once: true,
  execute(client) {
    console.log(`[READY] Bot logged in as ${client.user.tag}`);
    console.log(`[READY] Serving ${client.guilds.cache.size} server(s)`);
    console.log(`[READY] Monitoring ${client.channels.cache.size} channel(s)`);

    // Set bot status
    client.user.setPresence({
      activities: [{
        name: 'council votes | /vote',
        type: 3 // Watching
      }],
      status: 'online'
    });

    console.log('[READY] Bot is now online and ready to handle votes!');
  }
};
