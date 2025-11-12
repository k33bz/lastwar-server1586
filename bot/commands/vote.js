/**
 * Vote Command
 * Slash command handler for /vote
 */

const { SlashCommandBuilder, PermissionFlagsBits } = require('discord.js');
const { getActiveVotes, getVote, getAdminUsers } = require('../utils/dataAccess');
const { createVote, publishVote } = require('../utils/voteManager');
const { verifyVoteIntegrity, generateVerificationReceipt } = require('../utils/voteIntegrity');
const { getPresidentDiscordId } = require('../utils/councilUtils');
const { createVoteRequest, getPendingRequests } = require('../utils/voteRequestManager');

module.exports = {
  data: new SlashCommandBuilder()
    .setName('vote')
    .setDescription('Manage council votes')
    .addSubcommand(subcommand =>
      subcommand
        .setName('create')
        .setDescription('Create a new vote (President/Admin only)')
    )
    .addSubcommand(subcommand =>
      subcommand
        .setName('request')
        .setDescription('Request a vote to be created (sent to president for approval)')
    )
    .addSubcommand(subcommand =>
      subcommand
        .setName('status')
        .setDescription('Check status of active votes')
        .addStringOption(option =>
          option
            .setName('vote_id')
            .setDescription('Specific vote ID (optional)')
            .setRequired(false)
        )
    )
    .addSubcommand(subcommand =>
      subcommand
        .setName('verify')
        .setDescription('Verify vote integrity')
        .addStringOption(option =>
          option
            .setName('vote_id')
            .setDescription('Vote ID to verify')
            .setRequired(true)
        )
    )
    .addSubcommand(subcommand =>
      subcommand
        .setName('requests')
        .setDescription('View pending vote requests (President only)')
    ),

  async execute(interaction) {
    const subcommand = interaction.options.getSubcommand();

    try {
      switch (subcommand) {
        case 'create':
          await handleCreate(interaction);
          break;
        case 'request':
          await handleRequest(interaction);
          break;
        case 'status':
          await handleStatus(interaction);
          break;
        case 'verify':
          await handleVerify(interaction);
          break;
        case 'requests':
          await handleRequests(interaction);
          break;
        default:
          await interaction.reply({
            content: '❌ Unknown subcommand',
            ephemeral: true
          });
      }
    } catch (error) {
      console.error('[ERROR] Vote command error:', error);

      if (interaction.replied || interaction.deferred) {
        await interaction.followUp({
          content: `❌ Error: ${error.message}`,
          ephemeral: true
        });
      } else {
        await interaction.reply({
          content: `❌ Error: ${error.message}`,
          ephemeral: true
        });
      }
    }
  }
};

/**
 * Check if a Discord user has admin or president role
 * @param {string} discordId - Discord user ID
 * @returns {Promise<boolean>} - True if user has admin or president role
 */
async function isAdminOrPresident(discordId) {
  try {
    const adminUsers = await getAdminUsers();

    if (!adminUsers || !adminUsers.users) {
      console.warn('[WARN] No admin users found');
      return false;
    }

    const user = adminUsers.users.find(u => u.discord_id === discordId);

    if (!user) {
      return false;
    }

    // Check if user has admin or president role
    if (user.roles && Array.isArray(user.roles)) {
      return user.roles.includes('admin') || user.roles.includes('president');
    }

    return false;
  } catch (error) {
    console.error('[ERROR] Failed to check admin status:', error);
    return false;
  }
}

/**
 * Handle /vote create
 */
async function handleCreate(interaction) {
  // Check if user has permission to create votes
  const hasPermission = await isAdminOrPresident(interaction.user.id);

  if (!hasPermission) {
    return await interaction.reply({
      content: '❌ Only admins and the president can create votes directly.\n\n💡 Use `/vote request` to submit a vote request for approval.',
      ephemeral: true
    });
  }

  await interaction.reply({
    content: '✅ Check your DMs to create the vote!',
    ephemeral: true
  });

  try {
    const dm = await interaction.user.createDM();
    await initiateVoteCreation(interaction.user, dm, interaction.client);
  } catch (error) {
    console.error('[ERROR] Failed to send DM:', error);
    await interaction.followUp({
      content: '❌ I couldn\'t send you a DM. Please make sure your DMs are enabled for this server.',
      ephemeral: true
    });
  }
}

/**
 * Handle /vote status
 */
async function handleStatus(interaction) {
  const voteId = interaction.options.getString('vote_id');

  if (voteId) {
    // Show specific vote
    const vote = await getVote(voteId);

    if (!vote) {
      return await interaction.reply({
        content: `❌ Vote \`${voteId}\` not found`,
        ephemeral: true
      });
    }

    const submitted = vote.council_snapshot.voter_details.filter(v => v.vote_submitted).length;
    const total = vote.council_snapshot.voter_details.length;
    const endTime = new Date(vote.voting_period.end_time);
    const timeRemaining = endTime - new Date();
    const hoursRemaining = Math.max(0, Math.floor(timeRemaining / (1000 * 60 * 60)));
    const minutesRemaining = Math.max(0, Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60)));

    await interaction.reply({
      embeds: [{
        title: `📊 Vote Status: ${vote.vote_details.title}`,
        fields: [
          {
            name: 'Vote ID',
            value: vote.vote_id,
            inline: true
          },
          {
            name: 'Status',
            value: vote.status.toUpperCase(),
            inline: true
          },
          {
            name: 'Progress',
            value: `${submitted}/${total} votes submitted`,
            inline: true
          },
          {
            name: 'Time Remaining',
            value: vote.status === 'active'
              ? `${hoursRemaining}h ${minutesRemaining}m`
              : 'Completed',
            inline: true
          }
        ],
        color: vote.status === 'active' ? 0x667eea : 0x28a745,
        timestamp: new Date()
      }],
      ephemeral: true
    });
  } else {
    // Show all active votes
    const activeVotes = await getActiveVotes();

    if (activeVotes.length === 0) {
      return await interaction.reply({
        content: '📊 No active votes at this time',
        ephemeral: true
      });
    }

    const fields = activeVotes.map(vote => {
      const submitted = vote.council_snapshot.voter_details.filter(v => v.vote_submitted).length;
      const total = vote.council_snapshot.voter_details.length;

      return {
        name: vote.vote_details.title,
        value: `**ID:** ${vote.vote_id}\n**Progress:** ${submitted}/${total} votes\n**Created:** ${new Date(vote.created_at).toLocaleString()}`
      };
    });

    await interaction.reply({
      embeds: [{
        title: '📊 Active Votes',
        fields: fields,
        color: 0x667eea,
        timestamp: new Date()
      }],
      ephemeral: true
    });
  }
}

/**
 * Handle /vote verify
 */
async function handleVerify(interaction) {
  const voteId = interaction.options.getString('vote_id');
  const vote = await getVote(voteId);

  if (!vote) {
    return await interaction.reply({
      content: `❌ Vote \`${voteId}\` not found`,
      ephemeral: true
    });
  }

  const receipt = generateVerificationReceipt(vote);
  const verification = receipt.verification;

  await interaction.reply({
    embeds: [{
      title: '🔐 Vote Integrity Verification',
      description: verification.valid
        ? '✅ Vote integrity verified - no tampering detected'
        : `❌ Vote integrity compromised: ${verification.error}`,
      fields: [
        {
          name: 'Vote ID',
          value: vote.vote_id,
          inline: true
        },
        {
          name: 'Status',
          value: vote.status.toUpperCase(),
          inline: true
        },
        {
          name: 'Chain Length',
          value: receipt.chain_length.toString(),
          inline: true
        },
        {
          name: 'Vote Hash',
          value: `\`${receipt.vote_hash ? receipt.vote_hash.substring(0, 16) + '...' : 'N/A'}\``,
          inline: false
        },
        {
          name: 'Verification URL',
          value: receipt.verification_url,
          inline: false
        }
      ],
      color: verification.valid ? 0x28a745 : 0xdc3545,
      timestamp: new Date(receipt.verified_at)
    }],
    ephemeral: true
  });
}

/**
 * Initiate vote creation DM flow
 */
async function initiateVoteCreation(user, dm, client) {
  const collector = dm.createMessageCollector({
    filter: m => m.author.id === user.id,
    time: 600000 // 10 minutes
  });

  let voteData = {
    title: null,
    description: null,
    category: null
  };

  let step = 0;

  const steps = [
    {
      prompt: '**📝 Vote Creation - Step 1/3**\n\nWhat is the vote title? (Keep it concise, max 100 characters)',
      field: 'title',
      validate: (value) => value.length <= 100
    },
    {
      prompt: '**📝 Vote Creation - Step 2/3**\n\nProvide a detailed description or synopsis:',
      field: 'description'
    },
    {
      prompt: '**📝 Vote Creation - Step 3/3**\n\nSelect category:\n1️⃣ Rule Change\n2️⃣ Alliance Action\n3️⃣ Server Event\n4️⃣ Other\n\nReply with the number (1-4):',
      field: 'category',
      transform: (value) => {
        const categories = ['rule_change', 'alliance_action', 'server_event', 'other'];
        const index = parseInt(value) - 1;
        return categories[index];
      },
      validate: (value) => {
        const num = parseInt(value);
        return num >= 1 && num <= 4;
      }
    }
  ];

  // Send first prompt
  await dm.send(steps[step].prompt);

  collector.on('collect', async (message) => {
    const currentStep = steps[step];

    // Validate input
    if (currentStep.validate && !currentStep.validate(message.content)) {
      await dm.send(`❌ Invalid input. ${currentStep.prompt}`);
      return;
    }

    // Save response
    voteData[currentStep.field] = currentStep.transform
      ? currentStep.transform(message.content)
      : message.content;

    step++;

    if (step < steps.length) {
      // Next step
      await dm.send(steps[step].prompt);
    } else {
      // All data collected - create vote
      try {
        const vote = await createVote(voteData, user);

        await dm.send({
          embeds: [{
            title: '✅ Vote Created Successfully!',
            fields: [
              { name: 'Vote ID', value: vote.vote_id },
              { name: 'Title', value: vote.vote_details.title },
              { name: 'Duration', value: '24 hours' }
            ],
            description: 'Notifying council members now...',
            color: 0x28a745
          }]
        });

        // Post to vote channel and notify council
        await publishVote(vote, client);

        collector.stop('completed');
      } catch (error) {
        console.error('[ERROR] Failed to create vote:', error);
        await dm.send(`❌ Failed to create vote: ${error.message}\n\nPlease try again with \`/vote create\``);
        collector.stop('error');
      }
    }
  });

  collector.on('end', async (collected, reason) => {
    if (reason === 'time') {
      await dm.send('⏱️ Vote creation timed out. Please start over with `/vote create`.');
    }
  });
}

/**
 * Handle /vote request
 */
async function handleRequest(interaction) {
  await interaction.reply({
    content: '✅ Check your DMs to submit your vote request!',
    ephemeral: true
  });

  try {
    const dm = await interaction.user.createDM();
    await initiateVoteRequest(interaction.user, dm, interaction.client);
  } catch (error) {
    console.error('[ERROR] Failed to send DM:', error);
    await interaction.followUp({
      content: '❌ I couldn\'t send you a DM. Please make sure your DMs are enabled for this server.',
      ephemeral: true
    });
  }
}

/**
 * Initiate vote request DM flow
 */
async function initiateVoteRequest(user, dm, client) {
  const collector = dm.createMessageCollector({
    filter: m => m.author.id === user.id,
    time: 600000 // 10 minutes
  });

  let requestData = {
    title: null,
    description: null,
    category: null
  };

  let step = 0;

  const steps = [
    {
      prompt: '**📝 Vote Request - Step 1/3**\n\nWhat is the vote title? (Keep it concise, max 100 characters)',
      field: 'title',
      validate: (value) => value.length <= 100
    },
    {
      prompt: '**📝 Vote Request - Step 2/3**\n\nProvide a detailed description or synopsis:',
      field: 'description'
    },
    {
      prompt: '**📝 Vote Request - Step 3/3**\n\nSelect category:\n1️⃣ Rule Change\n2️⃣ Alliance Action\n3️⃣ Server Event\n4️⃣ Other\n\nReply with the number (1-4):',
      field: 'category',
      transform: (value) => {
        const categories = ['rule_change', 'alliance_action', 'server_event', 'other'];
        const index = parseInt(value) - 1;
        return categories[index];
      },
      validate: (value) => {
        const num = parseInt(value);
        return num >= 1 && num <= 4;
      }
    }
  ];

  // Send first prompt
  await dm.send(steps[step].prompt);

  collector.on('collect', async (message) => {
    const currentStep = steps[step];

    // Validate input
    if (currentStep.validate && !currentStep.validate(message.content)) {
      await dm.send(`❌ Invalid input. ${currentStep.prompt}`);
      return;
    }

    // Save response
    requestData[currentStep.field] = currentStep.transform
      ? currentStep.transform(message.content)
      : message.content;

    step++;

    if (step < steps.length) {
      // Next step
      await dm.send(steps[step].prompt);
    } else {
      // All data collected - create request
      try {
        const request = await createVoteRequest(requestData, user);

        await dm.send({
          embeds: [{
            title: '✅ Vote Request Submitted!',
            fields: [
              { name: 'Request ID', value: request.request_id },
              { name: 'Title', value: request.vote_details.title },
              { name: 'Status', value: 'Pending president approval' }
            ],
            description: 'Your vote request has been sent to the president for approval.\n\n**Auto-approval:** If the president doesn\'t respond within 12 hours, the vote will be automatically created.',
            color: 0x667eea,
            timestamp: new Date()
          }]
        });

        // Notify president
        await notifyPresidentOfRequest(request, client);

        collector.stop('completed');
      } catch (error) {
        console.error('[ERROR] Failed to create vote request:', error);
        await dm.send(`❌ Failed to submit vote request: ${error.message}\n\nPlease try again with \`/vote request\``);
        collector.stop('error');
      }
    }
  });

  collector.on('end', async (collected, reason) => {
    if (reason === 'time') {
      await dm.send('⏱️ Vote request timed out. Please start over with `/vote request`.');
    }
  });
}

/**
 * Notify president of new vote request
 */
async function notifyPresidentOfRequest(request, client) {
  const presidentId = await getPresidentDiscordId();

  if (!presidentId) {
    console.warn('[WARN] PRESIDENT_DISCORD_ID not configured, skipping notification');
    return;
  }

  try {
    const president = await client.users.fetch(presidentId);
    const dm = await president.createDM();

    await dm.send({
      embeds: [{
        title: '🗳️ New Vote Request',
        description: request.vote_details.description,
        fields: [
          {
            name: 'Request ID',
            value: request.request_id,
            inline: true
          },
          {
            name: 'Title',
            value: request.vote_details.title,
            inline: true
          },
          {
            name: 'Category',
            value: request.vote_details.category.replace('_', ' ').toUpperCase(),
            inline: true
          },
          {
            name: 'Requested By',
            value: `${request.requested_by.username} (${request.requested_by.tag})`,
            inline: false
          },
          {
            name: 'How to Approve/Reject',
            value: 'Reply to this DM with:\n```\napprove: ' + request.request_id + '\nreject: ' + request.request_id + ' [optional reason]\n```',
            inline: false
          },
          {
            name: '⚠️ Auto-Approval',
            value: 'This request will be **automatically approved** and the vote created if you don\'t respond within **12 hours**.',
            inline: false
          }
        ],
        color: 0xffc107,
        timestamp: new Date(request.created_at)
      }]
    });

    console.log(`[REQUEST] Notified president about request ${request.request_id}`);
  } catch (error) {
    console.error('[ERROR] Failed to notify president:', error);
  }
}

/**
 * Handle /vote requests (view pending)
 */
async function handleRequests(interaction) {
  // Check if user has permission to view requests
  const hasPermission = await isAdminOrPresident(interaction.user.id);

  if (!hasPermission) {
    return await interaction.reply({
      content: '❌ Only admins and the president can view pending vote requests.',
      ephemeral: true
    });
  }

  const pendingRequests = await getPendingRequests();

  if (pendingRequests.length === 0) {
    return await interaction.reply({
      content: '📋 No pending vote requests',
      ephemeral: true
    });
  }

  const fields = pendingRequests.map(req => {
    const age = Math.floor((Date.now() - new Date(req.created_at).getTime()) / (1000 * 60 * 60));
    const autoApproveIn = Math.max(0, 12 - age);

    return {
      name: req.vote_details.title,
      value: `**ID:** ${req.request_id}\n**By:** ${req.requested_by.username}\n**Age:** ${age}h ago\n**Auto-approve in:** ${autoApproveIn}h`
    };
  });

  await interaction.reply({
    embeds: [{
      title: '📋 Pending Vote Requests',
      description: `${pendingRequests.length} request(s) awaiting approval`,
      fields: fields,
      color: 0xffc107,
      timestamp: new Date()
    }],
    ephemeral: true
  });
}
