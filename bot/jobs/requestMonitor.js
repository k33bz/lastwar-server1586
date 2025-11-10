/**
 * Request Monitor Job
 * Auto-approves vote requests after 12 hours if president doesn't respond
 */

const cron = require('node-cron');
const { getPendingRequests, approveRequest, linkVoteToRequest } = require('../utils/voteRequestManager');
const { createVote, publishVote } = require('../utils/voteManager');

module.exports = function(client) {
  // Run every 15 minutes
  cron.schedule('*/15 * * * *', async () => {
    try {
      const pendingRequests = await getPendingRequests();

      if (pendingRequests.length === 0) {
        return; // No pending requests
      }

      const now = new Date();
      const AUTO_APPROVE_HOURS = 12;

      for (const request of pendingRequests) {
        const createdAt = new Date(request.created_at);
        const hoursSinceCreated = (now - createdAt) / (1000 * 60 * 60);

        // Check if 12 hours have passed
        if (hoursSinceCreated >= AUTO_APPROVE_HOURS) {
          console.log(`[REQUEST-MONITOR] Auto-approving request ${request.request_id} (${Math.floor(hoursSinceCreated)}h old)`);

          try {
            // Approve the request (using 'auto' as president ID)
            await approveRequest(request.request_id, 'auto-approved');

            // Create the vote automatically
            const vote = await createVote(request.vote_details, {
              id: 'system',
              username: 'Auto-Approval System',
              tag: 'System'
            });

            // Link vote to request
            await linkVoteToRequest(request.request_id, vote.vote_id);

            // Publish the vote
            await publishVote(vote, client);

            // Notify requester
            try {
              const requester = await client.users.fetch(request.requested_by.discord_id);
              const dm = await requester.createDM();

              await dm.send({
                embeds: [{
                  title: '✅ Your Vote Request Was Auto-Approved',
                  description: `The president didn't respond within 12 hours, so your vote request was automatically approved and the vote has been created.`,
                  fields: [
                    {
                      name: 'Request ID',
                      value: request.request_id
                    },
                    {
                      name: 'Vote ID',
                      value: vote.vote_id
                    },
                    {
                      name: 'Title',
                      value: vote.vote_details.title
                    },
                    {
                      name: 'Auto-Approval Reason',
                      value: `President did not respond within ${AUTO_APPROVE_HOURS} hours`
                    }
                  ],
                  color: 0xffc107
                }]
              });

              console.log(`[REQUEST-MONITOR] Notified ${request.requested_by.username} about auto-approval`);
            } catch (error) {
              console.error('[ERROR] Failed to notify requester:', error);
            }

            console.log(`[REQUEST-MONITOR] Auto-approved ${request.request_id}, created vote ${vote.vote_id}`);
          } catch (error) {
            console.error(`[ERROR] Failed to auto-approve request ${request.request_id}:`, error);
          }
        }
      }
    } catch (error) {
      console.error('[ERROR] Request monitor error:', error);
    }
  });

  console.log('[MONITOR] Request auto-approval monitoring started (runs every 15 minutes)');
};
