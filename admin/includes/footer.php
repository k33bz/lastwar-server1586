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
                        <a href="audit_log_viewer.php" class="quick-action">
                            📋 Audit Logs
                        </a>
                        <a href="backup_restore.php" class="quick-action">
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
                            <strong>Version:</strong> v2.1.0
                        </div>
                        <div class="info-item">
                            <strong>Last Updated:</strong> Oct 15, 2025
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
                    <a href="../documentation/" target="_blank">Documentation</a>
                    <a href="../support/" target="_blank">Support</a>
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

    <style>
        .admin-footer {
            background: #2c3e50;
            color: white;
            margin-top: 4rem;
            padding: 2rem 0 1rem;
        }
        
        .footer-container {
            max-width: 1200px;
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
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('securityModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
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
        
        setTimeout(function() {
            if (!warningShown && document.visibilityState === 'visible') {
                warningShown = true;
                if (confirm('Your session will expire in 5 minutes. Continue working?')) {
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
                            // Reset the timeout
                            setTimeout(arguments.callee, sessionTimeout);
                        }
                    })
                    .catch(error => {
                        console.error('Session refresh failed:', error);
                        alert('Session refresh failed. Please save your work and log in again.');
                    });
                } else {
                    // User chose not to continue, redirect to login
                    window.location.href = 'login.php';
                }
            }
        }, sessionTimeout);
        <?php endif; ?>
    </script>
</body>
</html>