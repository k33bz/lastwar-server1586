/**
 * Vote Integrity System
 * Blockchain-inspired hash chaining for immutable vote records
 */

const crypto = require('crypto');

/**
 * Create a hash of data
 */
function createHash(data) {
  const dataString = typeof data === 'string' ? data : JSON.stringify(data);
  return crypto.createHash('sha256').update(dataString).digest('hex');
}

/**
 * Create initial vote hash
 */
function createVoteHash(voteData) {
  const hashInput = {
    vote_id: voteData.vote_id,
    created_at: voteData.created_at,
    created_by: voteData.created_by,
    title: voteData.vote_details.title,
    description: voteData.vote_details.description,
    council_snapshot: voteData.council_snapshot
  };

  return createHash(hashInput);
}

/**
 * Create submission hash (links to previous hash in chain)
 */
function createSubmissionHash(submission, previousHash) {
  const hashInput = {
    alliance: submission.alliance_tag,
    voter: submission.voter_discord_id,
    choice: submission.vote_choice,
    timestamp: submission.submitted_at,
    previous_hash: previousHash
  };

  return createHash(hashInput);
}

/**
 * Add event to hash chain
 */
function addHashChainEvent(vote, eventName, eventData) {
  if (!vote.integrity) {
    vote.integrity = {
      vote_hash: createVoteHash(vote),
      hash_chain: []
    };
  }

  const previousHash = vote.integrity.hash_chain.length > 0
    ? vote.integrity.hash_chain[vote.integrity.hash_chain.length - 1].hash
    : vote.integrity.vote_hash;

  const newEvent = {
    event: eventName,
    timestamp: new Date().toISOString(),
    previous_hash: previousHash,
    hash: createHash({
      event: eventName,
      data: eventData,
      timestamp: new Date().toISOString(),
      previous_hash: previousHash
    })
  };

  vote.integrity.hash_chain.push(newEvent);

  return newEvent.hash;
}

/**
 * Verify hash chain integrity
 */
function verifyVoteIntegrity(vote) {
  if (!vote.integrity || !vote.integrity.hash_chain) {
    return {
      valid: false,
      error: 'Vote has no integrity data'
    };
  }

  const hashChain = vote.integrity.hash_chain;

  // Verify initial hash links to vote_hash
  if (hashChain.length > 0 && hashChain[0].previous_hash !== vote.integrity.vote_hash) {
    return {
      valid: false,
      error: 'Initial hash does not link to vote hash',
      tampered_event: hashChain[0].event
    };
  }

  // Verify each subsequent hash links to previous
  for (let i = 1; i < hashChain.length; i++) {
    const current = hashChain[i];
    const previous = hashChain[i - 1];

    if (current.previous_hash !== previous.hash) {
      return {
        valid: false,
        error: `Hash chain broken at index ${i}`,
        tampered_event: current.event
      };
    }
  }

  return {
    valid: true,
    message: 'Vote integrity verified',
    chain_length: hashChain.length
  };
}

/**
 * Generate verification receipt
 */
function generateVerificationReceipt(vote) {
  const verification = verifyVoteIntegrity(vote);

  return {
    vote_id: vote.vote_id,
    status: vote.status,
    verification: verification,
    vote_hash: vote.integrity?.vote_hash || null,
    chain_length: vote.integrity?.hash_chain?.length || 0,
    total_submissions: vote.submissions?.length || 0,
    verified_at: new Date().toISOString(),
    verification_url: `${process.env.WEBSITE_URL}/admin/vote_verify.php?id=${vote.vote_id}`
  };
}

/**
 * Create HMAC signature for webhook authentication
 */
function createWebhookSignature(payload) {
  const secret = process.env.WEBHOOK_SECRET || 'default_secret_change_me';
  const payloadString = typeof payload === 'string' ? payload : JSON.stringify(payload);

  return crypto
    .createHmac('sha256', secret)
    .update(payloadString)
    .digest('hex');
}

module.exports = {
  createHash,
  createVoteHash,
  createSubmissionHash,
  addHashChainEvent,
  verifyVoteIntegrity,
  generateVerificationReceipt,
  createWebhookSignature
};
