# Admin Panel Shared Components

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

## Usage

### Basic Implementation

```php
<?php
session_start();

// Set page title (optional)
$page_title = "Your Page Title";

// Include shared header
include 'includes/header.php';
?>

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
- Ensure content fits within responsive breakpoints