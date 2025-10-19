<?php
/**
 * Changelog Viewer
 *
 * Displays the project changelog from docs/CHANGELOG.md with Markdown rendering
 *
 * @version 1.0.0
 * @date 2025-10-19
 */

// Check if admin init is needed
$is_admin_view = true;
if (file_exists(__DIR__ . '/config.php')) {
    define('ADMIN_INIT', true);
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/jwt.php';

    try {
        $user = require_jwt_session();
    } catch (Exception $e) {
        // Public access allowed for changelog
        $user = (object)['aud' => 'guest'];
        $is_admin_view = false;
    }
}

// Load version data
$version_file = __DIR__ . '/../version.json';
$version_data = file_exists($version_file) ? json_decode(file_get_contents($version_file), true) : null;
$current_version = $version_data['version'] ?? '3.0.0';
$release_date = $version_data['releaseDate'] ?? '2025-10-16';

// Load changelog
$changelog_path = __DIR__ . '/../docs/CHANGELOG.md';
$changelog_content = file_exists($changelog_path) ? file_get_contents($changelog_path) : '';

/**
 * Simple Markdown to HTML converter
 * Handles common Markdown syntax for changelog display
 */
function markdown_to_html($markdown) {
    // Escape HTML first
    $html = htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8');

    // Headers (must be done before links to avoid breaking anchor tags)
    $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^## (.+)$/m', '<h2 id="' . '">$1</h2>', $html);
    $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);

    // Generate IDs for h2 headers (for anchor links)
    $html = preg_replace_callback('/<h2 id="">(.+?)<\/h2>/', function($matches) {
        $id = strtolower(preg_replace('/[^a-z0-9]+/', '-', $matches[1]));
        $id = trim($id, '-');
        return '<h2 id="' . $id . '">' . $matches[1] . '</h2>';
    }, $html);

    // Bold
    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);

    // Italic
    $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);

    // Code blocks (```)
    $html = preg_replace('/```(\w+)?\n(.*?)\n```/s', '<pre><code>$2</code></pre>', $html);

    // Inline code
    $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

    // Links [text](url)
    $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);

    // Unordered lists
    $html = preg_replace_callback('/((?:^[\*\-\+] .+$\n?)+)/m', function($matches) {
        $items = preg_replace('/^[\*\-\+] (.+)$/m', '<li>$1</li>', $matches[1]);
        return '<ul>' . $items . '</ul>';
    }, $html);

    // Ordered lists
    $html = preg_replace_callback('/((?:^\d+\. .+$\n?)+)/m', function($matches) {
        $items = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $matches[1]);
        return '<ol>' . $items . '</ol>';
    }, $html);

    // Checkboxes
    $html = str_replace('- [ ]', '<input type="checkbox" disabled>', $html);
    $html = str_replace('- [x]', '<input type="checkbox" checked disabled>', $html);
    $html = str_replace('- [X]', '<input type="checkbox" checked disabled>', $html);

    // Horizontal rules
    $html = preg_replace('/^---$/m', '<hr>', $html);

    // Blockquotes
    $html = preg_replace('/^&gt; (.+)$/m', '<blockquote>$1</blockquote>', $html);

    // Paragraphs (lines separated by blank lines)
    $paragraphs = preg_split('/\n\n+/', $html);
    $html = '';
    foreach ($paragraphs as $para) {
        $para = trim($para);
        if (empty($para)) continue;

        // Don't wrap if already wrapped in block element
        if (preg_match('/^<(h[1-6]|ul|ol|pre|blockquote|hr|table|div)/', $para)) {
            $html .= $para . "\n\n";
        } else {
            $html .= '<p>' . $para . '</p>' . "\n\n";
        }
    }

    // Tables (basic support)
    $html = preg_replace_callback('/(\|.+\|\n)+/', function($matches) {
        $rows = explode("\n", trim($matches[0]));
        $table = '<table class="changelog-table"><thead>';

        foreach ($rows as $i => $row) {
            if (empty(trim($row))) continue;

            // Skip separator row (|---|---|)
            if (preg_match('/^\|[\s\-:]+\|$/', $row)) {
                $table .= '</thead><tbody>';
                continue;
            }

            $cells = array_map('trim', explode('|', trim($row, '|')));
            $tag = ($i === 0) ? 'th' : 'td';
            $table .= '<tr>';
            foreach ($cells as $cell) {
                $table .= "<$tag>" . trim($cell) . "</$tag>";
            }
            $table .= '</tr>';
        }

        $table .= '</tbody></table>';
        return $table;
    }, $html);

    return $html;
}

$changelog_html = markdown_to_html($changelog_content);

// Include header if in admin context
if ($is_admin_view && file_exists(__DIR__ . '/includes/header.php')):
    include __DIR__ . '/includes/header.php';
else:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changelog - Server 1586 v<?php echo htmlspecialchars($current_version); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f7fa;
        }

        .header-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 1rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-banner h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .version-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .changelog-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        <?php endif; ?>

        /* Changelog-specific styles */
        .changelog-content h1 {
            color: #2c3e50;
            border-bottom: 3px solid #667eea;
            padding-bottom: 0.5rem;
            margin: 2rem 0 1rem 0;
        }

        .changelog-content h2 {
            color: #34495e;
            margin: 2rem 0 1rem 0;
            padding: 0.5rem 0;
            border-bottom: 2px solid #ecf0f1;
        }

        .changelog-content h3 {
            color: #546e7a;
            margin: 1.5rem 0 0.75rem 0;
        }

        .changelog-content h4 {
            color: #607d8b;
            margin: 1rem 0 0.5rem 0;
        }

        .changelog-content p {
            margin: 1rem 0;
            line-height: 1.8;
        }

        .changelog-content ul,
        .changelog-content ol {
            margin: 1rem 0 1rem 2rem;
            line-height: 1.8;
        }

        .changelog-content li {
            margin: 0.5rem 0;
        }

        .changelog-content code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #e83e8c;
        }

        .changelog-content pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 1rem;
            border-radius: 5px;
            overflow-x: auto;
            margin: 1rem 0;
        }

        .changelog-content pre code {
            background: none;
            color: inherit;
            padding: 0;
        }

        .changelog-content blockquote {
            border-left: 4px solid #667eea;
            padding-left: 1rem;
            margin: 1rem 0;
            color: #546e7a;
            font-style: italic;
        }

        .changelog-content hr {
            border: none;
            border-top: 2px solid #ecf0f1;
            margin: 2rem 0;
        }

        .changelog-content a {
            color: #667eea;
            text-decoration: none;
        }

        .changelog-content a:hover {
            text-decoration: underline;
        }

        .changelog-content strong {
            color: #2c3e50;
            font-weight: 600;
        }

        .changelog-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        .changelog-content th,
        .changelog-content td {
            padding: 0.75rem;
            text-align: left;
            border: 1px solid #e0e0e0;
        }

        .changelog-content th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .changelog-content tr:nth-child(even) {
            background: #fafafa;
        }

        .changelog-content input[type="checkbox"] {
            margin-right: 0.5rem;
        }

        .back-link {
            display: inline-block;
            margin: 1rem 0;
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .back-link:hover {
            background: #5568d3;
            text-decoration: none;
        }

        .toc {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 5px;
            margin: 2rem 0;
            border-left: 4px solid #667eea;
        }

        .toc h3 {
            margin-top: 0;
        }

        @media (max-width: 768px) {
            .header-banner h1 {
                font-size: 1.5rem;
            }

            .changelog-content {
                padding: 1rem;
            }

            .changelog-content h1 {
                font-size: 1.75rem;
            }

            .changelog-content h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php if (!$is_admin_view): ?>
    <div class="header-banner">
        <h1>📋 Server 1586 Changelog</h1>
        <div class="version-badge">
            Version <?php echo htmlspecialchars($current_version); ?> - <?php echo htmlspecialchars($release_date); ?>
        </div>
    </div>

    <div class="container">
        <a href="../index.html" class="back-link">← Back to Main Site</a>
        <a href="dashboard.php" class="back-link" style="margin-left: 1rem;">🔧 Admin Dashboard</a>
        <?php endif; ?>

        <div class="changelog-content">
            <?php echo $changelog_html; ?>
        </div>

        <?php if (!$is_admin_view): ?>
    </div>
    <?php endif; ?>

<?php if ($is_admin_view && file_exists(__DIR__ . '/includes/footer.php')):
    include __DIR__ . '/includes/footer.php';
else:
?>
</body>
</html>
<?php endif; ?>
