/**
 * Data Access Layer
 * Direct file system access to website data files
 */

const fs = require('fs').promises;
const path = require('path');

const DATA_DIR = path.join(__dirname, process.env.DATA_DIR || '../../data');

/**
 * Get rotation schedule
 */
async function getRotationSchedule() {
  const data = await fs.readFile(
    path.join(DATA_DIR, 'rotation-schedule.json'),
    'utf8'
  );
  return JSON.parse(data);
}

/**
 * Get alliances data
 */
async function getAlliances() {
  const data = await fs.readFile(
    path.join(DATA_DIR, 'alliances.json'),
    'utf8'
  );
  return JSON.parse(data);
}

/**
 * Get council data (president, roles, etc.)
 */
async function getCouncilData() {
  const data = await fs.readFile(
    path.join(DATA_DIR, 'council.json'),
    'utf8'
  );
  return JSON.parse(data);
}

/**
 * Get all votes
 */
async function getVotes() {
  const votesPath = path.join(DATA_DIR, 'discord-votes.json');

  try {
    const data = await fs.readFile(votesPath, 'utf8');
    return JSON.parse(data);
  } catch (error) {
    if (error.code === 'ENOENT') {
      // File doesn't exist, create it
      const initialData = { votes: [] };
      await fs.writeFile(votesPath, JSON.stringify(initialData, null, 2));
      return initialData;
    }
    throw error;
  }
}

/**
 * Save votes
 */
async function saveVotes(votesData) {
  const votesPath = path.join(DATA_DIR, 'discord-votes.json');
  await fs.writeFile(votesPath, JSON.stringify(votesData, null, 2));
}

/**
 * Get a specific vote by ID
 */
async function getVote(voteId) {
  const data = await getVotes();
  return data.votes.find(v => v.vote_id === voteId);
}

/**
 * Get all active votes
 */
async function getActiveVotes() {
  const data = await getVotes();
  return data.votes.filter(v => v.status === 'active');
}

/**
 * Save a new vote
 */
async function saveVote(vote) {
  const data = await getVotes();

  // Check if vote already exists
  const existingIndex = data.votes.findIndex(v => v.vote_id === vote.vote_id);

  if (existingIndex >= 0) {
    // Update existing vote
    data.votes[existingIndex] = vote;
  } else {
    // Add new vote
    data.votes.unshift(vote);
  }

  await saveVotes(data);
  return vote;
}

/**
 * Update vote status
 */
async function updateVote(voteId, updates) {
  const data = await getVotes();
  const voteIndex = data.votes.findIndex(v => v.vote_id === voteId);

  if (voteIndex < 0) {
    throw new Error(`Vote ${voteId} not found`);
  }

  // Merge updates
  data.votes[voteIndex] = {
    ...data.votes[voteIndex],
    ...updates
  };

  await saveVotes(data);
  return data.votes[voteIndex];
}

module.exports = {
  getRotationSchedule,
  getAlliances,
  getCouncilData,
  getVotes,
  saveVotes,
  getVote,
  getActiveVotes,
  saveVote,
  updateVote
};
