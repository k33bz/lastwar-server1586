<?php
/**
 * Email Utilities
 * Shared functions for email masking and display
 */

/**
 * Mask email address for privacy
 * @param string $email The email to mask
 * @return string Masked email
 */
function maskEmail($email) {
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return $email; // Invalid email format
    }
    
    $username = $parts[0];
    $domain = $parts[1];
    
    if (strlen($username) <= 2) {
        return $username . '@' . $domain; // Too short to mask
    }
    
    $masked = $username[0] . str_repeat('*', strlen($username) - 2) . $username[strlen($username) - 1];
    return $masked . '@' . $domain;
}

/**
 * Generate email display HTML with toggle functionality
 * @param string $email The email address
 * @param bool $showToggle Whether to show the toggle button
 * @return string HTML for email display
 */
function emailDisplay($email, $showToggle = true) {
    $masked = maskEmail($email);
    $html = '<span class="email-text email-masked" data-email="' . htmlspecialchars($email) . '" data-masked="' . htmlspecialchars($masked) . '">';
    $html .= htmlspecialchars($masked);
    $html .= '</span>';
    
    if ($showToggle) {
        $html .= ' <button class="email-toggle-btn" onclick="toggleSingleEmail(this)" title="Show email">';
        $html .= '<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
        $html .= '</button>';
    }
    
    return $html;
}
?>