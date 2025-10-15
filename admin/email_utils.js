/**
 * Email Utility Functions
 *
 * Shared utilities for email masking and PII protection across admin pages
 *
 * @version 1.0.0
 * @date 2025-10-15
 */

/**
 * Mask an email address for PII protection
 * Shows first and last character of local part, rest as asterisks
 *
 * @param {string} email - Full email address
 * @returns {string} Masked email address
 */
function maskEmail(email) {
    const parts = email.split('@');
    if (parts.length !== 2) return email;

    const localPart = parts[0];
    const domain = parts[1];

    if (localPart.length <= 2) {
        // For very short local parts, just show asterisks
        return '***@' + domain;
    }

    const masked = localPart.substring(0, 1) +
                   '*'.repeat(Math.max(6, localPart.length - 2)) +
                   localPart.substring(localPart.length - 1) +
                   '@' + domain;

    return masked;
}

/**
 * Toggle email visibility in header (PII protection)
 *
 * @param {HTMLElement} emailSpan - The span element containing the email
 */
function toggleHeaderEmail(emailSpan) {
    const fullEmail = emailSpan.getAttribute('data-email');
    const isHidden = emailSpan.classList.contains('email-hidden');

    if (isHidden) {
        // Show full email
        emailSpan.textContent = fullEmail;
        emailSpan.classList.remove('email-hidden');
        emailSpan.classList.add('email-visible');
        emailSpan.title = 'Click to hide email';
    } else {
        // Hide email (mask it)
        emailSpan.textContent = maskEmail(fullEmail);
        emailSpan.classList.remove('email-visible');
        emailSpan.classList.add('email-hidden');
        emailSpan.title = 'Click to show email';
    }
}

/**
 * Toggle email visibility in table (PII protection)
 * Used in tables where emails have an eye icon to reveal
 *
 * @param {HTMLElement} eyeIcon - The eye icon element
 */
function toggleEmail(eyeIcon) {
    const emailSpan = eyeIcon.previousElementSibling;
    const fullEmail = emailSpan.getAttribute('data-email');
    const isHidden = emailSpan.classList.contains('email-hidden');

    if (isHidden) {
        // Show full email
        emailSpan.textContent = fullEmail;
        emailSpan.classList.remove('email-hidden');
        emailSpan.classList.add('email-visible');
        eyeIcon.title = 'Hide Email';
    } else {
        // Hide email (mask it)
        emailSpan.textContent = maskEmail(fullEmail);
        emailSpan.classList.remove('email-visible');
        emailSpan.classList.add('email-hidden');
        eyeIcon.title = 'Show Email';
    }
}
