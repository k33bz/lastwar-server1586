<?php
/**
 * Enhanced JWT Middleware with Automatic Rotation
 *
 * Drop-in replacement for require_jwt_session() with automatic token rotation
 *
 * @version 1.0.0
 * @date 2025-10-15
 */

if (!defined('ADMIN_INIT')) {
    define('ADMIN_INIT', true);
}
require_once __DIR__ . '/token_rotation.php';

/**
 * Enhanced JWT session validation with automatic rotation
 * Use this instead of require_jwt_session() in protected pages
 *
 * @return object Decoded JWT token
 */
function require_enhanced_jwt_session() {
    return require_jwt_session_with_rotation();
}

/**
 * Enhanced admin session validation with rotation
 *
 * @return object Decoded JWT token
 */
function require_enhanced_admin_session() {
    $token = require_jwt_session_with_rotation();
    
    if ($token->aud !== 'admin') {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }
    
    return $token;
}

/**
 * Add rotation headers to API responses
 * Call this in API endpoints to inform client about token rotation
 *
 * @param object $token Current token
 */
function add_rotation_headers($token) {
    $time_to_rotation = ($token->exp - time()) - (SESSION_TOKEN_EXPIRY * TOKEN_ROTATION_THRESHOLD);
    
    if ($time_to_rotation > 0) {
        header('X-Token-Rotation-In: ' . $time_to_rotation);
    } else {
        header('X-Token-Rotated: true');
    }
}

/**
 * JavaScript helper for client-side token rotation detection
 *
 * @return string JavaScript code
 */
function get_rotation_javascript() {
    return <<<'JS'
<script>
// JWT Token Rotation Helper
(function() {
    let rotationCheckInterval;
    
    function checkTokenRotation() {
        // Check for rotation headers in AJAX responses
        const originalXHR = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function() {
            this.addEventListener('load', function() {
                if (this.getResponseHeader('X-Token-Rotated')) {
                    console.log('JWT token was rotated by server');
                    // Token was rotated, cookie is already updated
                }
                
                const rotationIn = this.getResponseHeader('X-Token-Rotation-In');
                if (rotationIn) {
                    const seconds = parseInt(rotationIn);
                    console.log(`Token will rotate in ${seconds} seconds`);
                }
            });
            return originalXHR.apply(this, arguments);
        };
    }
    
    // Initialize rotation monitoring
    checkTokenRotation();
    
    // Optional: Proactive rotation check every 5 minutes
    rotationCheckInterval = setInterval(function() {
        fetch(window.location.pathname, {
            method: 'HEAD',
            credentials: 'same-origin'
        }).then(response => {
            if (response.headers.get('X-Token-Rotated')) {
                console.log('Token rotated during background check');
            }
        }).catch(err => {
            console.log('Background token check failed:', err);
        });
    }, 5 * 60 * 1000); // 5 minutes
})();
</script>
JS;
}
?>