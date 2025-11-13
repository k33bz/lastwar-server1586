<?php
/**
 * Generate Test Token
 *
 * Admin-only page to generate long-lived JWT tokens for API testing
 * Tokens are valid for configurable days and can be used for automated testing
 *
 * Changelog:
 * - v1.4.1 (2025-10-16): Fixed download filename to use full identifier instead of just role
 *                        (e.g., test-r4-ape-1760671927.txt instead of test-token-r4-{timestamp}.txt)
 * - v1.4.0 (2025-10-16): Fixed identifier to include APE suffix (test-{role}[-ape]-{timestamp})
 *                        for better distinction between R4/R4+APE and R5/R5+APE tokens
 * - v1.3.0 (2025-10-16): Added breadcrumb navigation, contextual help tooltips,
 *                        and help modal for detailed token information
 * - v1.2.0 (2025-10-16): Replaced alert with toast notification for copy feedback
 * - v1.1.0 (2025-10-16): Simplified token generation without email requirement,
 *                        added localhost testing instructions
 * - v1.0.0 (2025-10-16): Initial release
 *
 * @version 1.4.1
 * @date 2025-10-16
 */

// Require admin authentication
require_once 'jwt.php';
require_once 'json_helpers.php';
require_once 'audit_logger.php';

$user = require_admin_session();

// Set page title for header
$page_title = "Generate Test Token";

// Handle token generation
$generated_token = null;
$token_info = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    try {
        $test_role = $_POST['test_role'] ?? 'r4';
        $test_alliances = isset($_POST['test_alliances']) ? explode(',', $_POST['test_alliances']) : ['*'];
        $test_powereditor = isset($_POST['test_powereditor']);
        $expiry_days = (int)($_POST['expiry_days'] ?? 30);

        // Validate inputs
        if (!in_array($test_role, ['admin', 'r5', 'r4'])) {
            throw new Exception('Invalid role selected');
        }

        if ($expiry_days < 1 || $expiry_days > 365) {
            throw new Exception('Expiry must be between 1 and 365 days');
        }

        // Clean alliance tags
        $test_alliances = array_map('trim', $test_alliances);
        $test_alliances = array_filter($test_alliances);

        if (empty($test_alliances)) {
            $test_alliances = ['*'];
        }

        // Generate test token with simple identifier
        $jti = bin2hex(random_bytes(16));
        $expiry_seconds = $expiry_days * 24 * 60 * 60;

        // Create identifier: test-{role}[-ape]-{timestamp}
        $role_suffix = $test_powereditor ? '-ape' : '';
        $test_identifier = 'test-' . $test_role . $role_suffix . '-' . time();

        $payload = [
            'sub' => $test_identifier,
            'aud' => $test_role,
            'alliances' => $test_alliances,
            'powereditor' => $test_powereditor,
            'jti' => $jti,
            'test_token' => true  // Mark as test token
        ];

        $generated_token = encode_jwt($payload, $expiry_seconds);

        $token_info = [
            'identifier' => $test_identifier,
            'role' => $test_role,
            'alliances' => $test_alliances,
            'powereditor' => $test_powereditor,
            'jti' => $jti,
            'issued_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', time() + $expiry_seconds),
            'expiry_days' => $expiry_days
        ];

        // Log token generation
        log_audit_event('generate_test_token', $user->sub, [
            'test_identifier' => $test_identifier,
            'role' => $test_role,
            'alliances' => $test_alliances,
            'powereditor' => $test_powereditor,
            'expiry_days' => $expiry_days,
            'jti' => $jti
        ]);

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Include breadcrumbs helper (needed before header for help functions)
require_once 'includes/breadcrumbs.php';

// Include shared header
include 'includes/header.php';
?>

<?php echo render_breadcrumbs(); ?>

<div class="test-token-container">
    <div class="page-header">
        <h1 class="page-title">
            <span class="title-icon">🔑</span>
            Generate Test Token
        </h1>
        <p class="page-subtitle">Create long-lived JWT tokens for API testing and automation</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <?php if ($generated_token): ?>
    <!-- Token Generated Successfully -->
    <div class="success-section">
        <div class="alert alert-success">
            <strong>Success!</strong> Test token generated successfully.
        </div>

        <div class="token-display">
            <h3>Generated Token</h3>
            <div class="token-box">
                <code id="generatedToken"><?php echo htmlspecialchars($generated_token); ?></code>
            </div>
            <div class="token-actions">
                <button onclick="copyToken()" class="btn btn-primary">
                    <span class="btn-icon">📋</span>
                    Copy Token
                </button>
                <button onclick="downloadToken()" class="btn btn-secondary">
                    <span class="btn-icon">💾</span>
                    Download as File
                </button>
            </div>
        </div>

        <div class="localhost-notice">
            <h3>🔧 Testing on Localhost</h3>
            <p><strong>IMPORTANT:</strong> Localhost and production use different JWT secret keys!</p>
            <ul>
                <li>This token was generated on <strong><?php echo $_SERVER['HTTP_HOST']; ?></strong></li>
                <li>It will ONLY work on this environment</li>
                <li>Generate a separate token on localhost to test locally</li>
                <li>Generate a separate token on production to test in production</li>
            </ul>
            <div class="test-url-box">
                <strong>Test this token at:</strong>
                <div class="url-display">
                    <code><?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/test_token_auth.php'; ?></code>
                </div>
            </div>
        </div>

        <div class="token-info">
            <h3>Token Information</h3>
            <table class="info-table">
                <tr>
                    <td><strong>Identifier:</strong></td>
                    <td><code><?php echo htmlspecialchars($token_info['identifier']); ?></code></td>
                </tr>
                <tr>
                    <td><strong>Role:</strong></td>
                    <td><span class="role-badge role-<?php echo $token_info['role']; ?>"><?php echo strtoupper($token_info['role']); ?></span></td>
                </tr>
                <tr>
                    <td><strong>Alliances:</strong></td>
                    <td><?php echo htmlspecialchars(implode(', ', $token_info['alliances'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Power Editor:</strong></td>
                    <td><?php echo $token_info['powereditor'] ? '✓ Yes' : '✗ No'; ?></td>
                </tr>
                <tr>
                    <td><strong>JWT ID (jti):</strong></td>
                    <td><code><?php echo htmlspecialchars($token_info['jti']); ?></code></td>
                </tr>
                <tr>
                    <td><strong>Issued At:</strong></td>
                    <td><?php echo htmlspecialchars($token_info['issued_at']); ?></td>
                </tr>
                <tr>
                    <td><strong>Expires At:</strong></td>
                    <td><?php echo htmlspecialchars($token_info['expires_at']); ?></td>
                </tr>
                <tr>
                    <td><strong>Valid For:</strong></td>
                    <td><?php echo $token_info['expiry_days']; ?> days</td>
                </tr>
            </table>
        </div>

        <div class="usage-instructions">
            <h3>Usage Instructions</h3>
            <div class="instruction-box">
                <h4>Test Token Authentication (curl):</h4>
                <pre><code>curl -b "jwt=YOUR_TOKEN" <?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/test_token_auth.php'; ?></code></pre>

                <h4>Access Dashboard (curl):</h4>
                <pre><code>curl -b "jwt=YOUR_TOKEN" <?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/dashboard.php'; ?></code></pre>

                <h4>Using with JavaScript fetch:</h4>
                <pre><code>fetch('<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/dashboard.php'; ?>', {
    credentials: 'include',
    headers: {
        'Cookie': 'jwt=YOUR_TOKEN'
    }
});</code></pre>

                <h4>Using with Python requests:</h4>
                <pre><code>import requests
cookies = {'jwt': 'YOUR_TOKEN'}
response = requests.get('<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/dashboard.php'; ?>', cookies=cookies)
print(response.status_code, response.headers.get('content-type'))</code></pre>
            </div>
        </div>

        <div class="warning-box">
            <strong>⚠️ Security Warning:</strong>
            <ul>
                <li>Keep this token secure - it provides full access to the admin panel</li>
                <li>Token is valid for <?php echo $token_info['expiry_days']; ?> days</li>
                <li>You can revoke this token anytime from the Security Monitor</li>
                <li>Do not commit this token to version control</li>
                <li>Do not share this token publicly</li>
            </ul>
        </div>

        <div class="action-buttons">
            <a href="generate_test_token.php" class="btn btn-primary">Generate Another Token</a>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    <?php else: ?>
    <!-- Token Generation Form -->
    <div class="form-section">
        <form method="POST" class="token-form">
            <input type="hidden" name="action" value="generate">

            <div class="form-group">
                <label for="test_role">
                    Role / Access Level
                    <?php echo help_tooltip('Determines the permission level of the test token. R4 has basic access, R5 can sign rules, Admin has full control.', 'right'); ?>
                </label>
                <select id="test_role" name="test_role" class="form-control" required>
                    <option value="r4">R4 - Basic Access</option>
                    <option value="r5">R5 - Alliance Leader</option>
                    <option value="admin">Admin - Full Access</option>
                </select>
                <small class="form-help">Access level for the test token (identifier format: test-{role}[-ape]-{timestamp})</small>
            </div>

            <div class="form-group">
                <label for="test_alliances">
                    Alliances (comma-separated)
                    <?php echo help_tooltip('Use * for access to all alliances, or specify specific alliance tags separated by commas (e.g., UvvU, 1984, K44)', 'right'); ?>
                </label>
                <input type="text"
                       id="test_alliances"
                       name="test_alliances"
                       class="form-control"
                       placeholder="*"
                       value="*">
                <small class="form-help">Use * for all alliances, or specify tags: UvvU, 1984, K44</small>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="test_powereditor" id="test_powereditor">
                    <span>Power Editor Access</span>
                    <?php echo help_tooltip('Grants permission to edit alliance power values directly. Only admins and specially authorized users should have this permission.', 'right'); ?>
                </label>
                <small class="form-help">Allow editing alliance power values</small>
            </div>

            <div class="form-group">
                <label for="expiry_days">
                    Token Expiry (days)
                    <?php echo help_tooltip('How long the token remains valid. Recommended: 30 days for testing, 7 days for CI/CD, 365 days for long-term automation.', 'right'); ?>
                </label>
                <input type="number"
                       id="expiry_days"
                       name="expiry_days"
                       class="form-control"
                       value="30"
                       min="1"
                       max="365"
                       required>
                <small class="form-help">Number of days before token expires (1-365)</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    <span class="btn-icon">🔑</span>
                    Generate Test Token
                </button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

        <div class="info-box">
            <h3>
                What is a Test Token?
                <?php echo help_modal(
                    'Understanding Test Tokens',
                    '<p>Test tokens are specialized JWT (JSON Web Token) authentication tokens designed for:</p>
                    <h4>Primary Use Cases:</h4>
                    <ul>
                        <li><strong>Automated Testing:</strong> Run integration tests against the admin API without manual login</li>
                        <li><strong>Development:</strong> Test API endpoints efficiently during development</li>
                        <li><strong>CI/CD Pipelines:</strong> Authenticate automated deployment and update scripts</li>
                        <li><strong>Debugging:</strong> Test specific user roles and permission scenarios</li>
                        <li><strong>API Integration:</strong> Integrate third-party tools with the admin system</li>
                    </ul>
                    <h4>Security Features:</h4>
                    <ul>
                        <li>All test token generation is logged in the audit log</li>
                        <li>Tokens can be revoked instantly from Security Monitor</li>
                        <li>Configurable expiry (1-365 days) for security control</li>
                        <li>Role-based permissions (R4, R5, Admin)</li>
                        <li>Alliance-specific access control</li>
                    </ul>
                    <h4>Important Notes:</h4>
                    <ul>
                        <li><strong>Environment-Specific:</strong> Localhost and production use different secret keys - tokens are NOT interchangeable</li>
                        <li><strong>Identifier Format:</strong> Auto-generated as <code>test-{role}[-ape]-{timestamp}</code> (APE suffix added if Power Editor enabled)</li>
                        <li><strong>Storage:</strong> Keep tokens secure - they provide full access based on assigned role</li>
                        <li><strong>Best Practice:</strong> Use shortest expiry period needed for your use case</li>
                    </ul>',
                    'test-token-help'
                ); ?>
            </h3>
            <p>Test tokens are long-lived JWT tokens that can be used for:</p>
            <ul>
                <li><strong>Automated Testing:</strong> Run integration tests against the admin API</li>
                <li><strong>Development:</strong> Test API endpoints without logging in manually</li>
                <li><strong>CI/CD:</strong> Authenticate automated deployment scripts</li>
                <li><strong>Debugging:</strong> Test specific user roles and permissions</li>
            </ul>
            <p><strong>Note:</strong> Test tokens are logged in the audit log and can be revoked anytime. <em>Click the ℹ️ icon for detailed information.</em></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.test-token-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 2rem;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.title-icon {
    font-size: 3rem;
}

.page-subtitle {
    font-size: 1.1rem;
    color: #6c757d;
}

/* Alerts */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Form Section */
.form-section {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.token-form {
    max-width: 600px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-help {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #6c757d;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

/* Token Display */
.success-section {
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.token-display {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.token-display h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.token-box {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    margin-bottom: 1rem;
    overflow-x: auto;
}

.token-box code {
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    color: #495057;
    word-break: break-all;
}

.token-actions {
    display: flex;
    gap: 1rem;
}

/* Localhost Notice */
.localhost-notice {
    background: #fff3cd;
    border: 2px solid #ffc107;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.localhost-notice h3 {
    color: #856404;
    margin-bottom: 0.75rem;
    margin-top: 0;
}

.localhost-notice p {
    color: #856404;
    margin-bottom: 0.75rem;
}

.localhost-notice ul {
    color: #856404;
    margin: 0.5rem 0 1rem 1.5rem;
    padding: 0;
}

.localhost-notice li {
    margin-bottom: 0.5rem;
}

.test-url-box {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.test-url-box strong {
    display: block;
    color: #856404;
    margin-bottom: 0.5rem;
}

.url-display {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}

.url-display code {
    color: #495057;
    font-size: 0.9rem;
    word-break: break-all;
}

/* Token Info Table */
.token-info {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.token-info h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table tr {
    border-bottom: 1px solid #dee2e6;
}

.info-table td {
    padding: 0.75rem 0;
}

.info-table td:first-child {
    width: 180px;
    color: #6c757d;
}

.info-table code {
    background: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
}

.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.role-badge.role-admin {
    background: #fee;
    color: #c33;
}

.role-badge.role-r5 {
    background: #fff3cd;
    color: #856404;
}

.role-badge.role-r4 {
    background: #e2e3e5;
    color: #6c757d;
}

/* Usage Instructions */
.usage-instructions {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.usage-instructions h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.instruction-box h4 {
    color: #495057;
    font-size: 0.9rem;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}

.instruction-box pre {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    overflow-x: auto;
    margin-bottom: 1rem;
}

.instruction-box code {
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    color: #495057;
}

/* Warning Box */
.warning-box {
    background: #fff3cd;
    border: 1px solid #ffc107;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.warning-box strong {
    color: #856404;
    display: block;
    margin-bottom: 0.75rem;
}

.warning-box ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #856404;
}

.warning-box li {
    margin-bottom: 0.5rem;
}

/* Info Box */
.info-box {
    background: #e7f3ff;
    border: 1px solid #3498db;
    padding: 1.5rem;
    border-radius: 12px;
    margin-top: 2rem;
}

.info-box h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.info-box ul {
    margin: 0;
    padding-left: 1.5rem;
}

.info-box li {
    margin-bottom: 0.5rem;
}

/* Buttons */
.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.btn-icon {
    font-size: 1.25rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

/* Copy Toast Notification */
.copy-toast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(40, 167, 69, 0.4);
    font-weight: 600;
    font-size: 1rem;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
    z-index: 10000;
    pointer-events: none;
}

.copy-toast.show {
    opacity: 1;
    transform: translateY(0);
}

/* Responsive Design */
@media (max-width: 768px) {
    .test-token-container {
        padding: 1rem;
    }

    .page-title {
        font-size: 2rem;
        flex-direction: column;
        gap: 0.5rem;
    }

    .form-actions,
    .token-actions,
    .action-buttons {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }

    .info-table td:first-child {
        width: auto;
    }

    .copy-toast {
        bottom: 1rem;
        right: 1rem;
        left: 1rem;
        text-align: center;
    }
}
</style>

<script>
function copyToken() {
    const tokenElement = document.getElementById('generatedToken');
    const token = tokenElement.textContent;
    const button = event.target.closest('button');

    navigator.clipboard.writeText(token).then(() => {
        showCopySuccess(button);
    }).catch(err => {
        console.error('Failed to copy token:', err);
        // Fallback: select text
        const range = document.createRange();
        range.selectNode(tokenElement);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        showCopySuccess(button, 'Token selected! Press Ctrl+C to copy.');
    });
}

function showCopySuccess(button, message = 'Token copied to clipboard!') {
    // Store original button content
    const originalContent = button.innerHTML;

    // Update button to show success
    button.innerHTML = '<span class="btn-icon">✓</span>' + message.split('!')[0];
    button.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';

    // Create and show toast notification
    const toast = document.createElement('div');
    toast.className = 'copy-toast';
    toast.textContent = message;
    document.body.appendChild(toast);

    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);

    // Reset button and remove toast after delay
    setTimeout(() => {
        button.innerHTML = originalContent;
        button.style.background = '';
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

function downloadToken() {
    const token = document.getElementById('generatedToken').textContent;
    const tokenInfo = <?php echo json_encode($token_info ?? []); ?>;

    const content = `# Test JWT Token
# Generated: ${tokenInfo.issued_at || 'N/A'}
# Expires: ${tokenInfo.expires_at || 'N/A'}
# Identifier: ${tokenInfo.identifier || 'N/A'}
# Role: ${tokenInfo.role || 'N/A'}
# Alliances: ${(tokenInfo.alliances || []).join(', ')}
# Power Editor: ${tokenInfo.powereditor ? 'Yes' : 'No'}
# JWT ID: ${tokenInfo.jti || 'N/A'}

${token}
`;

    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${tokenInfo.identifier || 'test-token'}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php include 'includes/footer.php'; ?>
