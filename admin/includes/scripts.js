/**
 * Admin Panel Shared JavaScript Utilities
 *
 * Common JavaScript functions used across admin pages
 *
 * @version 1.5.0
 * @date 2025-11-19
 * @changelog
 *   1.5.0 (2025-11-19) - Added automatic session expiration monitoring
 *                       - Decodes JWT cookie to check expiration timestamp
 *                       - Auto-redirects to login when session expires
 *                       - Checks every 30 seconds for expired sessions
 *   1.4.0 (2025-11-06) - Added comprehensive form validation library (Issue #20)
 *                       - Client-side validators matching PHP backend
 *                       - Real-time field validation with visual feedback
 *                       - Reusable validation framework for all forms
 *   1.3.0 (2025-10-28) - Added CSRF protection to API requests (Issue #21)
 *                       - getCsrfToken() extracts token from meta tag
 *                       - apiRequest() automatically includes X-CSRF-Token header for POST/PUT/DELETE/PATCH
 *                       - Timing-attack-safe validation on server side
 *   1.2.0 (2025-10-16) - Enhanced confirmAction() and added alertModal() to replace alert() and confirm()
 *                       - Added danger mode, custom button text, animations
 *                       - Added keyboard support (ESC, Enter)
 *   1.1.0 (2025-10-16) - Added closeModalOnBackdrop() for help modal support
 *   1.0.0 (2025-10-16) - Initial consolidated utilities
 */

// ============================================================================
// Session Expiration Monitoring
// ============================================================================

// Global variable to store token expiration (set by PHP in header)
window.SESSION_EXPIRY = window.SESSION_EXPIRY || null;

// Track if we've already shown a redirect to prevent loops
let sessionCheckRedirecting = false;

/**
 * Check if session has expired and redirect if needed
 * Shows warning modal before expiration
 */
function checkSessionExpiration() {
    // Don't check if we're already in the process of redirecting
    if (sessionCheckRedirecting) {
        return;
    }

    // If no expiry time provided by server, skip check
    if (!window.SESSION_EXPIRY) {
        return;
    }

    const now = Math.floor(Date.now() / 1000); // Current time in seconds
    const expiresAt = window.SESSION_EXPIRY;
    const timeUntilExpiry = expiresAt - now;

    if (timeUntilExpiry <= 0) {
        // Token has expired - redirect to login (only if not already redirecting)
        if (!sessionCheckRedirecting) {
            console.warn('[SESSION] Token expired, redirecting to login');
            sessionCheckRedirecting = true;
            window.location.href = 'login.php?error=expired';
        }
    } else if (timeUntilExpiry <= 300 && timeUntilExpiry > 0) {
        // Less than 5 minutes remaining - show warning modal
        showSessionExpirationWarning(timeUntilExpiry);
    }
}

/**
 * Show session expiration warning modal
 * @param {number} secondsRemaining - Seconds until expiration
 */
function showSessionExpirationWarning(secondsRemaining) {
    // Check if modal already exists
    let modal = document.getElementById('session-expiration-modal');

    if (!modal) {
        // Create modal
        modal = document.createElement('div');
        modal.id = 'session-expiration-modal';
        modal.innerHTML = `
            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <h3 style="margin: 0 0 1rem 0; color: #ff6b6b;">⚠️ Session Expiring Soon</h3>
                    <p style="margin: 0 0 1.5rem 0; color: #333;">Your session will expire in <strong id="expiry-countdown">--</strong>.</p>
                    <p style="margin: 0 0 1.5rem 0; color: #666; font-size: 0.9rem;">Click "Extend Session" to continue working, or you'll be logged out automatically.</p>
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button id="logout-now-btn" style="padding: 0.75rem 1.5rem; border: 1px solid #ccc; background: white; border-radius: 6px; cursor: pointer;">
                            Logout Now
                        </button>
                        <button id="extend-session-btn" style="padding: 0.75rem 1.5rem; border: none; background: #667eea; color: white; border-radius: 6px; cursor: pointer; font-weight: 600;">
                            Extend Session
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Add event listeners
        document.getElementById('logout-now-btn').addEventListener('click', function() {
            window.location.href = 'logout.php';
        });

        document.getElementById('extend-session-btn').addEventListener('click', refreshSessionToken);
    }

    // Update countdown
    const minutes = Math.floor(secondsRemaining / 60);
    const seconds = secondsRemaining % 60;
    const countdownEl = document.getElementById('expiry-countdown');
    if (countdownEl) {
        countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }
}

/**
 * Refresh the JWT token
 */
async function refreshSessionToken() {
    const extendBtn = document.getElementById('extend-session-btn');
    const originalText = extendBtn.textContent;

    try {
        extendBtn.textContent = 'Refreshing...';
        extendBtn.disabled = true;

        const response = await fetch('refresh_token_api.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            // Update global SESSION_EXPIRY with new expiration time
            window.SESSION_EXPIRY = data.expires_at;

            // Success - close modal and reset monitoring
            const modal = document.getElementById('session-expiration-modal');
            if (modal) {
                modal.remove();
            }

            console.log('[SESSION] Token refreshed successfully - new expiry:', new Date(data.expires_at * 1000).toLocaleString());

            // Show success message
            showToast('Session extended successfully!', 'success');
        } else {
            throw new Error(data.error || 'Failed to refresh session');
        }
    } catch (error) {
        console.error('[SESSION] Failed to refresh token:', error);
        extendBtn.textContent = originalText;
        extendBtn.disabled = false;
        showToast('Failed to extend session. Please login again.', 'error');
    }
}

/**
 * Show a toast notification
 * @param {string} message - Message to display
 * @param {string} type - Type of toast (success, error, info)
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#667eea'};
        color: white;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10001;
        animation: slideIn 0.3s ease-out;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Initialize session expiration monitoring
 * Checks every 30 seconds for expired sessions
 */
function initSessionMonitoring() {
    // Check immediately on page load
    checkSessionExpiration();

    // Check every 30 seconds
    setInterval(checkSessionExpiration, 30000);
}

// Start monitoring when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSessionMonitoring);
} else {
    initSessionMonitoring();
}

// ============================================================================
// Email Masking & Display
// ============================================================================

/**
 * Mask an email address for privacy
 * @param {string} email - The email to mask
 * @returns {string} Masked email
 */
function maskEmail(email) {
    const parts = email.split('@');
    if (parts.length !== 2) {
        return email; // Invalid email format
    }

    const username = parts[0];
    const domain = parts[1];

    if (username.length <= 2) {
        return username + '@' + domain; // Too short to mask
    }

    const masked = username[0] + '*'.repeat(username.length - 2) + username[username.length - 1];
    return masked + '@' + domain;
}

/**
 * Toggle single email display between masked and full
 * @param {HTMLElement} button - The toggle button element
 */
function toggleSingleEmail(button) {
    const emailSpan = button.previousElementSibling;
    const isShowingMasked = emailSpan.classList.contains('email-masked');

    if (isShowingMasked) {
        // Show full email
        emailSpan.textContent = emailSpan.dataset.email;
        emailSpan.classList.remove('email-masked');
        emailSpan.classList.add('email-text');
        button.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
        button.title = 'Hide email';
    } else {
        // Show masked email
        emailSpan.textContent = emailSpan.dataset.masked;
        emailSpan.classList.remove('email-text');
        emailSpan.classList.add('email-masked');
        button.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
        button.title = 'Show email';
    }
}

/**
 * Toggle all emails on page between masked and full
 */
function toggleAllEmails() {
    const emailSpans = document.querySelectorAll('.email-text, .email-masked');
    const isShowingMasked = emailSpans[0]?.classList.contains('email-masked');

    emailSpans.forEach(span => {
        if (isShowingMasked) {
            span.textContent = span.dataset.email;
            span.classList.remove('email-masked');
            span.classList.add('email-text');
        } else {
            span.textContent = span.dataset.masked;
            span.classList.remove('email-text');
            span.classList.add('email-masked');
        }
    });
}

// ============================================================================
// Modal Management
// ============================================================================

/**
 * Open a modal by ID
 * @param {string} modalId - The ID of the modal to open
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        modal.classList.add('show');
    }
}

/**
 * Close a modal by ID
 * @param {string} modalId - The ID of the modal to close
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
}

/**
 * Close all open modals
 */
function closeAllModals() {
    const modals = document.querySelectorAll('.modal.show');
    modals.forEach(modal => {
        modal.style.display = 'none';
        modal.classList.remove('show');
    });
}

/**
 * Close modal when clicking on backdrop
 * @param {Event} event - Click event
 * @param {string} modalId - The ID of the modal to close
 */
function closeModalOnBackdrop(event, modalId) {
    if (event.target.id === modalId) {
        closeModal(modalId);
    }
}

/**
 * Initialize modal event listeners
 */
function initializeModals() {
    // Close modals on outside click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                this.classList.remove('show');
            }
        });
    });

    // Close modals on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

// ============================================================================
// Toast Notifications
// ============================================================================

/**
 * Show a toast notification
 * @param {string} message - The message to display
 * @param {string} type - Type of toast: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Duration in ms (default: 3000)
 */
function showToast(message, type = 'info', duration = 3000) {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(toastContainer);
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;

    // Toast styles
    const colors = {
        success: { bg: '#d4edda', border: '#c3e6cb', text: '#155724' },
        error: { bg: '#f8d7da', border: '#f5c6cb', text: '#721c24' },
        warning: { bg: '#fff3cd', border: '#ffeaa7', text: '#856404' },
        info: { bg: '#d1ecf1', border: '#bee5eb', text: '#0c5460' }
    };

    const color = colors[type] || colors.info;
    toast.style.cssText = `
        background: ${color.bg};
        border: 1px solid ${color.border};
        color: ${color.text};
        padding: 12px 20px;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        min-width: 250px;
        max-width: 400px;
        font-size: 14px;
        animation: slideInRight 0.3s ease;
    `;

    toastContainer.appendChild(toast);

    // Auto-remove after duration
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            toast.remove();
            // Remove container if empty
            if (toastContainer.children.length === 0) {
                toastContainer.remove();
            }
        }, 300);
    }, duration);
}

// Add toast, modal, and validation styles to page
if (!document.getElementById('toast-animations')) {
    const style = document.createElement('style');
    style.id = 'toast-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        /* Modal button styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }
        /* Modal overlay transition */
        .modal {
            transition: opacity 0.3s ease;
        }
        /* Form validation styles */
        .is-invalid {
            border-color: #dc3545 !important;
            background-color: #fff5f5 !important;
        }
        .is-valid {
            border-color: #28a745 !important;
            background-color: #f0fff4 !important;
        }
        .invalid-feedback {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
            display: block;
        }
    `;
    document.head.appendChild(style);
}

// ============================================================================
// Form Utilities
// ============================================================================

/**
 * Serialize form data to FormData object
 * @param {HTMLFormElement} form - The form element
 * @returns {FormData}
 */
function serializeForm(form) {
    return new FormData(form);
}

/**
 * Serialize form data to JSON object
 * @param {HTMLFormElement} form - The form element
 * @returns {Object}
 */
function serializeFormJSON(form) {
    const formData = new FormData(form);
    const data = {};
    for (let [key, value] of formData.entries()) {
        if (data[key]) {
            if (!Array.isArray(data[key])) {
                data[key] = [data[key]];
            }
            data[key].push(value);
        } else {
            data[key] = value;
        }
    }
    return data;
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean}
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Reset form and clear validation errors
 * @param {HTMLFormElement} form - The form element
 */
function resetForm(form) {
    form.reset();
    form.querySelectorAll('.error').forEach(el => el.remove());
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
}

// ============================================================================
// Form Validation Library
// ============================================================================

/**
 * Validation result object
 * @typedef {Object} ValidationResult
 * @property {boolean} valid - Whether validation passed
 * @property {string} error - Error message if validation failed
 */

/**
 * Validate alliance tag (2-10 chars, alphanumeric uppercase)
 * @param {string} tag - Alliance tag to validate
 * @param {boolean} strict - Enforce uppercase requirement
 * @returns {ValidationResult}
 */
function validateAllianceTag(tag, strict = true) {
    const sanitized = tag.trim().toUpperCase();

    if (sanitized.length < 2 || sanitized.length > 10) {
        return { valid: false, error: 'Alliance tag must be 2-10 characters' };
    }

    if (!/^[A-Z0-9]+$/.test(sanitized)) {
        return { valid: false, error: 'Alliance tag must contain only letters and numbers' };
    }

    return { valid: true, error: '' };
}

/**
 * Validate alliance name (3-100 chars)
 * @param {string} name - Alliance name to validate
 * @returns {ValidationResult}
 */
function validateAllianceName(name) {
    const sanitized = name.trim();

    if (sanitized.length < 3) {
        return { valid: false, error: 'Alliance name must be at least 3 characters' };
    }

    if (sanitized.length > 100) {
        return { valid: false, error: 'Alliance name must not exceed 100 characters' };
    }

    return { valid: true, error: '' };
}

/**
 * Validate alliance power (0 to 10 trillion)
 * @param {number|string} power - Power value to validate
 * @returns {ValidationResult}
 */
function validateAlliancePower(power) {
    const num = parseInt(power);

    if (isNaN(num)) {
        return { valid: false, error: 'Power must be a valid number' };
    }

    if (num < 0) {
        return { valid: false, error: 'Power cannot be negative' };
    }

    if (num > 10000000000000) {
        return { valid: false, error: 'Power exceeds maximum allowed value (10 trillion)' };
    }

    return { valid: true, error: '' };
}

/**
 * Validate R5 name (3-50 chars)
 * @param {string} name - R5 name to validate
 * @returns {ValidationResult}
 */
function validateR5Name(name) {
    const sanitized = name.trim();

    if (sanitized.length < 3) {
        return { valid: false, error: 'R5 name must be at least 3 characters' };
    }

    if (sanitized.length > 50) {
        return { valid: false, error: 'R5 name must not exceed 50 characters' };
    }

    return { valid: true, error: '' };
}

/**
 * Validate URL format
 * @param {string} url - URL to validate
 * @param {boolean} required - Whether field is required
 * @returns {ValidationResult}
 */
function validateURL(url, required = false) {
    const sanitized = url.trim();

    if (!sanitized && !required) {
        return { valid: true, error: '' };
    }

    if (!sanitized && required) {
        return { valid: false, error: 'URL is required' };
    }

    try {
        new URL(sanitized);
        return { valid: true, error: '' };
    } catch {
        return { valid: false, error: 'Invalid URL format' };
    }
}

/**
 * Validate text field with length constraints
 * @param {string} text - Text to validate
 * @param {number} min - Minimum length
 * @param {number} max - Maximum length
 * @param {boolean} required - Whether field is required
 * @returns {ValidationResult}
 */
function validateTextField(text, min = 0, max = 1000, required = false) {
    const sanitized = text.trim();

    if (!sanitized && !required) {
        return { valid: true, error: '' };
    }

    if (!sanitized && required) {
        return { valid: false, error: 'This field is required' };
    }

    if (sanitized.length < min) {
        return { valid: false, error: `Must be at least ${min} characters` };
    }

    if (sanitized.length > max) {
        return { valid: false, error: `Must not exceed ${max} characters` };
    }

    return { valid: true, error: '' };
}

/**
 * Validate numeric field with range constraints
 * @param {number|string} value - Value to validate
 * @param {number} min - Minimum value
 * @param {number} max - Maximum value
 * @param {boolean} required - Whether field is required
 * @returns {ValidationResult}
 */
function validateNumericField(value, min = 0, max = Number.MAX_SAFE_INTEGER, required = false) {
    const str = String(value).trim();

    if (!str && !required) {
        return { valid: true, error: '' };
    }

    if (!str && required) {
        return { valid: false, error: 'This field is required' };
    }

    const num = parseFloat(value);

    if (isNaN(num)) {
        return { valid: false, error: 'Must be a valid number' };
    }

    if (num < min) {
        return { valid: false, error: `Must be at least ${min}` };
    }

    if (num > max) {
        return { valid: false, error: `Must not exceed ${max}` };
    }

    return { valid: true, error: '' };
}

/**
 * Validate Discord channel ID (18-20 digit numeric)
 * @param {string} channelId - Discord channel ID to validate
 * @param {boolean} required - Whether field is required
 * @returns {ValidationResult}
 */
function validateDiscordChannelId(channelId, required = false) {
    const sanitized = channelId.trim();

    if (!sanitized && !required) {
        return { valid: true, error: '' };
    }

    if (!sanitized && required) {
        return { valid: false, error: 'Discord channel ID is required' };
    }

    if (!/^\d{18,20}$/.test(sanitized)) {
        return { valid: false, error: 'Discord channel ID must be 18-20 digits' };
    }

    return { valid: true, error: '' };
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @param {boolean} required - Whether field is required
 * @returns {ValidationResult}
 */
function validateEmail(email, required = true) {
    const sanitized = email.trim();

    if (!sanitized && !required) {
        return { valid: true, error: '' };
    }

    if (!sanitized && required) {
        return { valid: false, error: 'Email is required' };
    }

    if (!isValidEmail(sanitized)) {
        return { valid: false, error: 'Invalid email format' };
    }

    return { valid: true, error: '' };
}

/**
 * Show validation error on a field
 * @param {HTMLInputElement} field - Input field element
 * @param {string} error - Error message
 */
function showFieldError(field, error) {
    // Remove existing error
    clearFieldError(field);

    // Add invalid class
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');

    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.style.cssText = 'color: #dc3545; font-size: 0.875em; margin-top: 0.25rem; display: block;';
    errorDiv.textContent = error;

    // Insert error after field
    field.parentNode.insertBefore(errorDiv, field.nextSibling);
}

/**
 * Clear validation error from a field
 * @param {HTMLInputElement} field - Input field element
 */
function clearFieldError(field) {
    field.classList.remove('is-invalid');

    // Remove error message if exists
    const errorDiv = field.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
        errorDiv.remove();
    }
}

/**
 * Mark field as valid
 * @param {HTMLInputElement} field - Input field element
 */
function markFieldValid(field) {
    clearFieldError(field);
    field.classList.add('is-valid');
}

/**
 * Validate a single field based on its data-validate attribute
 * @param {HTMLInputElement} field - Input field element
 * @returns {boolean} - Whether validation passed
 */
function validateField(field) {
    const validateType = field.dataset.validate;
    const required = field.hasAttribute('required') || field.dataset.required === 'true';
    const value = field.value;

    let result = { valid: true, error: '' };

    // Run appropriate validator
    switch (validateType) {
        case 'email':
            result = validateEmail(value, required);
            break;
        case 'alliance-tag':
            result = validateAllianceTag(value, true);
            break;
        case 'alliance-name':
            result = validateAllianceName(value);
            break;
        case 'alliance-power':
            result = validateAlliancePower(value);
            break;
        case 'r5-name':
            result = validateR5Name(value);
            break;
        case 'url':
            result = validateURL(value, required);
            break;
        case 'discord-channel-id':
            result = validateDiscordChannelId(value, required);
            break;
        case 'text':
            const min = parseInt(field.dataset.min) || 0;
            const max = parseInt(field.dataset.max) || 1000;
            result = validateTextField(value, min, max, required);
            break;
        case 'number':
            const numMin = parseFloat(field.dataset.min) || 0;
            const numMax = parseFloat(field.dataset.max) || Number.MAX_SAFE_INTEGER;
            result = validateNumericField(value, numMin, numMax, required);
            break;
        default:
            // No validation specified
            if (required && !value.trim()) {
                result = { valid: false, error: 'This field is required' };
            }
    }

    // Show/hide error
    if (!result.valid) {
        showFieldError(field, result.error);
        return false;
    } else {
        markFieldValid(field);
        return true;
    }
}

/**
 * Validate all fields in a form
 * @param {HTMLFormElement} form - Form element
 * @returns {boolean} - Whether all validation passed
 */
function validateForm(form) {
    const fields = form.querySelectorAll('[data-validate], [required]');
    let isValid = true;

    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });

    return isValid;
}

/**
 * Attach real-time validation to form fields
 * @param {HTMLFormElement} form - Form element
 */
function attachValidation(form) {
    const fields = form.querySelectorAll('[data-validate]');

    fields.forEach(field => {
        // Validate on blur
        field.addEventListener('blur', () => {
            validateField(field);
        });

        // Clear error on input (wait for next blur to revalidate)
        field.addEventListener('input', () => {
            if (field.classList.contains('is-invalid')) {
                clearFieldError(field);
            }
        });
    });

    // Validate form on submit
    form.addEventListener('submit', (e) => {
        if (!validateForm(form)) {
            e.preventDefault();

            // Focus first invalid field
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.focus();
            }

            showToast('Please fix validation errors before submitting', 'error');
        }
    });
}

// ============================================================================
// API Helpers
// ============================================================================

/**
 * Get CSRF token from meta tag
 * @returns {string|null}
 */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : null;
}

/**
 * Make an API request with error handling and CSRF protection
 * @param {string} url - API endpoint URL
 * @param {Object} options - Fetch options
 * @returns {Promise}
 */
async function apiRequest(url, options = {}) {
    try {
        // Get CSRF token for state-changing requests
        const csrfToken = getCsrfToken();
        const method = (options.method || 'GET').toUpperCase();
        const needsCsrf = ['POST', 'PUT', 'DELETE', 'PATCH'].includes(method);

        // Build headers
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };

        // Add CSRF token header for state-changing requests
        if (needsCsrf && csrfToken) {
            headers['X-CSRF-Token'] = csrfToken;
        }

        const response = await fetch(url, {
            ...options,
            credentials: 'include',
            headers
        });

        // Check for 401 Unauthorized - session expired
        if (response.status === 401) {
            console.warn('[SESSION] API returned 401 Unauthorized, redirecting to login');
            window.location.href = 'login.php?error=expired';
            throw new Error('Session expired');
        }

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        // Check for session-related error codes
        if (data.code === 'expired' || data.code === 'no_session') {
            console.warn('[SESSION] API returned session error, redirecting to login');
            window.location.href = `login.php?error=${data.code}`;
            throw new Error(data.error || 'Session expired');
        }

        if (data.error) {
            throw new Error(data.error);
        }

        return data;
    } catch (error) {
        console.error('API Request Error:', error);
        throw error;
    }
}

/**
 * Make a POST request to API
 * @param {string} url - API endpoint URL
 * @param {Object} data - Data to send
 * @returns {Promise}
 */
async function apiPost(url, data) {
    return apiRequest(url, {
        method: 'POST',
        body: data instanceof FormData ? data : JSON.stringify(data)
    });
}

/**
 * Make a GET request to API
 * @param {string} url - API endpoint URL
 * @param {Object} params - Query parameters
 * @returns {Promise}
 */
async function apiGet(url, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const fullUrl = queryString ? `${url}?${queryString}` : url;
    return apiRequest(fullUrl);
}

// ============================================================================
// String & Data Utilities
// ============================================================================

/**
 * Escape HTML to prevent XSS
 * @param {string} text - Text to escape
 * @returns {string}
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Format number with thousands separator
 * @param {number} num - Number to format
 * @returns {string}
 */
function formatNumber(num) {
    return Number(num).toLocaleString();
}

/**
 * Format bytes to human-readable size
 * @param {number} bytes - Bytes to format
 * @returns {string}
 */
function formatBytes(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    bytes = Math.max(bytes, 0);
    const pow = Math.floor((bytes ? Math.log(bytes) : 0) / Math.log(1024));
    const powMin = Math.min(pow, units.length - 1);
    bytes /= (1 << (10 * powMin));
    return Math.round(bytes * 100) / 100 + ' ' + units[powMin];
}

/**
 * Calculate time ago from timestamp
 * @param {string|Date} datetime - Date/time to calculate from
 * @returns {string}
 */
function timeAgo(datetime) {
    const timestamp = new Date(datetime).getTime();
    const diff = Date.now() - timestamp;
    const seconds = Math.floor(diff / 1000);

    if (seconds < 60) return seconds + ' seconds ago';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';

    return new Date(timestamp).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Debounce function calls
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in ms
 * @returns {Function}
 */
function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ============================================================================
// Table Utilities
// ============================================================================

/**
 * Sort table by column
 * @param {HTMLTableElement} table - Table element
 * @param {number} columnIndex - Column index to sort by
 * @param {string} direction - Sort direction ('asc' or 'desc')
 */
function sortTableByColumn(table, columnIndex, direction = 'asc') {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();

        // Try to parse as numbers
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return direction === 'asc' ? aNum - bNum : bNum - aNum;
        }

        // String comparison
        return direction === 'asc'
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
    });

    rows.forEach(row => tbody.appendChild(row));
}

/**
 * Filter table rows by search term
 * @param {HTMLTableElement} table - Table element
 * @param {string} searchTerm - Search term
 */
function filterTable(table, searchTerm) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    const term = searchTerm.toLowerCase();

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
}

// ============================================================================
// Copy to Clipboard
// ============================================================================

/**
 * Copy text to clipboard
 * @param {string} text - Text to copy
 * @returns {Promise<boolean>}
 */
async function copyToClipboard(text) {
    try {
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(text);
            return true;
        } else {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            return true;
        }
    } catch (error) {
        console.error('Failed to copy to clipboard:', error);
        return false;
    }
}

// ============================================================================
// Confirmation Dialogs
// ============================================================================

/**
 * Show a confirmation dialog (replaces window.confirm)
 * @param {string} message - Confirmation message
 * @param {string} title - Dialog title
 * @param {Object} options - Dialog options { confirmText, cancelText, dangerMode }
 * @returns {Promise<boolean>}
 */
async function confirmAction(message, title = 'Confirm Action', options = {}) {
    return new Promise((resolve) => {
        const defaults = {
            confirmText: 'Confirm',
            cancelText: 'Cancel',
            dangerMode: false
        };
        const opts = { ...defaults, ...options };

        // Create modal
        const confirmBtnClass = opts.dangerMode ? 'btn-danger' : 'btn-primary';
        const headerGradient = opts.dangerMode
            ? 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)'
            : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';

        const modalHtml = `
            <div id="confirmModal" class="modal" style="display: flex; z-index: 99999;">
                <div class="modal-content" style="max-width: 500px; animation: slideIn 0.3s ease;">
                    <div class="modal-header" style="background: ${headerGradient}; color: white; border-radius: 12px 12px 0 0;">
                        <h3 style="margin: 0;">${escapeHtml(title)}</h3>
                    </div>
                    <div class="modal-body" style="padding: 2rem;">
                        <p style="font-size: 1.1rem; line-height: 1.6; white-space: pre-line;">${escapeHtml(message)}</p>
                    </div>
                    <div class="modal-footer" style="padding: 1rem 2rem; display: flex; gap: 1rem; justify-content: flex-end; border-top: 1px solid #eee;">
                        <button type="button" class="btn btn-secondary" id="confirmNo">${escapeHtml(opts.cancelText)}</button>
                        <button type="button" class="btn ${confirmBtnClass}" id="confirmYes">${escapeHtml(opts.confirmText)}</button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        const modal = document.getElementById('confirmModal');
        const yesBtn = document.getElementById('confirmYes');
        const noBtn = document.getElementById('confirmNo');

        // Focus confirm button
        setTimeout(() => yesBtn.focus(), 100);

        const cleanup = (result) => {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
                resolve(result);
            }, 200);
        };

        yesBtn.addEventListener('click', () => cleanup(true));
        noBtn.addEventListener('click', () => cleanup(false));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) cleanup(false);
        });

        // ESC key to cancel
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                document.removeEventListener('keydown', escHandler);
                cleanup(false);
            }
        };
        document.addEventListener('keydown', escHandler);
    });
}

/**
 * Show a modal dialog (convenience wrapper for confirmAction/alertModal)
 * @param {Object} options - Modal options
 * @param {string} options.title - Modal title
 * @param {string} options.message - Modal message
 * @param {string} options.confirmText - Confirm button text (default: 'OK')
 * @param {string} options.cancelText - Cancel button text (optional, creates confirm dialog)
 * @param {string} options.confirmClass - CSS class for confirm button (e.g., 'btn-danger')
 * @param {Function} options.onConfirm - Callback when confirmed
 * @param {Function} options.onCancel - Callback when cancelled
 * @returns {Promise<boolean>}
 */
async function showModal(options = {}) {
    const defaults = {
        title: 'Notice',
        message: '',
        confirmText: 'OK',
        cancelText: null,
        confirmClass: 'btn-primary',
        onConfirm: null,
        onCancel: null
    };
    const opts = { ...defaults, ...options };

    // If no cancel button, show alert
    if (!opts.cancelText) {
        await alertModal(opts.message, opts.title, 'info');
        if (opts.onConfirm) await opts.onConfirm();
        return true;
    }

    // Show confirmation dialog
    const dangerMode = opts.confirmClass === 'btn-danger';
    const confirmed = await confirmAction(opts.message, opts.title, {
        confirmText: opts.confirmText,
        cancelText: opts.cancelText,
        dangerMode
    });

    if (confirmed && opts.onConfirm) {
        await opts.onConfirm();
    } else if (!confirmed && opts.onCancel) {
        await opts.onCancel();
    }

    return confirmed;
}

/**
 * Simplified alert replacement with modal
 * @param {string} message - Alert message
 * @param {string} title - Alert title
 * @param {string} type - Alert type: 'info', 'success', 'warning', 'error'
 * @returns {Promise<void>}
 */
async function alertModal(message, title = 'Notice', type = 'info') {
    return new Promise((resolve) => {
        const gradients = {
            info: 'linear-gradient(135deg, #3498db 0%, #2980b9 100%)',
            success: 'linear-gradient(135deg, #27ae60 0%, #229954 100%)',
            warning: 'linear-gradient(135deg, #f39c12 0%, #e67e22 100%)',
            error: 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)'
        };

        const icons = {
            info: 'ℹ️',
            success: '✅',
            warning: '⚠️',
            error: '❌'
        };

        const headerGradient = gradients[type] || gradients.info;
        const icon = icons[type] || icons.info;

        const modalHtml = `
            <div id="alertModal" class="modal" style="display: flex; z-index: 99999;">
                <div class="modal-content" style="max-width: 500px; animation: slideIn 0.3s ease;">
                    <div class="modal-header" style="background: ${headerGradient}; color: white; border-radius: 12px 12px 0 0;">
                        <h3 style="margin: 0;">${icon} ${escapeHtml(title)}</h3>
                    </div>
                    <div class="modal-body" style="padding: 2rem;">
                        <p style="font-size: 1.1rem; line-height: 1.6; white-space: pre-line;">${escapeHtml(message)}</p>
                    </div>
                    <div class="modal-footer" style="padding: 1rem 2rem; display: flex; justify-content: flex-end; border-top: 1px solid #eee;">
                        <button type="button" class="btn btn-primary" id="alertOk">OK</button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        const modal = document.getElementById('alertModal');
        const okBtn = document.getElementById('alertOk');

        setTimeout(() => okBtn.focus(), 100);

        const cleanup = () => {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
                resolve();
            }, 200);
        };

        okBtn.addEventListener('click', cleanup);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) cleanup();
        });

        // ESC key or Enter to dismiss
        const keyHandler = (e) => {
            if (e.key === 'Escape' || e.key === 'Enter') {
                document.removeEventListener('keydown', keyHandler);
                cleanup();
            }
        };
        document.addEventListener('keydown', keyHandler);
    });
}

// ============================================================================
// Initialize on DOM Ready
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    initializeModals();
});
