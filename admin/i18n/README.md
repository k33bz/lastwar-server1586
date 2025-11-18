# Admin Panel Internationalization (i18n) Guide

## Overview

The admin panel i18n system provides multi-language support for all user-facing content. It supports 5 languages with English as the default fallback.

**Supported Languages:**
- English (`en`) - Default
- Spanish (`es`)
- Portuguese (`pt`)
- German (`de`)
- Korean (`ko`)

## Architecture

### Directory Structure

```
admin/
├── includes/
│   └── i18n.php                    # Core i18n functions
├── i18n/
│   ├── README.md                   # This file
│   ├── en/
│   │   └── translations.json       # English translations
│   ├── es/
│   │   └── translations.json       # Spanish translations
│   ├── pt/
│   │   └── translations.json       # Portuguese translations
│   ├── de/
│   │   └── translations.json       # German translations
│   └── ko/
│       └── translations.json       # Korean translations
```

### Translation File Format

Translations are stored in JSON files with nested keys:

```json
{
  "help": {
    "president_approvals": {
      "title": "President Vote Approval Help",
      "sections": {
        "overview": {
          "title": "Overview",
          "intro": "As the server <strong>President</strong>..."
        }
      }
    }
  },
  "common": {
    "buttons": {
      "approve": "Approve",
      "reject": "Reject"
    }
  }
}
```

## Quick Start

### 1. Initialize i18n in header.php

Add this ONCE at the beginning of `admin/includes/header.php`:

```php
<?php
// Define base path for i18n system
define('ADMIN_BASE_PATH', dirname(__DIR__));

// Include i18n system
require_once __DIR__ . '/i18n.php';

// Handle language changes (must be before session_start)
i18n_handle_language_change();

// Initialize i18n
i18n_init();
?>
```

### 2. Use Translation Function

Use the `__()` function throughout your PHP code:

```php
// Simple translation
echo __('common.buttons.approve');
// Output: "Approve"

// Translation with parameters
echo __('forms.validation.min_length', ['length' => 5]);
// Output: "Must be at least 5 characters"

// Get nested value
echo __('help.president_approvals.title');
// Output: "President Vote Approval Help"
```

### 3. Add Language Switcher to Header

In your header template, add the language switcher widget:

```php
<nav>
    <!-- Existing navigation -->
    <div class="language-switcher-container">
        <?php echo i18n_render_language_switcher(); ?>
    </div>
</nav>
```

## Core Functions

### `i18n_init()`
Initializes the i18n system. Detects language and loads translations.

```php
i18n_init();
```

### `__($key, $params = [], $language = null)`
Main translation function. Returns translated string for given key.

```php
// Basic usage
$title = __('help.president_approvals.title');

// With parameters
$message = __('forms.validation.min_length', ['length' => 10]);

// Force specific language
$spanish = __('common.buttons.approve', [], 'es');
```

### `i18n_set_language($language)`
Changes current language.

```php
i18n_set_language('es'); // Switch to Spanish
i18n_set_language('en'); // Switch to English
```

### `i18n_get_current_language()`
Returns current language code.

```php
$current = i18n_get_current_language(); // Returns: 'en', 'es', etc.
```

### `i18n_get_supported_languages()`
Returns array of all supported languages with metadata.

```php
$languages = i18n_get_supported_languages();
// Returns: ['en' => ['code' => 'en', 'name' => 'English', ...], ...]
```

### `i18n_render_language_switcher()`
Returns HTML for language selection dropdown.

```php
echo i18n_render_language_switcher();
```

## Translation Keys Convention

Use dot notation for nested keys:

| **Pattern** | **Example** | **Description** |
|-------------|-------------|-----------------|
| `section.subsection.key` | `help.president.title` | General structure |
| `common.element.action` | `common.buttons.approve` | Reusable UI elements |
| `forms.type.field` | `forms.validation.required` | Form-related strings |
| `module.feature.text` | `help.president_approvals.overview.intro` | Feature-specific content |

### Recommended Key Organization

```json
{
  "common": {          // Reusable UI elements
    "buttons": {},
    "labels": {},
    "messages": {},
    "navigation": {}
  },
  "forms": {           // Form validation and labels
    "validation": {},
    "labels": {},
    "placeholders": {}
  },
  "help": {            // Help drawer content
    "president_approvals": {},
    "vote_proposals": {},
    "alliance_edit": {}
  },
  "pages": {           // Page-specific content
    "dashboard": {},
    "alliances": {},
    "votes": {}
  },
  "modals": {          // Modal dialogs
    "confirm": {},
    "alert": {},
    "success": {}
  }
}
```

## Parameter Interpolation

Use `{{parameter_name}}` syntax for dynamic values:

### JSON Translation:
```json
{
  "messages": {
    "welcome": "Welcome, {{name}}!",
    "items_count": "You have {{count}} items",
    "date_range": "From {{start_date}} to {{end_date}}"
  }
}
```

### PHP Usage:
```php
echo __('messages.welcome', ['name' => $user_name]);
// Output: "Welcome, John!"

echo __('messages.items_count', ['count' => 5]);
// Output: "You have 5 items"

echo __('messages.date_range', [
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31'
]);
// Output: "From 2025-01-01 to 2025-12-31"
```

## HTML in Translations

HTML is allowed in translations for formatting:

```json
{
  "help": {
    "note": "This is <strong>important</strong> information."
  }
}
```

**Output as-is (no escaping):**
```php
echo __('help.note');
// Output: "This is <strong>important</strong> information."
```

## Language Detection Priority

The system detects language in this order:

1. **Session** - User's selected language stored in `$_SESSION['language']`
2. **User Profile** (future) - Database-stored preference
3. **Browser** - `Accept-Language` header
4. **Default** - English (`en`)

## Adding New Translations

### Step 1: Add to English
Add the key to `admin/i18n/en/translations.json`:

```json
{
  "common": {
    "buttons": {
      "new_action": "New Action"
    }
  }
}
```

### Step 2: Translate to Other Languages
Add the same key to other language files with translated values:

**Spanish (`es/translations.json`):**
```json
{
  "common": {
    "buttons": {
      "new_action": "Nueva Acción"
    }
  }
}
```

**Portuguese (`pt/translations.json`):**
```json
{
  "common": {
    "buttons": {
      "new_action": "Nova Ação"
    }
  }
}
```

### Step 3: Use in Code
```php
echo __('common.buttons.new_action');
```

## Help Modal Integration

### Old Format (PHP Array):
```php
// admin/includes/help_content/example_help.php
return [
    'title' => 'Example Help',
    'sections' => [
        [
            'title' => 'Overview',
            'content' => '<p>Hardcoded English text...</p>'
        ]
    ]
];
```

### New Format (i18n):
```php
// admin/includes/help_content/example_help.php
return [
    'title' => __('help.example.title'),
    'sections' => [
        [
            'title' => __('help.example.sections.overview.title'),
            'content' => __('help.example.sections.overview.content')
        ]
    ]
];
```

**Translation File:**
```json
{
  "help": {
    "example": {
      "title": "Example Help",
      "sections": {
        "overview": {
          "title": "Overview",
          "content": "<p>Translatable content...</p>"
        }
      }
    }
  }
}
```

## Migration Guide

### For Existing PHP Pages

**Before:**
```php
<button>Approve</button>
<p>Welcome, <?php echo $user_name; ?>!</p>
```

**After:**
```php
<button><?php echo __('common.buttons.approve'); ?></button>
<p><?php echo __('messages.welcome', ['name' => $user_name]); ?></p>
```

### For Help Content Files

See "Help Modal Integration" section above.

## Translation Tools

### Using LM Studio for Translations

You can use the existing `translate_locale.py` script pattern from the client side:

```python
# Example: translate_admin.py
import json
import requests

def translate_admin_strings(source_lang='en', target_lang='es'):
    # Load English translations
    with open(f'admin/i18n/{source_lang}/translations.json') as f:
        source = json.load(f)

    # Translate using LM Studio API
    # ... (similar to client/translate_locale.py)

    # Save translated file
    with open(f'admin/i18n/{target_lang}/translations.json', 'w') as f:
        json.dump(translated, f, indent=2, ensure_ascii=False)
```

## Best Practices

### DO:
- ✅ Use consistent key naming (dot notation)
- ✅ Keep English as source of truth
- ✅ Use parameters for dynamic values
- ✅ Organize keys logically by feature/module
- ✅ Test with longer translated strings (German, Portuguese)
- ✅ Use HTML for formatting within translations

### DON'T:
- ❌ Hardcode user-facing text
- ❌ Concatenate translated strings with `+` or `.`
- ❌ Assume English text length applies to all languages
- ❌ Put code/logic in translation strings
- ❌ Use different parameters across languages for same key
- ❌ Forget to add keys to all language files

## Troubleshooting

### Missing Translation Key
**Symptom:** Page displays translation key instead of text (e.g., "common.buttons.approve")
**Solution:** Add the key to the appropriate language file in `admin/i18n/{lang}/translations.json`

### Wrong Language Displaying
**Symptom:** Page shows wrong language despite selection
**Solution:** Check session is started before `i18n_init()`. Clear browser cache.

### Translation Not Updating
**Symptom:** Changes to translation file not reflecting
**Solution:** Refresh the page or clear PHP opcode cache. Check JSON file for syntax errors.

### JSON Parse Error
**Symptom:** Error log shows JSON parse error
**Solution:** Validate JSON syntax using `python -m json.tool file.json`. Check for trailing commas, missing quotes.

## Error Handling

The i18n system gracefully handles errors:

- **Missing translation key**: Returns the key itself + logs error
- **Invalid language code**: Falls back to English
- **Corrupted JSON file**: Logs error, returns empty translations
- **Missing translation file**: Logs error, uses English fallback

All errors are logged to PHP error log for debugging.

## Testing

### Test Language Switching
```php
// Test script
i18n_set_language('en');
echo __('common.buttons.approve') . "\n"; // Should show "Approve"

i18n_set_language('es');
echo __('common.buttons.approve') . "\n"; // Should show "Aprobar"

i18n_set_language('pt');
echo __('common.buttons.approve') . "\n"; // Should show "Aprovar"
```

### Test Parameter Interpolation
```php
$result = __('messages.welcome', ['name' => 'Test']);
assert(strpos($result, 'Test') !== false);
```

### Validate JSON Files
```bash
# Check all translation files for valid JSON
for lang in en es pt de ko; do
    echo "Validating $lang..."
    python -m json.tool admin/i18n/$lang/translations.json > /dev/null
done
```

## Future Enhancements

- [ ] User profile language preference (database column)
- [ ] Translation management UI
- [ ] Import/export for translation services (Crowdin, Phrase)
- [ ] Automated translation via LM Studio
- [ ] Missing translation report
- [ ] RTL language support (if needed)
- [ ] Pluralization rules per language

## Support

For issues or questions:
1. Check this README
2. Review `admin/includes/i18n.php` source code
3. Check PHP error logs
4. Create GitHub issue with details

---

**Version:** 1.0.0
**Last Updated:** 2025-11-14
**Maintained by:** Server 1586 Development Team
