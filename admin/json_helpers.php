<?php
/**
 * JSON File Handling Utilities with File Locking
 *
 * Provides secure read/write operations for JSON files with proper
 * file locking to prevent race conditions during concurrent access
 *
 * @version 1.0.0
 * @date 2025-10-12
 * @changelog
 *   1.0.0 (2025-10-12) - Initial implementation with flock support
 */

define('ADMIN_INIT', true);
require_once __DIR__ . '/config.php';

/**
 * Read JSON file with shared lock
 *
 * @param string $path Path to JSON file
 * @return array Decoded JSON data
 * @throws Exception if file cannot be locked or read
 */
function read_json_file($path) {
    if (!file_exists($path)) {
        throw new Exception("File not found: $path");
    }

    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new Exception("Could not open $path for reading.");
    }

    if (flock($handle, LOCK_SH)) {
        $data = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        $decoded = json_decode($data, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON decode error in $path: " . json_last_error_msg());
        }

        return $decoded;
    } else {
        fclose($handle);
        throw new Exception("Could not lock $path for reading.");
    }
}

/**
 * Write JSON file with exclusive lock
 *
 * @param string $path Path to JSON file
 * @param mixed $data Data to encode and write
 * @return bool Success status
 * @throws Exception if file cannot be locked or written
 */
function write_json_file($path, $data) {
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        throw new Exception("Could not open $path for writing.");
    }

    if (flock($handle, LOCK_EX)) {
        ftruncate($handle, 0);
        rewind($handle);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            flock($handle, LOCK_UN);
            fclose($handle);
            throw new Exception("JSON encode error: " . json_last_error_msg());
        }

        $result = fwrite($handle, $json);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        if ($result === false) {
            throw new Exception("Could not write to $path.");
        }

        return true;
    } else {
        fclose($handle);
        throw new Exception("Could not lock $path for writing.");
    }
}

/**
 * Safely update JSON file with callback function
 *
 * Reads file, applies callback to modify data, writes back atomically
 *
 * @param string $path Path to JSON file
 * @param callable $callback Function that receives and returns modified data
 * @return mixed Result from callback
 * @throws Exception if operation fails
 */
function update_json_file($path, $callback) {
    $handle = fopen($path, 'c+');
    if ($handle === false) {
        throw new Exception("Could not open $path for updating.");
    }

    if (flock($handle, LOCK_EX)) {
        // Read current data
        $data = stream_get_contents($handle);
        $decoded = json_decode($data, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            flock($handle, LOCK_UN);
            fclose($handle);
            throw new Exception("JSON decode error in $path: " . json_last_error_msg());
        }

        // Apply callback
        $result = $callback($decoded);

        // Write updated data
        ftruncate($handle, 0);
        rewind($handle);

        $json = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            flock($handle, LOCK_UN);
            fclose($handle);
            throw new Exception("JSON encode error: " . json_last_error_msg());
        }

        fwrite($handle, $json);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $result;
    } else {
        fclose($handle);
        throw new Exception("Could not lock $path for updating.");
    }
}

/**
 * Get user by email from users.json
 *
 * @param string $email User email address
 * @return array|null User data or null if not found
 */
function get_user_by_email($email) {
    try {
        $users_data = read_json_file(USERS_FILE);
        $email = strtolower(trim($email));

        foreach ($users_data['users'] as $user) {
            if (strtolower($user['email']) === $email) {
                return $user;
            }
        }
        return null;
    } catch (Exception $e) {
        error_log("Error reading users: " . $e->getMessage());
        return null;
    }
}

/**
 * Add user to users.json
 *
 * @param string $email User email
 * @param array $alliances Alliance tags user can access
 * @param string $role User role (admin or alliance)
 * @return bool Success status
 */
function add_user($email, $alliances, $role = 'alliance') {
    try {
        return update_json_file(USERS_FILE, function(&$data) use ($email, $alliances, $role) {
            $email = strtolower(trim($email));

            // Check if user already exists
            foreach ($data['users'] as $user) {
                if (strtolower($user['email']) === $email) {
                    throw new Exception("User already exists: $email");
                }
            }

            // Add new user
            $data['users'][] = [
                'email' => $email,
                'alliances' => $alliances,
                'role' => $role
            ];

            return true;
        });
    } catch (Exception $e) {
        error_log("Error adding user: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user in users.json
 *
 * @param string $email User email
 * @param array $alliances New alliance tags
 * @param string $role New role
 * @return bool Success status
 */
function update_user($email, $alliances, $role) {
    try {
        return update_json_file(USERS_FILE, function(&$data) use ($email, $alliances, $role) {
            $email = strtolower(trim($email));
            $found = false;

            foreach ($data['users'] as &$user) {
                if (strtolower($user['email']) === $email) {
                    $user['alliances'] = $alliances;
                    $user['role'] = $role;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception("User not found: $email");
            }

            return true;
        });
    } catch (Exception $e) {
        error_log("Error updating user: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete user from users.json
 *
 * @param string $email User email
 * @return bool Success status
 */
function delete_user($email) {
    try {
        return update_json_file(USERS_FILE, function(&$data) use ($email) {
            $email = strtolower(trim($email));
            $original_count = count($data['users']);

            $data['users'] = array_values(array_filter($data['users'], function($user) use ($email) {
                return strtolower($user['email']) !== $email;
            }));

            if (count($data['users']) === $original_count) {
                throw new Exception("User not found: $email");
            }

            return true;
        });
    } catch (Exception $e) {
        error_log("Error deleting user: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if JWT ID is blacklisted
 *
 * @param string $jti JWT ID to check
 * @return bool True if blacklisted
 */
function is_token_blacklisted($jti) {
    try {
        $blacklist = read_json_file(BLACKLIST_FILE);
        return in_array($jti, $blacklist['jti'], true);
    } catch (Exception $e) {
        error_log("Error checking blacklist: " . $e->getMessage());
        return false;
    }
}

/**
 * Add JWT ID to blacklist
 *
 * @param string $jti JWT ID to blacklist
 * @param int $expiry Token expiry timestamp (optional)
 * @return bool Success status
 */
function blacklist_token($jti, $expiry = null) {
    try {
        return update_json_file(BLACKLIST_FILE, function(&$data) use ($jti, $expiry) {
            if (!in_array($jti, $data['jti'], true)) {
                $data['jti'][] = $jti;

                // Store expiry for cleanup
                if ($expiry !== null) {
                    $data['expires'][$jti] = $expiry;
                }
            }
            return true;
        });
    } catch (Exception $e) {
        error_log("Error blacklisting token: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up expired tokens from blacklist
 *
 * @return int Number of tokens removed
 */
function cleanup_blacklist() {
    try {
        $removed = 0;
        update_json_file(BLACKLIST_FILE, function(&$data) use (&$removed) {
            $now = time();
            $new_jti = [];
            $new_expires = [];

            foreach ($data['jti'] as $jti) {
                $expiry = $data['expires'][$jti] ?? null;

                // Keep tokens without expiry or not yet expired
                if ($expiry === null || $expiry > $now) {
                    $new_jti[] = $jti;
                    if ($expiry !== null) {
                        $new_expires[$jti] = $expiry;
                    }
                } else {
                    $removed++;
                }
            }

            $data['jti'] = $new_jti;
            $data['expires'] = $new_expires;

            return true;
        });

        return $removed;
    } catch (Exception $e) {
        error_log("Error cleaning blacklist: " . $e->getMessage());
        return 0;
    }
}
?>