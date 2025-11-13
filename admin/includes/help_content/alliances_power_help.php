<?php
/**
 * Help Content: Alliance Power Editing
 * For APE, R5, and R4 roles
 */

$is_ape = has_role($token, 'ape');
$is_r5 = has_role($token, 'r5');
$is_r4 = has_role($token, 'r4');

return [
    'title' => 'Power Management Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>This page provides a quick way to update alliance power values across the server. ' .
                ($is_ape ? 'As an <strong>Alliance Power Editor (APE)</strong>, you can update power for ALL alliances on the server.' :
                'As an ' . ($is_r5 ? '<strong>R5</strong>' : '<strong>R4</strong>') . ', you can update power for your assigned alliance(s).') . '</p>

                <div class="help-note">
                    <strong>Purpose:</strong> This streamlined interface is designed for quick weekly power updates without needing to navigate to individual alliance edit pages.
                </div>

                ' . ($is_ape ? '
                <div class="help-warning">
                    <strong>APE Role Responsibility:</strong> With great power comes great responsibility! You have the ability to update ANY alliance\'s power. Please ensure all updates are accurate and authorized. All changes are logged in the audit trail.
                </div>
                ' : '') . '
            '
        ],
        [
            'title' => 'Understanding the Interface',
            'content' => '
                <p>The page displays all alliances in a table format with the following columns:</p>

                <p><strong>Rank:</strong> The alliance\'s current ranking based on power (1 = highest power).</p>

                <p><strong>Tag:</strong> The alliance\'s unique identifier (e.g., "UvvU", "ORCE").</p>

                <p><strong>Name:</strong> The full alliance name.</p>

                <p><strong>Current Power:</strong> The current power value on record, formatted with commas for readability (e.g., "7,804,360,932").</p>

                <p><strong>New Power:</strong> An editable field where you can enter the updated power value.</p>

                <p><strong>Actions:</strong> A "Save" button to save the new power value for that alliance.</p>

                ' . (!$is_ape ? '
                <div class="help-note">
                    <strong>Your Access:</strong> You can only edit alliances you are assigned to. Other alliances will be visible but read-only for reference.
                </div>
                ' : '') . '
            '
        ],
        [
            'title' => 'How to Update Power',
            'content' => '
                <p><strong>Step-by-step process:</strong></p>
                <ol>
                    <li><strong>Check in-game power:</strong> Open Last War and navigate to your alliance (or the alliance you\'re updating)</li>
                    <li><strong>Note the total power:</strong> Copy the exact total alliance power value</li>
                    <li><strong>Enter the new value:</strong> Click in the "New Power" field for the alliance and type/paste the power value</li>
                    <li><strong>Format:</strong> You can enter the number with or without commas (e.g., "7804360932" or "7,804,360,932")</li>
                    <li><strong>Save:</strong> Click the "Save" button for that alliance</li>
                    <li><strong>Confirmation:</strong> You\'ll see a success message when the update is complete</li>
                    <li><strong>Verify:</strong> The "Current Power" column will update with your new value</li>
                </ol>

                <div class="help-success">
                    <strong>Tip:</strong> You can update multiple alliances in sequence. Just update one field, save it, then move to the next alliance.
                </div>
            '
        ],
        [
            'title' => 'Number Formatting',
            'content' => '
                <p>The system accepts power values in multiple formats:</p>

                <p><strong>With commas:</strong> <code>7,804,360,932</code></p>
                <p><strong>Without commas:</strong> <code>7804360932</code></p>
                <p><strong>With spaces:</strong> <code>7 804 360 932</code> (spaces will be removed)</p>

                <p>The system will automatically clean and format the number before saving.</p>

                <div class="help-warning">
                    <strong>Important:</strong> Make sure you enter the FULL power value, not abbreviated versions like "7.8B". The system expects the complete number.
                </div>

                <p><strong>Valid Examples:</strong></p>
                <ul>
                    <li>✅ 7804360932</li>
                    <li>✅ 7,804,360,932</li>
                    <li>✅ 50000000</li>
                </ul>

                <p><strong>Invalid Examples:</strong></p>
                <ul>
                    <li>❌ 7.8B (abbreviations not supported)</li>
                    <li>❌ 7.8 billion (words not supported)</li>
                    <li>❌ abc123 (letters not allowed)</li>
                </ul>
            '
        ],
        [
            'title' => 'What Happens After Saving',
            'content' => '
                <p>When you save a power update, several things happen automatically:</p>

                <p><strong>1. Immediate Updates:</strong></p>
                <ul>
                    <li>The alliance\'s power value is updated in the database</li>
                    <li>The public website rankings are recalculated</li>
                    <li>The alliance\'s rank may change based on the new power</li>
                    <li>The change appears on the public website within seconds</li>
                </ul>

                <p><strong>2. Historical Tracking:</strong></p>
                <ul>
                    <li>The power change is recorded in the power history</li>
                    <li>This data is used for power trend charts on the website</li>
                    <li>Historical data helps track alliance growth over time</li>
                </ul>

                <p><strong>3. Audit Logging:</strong></p>
                <ul>
                    <li>Your email and timestamp are recorded</li>
                    <li>The old and new power values are logged</li>
                    <li>Admins can review all power changes for accountability</li>
                </ul>

                <div class="help-note">
                    <strong>Council Rotation Impact:</strong> If a power update causes rank changes that affect council membership (top 5 permanent seats or rotating positions), the council schedule may need to be updated. Contact an admin if you notice this.
                </div>
            '
        ],
        [
            'title' => 'Best Practices',
            'content' => '
                <p><strong>Regular Updates:</strong> Update power values weekly (or more frequently during active events) to keep rankings accurate.</p>

                <p><strong>Verify Before Saving:</strong> Double-check the power value before clicking Save. Once saved, you\'ll need to update it again if you made an error.</p>

                <p><strong>Screenshot Proof:</strong> Consider taking a screenshot of the in-game power when updating, especially for significant changes. This can help resolve disputes.</p>

                <p><strong>Coordinate with Officers:</strong> If multiple people have APE or R5 access, coordinate who updates power to avoid conflicts or duplicate updates.</p>

                ' . ($is_ape ? '
                <p><strong>For APE Role:</strong></p>
                <ul>
                    <li>Only update alliances you have authorization to update</li>
                    <li>Communicate with alliance leadership before making changes</li>
                    <li>Keep a record of update requests for accountability</li>
                    <li>Report any suspicious or unusual power changes to admins</li>
                </ul>
                ' : '') . '

                <div class="help-success">
                    <strong>Communication Tip:</strong> If you notice an alliance hasn\'t updated their power in a while, reach out to their R5 via Discord to offer assistance.
                </div>
            '
        ],
        [
            'title' => 'Ranking System Explained',
            'content' => '
                <p>Rankings are calculated automatically based on power values:</p>

                <p><strong>Rank 1:</strong> Highest power alliance on the server</p>
                <p><strong>Rank 2:</strong> Second highest power</p>
                <p><strong>Rank 3:</strong> Third highest power</p>
                <p><strong>...and so on</strong></p>

                <p><strong>Ties:</strong> If two alliances have identical power, they are ranked in the order they appear in the database (usually the order they were added).</p>

                <p><strong>Council Implications:</strong></p>
                <ul>
                    <li><strong>Ranks 1-5:</strong> Permanent council seats (always on council)</li>
                    <li><strong>Ranks 6-15:</strong> Eligible for rotating council seats (2 alliances selected weekly)</li>
                    <li><strong>Rank 16+:</strong> Not on council (but can still participate in server activities)</li>
                </ul>

                <div class="help-note">
                    <strong>President Alliance:</strong> The council president is designated separately and is not automatically the #1 ranked alliance. The president designation is managed by admins.
                </div>
            '
        ],
        [
            'title' => 'Troubleshooting',
            'content' => '
                <p><strong>Can\'t edit certain alliances:</strong> ' . ($is_ape ? 'This shouldn\'t happen for APE role. Try refreshing the page. If the issue persists, contact an admin.' : 'You can only edit alliances you\'re assigned to. Contact an admin if you need access to a different alliance.') . '</p>

                <p><strong>"Invalid power value" error:</strong> Make sure you\'re entering only numbers (commas are okay). Remove any letters, spaces, or symbols like "B" for billion.</p>

                <p><strong>Save button not responding:</strong> Check your internet connection. Try refreshing the page and entering the value again.</p>

                <p><strong>Power updated but ranking didn\'t change:</strong> Rankings only change if your new power value passes another alliance. The current rank is shown in the leftmost column.</p>

                <p><strong>Accidental wrong value saved:</strong> Simply enter the correct value and save again. All changes are logged, so errors can be reviewed if needed.</p>

                <p><strong>Need to bulk update many alliances:</strong> ' . ($is_ape ? 'Use this page to update them one by one, or contact an admin about importing power data from a CSV file.' : 'Contact an APE or admin - they can update multiple alliances more efficiently.') . '</p>
            '
        ],
        [
            'title' => 'Keyboard Shortcuts',
            'content' => '
                <p>Make updates faster with these keyboard shortcuts:</p>

                <p><strong>Tab:</strong> Move to the next power input field</p>
                <p><strong>Shift + Tab:</strong> Move to the previous power input field</p>
                <p><strong>Enter:</strong> Save the current field (if focused on an input)</p>
                <p><strong>Ctrl/Cmd + F:</strong> Search for specific alliance (browser feature)</p>

                <div class="help-success">
                    <strong>Pro Tip:</strong> Use Tab to quickly navigate between alliances, type the power value, press Enter to save, then Tab to the next one. This creates a smooth workflow for updating multiple alliances.
                </div>
            '
        ]
    ]
];
