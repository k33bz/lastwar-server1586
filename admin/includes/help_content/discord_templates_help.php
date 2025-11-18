<?php
/**
 * Help Content: Discord Message Templates
 */

return [
    'title' => 'Discord Templates Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>Discord Templates allow you to create reusable message formats for common announcements and notifications. Templates support variable substitution, making it easy to send personalized messages without retyping content.</p>

                <div class="help-note">
                    <strong>Purpose:</strong> Save time by creating templates for recurring announcements like council rotations, server events, power updates, and more.
                </div>
            '
        ],
        [
            'title' => 'Understanding Templates',
            'content' => '
                <p>A template consists of:</p>

                <p><strong>Template Name:</strong> A descriptive identifier for the template (e.g., "Weekly Council Rotation", "Server Event Announcement")</p>

                <p><strong>Message Content:</strong> The text that will be sent to Discord, including any variables</p>

                <p><strong>Variables:</strong> Placeholders that get replaced with actual values when the message is sent</p>

                <p><strong>Common Use Cases:</strong></p>
                <ul>
                    <li>Weekly council rotation announcements</li>
                    <li>Server-wide event notifications</li>
                    <li>Vote result announcements</li>
                    <li>Power ranking updates</li>
                    <li>Rule reminders</li>
                    <li>Season transition messages</li>
                </ul>
            '
        ],
        [
            'title' => 'Using Variables',
            'content' => '
                <p>Variables are placeholders in your templates that get replaced with dynamic content. They use the format <code>{{variable_name}}</code>.</p>

                <p><strong>Common Variables:</strong></p>

                <p><code>{{week_number}}</code> - Current week number</p>
                <p><code>{{date}}</code> - Current date</p>
                <p><code>{{alliance_name}}</code> - Alliance name</p>
                <p><code>{{alliance_tag}}</code> - Alliance tag</p>
                <p><code>{{top5}}</code> - List of top 5 alliances</p>
                <p><code>{{rotating}}</code> - Rotating council members</p>
                <p><code>{{vote_title}}</code> - Vote proposal title</p>
                <p><code>{{vote_result}}</code> - Vote result (passed/failed)</p>

                <p><strong>Example Template:</strong></p>
                <pre>
📢 **Week {{week_number}} Council Rotation**

**Top 5 Permanent:**
{{top5}}

**Rotating Members:**
{{rotating}}

Council is now active for this week!
                </pre>

                <div class="help-success">
                    <strong>Tip:</strong> Variables are case-sensitive. Use the exact variable names shown in the variable reference.
                </div>
            '
        ],
        [
            'title' => 'Creating a Template',
            'content' => '
                <p><strong>Step-by-step process:</strong></p>
                <ol>
                    <li>Click <strong>"Add Template"</strong></li>
                    <li><strong>Template Name:</strong> Enter a descriptive name (e.g., "Council Rotation Announcement")</li>
                    <li><strong>Message Content:</strong> Write your message text
                        <ul>
                            <li>Use Discord markdown for formatting (bold with **text**, italic with *text*)</li>
                            <li>Include variables using {{variable_name}} syntax</li>
                            <li>Add emojis for visual appeal</li>
                        </ul>
                    </li>
                    <li><strong>Preview:</strong> Check the preview to see how it will look</li>
                    <li>Click <strong>"Save Template"</strong></li>
                </ol>

                <div class="help-note">
                    <strong>Formatting:</strong> Discord supports markdown formatting. Use **bold**, *italic*, __underline__, ~~strikethrough~~, and `code` formatting in your templates.
                </div>
            '
        ],
        [
            'title' => 'Discord Markdown Formatting',
            'content' => '
                <p>Enhance your templates with Discord markdown:</p>

                <p><strong>Text Formatting:</strong></p>
                <ul>
                    <li><code>**bold text**</code> - <strong>bold text</strong></li>
                    <li><code>*italic text*</code> - <em>italic text</em></li>
                    <li><code>__underline__</code> - underline</li>
                    <li><code>~~strikethrough~~</code> - strikethrough</li>
                    <li><code>`code`</code> - inline code</li>
                </ul>

                <p><strong>Headers and Lists:</strong></p>
                <ul>
                    <li><code># Heading</code> - Large heading</li>
                    <li><code>## Subheading</code> - Medium heading</li>
                    <li><code>- List item</code> - Bullet point</li>
                    <li><code>1. Item</code> - Numbered list</li>
                </ul>

                <p><strong>Links and Mentions:</strong></p>
                <ul>
                    <li><code>[Link text](URL)</code> - Clickable link</li>
                    <li><code>@everyone</code> - Mention everyone (use sparingly!)</li>
                    <li><code>@here</code> - Mention online users</li>
                    <li><code>&lt;@user_id&gt;</code> - Mention specific user</li>
                </ul>

                <div class="help-warning">
                    <strong>@everyone Usage:</strong> Use @everyone sparingly as it notifies all server members. Consider using @here for less intrusive notifications.
                </div>
            '
        ],
        [
            'title' => 'Using Templates',
            'content' => '
                <p>Once created, templates can be used in several ways:</p>

                <p><strong>Manual Sending:</strong></p>
                <ol>
                    <li>Go to <strong>Discord → Announcements</strong></li>
                    <li>Select a template from the dropdown</li>
                    <li>Fill in any variable values if prompted</li>
                    <li>Preview the message</li>
                    <li>Select target channel</li>
                    <li>Send</li>
                </ol>

                <p><strong>Scheduled Messages:</strong></p>
                <ol>
                    <li>Go to <strong>Discord → Scheduled</strong></li>
                    <li>Create a new scheduled message</li>
                    <li>Select a template</li>
                    <li>Set date/time</li>
                    <li>Messages will be sent automatically</li>
                </ol>

                <p><strong>Recurring Messages:</strong></p>
                <ol>
                    <li>Go to <strong>Discord → Recurring</strong></li>
                    <li>Set up recurring schedule (weekly, monthly, etc.)</li>
                    <li>Select a template</li>
                    <li>Messages repeat automatically</li>
                </ol>
            '
        ],
        [
            'title' => 'Editing Templates',
            'content' => '
                <p>To modify an existing template:</p>

                <ol>
                    <li>Find the template in the list</li>
                    <li>Click <strong>"Edit"</strong></li>
                    <li>Update the name or content</li>
                    <li>Click <strong>"Save Changes"</strong></li>
                </ol>

                <div class="help-warning">
                    <strong>Active Messages:</strong> If you edit a template that\'s being used in scheduled or recurring messages, those messages will use the updated version. Review scheduled messages after editing templates.
                </div>

                <p><strong>When to Edit vs Create New:</strong></p>
                <ul>
                    <li><strong>Edit:</strong> Minor wording changes, fixing typos, updating formatting</li>
                    <li><strong>Create New:</strong> Substantially different content, different use case</li>
                </ul>
            '
        ],
        [
            'title' => 'Template Examples',
            'content' => '
                <p><strong>Council Rotation Template:</strong></p>
                <pre>
📊 **Week {{week_number}} Council Update**

**Permanent Members (Top 5):**
{{top5}}

**This Week\'s Rotating Members:**
{{rotating}}

Good luck to all council members this week!
                </pre>

                <p><strong>Server Event Template:</strong></p>
                <pre>
🎮 **Server Event Alert!**

**Event:** {{event_name}}
**Time:** {{event_time}}
**Duration:** {{duration}}

**Rewards:**
{{rewards}}

See you there! 🏆
                </pre>

                <p><strong>Vote Result Template:</strong></p>
                <pre>
📜 **Vote Results - {{vote_title}}**

**Result:** {{vote_result}}

**Vote Breakdown:**
{{vote_breakdown}}

Thank you to all council members who participated!
                </pre>

                <p><strong>Power Update Reminder:</strong></p>
                <pre>
⚡ **Weekly Power Update Reminder**

R5s and APEs: Please update alliance power values by {{deadline}}.

Use the Power Updates page in the admin panel.

Questions? Contact an admin.
                </pre>
            '
        ],
        [
            'title' => 'Best Practices',
            'content' => '
                <p><strong>Template Organization:</strong></p>
                <ul>
                    <li>Use clear, descriptive names</li>
                    <li>Group related templates (prefix with category like "Event - ", "Council - ")</li>
                    <li>Keep templates focused on one purpose</li>
                    <li>Remove unused templates periodically</li>
                </ul>

                <p><strong>Content Guidelines:</strong></p>
                <ul>
                    <li>Keep messages concise and scannable</li>
                    <li>Use formatting to highlight important info</li>
                    <li>Include relevant emojis for visual appeal</li>
                    <li>Proofread before saving</li>
                </ul>

                <p><strong>Variable Usage:</strong></p>
                <ul>
                    <li>Document which variables each template uses</li>
                    <li>Test templates with sample data before using in production</li>
                    <li>Ensure all variables will have values when message is sent</li>
                </ul>

                <div class="help-success">
                    <strong>Tip:</strong> Create a "Test" template to experiment with formatting and variables before creating production templates.
                </div>
            '
        ],
        [
            'title' => 'Troubleshooting',
            'content' => '
                <p><strong>Variables not replacing:</strong></p>
                <ul>
                    <li>Check variable name spelling and case</li>
                    <li>Ensure double curly braces: {{variable}} not {variable}</li>
                    <li>Verify the variable is supported in this context</li>
                </ul>

                <p><strong>Formatting not displaying correctly:</strong></p>
                <ul>
                    <li>Discord markdown requires specific syntax - check formatting guide</li>
                    <li>Preview message before sending</li>
                    <li>Some formatting may not work in all Discord clients</li>
                </ul>

                <p><strong>Can\'t save template:</strong></p>
                <ul>
                    <li>Ensure template name is unique</li>
                    <li>Check that message content isn\'t empty</li>
                    <li>Very long messages may need to be shortened (Discord limit: 2000 characters)</li>
                </ul>

                <p><strong>Template deleted accidentally:</strong></p>
                <ul>
                    <li>Templates cannot be recovered once deleted</li>
                    <li>Recreate from scratch or from scheduled/recurring messages that used it</li>
                    <li>Consider backing up important templates by copying content</li>
                </ul>
            '
        ]
    ]
];
