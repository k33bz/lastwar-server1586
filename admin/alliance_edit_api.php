<?php
/**
 * Alliance Editor API
 * Handles alliance information updates for R4/R5 users
 *
 * Documentation:
 * - Alliance Management Guide: https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/ALLIANCE_MANAGEMENT_GUIDE.md
 * - Alliance Data Schema: https://github.com/k33bz/lastwar-server1586/blob/mainline/data/ALLIANCE_SCHEMA.md
 * - User Personas (Roles): https://github.com/k33bz/lastwar-server1586/blob/mainline/admin/USER-PERSONAS.md
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.0.0
 * @date 2025-10-15
 */

// Require JWT authentication
require_once 'jwt.php';
require_once 'audit_logger.php';
require_once 'includes/input_validator.php';

$user = require_jwt_session();

// Create proper user token for role checking
$user_token = (object)[
    'sub' => $user->sub,
    'aud' => $user->aud,
    'alliances' => $user->alliances ?? []
];

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'update':
        handle_alliance_update();
        break;
    case 'sign_rules':
        handle_rules_signature();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handle_alliance_update() {
    global $user_token;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    // CSRF Protection
    requireCsrfToken();

    $tag = $_POST['tag'] ?? '';
    if (!$tag) {
        http_response_code(400);
        echo json_encode(['error' => 'Alliance tag required']);
        return;
    }

    // Validate and sanitize tag
    $tag_validation = validate_alliance_tag($tag, false); // Not strict - may have already been created
    if (!$tag_validation['valid']) {
        http_response_code(400);
        echo json_encode(['error' => $tag_validation['error']]);
        return;
    }
    $tag = $tag_validation['sanitized'];

    // Check permission
    if (!has_alliance_access($user_token, $tag)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. You do not have permission to edit this alliance.']);
        return;
    }
    
    // Load alliance helper
    require_once 'includes/alliance_helper.php';
    
    // Load alliances data using helper
    $alliances_array = AllianceHelper::loadAlliances();
    
    // Find alliance using helper
    $result = AllianceHelper::getAllianceByTag($tag, $alliances_array);
    
    if (!$result) {
        http_response_code(404);
        echo json_encode(['error' => 'Alliance not found']);
        return;
    }
    
    $alliance = $result['alliance'];
    $index = $result['index'];
    
    // Check if user is R4 (cannot edit certain fields)
    $is_r4_only = (strtolower($user_token->aud) === 'r4');
    
    try {
        // Basic fields - R4 cannot change alliance name
        if (!$is_r4_only && isset($_POST['name'])) {
            $name_validation = validate_alliance_name($_POST['name']);
            if (!$name_validation['valid']) {
                http_response_code(400);
                echo json_encode(['error' => 'Alliance name: ' . $name_validation['error']]);
                return;
            }
            $alliances_array[$index]['name'] = $name_validation['sanitized'];
        }

        // R5 info - R4 cannot change R5 name
        if (!$is_r4_only) {
            $r5_name_validation = validate_r5_name($_POST['r5_name'] ?? $alliance['r5']);
            if (!$r5_name_validation['valid']) {
                http_response_code(400);
                echo json_encode(['error' => 'R5 name: ' . $r5_name_validation['error']]);
                return;
            }
            $alliances_array[$index]['r5'] = set_r5_data(
                $alliance['r5'],
                $r5_name_validation['sanitized'],
                $_POST['r5_game_id'] ?: null,
                $_POST['r5_discord_id'] ?: null
            );
        }

        // Discord info
        if (!isset($alliances_array[$index]['discord'])) {
            $alliances_array[$index]['discord'] = [];
        }

        // Validate Discord server name
        $discord_server_validation = validate_text_field($_POST['discord_server'] ?? '', 0, 100);
        if (!$discord_server_validation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => 'Discord server: ' . $discord_server_validation['error']]);
            return;
        }
        $alliances_array[$index]['discord']['serverName'] = $discord_server_validation['sanitized'] ?: null;

        // Validate Discord invite URL
        $discord_invite_validation = validate_url($_POST['discord_invite'] ?? '');
        if (!$discord_invite_validation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => 'Discord invite URL: ' . $discord_invite_validation['error']]);
            return;
        }
        $alliances_array[$index]['discord']['inviteUrl'] = $discord_invite_validation['sanitized'] ?: null;

        // Validate Discord logo URL
        $discord_logo_validation = validate_url($_POST['discord_logo'] ?? '');
        if (!$discord_logo_validation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => 'Discord logo URL: ' . $discord_logo_validation['error']]);
            return;
        }
        $alliances_array[$index]['discord']['logoUrl'] = $discord_logo_validation['sanitized'] ?: null;

        // Discord announcement channels (Issue #59)
        $discord_channels = [];

        // Debug: Log what we're receiving
        error_log("Discord channels POST data: " . print_r($_POST['discord_channels'] ?? 'NOT SET', true));

        if (isset($_POST['discord_channels']) && is_array($_POST['discord_channels'])) {
            error_log("Processing " . count($_POST['discord_channels']) . " discord channels");
            foreach ($_POST['discord_channels'] as $channel_data) {
                $channel_id = trim($channel_data['id'] ?? '');
                $channel_name = trim($channel_data['name'] ?? '');
                $channel_type = $channel_data['type'] ?? 'announcements';
                $channel_enabled = ($channel_data['enabled'] ?? '0') === '1';

                // Validate channel ID (18-20 digits)
                if (!empty($channel_id)) {
                    if (!preg_match('/^\d{15,20}$/', $channel_id)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid Discord channel ID format: ' . $channel_id . '. Must be 15-20 digits.']);
                        return;
                    }

                    // Validate channel name
                    $channel_name_validation = validate_text_field($channel_name, 1, 100, true);
                    if (!$channel_name_validation['valid']) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Channel name for ' . $channel_id . ': ' . $channel_name_validation['error']]);
                        return;
                    }

                    $discord_channels[] = [
                        'id' => $channel_id,
                        'name' => $channel_name_validation['sanitized'],
                        'type' => in_array($channel_type, ['announcements', 'events', 'reminders', 'general']) ? $channel_type : 'announcements',
                        'enabled' => $channel_enabled
                    ];
                }
            }
        }
        $alliances_array[$index]['discord']['channels'] = $discord_channels;

        // Debug: Log what we're saving
        error_log("Saving " . count($discord_channels) . " discord channels for alliance " . $tag);
        error_log("Channels being saved: " . json_encode($discord_channels));

        // Contact info
        if (!isset($alliances_array[$index]['contact'])) {
            $alliances_array[$index]['contact'] = [];
        }

        // Validate recruitment contact
        $recruitment_contact_validation = validate_text_field($_POST['recruitment_contact'] ?? '', 0, 100);
        if (!$recruitment_contact_validation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => 'Recruitment contact: ' . $recruitment_contact_validation['error']]);
            return;
        }
        $alliances_array[$index]['contact']['recruitmentContact'] = $recruitment_contact_validation['sanitized'] ?: null;

        // Validate Discord recruitment
        $discord_recruitment_validation = validate_text_field($_POST['discord_recruitment'] ?? '', 0, 100);
        if (!$discord_recruitment_validation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => 'Discord recruitment: ' . $discord_recruitment_validation['error']]);
            return;
        }
        $alliances_array[$index]['contact']['discordRecruitment'] = $discord_recruitment_validation['sanitized'] ?: null;

        // Alliance info
        if (!isset($alliances_array[$index]['info'])) {
            $alliances_array[$index]['info'] = [];
        }

        // Validate description (max 500 chars)
        $description_validation = validate_text_field($_POST['description'] ?? '', 0, 500);
        if (!$description_validation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => 'Description: ' . $description_validation['error']]);
            return;
        }
        $alliances_array[$index]['info']['description'] = $description_validation['sanitized'] ?: null;

        // Validate timezone (max 50 chars)
        $timezone_validation = validate_text_field($_POST['timezone'] ?? '', 0, 50);
        if (!$timezone_validation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => 'Timezone: ' . $timezone_validation['error']]);
            return;
        }
        $alliances_array[$index]['info']['timezone'] = $timezone_validation['sanitized'] ?: null;
        $alliances_array[$index]['info']['recruiting'] = isset($_POST['recruiting']);

        // Requirements
        if (!isset($alliances_array[$index]['info']['requirements'])) {
            $alliances_array[$index]['info']['requirements'] = [];
        }

        // Validate min power (0 to 10 trillion)
        $min_power_validation = validate_numeric_field($_POST['min_power'] ?? null, 0, 10000000000000);
        if (!$min_power_validation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => 'Minimum power: ' . $min_power_validation['error']]);
            return;
        }
        $alliances_array[$index]['info']['requirements']['minPower'] = $min_power_validation['sanitized'];

        // Validate min level (0 to 100)
        $min_level_validation = validate_numeric_field($_POST['min_level'] ?? null, 0, 100);
        if (!$min_level_validation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => 'Minimum level: ' . $min_level_validation['error']]);
            return;
        }
        $alliances_array[$index]['info']['requirements']['minLevel'] = $min_level_validation['sanitized'];

        // Validate activity (max 100 chars)
        $activity_validation = validate_text_field($_POST['activity'] ?? '', 0, 100);
        if (!$activity_validation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => 'Activity: ' . $activity_validation['error']]);
            return;
        }
        $alliances_array[$index]['info']['requirements']['activity'] = $activity_validation['sanitized'] ?: null;

        // Validate requirements notes (max 500 chars)
        $requirements_notes_validation = validate_text_field($_POST['requirements_notes'] ?? '', 0, 500);
        if (!$requirements_notes_validation['valid']) {
            http_response_code(400);
            echo json_encode(['error' => 'Requirements notes: ' . $requirements_notes_validation['error']]);
            return;
        }
        $alliances_array[$index]['info']['requirements']['notes'] = $requirements_notes_validation['sanitized'] ?: null;
        
        // Update timestamp
        if (!isset($alliances_array[$index]['metadata'])) {
            $alliances_array[$index]['metadata'] = [];
        }
        $alliances_array[$index]['metadata']['lastUpdated'] = date('Y-m-d\TH:i:s\Z');
        
        // Save using helper
        AllianceHelper::saveAlliances($alliances_array);

        // Log audit event
        log_audit_event('alliance_updated', $user_token->sub, [
            'alliance_tag' => $tag,
            'fields_updated' => array_keys($_POST),
            'is_r4_edit' => $is_r4_only
        ]);

        echo json_encode(['success' => true, 'message' => 'Alliance updated successfully']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update alliance: ' . $e->getMessage()]);
    }
}

function handle_rules_signature() {
    global $user_token;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    // CSRF Protection
    requireCsrfToken();

    $tag = $_POST['tag'] ?? '';
    if (!$tag) {
        http_response_code(400);
        echo json_encode(['error' => 'Alliance tag required']);
        return;
    }
    
    // Check permission - only R5 can sign rules
    if (!can_sign_rules($user_token, $tag)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Only R5 can sign rules.']);
        return;
    }
    
    // Load alliances data
    $alliances_file = __DIR__ . '/../data/alliances.json';
    $alliances_data = read_json_file($alliances_file);
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
        http_response_code(404);
        echo json_encode(['error' => 'Alliance not found']);
        return;
    }
    
    try {
        // Load available rule versions
        $amendments_file = __DIR__ . '/../data/amendments.json';
        $amendments = file_exists($amendments_file) ? read_json_file($amendments_file) : [];
        
        // Build list of all versions
        $all_versions = ['1.0'];
        if (!empty($amendments)) {
            $amendment_versions = array_column($amendments, 'version');
            $all_versions = array_merge($all_versions, $amendment_versions);
            $all_versions = array_unique($all_versions);
            usort($all_versions, 'version_compare');
        }
        
        $current_rules_version = end($all_versions);
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
        
        // Save using helper
        AllianceHelper::saveAlliances($alliances_array);

        // Log audit event
        log_audit_event('rules_signed', $user_token->sub, [
            'alliance_tag' => $tag,
            'version' => $version_to_sign,
            'r5_name' => $r5_name
        ]);

        echo json_encode(['success' => true, 'message' => 'Rules signed successfully']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to sign rules: ' . $e->getMessage()]);
    }
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
?>