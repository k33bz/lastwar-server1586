/**
 * Message Create Event
 * Handles DM messages for vote submissions
 */

const { getActiveVotes } = require('../utils/dataAccess');
const { getVoterInfo, getPresidentDiscordId } = require('../utils/councilUtils');
const { recordVoteSubmission, finalizeVote, createVote, publishVote } = require('../utils/voteManager');
const { getRequest, approveRequest, rejectRequest, linkVoteToRequest } = require('../utils/voteRequestManager');

module.exports = {
  name: 'messageCreate',
  async execute(message, client) {
    // Ignore bot messages
    if (message.author.bot) return;

    // Only handle DMs
    if (!message.channel.isDMBased()) return;

    // Get message content once
    const content = message.content.toLowerCase().trim();

    // Check for approve/reject commands (president only)
    const approveMatch = content.match(/^approve:\s*(votereq_\d+_[a-z0-9]+)$/i);
    const rejectMatch = content.match(/^reject:\s*(votereq_\d+_[a-z0-9]+)\s*(.*)$/i);

    if (approveMatch || rejectMatch) {
      // Check if user is president
      const presidentId = await getPresidentDiscordId();

      if (message.author.id !== presidentId) {
        await message.reply('❌ Only the president can approve/reject vote requests.');
        return;
      }

      if (approveMatch) {
        await handleApproveRequest(approveMatch[1], message, client);
        return;
      } else if (rejectMatch) {
        await handleRejectRequest(rejectMatch[1], rejectMatch[2] || 'No reason provided', message, client);
        return;
      }
    }

    // Check for active votes
    const activeVotes = await getActiveVotes();

    if (activeVotes.length === 0) {
      return; // No active votes, ignore DM
    }

    // Parse vote command
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

/**
 * Handle approve request command
 */
async function handleApproveRequest(requestId, message, client) {
  try {
    const request = await getRequest(requestId);

    if (!request) {
      await message.reply(`❌ Request \`${requestId}\` not found.`);
      return;
    }

    if (request.status !== 'pending') {
      await message.reply(`❌ Request \`${requestId}\` has already been ${request.status}.`);
      return;
    }

    // Approve the request
    await approveRequest(requestId, message.author.id);

    await message.reply({
      embeds: [{
        title: '✅ Request Approved',
        description: 'Creating vote now...',
        fields: [
          {
            name: 'Request ID',
            value: requestId
          },
          {
            name: 'Title',
            value: request.vote_details.title
          }
        ],
        color: 0x28a745
      }]
    });

    // Create the vote automatically
    const vote = await createVote(request.vote_details, message.author);

    // Link vote to request
    await linkVoteToRequest(requestId, vote.vote_id);

    // Publish the vote
    await publishVote(vote, client);

    // Notify requester
    try {
      const requester = await client.users.fetch(request.requested_by.discord_id);
      const dm = await requester.createDM();

      await dm.send({
        embeds: [{
          title: '✅ Your Vote Request Was Approved!',
          description: `The president has approved your vote request and the vote has been created.`,
          fields: [
            {
              name: 'Request ID',
              value: requestId
            },
            {
              name: 'Vote ID',
              value: vote.vote_id
            },
            {
              name: 'Title',
              value: vote.vote_details.title
            }
          ],
          color: 0x28a745
        }]
      });
    } catch (error) {
      console.error('[ERROR] Failed to notify requester:', error);
    }

    await message.reply({
      embeds: [{
        title: '✅ Vote Created Successfully',
        fields: [
          {
            name: 'Vote ID',
            value: vote.vote_id
          },
          {
            name: 'Title',
            value: vote.vote_details.title
          }
        ],
        description: 'Council members have been notified.',
        color: 0x28a745
      }]
    });

    console.log(`[REQUEST] Approved ${requestId}, created vote ${vote.vote_id}`);
  } catch (error) {
    console.error('[ERROR] Failed to approve request:', error);
    await message.reply(`❌ Failed to approve request: ${error.message}`);
  }
}

/**
 * Handle reject request command
 */
async function handleRejectRequest(requestId, reason, message, client) {
  try {
    const request = await getRequest(requestId);

    if (!request) {
      await message.reply(`❌ Request \`${requestId}\` not found.`);
      return;
    }

    if (request.status !== 'pending') {
      await message.reply(`❌ Request \`${requestId}\` has already been ${request.status}.`);
      return;
    }

    // Reject the request
    await rejectRequest(requestId, message.author.id, reason);

    await message.reply({
      embeds: [{
        title: '❌ Request Rejected',
        fields: [
          {
            name: 'Request ID',
            value: requestId
          },
          {
            name: 'Title',
            value: request.vote_details.title
          },
          {
            name: 'Reason',
            value: reason
          }
        ],
        color: 0xe74c3c
      }]
    });

    // Notify requester
    try {
      const requester = await client.users.fetch(request.requested_by.discord_id);
      const dm = await requester.createDM();

      await dm.send({
        embeds: [{
          title: '❌ Your Vote Request Was Rejected',
          description: `The president has rejected your vote request.`,
          fields: [
            {
              name: 'Request ID',
              value: requestId
            },
            {
              name: 'Title',
              value: request.vote_details.title
            },
            {
              name: 'Reason',
              value: reason
            }
          ],
          color: 0xe74c3c
        }]
      });
    } catch (error) {
      console.error('[ERROR] Failed to notify requester:', error);
    }

    console.log(`[REQUEST] Rejected ${requestId}: ${reason}`);
  } catch (error) {
    console.error('[ERROR] Failed to reject request:', error);
    await message.reply(`❌ Failed to reject request: ${error.message}`);
  }
}
