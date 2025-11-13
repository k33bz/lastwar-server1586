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
        })();
    </script>

    <?php
}
