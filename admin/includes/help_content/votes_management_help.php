<?php
/**
 * Help Content: Votes Management
 */

return [
    'title' => 'Votes Management Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>Votes Management provides a centralized view of all council votes - past, present, and future. View vote results, participation rates, and detailed voting records.</p>

                <div class="help-note">
                    <strong>Purpose:</strong> Track council decision-making, monitor participation, and review historical votes for accountability.
                </div>
            '
        ],
        [
            'title' => 'Vote Lifecycle',
            'content' => '
                <p><strong>Proposal Phase:</strong></p>
                <ul>
                    <li>Council member (R5/R4/APE) submits proposal</li>
                    <li>President reviews and approves/rejects</li>
                    <li>Or auto-approved after 12 hours</li>
                </ul>

                <p><strong>Active Voting:</strong></p>
                <ul>
                    <li>Vote is published to Discord</li>
                    <li>Council members receive DM notifications</li>
                    <li>24-hour voting period begins</li>
                    <li>Members submit votes via Discord</li>
                </ul>

                <p><strong>Finalization:</strong></p>
                <ul>
                    <li>Vote closes after 24 hours or all votes submitted</li>
                    <li>Results calculated automatically</li>
                    <li>Results posted to Discord channel</li>
                    <li>All voters receive result DM</li>
                </ul>
            '
        ],
        [
            'title' => 'Viewing Vote Details',
            'content' => '
                <p>Click any vote to see:</p>

                <p><strong>Vote Information:</strong></p>
                <ul>
                    <li>Title and description</li>
                    <li>Created by (requester)</li>
                    <li>Creation and deadline dates</li>
                    <li>Status (active, passed, failed)</li>
                </ul>

                <p><strong>Voting Records:</strong></p>
                <ul>
                    <li>Who voted and their choices</li>
                    <li>Vote timestamps</li>
                    <li>Participation rate</li>
                    <li>Who hasn\'t voted yet (for active votes)</li>
                </ul>

                <p><strong>Results:</strong></p>
                <ul>
                    <li>Yes/No/Abstain counts</li>
                    <li>Percentage breakdowns</li>
                    <li>Final outcome</li>
                </ul>
            '
        ],
        [
            'title' => 'Vote Filters',
            'content' => '
                <p>Filter votes to find specific records:</p>

                <p><strong>By Status:</strong></p>
                <ul>
                    <li>Active - Currently accepting votes</li>
                    <li>Passed - Majority voted yes</li>
                    <li>Failed - Majority voted no or insufficient participation</li>
                    <li>Pending - Awaiting president approval</li>
                </ul>

                <p><strong>By Date:</strong></p>
                <ul>
                    <li>Current week</li>
                    <li>Last 30 days</li>
                    <li>Custom date range</li>
                </ul>

                <p><strong>By Creator:</strong></p>
                <ul>
                    <li>View votes from specific alliance</li>
                    <li>Track proposal patterns</li>
                </ul>
            '
        ],
        [
            'title' => 'Understanding Results',
            'content' => '
                <p><strong>Passing Criteria:</strong></p>
                <ul>
                    <li>Simple majority (>50%) of Yes votes</li>
                    <li>Abstentions don\'t count toward total</li>
                    <li>Minimum participation requirements may apply</li>
                </ul>

                <p><strong>Vote Outcomes:</strong></p>
                <ul>
                    <li><strong>Passed:</strong> Majority voted yes</li>
                    <li><strong>Failed:</strong> Majority voted no</li>
                    <li><strong>Insufficient Participation:</strong> Too few votes</li>
                </ul>

                <div class="help-note">
                    <strong>Transparency:</strong> All vote records are permanent and cannot be edited after finalization, ensuring accountability.
                </div>
            '
        ]
    ]
];
