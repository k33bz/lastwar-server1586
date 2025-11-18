<?php
/**
 * Help Content: User Management
 * Admin-only feature
 */

return [
    'title' => 'User Management Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>User Management allows administrators to control access to the admin panel by managing user accounts and their assigned roles.</p>

                <div class="help-warning">
                    <strong>Admin Only:</strong> This feature is restricted to users with the Admin role. All changes are logged in the security audit trail.
                </div>

                <p>Users authenticate via magic link emails - there are no passwords to manage. Each user\'s email address is their unique identifier.</p>
            '
        ],
        [
            'title' => 'Understanding Roles',
            'content' => '
                <p>Roles determine what features and permissions a user has access to. Users can have multiple roles simultaneously.</p>

                <p><strong>Available Roles:</strong></p>

                <p><strong>admin</strong> - Full system access</p>
                <ul>
                    <li>Manage all users and permissions</li>
                    <li>Access security settings and audit logs</li>
                    <li>Configure system-wide settings</li>
                    <li>Backup and restore functionality</li>
                </ul>

                <p><strong>president</strong> - Council governance leader</p>
                <ul>
                    <li>Approve or reject vote proposals from council members</li>
                    <li>Manage council rotation schedule</li>
                    <li>Create council votes directly (bypassing approval)</li>
                    <li>Full access to governance features</li>
                </ul>

                <p><strong>ape</strong> - Alliance Power Editor</p>
                <ul>
                    <li>Update power values for ANY alliance on the server</li>
                    <li>Quick power update interface</li>
                    <li>Typically assigned to trusted members who maintain alliance data</li>
                </ul>

                <p><strong>r5</strong> - Alliance Leader</p>
                <ul>
                    <li>Edit details for assigned alliance(s)</li>
                    <li>Update alliance power</li>
                    <li>Manage R4 officers for their alliance</li>
                    <li>Submit council vote proposals</li>
                </ul>

                <p><strong>r4</strong> - Alliance Officer</p>
                <ul>
                    <li>Limited editing access to assigned alliance(s)</li>
                    <li>Can update some alliance information</li>
                    <li>Submit council vote proposals</li>
                </ul>

                <div class="help-note">
                    <strong>Multiple Roles:</strong> A user can have multiple roles. For example, a user might be both an R5 and an APE, giving them alliance leadership plus server-wide power editing.
                </div>
            '
        ],
        [
            'title' => 'Adding a New User',
            'content' => '
                <p><strong>Step-by-step process:</strong></p>
                <ol>
                    <li>Click the <strong>"Add New User"</strong> button</li>
                    <li><strong>Enter the email address</strong> - This will be used for magic link authentication</li>
                    <li><strong>Select role(s)</strong> - Check all applicable roles for this user</li>
                    <li><strong>Assign alliances (if R5/R4/APE):</strong>
                        <ul>
                            <li>Select which alliance(s) this user can manage</li>
                            <li>APE role typically gets access to all alliances</li>
                            <li>R5/R4 roles usually get 1-2 specific alliances</li>
                        </ul>
                    </li>
                    <li><strong>Power editor checkbox (optional):</strong> Enable if user needs power editing capability</li>
                    <li>Click <strong>"Create User"</strong> to save</li>
                </ol>

                <div class="help-success">
                    <strong>First Login:</strong> After creating a user, they can visit the login page and request a magic link. They\'ll receive an email with a secure login token that\'s valid for 10 minutes.
                </div>
            '
        ],
        [
            'title' => 'Editing Existing Users',
            'content' => '
                <p>To modify a user\'s permissions or alliance assignments:</p>

                <ol>
                    <li>Find the user in the user list</li>
                    <li>Click the <strong>"Edit"</strong> button</li>
                    <li>Update roles by checking/unchecking role checkboxes</li>
                    <li>Modify alliance assignments as needed</li>
                    <li>Toggle power editor status if needed</li>
                    <li>Click <strong>"Save Changes"</strong></li>
                </ol>

                <p><strong>Email Address Changes:</strong> Email addresses cannot be changed once a user is created. If a user needs a different email:</p>
                <ol>
                    <li>Create a new user with the new email address</li>
                    <li>Copy over the roles and alliance assignments</li>
                    <li>Delete the old user account</li>
                </ol>

                <div class="help-warning">
                    <strong>Active Sessions:</strong> Changes to user permissions take effect immediately. If a user is currently logged in, they may need to logout and login again for changes to fully apply.
                </div>
            '
        ],
        [
            'title' => 'Deleting Users',
            'content' => '
                <p>To remove a user from the system:</p>

                <ol>
                    <li>Find the user in the user list</li>
                    <li>Click the <strong>"Delete"</strong> button</li>
                    <li>Confirm the deletion in the popup</li>
                </ol>

                <div class="help-warning">
                    <strong>Permanent Action:</strong> Deleting a user is permanent. Their email address and role assignments will be removed from the system. They will no longer be able to request magic links or access the admin panel.
                </div>

                <div class="help-note">
                    <strong>Alternative:</strong> If you want to temporarily revoke access without deleting, remove all roles from the user instead. This preserves their account for future reactivation.
                </div>
            '
        ],
        [
            'title' => 'Alliance Assignment Best Practices',
            'content' => '
                <p><strong>R5 Assignments:</strong></p>
                <ul>
                    <li>Each alliance should have at least one R5 assigned</li>
                    <li>R5 users can manage their alliance\'s R4 officers</li>
                    <li>Consider assigning 2 R5s for important alliances (backup access)</li>
                </ul>

                <p><strong>R4 Assignments:</strong></p>
                <ul>
                    <li>R4 users have limited editing capabilities</li>
                    <li>Useful for alliance officers who need to help with data entry</li>
                    <li>R4s cannot modify R5 or other R4 assignments</li>
                </ul>

                <p><strong>APE Assignments:</strong></p>
                <ul>
                    <li>APE role should only be given to highly trusted users</li>
                    <li>APEs can edit ANY alliance, not just assigned ones</li>
                    <li>Typically 2-3 APE users is sufficient for most servers</li>
                    <li>All APE actions are logged for accountability</li>
                </ul>

                <div class="help-success">
                    <strong>Tip:</strong> For new alliances, create R5 access for their leadership as soon as the alliance is added. This empowers alliance leaders to maintain their own data.
                </div>
            '
        ],
        [
            'title' => 'Security Considerations',
            'content' => '
                <p><strong>Admin Role:</strong></p>
                <ul>
                    <li>Only assign admin role to highly trusted individuals</li>
                    <li>Admins can create/delete other admins</li>
                    <li>Admins have access to sensitive data and security settings</li>
                    <li>Review admin accounts regularly</li>
                </ul>

                <p><strong>President Role:</strong></p>
                <ul>
                    <li>Only one user should have president role at a time</li>
                    <li>President represents the server in governance</li>
                    <li>Can approve votes and manage council</li>
                </ul>

                <p><strong>Email Verification:</strong></p>
                <ul>
                    <li>Verify email addresses before creating users</li>
                    <li>Typos in email addresses will prevent users from logging in</li>
                    <li>Test new user accounts by having them request a magic link</li>
                </ul>

                <div class="help-warning">
                    <strong>Audit Trail:</strong> All user management actions (create, edit, delete) are logged in the security audit log. Review this log regularly to ensure no unauthorized changes are made.
                </div>
            '
        ],
        [
            'title' => 'Troubleshooting',
            'content' => '
                <p><strong>User can\'t receive magic link emails:</strong></p>
                <ul>
                    <li>Verify the email address is correct (no typos)</li>
                    <li>Check the user\'s spam/junk folder</li>
                    <li>Verify SMTP settings are configured correctly (admin only)</li>
                    <li>Test with a different email address if issues persist</li>
                </ul>

                <p><strong>User has wrong permissions:</strong></p>
                <ul>
                    <li>Edit the user and verify roles are checked correctly</li>
                    <li>Confirm alliance assignments are correct</li>
                    <li>Have user logout and login again to refresh permissions</li>
                </ul>

                <p><strong>Can\'t delete a user:</strong></p>
                <ul>
                    <li>Ensure you have admin role</li>
                    <li>You cannot delete yourself while logged in</li>
                    <li>Check browser console for error messages</li>
                </ul>

                <p><strong>Duplicate email addresses:</strong></p>
                <ul>
                    <li>Each email must be unique in the system</li>
                    <li>If you get a "user already exists" error, check for duplicates</li>
                    <li>Email addresses are case-insensitive</li>
                </ul>
            '
        ]
    ]
];
