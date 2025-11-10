<?php
/**
 * Profile API
 * Handles user profile operations (search, create, update)
 */

header('Content-Type: application/json');

$profiles_file = __DIR__ . '/../data/user-profiles.json';
$alliances_file = __DIR__ . '/../data/alliances.json';

// Load profiles
function load_profiles() {
    global $profiles_file;
    if (!file_exists($profiles_file)) {
        return ['profiles' => [], 'metadata' => ['total_profiles' => 0]];
    }
    return json_decode(file_get_contents($profiles_file), true);
}

// Save profiles
function save_profiles($data) {
    global $profiles_file;
    $data['metadata']['last_updated'] = gmdate('Y-m-d\TH:i:s\Z');
    $data['metadata']['total_profiles'] = count($data['profiles']);
    file_put_contents($profiles_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Generate profile ID
function generate_profile_id() {
    $date = gmdate('Ymd');
    $random = bin2hex(random_bytes(4));
    return "prof_{$date}_{$random}";
}

// Search for profile
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'search') {
    $alliance = $_GET['alliance'] ?? '';
    $name = $_GET['name'] ?? '';

    if (empty($alliance) || empty($name)) {
        echo json_encode(['error' => 'Missing alliance or name parameter']);
        exit;
    }

    $data = load_profiles();

    // Search for existing profile
    $found = null;
    foreach ($data['profiles'] as $profile) {
        if (strtolower($profile['alliance_tag']) === strtolower($alliance) &&
            strtolower($profile['game_name']) === strtolower($name)) {
            $found = $profile;
            break;
        }
    }

    if ($found) {
        echo json_encode([
            'found' => true,
            'profile' => $found
        ]);
    } else {
        echo json_encode([
            'found' => false,
            'message' => 'Profile not found'
        ]);
    }
    exit;
}

// Create or update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    $data = load_profiles();

    // Create new profile
    if ($action === 'create') {
        $alliance_tag = $input['alliance_tag'] ?? '';
        $game_name = $input['game_name'] ?? '';

        if (empty($alliance_tag) || empty($game_name)) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        // Check if profile already exists
        foreach ($data['profiles'] as $profile) {
            if (strtolower($profile['alliance_tag']) === strtolower($alliance_tag) &&
                strtolower($profile['game_name']) === strtolower($game_name)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Profile already exists',
                    'profile' => $profile
                ]);
                exit;
            }
        }

        $new_profile = [
            'profile_id' => generate_profile_id(),
            'alliance_tag' => $alliance_tag,
            'game_name' => $game_name,
            'game_id' => null,
            'discord_id' => null,
            'discord_tag' => null,
            'role' => 'member',
            'verified' => false,
            'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'updated_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'updated_by' => 'self',
            'metadata' => [
                'last_alliance_sync' => null,
                'admin_notes' => null
            ]
        ];

        $data['profiles'][] = $new_profile;
        save_profiles($data);

        echo json_encode([
            'success' => true,
            'profile' => $new_profile
        ]);
        exit;
    }

    // Update existing profile
    if ($action === 'update') {
        $profile_id = $input['profile_id'] ?? '';
        $alliance_tag = $input['alliance_tag'] ?? '';
        $game_name = $input['game_name'] ?? '';

        if (empty($profile_id) && (empty($alliance_tag) || empty($game_name))) {
            echo json_encode(['success' => false, 'error' => 'Missing profile identifier']);
            exit;
        }

        $found_index = null;
        foreach ($data['profiles'] as $index => $profile) {
            if (!empty($profile_id) && $profile['profile_id'] === $profile_id) {
                $found_index = $index;
                break;
            } elseif (strtolower($profile['alliance_tag']) === strtolower($alliance_tag) &&
                      strtolower($profile['game_name']) === strtolower($game_name)) {
                $found_index = $index;
                break;
            }
        }

        if ($found_index === null) {
            echo json_encode(['success' => false, 'error' => 'Profile not found']);
            exit;
        }

        // Update allowed fields (self-service)
        if (isset($input['game_id'])) {
            $data['profiles'][$found_index]['game_id'] = $input['game_id'];
        }
        if (isset($input['discord_id'])) {
            $data['profiles'][$found_index]['discord_id'] = $input['discord_id'];
        }
        if (isset($input['discord_tag'])) {
            $data['profiles'][$found_index]['discord_tag'] = $input['discord_tag'];
        }

        $data['profiles'][$found_index]['updated_at'] = gmdate('Y-m-d\TH:i:s\Z');
        $data['profiles'][$found_index]['updated_by'] = 'self';

        save_profiles($data);

        echo json_encode([
            'success' => true,
            'profile' => $data['profiles'][$found_index]
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

echo json_encode(['error' => 'Invalid request method']);
?>
