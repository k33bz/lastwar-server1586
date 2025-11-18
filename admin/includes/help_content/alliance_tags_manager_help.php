<?php
/**
 * Help Content: Alliance Tags Manager
 */

return [
    'title' => 'Alliance Tags Manager Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '
                <p>Alliance Tags Manager allows you to control which alliance tags are displayed on the public website and in which order they appear.</p>

                <div class="help-note">
                    <strong>Purpose:</strong> Curate the public-facing alliance list by hiding inactive alliances, setting display order, and managing visibility.
                </div>
            '
        ],
        [
            'title' => 'Managing Tag Visibility',
            'content' => '
                <p><strong>Visibility Options:</strong></p>

                <p><strong>Visible:</strong> Alliance appears on the public website</p>
                <ul>
                    <li>Shown in alliance rankings</li>
                    <li>Appears in search results</li>
                    <li>Included in council displays</li>
                </ul>

                <p><strong>Hidden:</strong> Alliance is excluded from public view</p>
                <ul>
                    <li>Still exists in database</li>
                    <li>Can be edited in admin panel</li>
                    <li>Won\'t appear on public website</li>
                </ul>

                <div class="help-note">
                    <strong>When to Hide:</strong> Hide alliances that are disbanded, inactive, or merged with others to keep the public website clean and current.
                </div>
            '
        ],
        [
            'title' => 'Display Order',
            'content' => '
                <p>Control the order in which alliances appear on the website:</p>

                <p><strong>Automatic Ordering (Recommended):</strong></p>
                <ul>
                    <li>Alliances sorted by power ranking</li>
                    <li>Highest power appears first</li>
                    <li>Updates automatically when power changes</li>
                </ul>

                <p><strong>Manual Ordering:</strong></p>
                <ul>
                    <li>Set custom display order numbers</li>
                    <li>Lower numbers appear first</li>
                    <li>Useful for featuring specific alliances</li>
                </ul>

                <div class="help-success">
                    <strong>Tip:</strong> Use automatic ordering for rankings pages and manual ordering for featured alliance displays.
                </div>
            '
        ],
        [
            'title' => 'Bulk Operations',
            'content' => '
                <p>Manage multiple alliances at once:</p>

                <p><strong>Show All:</strong> Make all alliances visible</p>
                <p><strong>Hide All:</strong> Hide all alliances (then selectively show active ones)</p>
                <p><strong>Reset Order:</strong> Return to automatic power-based ordering</p>

                <div class="help-warning">
                    <strong>Bulk Actions:</strong> Bulk operations affect ALL alliances. Use carefully and verify results after applying.
                </div>
            '
        ],
        [
            'title' => 'Best Practices',
            'content' => '
                <p><strong>Regular Maintenance:</strong></p>
                <ul>
                    <li>Review alliance visibility monthly</li>
                    <li>Hide disbanded or merged alliances</li>
                    <li>Update when alliances change names</li>
                </ul>

                <p><strong>Public Website Quality:</strong></p>
                <ul>
                    <li>Only show active alliances</li>
                    <li>Keep the list current and accurate</li>
                    <li>Remove duplicate or test alliances</li>
                </ul>
            '
        ]
    ]
];
