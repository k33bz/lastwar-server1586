<?php
/**
 * Multi-Factor Authentication System
 *
 * Provides TOTP-based 2FA, backup codes, and hardware key support
 *
 * @version 1.0.0
 * @date 2025-10-15
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/json_helpers.php';

/**
 * Generate TOTP secret for user
 *
 * @return string Base32 encoded secret
 */
function generate_totp_secret() {
    $secret = '';
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    
    for ($i = 0; $i < 32; $i++) {
        $secret .= $chars[random_int(0, 31)];
    }
    
    return $secret;
}

/**
 * Generate QR code URL for TOTP setup
 *
 * @param string $email User email
 * @param string $secret TOTP secret
 * @return string QR code URL
 */
function generate_qr_code_url($email, $secret) {
    $issuer = 'Last War 1586 Admin';
    $label = urlencode($issuer . ':' . $email);
    $params = http_build_query([
        'secret' => $secret,
        'issuer' => $issuer,
        'algorithm' => 'SHA1',
        'digits' => 6,
        'period' => 30
    ]);
    
    $otpauth_url = "otpauth://totp/{$label}?{$params}";
    return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($otpauth_url);
}

/**
 * Verify TOTP code
 *
 * @param string $secret User's TOTP secret
 * @param string $code 6-digit code from authenticator
 * @param int $window Time window tolerance (default: 1 = ±30 seconds)
 * @return bool True if code is valid
 */
function verify_totp_code($secret, $code, $window = 1) {
    $time = floor(time() / 30);
    
    for ($i = -$window; $i <= $window; $i++) {
        $calculated_code = generate_totp_code($secret, $time + $i);
        if (hash_equals($calculated_code, $code)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Generate TOTP code for given time
 *
 * @param string $secret Base32 secret
 * @param int $time Time counter
 * @return string 6-digit code
 */
function generate_totp_code($secret, $time) {
    $secret = base32_decode($secret);
    $time = pack('N*', 0) . pack('N*', $time);
    $hash = hash_hmac('sha1', $time, $secret, true);
    $offset = ord($hash[19]) & 0xf;
    $code = (
        ((ord($hash[$offset + 0]) & 0x7f) << 24) |
        ((ord($hash[$offset + 1]) & 0xff) << 16) |
        ((ord($hash[$offset + 2]) & 0xff) << 8) |
        (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;
    
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

/**
 * Base32 decode function
 */
function base32_decode($input) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $v = 0;
    $vbits = 0;
    
    for ($i = 0, $j = strlen($input); $i < $j; $i++) {
        $v <<= 5;
        $v += strpos($alphabet, $input[$i]);
        $vbits += 5;
        
        if ($vbits >= 8) {
            $output .= chr($v >> ($vbits - 8));
            $vbits -= 8;
        }
    }
    
    return $output;
}

/**
 * Generate backup codes for user
 *
 * @param int $count Number of codes to generate
 * @return array Array of backup codes
 */
function generate_backup_codes($count = 10) {
    $codes = [];
    
    for ($i = 0; $i < $count; $i++) {
        // Generate 8-character alphanumeric code
        $code = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        
        for ($j = 0; $j < 8; $j++) {
            $code .= $chars[random_int(0, 35)];
        }
        
        $codes[] = $code;
    }
    
    return $codes;
}

/**
 * Enable MFA for user
 *
 * @param string $email User email
 * @param string $totp_secret TOTP secret
 * @param array $backup_codes Backup codes
 * @return bool Success status
 */
function enable_user_mfa($email, $totp_secret, $backup_codes) {
    try {
        return update_json_file(USERS_FILE, function(&$data) use ($email, $totp_secret, $backup_codes) {
            $email = strtolower(trim($email));
            
            foreach ($data['users'] as &$user) {
                if (strtolower($user['email']) === $email) {
                    $user['mfa'] = [
                        'enabled' => true,
                        'totp_secret' => $totp_secret,
                        'backup_codes' => $backup_codes,
                        'enabled_at' => time(),
                        'last_used' => null
                    ];
                    return true;
                }
            }
            
            return false;
        });
    } catch (Exception $e) {
        error_log("Failed to enable MFA for $email: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify MFA code (TOTP or backup code)
 *
 * @param string $email User email
 * @param string $code Code to verify
 * @return bool True if code is valid
 */
function verify_mfa_code($email, $code) {
    try {
        $user = get_user_by_email($email);
        
        if (!$user || !isset($user['mfa']) || !$user['mfa']['enabled']) {
            return false;
        }
        
        $mfa = $user['mfa'];
        
        // Try TOTP first
        if (verify_totp_code($mfa['totp_secret'], $code)) {
            // Update last used timestamp
            update_mfa_last_used($email);
            return true;
        }
        
        // Try backup codes
        if (in_array($code, $mfa['backup_codes'])) {
            // Remove used backup code
            remove_backup_code($email, $code);
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("MFA verification failed for $email: " . $e->getMessage());
        return false;
    }
}

/**
 * Update MFA last used timestamp
 */
function update_mfa_last_used($email) {
    update_json_file(USERS_FILE, function(&$data) use ($email) {
        $email = strtolower(trim($email));
        
        foreach ($data['users'] as &$user) {
            if (strtolower($user['email']) === $email && isset($user['mfa'])) {
                $user['mfa']['last_used'] = time();
                return true;
            }
        }
        
        return false;
    });
}

/**
 * Remove used backup code
 */
function remove_backup_code($email, $code) {
    update_json_file(USERS_FILE, function(&$data) use ($email, $code) {
        $email = strtolower(trim($email));
        
        foreach ($data['users'] as &$user) {
            if (strtolower($user['email']) === $email && isset($user['mfa'])) {
                $user['mfa']['backup_codes'] = array_values(
                    array_filter($user['mfa']['backup_codes'], function($c) use ($code) {
                        return $c !== $code;
                    })
                );
                return true;
            }
        }
        
        return false;
    });
}

/**
 * Check if user has MFA enabled
 *
 * @param string $email User email
 * @return bool True if MFA is enabled
 */
function user_has_mfa($email) {
    $user = get_user_by_email($email);
    return $user && isset($user['mfa']) && $user['mfa']['enabled'];
}

/**
 * Disable MFA for user
 *
 * @param string $email User email
 * @return bool Success status
 */
function disable_user_mfa($email) {
    try {
        return update_json_file(USERS_FILE, function(&$data) use ($email) {
            $email = strtolower(trim($email));
            
            foreach ($data['users'] as &$user) {
                if (strtolower($user['email']) === $email) {
                    $user['mfa'] = [
                        'enabled' => false,
                        'disabled_at' => time()
                    ];
                    return true;
                }
            }
            
            return false;
        });
    } catch (Exception $e) {
        error_log("Failed to disable MFA for $email: " . $e->getMessage());
        return false;
    }
}
?>