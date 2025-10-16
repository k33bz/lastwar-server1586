<?php
/**
 * Generate Test Token
 *
 * Admin-only page to generate long-lived JWT tokens for API testing
 * Tokens are valid for 30 days and can be used for automated testing
 *
 * @version 1.0.0
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
        $test_email = trim($_POST['test_email'] ?? '');
        $test_role = $_POST['test_role'] ?? 'r4';
        $test_alliances = isset($_POST['test_alliances']) ? explode(',', $_POST['test_alliances']) : ['*'];
        $test_powereditor = isset($_POST['test_powereditor']);
        $expiry_days = (int)($_POST['expiry_days'] ?? 30);

        // Validate inputs
        if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Valid email address required');
        }

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

        // Generate test token
        $jti = bin2hex(random_bytes(16));
        $expiry_seconds = $expiry_days * 24 * 60 * 60;

        $payload = [
            'sub' => $test_email,
            'aud' => $test_role,
            'alliances' => $test_alliances,
            'powereditor' => $test_powereditor,
            'jti' => $jti,
            'test_token' => true  // Mark as test token
        ];

        $generated_token = encode_jwt($payload, $expiry_seconds);

        $token_info = [
            'email' => $test_email,
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
            'test_email' => $test_email,
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

// Include shared header
include 'includes/header.php';
?>

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

        <div class="token-info">
            <h3>Token Information</h3>
            <table class="info-table">
                <tr>
                    <td><strong>Email:</strong></td>
                    <td><?php echo htmlspecialchars($token_info['email']); ?></td>
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
                <h4>Using with curl:</h4>
                <pre><code>curl -H "Cookie: jwt=YOUR_TOKEN" https://yourdomain.com/admin/dashboard.php</code></pre>

                <h4>Using with JavaScript fetch:</h4>
                <pre><code>fetch('https://yourdomain.com/admin/dashboard.php', {
    credentials: 'include',
    headers: {
        'Cookie': 'jwt=YOUR_TOKEN'
    }
});</code></pre>

                <h4>Using with Python requests:</h4>
                <pre><code>import requests
cookies = {'jwt': 'YOUR_TOKEN'}
response = requests.get('https://yourdomain.com/admin/dashboard.php', cookies=cookies)</code></pre>
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
                <label for="test_email">Test User Email</label>
                <input type="email"
                       id="test_email"
                       name="test_email"
                       class="form-control"
                       placeholder="test@example.com"
                       required>
                <small class="form-help">Email address for the test token (doesn't need to exist in users.json)</small>
            </div>

            <div class="form-group">
                <label for="test_role">Role</label>
                <select id="test_role" name="test_role" class="form-control" required>
                    <option value="r4">R4 - Basic Access</option>
                    <option value="r5">R5 - Alliance Leader</option>
                    <option value="admin">Admin - Full Access</option>
                </select>
                <small class="form-help">Access level for the test token</small>
            </div>

            <div class="form-group">
                <label for="test_alliances">Alliances (comma-separated)</label>
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
                </label>
                <small class="form-help">Allow editing alliance power values</small>
            </div>

            <div class="form-group">
                <label for="expiry_days">Token Expiry (days)</label>
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
            <h3>What is a Test Token?</h3>
            <p>Test tokens are long-lived JWT tokens that can be used for:</p>
            <ul>
                <li><strong>Automated Testing:</strong> Run integration tests against the admin API</li>
                <li><strong>Development:</strong> Test API endpoints without logging in manually</li>
                <li><strong>CI/CD:</strong> Authenticate automated deployment scripts</li>
                <li><strong>Debugging:</strong> Test specific user roles and permissions</li>
            </ul>
            <p><strong>Note:</strong> Test tokens are logged in the audit log and can be revoked anytime.</p>
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
}
</style>

<script>
function copyToken() {
    const tokenElement = document.getElementById('generatedToken');
    const token = tokenElement.textContent;

    navigator.clipboard.writeText(token).then(() => {
        alert('Token copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy token:', err);
        // Fallback: select text
        const range = document.createRange();
        range.selectNode(tokenElement);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
        alert('Token selected. Press Ctrl+C (or Cmd+C) to copy.');
    });
}

function downloadToken() {
    const token = document.getElementById('generatedToken').textContent;
    const tokenInfo = <?php echo json_encode($token_info ?? []); ?>;

    const content = `# Test JWT Token
# Generated: ${tokenInfo.issued_at || 'N/A'}
# Expires: ${tokenInfo.expires_at || 'N/A'}
# Email: ${tokenInfo.email || 'N/A'}
# Role: ${tokenInfo.role || 'N/A'}
# Alliances: ${(tokenInfo.alliances || []).join(', ')}

${token}
`;

    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `test-token-${tokenInfo.role || 'user'}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php include 'includes/footer.php'; ?>
