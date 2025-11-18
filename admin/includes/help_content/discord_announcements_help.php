<?php
/**
 * Help Content: Discord Announcements
 */

return [
    'title' => 'Discord Announcements Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>Discord Announcements allows you to send messages from the admin panel directly to your Discord server channels. You can send one-time messages, use saved templates, or compose custom content.</p>

                <div class="help-note">
                    <strong>Purpose:</strong> Quickly communicate important information to your server without leaving the admin panel or manually typing in Discord.
                </div>
            '
        ],
        [
            'title' => 'Sending an Announcement',
            'content' => '
                <p><strong>Step-by-step process:</strong></p>
                <ol>
                    <li><strong>Select Channel:</strong> Choose which Discord channel to send to
                        <ul>
                            <li>Channels must be configured in Discord → Channels first</li>
                            <li>Common channels: Announcements, General, Council</li>
                        </ul>
                    </li>
                    <li><strong>Compose Message:</strong> Choose one of three methods:
                        <ul>
                            <li><strong>Use Template:</strong> Select from saved templates</li>
                            <li><strong>Quick Message:</strong> Type a simple text message</li>
                            <li><strong>Custom Message:</strong> Write formatted content with variables</li>
                        </ul>
                    </li>
                    <li><strong>Preview:</strong> Review how the message will appear in Discord</li>
                    <li><strong>Send:</strong> Click "Send Announcement" to post immediately</li>
                </ol>

                <div class="help-warning">
                    <strong>Immediate Sending:</strong> Announcements are sent immediately when you click Send. There is no undo function, so review carefully before sending.
                </div>
            '
        ],
        [
            'title' => 'Using Templates',
            'content' => '
                <p>Templates provide pre-formatted messages for common announcements:</p>

                <p><strong>Benefits of Templates:</strong></p>
                <ul>
                    <li>Consistent formatting across announcements</li>
                    <li>Save time - no need to retype recurring messages</li>
                    <li>Variables auto-populate with current data</li>
                    <li>Professional, polished appearance</li>
                </ul>

                <p><strong>How to Use:</strong></p>
                <ol>
                    <li>Select "Use Template" option</li>
                    <li>Choose template from dropdown</li>
                    <li>Fill in any required variable values if prompted</li>
                    <li>Preview the result</li>
                    <li>Send</li>
                </ol>

                <div class="help-note">
                    <strong>Creating Templates:</strong> Manage templates in Discord → Templates. Create reusable formats for council rotations, events, power updates, etc.
                </div>
            '
        ],
        [
            'title' => 'Formatting Messages',
            'content' => '
                <p>Discord supports markdown formatting to make messages more readable:</p>

                <p><strong>Text Styles:</strong></p>
                <ul>
                    <li><code>**bold**</code> - Makes text <strong>bold</strong></li>
                    <li><code>*italic*</code> - Makes text <em>italic</em></li>
                    <li><code>__underline__</code> - Underlines text</li>
                    <li><code>~~strikethrough~~</code> - Strikes through text</li>
                </ul>

                <p><strong>Structure:</strong></p>
                <ul>
                    <li><code># Heading</code> - Large heading</li>
                    <li><code>## Subheading</code> - Medium heading</li>
                    <li><code>- Item</code> - Bullet point</li>
                    <li><code>1. Item</code> - Numbered list</li>
                </ul>

                <p><strong>Other Formatting:</strong></p>
                <ul>
                    <li><code>`code`</code> - Inline code formatting</li>
                    <li><code>```code block```</code> - Multi-line code block</li>
                    <li><code>[Link](URL)</code> - Clickable hyperlink</li>
                </ul>

                <p><strong>Example Formatted Message:</strong></p>
                <pre>
📢 **Important Server Update**

We\'re implementing new rules for council rotations:

- Top 5 alliances are permanent members
- 2 rotating slots for ranks 6-15
- Rotation changes every **Monday**

Questions? Contact an admin!
                </pre>
            '
        ],
        [
            'title' => 'Channel Selection',
            'content' => '
                <p>Choose the appropriate channel based on your message type:</p>

                <p><strong>Announcements Channel:</strong></p>
                <ul>
                    <li>Server-wide important news</li>
                    <li>Rule changes</li>
                    <li>Event notifications</li>
                    <li>Season transitions</li>
                </ul>

                <p><strong>Council Channel:</strong></p>
                <ul>
                    <li>Council rotation updates</li>
                    <li>Vote announcements</li>
                    <li>Council-specific information</li>
                    <li>NAP15 notifications</li>
                </ul>

                <p><strong>General Channel:</strong></p>
                <ul>
                    <li>Casual updates</li>
                    <li>Non-critical reminders</li>
                    <li>Community engagement</li>
                </ul>

                <div class="help-note">
                    <strong>Channel Configuration:</strong> If a channel isn\'t listed, configure it first in Discord → Channels with the proper channel ID and type.
                </div>
            '
        ],
        [
            'title' => 'Mentions and Notifications',
            'content' => '
                <p>You can mention users or roles to send notifications:</p>

                <p><strong>@everyone:</strong> Notifies ALL server members</p>
                <ul>
                    <li>Use only for critical announcements</li>
                    <li>Server events, emergencies, major updates</li>
                    <li>Can be disruptive - use sparingly</li>
                </ul>

                <p><strong>@here:</strong> Notifies only ONLINE members</p>
                <ul>
                    <li>Less intrusive than @everyone</li>
                    <li>Good for time-sensitive info</li>
                    <li>Use for events starting soon</li>
                </ul>

                <p><strong>Role Mentions:</strong> <code>@RoleName</code></p>
                <ul>
                    <li>Notify specific groups (e.g., @Council, @R5)</li>
                    <li>Must have role mention permissions enabled</li>
                    <li>More targeted than @everyone</li>
                </ul>

                <div class="help-warning">
                    <strong>Notification Etiquette:</strong> Excessive use of @everyone can annoy members. Consider whether a mention is truly necessary or if a regular message suffices.
                </div>
            '
        ],
        [
            'title' => 'Message Preview',
            'content' => '
                <p>Always preview messages before sending:</p>

                <p><strong>What to Check:</strong></p>
                <ul>
                    <li><strong>Formatting:</strong> Verify bold, italic, lists display correctly</li>
                    <li><strong>Variables:</strong> Ensure all {{variables}} were replaced with actual values</li>
                    <li><strong>Spelling:</strong> Check for typos and grammar</li>
                    <li><strong>Links:</strong> Verify URLs are correct and clickable</li>
                    <li><strong>Emojis:</strong> Confirm emojis render properly</li>
                    <li><strong>Length:</strong> Ensure message fits within Discord\'s 2000 character limit</li>
                </ul>

                <div class="help-success">
                    <strong>Tip:</strong> Send test messages to a private test channel first to verify formatting before posting to public channels.
                </div>
            '
        ],
        [
            'title' => 'Common Announcement Types',
            'content' => '
                <p><strong>Weekly Council Rotation:</strong></p>
                <pre>
📊 **Week 23 Council Rotation**

**Top 5:** UvvU, ORCE, MTOP, FNXS, MZKU
**Rotating:** bfp8, UUSN

Council is now active!
                </pre>

                <p><strong>Server Event:</strong></p>
                <pre>
🎮 **Event Alert: Server Boss Battle**

**When:** Saturday 7PM Server Time
**Duration:** 2 hours
**Rewards:** Premium currency + gear

@everyone Get ready!
                </pre>

                <p><strong>Rule Update:</strong></p>
                <pre>
📜 **Server Rules Updated**

We\'ve updated the NAP15 participation rules:
- All NAP15 alliances rotate on council
- 2 rotating slots per week
- Fair distribution over time

Questions? Contact an admin.
                </pre>

                <p><strong>Power Update Reminder:</strong></p>
                <pre>
⚡ **Power Update Reminder**

R5s and APEs: Please update your alliance power by Sunday evening.

Use the admin panel → Alliances → Power Updates

Current week: Week 23
                </pre>
            '
        ],
        [
            'title' => 'Best Practices',
            'content' => '
                <p><strong>Timing:</strong></p>
                <ul>
                    <li>Send important announcements during peak server activity times</li>
                    <li>Avoid sending multiple announcements in quick succession</li>
                    <li>Schedule recurring announcements (use Discord → Scheduled instead)</li>
                </ul>

                <p><strong>Content:</strong></p>
                <ul>
                    <li>Keep messages concise and scannable</li>
                    <li>Use emojis to make messages visually appealing</li>
                    <li>Include relevant dates/times in announcements</li>
                    <li>Proofread before sending</li>
                </ul>

                <p><strong>Frequency:</strong></p>
                <ul>
                    <li>Don\'t spam the announcement channel</li>
                    <li>Combine related updates into one message when possible</li>
                    <li>Use threads for follow-up discussions</li>
                </ul>

                <div class="help-note">
                    <strong>Engagement:</strong> Encourage responses by asking questions or including calls-to-action in your announcements.
                </div>
            '
        ],
        [
            'title' => 'Troubleshooting',
            'content' => '
                <p><strong>Message not appearing in Discord:</strong></p>
                <ul>
                    <li>Verify the channel is configured correctly</li>
                    <li>Check bot permissions for that channel</li>
                    <li>Ensure bot has "Send Messages" permission</li>
                    <li>Try sending to a different channel to isolate the issue</li>
                </ul>

                <p><strong>Formatting not working:</strong></p>
                <ul>
                    <li>Check markdown syntax (proper use of *, **, etc.)</li>
                    <li>Ensure no typos in formatting codes</li>
                    <li>Some Discord clients may display formatting differently</li>
                </ul>

                <p><strong>Variables showing as {{text}}:</strong></p>
                <ul>
                    <li>Template variables only work when using template system</li>
                    <li>Custom messages don\'t auto-replace variables</li>
                    <li>Use templates feature for variable substitution</li>
                </ul>

                <p><strong>"Permission denied" error:</strong></p>
                <ul>
                    <li>Bot may not have permission to post in selected channel</li>
                    <li>Check Discord channel permissions for the bot role</li>
                    <li>Verify bot is still in the server</li>
                </ul>

                <p><strong>Message truncated or cut off:</strong></p>
                <ul>
                    <li>Discord has 2000 character limit per message</li>
                    <li>Shorten message or split into multiple messages</li>
                    <li>Remove unnecessary content or formatting</li>
                </ul>
            '
        ]
    ]
];
