/**
 * Admin Panel Shared JavaScript Utilities
 *
 * Common JavaScript functions used across admin pages
 *
 * @version 1.2.0
 * @date 2025-10-16
 * @changelog
 *   1.2.0 (2025-10-16) - Enhanced confirmAction() and added alertModal() to replace alert() and confirm()
 *                       - Added danger mode, custom button text, animations
 *                       - Added keyboard support (ESC, Enter)
 *   1.1.0 (2025-10-16) - Added closeModalOnBackdrop() for help modal support
 *   1.0.0 (2025-10-16) - Initial consolidated utilities
 */

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

// Add toast and modal animations to page
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
// API Helpers
// ============================================================================

/**
 * Make an API request with error handling
 * @param {string} url - API endpoint URL
 * @param {Object} options - Fetch options
 * @returns {Promise}
 */
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

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
