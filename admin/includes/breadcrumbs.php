<?php
/**
 * Breadcrumb Navigation Helper
 *
 * Provides breadcrumb navigation for admin pages with automatic current page detection
 *
 * @version 1.0.0
 * @date 2025-10-16
 */

// Breadcrumb configuration
$breadcrumb_config = [
    'dashboard.php' => [
        'title' => 'Dashboard',
        'icon' => '🏠',
        'parent' => null
    ],
    'security_monitor.php' => [
        'title' => 'Security Monitor',
        'icon' => '🔒',
        'parent' => 'dashboard.php'
    ],
    'generate_test_token.php' => [
        'title' => 'Generate Test Token',
        'icon' => '🔑',
        'parent' => 'security_monitor.php'
    ],
    'alliance_edit.php' => [
        'title' => 'Alliance Management',
        'icon' => '✏️',
        'parent' => 'dashboard.php'
    ],
    'test_token_auth.php' => [
        'title' => 'Test Token Auth',
        'icon' => '🔬',
        'parent' => 'generate_test_token.php'
    ],
];

/**
 * Render breadcrumb navigation
 *
 * @param string|null $current_page Current page filename (auto-detected if null)
 * @return string HTML breadcrumb navigation
 */
function render_breadcrumbs($current_page = null) {
    global $breadcrumb_config;

    // Auto-detect current page
    if ($current_page === null) {
        $current_page = basename($_SERVER['PHP_SELF']);
    }

    // Build breadcrumb trail
    $trail = [];
    $page = $current_page;

    while ($page && isset($breadcrumb_config[$page])) {
        array_unshift($trail, $page);
        $page = $breadcrumb_config[$page]['parent'];
    }

    // If no trail found, default to current page only
    if (empty($trail) && isset($breadcrumb_config[$current_page])) {
        $trail = [$current_page];
    }

    // Render breadcrumbs
    $html = '<nav class="breadcrumbs" aria-label="Breadcrumb">';
    $html .= '<ol class="breadcrumb-list">';

    foreach ($trail as $index => $page_file) {
        $config = $breadcrumb_config[$page_file];
        $is_current = ($page_file === $current_page);

        $html .= '<li class="breadcrumb-item' . ($is_current ? ' current' : '') . '">';

        if ($is_current) {
            $html .= '<span class="breadcrumb-current">';
            $html .= $config['icon'] . ' ' . htmlspecialchars($config['title']);
            $html .= '</span>';
        } else {
            $html .= '<a href="' . htmlspecialchars($page_file) . '" class="breadcrumb-link">';
            $html .= $config['icon'] . ' ' . htmlspecialchars($config['title']);
            $html .= '</a>';
            $html .= '<span class="breadcrumb-separator">›</span>';
        }

        $html .= '</li>';
    }

    $html .= '</ol>';
    $html .= '</nav>';

    return $html;
}

/**
 * Render contextual help tooltip
 *
 * @param string $text Help text content
 * @param string $position Tooltip position (top, right, bottom, left)
 * @return string HTML help icon with tooltip
 */
function help_tooltip($text, $position = 'top') {
    $html = '<span class="help-tooltip" data-position="' . htmlspecialchars($position) . '">';
    $html .= '<span class="help-icon">ℹ️</span>';
    $html .= '<span class="help-content">' . htmlspecialchars($text) . '</span>';
    $html .= '</span>';
    return $html;
}

/**
 * Render help modal trigger
 *
 * @param string $title Modal title
 * @param string $content Modal content (HTML allowed)
 * @param string $id Unique modal ID
 * @return string HTML modal trigger and modal
 */
function help_modal($title, $content, $id) {
    $modal_id = 'modal-' . $id;

    $html = '<span class="help-modal-trigger" onclick="openModal(\'' . $modal_id . '\')">';
    $html .= '<span class="help-icon">ℹ️</span>';
    $html .= '</span>';

    $html .= '<div id="' . $modal_id . '" class="help-modal" onclick="closeModalOnBackdrop(event, \'' . $modal_id . '\')">';
    $html .= '<div class="help-modal-content">';
    $html .= '<div class="help-modal-header">';
    $html .= '<h3>' . htmlspecialchars($title) . '</h3>';
    $html .= '<button class="help-modal-close" onclick="closeModal(\'' . $modal_id . '\')">&times;</button>';
    $html .= '</div>';
    $html .= '<div class="help-modal-body">';
    $html .= $content; // HTML allowed for formatted content
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}
?>
