# Alert and Confirm to Modal Replacements

**Date:** October 16, 2025
**Version:** 1.0.0

## Summary

This document provides search-and-replace patterns to convert all `alert()` and `confirm()` calls to modern modal dialogs across the admin panel.

---

## ✅ Already Completed

### Session Expiration (footer.php)
- **Status:** ✅ Complete
- Session warning now uses modal instead of confirm()
- Session refresh error now uses modal instead of alert()

---

## 🔄 Replacements Needed

### 1. User Deletion Confirmations

**Files:**
- `admin/admin_api.php:473`
- `admin/user_management.php:917`
- `admin/security_monitor.php:909`

**Pattern to Find:**
```javascript
onclick="return confirm('Are you sure you want to delete this user?')"
```

**Replace With:**
```javascript
onclick="event.preventDefault(); confirmAction('Are you sure you want to delete this user?\n\nThis action cannot be undone.', 'Delete User', {confirmText: 'Delete', cancelText: 'Cancel', dangerMode: true}).then(result => { if(result) this.closest('form').submit(); }); return false;"
```

---

### 2. Alliance Deletion Confirmations

**File:** `admin/alliances_power.php:713`

**Current Code:**
```javascript
if (!confirm(`Mark alliance "${tag}" for deletion?\n\nClick "Save All Changes" to permanently delete.`)) {
    return;
}
```

**Replace With:**
```javascript
const confirmed = await confirmAction(
    `Mark alliance "${tag}" for deletion?\n\nClick "Save All Changes" to permanently delete.`,
    'Delete Alliance',
    { confirmText: 'Mark for Deletion', cancelText: 'Cancel', dangerMode: true }
);
if (!confirmed) return;
```

---

### 3. Unsaved Changes Warning

**File:** `admin/alliances_power.php:730`

**Current Code:**
```javascript
if (!confirm('You have unsaved changes. Are you sure you want to reload?')) {
    return;
}
```

**Replace With:**
```javascript
const confirmed = await confirmAction(
    'You have unsaved changes. Are you sure you want to reload?',
    'Unsaved Changes',
    { confirmText: 'Reload Anyway', cancelText: 'Stay', dangerMode: true }
);
if (!confirmed) return;
```

---

### 4. Secret Key Rotation Confirmations

**File:** `admin/security_keys.php:241`

**Current Code:**
```javascript
onclick="return confirm('This will log out all users. Continue?')"
```

**Replace With:**
```javascript
onclick="event.preventDefault(); confirmAction('This will log out all users and invalidate all active sessions.\n\nContinue with rotation?', 'Rotate Secret Key', {confirmText: 'Rotate Now', cancelText: 'Cancel', dangerMode: true}).then(result => { if(result) this.closest('form').submit(); }); return false;"
```

**File:** `admin/security_keys.php:258`

**Current Code:**
```javascript
onclick="return confirm('EMERGENCY ROTATION: This will immediately invalidate all sessions and send security alerts. Are you sure?')"
```

**Replace With:**
```javascript
onclick="event.preventDefault(); confirmAction('EMERGENCY ROTATION\n\nThis will immediately:\n• Invalidate ALL active sessions\n• Log out ALL users\n• Send security alerts\n• Require everyone to re-authenticate\n\nThis is a critical security operation. Are you absolutely sure?', '🚨 Emergency Rotation', {confirmText: 'Emergency Rotate', cancelText: 'Cancel', dangerMode: true}).then(result => { if(result) this.closest('form').submit(); }); return false;"
```

---

### 5. Test Token Revocation

**File:** `admin/security_monitor.php:909`

**Current Code:**
```javascript
onsubmit="return confirm('Revoke this test token? This action cannot be undone.')"
```

**Replace With:**
```javascript
onsubmit="event.preventDefault(); confirmAction('Revoke this test token?\n\nThis action cannot be undone and will immediately invalidate the token.', 'Revoke Token', {confirmText: 'Revoke', cancelText: 'Cancel', dangerMode: true}).then(result => { if(result) this.submit(); }); return false;"
```

---

### 6. Copy Fallback Alerts (Replace with Toast)

**Files:**
- `admin/generate_magic_link.php:327`
- `admin/security_audit.php:663`
- `admin/user_management.php:1163`
- `admin/user_management.php:1188`

**Current Code:**
```javascript
alert('Failed to copy. Please select and copy manually.');
```

**Replace With:**
```javascript
showToast('Failed to copy automatically. Text has been selected - press Ctrl+C to copy.', 'warning');
```

---

### 7. Error Loading Raw Logs

**File:** `admin/security_audit.php:636`

**Current Code:**
```javascript
alert('Error loading raw logs: ' + error.message);
```

**Replace With:**
```javascript
alertModal('Error loading raw logs:\n\n' + error.message, 'Load Error', 'error');
```

---

### 8. User Management Errors

**File:** `admin/user_management.php:903, 907, 934, 938, 1036, 1040, 1079, 1083`

**Current Code (example):**
```javascript
alert('Error: ' + data.message);
```

**Replace With:**
```javascript
alertModal(data.message, 'Error', 'error');
```

**Current Code (example):**
```javascript
alert('Error updating user: ' + error);
```

**Replace With:**
```javascript
alertModal('Failed to update user:\n\n' + error, 'Update Error', 'error');
```

---

### 9. Form Validation Alerts

**File:** `admin/user_management.php:994, 1001, 1021`

**Current Code:**
```javascript
alert('Please enter an email address');
```

**Replace With:**
```javascript
alertModal('Please enter an email address.', 'Validation Error', 'warning');
```

**Current Code:**
```javascript
alert('Please enter a valid email address');
```

**Replace With:**
```javascript
alertModal('Please enter a valid email address.', 'Invalid Email', 'warning');
```

**Current Code:**
```javascript
alert('Please select at least one alliance');
```

**Replace With:**
```javascript
alertModal('Please select at least one alliance before adding the user.', 'Missing Alliance', 'warning');
```

---

## 📋 Implementation Steps

### Step 1: Ensure scripts.js is Loaded

All pages that use modals must load `includes/scripts.js`:

```php
<?php include 'includes/header.php'; ?>
<!-- Page content -->
<?php include 'includes/footer.php'; ?>
```

The header.php already includes scripts.js, so most pages are already covered.

### Step 2: Convert Inline onclick confirm()

For inline `onclick="return confirm(...)"`:

**Before:**
```html
<button onclick="return confirm('Delete?')">Delete</button>
```

**After:**
```html
<button onclick="event.preventDefault(); confirmAction('Delete this item?', 'Confirm Delete', {dangerMode: true}).then(result => { if(result) performDelete(); }); return false;">Delete</button>
```

### Step 3: Convert JavaScript confirm()

For JavaScript `if (confirm(...))`:

**Before:**
```javascript
if (confirm('Delete this item?')) {
    performDelete();
}
```

**After:**
```javascript
const confirmed = await confirmAction(
    'Delete this item?',
    'Confirm Delete',
    { dangerMode: true }
);
if (confirmed) {
    performDelete();
}
```

**Note:** Function must be `async` to use `await`.

### Step 4: Convert alert()

**Before:**
```javascript
alert('Operation failed: ' + error);
```

**After:**
```javascript
await alertModal('Operation failed:\n\n' + error, 'Error', 'error');
```

Or use toast for non-critical messages:
```javascript
showToast('Operation failed: ' + error, 'error');
```

---

## 🎨 Modal Options

### confirmAction() Options

```javascript
confirmAction(message, title, options)
```

**Parameters:**
- `message` (string): The confirmation question/message
- `title` (string): Modal title
- `options` (object):
  - `confirmText` (string): Text for confirm button (default: "Confirm")
  - `cancelText` (string): Text for cancel button (default: "Cancel")
  - `dangerMode` (boolean): Use red styling for destructive actions (default: false)

**Returns:** Promise<boolean>

**Examples:**

```javascript
// Standard confirmation
const result = await confirmAction(
    'Save changes?',
    'Confirm Save'
);

// Dangerous action (red button)
const result = await confirmAction(
    'Delete all data?',
    'Confirm Delete',
    { confirmText: 'Delete', dangerMode: true }
);

// Custom button text
const result = await confirmAction(
    'Discard unsaved changes?',
    'Unsaved Changes',
    { confirmText: 'Discard', cancelText: 'Keep Editing', dangerMode: true }
);
```

### alertModal() Options

```javascript
alertModal(message, title, type)
```

**Parameters:**
- `message` (string): The alert message
- `title` (string): Modal title
- `type` (string): Alert type: 'info', 'success', 'warning', 'error'

**Returns:** Promise<void>

**Examples:**

```javascript
// Info alert
await alertModal('Operation completed', 'Success', 'success');

// Error alert
await alertModal('Failed to save data', 'Error', 'error');

// Warning alert
await alertModal('This action cannot be undone', 'Warning', 'warning');
```

---

## 🧪 Testing

After replacements, test:

1. ✅ All confirmation dialogs appear as modals
2. ✅ Keyboard shortcuts work (ESC to cancel, Enter to confirm)
3. ✅ Click outside modal closes it
4. ✅ Danger mode shows red for destructive actions
5. ✅ Session warnings use modals
6. ✅ Error messages use modals or toasts
7. ✅ Form submissions still work after modal confirmation

---

## 📝 Notes

- **Toast vs Modal**: Use toasts for non-critical notifications, modals for important messages requiring acknowledgment
- **Async/Await**: All modal functions return Promises, so use `await` or `.then()`
- **Form Submissions**: Use `event.preventDefault()` and submit via JavaScript after confirmation
- **Accessibility**: Modals include keyboard support and focus management

---

**Last Updated:** October 16, 2025
**Maintained By:** k33bz
