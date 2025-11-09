/**
 * Webhook Client
 * Sends vote results to website via authenticated webhook
 */

const { createWebhookSignature } = require('./voteIntegrity');

/**
 * Send vote to website
 */
async function sendToWebsite(vote) {
  const webhookUrl = `${process.env.WEBSITE_URL}/admin/discord_vote_webhook.php`;

  const payload = JSON.stringify(vote);
  const signature = createWebhookSignature(payload);

  try {
    const response = await fetch(webhookUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Bot-Signature': signature
      },
      body: payload
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const result = await response.json();
    console.log(`[WEBHOOK] Sent vote ${vote.vote_id} to website:`, result);

    return result;
  } catch (error) {
    console.error('[ERROR] Failed to send vote to website:', error);
    throw error;
  }
}

module.exports = {
  sendToWebsite
};
