/**
 * Council Rotation Utilities
 * Determines current council members based on rotation schedule
 */

const { getRotationSchedule, getAlliances } = require('./dataAccess');

/**
 * Get current council members with voter details
 */
async function getCurrentCouncilMembers() {
  const rotation = await getRotationSchedule();
  const alliances = await getAlliances();

  const currentWeek = rotation.currentWeekNumber;
  const schedule = rotation.schedule.find(s => s.weekNumber === currentWeek);

  if (!schedule) {
    throw new Error(`No schedule found for week ${currentWeek}`);
  }

  // Top 5 are permanent members
  const permanentMembers = rotation.metadata.top15Snapshot.slice(0, 5);
  const rotatingMembers = schedule.rotatingMembers;

  const councilTags = [...permanentMembers, ...rotatingMembers];

  // Get voter details from alliances.json
  const voterDetails = councilTags.map(tag => {
    const alliance = alliances.find(a => a.tag === tag);

    if (!alliance) {
      console.warn(`[WARN] Alliance ${tag} not found in alliances.json`);
      return {
        alliance_tag: tag,
        r5_name: 'Unknown',
        discord_id: null,
        delegated_to: null,
        vote_submitted: false,
        submission_time: null
      };
    }

    return {
      alliance_tag: tag,
      r5_name: alliance.r5?.name || 'Unknown',
      discord_id: alliance.r5?.discordId || null,
      delegated_to: alliance.r5?.delegated_voter || null,
      vote_submitted: false,
      submission_time: null
    };
  });

  return {
    week_number: currentWeek,
    permanent_members: permanentMembers,
    rotating_members: rotatingMembers,
    voter_details: voterDetails
  };
}

/**
 * Check if a Discord user ID is eligible to vote
 */
async function isEligibleVoter(discordId) {
  const council = await getCurrentCouncilMembers();

  return council.voter_details.find(voter => {
    // Check if user is the R5 or the delegated voter
    return voter.discord_id === discordId || voter.delegated_to === discordId;
  });
}

/**
 * Get voter info by Discord ID
 */
async function getVoterInfo(discordId, vote) {
  if (!vote || !vote.council_snapshot) {
    return null;
  }

  return vote.council_snapshot.voter_details.find(voter => {
    return voter.discord_id === discordId || voter.delegated_to === discordId;
  });
}

/**
 * Check if all votes are submitted
 */
function allVotesSubmitted(vote) {
  return vote.council_snapshot.voter_details.every(v => v.vote_submitted);
}

/**
 * Count votes by choice
 */
function countVotes(submissions) {
  const counts = {
    yes: 0,
    no: 0,
    abstain: 0
  };

  submissions.forEach(submission => {
    const choice = submission.vote_choice.toLowerCase();
    if (counts.hasOwnProperty(choice)) {
      counts[choice]++;
    }
  });

  return counts;
}

/**
 * Determine vote outcome
 */
function determineOutcome(counts, totalEligible) {
  const { yes, no } = counts;

  if (yes > no) {
    return 'approved';
  } else if (no > yes) {
    return 'rejected';
  } else {
    return 'tie';
  }
}

module.exports = {
  getCurrentCouncilMembers,
  isEligibleVoter,
  getVoterInfo,
  allVotesSubmitted,
  countVotes,
  determineOutcome
};
