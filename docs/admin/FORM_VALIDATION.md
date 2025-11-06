# Form Validation System

**Version:** 1.0.0
**Last Updated:** 2025-11-06
**Implemented:** v3.1.0

Client-side form validation library for admin panel with real-time feedback and visual indicators.

---

## Table of Contents

1. [Overview](#overview)
2. [Quick Start](#quick-start)
3. [Validators Reference](#validators-reference)
4. [Usage Patterns](#usage-patterns)
5. [Integration Examples](#integration-examples)
6. [Custom Validation](#custom-validation)
7. [Visual Styling](#visual-styling)
8. [Best Practices](#best-practices)

---

## Overview

The form validation system provides:
- **Real-time validation** - Validate on blur, clear errors on input
- **Visual feedback** - Red borders and error messages for invalid fields
- **Reusable validators** - Matching PHP backend validators
- **Easy integration** - Add `data-validate` attributes to fields
- **Automatic form validation** - Validates all fields on submit

**Location:** `admin/includes/scripts.js` (lines 349-741)

---

## Quick Start

### 1. Add Validation Attributes to HTML

```html
<form id="myForm">
    <div class="form-group">
        <label>Email:</label>
        <input type="email"
               name="email"
               data-validate="email"
               required
               placeholder="user@example.com">
    </div>

    <div class="form-group">
        <label>Alliance Tag:</label>
        <input type="text"
               name="tag"
               data-validate="alliance-tag"
               required
               placeholder="UvvU">
    </div>

    <button type="submit">Save</button>
</form>
```

### 2. Initialize Validation in JavaScript

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myForm');
    attachValidation(form);
});
```

### 3. Done!

Validation now runs automatically:
- **On blur** - Field validates when user leaves it
- **On input** - Error clears as user types
- **On submit** - All fields validate before submission

---

## Validators Reference

### Alliance-Specific Validators

#### `alliance-tag`
Validates alliance tags (2-10 characters, alphanumeric uppercase).

```html
<input type="text" data-validate="alliance-tag" required>
```

**Rules:**
- 2-10 characters
- Alphanumeric only (A-Z, 0-9)
- Auto-converts to uppercase

**Errors:**
- "Alliance tag must be 2-10 characters"
- "Alliance tag must contain only letters and numbers"

---

#### `alliance-name`
Validates alliance names (3-100 characters).

```html
<input type="text" data-validate="alliance-name" required>
```

**Rules:**
- 3-100 characters
- Any characters allowed

**Errors:**
- "Alliance name must be at least 3 characters"
- "Alliance name must not exceed 100 characters"

---

#### `alliance-power`
Validates alliance power values (0 to 10 trillion).

```html
<input type="number" data-validate="alliance-power" required>
```

**Rules:**
- Must be numeric
- 0 to 10,000,000,000,000 (10 trillion)

**Errors:**
- "Power must be a valid number"
- "Power cannot be negative"
- "Power exceeds maximum allowed value (10 trillion)"

---

#### `r5-name`
Validates R5 names (3-50 characters).

```html
<input type="text" data-validate="r5-name" required>
```

**Rules:**
- 3-50 characters
- Any characters allowed

**Errors:**
- "R5 name must be at least 3 characters"
- "R5 name must not exceed 50 characters"

---

### General Validators

#### `email`
Validates email addresses.

```html
<input type="email" data-validate="email" required>
```

**Rules:**
- Standard email format (user@domain.tld)

**Errors:**
- "Email is required" (if required)
- "Invalid email format"

---

#### `url`
Validates URL format.

```html
<input type="url" data-validate="url">
```

**Rules:**
- Valid URL format (https://example.com)
- Optional unless `required` attribute present

**Errors:**
- "URL is required" (if required)
- "Invalid URL format"

---

#### `text`
Validates text fields with min/max length.

```html
<input type="text"
       data-validate="text"
       data-min="3"
       data-max="100"
       required>
```

**Attributes:**
- `data-min` - Minimum length (default: 0)
- `data-max` - Maximum length (default: 1000)
- `required` - Whether field is required

**Errors:**
- "This field is required" (if required)
- "Must be at least {min} characters"
- "Must not exceed {max} characters"

---

#### `number`
Validates numeric fields with min/max range.

```html
<input type="number"
       data-validate="number"
       data-min="0"
       data-max="1000"
       required>
```

**Attributes:**
- `data-min` - Minimum value (default: 0)
- `data-max` - Maximum value (default: Number.MAX_SAFE_INTEGER)
- `required` - Whether field is required

**Errors:**
- "This field is required" (if required)
- "Must be a valid number"
- "Must be at least {min}"
- "Must not exceed {max}"

---

#### `discord-channel-id`
Validates Discord channel IDs (18-20 digits).

```html
<input type="text" data-validate="discord-channel-id">
```

**Rules:**
- 18-20 digits
- Numeric only

**Errors:**
- "Discord channel ID is required" (if required)
- "Discord channel ID must be 18-20 digits"

---

## Usage Patterns

### Pattern 1: Simple Form with Auto-Validation

```html
<form id="userForm">
    <input type="email" name="email" data-validate="email" required>
    <button type="submit">Save</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    attachValidation(document.getElementById('userForm'));
});
</script>
```

**Features:**
- Validates on blur
- Clears errors on input
- Prevents submit if invalid
- Focuses first invalid field
- Shows toast notification

---

### Pattern 2: Manual Validation Before API Call

```javascript
function saveUser() {
    const form = document.getElementById('userForm');

    // Validate all fields
    if (!validateForm(form)) {
        const firstInvalid = form.querySelector('.is-invalid');
        if (firstInvalid) {
            firstInvalid.focus();
        }
        return;
    }

    // All valid, proceed with API call
    const formData = new FormData(form);
    fetch('user_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('User saved successfully!', 'success');
        }
    });
}
```

---

### Pattern 3: Dynamic Form Fields (Inline Editing)

```javascript
function renderTable() {
    const tbody = document.querySelector('#myTable tbody');
    tbody.innerHTML = '';

    data.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="text"
                       value="${item.tag}"
                       data-validate="alliance-tag"
                       required>
            </td>
        `;
        tbody.appendChild(row);
    });

    // Attach validation to newly created inputs
    const inputs = tbody.querySelectorAll('input[data-validate]');
    inputs.forEach(input => {
        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('input', () => {
            if (input.classList.contains('is-invalid')) {
                clearFieldError(input);
            }
        });
    });
}
```

---

### Pattern 4: Single Field Validation

```javascript
const emailField = document.getElementById('email');

// Validate single field
if (validateField(emailField)) {
    console.log('Email is valid!');
} else {
    console.log('Email has errors');
}
```

---

## Integration Examples

### User Management Form

**File:** `admin/user_management.php`

```html
<form id="addUserForm">
    <div class="form-group">
        <label>Email:</label>
        <input type="email"
               id="addEmail"
               name="email"
               required
               data-validate="email"
               placeholder="user@example.com">
    </div>
    <button type="button" onclick="addUser()">Add User</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addForm = document.getElementById('addUserForm');
    if (addForm) {
        attachValidation(addForm);
    }
});

function addUser() {
    const form = document.getElementById('addUserForm');

    // Validate form
    if (!validateForm(form)) {
        const firstInvalid = form.querySelector('.is-invalid');
        if (firstInvalid) {
            firstInvalid.focus();
        }
        return;
    }

    // Proceed with API call...
}
</script>
```

---

### Alliance Power Editor

**File:** `admin/alliances_power.php`

```javascript
function renderAlliances() {
    alliances.forEach((alliance, rank) => {
        row.innerHTML = `
            <td>
                <input type="text"
                       value="${alliance.tag}"
                       data-validate="alliance-tag"
                       required>
            </td>
            <td>
                <input type="text"
                       value="${alliance.name}"
                       data-validate="alliance-name"
                       required>
            </td>
            <td>
                <input type="number"
                       value="${alliance.power}"
                       data-validate="alliance-power"
                       required
                       min="0"
                       max="10000000000000">
            </td>
        `;
        tbody.appendChild(row);
    });

    // Attach validation
    const inputs = tbody.querySelectorAll('input[data-validate]');
    inputs.forEach(input => {
        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('input', () => {
            if (input.classList.contains('is-invalid')) {
                clearFieldError(input);
            }
        });
    });
}

function saveAlliances() {
    // Validate all inputs before saving
    const inputs = document.querySelectorAll('#alliancesTable input[data-validate]');
    let hasErrors = false;

    inputs.forEach(input => {
        if (!validateField(input)) {
            hasErrors = true;
        }
    });

    if (hasErrors) {
        showToast('Please fix validation errors before saving', 'error');
        return;
    }

    // Proceed with save...
}
```

---

## Custom Validation

### Adding Custom Validators

Edit `admin/includes/scripts.js` and add your validator function:

```javascript
/**
 * Validate custom field
 * @param {string} value - Value to validate
 * @param {boolean} required - Whether field is required
 * @returns {ValidationResult}
 */
function validateCustomField(value, required = false) {
    const sanitized = value.trim();

    if (!sanitized && !required) {
        return { valid: true, error: '' };
    }

    if (!sanitized && required) {
        return { valid: false, error: 'This field is required' };
    }

    // Your custom validation logic
    if (sanitized.length < 5) {
        return { valid: false, error: 'Must be at least 5 characters' };
    }

    return { valid: true, error: '' };
}
```

Then add it to the `validateField()` switch statement:

```javascript
function validateField(field) {
    const validateType = field.dataset.validate;
    const required = field.hasAttribute('required');
    const value = field.value;

    let result = { valid: true, error: '' };

    switch (validateType) {
        case 'custom':
            result = validateCustomField(value, required);
            break;
        // ... other cases ...
    }

    // Show/hide error ...
}
```

### Using Custom Validators

```html
<input type="text" data-validate="custom" required>
```

---

## Visual Styling

### CSS Classes

Validation adds these classes automatically:

- **`.is-invalid`** - Field with error (red border, pink background)
- **`.is-valid`** - Field successfully validated (green border, light green background)
- **`.invalid-feedback`** - Error message element (red text below field)

### Styles Applied

```css
.is-invalid {
    border-color: #dc3545 !important;
    background-color: #fff5f5 !important;
}

.is-valid {
    border-color: #28a745 !important;
    background-color: #f0fff4 !important;
}

.invalid-feedback {
    color: #dc3545;
    font-size: 0.875em;
    margin-top: 0.25rem;
    display: block;
}
```

### Customizing Styles

Override in your page's CSS:

```css
/* Custom invalid field styling */
.is-invalid {
    border-color: #ff0000 !important;
    box-shadow: 0 0 5px rgba(255, 0, 0, 0.5);
}

/* Custom error message styling */
.invalid-feedback {
    color: #cc0000;
    font-weight: bold;
}
```

---

## Best Practices

### 1. Always Use `data-validate` Attribute

```html
<!-- ✅ Good -->
<input type="text" data-validate="alliance-tag" required>

<!-- ❌ Bad - no validation -->
<input type="text" required>
```

### 2. Validate Before API Calls

```javascript
function saveData() {
    // ✅ Validate first
    if (!validateForm(form)) {
        return;
    }

    // Then make API call
    fetch('api.php', { /* ... */ });
}
```

### 3. Attach Validation to Dynamic Forms

```javascript
function renderDynamicForm() {
    // Render form fields...

    // ✅ Attach validation after rendering
    const inputs = form.querySelectorAll('input[data-validate]');
    inputs.forEach(input => {
        input.addEventListener('blur', () => validateField(input));
    });
}
```

### 4. Use Specific Validators

```html
<!-- ✅ Specific validator -->
<input type="text" data-validate="alliance-tag">

<!-- ❌ Generic validator -->
<input type="text" data-validate="text" data-min="2" data-max="10">
```

### 5. Combine with Server-Side Validation

Client-side validation is for UX. **Always validate on server too.**

```php
// Server-side validation (required!)
$result = validate_alliance_tag($tag);
if (!$result['valid']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $result['error']]);
    exit;
}
```

### 6. Use `required` Attribute

```html
<!-- ✅ Required field -->
<input type="text" data-validate="email" required>

<!-- ⚠️ Optional field -->
<input type="text" data-validate="email">
```

### 7. Clear Errors on Modal Close

```javascript
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    const form = modal.querySelector('form');

    if (form) {
        // Clear validation errors
        form.querySelectorAll('.is-invalid').forEach(field => {
            clearFieldError(field);
        });
    }

    modal.style.display = 'none';
}
```

---

## API Reference

### Functions

#### `attachValidation(form)`
Attach real-time validation to all fields in a form.

```javascript
const form = document.getElementById('myForm');
attachValidation(form);
```

---

#### `validateForm(form)`
Validate all fields in a form. Returns `true` if all valid.

```javascript
if (validateForm(form)) {
    // Proceed
} else {
    // Show error
}
```

---

#### `validateField(field)`
Validate a single field. Returns `true` if valid.

```javascript
const emailField = document.getElementById('email');
if (validateField(emailField)) {
    console.log('Valid!');
}
```

---

#### `showFieldError(field, error)`
Manually show an error on a field.

```javascript
showFieldError(emailField, 'This email is already taken');
```

---

#### `clearFieldError(field)`
Clear error from a field.

```javascript
clearFieldError(emailField);
```

---

#### `markFieldValid(field)`
Mark a field as valid (green border).

```javascript
markFieldValid(emailField);
```

---

## Related Documentation

- [Admin Panel Architecture](ARCHITECTURE.md)
- [UI/UX Guidelines](UI_UX_GUIDELINES.md)
- [JavaScript Patterns](JAVASCRIPT_PATTERNS.md)
- [Loading States & Toast Notifications](../LOADING_STATES.md)

---

## Issue Reference

- **GitHub Issue:** #20 - Add form validation to all admin forms
- **Milestone:** v3.1.0 - Performance & UX
- **Implemented:** 2025-11-06

---

**Maintained By:** k33bz
**Last Updated:** 2025-11-06
**Documentation Version:** 1.0.0
