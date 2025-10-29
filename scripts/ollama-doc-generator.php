#!/usr/bin/env php
<?php
/**
 * Ollama Documentation Generator
 *
 * Automatically generates documentation and changelog entries using local Ollama LLM
 * Triggered by git hooks to analyze commits and update docs
 *
 * Documentation:
 * - Setup Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/docs/OLLAMA_AUTOMATION.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.0.0
 * @date 2025-10-29
 *
 * Usage:
 *   php ollama-doc-generator.php [mode] [options]
 *
 * Modes:
 *   post-commit    - Generate changelog from last commit (default)
 *   changelog      - Generate changelog entry
 *   commit-msg     - Enhance commit message
 *   code-docs      - Generate code documentation
 *   --dry-run      - Preview without writing
 *   --help         - Show this help
 *
 * Examples:
 *   php ollama-doc-generator.php post-commit
 *   php ollama-doc-generator.php changelog --dry-run
 *   SKIP_OLLAMA=1 git commit  # Skip automation
 */

// Configuration
const CONFIG_FILE = __DIR__ . '/ollama-config.json';
const OLLAMA_URL = 'http://localhost:11434';
const DEFAULT_MODEL = 'qwen2.5-coder:14b';
const CHANGELOG_PATH = __DIR__ . '/../docs/CHANGELOG.md';
const VERSION_PATH = __DIR__ . '/../version.json';
const TIMEOUT = 30; // seconds

// Check if automation is disabled
if (getenv('SKIP_OLLAMA') === '1') {
    echo "ℹ️  Ollama automation skipped (SKIP_OLLAMA=1)\n";
    exit(0);
}

// Parse arguments
$mode = $argv[1] ?? 'post-commit';
$dryRun = in_array('--dry-run', $argv);

if ($mode === '--help' || $mode === '-h') {
    showHelp();
    exit(0);
}

// Load configuration
$config = loadConfig();

if (!$config['enabled']) {
    echo "ℹ️  Ollama automation disabled in config\n";
    exit(0);
}

// Main execution
try {
    echo "🤖 Ollama Documentation Generator\n";
    echo "   Model: {$config['model']}\n";
    echo "   Mode: $mode\n";
    if ($dryRun) echo "   Dry run: Yes\n";
    echo "\n";

    // Check Ollama is running
    if (!checkOllamaRunning($config['ollama_url'])) {
        echo "❌ Ollama is not running. Start with: ollama serve\n";
        exit(0); // Don't block git operations
    }

    // Execute based on mode
    switch ($mode) {
        case 'post-commit':
            handlePostCommit($config, $dryRun);
            break;
        case 'changelog':
            generateChangelog($config, $dryRun);
            break;
        case 'commit-msg':
            enhanceCommitMessage($config, $dryRun);
            break;
        case 'code-docs':
            generateCodeDocs($config, $dryRun);
            break;
        default:
            echo "❌ Unknown mode: $mode\n";
            showHelp();
            exit(1);
    }

    echo "\n✅ Done!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Continuing without automation...\n";
    exit(0); // Don't block git operations
}

// ============================================================================
// Functions
// ============================================================================

/**
 * Load configuration from file or use defaults
 */
function loadConfig(): array {
    $defaults = [
        'enabled' => true,
        'model' => DEFAULT_MODEL,
        'ollama_url' => OLLAMA_URL,
        'temperature' => 0.3,
        'max_tokens' => 500,
        'auto_commit' => false,
        'update_changelog' => true,
        'update_code_docs' => false
    ];

    if (file_exists(CONFIG_FILE)) {
        $config = json_decode(file_get_contents(CONFIG_FILE), true);
        return array_merge($defaults, $config);
    }

    return $defaults;
}

/**
 * Check if Ollama is running
 */
function checkOllamaRunning(string $url): bool {
    $ch = curl_init("$url/api/tags");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}

/**
 * Send prompt to Ollama and get response
 */
function queryOllama(string $url, string $model, string $prompt, float $temperature, int $maxTokens): string {
    $data = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => $temperature,
            'num_predict' => $maxTokens
        ]
    ];

    $ch = curl_init("$url/api/generate");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, TIMEOUT);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Ollama request failed with HTTP $httpCode");
    }

    $result = json_decode($response, true);
    return $result['response'] ?? throw new Exception("Invalid Ollama response");
}

/**
 * Get last commit information
 */
function getLastCommit(): array {
    exec('git log -1 --pretty=format:"%H|%s|%b"', $output);
    $parts = explode('|', $output[0] ?? '', 3);

    exec('git diff-tree --no-commit-id --name-status -r HEAD', $files);
    exec('git show HEAD --format="" --unified=3', $diff);

    return [
        'hash' => $parts[0] ?? '',
        'subject' => $parts[1] ?? '',
        'body' => $parts[2] ?? '',
        'files' => $files,
        'diff' => implode("\n", $diff)
    ];
}

/**
 * Handle post-commit hook
 */
function handlePostCommit(array $config, bool $dryRun): void {
    echo "📝 Analyzing last commit...\n";

    $commit = getLastCommit();

    if (empty($commit['hash'])) {
        echo "⚠️  No commits found\n";
        return;
    }

    echo "   Commit: " . substr($commit['hash'], 0, 7) . "\n";
    echo "   Message: {$commit['subject']}\n";
    echo "   Files: " . count($commit['files']) . "\n\n";

    // Generate changelog entry if enabled
    if ($config['update_changelog']) {
        echo "📋 Generating changelog entry...\n";
        generateChangelogFromCommit($config, $commit, $dryRun);
    }

    // Generate code documentation if enabled
    if ($config['update_code_docs']) {
        echo "📖 Generating code documentation...\n";
        generateCodeDocsFromCommit($config, $commit, $dryRun);
    }
}

/**
 * Generate changelog entry from commit
 */
function generateChangelogFromCommit(array $config, array $commit, bool $dryRun): void {
    $prompt = <<<PROMPT
You are a technical documentation expert analyzing a git commit. Generate a concise changelog entry.

**Commit Message:**
{$commit['subject']}

**Files Changed:**
{$commit['files'][0]}

**Code Changes:**
{$commit['diff']}

**Task:** Generate a changelog entry in this markdown format:

### Added
- New features (if any)

### Changed
- Modifications (if any)

### Fixed
- Bug fixes (if any)

Keep it concise, professional, and user-focused. Only include sections that apply.
Respond with ONLY the markdown changelog entry, no extra text.
PROMPT;

    echo "   Querying Ollama ({$config['model']})...\n";
    $startTime = microtime(true);

    try {
        $entry = queryOllama(
            $config['ollama_url'],
            $config['model'],
            $prompt,
            $config['temperature'],
            $config['max_tokens']
        );

        $duration = round(microtime(true) - $startTime, 2);
        echo "   ✓ Generated in {$duration}s\n\n";

        echo "   Preview:\n";
        echo "   " . str_repeat("-", 60) . "\n";
        $lines = explode("\n", trim($entry));
        foreach ($lines as $line) {
            echo "   $line\n";
        }
        echo "   " . str_repeat("-", 60) . "\n\n";

        if ($dryRun) {
            echo "   (Dry run - not writing to file)\n";
        } else {
            updateChangelog($entry, $commit);
        }

    } catch (Exception $e) {
        echo "   ❌ Failed: " . $e->getMessage() . "\n";
    }
}

/**
 * Generate changelog (manual mode)
 */
function generateChangelog(array $config, bool $dryRun): void {
    echo "📋 Manual changelog generation not yet implemented\n";
    echo "   Use: php ollama-doc-generator.php post-commit\n";
}

/**
 * Enhance commit message
 */
function enhanceCommitMessage(array $config, bool $dryRun): void {
    echo "💬 Commit message enhancement not yet implemented\n";
}

/**
 * Generate code documentation
 */
function generateCodeDocs(array $config, bool $dryRun): void {
    echo "📖 Code documentation generation not yet implemented\n";
}

/**
 * Generate code docs from commit
 */
function generateCodeDocsFromCommit(array $config, array $commit, bool $dryRun): void {
    echo "   ⚠️  Code docs from commit not yet implemented\n";
}

/**
 * Update CHANGELOG.md with new entry
 */
function updateChangelog(string $entry, array $commit): void {
    $changelogPath = CHANGELOG_PATH;

    if (!file_exists($changelogPath)) {
        echo "   ⚠️  CHANGELOG.md not found at $changelogPath\n";
        return;
    }

    // Read current changelog
    $changelog = file_get_contents($changelogPath);

    // Get current version from version.json
    $versionData = json_decode(file_get_contents(VERSION_PATH), true);
    $currentVersion = $versionData['version'] ?? '3.3.0';

    // Parse version and increment patch version
    $versionParts = explode('.', $currentVersion);
    $versionParts[2] = (int)$versionParts[2] + 1;
    $newVersion = implode('.', $versionParts);

    // Generate changelog entry header
    $date = date('Y-m-d');
    $commitHash = substr($commit['hash'], 0, 7);

    $newEntry = "\n## [$newVersion] - $date\n\n";
    $newEntry .= "**Commit:** $commitHash - {$commit['subject']}\n\n";
    $newEntry .= $entry . "\n\n";
    $newEntry .= "---\n";

    // Find insertion point (after the header section, before first version entry)
    $lines = explode("\n", $changelog);
    $insertIndex = 0;
    $inHeader = true;

    foreach ($lines as $index => $line) {
        // Look for first version header (## [X.Y.Z])
        if (preg_match('/^## \[[\d\.]+\]/', $line)) {
            $insertIndex = $index;
            break;
        }
    }

    if ($insertIndex === 0) {
        echo "   ⚠️  Could not find insertion point in CHANGELOG.md\n";
        return;
    }

    // Insert new entry
    array_splice($lines, $insertIndex, 0, explode("\n", $newEntry));
    $updatedChangelog = implode("\n", $lines);

    // Write back to file
    if (file_put_contents($changelogPath, $updatedChangelog)) {
        echo "   ✅ Updated CHANGELOG.md with version $newVersion\n";
        echo "   📝 Location: $changelogPath\n";
    } else {
        echo "   ❌ Failed to write to CHANGELOG.md\n";
    }
}

/**
 * Show help message
 */
function showHelp(): void {
    echo <<<HELP
Ollama Documentation Generator v1.0.0

Automatically generates documentation and changelog entries using local Ollama LLM.

USAGE:
    php ollama-doc-generator.php [mode] [options]

MODES:
    post-commit    Generate changelog from last commit (default)
    changelog      Generate changelog entry manually
    commit-msg     Enhance commit message
    code-docs      Generate code documentation
    --help         Show this help

OPTIONS:
    --dry-run      Preview changes without writing files

ENVIRONMENT:
    SKIP_OLLAMA=1  Disable automation for this commit

EXAMPLES:
    # Run after commit (git hook)
    php ollama-doc-generator.php post-commit

    # Preview without writing
    php ollama-doc-generator.php post-commit --dry-run

    # Skip automation
    SKIP_OLLAMA=1 git commit -m "message"

SETUP:
    1. Install Ollama: https://ollama.ai
    2. Pull model: ollama pull qwen2.5-coder:14b
    3. Install git hook: see docs/OLLAMA_AUTOMATION.md

CONFIGURATION:
    Edit scripts/ollama-config.json to customize behavior

MORE INFO:
    docs/OLLAMA_AUTOMATION.md

HELP;
}
