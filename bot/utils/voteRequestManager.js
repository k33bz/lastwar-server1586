/**
 * Vote Request Manager
 * Handles vote requests from users that need president approval
 */

const fs = require('fs').promises;
const path = require('path');

const REQUESTS_FILE = path.join(__dirname, '../../data/discord-vote-requests.json');

/**
 * Generate unique request ID
 */
function generateRequestId() {
  const date = new Date().toISOString().slice(0, 10).replace(/-/g, '');
  const random = Math.random().toString(36).substring(2, 8);
  return `votereq_${date}_${random}`;
}

/**
 * Load vote requests
 */
async function loadRequests() {
  try {
    const data = await fs.readFile(REQUESTS_FILE, 'utf8');
    return JSON.parse(data);
  } catch (error) {
    if (error.code === 'ENOENT') {
      // File doesn't exist, create it
      const initialData = { requests: [] };
      await saveRequests(initialData);
      return initialData;
    }
    throw error;
  }
}

/**
 * Save vote requests
 */
async function saveRequests(data) {
  await fs.writeFile(REQUESTS_FILE, JSON.stringify(data, null, 2));
}

/**
 * Create a new vote request
 */
async function createVoteRequest(requestData, requester) {
  const requestId = generateRequestId();
  const now = new Date().toISOString();

  const request = {
    request_id: requestId,
    status: 'pending',
    created_at: now,
    requested_by: {
      discord_id: requester.id,
      username: requester.username,
      tag: requester.tag
    },
    vote_details: {
      title: requestData.title,
      description: requestData.description,
      category: requestData.category || 'other'
    },
    president_response: null,
    responded_at: null,
    created_vote_id: null
  };

  const data = await loadRequests();
  data.requests.unshift(request);
  await saveRequests(data);

  console.log(`[REQUEST] Created vote request ${requestId} by ${requester.username}`);

  return request;
}

/**
 * Get pending requests
 */
async function getPendingRequests() {
  const data = await loadRequests();
  return data.requests.filter(r => r.status === 'pending');
}

/**
 * Get a specific request
 */
async function getRequest(requestId) {
  const data = await loadRequests();
  return data.requests.find(r => r.request_id === requestId);
}

/**
 * Approve a vote request
 */
async function approveRequest(requestId, presidentId) {
  const data = await loadRequests();
  const requestIndex = data.requests.findIndex(r => r.request_id === requestId);

  if (requestIndex < 0) {
    throw new Error(`Request ${requestId} not found`);
  }

  data.requests[requestIndex].status = 'approved';
  data.requests[requestIndex].president_response = {
    approved: true,
    president_discord_id: presidentId,
    timestamp: new Date().toISOString()
  };
  data.requests[requestIndex].responded_at = new Date().toISOString();

  await saveRequests(data);

  console.log(`[REQUEST] Approved vote request ${requestId}`);

  return data.requests[requestIndex];
}

/**
 * Reject a vote request
 */
async function rejectRequest(requestId, presidentId, reason) {
  const data = await loadRequests();
  const requestIndex = data.requests.findIndex(r => r.request_id === requestId);

  if (requestIndex < 0) {
    throw new Error(`Request ${requestId} not found`);
  }

  data.requests[requestIndex].status = 'rejected';
  data.requests[requestIndex].president_response = {
    approved: false,
    president_discord_id: presidentId,
    reason: reason || 'No reason provided',
    timestamp: new Date().toISOString()
  };
  data.requests[requestIndex].responded_at = new Date().toISOString();

  await saveRequests(data);

  console.log(`[REQUEST] Rejected vote request ${requestId}`);

  return data.requests[requestIndex];
}

/**
 * Update request with created vote ID
 */
async function linkVoteToRequest(requestId, voteId) {
  const data = await loadRequests();
  const requestIndex = data.requests.findIndex(r => r.request_id === requestId);

  if (requestIndex >= 0) {
    data.requests[requestIndex].created_vote_id = voteId;
    await saveRequests(data);
  }
}

module.exports = {
  generateRequestId,
  createVoteRequest,
  getPendingRequests,
  getRequest,
  approveRequest,
  rejectRequest,
  linkVoteToRequest
};
