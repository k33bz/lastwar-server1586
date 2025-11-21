# Admin Panel Internationalization (i18n) Implementation

## Overview

Complete implementation of multi-language support for the Server 1586 admin panel, covering 5 languages: English (EN), Spanish (ES), Portuguese (PT), German (DE), and Korean (KO).

## Features

### Translation System
- **Function**: `__('translation.key')` - Simple dot notation for accessing translations
- **Storage**: JSON files in `admin/i18n/{lang}/translations.json`
- **Auto-loading**: User's language preference loaded from JWT on every page
- **Fallback**: English (EN) used when translation key not found

### Language Selection
- **Login Page**: Language selector dropdown
- **User Profile**: Users can change preferred language
- **Persistence**: Language stored in user profile (`users.json`)
- **Session**: Language stored in JWT token for fast access

### Email Localization
- **Magic Link Emails**: Sent in user's preferred language
- **Templates**: Fully translated email templates with preserved formatting
- **Variables**: Template variables (e.g., `{{app_name}}`) properly preserved

### Technical Terms Preservation
The translation system preserves technical terms that should not be translated:
- Game roles: R5, R4, APE
- Systems: Discord, SMTP, JWT, API, NAP15
- Alliance tags: UvvU, ORCE, MTOP, FNXS, MZKU
- User roles: admin, president, council
- Emojis: 🚀, 📋, 🛡️, ⚠️, ✅, 🗳️, 📅, 📊, ℹ️

## File Structure

```
admin/
├── i18n/
│   ├── en/
│   │   └── translations.json    # English (source)
│   ├── es/
│   │   └── translations.json    # Spanish
│   ├── pt/
│   │   └── translations.json    # Portuguese
│   ├── de/
│   │   └── translations.json    # German
│   └── ko/
│       └── translations.json    # Korean
│
├── includes/
│   ├── i18n.php                 # Translation engine
│   └── header.php               # Language switcher UI
│
├── jwt.php                      # Language in JWT tokens
├── callback.php                 # Language loading on login
├── json_helpers.php             # User language preference
├── profile_api.php              # Language update endpoint
├── mailer.php                   # Email localization
└── [admin pages]                # Pages using __() function
```

## Translation File Structure

```json
{
  "pages": {
    "dashboard": {
      "title": "Dashboard",
      "description": "Alliance Admin Portal"
    }
  },
  "common": {
    "buttons": {
      "save": "Save",
      "cancel": "Cancel",
      "submit": "Submit"
    },
    "labels": {
      "email": "Email",
      "password": "Password"
    },
    "alliance": {
      "r5": "R5 (Leader)",
      "r4": "R4 (Officer)",
      "ape": "APE (Power Editor)"
    }
  },
  "login": {
    "page_title": "Admin Login",
    "form": {
      "email_label": "Alliance Email Address",
      "submit_button": "Send Magic Link"
    }
  },
  "emails": {
    "magic_link": {
      "subject": "Your Login Link for {{app_name}}",
      "greeting": "Hello {{username}},"
    }
  }
}
```

## Usage Examples

### In PHP Files

```php
// Page title
$page_title = __('pages.dashboard.title');

// Button text
echo __('common.buttons.save');

// With variables
echo __('emails.magic_link.greeting', ['username' => 'John']);

// Nested keys
echo __('common.alliance.r5');
```

### Language Priority

1. **JWT Token** - Language stored in session token
2. **User Profile** - Language preference in `users.json`
3. **Browser Header** - `Accept-Language` HTTP header
4. **Default** - English (EN)

## Translation Workflow

### Automated Translation

Use the `translate_admin_smart.py` script:

```bash
python translate_admin_smart.py
```

**Requirements:**
- LM Studio running on `localhost:1234`
- Hunyuan-MT-7B translation model loaded

**Process:**
1. Reads English translations (`admin/i18n/en/translations.json`)
2. Translates each section to target languages
3. Preserves technical terms using glossary
4. Cleans up any instruction text from LLM responses
5. Saves to language-specific files

### Manual Translation

1. Edit `admin/i18n/en/translations.json` (add/modify English text)
2. Run translation script to propagate changes
3. Review translated files for accuracy
4. Test in admin panel with language selector

## Pages Translated

Currently, the following pages use the translation system:

1. `login.php` - Login page with language selector
2. `dashboard.php` - Main dashboard
3. `user_management.php` - User administration
4. `alliance_edit.php` - Alliance editing
5. `user_profile.php` - User profile with language preference
6. `alliances_power.php` - Power updates
7. `council_rotation.php` - Council rotation management
8. `discord_announcements.php` - Discord announcements
9. `votes_management.php` - Vote management
10. `alliance_tags_manager.php` - Alliance tags

**Note:** ~40 additional admin pages need page title translation.

## Email Templates

### Magic Link Email

**Sections:**
- Subject line
- Greeting with username
- Introduction text
- Call-to-action button
- Alternative link text
- Security information (5 bullet points)
- No-reply notice
- Footer with signature

**Variable Replacement:**
- `{{app_name}}` - Application name
- `{{username}}` - User's name (extracted from email)
- `{{email}}` - User's email address

### Role Change Email

**Sections:**
- Subject line
- Greeting
- Change summary
- "What Changed" section
- Change details (role, power editor, alliances)
- Questions and access text
- Footer

## API Endpoints

### Language Preference

**Update User Language:**
```
POST /admin/profile_api.php?action=update
{
  "preferred_language": "es"
}
```

**Supported Values:**
- `en` - English
- `es` - Spanish (Español)
- `pt` - Portuguese (Português)
- `de` - German (Deutsch)
- `ko` - Korean (한국어)

## Implementation Details

### Session Management

**JWT Token Structure:**
```json
{
  "sub": "user@example.com",
  "aud": "admin",
  "lang": "es",
  "exp": 1234567890
}
```

**Language Loading:**
```php
// In require_jwt_session()
if (isset($token->lang)) {
    i18n_load_translations($token->lang);
}
```

### User Profile Storage

**users.json Structure:**
```json
{
  "users": [
    {
      "email": "user@example.com",
      "role": "admin",
      "preferred_language": "es",
      "alliances": ["UvvU"]
    }
  ]
}
```

### Translation Function

**Core Implementation:**
```php
function __($key, $replacements = []) {
    global $i18n_translations;

    // Split dot notation
    $keys = explode('.', $key);
    $value = $i18n_translations;

    // Navigate nested structure
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $key; // Fallback
        }
        $value = $value[$k];
    }

    // Replace variables
    foreach ($replacements as $placeholder => $replacement) {
        $value = str_replace('{{'.$placeholder.'}}', $replacement, $value);
    }

    return $value;
}
```

## Testing

### Manual Testing

1. Start development server:
   ```bash
   python dev_server.py
   ```

2. Access admin panel: http://localhost:8000/admin/login.php

3. Select language from dropdown

4. Request magic link

5. Check email for translated content

6. Log in and verify page translations

7. Change language in user profile

8. Verify language persists across sessions

### Translation Quality

Check for:
- ✅ Technical terms preserved (R5, Discord, etc.)
- ✅ HTML tags intact
- ✅ Variable placeholders unchanged ({{app_name}})
- ✅ Formatting preserved (newlines, spacing)
- ✅ Emojis displayed correctly
- ✅ Context-appropriate translations

## Known Issues

### Fixed Issues

1. **Login Page Showing Raw Prompts** - FIXED
   - **Problem**: LM Studio was returning instruction text
   - **Solution**: Simplified prompts, added cleanup post-processing
   - **Commit**: Current session

2. **Inefficient Translation** - FIXED
   - **Problem**: Individual API calls for each string
   - **Solution**: Batch translation by section
   - **Performance**: 2-3 minutes vs 15+ minutes

### Pending Work

1. **Help Drawer Content** (~13 files)
   - Complex dynamic PHP content
   - Role-based conditional text
   - Postponed for future implementation

2. **Remaining Admin Pages** (~40 pages)
   - Need page title translation
   - Main UI elements hardcoded in English
   - JavaScript messages need translation

3. **Client-Side Messages**
   - Form validation errors
   - AJAX response messages
   - Alert dialogs

## Future Enhancements

1. **Right-to-Left (RTL) Support**
   - Add Arabic language support
   - CSS direction adjustments

2. **Translation Management UI**
   - Web interface for editing translations
   - Real-time preview
   - Translation status tracking

3. **Pluralization**
   - Support for plural forms
   - Language-specific plural rules

4. **Date/Time Localization**
   - Format dates per locale
   - Timezone handling

5. **Help Drawer Translation**
   - Extract dynamic content
   - Conditional translation
   - Role-based language

## Resources

### Documentation
- `admin/EMAIL_I18N.md` - Email translation guide
- `CLAUDE.md` - Development workflow
- `README.md` - Project overview

### Translation Scripts
- `translate_admin_smart.py` - Admin UI translation
- `translate_rules.py` - Client rules translation
- `translate_locale.py` - Client locale translation

### LM Studio
- **API Endpoint**: http://localhost:1234/v1/chat/completions
- **Model**: Tencent Hunyuan-MT-7B
- **Temperature**: 0.1 (for consistency)
- **Max Tokens**: 2048

---

**Last Updated**: November 16, 2025
**Status**: ✅ Core Implementation Complete
**Coverage**: Login + 9 Admin Pages + Email System
