# Admin Panel Shared Components

## 📍 Navigation
- **← Back to Admin**: [../README.md](../README.md)
- **← Back to Main**: [../../README.md](../../README.md)
- **📚 Full Documentation**: [../../DOCUMENTATION.md](../../DOCUMENTATION.md)

## Overview
This directory contains shared header and footer components for the admin panel to ensure consistent design and functionality across all admin pages.

## Files

### `header.php`
- **Purpose**: Provides consistent navigation, security checks, and styling
- **Features**:
  - Automatic authentication verification
  - Responsive navigation menu
  - User information display
  - Security headers
  - Mobile-friendly design
  - Session management

### `footer.php`
- **Purpose**: Provides consistent footer with system information and quick actions
- **Features**:
  - System status indicators
  - Quick action buttons
  - Security information modal
  - Version information
  - Session timeout warnings
  - Auto-refresh functionality

### `breadcrumbs.php` (NEW - v3.0.0)
- **Purpose**: Provides breadcrumb navigation and contextual help components
- **Features**:
  - Auto-detecting breadcrumb navigation trails
  - Parent-child page relationships
  - Contextual help tooltips (4-position support)
  - Help modals for detailed information
  - Accessible ARIA labels
  - Icon support for visual clarity

### `styles.css` (v3.0.0)
- **Purpose**: Consolidated shared CSS for all admin pages
- **Features**:
  - Breadcrumb navigation styles
  - Help tooltip system with directional arrows
  - Help modal system with animations
  - Mobile responsive design
  - Consistent color scheme and spacing

### `scripts.js` (v1.1.0)
- **Purpose**: Shared JavaScript utilities for admin pages
- **Features**:
  - Modal management (open, close, backdrop)
  - Toast notifications
  - Email masking utilities
  - Form serialization helpers
  - API request wrappers
  - String and data utilities
  - Copy to clipboard functionality

## Usage

### Basic Implementation

```php
<?php
session_start();

// Set page title (optional)
$page_title = "Your Page Title";

// Include shared header
include 'includes/header.php';

// Include breadcrumbs helper (NEW)
require_once 'includes/breadcrumbs.php';
?>

<!-- Render breadcrumb navigation (NEW) -->
<?php echo render_breadcrumbs(); ?>

<!-- Your page content here -->
<div class="page-header">
    <h1 class="page-title">Page Title</h1>
    <p class="page-description">Page description</p>
</div>

<div class="container">
    <!-- Your content -->
</div>

<?php include 'includes/footer.php'; ?>
```

### Breadcrumb Navigation

**Auto-detecting breadcrumbs** based on current page:

```php
// Automatically detects current page from $_SERVER['PHP_SELF']
<?php echo render_breadcrumbs(); ?>
```

**Manual page specification:**

```php
// Explicitly set the current page
<?php echo render_breadcrumbs('security_monitor.php'); ?>
```

**Configuring breadcrumb trails:**

Edit `includes/breadcrumbs.php` to add new pages to `$breadcrumb_config`:

```php
$breadcrumb_config = [
    'your_page.php' => [
        'title' => 'Your Page Title',
        'icon' => '📄',  // Optional emoji icon
        'parent' => 'dashboard.php'  // Parent page (null for root)
    ]
];
```

### Contextual Help System

#### Help Tooltips

**Basic tooltip:**

```php
<label for="field_name">
    Field Label
    <?php echo help_tooltip('This is helpful tooltip text'); ?>
</label>
```

**Positioned tooltip:**

```php
<!-- Position options: top, right, bottom, left -->
<?php echo help_tooltip('Help text here', 'right'); ?>
<?php echo help_tooltip('Help text here', 'bottom'); ?>
```

**Example in form:**

```php
<div class="form-group">
    <label for="expiry_days">
        Token Expiry (days)
        <?php echo help_tooltip('Number of days before token expires (1-365)', 'right'); ?>
    </label>
    <input type="number" id="expiry_days" name="expiry_days" class="form-control">
</div>
```

#### Help Modals

**Basic modal:**

```php
<h3>
    Section Title
    <?php echo help_modal('Modal Title', 'Detailed content here', 'unique-modal-id'); ?>
</h3>
```

**Modal with HTML content:**

```php
<?php
$modal_content = '
    <p>Introduction paragraph.</p>
    <h4>Subsection</h4>
    <ul>
        <li>List item 1</li>
        <li>List item 2</li>
    </ul>
    <pre><code>Code example</code></pre>
';

echo help_modal('Detailed Help', $modal_content, 'my-help-modal');
?>
```

**Full example:**

```php
<div class="info-box">
    <h3>
        What is a Test Token?
        <?php echo help_modal(
            'Understanding Test Tokens',
            '<p>Test tokens are JWT tokens used for:</p>
            <ul>
                <li><strong>API Testing:</strong> Automated integration tests</li>
                <li><strong>Development:</strong> Quick API endpoint testing</li>
                <li><strong>CI/CD:</strong> Deployment authentication</li>
            </ul>',
            'test-token-help'
        ); ?>
    </h3>
    <p>Brief description...</p>
</div>
```

### Required Session Variables
The header expects these session variables:
- `$_SESSION['user_id']` - User ID
- `$_SESSION['role']` - Must be 'admin'
- `$_SESSION['username']` - Display name (optional)

### Page Title
Set `$page_title` before including the header to customize the browser title:
```php
$page_title = "Dashboard"; // Results in "Dashboard - Admin Panel"
```

### Navigation Highlighting
The header automatically highlights the current page in navigation based on the filename.

### Alert Messages
Use these CSS classes for consistent alert styling:
```php
<div class="alert alert-success">Success message</div>
<div class="alert alert-error">Error message</div>
<div class="alert alert-warning">Warning message</div>
```

## Styling

### Available CSS Classes

#### Layout
- `.main-container` - Main content wrapper (max-width: 1200px)
- `.page-header` - Page title section
- `.container` - Content container with white background

#### Components
- `.btn` - Base button class
- `.btn-primary` - Primary action button (blue)
- `.btn-secondary` - Secondary action button (gray)
- `.btn-danger` - Danger action button (red)

#### Alerts
- `.alert` - Base alert class
- `.alert-success` - Success alert (green)
- `.alert-error` - Error alert (red)
- `.alert-warning` - Warning alert (yellow)

### Responsive Design
The components are fully responsive and work on:
- Desktop (1200px+)
- Tablet (768px - 1199px)
- Mobile (< 768px)

## Security Features

### Authentication
- Automatic redirect to login if not authenticated
- Role-based access control (admin only)
- Session validation

### Security Headers
- X-Content-Type-Options: nosniff
- X-Frame-Options: DENY
- X-XSS-Protection: 1; mode=block
- robots: noindex, nofollow

### Session Management
- Automatic session refresh via AJAX
- Session timeout warnings
- Secure logout functionality

## Customization

### Adding New Navigation Items
Edit `header.php` and add new navigation links:
```php
<a href="new_page.php" class="nav-link <?php echo $current_page === 'new_page.php' ? 'active' : ''; ?>">
    New Page
</a>
```

### Modifying Footer Content
Edit `footer.php` to add new sections or modify existing ones.

### Custom Styling
Add page-specific styles after including the header:
```php
<?php include 'includes/header.php'; ?>

<style>
/* Your custom styles here */
.custom-class {
    /* Custom styling */
}
</style>
```

## Dependencies

### Required Files
- `refresh_session.php` - Session refresh endpoint
- `../logout.php` - Logout functionality
- `../login.php` - Login page for redirects

### Optional Files
- `../documentation/` - Documentation links
- `../support/` - Support page links

## Version History

### v3.0.0 (2025-10-16)
- **NEW**: Breadcrumb navigation system (`breadcrumbs.php`)
- **NEW**: Contextual help tooltips with 4-position support
- **NEW**: Help modal system for detailed information
- **NEW**: Consolidated shared CSS (`styles.css`)
- **NEW**: Shared JavaScript utilities (`scripts.js`)
- Enhanced documentation and usage examples

### v1.0.0 (2025-10-15)
- Initial implementation
- Responsive design
- Security features
- Session management
- Navigation system
- Footer with system status

## Best Practices

1. **Always include session_start()** before the header
2. **Set page title** for better UX and SEO
3. **Use consistent alert classes** for messages
4. **Test on mobile devices** to ensure responsiveness
5. **Follow security guidelines** for admin pages
6. **Use semantic HTML** within your content areas

## Troubleshooting

### Common Issues

**Navigation not highlighting correctly**
- Ensure the filename matches exactly in the navigation array

**Session timeout not working**
- Verify `refresh_session.php` exists and is accessible
- Check that audit logging is properly configured

**Styling conflicts**
- Ensure your custom CSS doesn't override critical layout styles
- Use specific selectors to avoid conflicts

**Mobile display issues**
- Test viewport meta tag is not overridden
- Ensure content fits within responsive breakpoints---


## 📞 Support & Contact

For questions about shared components:
- **Admin Documentation**: [../README.md](../README.md)
- **Main Documentation**: [../../README.md](../../README.md)
- **GitHub Issues**: [Report bugs or request features](https://github.com/username/your-repo/issues)

---

**Version**: 3.0.0 | **Last Updated**: October 16, 2025 | **Part of**: [Server 1586 Admin System](../README.md)