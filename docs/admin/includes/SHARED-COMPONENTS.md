# Admin Panel Shared Components

**Version:** 1.0.0
**Date:** October 16, 2025

This directory contains all shared components used across the admin panel pages.

---

## Table of Contents

1. [Overview](#overview)
2. [File Structure](#file-structure)
3. [PHP Components](#php-components)
4. [CSS Styles](#css-styles)
5. [JavaScript Utilities](#javascript-utilities)
6. [API Helpers](#api-helpers)
7. [Usage Examples](#usage-examples)
8. [Best Practices](#best-practices)

---

## Overview

The `includes/` directory provides reusable components that ensure consistency across all admin pages:

- **header.php** - Shared navigation and authentication
- **footer.php** - System status and session management
- **email_utils.php** - Email masking functions
- **styles.css** - Consolidated CSS styles (NEW)
- **scripts.js** - Shared JavaScript utilities (NEW)
- **api_helpers.php** - Standardized API responses (NEW)

---

## File Structure

```
admin/includes/
├── header.php              # Shared header with navigation
├── footer.php              # Shared footer with status
├── email_utils.php         # Email masking utilities
├── styles.css              # Consolidated CSS (NEW)
├── scripts.js              # JavaScript utilities (NEW)
├── api_helpers.php         # API response helpers (NEW)
└── README.md               # This file
```

---

## PHP Components

### header.php

**Purpose:** Provides consistent header, navigation, and authentication checks

**Features:**
- JWT session validation
- Navigation menu with role-based links
- User info display with email masking
- Token countdown timer
- Security headers
- Includes shared CSS and JavaScript

**Usage:**
```php
<?php
// Require JWT authentication first
require_once 'jwt.php';
$user = require_jwt_session();

// Set page title (optional)
$page_title = "Dashboard";

// Include shared header
include 'includes/header.php';
?>

<!-- Your page content here -->

<?php include 'includes/footer.php'; ?>
```

**Navigation Links:**
- Dashboard (all roles)
- Alliances (all roles)
- Power Editor (admin + APE users)
- Users (admin only)
- Logs (admin only)
- Security (admin only)

**Variables Required:**
- `$user` - JWT user object from `require_jwt_session()`
- `$page_title` (optional) - Page title for browser tab

---

### footer.php

**Purpose:** Provides consistent footer with system status and quick actions

**Features:**
- System status indicators (online/offline)
- Quick action links (role-based)
- System information (version, security level)
- Session management and auto-refresh
- Security info modal

**Usage:**
```php
<?php include 'includes/footer.php'; ?>
```

**System Status Indicators:**
- Authentication System
- Key Rotation
- Security Monitor

**Session Management:**
- 25-minute warning before 30-minute expiry
- Auto-refresh option
- Countdown timer in logout button

---

### email_utils.php

**Purpose:** Centralized email masking and display functions

**Functions:**

#### `maskEmail($email)`
Masks an email address for privacy.

```php
$masked = maskEmail('user@example.com');
// Returns: u***r@example.com
```

#### `emailDisplay($email, $showToggle = true)`
Generates HTML for email display with toggle button.

```php
echo emailDisplay('user@example.com', true);
// Outputs: <span>...</span> <button>...</button>
```

**Features:**
- Click-to-reveal functionality
- SVG eye icons for show/hide
- Data attributes for email storage
- Automatic masking on load

---

## CSS Styles

### styles.css

**Purpose:** Consolidated styles for consistent design across all admin pages

**Sections:**

1. **Base & Layout**
   - Container, page headers, titles, descriptions

2. **Buttons**
   - Primary, secondary, success, danger, small variants
   - Hover effects and transitions

3. **Tables**
   - Standard table styling
   - Sortable headers with indicators
   - Hover states

4. **Forms**
   - Input fields, select boxes, checkboxes
   - Focus states and validation
   - Checkbox groups

5. **Alerts & Messages**
   - Success, error, warning, info alerts
   - Info boxes and warning boxes

6. **Modals**
   - Modal overlays and content
   - Headers, bodies, footers
   - Close buttons

7. **Statistics Cards**
   - Stat grids and cards
   - Number displays

8. **Badges**
   - Role badges (admin, R5, R4)
   - APE badges
   - Alliance tags
   - Backup reason badges

9. **Status Indicators**
   - Active/inactive dots
   - Status text

10. **Email Display & Masking**
    - Email containers
    - Toggle buttons
    - Masked text styles

11. **Search & Filters**
    - Search bars
    - Filter dropdowns
    - Results count

12. **Actions & Button Groups**
    - Action button groups
    - Flex layouts

13. **Loading States**
    - Spinners
    - Loading text

14. **Empty States**
    - Empty message displays

15. **Utility Classes**
    - Table containers
    - Results count
    - Sections
    - Headers

16. **Responsive Design**
    - Mobile breakpoints (768px)
    - Tablet layouts

17. **Print Styles**
    - Print-friendly layouts

**Usage:**
```html
<!-- Automatically included via header.php -->
<link rel="stylesheet" href="includes/styles.css">
```

**Class Examples:**
```html
<button class="btn btn-primary">Save</button>
<div class="alert alert-success">Success message</div>
<span class="role-badge role-admin">ADMIN</span>
<table class="users-table">...</table>
```

---

## JavaScript Utilities

### scripts.js

**Purpose:** Shared JavaScript functions for common operations

**Sections:**

#### 1. Email Masking & Display
```javascript
maskEmail(email)                    // Mask an email address
toggleSingleEmail(button)           // Toggle one email display
toggleAllEmails()                   // Toggle all emails on page
```

#### 2. Modal Management
```javascript
openModal(modalId)                  // Open modal by ID
closeModal(modalId)                 // Close modal by ID
closeAllModals()                    // Close all open modals
initializeModals()                  // Setup modal event listeners
```

#### 3. Toast Notifications
```javascript
showToast(message, type, duration)  // Show toast notification
// Types: 'success', 'error', 'warning', 'info'
// Example: showToast('Saved!', 'success', 3000)
```

#### 4. Form Utilities
```javascript
serializeForm(form)                 // Form to FormData
serializeFormJSON(form)             // Form to JSON object
isValidEmail(email)                 // Validate email format
resetForm(form)                     // Reset form and clear errors
```

#### 5. API Helpers
```javascript
apiRequest(url, options)            // Make API request
apiPost(url, data)                  // POST request
apiGet(url, params)                 // GET request with params
```

#### 6. String & Data Utilities
```javascript
escapeHtml(text)                    // Escape HTML
formatNumber(num)                   // Format with thousands separator
formatBytes(bytes)                  // Format bytes to KB/MB/GB
timeAgo(datetime)                   // Calculate time ago
debounce(func, wait)                // Debounce function calls
```

#### 7. Table Utilities
```javascript
sortTableByColumn(table, index, dir) // Sort table column
filterTable(table, searchTerm)       // Filter table rows
```

#### 8. Copy to Clipboard
```javascript
copyToClipboard(text)               // Copy text to clipboard
// Returns Promise<boolean>
```

#### 9. Confirmation Dialogs
```javascript
confirmAction(message, title)       // Show confirmation modal
// Returns Promise<boolean>
```

**Usage:**
```html
<!-- Automatically included via header.php -->
<script src="includes/scripts.js"></script>

<script>
// Use utilities
showToast('Changes saved!', 'success');

const confirmed = await confirmAction('Delete this user?', 'Confirm Delete');
if (confirmed) {
    // Proceed with deletion
}
</script>
```

---

## API Helpers

### api_helpers.php

**Purpose:** Standardized API response functions for consistent JSON responses

**Functions:**

#### Success Responses

```php
apiSuccess($data = null, $message = null, $http_code = 200)
```
Send a successful JSON response.

```php
apiSuccess(['user_id' => 123], 'User created successfully', 201);
// {"success": true, "message": "User created successfully", "user_id": 123}
```

#### Error Responses

```php
apiError($error, $http_code = 400, $details = null)
```
Send an error JSON response.

```php
apiError('Invalid email format', 400);
// {"success": false, "error": "Invalid email format"}
```

#### Validation Errors

```php
apiValidationError($errors, $message = 'Validation failed')
```
Send validation error with field-specific errors.

```php
apiValidationError([
    'email' => 'Email is required',
    'password' => 'Password must be 8+ characters'
]);
```

#### HTTP Status Helpers

```php
apiUnauthorized($message = 'Unauthorized')      // 401
apiForbidden($message = 'Forbidden')            // 403
apiNotFound($message = 'Not found')             // 404
apiServerError($message, $exception = null)     // 500
```

#### Request Method Validation

```php
requireMethod($method)      // Require specific HTTP method(s)
requirePost()               // Require POST
requireGet()                // Require GET
```

#### Request Input

```php
getJsonInput($associative = true)               // Get JSON from request body
validateRequired($data, $required_fields)       // Validate required fields
getParam($key, $default, $method)               // Get request parameter
getIntParam($key, $default, $min, $max)         // Get integer parameter
```

#### Security & Utilities

```php
setCorsHeaders($origins, $methods)              // Set CORS headers
checkRateLimit($id, $max, $window)              // Rate limiting
paginate($data, $page, $per_page)               // Paginate array
logApiRequest($endpoint, $data)                 // Log API request
sanitizeInput($input)                           // Sanitize input
validateEmail($email)                           // Validate email
```

**Usage Example:**
```php
<?php
require_once __DIR__ . '/includes/api_helpers.php';
require_once 'jwt.php';

// Validate authentication
try {
    $user = require_jwt_session();
} catch (Exception $e) {
    apiUnauthorized('Invalid session');
}

// Require POST method
requirePost();

// Get JSON input
$input = getJsonInput();

// Validate required fields
validateRequired($input, ['email', 'role']);

// Validate email format
if (!validateEmail($input['email'])) {
    apiValidationError(['email' => 'Invalid email format']);
}

// Process request...
$result = processUser($input);

// Return success
apiSuccess($result, 'User updated successfully');
?>
```

---

## Usage Examples

### Standard Admin Page Template

```php
<?php
/**
 * Example Admin Page
 * @version 1.0.0
 */

// Require JWT authentication
require_once 'jwt.php';
$user = require_jwt_session();

// Check permissions
if ($user->aud !== 'admin') {
    header('Location: dashboard.php?error=access_denied');
    exit();
}

// Set page title
$page_title = "Example Page";

// Include shared header
include 'includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Example Page</h1>
    <p class="page-description">This is an example page</p>
</div>

<div class="container">
    <div class="actions">
        <button class="btn btn-primary" onclick="doSomething()">Action</button>
        <a href="dashboard.php" class="btn btn-secondary">← Back</a>
    </div>

    <table class="users-table">
        <thead>
            <tr>
                <th>Column 1</th>
                <th>Column 2</th>
            </tr>
        </thead>
        <tbody>
            <!-- Table content -->
        </tbody>
    </table>
</div>

<script>
function doSomething() {
    showToast('Action performed!', 'success');
}
</script>

<?php include 'includes/footer.php'; ?>
```

### Standard API Endpoint Template

```php
<?php
/**
 * Example API Endpoint
 * @version 1.0.0
 */

require_once __DIR__ . '/includes/api_helpers.php';
require_once 'jwt.php';

// Authenticate
try {
    $user = require_jwt_session();
} catch (Exception $e) {
    apiUnauthorized('Invalid session');
}

// Check permissions
if ($user->aud !== 'admin') {
    apiForbidden('Admin access required');
}

// Require POST
requirePost();

// Get action
$action = getParam('action');

// Process action
switch ($action) {
    case 'create':
        $input = getJsonInput();
        validateRequired($input, ['name', 'value']);

        // Process...
        apiSuccess(['id' => 123], 'Created successfully', 201);
        break;

    case 'delete':
        $id = getIntParam('id');

        if (!$id) {
            apiError('ID is required', 400);
        }

        // Process...
        apiSuccess(null, 'Deleted successfully');
        break;

    default:
        apiError('Invalid action', 400);
}
?>
```

---

## Best Practices

### General Guidelines

1. **Always Include Header/Footer**
   ```php
   include 'includes/header.php';  // At top
   // Your content
   include 'includes/footer.php';  // At bottom
   ```

2. **Set Page Title**
   ```php
   $page_title = "Your Page Title";  // Before header include
   ```

3. **Validate Authentication**
   ```php
   $user = require_jwt_session();  // Before header include
   ```

4. **Use Shared Styles**
   - Don't duplicate CSS - use existing classes
   - Add page-specific styles in `<style>` tags only when necessary

5. **Use Shared JavaScript**
   - Use provided utility functions
   - Don't reimplement common operations

### API Development

1. **Use API Helpers**
   ```php
   require_once __DIR__ . '/includes/api_helpers.php';
   ```

2. **Standardize Responses**
   ```php
   apiSuccess($data);    // For success
   apiError($message);   // For errors
   ```

3. **Validate Input**
   ```php
   validateRequired($data, ['field1', 'field2']);
   ```

4. **Handle Errors Gracefully**
   ```php
   try {
       // Operation
       apiSuccess($result);
   } catch (Exception $e) {
       apiServerError('Operation failed', $e);
   }
   ```

### JavaScript Development

1. **Use Toast Instead of Alert**
   ```javascript
   // Bad
   alert('Success!');

   // Good
   showToast('Success!', 'success');
   ```

2. **Use Confirmation Helper**
   ```javascript
   // Bad
   if (confirm('Delete?')) { /* delete */ }

   // Good
   const confirmed = await confirmAction('Delete this item?');
   if (confirmed) { /* delete */ }
   ```

3. **Use Modal Helper**
   ```javascript
   // Open modal
   openModal('myModal');

   // Close modal
   closeModal('myModal');
   ```

4. **Use API Helpers**
   ```javascript
   // Good
   const data = await apiPost('endpoint.php', formData);
   if (data.success) {
       showToast('Saved!', 'success');
   }
   ```

### CSS Development

1. **Use Existing Classes**
   ```html
   <!-- Use shared classes -->
   <button class="btn btn-primary">Save</button>
   <div class="alert alert-success">Success</div>
   ```

2. **Follow Naming Convention**
   - Use BEM-style naming for new components
   - Example: `.component-name__element--modifier`

3. **Mobile First**
   - Design for mobile first
   - Use media queries for larger screens

---

## Maintenance

### Adding New Shared Components

1. Create file in `includes/` directory
2. Document in this README
3. Update header.php if auto-include needed
4. Test across multiple pages

### Modifying Existing Components

1. Test changes on all affected pages
2. Update version numbers
3. Update changelog
4. Document breaking changes

---

**Last Updated:** October 16, 2025
**Maintained By:** k33bz
