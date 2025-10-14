<?php
/**
 * Comprehensive Alliance Editor
 * Allows R4+ users to edit all alliance fields
 * R5 can also sign rules
 *
 * @version 1.2.0
 * @date 2025-10-13
 * @changelog
 *   1.2.0 (2025-10-13) - R4 users cannot edit alliance name or R5 name
 *                      - Changed "Game ID" label to "UID"
 *                      - Added readonly attributes for R4-restricted fields
 *   1.1.0 (2025-10-13) - Added version dropdown for rule signatures
 *                      - Prevent signing already-signed versions
 *                      - Default to newest version
 *   1.0.0 (2025-10-12) - Initial implementation
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';

$user_token = require_jwt_session();

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

// Handle edit action
if (isset($_GET['tag'])) {
    $tag = $_GET['tag'];

    // Check permission - must be R4 or higher
    if (!is_r4_or_higher($user_token) && !has_alliance_access($user_token, $tag)) {
        http_response_code(403);
        die('Access denied. R4+ privileges required.');
    }

    // Load alliances data
    $alliances_data = read_json_file(ALLIANCES_FILE);
    $alliances_array = is_array($alliances_data) && isset($alliances_data[0]) ? $alliances_data : ($alliances_data['alliances'] ?? []);
    $alliance = null;
    $index = -1;

    foreach ($alliances_array as $i => $a) {
        if (strtolower($a['tag'] ?? '') === strtolower($tag)) {
            $alliance = $a;
            $index = $i;
            break;
        }
    }

    if (!$alliance) {
        die('Alliance not found.');
    }

    // Helper functions
    function get_r5_name($r5_data) {
        if (is_string($r5_data)) return $r5_data;
        if (is_array($r5_data) && isset($r5_data['name'])) return $r5_data['name'];
        return '';
    }

    function set_r5_data($original_r5, $new_name, $game_id = null, $discord_id = null) {
        if (is_array($original_r5)) {
            $original_r5['name'] = $new_name;
            if ($game_id !== null) $original_r5['gameId'] = $game_id;
            if ($discord_id !== null) $original_r5['discordId'] = $discord_id;
            return $original_r5;
        }
        // If it was a string, upgrade to object format
        return [
            'name' => $new_name,
            'gameId' => $game_id,
            'discordId' => $discord_id
        ];
    }

    // Check if user is R4 (cannot edit certain fields)
    $is_r4_only = (strtolower($user_token->aud) === 'r4');

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Basic fields - R4 cannot change alliance name
        if (!$is_r4_only) {
            $alliances_array[$index]['name'] = $_POST['name'] ?? $alliance['name'];
        }

        // Handle signature (R5 only)
        if (isset($_POST['sign_rules']) && can_sign_rules($user_token, $tag)) {
            // Get version to sign from dropdown
            $version_to_sign = $_POST['signature_version'] ?? $current_rules_version;

            // Initialize r5History if needed
            if (!isset($alliances_array[$index]['r5History']) || !is_array($alliances_array[$index]['r5History'])) {
                $alliances_array[$index]['r5History'] = [];
            }

            // Find current R5
            $current_r5_index = -1;
            foreach ($alliances_array[$index]['r5History'] as $j => $r5) {
                if ($r5['current'] ?? false) {
                    $current_r5_index = $j;
                    break;
                }
            }

            // Get R5 name
            $r5_name = $_POST['r5_name'] ?? get_r5_name($alliance['r5']);

            // If no current R5, create one
            if ($current_r5_index === -1) {
                $alliances_array[$index]['r5History'][] = [
                    'r5Name' => $r5_name,
                    'gameId' => $_POST['r5_game_id'] ?: null,
                    'discordId' => $_POST['r5_discord_id'] ?: null,
                    'startDate' => date('Y-m-d\TH:i:s\Z'),
                    'endDate' => null,
                    'current' => true,
                    'signatures' => []
                ];
                $current_r5_index = count($alliances_array[$index]['r5History']) - 1;
            }

            // Add signature
            $alliances_array[$index]['r5History'][$current_r5_index]['signatures'][] = [
                'version' => $version_to_sign,
                'signedAt' => date('Y-m-d\TH:i:s\Z'),
                'signedBy' => $r5_name,
                'notes' => $_POST['signature_notes'] ?? "Signed version $version_to_sign"
            ];

            // Update signed status (only true if latest version is signed)
            $alliances_array[$index]['signed'] = ($version_to_sign === $current_rules_version);
        } elseif (isset($_POST['signed']) && can_sign_rules($user_token, $tag)) {
            // Only R5 can change signed status
            $alliances_array[$index]['signed'] = isset($_POST['signed']);
        }

        // R5 info - R4 cannot change R5 name
        if (!$is_r4_only) {
            $alliances_array[$index]['r5'] = set_r5_data(
                $alliance['r5'],
                $_POST['r5_name'] ?? '',
                $_POST['r5_game_id'] ?: null,
                $_POST['r5_discord_id'] ?: null
            );

            // Also update r5Name in current r5History entry to keep them synchronized
            if (isset($alliances_array[$index]['r5History']) && is_array($alliances_array[$index]['r5History'])) {
                foreach ($alliances_array[$index]['r5History'] as $j => &$r5_entry) {
                    if ($r5_entry['current'] ?? false) {
                        $r5_entry['r5Name'] = $_POST['r5_name'] ?? '';
                        if ($_POST['r5_game_id']) $r5_entry['gameId'] = $_POST['r5_game_id'];
                        if ($_POST['r5_discord_id']) $r5_entry['discordId'] = $_POST['r5_discord_id'];
                        break;
                    }
                }
                unset($r5_entry);
            }
        }

        // Discord info
        if (!isset($alliances_array[$index]['discord'])) {
            $alliances_array[$index]['discord'] = [];
        }
        $alliances_array[$index]['discord']['serverName'] = $_POST['discord_server'] ?: null;
        $alliances_array[$index]['discord']['inviteUrl'] = $_POST['discord_invite'] ?: null;
        $alliances_array[$index]['discord']['logoUrl'] = $_POST['discord_logo'] ?: null;

        // Contact info
        if (!isset($alliances_array[$index]['contact'])) {
            $alliances_array[$index]['contact'] = [];
        }
        $alliances_array[$index]['contact']['recruitmentContact'] = $_POST['recruitment_contact'] ?: null;
        $alliances_array[$index]['contact']['discordRecruitment'] = $_POST['discord_recruitment'] ?: null;

        // Alliance info
        if (!isset($alliances_array[$index]['info'])) {
            $alliances_array[$index]['info'] = [];
        }
        $alliances_array[$index]['info']['description'] = $_POST['description'] ?: null;
        $alliances_array[$index]['info']['timezone'] = $_POST['timezone'] ?: null;
        $alliances_array[$index]['info']['recruiting'] = isset($_POST['recruiting']);

        // Requirements
        if (!isset($alliances_array[$index]['info']['requirements'])) {
            $alliances_array[$index]['info']['requirements'] = [];
        }
        $alliances_array[$index]['info']['requirements']['minPower'] = $_POST['min_power'] ? (int)$_POST['min_power'] : null;
        $alliances_array[$index]['info']['requirements']['minLevel'] = $_POST['min_level'] ? (int)$_POST['min_level'] : null;
        $alliances_array[$index]['info']['requirements']['activity'] = $_POST['activity'] ?: null;
        $alliances_array[$index]['info']['requirements']['notes'] = $_POST['requirements_notes'] ?: null;

        // Update timestamp
        if (!isset($alliances_array[$index]['metadata'])) {
            $alliances_array[$index]['metadata'] = [];
        }
        $alliances_array[$index]['metadata']['lastUpdated'] = date('Y-m-d\TH:i:s\Z');

        // Write back
        write_json_file(ALLIANCES_FILE, $alliances_array);

        header('Location: dashboard.php?success=alliance_updated');
        exit;
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

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit Alliance - <?= htmlspecialchars($alliance['tag']) ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f5f5f5;
                padding: 20px;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            h1 { color: #333; margin-bottom: 20px; }
            h2 {
                color: #667eea;
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
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Edit Alliance: <?= htmlspecialchars($alliance['tag']) ?></h1>

            <div class="info-box">
                <p><strong>Rank:</strong> <?= htmlspecialchars($alliance['rank'] ?? 'N/A') ?></p>
                <p><strong>Power:</strong> <?= isset($alliance['power']) ? number_format($alliance['power']) : 'N/A' ?></p>
                <p><strong>Your Role:</strong> <?= htmlspecialchars(strtoupper($user_token->aud)) ?></p>
            </div>

            <form method="POST">
                <h2>Basic Information</h2>

                <div class="form-group">
                    <label>Alliance Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($alliance['name'] ?? '') ?>" required<?= $is_r4_only ? \' readonly style="background: #f0f0f0; cursor: not-allowed;"\' : \'\' ?>>
                </div>

                <!-- Rules Signature Section -->
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

                    <?php if ($can_sign): ?>
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

                        <script>
                        function updateSignatureStatus() {
                            const select = document.getElementById('signature_version');
                            const checkbox = document.getElementById('sign_rules');
                            const notice = document.getElementById('already_signed_notice');
                            const label = document.getElementById('sign_label');
                            const notesTextarea = document.getElementById('signature_notes');
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

                        // Initialize on page load
                        updateSignatureStatus();
                        </script>
                    <?php else: ?>
                        <div class="signature-notice" style="margin-top: 15px;">
                            <strong>Note:</strong> Only R5 leaders can sign the server rules agreement.
                        </div>
                    <?php endif; ?>
                </div>

                <h2>R5 Leader Information</h2>

                <div class="form-group">
                    <label>R5 Name</label>
                    <input type="text" name="r5_name" value="<?= htmlspecialchars($r5_name) ?>" required<?= $is_r4_only ? \' readonly style="background: #f0f0f0; cursor: not-allowed;"\' : \'\' ?>>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>UID <small>(optional)</small></label>
                        <input type="text" name="r5_game_id" value="<?= htmlspecialchars($r5_game_id) ?>">
                    </div>
                    <div class="form-group">
                        <label>Discord ID <small>(optional)</small></label>
                        <input type="text" name="r5_discord_id" value="<?= htmlspecialchars($r5_discord_id) ?>">
                    </div>
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
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

http_response_code(404);
echo 'Alliance not found';
?>
