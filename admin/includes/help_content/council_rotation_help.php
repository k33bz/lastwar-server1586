<?php
/**
 * Help Content: Council Rotation Schedule
 * For Admin and President roles
 */

$is_admin = has_role($user, 'admin');
$is_president = has_role($user, 'president');

return [
    'title' => 'Council Rotation Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>The Council Rotation system manages the weekly rotation of council seats among server alliances. The server council consists of permanent members (top 5 ranked alliances) and rotating members (selected from ranks 6-15).</p>

                ' . ($is_admin || $is_president ? '
                <div class="help-note">
                    <strong>Your Role:</strong> As ' . ($is_admin ? 'an Admin' : 'the President') . ', you can manage the council rotation schedule, including creating new rotations and updating existing ones.
                </div>
                ' : '') . '

                <p><strong>Council Structure:</strong></p>
                <ul>
                    <li><strong>Top 5 Permanent:</strong> Alliances ranked 1-5 by power always have council seats</li>
                    <li><strong>2 Rotating Seats:</strong> Two alliances from ranks 6-15 are selected each week</li>
                    <li><strong>President Alliance:</strong> The alliance of the current council president</li>
                </ul>
            '
        ],
        [
            'title' => 'Understanding Rotation Weeks',
            'content' => '
                <p>Each rotation represents one week\'s council composition:</p>

                <p><strong>Week Information:</strong></p>
                <ul>
                    <li><strong>Week Number:</strong> Sequential week count for the server (e.g., Week 23, Week 24)</li>
                    <li><strong>Start Date:</strong> When this rotation begins (typically Monday)</li>
                    <li><strong>End Date:</strong> When this rotation ends (typically Sunday)</li>
                    <li><strong>Status:</strong> Current, Upcoming, or Past</li>
                </ul>

                <p><strong>Rotation Schedule Display:</strong></p>
                <ul>
                    <li>Current week is highlighted</li>
                    <li>Future rotations can be planned in advance</li>
                    <li>Past rotations are archived for reference</li>
                </ul>

                <div class="help-note">
                    <strong>Planning Ahead:</strong> You can create multiple future rotations to ensure smooth transitions. Council members can see upcoming rotations and prepare accordingly.
                </div>
            '
        ],
        [
            'title' => 'Top 5 Permanent Seats',
            'content' => '
                <p>The top 5 alliances by power ranking always hold permanent council seats:</p>

                <p><strong>Automatic Updates:</strong></p>
                <ul>
                    <li>Top 5 is determined by current alliance power rankings</li>
                    <li>When creating a new rotation, the system can auto-populate top 5</li>
                    <li>If power rankings change, top 5 may shift between weeks</li>
                </ul>

                <p><strong>Selecting Top 5:</strong></p>
                <ol>
                    <li>When creating a rotation, you\'ll see a top 5 selection interface</li>
                    <li>Select the 5 alliances that should hold permanent seats this week</li>
                    <li>Typically these match the current power rankings 1-5</li>
                    <li>Order matters - select them in rank order (1st, 2nd, 3rd, 4th, 5th)</li>
                </ol>

                <div class="help-warning">
                    <strong>Rank Changes:</strong> If an alliance\'s power changes dramatically during a week, they may drop out of top 5. The rotation reflects the rankings at the time it was created - mid-week changes don\'t automatically update the council.
                </div>
            '
        ],
        [
            'title' => 'Rotating Seats (NAP15)',
            'content' => '
                <p>Two alliances from ranks 6-15 (the NAP15 group) rotate onto the council each week:</p>

                <p><strong>Rotation Process:</strong></p>
                <ul>
                    <li>Alliances ranked 6-15 are eligible for rotating seats</li>
                    <li>Two are selected each week</li>
                    <li>Selection can be based on:
                        <ul>
                            <li>Fair rotation schedule (everyone gets turns)</li>
                            <li>Alliance activity/participation</li>
                            <li>Special circumstances or events</li>
                        </ul>
                    </li>
                    <li>Rotating members have full voting rights while on council</li>
                </ul>

                <p><strong>Selecting Rotating Members:</strong></p>
                <ol>
                    <li>Review which alliances have recently had rotating seats</li>
                    <li>Select two alliances from ranks 6-15 who haven\'t had recent turns</li>
                    <li>Consider participation and engagement when selecting</li>
                    <li>Communicate selections in advance so alliances can prepare</li>
                </ol>

                <div class="help-success">
                    <strong>Fair Rotation:</strong> Keep a mental (or written) note of which alliances have been on council recently to ensure everyone in NAP15 gets fair representation over time.
                </div>
            '
        ],
        [
            'title' => 'Creating a New Rotation',
            'content' => '
                <p><strong>Step-by-step process:</strong></p>
                <ol>
                    <li>Click <strong>"Create New Rotation"</strong></li>
                    <li><strong>Week Number:</strong> Enter the week number (auto-increments from previous)</li>
                    <li><strong>Start Date:</strong> Select the Monday this rotation begins</li>
                    <li><strong>End Date:</strong> Select the Sunday this rotation ends</li>
                    <li><strong>Top 5 Permanent:</strong> Select the 5 highest-ranked alliances
                        <ul>
                            <li>These are typically auto-populated from current rankings</li>
                            <li>Verify they match actual power rankings 1-5</li>
                        </ul>
                    </li>
                    <li><strong>Rotating Members:</strong> Select 2 alliances from NAP15 (ranks 6-15)
                        <ul>
                            <li>Choose alliances that haven\'t had recent turns</li>
                            <li>Ensure fair distribution over time</li>
                        </ul>
                    </li>
                    <li><strong>President Alliance:</strong> Select which alliance holds the president seat
                        <ul>
                            <li>Usually remains constant unless presidency transfers</li>
                        </ul>
                    </li>
                    <li>Click <strong>"Save Rotation"</strong></li>
                </ol>

                <div class="help-note">
                    <strong>Advance Planning:</strong> Create rotations 1-2 weeks in advance so council members know when they\'ll be on duty.
                </div>
            '
        ],
        [
            'title' => 'Editing Existing Rotations',
            'content' => '
                <p>You can edit rotations to correct mistakes or handle mid-week changes:</p>

                <p><strong>When to Edit:</strong></p>
                <ul>
                    <li><strong>Before the week starts:</strong> Freely edit any future rotation</li>
                    <li><strong>During the week:</strong> Only edit if absolutely necessary (causes confusion)</li>
                    <li><strong>Past rotations:</strong> Generally leave as historical record</li>
                </ul>

                <p><strong>Editing Process:</strong></p>
                <ol>
                    <li>Find the rotation in the list</li>
                    <li>Click <strong>"Edit"</strong></li>
                    <li>Update the necessary fields</li>
                    <li>Click <strong>"Save Changes"</strong></li>
                </ol>

                <div class="help-warning">
                    <strong>Mid-Week Changes:</strong> If you must edit an active rotation, communicate the change to all council members immediately via Discord. Unexpected council changes can disrupt governance.
                </div>
            '
        ],
        [
            'title' => 'Integration with Other Features',
            'content' => '
                <p>Council rotation affects several other system features:</p>

                <p><strong>Vote Eligibility:</strong></p>
                <ul>
                    <li>Only current council members can vote on council votes</li>
                    <li>The system checks the active rotation to determine eligibility</li>
                    <li>When a vote is created, eligible voters are based on current rotation</li>
                </ul>

                <p><strong>Vote Proposals:</strong></p>
                <ul>
                    <li>Council members (R5/R4/APE from council alliances) can propose votes</li>
                    <li>Proposal permissions are based on current council membership</li>
                </ul>

                <p><strong>Public Website:</strong></p>
                <ul>
                    <li>Current council composition is displayed publicly</li>
                    <li>Players can see which alliances are currently on council</li>
                    <li>Future rotations may be visible for transparency</li>
                </ul>

                <div class="help-note">
                    <strong>Sync Timing:</strong> Changes to council rotation sync to the public website and Discord bot within minutes.
                </div>
            '
        ],
        [
            'title' => 'Best Practices',
            'content' => '
                <p><strong>Weekly Schedule:</strong></p>
                <ul>
                    <li>Create new rotations at least 3-4 days before they start</li>
                    <li>Announce new rotations in Discord so members know who\'s on council</li>
                    <li>Keep a consistent rotation schedule (e.g., Monday-Sunday)</li>
                </ul>

                <p><strong>Fair Representation:</strong></p>
                <ul>
                    <li>Track which NAP15 alliances have been on council recently</li>
                    <li>Ensure all eligible alliances get turns over time</li>
                    <li>Consider activity level when selecting rotating members</li>
                </ul>

                <p><strong>Communication:</strong></p>
                <ul>
                    <li>Notify rotating alliances in advance that they\'ll be on council</li>
                    <li>Explain council responsibilities to new members</li>
                    <li>Post rotation schedule publicly so everyone can plan</li>
                </ul>

                <p><strong>Documentation:</strong></p>
                <ul>
                    <li>Keep past rotations as historical records</li>
                    <li>Note any special circumstances in alliance notes</li>
                    <li>Track participation for future rotation decisions</li>
                </ul>
            '
        ],
        [
            'title' => 'Troubleshooting',
            'content' => '
                <p><strong>Wrong alliances showing in top 5:</strong></p>
                <ul>
                    <li>Check current alliance power rankings</li>
                    <li>Update power values if they\'re outdated</li>
                    <li>Manually select correct alliances when creating rotation</li>
                </ul>

                <p><strong>Council member can\'t vote:</strong></p>
                <ul>
                    <li>Verify their alliance is in the current rotation</li>
                    <li>Check that the rotation dates are correct (is it actually active?)</li>
                    <li>Ensure the user has R5/R4/APE role for a council alliance</li>
                </ul>

                <p><strong>Can\'t create new rotation:</strong></p>
                <ul>
                    <li>Verify you have Admin or President role</li>
                    <li>Check for date conflicts with existing rotations</li>
                    <li>Ensure all required fields are filled</li>
                </ul>

                <p><strong>Changes not appearing on website:</strong></p>
                <ul>
                    <li>Wait a few minutes for sync to complete</li>
                    <li>Check that the rotation is saved correctly</li>
                    <li>Verify the rotation dates are active</li>
                    <li>Clear browser cache and refresh the public site</li>
                </ul>
            '
        ]
    ]
];
