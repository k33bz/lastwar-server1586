<?php
/**
 * Help Drawer Component
 * Version: 1.0.0
 * Created: 2025-11-12
 *
 * Reusable help documentation drawer for admin panel pages.
 * Provides contextual, role-specific help documentation.
 *
 * Usage:
 *   // At the end of your page, before footer
 *   render_help_drawer([
 *       'title' => 'Alliance Management Help',
 *       'sections' => [
 *           ['title' => 'Section 1', 'content' => '...'],
 *           ['title' => 'Section 2', 'content' => '...']
 *       ]
 *   ]);
 */

/**
 * Render help drawer with provided content
 *
 * @param array $config Configuration array with 'title' and 'sections'
 */
function render_help_drawer($config) {
    $title = $config['title'] ?? 'Help & Documentation';
    $sections = $config['sections'] ?? [];
    ?>

    <!-- Help Drawer Trigger Button -->
    <button id="help-drawer-trigger" class="help-drawer-trigger" aria-label="Open help documentation" title="Help & Documentation">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        <span>Help</span>
    </button>

    <!-- Help Drawer Overlay -->
    <div id="help-drawer-overlay" class="help-drawer-overlay"></div>

    <!-- Help Drawer -->
    <div id="help-drawer" class="help-drawer">
        <div class="help-drawer-header">
            <h2><?php echo htmlspecialchars($title); ?></h2>
            <button id="help-drawer-close" class="help-drawer-close" aria-label="Close help">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="help-drawer-content">
            <?php foreach ($sections as $section): ?>
                <div class="help-section">
                    <h3 class="help-section-title"><?php echo htmlspecialchars($section['title']); ?></h3>
                    <div class="help-section-content">
                        <?php echo $section['content']; // Content should be pre-sanitized HTML ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="help-drawer-footer">
            <button id="help-report-problem-btn" class="help-report-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Report a Problem
            </button>
        </div>
    </div>

    <!-- Problem Report Modal -->
    <div id="help-report-modal" class="help-report-modal">
        <div class="help-report-modal-content">
            <div class="help-report-modal-header">
                <h3>Report a Problem</h3>
                <button id="help-report-modal-close" class="help-modal-close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="help-report-modal-body">
                <p style="color: #666; margin-bottom: 1rem;">Describe the problem you're experiencing. This will notify the admin team with details about your session.</p>

                <div class="help-form-group">
                    <label for="help-problem-description">Problem Description</label>
                    <textarea id="help-problem-description" rows="6" placeholder="Describe what you were trying to do and what went wrong..."></textarea>
                </div>

                <div class="help-report-context">
                    <strong>The following information will be included automatically:</strong>
                    <ul>
                        <li>Current page: <span id="help-context-page"></span></li>
                        <li>Your account: <span id="help-context-user"></span></li>
                        <li>Browser: <span id="help-context-browser"></span></li>
                        <li>Timestamp: <span id="help-context-timestamp"></span></li>
                    </ul>
                </div>

                <div id="help-report-error" class="help-report-error" style="display: none;"></div>
                <div id="help-report-success" class="help-report-success" style="display: none;"></div>
            </div>

            <div class="help-report-modal-footer">
                <button id="help-report-cancel-btn" class="help-btn-secondary">Cancel</button>
                <button id="help-report-submit-btn" class="help-btn-primary">Submit Report</button>
            </div>
        </div>
    </div>

    <style>
        /* Help Drawer Trigger Button */
        .help-drawer-trigger {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #0066cc;
            color: white;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
            transition: all 0.3s ease;
            z-index: 999;
        }

        .help-drawer-trigger:hover {
            background: #0052a3;
            box-shadow: 0 6px 16px rgba(0, 102, 204, 0.4);
            transform: translateY(-2px);
        }

        .help-drawer-trigger svg {
            width: 20px;
            height: 20px;
        }

        /* Dark theme button */
        body.dark-theme .help-drawer-trigger {
            background: #1e88e5;
            box-shadow: 0 4px 12px rgba(30, 136, 229, 0.3);
        }

        body.dark-theme .help-drawer-trigger:hover {
            background: #1976d2;
            box-shadow: 0 6px 16px rgba(30, 136, 229, 0.4);
        }

        /* Help Drawer Overlay */
        .help-drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            z-index: 1000;
        }

        .help-drawer-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Help Drawer */
        .help-drawer {
            position: fixed;
            top: 0;
            right: -500px;
            width: 500px;
            max-width: 90vw;
            height: 100vh;
            background: white;
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
            transition: right 0.3s ease;
            z-index: 1001;
            display: flex;
            flex-direction: column;
        }

        .help-drawer.active {
            right: 0;
        }

        /* Dark theme drawer */
        body.dark-theme .help-drawer {
            background: #16213e;
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.4);
        }

        /* Help Drawer Header */
        .help-drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            background: #f5f5f5;
        }

        body.dark-theme .help-drawer-header {
            background: #0f3460;
            border-bottom-color: #1a4d7a;
        }

        .help-drawer-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #2c3e50;
        }

        body.dark-theme .help-drawer-header h2 {
            color: #e0e0e0;
        }

        .help-drawer-close {
            background: transparent;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .help-drawer-close:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #333;
        }

        body.dark-theme .help-drawer-close {
            color: #b0b0b0;
        }

        body.dark-theme .help-drawer-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #e0e0e0;
        }

        /* Help Drawer Content */
        .help-drawer-content {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        /* Help Sections */
        .help-section {
            margin-bottom: 2rem;
        }

        .help-section:last-child {
            margin-bottom: 0;
        }

        .help-section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0066cc;
            margin: 0 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #0066cc;
        }

        body.dark-theme .help-section-title {
            color: #1e88e5;
            border-bottom-color: #1e88e5;
        }

        .help-section-content {
            color: #333;
            line-height: 1.6;
        }

        body.dark-theme .help-section-content {
            color: #e0e0e0;
        }

        .help-section-content p {
            margin: 0 0 1rem 0;
        }

        .help-section-content ul,
        .help-section-content ol {
            margin: 0 0 1rem 0;
            padding-left: 1.5rem;
        }

        .help-section-content li {
            margin-bottom: 0.5rem;
        }

        .help-section-content strong {
            color: #0066cc;
            font-weight: 600;
        }

        body.dark-theme .help-section-content strong {
            color: #1e88e5;
        }

        .help-section-content code {
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 0.2rem 0.4rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #d63384;
        }

        body.dark-theme .help-section-content code {
            background: #0f3460;
            border-color: #1a4d7a;
            color: #ff6b9d;
        }

        .help-section-content .help-note {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }

        body.dark-theme .help-section-content .help-note {
            background: rgba(33, 150, 243, 0.1);
            border-left-color: #1e88e5;
        }

        .help-section-content .help-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }

        body.dark-theme .help-section-content .help-warning {
            background: rgba(255, 193, 7, 0.1);
            border-left-color: #ffb300;
        }

        .help-section-content .help-success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }

        body.dark-theme .help-section-content .help-success {
            background: rgba(40, 167, 69, 0.1);
            border-left-color: #4caf50;
        }

        /* Help Drawer Footer */
        .help-drawer-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e0e0e0;
            background: #f9f9f9;
        }

        body.dark-theme .help-drawer-footer {
            background: #0f3460;
            border-top-color: #1a4d7a;
        }

        .help-report-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: #ffc107;
            color: #000;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .help-report-btn:hover {
            background: #ffb300;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3);
        }

        body.dark-theme .help-report-btn {
            background: #ff9800;
            color: #000;
        }

        body.dark-theme .help-report-btn:hover {
            background: #fb8c00;
        }

        /* Problem Report Modal */
        .help-report-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1002;
        }

        .help-report-modal.active {
            display: flex;
        }

        .help-report-modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        body.dark-theme .help-report-modal-content {
            background: #16213e;
        }

        .help-report-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
        }

        body.dark-theme .help-report-modal-header {
            border-bottom-color: #1a4d7a;
        }

        .help-report-modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #2c3e50;
        }

        body.dark-theme .help-report-modal-header h3 {
            color: #e0e0e0;
        }

        .help-modal-close {
            background: transparent;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .help-modal-close:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #333;
        }

        body.dark-theme .help-modal-close {
            color: #b0b0b0;
        }

        body.dark-theme .help-modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #e0e0e0;
        }

        .help-report-modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }

        .help-form-group {
            margin-bottom: 1.5rem;
        }

        .help-form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        body.dark-theme .help-form-group label {
            color: #e0e0e0;
        }

        .help-form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
            transition: border-color 0.2s ease;
        }

        .help-form-group textarea:focus {
            outline: none;
            border-color: #0066cc;
        }

        body.dark-theme .help-form-group textarea {
            background: #0f3460;
            border-color: #1a4d7a;
            color: #e0e0e0;
        }

        body.dark-theme .help-form-group textarea:focus {
            border-color: #1e88e5;
        }

        .help-report-context {
            background: #f5f5f5;
            border-left: 4px solid #0066cc;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        body.dark-theme .help-report-context {
            background: #0f3460;
            border-left-color: #1e88e5;
        }

        .help-report-context strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }

        body.dark-theme .help-report-context strong {
            color: #e0e0e0;
        }

        .help-report-context ul {
            margin: 0;
            padding-left: 1.5rem;
            color: #666;
        }

        body.dark-theme .help-report-context ul {
            color: #b0b0b0;
        }

        .help-report-context li {
            margin-bottom: 0.25rem;
        }

        .help-report-error,
        .help-report-success {
            padding: 0.75rem;
            border-radius: 6px;
            margin-top: 1rem;
        }

        .help-report-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
        }

        .help-report-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #060;
        }

        body.dark-theme .help-report-error {
            background: rgba(220, 53, 69, 0.2);
            border-color: rgba(220, 53, 69, 0.4);
            color: #ff6b9d;
        }

        body.dark-theme .help-report-success {
            background: rgba(40, 167, 69, 0.2);
            border-color: rgba(40, 167, 69, 0.4);
            color: #4caf50;
        }

        .help-report-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e0e0e0;
        }

        body.dark-theme .help-report-modal-footer {
            border-top-color: #1a4d7a;
        }

        .help-btn-primary,
        .help-btn-secondary {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .help-btn-primary {
            background: #0066cc;
            color: white;
        }

        .help-btn-primary:hover {
            background: #0052a3;
        }

        .help-btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        body.dark-theme .help-btn-primary {
            background: #1e88e5;
        }

        body.dark-theme .help-btn-primary:hover {
            background: #1976d2;
        }

        .help-btn-secondary {
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }

        .help-btn-secondary:hover {
            background: #e0e0e0;
        }

        body.dark-theme .help-btn-secondary {
            background: #0f3460;
            color: #e0e0e0;
            border-color: #1a4d7a;
        }

        body.dark-theme .help-btn-secondary:hover {
            background: #1a4d7a;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .help-drawer {
                width: 100vw;
                right: -100vw;
            }

            .help-drawer-trigger span {
                display: none;
            }

            .help-drawer-trigger {
                padding: 0.75rem;
                border-radius: 50%;
            }

            .help-report-modal-content {
                width: 95%;
                max-height: 95vh;
            }
        }

        /* Scrollbar styling */
        .help-drawer-content::-webkit-scrollbar {
            width: 8px;
        }

        .help-drawer-content::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        body.dark-theme .help-drawer-content::-webkit-scrollbar-track {
            background: #0f3460;
        }

        .help-drawer-content::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .help-drawer-content::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        body.dark-theme .help-drawer-content::-webkit-scrollbar-thumb {
            background: #1a4d7a;
        }

        body.dark-theme .help-drawer-content::-webkit-scrollbar-thumb:hover {
            background: #2563a8;
        }
    </style>

    <script>
        (function() {
            const trigger = document.getElementById('help-drawer-trigger');
            const drawer = document.getElementById('help-drawer');
            const overlay = document.getElementById('help-drawer-overlay');
            const closeBtn = document.getElementById('help-drawer-close');

            // Open drawer
            function openDrawer() {
                drawer.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            // Close drawer
            function closeDrawer() {
                drawer.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            // Event listeners
            trigger.addEventListener('click', openDrawer);
            closeBtn.addEventListener('click', closeDrawer);
            overlay.addEventListener('click', closeDrawer);

            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && drawer.classList.contains('active')) {
                    closeDrawer();
                }
            });

            // Problem Report Modal
            const reportBtn = document.getElementById('help-report-problem-btn');
            const reportModal = document.getElementById('help-report-modal');
            const reportModalClose = document.getElementById('help-report-modal-close');
            const reportCancelBtn = document.getElementById('help-report-cancel-btn');
            const reportSubmitBtn = document.getElementById('help-report-submit-btn');
            const reportDescription = document.getElementById('help-problem-description');
            const reportError = document.getElementById('help-report-error');
            const reportSuccess = document.getElementById('help-report-success');

            // Populate context information
            function populateContext() {
                document.getElementById('help-context-page').textContent = window.location.pathname;
                document.getElementById('help-context-user').textContent = '<?php echo addslashes($GLOBALS["user"]->email ?? "Unknown"); ?>';
                document.getElementById('help-context-browser').textContent = navigator.userAgent.split('(')[1]?.split(')')[0] || 'Unknown';
                document.getElementById('help-context-timestamp').textContent = new Date().toLocaleString();
            }

            // Open report modal
            function openReportModal() {
                populateContext();
                reportModal.classList.add('active');
                reportDescription.value = '';
                reportError.style.display = 'none';
                reportSuccess.style.display = 'none';
                reportDescription.focus();
            }

            // Close report modal
            function closeReportModal() {
                reportModal.classList.remove('active');
            }

            // Submit problem report
            async function submitProblemReport() {
                const description = reportDescription.value.trim();

                if (!description) {
                    reportError.textContent = 'Please describe the problem you\'re experiencing.';
                    reportError.style.display = 'block';
                    return;
                }

                reportSubmitBtn.disabled = true;
                reportSubmitBtn.textContent = 'Submitting...';
                reportError.style.display = 'none';
                reportSuccess.style.display = 'none';

                try {
                    const reportData = {
                        description: description,
                        page: window.location.pathname,
                        url: window.location.href,
                        browser: navigator.userAgent,
                        screen_resolution: `${window.screen.width}x${window.screen.height}`,
                        viewport: `${window.innerWidth}x${window.innerHeight}`,
                        timestamp: new Date().toISOString()
                    };

                    const response = await fetch('notifications_api.php?action=create_notification', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'include',
                        body: JSON.stringify({
                            type: 'support_request',
                            priority: 'high',
                            title: 'User Problem Report: ' + window.location.pathname.split('/').pop(),
                            message: description + '\n\n**Context:**\n' +
                                   '- Page: ' + reportData.page + '\n' +
                                   '- URL: ' + reportData.url + '\n' +
                                   '- Browser: ' + reportData.browser.split('(')[1]?.split(')')[0] + '\n' +
                                   '- Resolution: ' + reportData.screen_resolution + '\n' +
                                   '- Timestamp: ' + reportData.timestamp,
                            recipient_type: 'role',
                            recipients: ['admin']
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        reportSuccess.textContent = 'Problem report submitted successfully! An admin will be notified.';
                        reportSuccess.style.display = 'block';
                        reportDescription.value = '';

                        setTimeout(() => {
                            closeReportModal();
                        }, 3000);
                    } else {
                        reportError.textContent = data.error || 'Failed to submit problem report. Please try again.';
                        reportError.style.display = 'block';
                    }
                } catch (error) {
                    reportError.textContent = 'Network error: ' + error.message;
                    reportError.style.display = 'block';
                } finally {
                    reportSubmitBtn.disabled = false;
                    reportSubmitBtn.textContent = 'Submit Report';
                }
            }

            // Event listeners for report modal
            reportBtn.addEventListener('click', openReportModal);
            reportModalClose.addEventListener('click', closeReportModal);
            reportCancelBtn.addEventListener('click', closeReportModal);
            reportSubmitBtn.addEventListener('click', submitProblemReport);
            reportModal.addEventListener('click', function(e) {
                if (e.target === reportModal) {
                    closeReportModal();
                }
            });

            // Close report modal on Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && reportModal.classList.contains('active')) {
                    closeReportModal();
                }
            });
        })();
    </script>

    <?php
}
