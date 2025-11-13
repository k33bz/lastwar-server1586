<?php
/**
 * Help Content: Alliance Editing
 * For R5 and R4 roles
 */

return [
    'title' => 'Alliance Management Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>This page allows you to manage your alliance information that appears on the public website. All changes are saved automatically and will be visible to everyone viewing the server rankings.</p>

                <div class="help-note">
                    <strong>Note:</strong> As an ' . (has_role($token, 'r5') ? 'R5' : 'R4') . ', you can only edit alliances you are assigned to. Contact an admin if you need access to a different alliance.
                </div>
            '
        ],
        [
            'title' => 'Basic Alliance Information',
            'content' => '
                <p><strong>Alliance Tag:</strong> This is your alliance\'s unique identifier (e.g., "UvvU", "ORCE"). This cannot be changed here - contact an admin if the tag needs to be updated.</p>

                <p><strong>Alliance Name:</strong> The full name of your alliance. This appears on the public website alongside your tag.</p>

                <p><strong>Power Value:</strong> Your alliance\'s total power. This determines your ranking on the server. Update this regularly to keep the rankings accurate.</p>

                <div class="help-warning">
                    <strong>Important:</strong> Power values should be accurate. The rankings automatically update based on power, so entering incorrect values will affect your position in the leaderboard.
                </div>
            '
        ],
        [
            'title' => 'R5 Leadership',
            'content' => '
                <p><strong>R5 Name:</strong> The in-game name of your alliance leader. This appears on the public website.</p>

                <p><strong>R5 Discord ID:</strong> The Discord user ID of your R5. This is used for:</p>
                <ul>
                    <li>Council voting notifications (if your alliance is on the council)</li>
                    <li>Discord bot integration for alliance-specific features</li>
                    <li>Official communications from server leadership</li>
                </ul>

                <div class="help-note">
                    <strong>How to find a Discord ID:</strong>
                    <ol>
                        <li>Enable Developer Mode in Discord (User Settings → Advanced → Developer Mode)</li>
                        <li>Right-click on the user\'s profile</li>
                        <li>Click "Copy User ID"</li>
                        <li>The ID will be a long number (e.g., "123456789012345678")</li>
                    </ol>
                </div>
            '
        ],
        [
            'title' => 'R4 Officers',
            'content' => '
                <p>You can add multiple R4 officers to your alliance. Each R4 entry includes:</p>

                <p><strong>R4 Name:</strong> The in-game name of the officer.</p>

                <p><strong>Discord ID:</strong> The officer\'s Discord user ID for notifications and bot features.</p>

                <p><strong>Can Vote:</strong> Check this box to allow this R4 to vote on council matters when the R5 is unavailable. Only enable this for trusted officers.</p>

                <p><strong>Role/Title:</strong> Optional field to specify the officer\'s role (e.g., "Deputy", "Recruiter", "War Coordinator").</p>

                <div class="help-warning">
                    <strong>Vote Delegation:</strong> Only ONE person can vote per alliance in council votes. If multiple R4s have "Can Vote" enabled, only the first one to respond will have their vote counted. The R5 always has priority if they vote.
                </div>

                <p><strong>Adding R4s:</strong> Click the "Add R4" button to add a new officer. You can add as many as needed.</p>

                <p><strong>Removing R4s:</strong> Click the "Remove" button next to any R4 to delete their entry.</p>
            '
        ],
        [
            'title' => 'Rule Signing' . (has_role($token, 'r5') ? '' : ' (R5 Only)'),
            'content' => '
                ' . (has_role($token, 'r5') ? '
                <p>As an R5, you can sign the server rules on behalf of your alliance. This indicates your alliance agrees to follow the established server guidelines.</p>

                <p><strong>Signature Status:</strong> Check the "Rules Signed" box to indicate your alliance has reviewed and agreed to the server rules.</p>

                <p><strong>What it means:</strong></p>
                <ul>
                    <li>Your alliance name will appear in the "Signatories" section on the public website</li>
                    <li>It shows your commitment to fair play and server cooperation</li>
                    <li>Most alliances on the server have signed the rules</li>
                </ul>

                <div class="help-note">
                    <strong>Tip:</strong> Review the full server rules on the public website before signing. You can access them from the main navigation.
                </div>
                ' : '
                <p>Only the R5 can sign the server rules on behalf of your alliance. If you believe your alliance should sign the rules, please contact your R5.</p>

                <p>The "Rules Signed" checkbox will be disabled for R4 accounts.</p>
                '
            )
        ],
        [
            'title' => 'Saving Changes',
            'content' => '
                <p>All changes are saved automatically when you update any field. You\'ll see confirmation messages when saves are successful.</p>

                <p><strong>What happens after saving:</strong></p>
                <ul>
                    <li>Changes appear on the public website immediately</li>
                    <li>Rankings automatically update based on new power values</li>
                    <li>Your alliance card updates with the new information</li>
                    <li>Discord bot integration updates with new officer information</li>
                </ul>

                <div class="help-success">
                    <strong>Audit Trail:</strong> All changes are logged in the system audit log with your email, timestamp, and what was changed. This ensures accountability and allows admins to track all modifications.
                </div>
            '
        ],
        [
            'title' => 'Common Tasks',
            'content' => '
                <p><strong>Weekly Power Updates:</strong></p>
                <ol>
                    <li>Check your alliance\'s current total power in-game</li>
                    <li>Update the "Power Value" field with the new number</li>
                    <li>Verify the change saved successfully</li>
                    <li>Check the public website to confirm your ranking is correct</li>
                </ol>

                <p><strong>Adding a New R4:</strong></p>
                <ol>
                    <li>Click "Add R4" button</li>
                    <li>Enter the officer\'s in-game name</li>
                    <li>Get their Discord ID (see Discord ID instructions above)</li>
                    <li>Decide if they should have voting rights</li>
                    <li>Optionally add their role/title</li>
                    <li>Save - the new R4 will be added immediately</li>
                </ol>

                <p><strong>Changing R5 Leadership:</strong></p>
                <ol>
                    <li>Update the "R5 Name" field with the new leader\'s name</li>
                    <li>Update the "R5 Discord ID" with their Discord ID</li>
                    <li>Update R4 list if the old R5 is now an R4</li>
                    <li>Changes take effect immediately</li>
                </ol>
            '
        ],
        [
            'title' => 'Troubleshooting',
            'content' => '
                <p><strong>Can\'t see my alliance:</strong> You may not have been assigned to this alliance yet. Contact an admin to get the proper access.</p>

                <p><strong>Changes aren\'t saving:</strong> Check your internet connection and try again. If the problem persists, contact an admin.</p>

                <p><strong>Power value seems wrong:</strong> Double-check the value in-game. Remember to include all member power, not just fighting power.</p>

                <p><strong>Discord ID not working:</strong> Make sure you copied the full ID (it should be 17-19 digits). Test it by having the Discord bot send a test message.</p>

                <p><strong>Need help with something else?</strong> Contact the server admin team via Discord or create a support ticket.</p>
            '
        ]
    ]
];
