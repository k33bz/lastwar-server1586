<?php
/**
 * Help Content: Admin Dashboard
 */

$is_admin = has_role($user, 'admin');
$is_president = has_role($user, 'president');

return [
    'title' => 'Dashboard Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>The Dashboard is your central hub for managing the Last War 1586 server. From here, you can access all administration tools and monitor server activity.</p>

                ' . ($is_admin ? '
                <div class="help-note">
                    <strong>Admin Role:</strong> You have full access to all features including user management, security settings, and system configuration.
                </div>
                ' : '') . '

                ' . ($is_president ? '
                <div class="help-note">
                    <strong>President Role:</strong> You have access to council governance features including vote approvals and council rotation management.
                </div>
                ' : '') . '
            '
        ],
        [
            'title' => 'Quick Actions',
            'content' => '
                <p>The dashboard provides quick access to commonly used features:</p>

                <p><strong>Alliance Management:</strong></p>
                <ul>
                    <li><strong>Edit Alliances:</strong> Update alliance information, R5/R4 officers, and member counts</li>
                    <li><strong>Power Updates:</strong> Quick interface for updating alliance power values</li>
                    <li><strong>Alliance Tags:</strong> Manage alliance tag assignments and visibility</li>
                </ul>

                <p><strong>Discord Integration:</strong></p>
                <ul>
                    <li><strong>Channels:</strong> Configure Discord channel mappings</li>
                    <li><strong>Templates:</strong> Manage message templates for announcements</li>
                    <li><strong>Scheduled Messages:</strong> Set up recurring or one-time Discord posts</li>
                </ul>

                ' . ($is_admin || $is_president ? '
                <p><strong>Governance:</strong></p>
                <ul>
                    <li><strong>Council Rotation:</strong> Manage the weekly council rotation schedule</li>
                    <li><strong>Vote Management:</strong> Create and manage council votes</li>
                    ' . ($is_president ? '<li><strong>Approve Vote Proposals:</strong> Review and approve vote requests from council members</li>' : '') . '
                </ul>
                ' : '') . '
            '
        ],
        [
            'title' => 'Navigation Menu',
            'content' => '
                <p>The left sidebar provides organized access to all admin features:</p>

                <p><strong>Home:</strong> Return to the dashboard from any page</p>

                <p><strong>Alliances:</strong></p>
                <ul>
                    <li>Edit Alliances - Manage alliance details</li>
                    <li>Power Updates - Update alliance power values</li>
                    <li>Alliance Tags - Configure tag assignments</li>
                </ul>

                <p><strong>Governance:</strong></p>
                <ul>
                    <li>Council Rotation - Manage weekly rotation schedule</li>
                    <li>Vote Proposals - Submit new vote proposals (council members)</li>
                    ' . ($is_president ? '<li>Approve Votes - Review pending vote proposals (president only)</li>' : '') . '
                    <li>Manage Votes - View all votes and results</li>
                </ul>

                <p><strong>Discord:</strong></p>
                <ul>
                    <li>Channels - Configure channel mappings</li>
                    <li>Templates - Message template management</li>
                    <li>Scheduled - Set up scheduled messages</li>
                    <li>Announcements - Send server announcements</li>
                </ul>

                ' . ($is_admin ? '
                <p><strong>Admin (Admin Only):</strong></p>
                <ul>
                    <li>User Management - Manage admin users and permissions</li>
                    <li>Security Audit - Review security logs</li>
                    <li>Backups - System backup management</li>
                </ul>
                ' : '') . '
            '
        ],
        [
            'title' => 'User Profile & Settings',
            'content' => '
                <p>Access your profile and settings from the top-right corner:</p>

                <p><strong>Theme Toggle:</strong> Switch between light and dark mode</p>

                <p><strong>Language Switcher:</strong> Change the interface language (English, Spanish, Portuguese, German, Korean)</p>

                <p><strong>Profile:</strong> View and manage your user profile</p>

                <p><strong>Logout:</strong> Securely end your session</p>

                <div class="help-note">
                    <strong>Security:</strong> Your session will automatically expire after 8 hours of inactivity. Always logout when finished using shared computers.
                </div>
            '
        ],
        [
            'title' => 'Role-Based Access',
            'content' => '
                <p>Your available features depend on your assigned roles:</p>

                <p><strong>Admin:</strong> Full system access including user management, security settings, and all features</p>

                <p><strong>President:</strong> Council governance features including vote approvals and rotation management</p>

                <p><strong>APE (Alliance Power Editor):</strong> Can update power for ALL alliances on the server</p>

                <p><strong>R5:</strong> Alliance leadership - can edit assigned alliance details and power</p>

                <p><strong>R4:</strong> Alliance officers - limited editing access to assigned alliances</p>

                <p><strong>Council Member (R5/R4/APE):</strong> Can submit vote proposals for council consideration</p>

                <div class="help-warning">
                    <strong>Contact Admin:</strong> If you need additional roles or permissions, contact a server admin via Discord.
                </div>
            '
        ],
        [
            'title' => 'Help & Support',
            'content' => '
                <p>Most admin pages include a help drawer (accessible via the "?" button) with page-specific guidance.</p>

                <p><strong>Getting Help:</strong></p>
                <ul>
                    <li>Click the "?" button on any page for context-specific help</li>
                    <li>Check the changelog for recent updates and changes</li>
                    <li>Contact admins via Discord for technical support</li>
                </ul>

                <div class="help-success">
                    <strong>Tip:</strong> If you\'re unsure how a feature works, check the help drawer first - it contains detailed instructions and examples.
                </div>
            '
        ],
        [
            'title' => 'Security Best Practices',
            'content' => '
                <p><strong>Account Security:</strong></p>
                <ul>
                    <li>Never share your magic link login tokens</li>
                    <li>Always logout when finished on shared computers</li>
                    <li>Report suspicious activity to admins immediately</li>
                    <li>Use the magic link feature for secure, password-free authentication</li>
                </ul>

                <p><strong>Data Safety:</strong></p>
                <ul>
                    <li>All changes are logged in the audit trail</li>
                    <li>Double-check data before saving (especially power values and alliance info)</li>
                    <li>Contact an admin if you make a mistake - changes can be reviewed and corrected</li>
                </ul>

                ' . ($is_admin ? '
                <div class="help-warning">
                    <strong>Admin Responsibility:</strong> With admin access comes the responsibility to protect user data and maintain system security. Review security audit logs regularly.
                </div>
                ' : '') . '
            '
        ]
    ]
];
