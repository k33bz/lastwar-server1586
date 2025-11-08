<?php
// Ensure we have user data - get it if not already loaded
if (!isset($user)) {
    try {
        require_once __DIR__ . '/../jwt.php';
        $user = require_jwt_session();
    } catch (Exception $e) {
        // If we can't get user data, create a minimal user object
        $user = (object)['aud' => 'guest'];
    }
}

// Load version data from centralized version.json
$version_file = __DIR__ . '/../../version.json';
$version_data = null;
if (file_exists($version_file)) {
    $version_data = json_decode(file_get_contents($version_file), true);
}
$current_version = $version_data['version'] ?? '3.0.0';
$release_date = $version_data['releaseDate'] ?? '2025-10-16';
$last_updated = $version_data['lastUpdated'] ?? date('Y-m-d');
?>
    </main>
    
    <footer class="admin-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>System Status</h4>
                    <div class="status-indicators">
                        <div class="status-item">
                            <span class="status-dot status-online"></span>
                            <span>Authentication System</span>
                        </div>
                        <div class="status-item">
                            <span class="status-dot status-online"></span>
                            <span>Key Rotation</span>
                        </div>
                        <div class="status-item">
                            <span class="status-dot status-online"></span>
                            <span>Security Monitor</span>
                        </div>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Actions</h4>
                    <div class="quick-actions">
                        <?php if (isset($user) && $user->aud !== 'guest'): ?>
                        <a href="dashboard.php" class="quick-action">
                            🏠 Dashboard
                        </a>
                        <?php if ($user->aud === 'admin' || (function_exists('is_power_editor') && is_power_editor($user))): ?>
                        <a href="alliances_power.php" class="quick-action">
                            ⚡ Alliance Power
                        </a>
                        <?php endif; ?>
                        <?php if ($user->aud === 'admin'): ?>
                        <a href="user_management.php" class="quick-action">
                            👥 User Management
                        </a>
                        <a href="security_audit.php" class="quick-action">
                            📋 Audit Logs
                        </a>
                        <a href="security_backups.php" class="quick-action">
                            💾 Backups
                        </a>
                        <a href="security_monitor.php" class="quick-action">
                            🛡️ Security Monitor
                        </a>
                        <?php elseif ($user->aud === 'r5'): ?>
                        <a href="alliance_edit.php" class="quick-action">
                            ✏️ Edit Alliance
                        </a>
                        <?php elseif ($user->aud === 'r4'): ?>
                        <a href="alliance_edit.php" class="quick-action">
                            📝 Alliance Info
                        </a>
                        <?php endif; ?>
                        <?php else: ?>
                        <a href="login.php" class="quick-action">
                            🔐 Login
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>System Info</h4>
                    <div class="system-info">
                        <div class="info-item">
                            <strong>Version:</strong> v<?php echo htmlspecialchars($current_version); ?>
                        </div>
                        <div class="info-item">
                            <strong>Released:</strong> <?php echo date('M j, Y', strtotime($release_date)); ?>
                        </div>
                        <div class="info-item">
                            <strong>Security Level:</strong> <span class="security-high">Enterprise</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo $_ENV['APP_NAME'] ?? 'Admin Panel'; ?>. All rights reserved.</p>
                </div>
                <div class="footer-links">
                    <a href="changelog.php">📋 Changelog</a>
                    <a href="https://github.com/k33bz/lastwar-server1586" target="_blank">GitHub Repository</a>
                    <a href="https://github.com/k33bz/lastwar-server1586/issues" target="_blank">Report Issue</a>
                    <a href="#" onclick="showSecurityInfo()">Security Info</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Security Info Modal -->
    <div id="securityModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🛡️ Security Information</h3>
                <span class="close" onclick="closeSecurityInfo()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="security-metrics">
                    <div class="metric">
                        <strong>JWT Key Rotation:</strong> Active (30-day cycle)
                    </div>
                    <div class="metric">
                        <strong>Multi-Factor Auth:</strong> Enabled
                    </div>
                    <div class="metric">
                        <strong>Session Security:</strong> Enhanced
                    </div>
                    <div class="metric">
                        <strong>Audit Logging:</strong> Comprehensive
                    </div>
                    <div class="metric">
                        <strong>Rate Limiting:</strong> Active
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Expiration Warning Modal -->
    <div id="sessionWarningModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white;">
                <h3>⏰ Session Expiring Soon</h3>
            </div>
            <div class="modal-body">
                <p style="font-size: 1.1rem; margin-bottom: 1rem;">Your session will expire in <strong>5 minutes</strong>.</p>
                <p style="color: #6c757d; margin-bottom: 1.5rem;">Would you like to continue working? Your session will be automatically refreshed.</p>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button onclick="handleSessionExpiry(false)" class="btn btn-secondary">Log Out</button>
                    <button onclick="handleSessionExpiry(true)" class="btn btn-primary">Continue Working</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Refresh Error Modal -->
    <div id="sessionErrorModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white;">
                <h3>❌ Session Refresh Failed</h3>
            </div>
            <div class="modal-body">
                <p style="font-size: 1.1rem; margin-bottom: 1rem;">Unable to refresh your session automatically.</p>
                <p style="color: #6c757d; margin-bottom: 1.5rem;">Please save your work and log in again to continue.</p>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button onclick="closeModal('sessionErrorModal')" class="btn btn-secondary">Dismiss</button>
                    <button onclick="window.location.href='login.php'" class="btn btn-primary">Go to Login</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .admin-footer {
            background: #2c3e50;
            color: white;
            margin-top: 4rem;
            padding: 2rem 0 1rem;
        }
        
        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h4 {
            color: #ecf0f1;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .status-indicators {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .status-online {
            background: #27ae60;
            box-shadow: 0 0 6px rgba(39, 174, 96, 0.6);
        }
        
        .status-warning {
            background: #f39c12;
            box-shadow: 0 0 6px rgba(243, 156, 18, 0.6);
        }
        
        .status-offline {
            background: #e74c3c;
            box-shadow: 0 0 6px rgba(231, 76, 60, 0.6);
        }
        
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .quick-action {
            color: #bdc3c7;
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 4px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .quick-action:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .system-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .info-item {
            font-size: 0.9rem;
            color: #bdc3c7;
        }
        
        .security-high {
            color: #27ae60;
            font-weight: bold;
        }
        
        .footer-bottom {
            border-top: 1px solid #34495e;
            padding-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .copyright {
            color: #95a5a6;
            font-size: 0.9rem;
        }
        
        .footer-links {
            display: flex;
            gap: 1rem;
        }
        
        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        /* Modal Styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        
        .close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .security-metrics {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .metric {
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #27ae60;
        }

        /* Modal Button Styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
            }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>

    <script>
        function showSecurityInfo() {
            document.getElementById('securityModal').style.display = 'flex';
        }
        
        function closeSecurityInfo() {
            document.getElementById('securityModal').style.display = 'none';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            // Check all modals
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                // Only close if clicked directly on modal backdrop, not on content
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Close modals with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                // Find and close any visible modals
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (modal.style.display === 'flex' || modal.style.display === 'block') {
                        // Don't close session warning modal with ESC (important security notice)
                        if (modal.id !== 'sessionWarningModal') {
                            modal.style.display = 'none';
                        }
                    }
                });
            }
        });
        
        // Auto-refresh status indicators every 60 seconds
        setInterval(function() {
            // Check if we're on an admin page before making status calls
            if (window.location.pathname.includes('/admin/')) {
                fetch('security_monitor.php?action=status', {
                    method: 'GET',
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'ok') {
                        // Update status indicators if needed
                        document.querySelectorAll('.status-dot').forEach(dot => {
                            dot.className = 'status-dot status-online';
                        });
                    }
                })
                .catch(error => {
                    console.log('Status check unavailable');
                });
            }
        }, 60000);
        
        // Session management for authenticated users
        <?php if (isset($user) && $user->aud !== 'guest'): ?>
        let sessionTimeout = 25 * 60 * 1000; // 25 minutes (5 min before 30 min expiry)
        let warningShown = false;

        function handleSessionExpiry(continueWorking) {
            document.getElementById('sessionWarningModal').style.display = 'none';

            if (continueWorking) {
                fetch('refresh_session.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        warningShown = false;
                        console.log('Session refreshed successfully');
                        showToast('Session refreshed successfully', 'success');
                        // Reset the timeout
                        setTimeout(showSessionWarning, sessionTimeout);
                    } else {
                        document.getElementById('sessionErrorModal').style.display = 'flex';
                    }
                })
                .catch(error => {
                    console.error('Session refresh failed:', error);
                    document.getElementById('sessionErrorModal').style.display = 'flex';
                });
            } else {
                // User chose not to continue, redirect to login
                window.location.href = 'login.php';
            }
        }

        function showSessionWarning() {
            if (!warningShown) {
                warningShown = true;
                const modal = document.getElementById('sessionWarningModal');
                if (modal) {
                    modal.style.display = 'flex';
                    // Focus the modal for accessibility
                    modal.focus();
                }
            }
        }

        // Start session timeout timer
        setTimeout(showSessionWarning, sessionTimeout);

        // Also check on page visibility change (tab becomes active)
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible' && warningShown) {
                // If warning was shown while tab was hidden, ensure modal is visible
                const modal = document.getElementById('sessionWarningModal');
                if (modal && modal.style.display !== 'flex') {
                    modal.style.display = 'flex';
                }
            }
        });
        <?php endif; ?>

        // Global Loading Overlay System
        window.Loading = {
            show: function(message = 'Loading...') {
                const overlay = document.getElementById('globalLoadingOverlay');
                const text = document.getElementById('loadingText');
                if (overlay && text) {
                    text.textContent = message;
                    overlay.style.display = 'flex';
                }
            },
            hide: function() {
                const overlay = document.getElementById('globalLoadingOverlay');
                if (overlay) {
                    overlay.style.display = 'none';
                }
            },
            update: function(message) {
                const text = document.getElementById('loadingText');
                if (text) {
                    text.textContent = message;
                }
            }
        };

        // Utility function to show toast notifications
        window.showToast = function(message, type = 'info') {
            // Check if toast exists, create if not
            let toast = document.getElementById('globalToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'globalToast';
                toast.className = 'global-toast';
                document.body.appendChild(toast);
            }

            // Set message and type
            toast.textContent = message;
            toast.className = 'global-toast toast-' + type + ' toast-show';

            // Auto-hide after 3 seconds
            setTimeout(() => {
                toast.className = 'global-toast toast-' + type;
            }, 3000);
        };
    </script>

    <!-- Global Loading Overlay -->
    <div id="globalLoadingOverlay" class="global-loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <div class="spinner-border"></div>
            <p id="loadingText">Loading...</p>
        </div>
    </div>

    <style>
        /* Global Loading Overlay */
        .global-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(3px);
        }

        .loading-spinner {
            background: white;
            padding: 2.5rem 3rem;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .spinner-border {
            width: 60px;
            height: 60px;
            border: 6px solid #f3f3f3;
            border-top: 6px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-spinner p {
            margin: 0;
            color: #2c3e50;
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* Global Toast Notifications */
        .global-toast {
            position: fixed;
            top: -100px;
            right: 20px;
            min-width: 300px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            transition: top 0.3s ease-out;
            font-size: 1rem;
            font-weight: 500;
        }

        .global-toast.toast-show {
            top: 80px;
        }

        .toast-success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
        }

        .toast-error {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .toast-warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }

        .toast-info {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        @media (max-width: 768px) {
            .loading-spinner {
                padding: 2rem;
            }

            .spinner-border {
                width: 50px;
                height: 50px;
            }

            .global-toast {
                min-width: auto;
                max-width: 90%;
                right: 5%;
            }
        }
    </style>
</body>
</html>