/**
 * Vote Monitor Job
 * Periodically checks for:
 * 1. Web-created votes that haven't been published to Discord yet
 * 2. Votes that need to be finalized
 */

const cron = require('node-cron');
const { getActiveVotes } = require('../utils/dataAccess');
const { finalizeVote, publishVote } = require('../utils/voteManager');

module.exports = function(client) {
  // Run every minute
  cron.schedule('* * * * *', async () => {
    try {
      const activeVotes = await getActiveVotes();

      if (activeVotes.length === 0) {
        return; // No active votes
      }

      const now = new Date();

      for (const vote of activeVotes) {
        // Check if vote was created via web but not yet published to Discord
        if (!vote.discord_metadata.vote_message_id && vote.created_by.source === 'web') {
          console.log(`[MONITOR] Unpublished web-created vote detected: ${vote.vote_id}, publishing to Discord...`);
          try {
            await publishVote(vote, client);
            console.log(`[MONITOR] Successfully published web-created vote ${vote.vote_id}`);
          } catch (error) {
            console.error(`[ERROR] Failed to publish web-created vote ${vote.vote_id}:`, error);
          }
          continue; // Skip deadline check for this iteration
        }

        // Check if vote was created via web approval but not yet published
        if (!vote.discord_metadata.vote_message_id && vote.created_by.source === 'web_approval') {
          console.log(`[MONITOR] Unpublished web-approved vote detected: ${vote.vote_id}, publishing to Discord...`);
          try {
            await publishVote(vote, client);
            console.log(`[MONITOR] Successfully published web-approved vote ${vote.vote_id}`);
          } catch (error) {
            console.error(`[ERROR] Failed to publish web-approved vote ${vote.vote_id}:`, error);
          }
          continue; // Skip deadline check for this iteration
        }

        // Check if voting period has ended
        const endTime = new Date(vote.voting_period.end_time);
        if (now >= endTime) {
          console.log(`[MONITOR] Vote ${vote.vote_id} has reached deadline, finalizing...`);
          await finalizeVote(vote.vote_id, 'time_expired', client);
        }
      }
    } catch (error) {
      console.error('[ERROR] Vote monitor error:', error);
    }
  });

  console.log('[MONITOR] Vote monitoring cron job started (runs every minute)');
};
