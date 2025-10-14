<?php
/**
 * Rules Signature System
 * Allows R5 leaders to sign the current version of server rules
 *
 * @version 1.0.0
 * @date 2025-10-12
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/json_helpers.php';

$user_token = require_jwt_session();

// Load current rules version
$amendments = read_json_file(__DIR__ . '/../data/amendments.json');
$current_version = !empty($amendments) ? $amendments[0]['version'] : '1.0';

// Load alliances
$alliances_data = read_json_file(ALLIANCES_FILE);
$alliances_array = is_array($alliances_data) && isset($alliances_data[0]) ? $alliances_data : ($alliances_data['alliances'] ?? []);

// Get user's alliances
$user_alliances = [];
if (in_array('*', $user_token->alliances) || $user_token->aud === 'admin') {
    $user_alliances = $alliances_array;
} else {
    foreach ($alliances_array as $alliance) {
        $tag = strtolower($alliance['tag'] ?? '');
        if (in_array($tag, array_map('strtolower', $user_token->alliances))) {
            $user_alliances[] = $alliance;
        }
    }
}

// Handle signature submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alliance_tag'])) {
    $tag = $_POST['alliance_tag'];

    // Verify permission
    if (!can_sign_rules($user_token, $tag)) {
        die('Access denied. Only R5 can sign rules.');
    }

    // Find and update alliance
    $updated = false;
    foreach ($alliances_array as $i => &$alliance) {
        if (strtolower($alliance['tag'] ?? '') === strtolower($tag)) {
            // Initialize r5History if needed
            if (!isset($alliance['r5History']) || !is_array($alliance['r5History'])) {
                $alliance['r5History'] = [];
            }

            // Find current R5
            $current_r5_index = -1;
            foreach ($alliance['r5History'] as $j => $r5) {
                if ($r5['current'] ?? false) {
                    $current_r5_index = $j;
                    break;
                }
            }

            // If no current R5, create one
            if ($current_r5_index === -1) {
                $r5_name = is_string($alliance['r5']) ? $alliance['r5'] : ($alliance['r5']['name'] ?? 'R5 Name');
                $alliance['r5History'][] = [
                    'r5Name' => $r5_name,
                    'gameId' => null,
                    'discordId' => null,
                    'startDate' => date('Y-m-d\TH:i:s\Z'),
                    'endDate' => null,
                    'current' => true,
                    'signatures' => []
                ];
                $current_r5_index = count($alliance['r5History']) - 1;
            }

            // Add signature
            $r5_name = is_string($alliance['r5']) ? $alliance['r5'] : ($alliance['r5']['name'] ?? 'R5 Name');
            $alliance['r5History'][$current_r5_index]['signatures'][] = [
                'version' => $current_version,
                'signedAt' => date('Y-m-d\TH:i:s\Z'),
                'signedBy' => $r5_name,
                'notes' => $_POST['notes'] ?? "Signed version $current_version"
            ];

            // Update signed status
            $alliance['signed'] = true;

            $updated = true;
            break;
        }
    }

    if ($updated) {
        write_json_file(ALLIANCES_FILE, $alliances_array);
        header('Location: sign_rules.php?success=signed&tag=' . urlencode($tag));
        exit;
    } else {
        $error = 'Alliance not found';
    }
}

// Get signature status for user's alliances
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign Server Rules</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-bottom: 10px; }
        .version-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #155724;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #856404;
        }
        .info {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #2c3e50;
        }
        .alliance-card {
            border: 2px solid #eee;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .alliance-card.signed {
            border-color: #28a745;
            background: #f8fff9;
        }
        .alliance-card h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .alliance-card.signed h3 {
            color: #28a745;
        }
        .signature-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-size: 14px;
        }
        .form-group {
            margin: 15px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-height: 80px;
        }
        button, .btn {
            padding: 10px 20px;
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
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-secondary {
            background: #666;
            color: white;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sign Server Rules</h1>
        <span class="version-badge">Current Version: <?= htmlspecialchars($current_version) ?></span>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'signed'): ?>
            <div class="success">
                <strong>Success!</strong> Rules have been signed for <?= htmlspecialchars($_GET['tag'] ?? 'your alliance') ?>.
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="warning">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!can_sign_rules($user_token, 'any')): ?>
            <div class="warning">
                <strong>Permission Required:</strong> Only R5 leaders can sign the server rules.
            </div>
        <?php endif; ?>

        <div class="info">
            <p><strong>About Rule Signing:</strong></p>
            <p>By signing the rules, your alliance's R5 confirms that they have read and agree to the current version of the server rules and NAP15 agreement.</p>
        </div>

        <h2 style="margin: 25px 0 15px 0; color: #333;">Your Alliances</h2>

        <?php if (empty($user_alliances)): ?>
            <p>No alliances assigned to your account.</p>
        <?php else: ?>
            <?php foreach ($user_alliances as $alliance): ?>
                <?php
                $tag = $alliance['tag'] ?? 'N/A';
                $can_sign = can_sign_rules($user_token, $tag);
                $signature = get_signature_status($alliance, $current_version);
                $is_signed = $signature !== null;
                ?>
                <div class="alliance-card <?= $is_signed ? 'signed' : '' ?>">
                    <h3>
                        <?= $is_signed ? '✓ ' : '' ?>
                        <?= htmlspecialchars($tag) ?> - <?= htmlspecialchars($alliance['name'] ?? 'Unknown') ?>
                    </h3>
                    <p><strong>Rank:</strong> <?= htmlspecialchars($alliance['rank'] ?? 'N/A') ?></p>

                    <?php if ($is_signed): ?>
                        <div class="signature-info">
                            <p><strong>✓ Signed Version <?= htmlspecialchars($signature['version']) ?></strong></p>
                            <p><strong>Signed by:</strong> <?= htmlspecialchars($signature['signedBy']) ?></p>
                            <p><strong>Date:</strong> <?= htmlspecialchars(date('Y-m-d H:i', strtotime($signature['signedAt']))) ?></p>
                            <?php if (!empty($signature['notes'])): ?>
                                <p><strong>Notes:</strong> <?= htmlspecialchars($signature['notes']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($can_sign): ?>
                        <form method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="alliance_tag" value="<?= htmlspecialchars($tag) ?>">

                            <?php if (!$is_signed): ?>
                                <div class="form-group">
                                    <label>Signature Notes <small>(optional)</small></label>
                                    <textarea name="notes" placeholder="Add any notes about this signature..."></textarea>
                                </div>
                                <div class="actions">
                                    <button type="submit" class="btn-success">Sign Version <?= htmlspecialchars($current_version) ?></button>
                                </div>
                            <?php else: ?>
                                <div class="form-group">
                                    <label>Re-sign Rules <small>(if rules have been updated)</small></label>
                                    <textarea name="notes" placeholder="Reason for re-signing..."></textarea>
                                </div>
                                <div class="actions">
                                    <button type="submit" class="btn-primary">Re-sign Version <?= htmlspecialchars($current_version) ?></button>
                                </div>
                            <?php endif; ?>
                        </form>
                    <?php elseif (!$is_signed): ?>
                        <p style="color: #856404; margin-top: 10px;"><em>Waiting for R5 signature...</em></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="actions" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <a href="../index.html" class="btn btn-primary" target="_blank">View Server Rules</a>
        </div>
    </div>
</body>
</html>
