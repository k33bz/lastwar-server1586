<?php
/**
 * Content Security Policy (CSP) Configuration
 *
 * Implements CSP headers to prevent XSS attacks and other security vulnerabilities
 *
 * @version 1.0.0
 * @created 2025-11-12
 *
 * Features:
 * - Prevents XSS attacks by restricting script sources
 * - Blocks unauthorized external resources
 * - Prevents clickjacking with frame-ancestors
 * - Uses nonces for inline scripts and styles
 * - Report-only mode for testing
 *
 * Usage:
 *   require_once __DIR__ . '/csp.php';
 *   $csp_nonce = generate_csp_nonce();
 *   apply_csp_headers($csp_nonce);
 *   // Then use <?php echo csp_nonce(); ?> in script/style tags
 */

/**
 * Generate a cryptographically secure nonce for CSP
 *
 * @return string Base64-encoded random nonce
 */
function generate_csp_nonce(): string {
    // Generate 16 bytes of random data
    $nonce = random_bytes(16);

    // Base64 encode for use in CSP header
    return base64_encode($nonce);
}

/**
 * Store nonce in global scope for easy access
 */
function set_csp_nonce(string $nonce): void {
    $GLOBALS['csp_nonce'] = $nonce;
}

/**
 * Get the current CSP nonce
 *
 * @return string The CSP nonce for this request
 */
function get_csp_nonce(): string {
    return $GLOBALS['csp_nonce'] ?? '';
}

/**
 * Helper function to output nonce attribute in templates
 *
 * @return string Formatted nonce attribute
 */
function csp_nonce(): string {
    $nonce = get_csp_nonce();
    return $nonce ? "nonce=\"{$nonce}\"" : '';
}

/**
 * Apply Content Security Policy headers
 *
 * @param string $nonce The nonce to use for inline scripts/styles
 * @param bool $report_only If true, uses Content-Security-Policy-Report-Only
 */
function apply_csp_headers(string $nonce, bool $report_only = false): void {
    // Define CSP directives
    $directives = [
        // Default source: only allow from same origin
        "default-src 'self'",

        // Scripts: self + nonce for inline scripts + unsafe-hashes for event handlers
        // 'unsafe-inline' is fallback for browsers that don't support nonces
        // 'unsafe-hashes' allows inline event handlers (onclick, etc.)
        "script-src 'self' 'nonce-{$nonce}' 'unsafe-inline' 'unsafe-hashes'",

        // Styles: self + nonce for inline styles + unsafe-hashes for style attributes
        // 'unsafe-hashes' allows inline style attributes
        "style-src 'self' 'nonce-{$nonce}' 'unsafe-inline' 'unsafe-hashes'",

        // Images: self + data URIs (for base64 images)
        "img-src 'self' data:",

        // Fonts: self + data URIs
        "font-src 'self' data:",

        // AJAX/Fetch: only same origin
        "connect-src 'self'",

        // Forms: only submit to same origin
        "form-action 'self'",

        // Frames: completely block embedding in frames (clickjacking protection)
        "frame-ancestors 'none'",

        // Base URI: restrict to self
        "base-uri 'self'",

        // Object/Embed: block all plugins
        "object-src 'none'",

        // Media: self only
        "media-src 'self'",

        // Worker scripts: self only
        "worker-src 'self'",

        // Manifest: self only
        "manifest-src 'self'",

        // Upgrade insecure requests (HTTP -> HTTPS)
        "upgrade-insecure-requests",
    ];

    // Join directives with semicolons
    $csp_value = implode('; ', $directives);

    // Send header
    $header_name = $report_only ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
    header("{$header_name}: {$csp_value}");
}

/**
 * Initialize CSP system
 *
 * Call this early in the request lifecycle, before any output
 *
 * @param bool $report_only If true, uses report-only mode (for testing)
 * @return string The generated nonce
 */
function init_csp(bool $report_only = false): string {
    // Generate nonce
    $nonce = generate_csp_nonce();

    // Store in global scope
    set_csp_nonce($nonce);

    // Apply CSP headers
    apply_csp_headers($nonce, $report_only);

    return $nonce;
}
