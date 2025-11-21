<?php
/**
 * Season 4 Page
 * Placeholder for Season 4 management
 *
 * @version 1.0.0
 * @date 2025-11-17
 */

// Require JWT authentication
define('ADMIN_INIT', true);
define('ADMIN_BASE_PATH', __DIR__);
require_once 'jwt.php';

$user = require_jwt_session();

// Set page title for header
$page_title = __('seasons.season4.title');

// Include header
require_once 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><?php echo __('seasons.season4.title'); ?></h1>
        <p class="page-description"><?php echo __('seasons.season4.description'); ?></p>
    </div>

    <div class="card" style="text-align: center; padding: 3rem;">
        <div style="font-size: 4rem; margin-bottom: 1rem;">🔜</div>
        <h2 style="color: var(--text-secondary); margin-bottom: 1rem;"><?php echo __('seasons.coming_soon'); ?></h2>
        <p style="color: var(--text-tertiary);"><?php echo __('seasons.season4.placeholder_message'); ?></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
