/**
 * Vote Manager
 * Handles vote creation, publishing, and finalization
 */

const { saveVote, updateVote, getVote } = require('./dataAccess');
const { getCurrentCouncilMembers, countVotes, determineOutcome, allVotesSubmitted } = require('./councilUtils');
const { createVoteHash, addHashChainEvent, createWebhookSignature } = require('./voteIntegrity');
const { sendToWebsite } = require('./webhookClient');

/**
 * Generate unique vote ID
 */
function generateVoteId() {
  const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
  const random = Math.random().toString(36).substring(2, 8);
  return `vote_${date}_${random}`;
}

/**
 * Create a new vote
 */
async function createVote(voteData, creator) {
  const voteId = generateVoteId();
  const now = new Date().toISOString();

  // Get current council members
  const councilSnapshot = await getCurrentCouncilMembers();

  const vote = {
    vote_id: voteId,
    status: 'active',
    created_at: now,
    created_by: {
      discord_id: creator.id,
      username: creator.username,
      role: 'president' // TODO: Get actual role from permissions
    },

    vote_details: {
      title: voteData.title,
      description: voteData.description,
      options: ['yes', 'no', 'abstain'],
      category: voteData.category || 'other'
    },

    voting_period: {
      start_time: now,
      end_time: new Date(Date.now() + (parseInt(process.env.VOTE_DURATION_HOURS || 24) * 60 * 60 * 1000)).toISOString(),
      duration_hours: parseInt(process.env.VOTE_DURATION_HOURS || 24),
      early_close_enabled: process.env.ENABLE_EARLY_CLOSE === 'true'
    },

    council_snapshot: councilSnapshot,

    submissions: [],

    results: null,

    discord_metadata: {
      vote_channel_id: null,
      vote_message_id: null,
      result_message_id: null,
      notification_dm_ids: []
    },

    integrity: {
      vote_hash: null,
      hash_chain: []
    }
  };

  // Create initial integrity hash
  vote.integrity.vote_hash = createVoteHash(vote);

  // Add creation event to hash chain
  addHashChainEvent(vote, 'vote_created', {
    vote_id: voteId,
    created_by: creator.username
  });

  // Save vote
  await saveVote(vote);

  console.log(`[VOTE] Created vote ${voteId}: ${vote.vote_details.title}`);

  return vote;
}

/**
 * Publish vote to Discord channel and notify voters
 */
async function publishVote(vote, client) {
  const channelId = process.env.VOTE_CHANNEL_ID;

  if (!channelId) {
    console.warn('[WARN] VOTE_CHANNEL_ID not configured, skipping channel post');
  } else {
    try {
      const channel = await client.channels.fetch(channelId);

      const message = await channel.send({
        embeds: [{
          title: `🗳️ New Council Vote: ${vote.vote_details.title}`,
          description: vote.vote_details.description,
          fields: [
            {
              name: 'Vote ID',
              value: vote.vote_id,
              inline: true
            },
            {
              name: 'Category',
              value: vote.vote_details.category.replace('_', ' ').toUpperCase(),
              inline: true
            },
            {
              name: 'Deadline',
              value: `<t:${Math.floor(new Date(vote.voting_period.end_time).getTime() / 1000)}:R>`,
              inline: true
            },
            {
              name: 'Council Members (Week ' + vote.council_snapshot.week_number + ')',
              value: [
                ...vote.council_snapshot.permanent_members.map(t => `${t} (Permanent)`),
                ...vote.council_snapshot.rotating_members.map(t => `${t} (Rotating)`)
              ].join('\n')
            },
            {
              name: 'How to Vote',
              value: 'Council members will receive a DM. Reply to the bot\'s DM with:\n`vote: yes` or `vote: no` or `vote: abstain`'
            }
          ],
          color: 0x667eea,
          timestamp: new Date(vote.created_at),
          footer: {
            text: `Created by ${vote.created_by.username}`
          }
        }]
      });

      vote.discord_metadata.vote_channel_id = channelId;
      vote.discord_metadata.vote_message_id = message.id;

      await updateVote(vote.vote_id, {
        discord_metadata: vote.discord_metadata
      });

      console.log(`[VOTE] Posted vote ${vote.vote_id} to channel ${channelId}`);
    } catch (error) {
      console.error('[ERROR] Failed to post vote to channel:', error);
    }
  }

  // Notify all eligible voters via DM
  await notifyVoters(vote, client);
}

/**
 * Notify eligible voters via DM
 */
async function notifyVoters(vote, client) {
  const dmIds = [];

  for (const voter of vote.council_snapshot.voter_details) {
    // Use delegated voter if set, otherwise R5
    const discordId = voter.delegated_to || voter.discord_id;

    if (!discordId) {
      console.warn(`[WARN] No Discord ID for ${voter.alliance_tag}, skipping notification`);
      continue;
    }

    try {
      const user = await client.users.fetch(discordId);
      const dm = await user.createDM();

      const message = await dm.send({
        embeds: [{
          title: `🗳️ New Council Vote: ${vote.vote_details.title}`,
          description: vote.vote_details.description,
          fields: [
            {
              name: 'Your Alliance',
              value: voter.alliance_tag,
              inline: true
            },
            {
              name: 'Vote ID',
              value: vote.vote_id,
              inline: true
            },
            {
              name: 'Voting Deadline',
              value: `<t:${Math.floor(new Date(vote.voting_period.end_time).getTime() / 1000)}:F>`,
              inline: false
            },
            {
              name: '📝 How to Vote',
              value: 'Reply to this DM with one of:\n```\nvote: yes\nvote: no\nvote: abstain\n```',
              inline: false
            }
          ],
          color: 0x667eea,
          timestamp: new Date()
        }]
      });

      dmIds.push(message.id);
      console.log(`[VOTE] Notified ${voter.alliance_tag} (${user.username}) about vote ${vote.vote_id}`);
    } catch (error) {
      console.error(`[ERROR] Failed to notify ${voter.alliance_tag}:`, error.message);
    }
  }

  vote.discord_metadata.notification_dm_ids = dmIds;

  await updateVote(vote.vote_id, {
    discord_metadata: vote.discord_metadata
  });
}

/**
 * Record a vote submission via unified API
 */
async function recordVoteSubmission(vote, voterInfo, discordUser, choice) {
  const now = new Date().toISOString();

  // Prepare API payload
  const payload = {
    vote_id: vote.vote_id,
    vote_choice: choice,
    submission_method: 'discord',
    discord_id: discordUser.id,
    username: discordUser.username,
    alliance_tag: voterInfo.alliance_tag,
    user_uid: null, // Bot doesn't have UID, API can look it up if needed
    user_email: null // Bot doesn't have email, API can look it up if needed
  };

  const payloadString = JSON.stringify(payload);
  const signature = createWebhookSignature(payloadString);

  // Call unified submission API
  const apiUrl = `${process.env.WEBSITE_URL}/admin/discord_vote_submit_api.php?action=submit_vote`;

  try {
    const response = await fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Bot-Signature': signature
      },
      body: payloadString
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({ error: response.statusText }));
      throw new Error(`API Error: ${errorData.error || response.statusText}`);
    }

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.error || 'Unknown API error');
    }

    console.log(`[VOTE] Recorded ${choice} vote from ${voterInfo.alliance_tag} (${discordUser.username}) on vote ${vote.vote_id} via API`);

    // Reload vote from file to get updated data
    const updatedVote = await getVote(vote.vote_id);

    // Update local vote object reference
    Object.assign(vote, updatedVote);

    // Add to hash chain (integrity tracking)
    addHashChainEvent(vote, `vote_submitted_${voterInfo.alliance_tag}`, {
      alliance: voterInfo.alliance_tag,
      choice: choice,
      timestamp: now
    });

    // Save vote with hash chain update
    await updateVote(vote.vote_id, vote);

    return result.submission;

  } catch (error) {
    console.error(`[ERROR] Failed to record vote via API:`, error.message);
    throw error;
  }
}

/**
 * Finalize a vote
 */
async function finalizeVote(voteId, reason, client) {
  const vote = await require('./dataAccess').getVote(voteId);

  if (!vote || vote.status !== 'active') {
    console.warn(`[WARN] Cannot finalize vote ${voteId}: not active`);
    return null;
  }

  const now = new Date().toISOString();

  // Calculate results
  const counts = countVotes(vote.submissions);
  const totalEligible = vote.council_snapshot.voter_details.length;
  const totalSubmitted = vote.submissions.length;
  const absentCount = totalEligible - totalSubmitted;

  const outcome = determineOutcome(counts, totalEligible);

  vote.status = 'completed';
  vote.results = {
    total_eligible: totalEligible,
    total_submitted: totalSubmitted,
    yes_count: counts.yes,
    no_count: counts.no,
    abstain_count: counts.abstain,
    absent_count: absentCount,
    outcome: outcome,
    finalized_at: now,
    finalization_reason: reason
  };

  // Add finalization to hash chain
  addHashChainEvent(vote, 'vote_finalized', vote.results);

  // Save updated vote
  await updateVote(voteId, vote);

  console.log(`[VOTE] Finalized vote ${voteId}: ${outcome.toUpperCase()} (${counts.yes} yes, ${counts.no} no, ${counts.abstain} abstain)`);

  // Post results to Discord channel
  await postVoteResults(vote, client);

  // Notify voters via DM
  await notifyVotersOfResults(vote, client);

  // Send to website
  await sendToWebsite(vote);

  return vote;
}

/**
 * Post vote results to Discord channel
 */
async function postVoteResults(vote, client) {
  const channelId = vote.discord_metadata.vote_channel_id;

  if (!channelId) {
    console.warn('[WARN] No channel ID for vote results');
    return;
  }

  try {
    const channel = await client.channels.fetch(channelId);

    const yesAlliances = vote.submissions.filter(s => s.vote_choice === 'yes').map(s => s.alliance_tag);
    const noAlliances = vote.submissions.filter(s => s.vote_choice === 'no').map(s => s.alliance_tag);
    const abstainAlliances = vote.submissions.filter(s => s.vote_choice === 'abstain').map(s => s.alliance_tag);
    const absentAlliances = vote.council_snapshot.voter_details
      .filter(v => !v.vote_submitted)
      .map(v => v.alliance_tag);

    const outcomeEmoji = vote.results.outcome === 'approved' ? '✅' : vote.results.outcome === 'rejected' ? '❌' : '⚖️';
    const outcomeColor = vote.results.outcome === 'approved' ? 0x28a745 : vote.results.outcome === 'rejected' ? 0xdc3545 : 0xffc107;

    const message = await channel.send({
      embeds: [{
        title: `${outcomeEmoji} Vote Results: ${vote.vote_details.title}`,
        description: `**Outcome: ${vote.results.outcome.toUpperCase()}**`,
        fields: [
          {
            name: `✅ Yes (${vote.results.yes_count})`,
            value: yesAlliances.length > 0 ? yesAlliances.join(', ') : 'None',
            inline: false
          },
          {
            name: `❌ No (${vote.results.no_count})`,
            value: noAlliances.length > 0 ? noAlliances.join(', ') : 'None',
            inline: false
          },
          {
            name: `⚪ Abstain (${vote.results.abstain_count})`,
            value: abstainAlliances.length > 0 ? abstainAlliances.join(', ') : 'None',
            inline: false
          },
          {
            name: `⭕ Absent (${vote.results.absent_count})`,
            value: absentAlliances.length > 0 ? absentAlliances.join(', ') : 'None',
            inline: false
          },
          {
            name: 'Finalization Reason',
            value: vote.results.finalization_reason === 'all_votes_submitted' ? 'All votes submitted (early close)' : '24-hour deadline reached',
            inline: false
          },
          {
            name: 'Vote ID',
            value: vote.vote_id,
            inline: true
          },
          {
            name: 'Integrity Hash',
            value: `\`${vote.integrity.vote_hash.substring(0, 16)}...\``,
            inline: true
          }
        ],
        color: outcomeColor,
        timestamp: new Date(vote.results.finalized_at)
      }]
    });

    vote.discord_metadata.result_message_id = message.id;

    await updateVote(vote.vote_id, {
      discord_metadata: vote.discord_metadata
    });

    console.log(`[VOTE] Posted results for vote ${vote.vote_id} to channel`);
  } catch (error) {
    console.error('[ERROR] Failed to post vote results:', error);
  }
}

/**
 * Notify voters of final results via DM
 */
async function notifyVotersOfResults(vote, client) {
  console.log(`[VOTE] Sending result notifications for vote ${vote.vote_id}...`);

  const outcomeEmoji = vote.results.outcome === 'approved' ? '✅' : vote.results.outcome === 'rejected' ? '❌' : '⚖️';
  const outcomeColor = vote.results.outcome === 'approved' ? 0x28a745 : vote.results.outcome === 'rejected' ? 0xdc3545 : 0xffc107;

  // Get list of all Discord IDs who received the vote
  const notifiedDiscordIds = vote.discord_metadata.notification_dm_ids || [];

  for (const discordId of notifiedDiscordIds) {
    try {
      const user = await client.users.fetch(discordId);
      const dm = await user.createDM();

      // Find if this voter submitted a vote
      const submission = vote.submissions.find(s => s.submitted_by.discord_id === discordId);
      const voterInfo = vote.council_snapshot.voter_details.find(v =>
        v.discord_id === discordId || v.delegated_voters?.some(dv => dv.discord_id === discordId)
      );

      await dm.send({
        embeds: [{
          title: `${outcomeEmoji} Vote Results: ${vote.vote_details.title}`,
          description: `**Final Outcome: ${vote.results.outcome.toUpperCase()}**`,
          fields: [
            {
              name: 'Your Vote',
              value: submission ? `You voted: **${submission.vote_choice.toUpperCase()}**` : '⚠️ You did not vote',
              inline: false
            },
            {
              name: 'Results',
              value: [
                `✅ Yes: ${vote.results.yes_count}`,
                `❌ No: ${vote.results.no_count}`,
                `⚪ Abstain: ${vote.results.abstain_count}`,
                `⭕ Absent: ${vote.results.absent_count}`
              ].join('\n'),
              inline: false
            },
            {
              name: 'Vote ID',
              value: vote.vote_id,
              inline: true
            },
            {
              name: 'Finalized',
              value: new Date(vote.results.finalized_at).toLocaleString(),
              inline: true
            }
          ],
          color: outcomeColor,
          timestamp: new Date(vote.results.finalized_at),
          footer: {
            text: vote.results.finalization_reason === 'all_votes_submitted' ?
              'All votes submitted (early close)' :
              '24-hour deadline reached'
          }
        }]
      });

      console.log(`[VOTE] Notified ${user.tag} of results for vote ${vote.vote_id}`);
    } catch (error) {
      console.error(`[ERROR] Failed to notify Discord user ${discordId} of results:`, error.message);
    }
  }

  console.log(`[VOTE] Result notifications sent for vote ${vote.vote_id}`);
}

module.exports = {
  generateVoteId,
  createVote,
  publishVote,
  notifyVoters,
  recordVoteSubmission,
  finalizeVote,
  postVoteResults,
  notifyVotersOfResults
};
