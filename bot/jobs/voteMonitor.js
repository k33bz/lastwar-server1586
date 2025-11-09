/**
 * Vote Monitor Job
 * Periodically checks for votes that need to be finalized
 */

const cron = require('node-cron');
const { getActiveVotes } = require('../utils/dataAccess');
const { finalizeVote } = require('../utils/voteManager');

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
        const endTime = new Date(vote.voting_period.end_time);

        // Check if voting period has ended
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
