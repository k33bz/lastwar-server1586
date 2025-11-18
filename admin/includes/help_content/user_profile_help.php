<?php
/**
 * Help Content: User Profile
 */

return [
    'title' => 'User Profile Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>Your User Profile shows your account information, assigned roles, alliance assignments, and recent activity.</p>

                <div class="help-note">
                    <strong>Purpose:</strong> View your permissions, check which alliances you can manage, and review your account status.
                </div>
            '
        ],
        [
            'title' => 'Account Information',
            'content' => '
                <p><strong>Email Address:</strong> Your login identifier - cannot be changed</p>

                <p><strong>Assigned Roles:</strong> Determines your permissions</p>
                <ul>
                    <li>Admin - Full system access</li>
                    <li>President - Council governance</li>
                    <li>APE - Alliance power editing</li>
                    <li>R5 - Alliance leadership</li>
                    <li>R4 - Alliance officer</li>
                </ul>

                <p><strong>Alliance Assignments:</strong> Alliances you have permission to manage</p>
                <ul>
                    <li>View which alliances you can edit</li>
                    <li>Check power editing permissions</li>
                </ul>
            '
        ],
        [
            'title' => 'Session Management',
            'content' => '
                <p><strong>Active Session:</strong></p>
                <ul>
                    <li>Current login expiry time</li>
                    <li>Sessions last 8 hours</li>
                    <li>Auto-logout after expiration</li>
                </ul>

                <p><strong>Security:</strong></p>
                <ul>
                    <li>Always logout on shared computers</li>
                    <li>Don\'t share magic link tokens</li>
                    <li>Report suspicious activity</li>
                </ul>

                <div class="help-warning">
                    <strong>Session Expiry:</strong> Your session will automatically expire after 8 hours of inactivity. You\'ll be redirected to login.
                </div>
            '
        ],
        [
            'title' => 'Requesting Permission Changes',
            'content' => '
                <p>If you need additional roles or alliance access:</p>

                <ol>
                    <li>Contact a server admin via Discord</li>
                    <li>Explain which permissions you need</li>
                    <li>Admin will update your account in User Management</li>
                    <li>Logout and login again to see new permissions</li>
                </ol>

                <div class="help-note">
                    <strong>Permission Updates:</strong> After an admin changes your roles, you may need to logout and login again for changes to take effect.
                </div>
            '
        ]
    ]
];
