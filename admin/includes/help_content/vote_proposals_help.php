<?php
/**
 * Help Content: Discord Vote Proposals
 * For R5, R4, and APE roles (council members)
 */

return [
    'title' => 'Council Vote Proposals Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>This page allows you to submit vote proposals for council consideration. As a council member, you can request votes on important server matters that require council approval.</p>

                <div class="help-note">
                    <strong>Who can submit proposals:</strong>
                    <ul>
                        <li>R5s from any council alliance</li>
                        <li>R4s with voting privileges</li>
                        <li>APE role members (if they are council members)</li>
                        <li>The server President</li>
                    </ul>
                </div>

                <p><strong>The Proposal Process:</strong></p>
                <ol>
                    <li>You submit a vote proposal using this page</li>
                    <li>The President reviews your proposal</li>
                    <li>President approves or rejects (or it auto-approves after 12 hours)</li>
                    <li>If approved, a vote is created and sent to all council members via Discord</li>
                    <li>Council members vote via Discord DMs (yes/no/abstain)</li>
                    <li>After 24 hours or when all votes are in, results are published</li>
                </ol>
            '
        ],
        [
            'title' => 'Creating a Vote Proposal',
            'content' => '
                <p><strong>Required Fields:</strong></p>

                <p><strong>Vote Title:</strong> A clear, concise title for the vote (max 200 characters). This should summarize what the vote is about.</p>
                <ul>
                    <li>✅ Good: "Approve New Alliance Territory Rules"</li>
                    <li>✅ Good: "Change Council Rotation from Weekly to Bi-Weekly"</li>
                    <li>❌ Bad: "Vote" (too vague)</li>
                    <li>❌ Bad: "Important thing we need to decide about the server and how we handle various situations" (too long)</li>
                </ul>

                <p><strong>Description:</strong> A detailed explanation of what is being voted on (max 1000 characters). Include:</p>
                <ul>
                    <li>What is being proposed</li>
                    <li>Why this vote is needed</li>
                    <li>Impact of a "yes" vote</li>
                    <li>Impact of a "no" vote</li>
                    <li>Any relevant background information</li>
                </ul>

                <p><strong>Category:</strong> Select the type of vote from the dropdown:</p>
                <ul>
                    <li><strong>Rule Change:</strong> Modifying server rules or policies</li>
                    <li><strong>Alliance Dispute:</strong> Resolving conflicts between alliances</li>
                    <li><strong>Territory Decision:</strong> Decisions about territory claims or assignments</li>
                    <li><strong>Event Planning:</strong> Major server events or competitions</li>
                    <li><strong>Other:</strong> Anything that doesn\'t fit the above categories</li>
                </ul>

                <div class="help-warning">
                    <strong>Be Specific:</strong> The more detailed and clear your proposal, the more likely it is to be approved quickly and receive informed votes from council members.
                </div>
            '
        ],
        [
            'title' => 'Viewing Your Proposals',
            'content' => '
                <p>All your submitted proposals appear in the table below the submission form. The table shows:</p>

                <p><strong>Status:</strong> The current state of your proposal</p>
                <ul>
                    <li><strong>Pending:</strong> Waiting for President approval</li>
                    <li><strong>Approved:</strong> Approved and vote has been created</li>
                    <li><strong>Rejected:</strong> Denied by the President</li>
                    <li><strong>Auto-Approved:</strong> Automatically approved after 12 hours</li>
                </ul>

                <p><strong>Title & Description:</strong> What you submitted</p>

                <p><strong>Category:</strong> The type of vote</p>

                <p><strong>Created At:</strong> When you submitted the proposal</p>

                <p><strong>Reviewed At:</strong> When the President took action (if applicable)</p>

                <p><strong>Reviewer:</strong> Who reviewed it (usually the President)</p>

                <p><strong>Notes:</strong> Comments from the President (usually present for rejections, explaining why)</p>

                <div class="help-note">
                    <strong>Refresh the Page:</strong> The table doesn\'t auto-update. Refresh your browser to see status changes.
                </div>
            '
        ],
        [
            'title' => 'What Happens After Approval',
            'content' => '
                <p>Once your proposal is approved (either by the President or auto-approved), the following happens automatically:</p>

                <p><strong>1. Vote Creation:</strong> A Discord vote is created with your title and description</p>

                <p><strong>2. Council Notification:</strong> All 7 council members receive a Discord DM with:</p>
                <ul>
                    <li>The vote title and description</li>
                    <li>Who submitted the proposal</li>
                    <li>Instructions on how to vote</li>
                    <li>The voting deadline (24 hours)</li>
                </ul>

                <p><strong>3. Voting Period:</strong> Council members respond via Discord DM with "vote: yes", "vote: no", or "vote: abstain"</p>

                <p><strong>4. Vote Finalization:</strong> The vote closes when either:</p>
                <ul>
                    <li>All 7 council members have voted, OR</li>
                    <li>24 hours have passed since the vote was created</li>
                </ul>

                <p><strong>5. Results Publication:</strong> Results are posted to:</p>
                <ul>
                    <li>Discord server (in the configured votes channel)</li>
                    <li>Website vote history</li>
                    <li>Each council member receives a DM with final results</li>
                </ul>

                <div class="help-success">
                    <strong>Tracking Your Vote:</strong> You can view the vote status and results in the Discord channel or by asking the Discord bot for vote details.
                </div>
            '
        ],
        [
            'title' => 'Auto-Approval System',
            'content' => '
                <p>To prevent proposals from being stuck indefinitely, there is an automatic approval system:</p>

                <p><strong>12-Hour Auto-Approval:</strong> If the President does not approve or reject your proposal within 12 hours, it will automatically be approved and the vote will be created.</p>

                <p><strong>Why this exists:</strong></p>
                <ul>
                    <li>Prevents President bottleneck (if they\'re busy or offline)</li>
                    <li>Ensures important votes don\'t get delayed unnecessarily</li>
                    <li>Maintains council activity even during President absence</li>
                </ul>

                <div class="help-note">
                    <strong>12-Hour Timer:</strong> This means even if you submit a proposal late at night, it will be approved by late afternoon the next day if the President hasn\'t acted.
                </div>

                <p><strong>President Priority:</strong> The President can still review and approve/reject your proposal before the 12-hour mark. Auto-approval only happens if they don\'t take action.</p>
            '
        ],
        [
            'title' => 'Handling Rejections',
            'content' => '
                <p>If the President rejects your proposal, don\'t be discouraged! Common reasons for rejection include:</p>

                <p><strong>Vague or Unclear:</strong> The proposal doesn\'t clearly explain what is being voted on</p>
                <p><em>Solution:</em> Resubmit with more detailed description and clearer title</p>

                <p><strong>Not a Council Matter:</strong> The issue doesn\'t require a council vote</p>
                <p><em>Solution:</em> Handle it through alliance coordination or contact admins directly</p>

                <p><strong>Already Decided:</strong> The topic has been recently voted on or is covered by existing rules</p>
                <p><em>Solution:</em> Check vote history and server rules before resubmitting</p>

                <p><strong>Needs More Discussion:</strong> The proposal is complex and needs offline discussion first</p>
                <p><em>Solution:</em> Bring it up in Discord council chat, then create formal vote after discussion</p>

                <p><strong>Timing Issues:</strong> Now might not be the right time (e.g., during major event)</p>
                <p><em>Solution:</em> Wait for a better time and resubmit</p>

                <div class="help-warning">
                    <strong>Review Rejection Notes:</strong> The President usually includes a reason in the "Notes" field. Read this carefully before resubmitting.
                </div>

                <p><strong>You can always resubmit:</strong> Rejections are not permanent. Revise your proposal based on the feedback and submit again.</p>
            '
        ],
        [
            'title' => 'Best Practices',
            'content' => '
                <p><strong>Before Submitting:</strong></p>
                <ul>
                    <li>Discuss the topic in Discord council chat to gauge support</li>
                    <li>Make sure it\'s truly a council-level decision</li>
                    <li>Check if similar votes have been done recently</li>
                    <li>Draft your proposal in a text editor first</li>
                </ul>

                <p><strong>Writing Good Proposals:</strong></p>
                <ul>
                    <li>Use clear, professional language</li>
                    <li>Avoid personal attacks or inflammatory language</li>
                    <li>Present both sides of the issue fairly</li>
                    <li>Include relevant facts and data</li>
                    <li>Proofread before submitting</li>
                </ul>

                <p><strong>Timing Considerations:</strong></p>
                <ul>
                    <li>Submit during active hours (not late at night) for faster review</li>
                    <li>Avoid major event days when council is busy</li>
                    <li>Give the President reasonable time to review</li>
                    <li>Don\'t submit multiple proposals at once unless urgent</li>
                </ul>

                <div class="help-success">
                    <strong>Communication:</strong> After submitting, you can message the President in Discord to notify them, especially if the matter is time-sensitive.
                </div>
            '
        ],
        [
            'title' => 'Vote Categories Explained',
            'content' => '
                <p><strong>Rule Change:</strong> Use this for:</p>
                <ul>
                    <li>Adding new server rules</li>
                    <li>Modifying existing rules</li>
                    <li>Removing outdated rules</li>
                    <li>Changes to council procedures</li>
                </ul>

                <p><strong>Alliance Dispute:</strong> Use this for:</p>
                <ul>
                    <li>Conflicts between alliances that need council mediation</li>
                    <li>Violations of server rules by alliances</li>
                    <li>Territory disputes between alliances</li>
                </ul>

                <p><strong>Territory Decision:</strong> Use this for:</p>
                <ul>
                    <li>Assigning new territories to alliances</li>
                    <li>Resolving territory overlaps</li>
                    <li>Territory rule changes</li>
                    <li>Territory trading or swaps</li>
                </ul>

                <p><strong>Event Planning:</strong> Use this for:</p>
                <ul>
                    <li>Server-wide events that need council approval</li>
                    <li>Major competitions or tournaments</li>
                    <li>Event rules and structure</li>
                    <li>Resource allocation for events</li>
                </ul>

                <p><strong>Other:</strong> Use this for:</p>
                <ul>
                    <li>Anything that doesn\'t fit above categories</li>
                    <li>Procedural votes</li>
                    <li>Policy decisions</li>
                    <li>Administrative matters</li>
                </ul>
            '
        ],
        [
            'title' => 'Troubleshooting',
            'content' => '
                <p><strong>Can\'t submit proposal:</strong> Make sure all required fields are filled out and within character limits. Check that you have proper council member status.</p>

                <p><strong>Proposal not showing up:</strong> Refresh the page. If it still doesn\'t appear, check browser console for errors or contact an admin.</p>

                <p><strong>Status stuck at "Pending":</strong> Wait for President review or the 12-hour auto-approval. You can message the President on Discord to follow up.</p>

                <p><strong>Want to edit a submitted proposal:</strong> You cannot edit after submission. If you need to make changes before approval, contact the President to reject it, then resubmit the corrected version.</p>

                <p><strong>Proposal approved but no vote created:</strong> This shouldn\'t happen. Contact an admin immediately - there may be a technical issue with the Discord bot integration.</p>

                <p><strong>Need help with something else?</strong> Contact the President or admin team via Discord.</p>
            '
        ]
    ]
];
