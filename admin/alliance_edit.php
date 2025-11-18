<?php
/**
 * Alliance Edit Page
 * Display-only page for editing individual alliance information
 * R4/R5 users can edit alliance details
 *
 * @version 2.0.0
 * @date 2025-10-15
 */

// Require JWT authentication
require_once 'jwt.php';

$user = require_jwt_session();

// Set page title for header
$page_title = __('pages.alliance_edit.title');

// Create proper user token for role checking
$user_token = (object)[
    'sub' => $user->sub,
    'aud' => $user->aud,
    'alliances' => $user->alliances ?? []
];

// Handle edit action
$tag = $_GET['tag'] ?? null;
$show_all = !$tag; // Show all alliances if no specific tag provided

if ($tag) {
    // Check permission for specific alliance
    if (!has_alliance_access($user_token, $tag)) {
        http_response_code(403);
        die('Access denied. You do not have permission to edit this alliance.');
    }
} else {
    // For viewing all alliances, only admins with * access should see everything
    // R4/R5 users should only see their assigned alliances
    if (!($user_token->aud === 'admin' && in_array('*', $user_token->alliances))) {
        // Redirect R4/R5 users to their first alliance if they only have one
        if (count($user_token->alliances) === 1 && $user_token->alliances[0] !== '*') {
            header('Location: alliance_edit.php?tag=' . urlencode($user_token->alliances[0]));
            exit();
        }
        // If they have multiple alliances, they can see the list (filtered below)
    }
}

// Load alliance helper
require_once 'includes/alliance_helper.php';

// Load alliances data using helper
$alliances_array = AllianceHelper::loadAlliances();

if ($show_all) {
    // Show all alliances - we'll handle this in the HTML section
    $alliance = null;
    $index = -1;
} else {
    // Find specific alliance using helper
    $result = AllianceHelper::getAllianceByTag($tag);
    
    if (!$result) {
        die('Alliance not found.');
    }
    
    $alliance = $result['alliance'];
    $index = $result['index'];
}

// Single alliance editing setup
if (!$show_all) {
    // Load all available rule versions
    $amendments_file = __DIR__ . '/../data/amendments.json';
    $amendments = file_exists($amendments_file) ? read_json_file($amendments_file) : [];

    // Build list of all versions (start with 1.0, then add all amendment versions)
    $all_versions = ['1.0'];
    if (!empty($amendments)) {
        $amendment_versions = array_column($amendments, 'version');
        $all_versions = array_merge($all_versions, $amendment_versions);
        // Remove duplicates and sort
        $all_versions = array_unique($all_versions);
        usort($all_versions, 'version_compare');
    }

    // Get the highest/latest version as default
    $current_rules_version = end($all_versions);
    reset($all_versions);
}

// Helper functions
function get_r5_name($r5_data) {
    if (is_string($r5_data)) return $r5_data;
    if (is_array($r5_data) && isset($r5_data['name'])) return $r5_data['name'];
    return '';
}

// Helper function to get signature status
function get_signature_status($alliance, $version) {
    if (!isset($alliance['r5History']) || !is_array($alliance['r5History'])) {
        return null;
    }
    foreach ($alliance['r5History'] as $r5) {
        if ($r5['current'] ?? false) {
            if (isset($r5['signatures'])) {
                foreach ($r5['signatures'] as $sig) {
                    if ($sig['version'] === $version) {
                        return $sig;
                    }
                }
            }
        }
    }
    return null;
}

// Single alliance variable extraction
if (!$show_all) {
    // Extract current values
    $r5_name = get_r5_name($alliance['r5'] ?? null);
    $r5_game_id = is_array($alliance['r5']) ? ($alliance['r5']['gameId'] ?? '') : '';
    $r5_discord_id = is_array($alliance['r5']) ? ($alliance['r5']['discordId'] ?? '') : '';
    $discord = $alliance['discord'] ?? [];
    $contact = $alliance['contact'] ?? [];
    $info = $alliance['info'] ?? [];
    $requirements = $info['requirements'] ?? [];
    $can_sign = can_sign_rules($user_token, $tag);
    $current_signature = get_signature_status($alliance, $current_rules_version);

    // Check if user is R4 (cannot edit certain fields)
    $is_r4_only = (strtolower($user_token->aud) === 'r4');
}

// Include shared header
include 'includes/header.php';

if ($show_all) {
    // Show all alliances list
    ?>
    <div class="page-header">
        <h1 class="page-title">✏️ Alliance Management</h1>
        <p class="page-description">Select an alliance to edit</p>
    </div>
    
    <div class="container">
        <?php
        // Calculate ranks using helper
        $alliance_ranks = AllianceHelper::calculateRanks($alliances_array);
        ?>
        
        <div class="alliances-grid">
            <?php foreach ($alliances_array as $alliance_item): ?>
                <?php 
                $can_edit = has_alliance_access($user_token, $alliance_item['tag'] ?? '');
                if (!$can_edit) continue; // Skip alliances user can't edit
                
                $alliance_tag = $alliance_item['tag'] ?? 'Unknown';
                $alliance_rank = $alliance_ranks[$alliance_tag] ?? '?';
                ?>
                <div class="alliance-card">
                    <div class="alliance-header">
                        <h3><?= htmlspecialchars($alliance_tag) ?></h3>
                        <div class="alliance-stats">
                            <span class="alliance-rank">#<?= $alliance_rank ?></span>
                            <span class="alliance-power"><?= number_format($alliance_item['power'] ?? 0) ?></span>
                        </div>
                    </div>
                    <div class="alliance-info">
                        <p><strong>R5:</strong> <?= htmlspecialchars(is_array($alliance_item['r5'] ?? null) ? ($alliance_item['r5']['name'] ?? 'Unknown') : ($alliance_item['r5'] ?? 'Unknown')) ?></p>
                        <?php if (!empty($alliance_item['info']['description'])): ?>
                            <p class="description"><?= htmlspecialchars(substr($alliance_item['info']['description'], 0, 100)) ?><?= strlen($alliance_item['info']['description']) > 100 ? '...' : '' ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="alliance-actions">
                        <a href="alliance_edit.php?tag=<?= urlencode($alliance_tag) ?>" class="btn btn-primary">
                            <span class="btn-icon">✏️</span>
                            Edit Alliance
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <style>
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .alliances-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .alliance-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .alliance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .alliance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .alliance-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.25rem;
        }
        
        .alliance-stats {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .alliance-rank {
            background: #f39c12;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .alliance-power {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .alliance-info p {
            margin: 0.5rem 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .alliance-info .description {
            font-style: italic;
            color: #495057;
        }
        
        .alliance-actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }
        
        .btn-icon {
            font-size: 1rem;
        }
        
        @media (max-width: 768px) {
            .alliances-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    
    <?php
    include 'includes/footer.php';
    exit();
}

// Continue with single alliance editing
?>

<div class="page-header">
    <h1 class="page-title">✏️ Edit Alliance: <?= htmlspecialchars($alliance['tag']) ?></h1>
    <p class="page-description">Edit alliance information and manage settings</p>
</div>

<div class="container">
    <style>
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        h2 {
            color: #2c3e50;
            font-size: 18px;
            margin: 25px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        .info-box {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
        }
        .info-box p {
            margin: 5px 0;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        label small {
            font-weight: normal;
            color: #666;
        }
        input[type="text"], input[type="number"], input[type="url"], textarea, select {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        textarea {
            min-height: 80px;
            resize: vertical;
        }
        input[type="checkbox"] {
            margin-right: 8px;
        }
        .checkbox-label {
            display: inline-flex;
            align-items: center;
            font-weight: normal;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        button, .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #666;
            color: white;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        .signature-notice {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .signature-section {
            background: #f8fff9;
            border: 2px solid #28a745;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .signature-section.unsigned {
            background: #fff8f0;
            border-color: #ffc107;
        }
        .signature-info {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .signature-info p {
            margin: 5px 0;
            color: #2c3e50;
        }
        .version-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 600;
        }
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }
        .r4-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }
        .r4-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        .r4-info {
            flex: 1;
        }
        .r4-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        .r4-details {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .r4-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        .r4-badge.can-vote {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .r4-badge.role {
            background: #e7f1ff;
            color: #004085;
            border: 1px solid #b8daff;
        }
        .r4-actions {
            display: flex;
            gap: 0.5rem;
        }
        .r4-actions button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .r4-actions .btn-edit {
            background: #667eea;
            color: white;
        }
        .r4-actions .btn-edit:hover {
            background: #5a67d8;
        }
        .r4-actions .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .r4-actions .btn-delete:hover {
            background: #c0392b;
        }
    </style>

    <div id="success-message" class="success-message"></div>
    <div id="error-message" class="error-message"></div>

    <div class="info-box">
        <p><strong>Rank:</strong> <?= htmlspecialchars($alliance['rank'] ?? 'N/A') ?></p>
        <p><strong>Power:</strong> <?= isset($alliance['power']) ? number_format($alliance['power']) : 'N/A' ?></p>
        <p><strong>Your Role:</strong> <?= htmlspecialchars(strtoupper($user_token->aud)) ?></p>
        <?php if ($user_token->aud === 'r4' && !empty($user_token->alliances)): ?>
            <p><strong>Alliance Access:</strong> <?= implode(', ', $user_token->alliances) ?></p>
        <?php endif; ?>
    </div>

    <form id="alliance-form">
        <input type="hidden" name="tag" value="<?= htmlspecialchars($tag) ?>">
        
        <h2>Basic Information</h2>

        <div class="form-group">
            <label>Alliance Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($alliance['name'] ?? '') ?>" required<?= $is_r4_only ? ' readonly style="background: #f0f0f0; cursor: not-allowed;"' : '' ?>>
        </div>

        <!-- Rules Signature Section -->
        <?php if ($can_sign): ?>
        <div class="signature-section <?= $current_signature ? '' : 'unsigned' ?>">
            <h2 style="margin: 0 0 15px 0; border: none; padding: 0;">
                <?= $current_signature ? '✓ ' : '' ?>Server Rules Signature
                <span class="version-badge">Latest: Version <?= htmlspecialchars($current_rules_version) ?></span>
            </h2>

            <?php if ($current_signature): ?>
                <div class="signature-info">
                    <p><strong>Status:</strong> ✓ Latest Version Signed</p>
                    <p><strong>Signed by:</strong> <?= htmlspecialchars($current_signature['signedBy']) ?></p>
                    <p><strong>Date:</strong> <?= htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($current_signature['signedAt']))) ?></p>
                    <?php if (!empty($current_signature['notes'])): ?>
                        <p><strong>Notes:</strong> <?= htmlspecialchars($current_signature['notes']) ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p style="color: #856404; margin: 10px 0;"><strong>⚠ Not Signed</strong> - This alliance has not yet signed version <?= htmlspecialchars($current_rules_version) ?> of the server rules.</p>
            <?php endif; ?>

            <div class="form-group" style="margin-top: 20px;">
                <label>Version to Sign</label>
                <select name="signature_version" id="signature_version" onchange="updateSignatureStatus()">
                    <?php
                    // Reverse order to show newest first
                    $reversed_versions = array_reverse($all_versions);
                    foreach ($reversed_versions as $version):
                        $version_signature = get_signature_status($alliance, $version);
                        $is_signed = ($version_signature !== null);
                    ?>
                        <option value="<?= htmlspecialchars($version) ?>"
                                <?= $version === $current_rules_version ? 'selected' : '' ?>
                                data-signed="<?= $is_signed ? '1' : '0' ?>">
                            Version <?= htmlspecialchars($version) ?><?= $is_signed ? ' (Already Signed)' : '' ?><?= $version === $current_rules_version ? ' (Latest)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>
                    Signature Notes
                    <small>(Optional)</small>
                </label>
                <textarea name="signature_notes" id="signature_notes" placeholder="e.g., Confirmed and agreed to all terms...">Signed version <?= htmlspecialchars($current_rules_version) ?></textarea>
            </div>

            <label class="checkbox-label" style="margin-top: 10px;">
                <input type="checkbox" name="sign_rules" id="sign_rules">
                <strong id="sign_label">Sign version <?= htmlspecialchars($current_rules_version) ?> of the server rules</strong>
            </label>
            <p id="already_signed_notice" style="color: #e74c3c; font-weight: 600; margin-top: 10px; display: none;">
                ⚠ This version has already been signed and cannot be signed again.
            </p>
            <p style="font-size: 13px; color: #666; margin-top: 5px;">
                <a href="../index.html" target="_blank" style="color: #667eea;">View current rules →</a>
            </p>
        </div>
        <?php else: ?>
        <div class="signature-section unsigned">
            <h2 style="margin: 0 0 15px 0; border: none; padding: 0;">
                Server Rules Signature
                <span class="version-badge">Latest: Version <?= htmlspecialchars($current_rules_version) ?></span>
            </h2>
            <div class="signature-notice">
                <strong>Note:</strong> Only R5 leaders can sign the server rules agreement.
            </div>
        </div>
        <?php endif; ?>

        <h2>R5 Leader Information</h2>

        <div class="form-group">
            <label>R5 Name</label>
            <input type="text" name="r5_name" value="<?= htmlspecialchars($r5_name) ?>" required<?= $is_r4_only ? ' readonly style="background: #f0f0f0; cursor: not-allowed;"' : '' ?>>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>UID <small>(optional)</small></label>
                <input type="text" name="r5_game_id" value="<?= htmlspecialchars($r5_game_id) ?>"<?= $is_r4_only ? ' readonly style="background: #f0f0f0; cursor: not-allowed;"' : '' ?>>
            </div>
            <div class="form-group">
                <label>Discord ID <small>(optional)</small></label>
                <input type="text" name="r5_discord_id" value="<?= htmlspecialchars($r5_discord_id) ?>"<?= $is_r4_only ? ' readonly style="background: #f0f0f0; cursor: not-allowed;"' : '' ?>>
            </div>
        </div>

        <h2>R4 Officers <span style="color: #667eea; font-size: 0.9rem;">✨ New</span></h2>
        <?php if ($is_r4_only): ?>
        <p style="color: #666; margin-bottom: 1rem;">R4 officers for this alliance. Only the R5 (alliance leader) can add or remove R4 officers and designate voting permissions.</p>
        <?php else: ?>
        <p style="color: #666; margin-bottom: 1rem;">Manage R4 officers for this alliance. You can designate R4s who can vote on council matters when you are absent.</p>
        <?php endif; ?>

        <div id="r4Container">
            <div id="r4List">
                <!-- R4s will be loaded here via JavaScript -->
            </div>

            <?php if ($is_r4_only): ?>
            <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #6c757d;">
                <span style="font-size: 1.2rem;">🔒</span>
                <span style="color: #495057; font-weight: 500;">Only the R5 can add or remove R4 officers</span>
            </div>
            <?php else: ?>
            <button type="button" class="btn-secondary" onclick="showAddR4Form()" style="margin-top: 1rem;">
                <span style="font-size: 1.2rem;">+</span> Add R4 Officer
            </button>
            <?php endif; ?>
        </div>

        <h2>Discord Server</h2>

        <div class="form-group">
            <label>Server Name</label>
            <input type="text" name="discord_server" value="<?= htmlspecialchars($discord['serverName'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Invite URL</label>
            <input type="url" name="discord_invite" value="<?= htmlspecialchars($discord['inviteUrl'] ?? '') ?>" placeholder="https://discord.gg/...">
        </div>

        <div class="form-group">
            <label>Logo URL <small>(path to logo image)</small></label>
            <input type="text" name="discord_logo" value="<?= htmlspecialchars($discord['logoUrl'] ?? '') ?>" placeholder="images/discord-logos/TAG.png">
        </div>

        <h2>Discord Announcement Channels <span style="color: #667eea; font-size: 0.9rem;">✨ New</span></h2>
        <p style="color: #666; margin-bottom: 1rem;">Configure Discord channels for sending announcements. R5 and R4 members can send messages to these channels.</p>

        <div id="discordChannelsContainer">
            <?php
            $discord_channels = $discord['channels'] ?? [];
            if (empty($discord_channels)): ?>
                <p style="color: #999; text-align: center; padding: 2rem;">No channels configured yet. Click "Add Channel" to get started.</p>
            <?php else: ?>
                <?php foreach ($discord_channels as $index => $channel): ?>
                    <div class="discord-channel-item" data-index="<?= $index ?>">
                        <div class="form-group">
                            <label>Channel ID <small>(Right-click channel in Discord → Copy ID)</small></label>
                            <input type="text" name="discord_channels[<?= $index ?>][id]" value="<?= htmlspecialchars($channel['id'] ?? '') ?>" placeholder="18-20 digit channel ID">
                        </div>
                        <div class="form-group">
                            <label>Channel Name <small>(for display only)</small></label>
                            <input type="text" name="discord_channels[<?= $index ?>][name]" value="<?= htmlspecialchars($channel['name'] ?? '') ?>" placeholder="e.g., announcements, events">
                        </div>
                        <div class="form-group">
                            <label>Channel Type</label>
                            <select name="discord_channels[<?= $index ?>][type]">
                                <option value="announcements" <?= ($channel['type'] ?? '') === 'announcements' ? 'selected' : '' ?>>Announcements</option>
                                <option value="events" <?= ($channel['type'] ?? '') === 'events' ? 'selected' : '' ?>>Events</option>
                                <option value="reminders" <?= ($channel['type'] ?? '') === 'reminders' ? 'selected' : '' ?>>Reminders</option>
                                <option value="general" <?= ($channel['type'] ?? '') === 'general' ? 'selected' : '' ?>>General</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="hidden" name="discord_channels[<?= $index ?>][enabled]" value="0">
                                <input type="checkbox" name="discord_channels[<?= $index ?>][enabled]" value="1" <?= ($channel['enabled'] ?? true) ? 'checked' : '' ?>>
                                <span>Enabled (users can send to this channel)</span>
                            </label>
                        </div>
                        <button type="button" class="btn-remove-channel" onclick="removeDiscordChannel(<?= $index ?>)">Remove Channel</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button type="button" class="btn-add-channel" onclick="addDiscordChannel()">+ Add Channel</button>

        <style>
            .discord-channel-item {
                background: #f8f9fa;
                border: 2px solid #e9ecef;
                border-radius: 8px;
                padding: 1.5rem;
                margin-bottom: 1rem;
                position: relative;
            }
            .btn-add-channel {
                background: #28a745;
                color: white;
                border: none;
                padding: 0.75rem 1.5rem;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 600;
                margin-bottom: 2rem;
            }
            .btn-add-channel:hover {
                background: #218838;
            }
            .btn-remove-channel {
                background: #dc3545;
                color: white;
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.9rem;
            }
            .btn-remove-channel:hover {
                background: #c82333;
            }
        </style>

        <script>
            let channelIndex = <?= count($discord_channels) ?>;

            function addDiscordChannel() {
                const container = document.getElementById('discordChannelsContainer');
                const noChannelsMsg = container.querySelector('p');
                if (noChannelsMsg) noChannelsMsg.remove();

                const channelHtml = `
                    <div class="discord-channel-item" data-index="${channelIndex}">
                        <div class="form-group">
                            <label>Channel ID <small>(Right-click channel in Discord → Copy ID)</small></label>
                            <input type="text" name="discord_channels[${channelIndex}][id]" placeholder="18-20 digit channel ID" required>
                        </div>
                        <div class="form-group">
                            <label>Channel Name <small>(for display only)</small></label>
                            <input type="text" name="discord_channels[${channelIndex}][name]" placeholder="e.g., announcements, events" required>
                        </div>
                        <div class="form-group">
                            <label>Channel Type</label>
                            <select name="discord_channels[${channelIndex}][type]">
                                <option value="announcements" selected>Announcements</option>
                                <option value="events">Events</option>
                                <option value="reminders">Reminders</option>
                                <option value="general">General</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="hidden" name="discord_channels[${channelIndex}][enabled]" value="0">
                                <input type="checkbox" name="discord_channels[${channelIndex}][enabled]" value="1" checked>
                                <span>Enabled (users can send to this channel)</span>
                            </label>
                        </div>
                        <button type="button" class="btn-remove-channel" onclick="removeDiscordChannel(${channelIndex})">Remove Channel</button>
                    </div>
                `;

                container.insertAdjacentHTML('beforeend', channelHtml);
                channelIndex++;
            }

            async function removeDiscordChannel(index) {
                const item = document.querySelector(`.discord-channel-item[data-index="${index}"]`);
                if (item) {
                    // Use confirmAction from scripts.js
                    const confirmed = await confirmAction(
                        'Are you sure you want to remove this Discord channel from the configuration?',
                        'Remove Discord Channel?',
                        {
                            confirmText: 'Remove',
                            cancelText: 'Cancel',
                            dangerMode: true
                        }
                    );

                    if (confirmed) {
                        item.remove();

                        // Show "no channels" message if empty
                        const container = document.getElementById('discordChannelsContainer');
                        if (container.children.length === 0) {
                            container.innerHTML = '<p style="color: #999; text-align: center; padding: 2rem;">No channels configured yet. Click "Add Channel" to get started.</p>';
                        }
                    }
                }
            }
        </script>

        <h2>Recruitment & Contact</h2>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="recruiting" <?= ($info['recruiting'] ?? false) ? 'checked' : '' ?>>
                Currently Recruiting
            </label>
        </div>

        <div class="form-group">
            <label>Recruitment Contact <small>(in-game name or email)</small></label>
            <input type="text" name="recruitment_contact" value="<?= htmlspecialchars($contact['recruitmentContact'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Discord Recruitment <small>(Discord username or channel)</small></label>
            <input type="text" name="discord_recruitment" value="<?= htmlspecialchars($contact['discordRecruitment'] ?? '') ?>">
        </div>

        <h2>Alliance Description</h2>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description"><?= htmlspecialchars($info['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Timezone</label>
            <input type="text" name="timezone" value="<?= htmlspecialchars($info['timezone'] ?? '') ?>" placeholder="e.g., Global, EST, PST, etc.">
        </div>

        <h2>Recruitment Requirements</h2>

        <div class="form-row">
            <div class="form-group">
                <label>Minimum Power</label>
                <input type="number" name="min_power" value="<?= htmlspecialchars($requirements['minPower'] ?? '') ?>" placeholder="e.g., 50000000">
            </div>
            <div class="form-group">
                <label>Minimum Level</label>
                <input type="number" name="min_level" value="<?= htmlspecialchars($requirements['minLevel'] ?? '') ?>" placeholder="e.g., 25">
            </div>
        </div>

        <div class="form-group">
            <label>Activity Level</label>
            <select name="activity">
                <option value="">Not specified</option>
                <option value="Casual" <?= ($requirements['activity'] ?? '') === 'Casual' ? 'selected' : '' ?>>Casual</option>
                <option value="Moderate" <?= ($requirements['activity'] ?? '') === 'Moderate' ? 'selected' : '' ?>>Moderate</option>
                <option value="High" <?= ($requirements['activity'] ?? '') === 'High' ? 'selected' : '' ?>>High</option>
                <option value="Hardcore" <?= ($requirements['activity'] ?? '') === 'Hardcore' ? 'selected' : '' ?>>Hardcore</option>
            </select>
        </div>

        <div class="form-group">
            <label>Additional Requirements <small>(optional notes)</small></label>
            <textarea name="requirements_notes"><?= htmlspecialchars($requirements['notes'] ?? '') ?></textarea>
        </div>

        <div class="actions">
            <button type="submit" class="btn-primary">Save Changes</button>
            <a href="alliance_edit.php" class="btn btn-secondary">← Back to Overview</a>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
// Include and render alliance tags widget
require_once 'includes/alliance_tags_widget.php';
echo render_alliance_tags_widget($tag, $user_token);
?>

<script>
function updateSignatureStatus() {
    const select = document.getElementById('signature_version');
    const checkbox = document.getElementById('sign_rules');
    const notice = document.getElementById('already_signed_notice');
    const label = document.getElementById('sign_label');
    const notesTextarea = document.getElementById('signature_notes');
    
    if (!select || !checkbox || !notice || !label || !notesTextarea) return;
    
    const selectedOption = select.options[select.selectedIndex];
    const isSigned = selectedOption.getAttribute('data-signed') === '1';
    const version = selectedOption.value;

    if (isSigned) {
        checkbox.disabled = true;
        checkbox.checked = false;
        notice.style.display = 'block';
        label.style.opacity = '0.5';
        notesTextarea.disabled = true;
    } else {
        checkbox.disabled = false;
        notice.style.display = 'none';
        label.style.opacity = '1';
        label.textContent = 'Sign version ' + version + ' of the server rules';
        notesTextarea.disabled = false;
        notesTextarea.value = 'Signed version ' + version;
    }
}

// R4 Management Functions
let r4Data = [];
let eligibleR4Users = [];
const isR4Only = <?= $is_r4_only ? 'true' : 'false' ?>;

async function loadR4s() {
    const tag = '<?= htmlspecialchars($tag) ?>';

    try {
        const response = await fetch(`../api/r4_api.php?tag=${encodeURIComponent(tag)}`, {
            credentials: 'include'
        });
        const result = await response.json();

        if (result.success) {
            r4Data = result.r4s || [];
            renderR4List();
        }
    } catch (error) {
        console.error('Failed to load R4s:', error);
    }
}

async function loadEligibleR4Users() {
    const tag = '<?= htmlspecialchars($tag) ?>';

    try {
        const response = await fetch(`r4_users_api.php?tag=${encodeURIComponent(tag)}`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });

        console.log('Eligible users API response status:', response.status);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('API error:', response.status, errorText);
            alert('Failed to load eligible R4 users. Please refresh the page and try again.');
            return;
        }

        const result = await response.json();
        console.log('API result:', result);

        if (result.success) {
            eligibleR4Users = result.eligible_users || [];
            populateR4UserDropdown();
        } else {
            console.error('API returned error:', result.error);
            alert('Failed to load eligible R4 users: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Failed to load eligible R4 users:', error);
        alert('Network error loading R4 users. Please check your connection.');
    }
}

function populateR4UserDropdown() {
    const select = document.getElementById('r4UserSelect');
    if (!select) {
        console.error('r4UserSelect element not found');
        return;
    }

    console.log('Populating dropdown with', eligibleR4Users.length, 'users');

    // Clear existing options except the first one
    select.innerHTML = '<option value="">-- Select an R4 user --</option>';

    eligibleR4Users.forEach((user, index) => {
        const option = document.createElement('option');
        option.value = index;
        option.textContent = user.in_game_name ?
            `${user.in_game_name} (${user.email})` :
            user.email;
        option.dataset.uid = user.uid || '';
        option.dataset.email = user.email;
        option.dataset.name = user.in_game_name || user.email;
        option.dataset.discordId = user.discord_id || '';
        select.appendChild(option);
    });

    console.log('Dropdown populated with', select.options.length - 1, 'options');
}

function onR4UserSelected() {
    const select = document.getElementById('r4UserSelect');
    const selectedOption = select.options[select.selectedIndex];

    if (selectedOption.value === '') {
        document.getElementById('r4Name').value = '';
        document.getElementById('r4Email').value = '';
        document.getElementById('r4UserUid').value = '';
        document.getElementById('r4DiscordId').value = '';
        return;
    }

    const uid = selectedOption.dataset.uid;
    const email = selectedOption.dataset.email;
    const name = selectedOption.dataset.name;
    const discordId = selectedOption.dataset.discordId;

    document.getElementById('r4Name').value = name;
    document.getElementById('r4Email').value = email;
    document.getElementById('r4UserUid').value = uid || '';
    document.getElementById('r4DiscordId').value = discordId || '';
}

function renderR4List() {
    const container = document.getElementById('r4List');

    if (r4Data.length === 0) {
        const emptyMessage = isR4Only
            ? 'No R4 officers have been added yet.'
            : 'No R4 officers added yet. Click "Add R4 Officer" to get started.';
        container.innerHTML = `<p style="color: #999; text-align: center; padding: 2rem;">${emptyMessage}</p>`;
        return;
    }

    container.innerHTML = r4Data.map((r4, index) => `
        <div class="r4-card">
            <div class="r4-info">
                <div class="r4-name">
                    ${escapeHtml(r4.name)}
                    ${r4.canVote ? '<span class="r4-badge can-vote">✓ Can Vote</span>' : ''}
                    ${r4.role ? `<span class="r4-badge role">${escapeHtml(r4.role)}</span>` : ''}
                </div>
                <div class="r4-details">
                    ${r4.gameId ? `UID: ${escapeHtml(r4.gameId)}` : ''}
                    ${r4.gameId && r4.discordId ? ' | ' : ''}
                    ${r4.discordId ? `Discord: ${escapeHtml(r4.discordId)}` : ''}
                    ${!r4.gameId && !r4.discordId ? '<em>No contact info</em>' : ''}
                </div>
            </div>
            ${!isR4Only ? `
            <div class="r4-actions">
                <button class="btn-edit" onclick="editR4(${index})">Edit</button>
                <button class="btn-delete" onclick="deleteR4(${index})">Delete</button>
            </div>
            ` : ''}
        </div>
    `).join('');
}

async function showAddR4Form() {
    if (isR4Only) {
        alert('Only the R5 can add R4 officers.');
        return;
    }
    const modal = document.getElementById('r4Modal');
    const form = document.getElementById('r4Form');
    if (!modal || !form) {
        console.error('Modal or form not found');
        return;
    }

    document.getElementById('r4ModalTitle').textContent = 'Add R4 Officer';
    document.getElementById('r4Index').value = '';
    form.reset();

    // Load eligible R4 users
    await loadEligibleR4Users();

    modal.style.display = 'block';
}

function editR4(index) {
    if (isR4Only) {
        alert('Only the R5 can edit R4 officers.');
        return;
    }
    const r4 = r4Data[index];

    document.getElementById('r4ModalTitle').textContent = 'Edit R4 Officer';
    document.getElementById('r4Index').value = index;

    // Hide user select for editing (can't change the user)
    const userSelectGroup = document.getElementById('r4UserSelect').closest('.form-group');
    if (userSelectGroup) userSelectGroup.style.display = 'none';

    // Make name field editable for editing existing entries
    const nameInput = document.getElementById('r4Name');
    nameInput.readOnly = false;
    nameInput.style.background = 'white';
    nameInput.value = r4.name || '';

    document.getElementById('r4Email').value = r4.email || '';
    document.getElementById('r4DiscordId').value = r4.discordId || '';
    document.getElementById('r4Role').value = r4.role || '';
    document.getElementById('r4CanVote').checked = r4.canVote || false;

    document.getElementById('r4Modal').style.display = 'block';
}

function closeR4Modal() {
    const modal = document.getElementById('r4Modal');
    const form = document.getElementById('r4Form');
    if (modal) modal.style.display = 'none';
    if (form) form.reset();

    // Reset user select visibility and name field readonly state
    const userSelectGroup = document.getElementById('r4UserSelect')?.closest('.form-group');
    if (userSelectGroup) userSelectGroup.style.display = '';

    const nameInput = document.getElementById('r4Name');
    if (nameInput) {
        nameInput.readOnly = true;
        nameInput.style.background = '#f0f0f0';
    }
}

async function saveR4(event) {
    event.preventDefault();

    const index = document.getElementById('r4Index').value;
    const isEdit = index !== '';
    const tag = '<?= htmlspecialchars($tag) ?>';

    const r4 = {
        name: document.getElementById('r4Name').value,
        email: document.getElementById('r4Email').value || null,
        user_uid: document.getElementById('r4UserUid').value || null,
        discordId: document.getElementById('r4DiscordId').value || null,
        role: document.getElementById('r4Role').value || null,
        canVote: document.getElementById('r4CanVote').checked
    };

    const payload = {
        action: isEdit ? 'update' : 'add',
        alliance_tag: tag,
        ...r4
    };

    if (isEdit) {
        payload.r4_index = parseInt(index);
    }

    try {
        const response = await fetch('../api/r4_api.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.success) {
            closeR4Modal();
            await loadR4s();
            showMessage('success', result.message || 'R4 saved successfully');
        } else {
            showMessage('error', result.error || 'Failed to save R4');
        }
    } catch (error) {
        console.error('Save failed:', error);
        showMessage('error', 'Network error: ' + error.message);
    }
}

async function deleteR4(index) {
    if (isR4Only) {
        alert('Only the R5 can remove R4 officers.');
        return;
    }
    const r4 = r4Data[index];

    if (!confirm(`Are you sure you want to remove ${r4.name} as an R4 officer?`)) {
        return;
    }

    const tag = '<?= htmlspecialchars($tag) ?>';

    try {
        const response = await fetch('../api/r4_api.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'delete',
                alliance_tag: tag,
                r4_index: index
            })
        });

        const result = await response.json();

        if (result.success) {
            await loadR4s();
            showMessage('success', result.message || 'R4 removed successfully');
        } else {
            showMessage('error', result.error || 'Failed to remove R4');
        }
    } catch (error) {
        console.error('Delete failed:', error);
        showMessage('error', 'Network error: ' + error.message);
    }
}

function showMessage(type, message) {
    const successMsg = document.getElementById('success-message');
    const errorMsg = document.getElementById('error-message');

    successMsg.style.display = 'none';
    errorMsg.style.display = 'none';

    if (type === 'success') {
        successMsg.textContent = message;
        successMsg.style.display = 'block';
        setTimeout(() => { successMsg.style.display = 'none'; }, 5000);
    } else {
        errorMsg.textContent = message;
        errorMsg.style.display = 'block';
        setTimeout(() => { errorMsg.style.display = 'none'; }, 5000);
    }

    // Scroll to top to show message
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSignatureStatus();
    loadR4s();
    
    // Handle form submission
    document.getElementById('alliance-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const successMsg = document.getElementById('success-message');
        const errorMsg = document.getElementById('error-message');
        
        // Hide previous messages
        successMsg.style.display = 'none';
        errorMsg.style.display = 'none';
        
        // Determine which API endpoint to use
        let apiUrl = 'alliance_edit_api.php?action=update';
        if (formData.get('sign_rules')) {
            apiUrl = 'alliance_edit_api.php?action=sign_rules';
        }
        
        // Get CSRF token from meta tag
        const csrfToken = getCsrfToken();

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                successMsg.textContent = data.message;
                successMsg.style.display = 'block';
                // Reload page after 2 seconds to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                errorMsg.textContent = data.error || 'An error occurred';
                errorMsg.style.display = 'block';
            }
        })
        .catch(error => {
            errorMsg.textContent = 'Network error: ' + error.message;
            errorMsg.style.display = 'block';
        });
    });
});
</script>

<?php
// Render help drawer
require_once 'includes/help_drawer.php';
$token = $user; // Use the JWT token for role-aware content
$help_config = require 'includes/help_content/alliance_edit_help.php';
render_help_drawer($help_config);
?>

<!-- R4 Modal (outside form for proper DOM structure) -->
<div id="r4Modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <h3 id="r4ModalTitle">Add R4 Officer</h3>
        <form id="r4Form" onsubmit="saveR4(event)">
            <input type="hidden" id="r4Index" value="">
            <input type="hidden" id="r4Email" value="">
            <input type="hidden" id="r4UserUid" value="">

            <div class="form-group">
                <label>Select R4 User <span style="color: red;">*</span></label>
                <select id="r4UserSelect" required onchange="onR4UserSelected()">
                    <option value="">-- Select an R4 user --</option>
                </select>
                <small style="color: #666;">Only users with R4 role assigned to this alliance are shown</small>
            </div>

            <div class="form-group">
                <label>In-Game Name <span style="color: red;">*</span></label>
                <input type="text" id="r4Name" required placeholder="In-game name" readonly style="background: #f0f0f0;">
                <small style="color: #666;">From user profile</small>
            </div>

            <div class="form-group">
                <label>Discord ID</label>
                <input type="text" id="r4DiscordId" placeholder="Not set in profile" readonly style="background: #f0f0f0;">
                <small style="color: #666;">From user profile</small>
            </div>

            <div class="form-group">
                <label>Role <small>(optional)</small></label>
                <input type="text" id="r4Role" placeholder="e.g., Recruiter, Deputy, Diplomat">
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" id="r4CanVote" style="margin-right: 8px;">
                    <span>Can vote on council matters (delegation)</span>
                </label>
                <small style="color: #666; margin-left: 28px;">When enabled, this R4 can vote when R5 is absent</small>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn-primary" style="flex: 1;">Save</button>
                <button type="button" class="btn-secondary" onclick="closeR4Modal()" style="flex: 1;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>