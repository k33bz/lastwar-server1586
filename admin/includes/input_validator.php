<?php
/**
 * Input Validation and Sanitization Helpers
 *
 * Centralized validation functions for user inputs
 * Defense-in-depth: validate on both client and server
 *
 * Documentation:
 * - Security Issue: Input validation implementation
 *
 * GitHub Issues: https://github.com/k33bz/lastwar-server1586/issues
 *
 * @version 1.0.0
 * @date 2025-10-30
 */

/**
 * Validate and sanitize alliance tag
 *
 * @param string $tag Alliance tag input
 * @param bool $strict Require alphanumeric only (default: true)
 * @return array ['valid' => bool, 'sanitized' => string, 'error' => string|null]
 */
function validate_alliance_tag($tag, $strict = true) {
    // Trim and uppercase
    $sanitized = strtoupper(trim($tag));

    // Check if empty
    if (empty($sanitized)) {
        return ['valid' => false, 'sanitized' => '', 'error' => 'Alliance tag cannot be empty'];
    }

    // Length validation (2-10 characters typical for alliance tags)
    if (strlen($sanitized) < 2 || strlen($sanitized) > 10) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => 'Alliance tag must be 2-10 characters'];
    }

    // Character validation (alphanumeric only if strict)
    if ($strict && !preg_match('/^[A-Z0-9]+$/', $sanitized)) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => 'Alliance tag must contain only letters and numbers'];
    }

    return ['valid' => true, 'sanitized' => $sanitized, 'error' => null];
}

/**
 * Validate and sanitize alliance name
 *
 * @param string $name Alliance name input
 * @return array ['valid' => bool, 'sanitized' => string, 'error' => string|null]
 */
function validate_alliance_name($name) {
    // Trim whitespace
    $sanitized = trim($name);

    // Check if empty
    if (empty($sanitized)) {
        return ['valid' => false, 'sanitized' => '', 'error' => 'Alliance name cannot be empty'];
    }

    // Length validation (3-100 characters)
    if (strlen($sanitized) < 3) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => 'Alliance name must be at least 3 characters'];
    }

    if (strlen($sanitized) > 100) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => 'Alliance name cannot exceed 100 characters'];
    }

    return ['valid' => true, 'sanitized' => $sanitized, 'error' => null];
}

/**
 * Validate and sanitize alliance power value
 *
 * @param mixed $power Power value input
 * @return array ['valid' => bool, 'sanitized' => int, 'error' => string|null]
 */
function validate_alliance_power($power) {
    // Cast to integer
    $sanitized = (int)$power;

    // Check for negative values
    if ($sanitized < 0) {
        return ['valid' => false, 'sanitized' => 0, 'error' => 'Power cannot be negative'];
    }

    // Maximum reasonable power (10 trillion)
    if ($sanitized > 10000000000000) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => 'Power exceeds maximum allowed value'];
    }

    return ['valid' => true, 'sanitized' => $sanitized, 'error' => null];
}

/**
 * Validate and sanitize R5 name
 *
 * @param string $name R5 name input
 * @return array ['valid' => bool, 'sanitized' => string, 'error' => string|null]
 */
function validate_r5_name($name) {
    // Trim whitespace
    $sanitized = trim($name);

    // Check if empty
    if (empty($sanitized)) {
        return ['valid' => false, 'sanitized' => '', 'error' => 'R5 name cannot be empty'];
    }

    // Length validation (2-50 characters)
    if (strlen($sanitized) < 2) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => 'R5 name must be at least 2 characters'];
    }

    if (strlen($sanitized) > 50) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => 'R5 name cannot exceed 50 characters'];
    }

    return ['valid' => true, 'sanitized' => $sanitized, 'error' => null];
}

/**
 * Validate URL
 *
 * @param string $url URL input
 * @param bool $required Whether URL is required (default: false)
 * @return array ['valid' => bool, 'sanitized' => string, 'error' => string|null]
 */
function validate_url($url, $required = false) {
    // Trim whitespace
    $sanitized = trim($url);

    // If empty and not required, return valid
    if (empty($sanitized)) {
        if ($required) {
            return ['valid' => false, 'sanitized' => '', 'error' => 'URL is required'];
        }
        return ['valid' => true, 'sanitized' => '', 'error' => null];
    }

    // Validate URL format
    if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => 'Invalid URL format'];
    }

    // Check protocol (only http/https allowed)
    $parsed = parse_url($sanitized);
    if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => 'URL must use http or https protocol'];
    }

    return ['valid' => true, 'sanitized' => $sanitized, 'error' => null];
}

/**
 * Validate and sanitize text field
 *
 * @param string $text Text input
 * @param int $min_length Minimum length (default: 0)
 * @param int $max_length Maximum length (default: 1000)
 * @param bool $required Whether field is required (default: false)
 * @return array ['valid' => bool, 'sanitized' => string, 'error' => string|null]
 */
function validate_text_field($text, $min_length = 0, $max_length = 1000, $required = false) {
    // Trim whitespace
    $sanitized = trim($text);

    // Check if empty
    if (empty($sanitized)) {
        if ($required) {
            return ['valid' => false, 'sanitized' => '', 'error' => 'This field is required'];
        }
        return ['valid' => true, 'sanitized' => '', 'error' => null];
    }

    // Length validation
    $length = strlen($sanitized);

    if ($length < $min_length) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => "Must be at least {$min_length} characters"];
    }

    if ($length > $max_length) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => "Cannot exceed {$max_length} characters"];
    }

    return ['valid' => true, 'sanitized' => $sanitized, 'error' => null];
}

/**
 * Validate numeric field with range
 *
 * @param mixed $value Numeric input
 * @param int $min Minimum value (default: 0)
 * @param int $max Maximum value (default: PHP_INT_MAX)
 * @param bool $required Whether field is required (default: false)
 * @return array ['valid' => bool, 'sanitized' => int|null, 'error' => string|null]
 */
function validate_numeric_field($value, $min = 0, $max = PHP_INT_MAX, $required = false) {
    // If empty/null
    if ($value === null || $value === '') {
        if ($required) {
            return ['valid' => false, 'sanitized' => null, 'error' => 'This field is required'];
        }
        return ['valid' => true, 'sanitized' => null, 'error' => null];
    }

    // Cast to integer
    $sanitized = (int)$value;

    // Range validation
    if ($sanitized < $min) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => "Value must be at least {$min}"];
    }

    if ($sanitized > $max) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => "Value cannot exceed {$max}"];
    }

    return ['valid' => true, 'sanitized' => $sanitized, 'error' => null];
}

/**
 * Validate that alliance tags exist in alliances.json
 *
 * @param array $tags Array of alliance tags to validate
 * @param array $valid_alliances Array of valid alliance data
 * @return array ['valid' => bool, 'sanitized' => array, 'error' => string|null]
 */
function validate_alliance_list($tags, $valid_alliances) {
    if (!is_array($tags)) {
        return ['valid' => false, 'sanitized' => [], 'error' => 'Alliance list must be an array'];
    }

    if (empty($tags)) {
        return ['valid' => false, 'sanitized' => [], 'error' => 'At least one alliance must be selected'];
    }

    // Get valid alliance tags from alliance data
    $valid_tags = array_column($valid_alliances, 'tag');
    $valid_tags = array_map('strtoupper', $valid_tags);

    // Sanitize and validate each tag
    $sanitized = [];
    foreach ($tags as $tag) {
        $tag_upper = strtoupper(trim($tag));

        // Special case: '*' means all alliances (admin)
        if ($tag_upper === '*') {
            $sanitized[] = '*';
            continue;
        }

        // Check if tag exists
        if (!in_array($tag_upper, $valid_tags)) {
            return ['valid' => false, 'sanitized' => $sanitized, 'error' => "Alliance '{$tag}' does not exist"];
        }

        $sanitized[] = $tag_upper;
    }

    return ['valid' => true, 'sanitized' => $sanitized, 'error' => null];
}

/**
 * Validate emoji/icon character
 *
 * @param string $icon Icon/emoji input
 * @return array ['valid' => bool, 'sanitized' => string, 'error' => string|null]
 */
function validate_icon($icon) {
    // Trim whitespace
    $sanitized = trim($icon);

    // Check if empty (default will be used)
    if (empty($sanitized)) {
        return ['valid' => true, 'sanitized' => '🏷️', 'error' => null];
    }

    // Check length (emoji can be 1-4 bytes in UTF-8)
    if (mb_strlen($sanitized, 'UTF-8') > 4) {
        return ['valid' => false, 'sanitized' => $sanitized, 'error' => 'Icon must be a single emoji character'];
    }

    return ['valid' => true, 'sanitized' => $sanitized, 'error' => null];
}
?>
