# Loading States & Toast Notifications

Global loading overlay and toast notification system for admin panel.

## Overview

The admin panel now includes a global loading overlay and toast notification system that provides consistent UX across all pages.

**Added in:** v3.1.0
**Location:** `admin/includes/footer.php`
**Global Objects:** `window.Loading`, `window.showToast()`

## Loading Overlay

### Usage

```javascript
// Show loading overlay
Loading.show('Saving changes...');

// Update loading message
Loading.update('Processing data...');

// Hide loading overlay
Loading.hide();
```

### Example: API Call with Loading

```javascript
async function saveData() {
    Loading.show('Saving alliance data...');

    try {
        const response = await fetch('api/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast('Data saved successfully!', 'success');
        } else {
            showToast('Error: ' + result.error, 'error');
        }
    } catch (error) {
        showToast('Network error occurred', 'error');
    } finally {
        Loading.hide();
    }
}
```

### Example: Multi-Step Process

```javascript
async function processBackup() {
    Loading.show('Creating backup...');

    try {
        // Step 1: Create backup
        await createBackup();
        Loading.update('Compressing files...');

        // Step 2: Compress
        await compressBackup();
        Loading.update('Uploading backup...');

        // Step 3: Upload
        await uploadBackup();
        Loading.hide();

        showToast('Backup completed successfully!', 'success');
    } catch (error) {
        Loading.hide();
        showToast('Backup failed: ' + error.message, 'error');
    }
}
```

## Toast Notifications

### Usage

```javascript
showToast(message, type);
```

### Parameters

- **message** (string): The notification message
- **type** (string): Type of notification
  - `'success'` - Green gradient (success operations)
  - `'error'` - Red gradient (errors, failures)
  - `'warning'` - Orange gradient (warnings, cautions)
  - `'info'` - Blue gradient (informational messages)

### Examples

```javascript
// Success notification
showToast('User created successfully!', 'success');

// Error notification
showToast('Failed to save changes', 'error');

// Warning notification
showToast('Session expiring soon', 'warning');

// Info notification
showToast('Data refreshed', 'info');
```

### Toast Behavior

- **Auto-dismiss:** Toasts automatically disappear after 3 seconds
- **Position:** Top-right corner (below header)
- **Z-index:** 10000 (above loading overlay)
- **Mobile:** Responsive, adjusts width on small screens

## Integration Examples

### Alliance Power Editor

```javascript
function saveAlliances() {
    Loading.show('Saving alliance power data...');

    fetch('alliances_power_api.php?action=update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify({ alliances: updates })
    })
    .then(response => response.json())
    .then(data => {
        Loading.hide();
        if (data.success) {
            showToast('Alliance data saved successfully!', 'success');
            loadAlliances();
        } else {
            showToast('Error: ' + data.error, 'error');
        }
    })
    .catch(error => {
        Loading.hide();
        showToast('Network error occurred', 'error');
    });
}
```

### User Management

```javascript
function deleteUser(email) {
    Loading.show('Deleting user...');

    fetch('user_api.php?action=delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify({ email })
    })
    .then(response => response.json())
    .then(data => {
        Loading.hide();
        if (data.success) {
            showToast('User deleted successfully', 'success');
            refreshUserList();
        } else {
            showToast('Failed to delete user', 'error');
        }
    })
    .catch(error => {
        Loading.hide();
        showToast('Error: ' + error.message, 'error');
    });
}
```

### Security Backups

```javascript
function createBackup() {
    Loading.show('Creating backup...');

    fetch('security_backups.php?action=create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
        }
    })
    .then(response => response.json())
    .then(data => {
        Loading.hide();
        if (data.success) {
            showToast('Backup created: ' + data.filename, 'success');
            loadBackupList();
        } else {
            showToast('Backup failed: ' + data.error, 'error');
        }
    })
    .catch(error => {
        Loading.hide();
        showToast('Backup error: ' + error.message, 'error');
    });
}
```

### Audit Log Loading

```javascript
function loadAuditLogs(page = 1) {
    Loading.show('Loading audit logs...');

    fetch(`security_audit.php?action=list&page=${page}`)
        .then(response => response.json())
        .then(data => {
            Loading.hide();
            renderAuditLogs(data.logs);
            updatePagination(data.totalPages);
        })
        .catch(error => {
            Loading.hide();
            showToast('Failed to load audit logs', 'error');
        });
}
```

## Best Practices

### 1. Always Use Try-Finally

```javascript
try {
    Loading.show('Processing...');
    await processData();
    showToast('Success!', 'success');
} catch (error) {
    showToast('Error: ' + error.message, 'error');
} finally {
    Loading.hide(); // Always hide, even on error
}
```

### 2. Descriptive Loading Messages

```javascript
// ❌ Not specific enough
Loading.show('Loading...');

// ✅ Clear and specific
Loading.show('Loading alliance power data...');
Loading.show('Saving user permissions...');
Loading.show('Creating backup archive...');
```

### 3. Update Messages for Long Operations

```javascript
Loading.show('Step 1/3: Fetching data...');
await fetchData();

Loading.update('Step 2/3: Processing...');
await processData();

Loading.update('Step 3/3: Saving...');
await saveData();

Loading.hide();
```

### 4. Toast for Quick Feedback

```javascript
// Use toast for immediate actions
showToast('Item copied to clipboard', 'success');
showToast('Changes saved', 'success');
showToast('Permission denied', 'error');
```

### 5. Combine Loading + Toast

```javascript
// Show loading during operation
Loading.show('Deleting alliance...');

try {
    await deleteAlliance(tag);
    showToast('Alliance deleted successfully', 'success');
} catch (error) {
    showToast('Failed to delete: ' + error.message, 'error');
} finally {
    Loading.hide();
}
```

## Styling

The loading overlay and toasts use the admin panel's design system:

- **Loading Overlay:**
  - Background: rgba(0, 0, 0, 0.7) with 3px blur
  - Spinner: Purple gradient (#667eea)
  - Animation: Smooth slide-in

- **Toast Colors:**
  - Success: Green gradient (#27ae60 → #229954)
  - Error: Red gradient (#e74c3c → #c0392b)
  - Warning: Orange gradient (#f39c12 → #e67e22)
  - Info: Blue gradient (#3498db → #2980b9)

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- IE11+ (degrades gracefully without backdrop-filter)
- Mobile responsive

## Migration from Inline Loading

### Before (Inline Loading)

```javascript
document.getElementById('loadingIndicator').style.display = 'block';
// ... operation ...
document.getElementById('loadingIndicator').style.display = 'none';
```

### After (Global Loading)

```javascript
Loading.show('Loading data...');
// ... operation ...
Loading.hide();
```

### Benefits

- ✅ Consistent UX across all pages
- ✅ No need to create per-page loading divs
- ✅ Blocks user interaction during operations
- ✅ Easy to update loading messages
- ✅ Toast notifications for quick feedback
- ✅ Mobile responsive
- ✅ Accessible (focus management)

## Related Documentation

- [Admin Panel Architecture](ARCHITECTURE.md)
- [UI/UX Guidelines](UI_UX_GUIDELINES.md)
- [JavaScript Patterns](JAVASCRIPT_PATTERNS.md)

## Issue Reference

- **GitHub Issue:** #18 - Add loading states to all API calls
- **Milestone:** v3.1.0 - Performance & UX
- **Implemented:** 2025-11-06
