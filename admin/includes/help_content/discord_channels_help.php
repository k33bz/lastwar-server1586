<?php
/**
 * Help Content: Discord Channels Configuration
 */

return [
    'title' => 'Discord Channels Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>Discord Channels configuration maps your Discord server\'s channels to specific functions in the admin system. This allows automated messages, announcements, and bot interactions to be sent to the correct channels.</p>

                <div class="help-note">
                    <strong>Purpose:</strong> By configuring channel mappings, you enable the admin panel and Discord bot to automatically post messages to appropriate channels (announcements, council notifications, vote results, etc.).
                </div>
            '
        ],
        [
            'title' => 'Getting Discord Channel IDs',
            'content' => '
                <p>To configure channels, you need their Discord Channel IDs. Here\'s how to find them:</p>

                <p><strong>Enable Developer Mode in Discord:</strong></p>
                <ol>
                    <li>Open Discord and go to <strong>User Settings</strong> (gear icon)</li>
                    <li>Navigate to <strong>App Settings → Advanced</strong></li>
                    <li>Enable <strong>Developer Mode</strong></li>
                </ol>

                <p><strong>Copy Channel IDs:</strong></p>
                <ol>
                    <li>Right-click on any channel in your Discord server</li>
                    <li>Select <strong>"Copy Channel ID"</strong> from the menu</li>
                    <li>The channel ID is now in your clipboard (format: 1234567890123456789)</li>
                </ol>

                <div class="help-success">
                    <strong>Tip:</strong> Channel IDs are long numbers (usually 18-19 digits). Keep a notepad open while configuring to organize your channel IDs before entering them.
                </div>
            '
        ],
        [
            'title' => 'Common Channel Types',
            'content' => '
                <p>Typical channel mappings for a Last War server:</p>

                <p><strong>Announcements Channel:</strong></p>
                <ul>
                    <li>General server announcements</li>
                    <li>Council updates</li>
                    <li>System notifications</li>
                </ul>

                <p><strong>Council Channel:</strong></p>
                <ul>
                    <li>Council member communications</li>
                    <li>Vote announcements</li>
                    <li>Vote results</li>
                </ul>

                <p><strong>Bot Commands Channel:</strong></p>
                <ul>
                    <li>Where users can interact with bot commands</li>
                    <li>Vote submission responses</li>
                    <li>Bot status messages</li>
                </ul>

                <p><strong>Logs Channel:</strong></p>
                <ul>
                    <li>Automated system logs</li>
                    <li>Bot activity tracking</li>
                    <li>Admin action notifications</li>
                </ul>

                <div class="help-note">
                    <strong>Flexible Setup:</strong> You can map multiple functions to the same channel, or use separate channels for each function. Configure based on your server\'s needs.
                </div>
            '
        ],
        [
            'title' => 'Adding a Channel Mapping',
            'content' => '
                <p><strong>Step-by-step process:</strong></p>
                <ol>
                    <li>Click <strong>"Add Channel"</strong> button</li>
                    <li><strong>Channel Name:</strong> Enter a descriptive name (e.g., "Council Announcements", "Server Logs")</li>
                    <li><strong>Channel ID:</strong> Paste the Discord Channel ID you copied</li>
                    <li><strong>Channel Type:</strong> Select the function this channel serves:
                        <ul>
                            <li><strong>announcements</strong> - General server announcements</li>
                            <li><strong>council</strong> - Council-specific messages</li>
                            <li><strong>votes</strong> - Vote announcements and results</li>
                            <li><strong>logs</strong> - System and bot logs</li>
                            <li><strong>general</strong> - Catch-all for other purposes</li>
                        </ul>
                    </li>
                    <li><strong>Enabled:</strong> Check this box to activate the channel mapping</li>
                    <li>Click <strong>"Save"</strong></li>
                </ol>

                <div class="help-warning">
                    <strong>Verify Channel IDs:</strong> Incorrect channel IDs will cause messages to fail. Double-check by pasting the ID back into Discord (use search: the channel should appear).
                </div>
            '
        ],
        [
            'title' => 'Editing Channel Mappings',
            'content' => '
                <p>To modify an existing channel mapping:</p>

                <ol>
                    <li>Find the channel in the list</li>
                    <li>Click <strong>"Edit"</strong></li>
                    <li>Update the name, ID, type, or enabled status</li>
                    <li>Click <strong>"Save Changes"</strong></li>
                </ol>

                <p><strong>Common Edits:</strong></p>
                <ul>
                    <li><strong>Channel Reorganization:</strong> When Discord channels are moved or renamed, update the mapping name</li>
                    <li><strong>Channel ID Changed:</strong> If a channel is deleted and recreated, update the ID</li>
                    <li><strong>Temporarily Disable:</strong> Uncheck "Enabled" to stop messages without deleting the mapping</li>
                </ul>

                <div class="help-note">
                    <strong>Name vs ID:</strong> The "Channel Name" field is just for your reference in the admin panel. The actual Discord channel name doesn\'t matter - only the Channel ID determines where messages are sent.
                </div>
            '
        ],
        [
            'title' => 'Testing Channel Configuration',
            'content' => '
                <p>After configuring channels, test them to ensure messages are sent correctly:</p>

                <p><strong>Test Announcement Channel:</strong></p>
                <ol>
                    <li>Go to <strong>Discord → Announcements</strong> in the admin menu</li>
                    <li>Create a test announcement</li>
                    <li>Send it to the configured announcement channel</li>
                    <li>Verify it appears in Discord</li>
                </ol>

                <p><strong>Test Vote Channel:</strong></p>
                <ol>
                    <li>Create a test council vote (or have the bot create one)</li>
                    <li>Verify the vote announcement appears in the council/votes channel</li>
                </ol>

                <p><strong>Test Bot Commands:</strong></p>
                <ol>
                    <li>Use a bot command in the designated bot channel</li>
                    <li>Verify the bot responds in the correct channel</li>
                </ol>

                <div class="help-success">
                    <strong>Tip:</strong> Create a dedicated "testing" channel in Discord for configuration testing. Once everything works, update the mappings to production channels.
                </div>
            '
        ],
        [
            'title' => 'Bot Permissions Required',
            'content' => '
                <p>For the bot to post messages to configured channels, it needs proper Discord permissions:</p>

                <p><strong>Required Permissions:</strong></p>
                <ul>
                    <li><strong>View Channels</strong> - Bot must see the channels</li>
                    <li><strong>Send Messages</strong> - Post text messages</li>
                    <li><strong>Embed Links</strong> - Send rich embed messages (for formatted announcements)</li>
                    <li><strong>Read Message History</strong> - For certain bot features</li>
                </ul>

                <p><strong>How to Grant Permissions:</strong></p>
                <ol>
                    <li>In Discord, go to <strong>Server Settings → Roles</strong></li>
                    <li>Find your bot\'s role</li>
                    <li>Enable the required permissions</li>
                    <li>Or configure per-channel permissions in <strong>Channel Settings → Permissions</strong></li>
                </ol>

                <div class="help-warning">
                    <strong>Permission Issues:</strong> If the bot cannot post messages, you\'ll see errors in the logs. Check that the bot has the necessary permissions in each configured channel.
                </div>
            '
        ],
        [
            'title' => 'Best Practices',
            'content' => '
                <p><strong>Organization:</strong></p>
                <ul>
                    <li>Use clear, descriptive names for channel mappings</li>
                    <li>Keep a backup list of channel IDs in case mappings are accidentally deleted</li>
                    <li>Document which channel types are used for what purposes</li>
                </ul>

                <p><strong>Channel Separation:</strong></p>
                <ul>
                    <li>Consider separate channels for announcements vs council business</li>
                    <li>Use a dedicated bot-commands channel to reduce spam in general chat</li>
                    <li>Keep logs in an admin-only channel for security</li>
                </ul>

                <p><strong>Maintenance:</strong></p>
                <ul>
                    <li>Review channel mappings when Discord server structure changes</li>
                    <li>Remove mappings for deleted Discord channels</li>
                    <li>Test after any Discord permissions changes</li>
                </ul>

                <div class="help-note">
                    <strong>Multiple Servers:</strong> If you run multiple Discord servers, you\'ll need separate channel mappings for each. The admin panel can manage multiple configurations.
                </div>
            '
        ],
        [
            'title' => 'Troubleshooting',
            'content' => '
                <p><strong>Messages not appearing in Discord:</strong></p>
                <ul>
                    <li>Verify the channel ID is correct</li>
                    <li>Check that the channel is "Enabled" in the mapping</li>
                    <li>Ensure the bot has permissions to post in that channel</li>
                    <li>Check Discord server-wide permissions for the bot role</li>
                </ul>

                <p><strong>Wrong channel receiving messages:</strong></p>
                <ul>
                    <li>Check the channel type mapping (announcements vs council vs votes)</li>
                    <li>Verify the channel ID matches the intended Discord channel</li>
                    <li>Ensure there are no duplicate channel type mappings</li>
                </ul>

                <p><strong>"Invalid Channel ID" error:</strong></p>
                <ul>
                    <li>Channel IDs must be numeric (18-19 digits)</li>
                    <li>Ensure you copied the Channel ID, not the Message ID or User ID</li>
                    <li>Verify the channel exists and wasn\'t deleted</li>
                </ul>

                <p><strong>Bot permission errors:</strong></p>
                <ul>
                    <li>Check bot role permissions in Server Settings</li>
                    <li>Check channel-specific permission overrides</li>
                    <li>Verify the bot is actually in your Discord server</li>
                    <li>Re-invite the bot with proper permissions if needed</li>
                </ul>
            '
        ]
    ]
];
