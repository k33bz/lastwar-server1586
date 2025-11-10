/**
 * Council Rotation Utilities
 * Determines current council members based on rotation schedule
 */

const { getRotationSchedule, getAlliances, getCouncilData, getAdminUsers } = require('./dataAccess');

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
        delegated_voters: [],
        vote_submitted: false,
        submission_time: null
      };
    }

    // Get R4s who can vote (delegation enabled)
    const delegated_voters = [];
    if (alliance.r4s && Array.isArray(alliance.r4s)) {
      alliance.r4s.forEach(r4 => {
        if (r4.canVote && r4.discordId) {
          delegated_voters.push({
            name: r4.name,
            discord_id: r4.discordId,
            role: r4.role || 'R4'
          });
        }
      });
    }

    return {
      alliance_tag: tag,
      r5_name: alliance.r5?.name || 'Unknown',
      discord_id: alliance.r5?.discordId || null,
      delegated_voters: delegated_voters,  // Array of R4s who can vote
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
    // Check if user is the R5
    if (voter.discord_id === discordId) {
      return true;
    }

    // Check if user is a delegated R4 voter
    if (voter.delegated_voters && Array.isArray(voter.delegated_voters)) {
      return voter.delegated_voters.some(r4 => r4.discord_id === discordId);
    }

    return false;
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
    // Check if user is the R5
    if (voter.discord_id === discordId) {
      return true;
    }

    // Check if user is a delegated R4 voter
    if (voter.delegated_voters && Array.isArray(voter.delegated_voters)) {
      return voter.delegated_voters.some(r4 => r4.discord_id === discordId);
    }

    return false;
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

/**
 * Get the current president's Discord ID
 * First checks admin users with "president" role, then falls back to council.json + alliances
 */
async function getPresidentDiscordId() {
  try {
    // First, check admin users for anyone with "president" role and Discord ID
    const adminUsers = await getAdminUsers();

    if (adminUsers && adminUsers.users) {
      const presidentUser = adminUsers.users.find(user =>
        user.roles && user.roles.includes('president') && user.discord_id
      );

      if (presidentUser && presidentUser.discord_id) {
        console.log(`[INFO] President Discord ID found in admin users: ${presidentUser.email}`);
        return presidentUser.discord_id;
      }
    }
  } catch (error) {
    console.warn('[WARN] Could not read admin users, falling back to alliance data:', error.message);
  }

  // Fallback: Use council.json + alliances.json
  const councilData = await getCouncilData();
  const alliances = await getAlliances();

  // Get president alliance tag from council.json
  const presidentAllianceTag = councilData.president?.alliance_tag;

  if (!presidentAllianceTag) {
    console.error('[ERROR] No president designated in council.json');
    return null;
  }

  // Find the alliance data
  const presidentAlliance = alliances.find(a => a.tag === presidentAllianceTag);

  if (!presidentAlliance) {
    console.error(`[ERROR] President alliance ${presidentAllianceTag} not found in alliances.json`);
    return null;
  }

  // Get R5 Discord ID
  const discordId = presidentAlliance.r5?.discordId || null;

  if (!discordId) {
    console.warn(`[WARN] President alliance ${presidentAllianceTag} R5 has no Discord ID set`);
  }

  return discordId;
}

module.exports = {
  getCurrentCouncilMembers,
  isEligibleVoter,
  getVoterInfo,
  allVotesSubmitted,
  countVotes,
  determineOutcome,
  getPresidentDiscordId
};
