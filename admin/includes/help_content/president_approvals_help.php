<?php
/**
 * Help Content: President Vote Approvals
 * For President role only
 */

return [
    'title' => 'President Vote Approval Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>As the server <strong>President</strong>, you are responsible for reviewing and approving vote proposals submitted by council members. This is a crucial role in the server\'s governance system.</p>

                <p><strong>Your Responsibilities:</strong></p>
                <ul>
                    <li>Review all incoming vote proposals</li>
                    <li>Approve well-formed, legitimate proposals</li>
                    <li>Reject proposals that are unclear, inappropriate, or don\'t require a vote</li>
                    <li>Provide clear feedback when rejecting proposals</li>
                    <li>Act in a timely manner (within 12 hours when possible)</li>
                </ul>

                <div class="help-note">
                    <strong>Auto-Approval Safety Net:</strong> If you don\'t review a proposal within 12 hours, it will automatically be approved and a vote will be created. This prevents bottlenecks but means you should check this page regularly.
                </div>
            '
        ],
        [
            'title' => 'Understanding the Proposal Table',
            'content' => '
                <p>The table displays all pending vote proposals that require your review:</p>

                <p><strong>Submitted By:</strong> The council member who created the proposal (shown as email address)</p>

                <p><strong>Title:</strong> The vote title - should be clear and concise</p>

                <p><strong>Description:</strong> Detailed explanation of what is being voted on - click to expand if truncated</p>

                <p><strong>Category:</strong> The type of vote (Rule Change, Alliance Dispute, Territory Decision, Event Planning, Other)</p>

                <p><strong>Submitted At:</strong> When the proposal was created</p>

                <p><strong>Time Remaining:</strong> Countdown until auto-approval (12 hours from submission)</p>

                <p><strong>Actions:</strong> Approve or Reject buttons</p>

                <div class="help-warning">
                    <strong>Priority Review:</strong> Proposals with less time remaining should be reviewed first. Look for red "URGENT" indicators showing less than 2 hours remain.
                </div>
            '
        ],
        [
            'title' => 'How to Approve a Proposal',
            'content' => '
                <p><strong>Step-by-step approval process:</strong></p>
                <ol>
                    <li><strong>Read carefully:</strong> Review the title, description, and category</li>
                    <li><strong>Assess legitimacy:</strong> Is this a council-level decision? Is it clear what is being voted on?</li>
                    <li><strong>Consider timing:</strong> Is now a good time for this vote? (e.g., not during major events)</li>
                    <li><strong>Click "Approve":</strong> If the proposal meets standards</li>
                    <li><strong>Confirmation:</strong> You\'ll see a success message</li>
                    <li><strong>Automatic actions:</strong> Vote is created immediately and DMs are sent to all council members</li>
                </ol>

                <div class="help-success">
                    <strong>What happens after approval:</strong>
                    <ul>
                        <li>Vote is created in Discord bot system</li>
                        <li>All 7 council members receive Discord DM with vote details</li>
                        <li>24-hour voting period begins</li>
                        <li>Proposal is removed from your pending list</li>
                        <li>Submitter is notified of approval</li>
                    </ul>
                </div>

                <p><strong>Approval Criteria:</strong></p>
                <ul>
                    <li>✅ Clear, specific title and description</li>
                    <li>✅ Legitimate council-level decision</li>
                    <li>✅ Not already covered by existing rules/votes</li>
                    <li>✅ Appropriate category selected</li>
                    <li>✅ Reasonable timing (not during major events/conflicts)</li>
                </ul>
            '
        ],
        [
            'title' => 'How to Reject a Proposal',
            'content' => '
                <p><strong>Step-by-step rejection process:</strong></p>
                <ol>
                    <li><strong>Identify the issue:</strong> Why is this proposal not ready for a vote?</li>
                    <li><strong>Click "Reject":</strong> A modal/prompt will appear</li>
                    <li><strong>Provide clear reasoning:</strong> Enter a detailed explanation of why you\'re rejecting it</li>
                    <li><strong>Be constructive:</strong> Tell the submitter what they need to fix to resubmit</li>
                    <li><strong>Submit rejection:</strong> Confirm the rejection</li>
                    <li><strong>Notification:</strong> Submitter is notified with your reasoning</li>
                </ol>

                <div class="help-note">
                    <strong>Be Specific with Feedback:</strong> Generic rejections like "not good enough" are not helpful. Explain exactly what needs to change.
                </div>

                <p><strong>Common Rejection Reasons:</strong></p>

                <p><strong>"Unclear proposal - needs more detail"</strong></p>
                <p><em>Example feedback:</em> "The description doesn\'t explain what specific rule change you\'re proposing. Please resubmit with the exact wording of the new/modified rule."</p>

                <p><strong>"Not a council matter"</strong></p>
                <p><em>Example feedback:</em> "This is an alliance-internal matter that should be handled between the two R5s directly. Council votes are for server-wide decisions."</p>

                <p><strong>"Recently voted on"</strong></p>
                <p><em>Example feedback:</em> "This topic was voted on 2 weeks ago (Vote #234). The council decided to keep the current system. Needs to wait at least 30 days before re-voting."</p>

                <p><strong>"Timing - during event"</strong></p>
                <p><em>Example feedback:</em> "Capital Clash starts in 2 days and council will be focused on that. Please resubmit this proposal after the event concludes (approx. 5 days)."</p>

                <p><strong>"Needs offline discussion first"</strong></p>
                <p><em>Example feedback:</em> "This is a complex issue that should be discussed in Discord council chat before a formal vote. Please raise it there and we can create a vote after discussion."</p>
            '
        ],
        [
            'title' => 'Time Management & Auto-Approval',
            'content' => '
                <p><strong>The 12-Hour Rule:</strong></p>
                <p>If you don\'t approve or reject a proposal within 12 hours of submission, it will automatically be approved and the vote will be created without your review.</p>

                <p><strong>Why this exists:</strong></p>
                <ul>
                    <li>Prevents you from being a bottleneck if you\'re offline/busy</li>
                    <li>Ensures council business continues even during your absence</li>
                    <li>Gives council members confidence their proposals won\'t languish</li>
                </ul>

                <p><strong>Best Practices:</strong></p>
                <ul>
                    <li><strong>Check daily:</strong> Review this page at least once per day</li>
                    <li><strong>Mobile access:</strong> The page is mobile-friendly if you need to review on-the-go</li>
                    <li><strong>Prioritize urgent:</strong> Review proposals with less time remaining first</li>
                    <li><strong>Quick decisions:</strong> Don\'t overthink - you can always course-correct later</li>
                    <li><strong>Delegate if needed:</strong> If you\'ll be unavailable for 12+ hours, let council know they can rely on auto-approval</li>
                </ul>

                <div class="help-warning">
                    <strong>Vacation/Absence:</strong> If you know you\'ll be away for an extended period, inform the council and admins. Auto-approval will handle proposals, but communication prevents confusion.
                </div>
            '
        ],
        [
            'title' => 'Decision-Making Guidelines',
            'content' => '
                <p>Your role is to be a <strong>gatekeeper</strong>, not a <strong>dictator</strong>. Here are guidelines for making approval decisions:</p>

                <p><strong>✅ APPROVE if:</strong></p>
                <ul>
                    <li>The proposal is clear and well-written</li>
                    <li>It\'s a legitimate council-level decision</li>
                    <li>You personally disagree but it\'s valid for council to decide</li>
                    <li>It follows proper format and guidelines</li>
                    <li>The timing is appropriate</li>
                </ul>

                <div class="help-success">
                    <strong>Important:</strong> Your approval doesn\'t mean you support the proposal\'s content - it means you believe it\'s worthy of a council vote. You\'ll get to vote on it yourself along with other council members.
                </div>

                <p><strong>❌ REJECT if:</strong></p>
                <ul>
                    <li>The proposal is vague or unclear</li>
                    <li>It\'s not actually a council-level decision</li>
                    <li>It\'s been recently voted on (avoid vote spam)</li>
                    <li>Timing is inappropriate (during major events, conflicts)</li>
                    <li>It\'s inappropriate, offensive, or violates server rules</li>
                    <li>It needs offline discussion before formal vote</li>
                </ul>

                <p><strong>When in doubt, approve:</strong> If you\'re unsure, it\'s generally better to let the council vote. Trust your council members to make the right decision.</p>

                <p><strong>Avoiding bias:</strong> Don\'t reject proposals just because you disagree with the content. Only reject for procedural/format reasons.</p>
            '
        ],
        [
            'title' => 'After Approval: Vote Lifecycle',
            'content' => '
                <p>Once you approve a proposal, here\'s what happens:</p>

                <p><strong>Immediate (0-5 minutes):</strong></p>
                <ul>
                    <li>Vote is created in the Discord bot database</li>
                    <li>All 7 council members receive DM notifications</li>
                    <li>24-hour countdown timer begins</li>
                    <li>Proposal is marked as "Approved" in the system</li>
                </ul>

                <p><strong>Voting Period (24 hours):</strong></p>
                <ul>
                    <li>Council members respond via DM: "vote: yes", "vote: no", or "vote: abstain"</li>
                    <li>Each vote is cryptographically sealed (cannot be changed)</li>
                    <li>Vote tracker shows how many members have voted</li>
                    <li>You can vote too! You\'re still a council member</li>
                </ul>

                <p><strong>Vote Finalization:</strong></p>
                <ul>
                    <li>Vote closes when all 7 members vote OR 24 hours pass</li>
                    <li>Results are calculated (yes/no/abstain counts)</li>
                    <li>Results posted to Discord vote channel</li>
                    <li>All council members receive DM with final results</li>
                    <li>Results recorded in website vote history</li>
                </ul>

                <div class="help-note">
                    <strong>Monitoring Votes:</strong> You can track active votes using the Discord bot command <code>/vote status</code> or by checking the votes channel.
                </div>
            '
        ],
        [
            'title' => 'Handling Special Situations',
            'content' => '
                <p><strong>Duplicate Proposals:</strong> If the same person submits the same proposal twice, reject the duplicate with a note: "Duplicate of proposal #123. Please delete the duplicate."</p>

                <p><strong>Personal Attacks:</strong> If a proposal contains attacks on individuals or alliances, reject with: "Proposals must be professional and constructive. Please rewrite without personal attacks."</p>

                <p><strong>Emergency Votes:</strong> If something is genuinely urgent (e.g., server crisis), approve immediately and note the urgency in Discord so council members prioritize it.</p>

                <p><strong>Poorly Timed Proposals:</strong> If the timing is bad (major event starting), you can either:
                    <ul>
                        <li>Reject with "Please resubmit after [event] concludes", OR</li>
                        <li>Contact the submitter on Discord to ask if they want to wait</li>
                    </ul>
                </p>

                <p><strong>Borderline Cases:</strong> If you\'re really unsure, you can:</p>
                <ul>
                    <li>Message the submitter on Discord for clarification</li>
                    <li>Ask for opinions in council chat</li>
                    <li>Approve it and trust council to vote appropriately</li>
                </ul>

                <div class="help-warning">
                    <strong>Never abuse the role:</strong> Don\'t reject proposals for personal/political reasons. Your role is procedural quality control, not censorship.
                </div>
            '
        ],
        [
            'title' => 'Communication Best Practices',
            'content' => '
                <p><strong>When Rejecting:</strong></p>
                <ul>
                    <li>Always provide specific, constructive feedback</li>
                    <li>Tell them exactly how to fix it</li>
                    <li>Be polite and professional</li>
                    <li>Encourage resubmission after fixes</li>
                </ul>

                <p><strong>When Approving:</strong></p>
                <ul>
                    <li>No message needed - system handles notifications</li>
                    <li>You can optionally DM the submitter to let them know</li>
                    <li>Consider posting in council chat: "Approved vote on [topic] - watch for DMs"</li>
                </ul>

                <p><strong>For Controversial Topics:</strong></p>
                <ul>
                    <li>Approve the vote (don\'t suppress it)</li>
                    <li>Post in council chat that it\'s going to vote</li>
                    <li>Encourage respectful, thoughtful voting</li>
                    <li>Let the council decide through democratic process</li>
                </ul>

                <div class="help-success">
                    <strong>Transparency:</strong> You can share your approval/rejection decisions in council chat to maintain transparency. This helps educate council members on what makes a good proposal.
                </div>
            '
        ],
        [
            'title' => 'Troubleshooting',
            'content' => '
                <p><strong>No proposals showing up:</strong> Great! That means there are no pending proposals. Check back later or refresh the page.</p>

                <p><strong>Can\'t approve/reject:</strong> Check your internet connection. Make sure you\'re still logged in (refresh if needed). Contact admin if the issue persists.</p>

                <p><strong>Approved but vote wasn\'t created:</strong> This is a critical bug. Contact admins immediately - likely a Discord bot integration issue.</p>

                <p><strong>Want to undo an approval:</strong> Once approved, the vote is created and cannot be cancelled. The vote will proceed normally. If you made a mistake, discuss with council in Discord.</p>

                <p><strong>Want to undo a rejection:</strong> Contact an admin. They can change the status back to "pending" so you can approve it.</p>

                <p><strong>Auto-approved while you were reviewing:</strong> If the 12-hour timer expires while you\'re reading a proposal, it may auto-approve before you click reject. Nothing you can do - the vote will proceed.</p>

                <p><strong>Time remaining showing wrong:</strong> Try refreshing the page. Countdown timers calculate from your local computer time.</p>
            '
        ],
        [
            'title' => 'Your Authority & Limits',
            'content' => '
                <p><strong>What you CAN do:</strong></p>
                <ul>
                    <li>Approve or reject any proposal</li>
                    <li>Require revisions before approval</li>
                    <li>Request offline discussion before voting</li>
                    <li>Set procedural standards for proposals</li>
                    <li>Communicate with submitters about improvements</li>
                </ul>

                <p><strong>What you CANNOT do:</strong></p>
                <ul>
                    <li>Censor proposals you personally disagree with</li>
                    <li>Cancel votes after approval</li>
                    <li>Override vote results (council decision is final)</li>
                    <li>Create votes without council member submission (unless you submit a proposal yourself)</li>
                    <li>Bypass the vote system for unilateral decisions</li>
                </ul>

                <div class="help-note">
                    <strong>Checks & Balances:</strong> Your role has the 12-hour auto-approval as a check against abuse. If you consistently reject legitimate proposals, council members can rely on auto-approval. All your actions are also logged in the audit system.
                </div>

                <p><strong>Remember:</strong> You\'re a facilitator of the council process, not a ruler. Your job is to ensure quality proposals reach a vote, not to control outcomes.</p>
            '
        ]
    ]
];
