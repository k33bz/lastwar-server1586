/**
 * Message Create Event
 * Handles DM messages for vote submissions
 */

const { getActiveVotes } = require('../utils/dataAccess');
const { getVoterInfo } = require('../utils/councilUtils');
const { recordVoteSubmission, finalizeVote } = require('../utils/voteManager');

module.exports = {
  name: 'messageCreate',
  async execute(message, client) {
    // Ignore bot messages
    if (message.author.bot) return;

    // Only handle DMs
    if (!message.channel.isDMBased()) return;

    // Check for active votes
    const activeVotes = await getActiveVotes();

    if (activeVotes.length === 0) {
      return; // No active votes, ignore DM
    }

    // Parse vote command
    const content = message.content.toLowerCase().trim();
    const voteMatch = content.match(/^vote:\s*(yes|no|abstain)$/);

    if (!voteMatch) {
      return; // Not a vote command, ignore
    }

    const choice = voteMatch[1];

    // Check each active vote to see if this user is eligible
    for (const vote of activeVotes) {
      const voterInfo = getVoterInfo(message.author.id, vote);

      if (!voterInfo) {
        continue; // Not eligible for this vote
      }

      // Check if already voted
      if (voterInfo.vote_submitted) {
        await message.reply({
          embeds: [{
            title: '❌ Already Voted',
            description: `You have already submitted your vote for **${vote.vote_details.title}**.`,
            fields: [
              {
                name: 'Your Alliance',
                value: voterInfo.alliance_tag
              },
              {
                name: 'Submission Time',
                value: `<t:${Math.floor(new Date(voterInfo.submission_time).getTime() / 1000)}:F>`
              }
            ],
            color: 0xe74c3c
          }]
        });
        continue;
      }

      // Record the vote
      try {
        await recordVoteSubmission(vote, voterInfo, message.author, choice);

        await message.reply({
          embeds: [{
            title: '✅ Vote Recorded',
            description: `Your vote has been cryptographically sealed and cannot be changed.`,
            fields: [
              {
                name: 'Vote',
                value: vote.vote_details.title
              },
              {
                name: 'Your Alliance',
                value: voterInfo.alliance_tag,
                inline: true
              },
              {
                name: 'Your Choice',
                value: choice.toUpperCase(),
                inline: true
              },
              {
                name: 'Recorded At',
                value: `<t:${Math.floor(Date.now() / 1000)}:F>`,
                inline: false
              },
              {
                name: '🔒 Security',
                value: 'Your vote has been added to the integrity hash chain and is immutable.'
              }
            ],
            color: 0x28a745,
            timestamp: new Date()
          }]
        });

        console.log(`[VOTE] ${voterInfo.alliance_tag} voted ${choice} on ${vote.vote_id}`);

        // Check if all votes are now in (early close)
        if (vote.voting_period.early_close_enabled) {
          const allSubmitted = vote.council_snapshot.voter_details.every(v => v.vote_submitted);

          if (allSubmitted) {
            console.log(`[VOTE] All votes submitted for ${vote.vote_id}, finalizing early...`);
            await finalizeVote(vote.vote_id, 'all_votes_submitted', client);
          }
        }
      } catch (error) {
        console.error('[ERROR] Failed to record vote:', error);
        await message.reply({
          embeds: [{
            title: '❌ Error',
            description: `Failed to record your vote: ${error.message}`,
            color: 0xe74c3c
          }]
        });
      }
    }
  }
};
