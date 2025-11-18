# Email Internationalization (i18n)

## Overview

The admin panel email system supports automatic language detection and translation for all email templates. Emails are sent in the user's preferred language based on their browser's `Accept-Language` header.

## Supported Languages

- **English (en)** 🇺🇸 - Default
- **Spanish (es)** 🇪🇸
- **Portuguese (pt)** 🇧🇷
- **German (de)** 🇩🇪
- **Korean (ko)** 🇰🇷

## Email Templates

All email templates support i18n:

1. **Magic Link Email** - Login authentication emails
2. **Role Change Email** - Permission update notifications
3. **Test Email** - SMTP configuration testing
4. **Council Rotation Email** - Council schedule updates

## How It Works

### Automatic Language Detection

```php
// Email is sent automatically in user's language
send_magic_link_email($email, $magic_link_url);

// Language is detected from HTTP_ACCEPT_LANGUAGE header
// Falls back to English if language not supported
```

### Manual Language Override

```php
// Force email to be sent in Spanish
send_magic_link_email($email, $magic_link_url, null, 'es');

// Force email to be sent in Portuguese
send_role_change_email($to, $changes, $changed_by, 'pt');

// Force email to be sent in German
send_test_email($to, 'de');

// Force email to be sent in Korean
send_council_rotation_notification($to, $stats, $regenerated_by, 'ko');
```

## Email Function Signatures

### Magic Link Email
```php
send_magic_link_email(
    string $to,              // Recipient email
    string $magic_link_url,  // Magic link URL
    string $username = null, // Optional username (defaults to email prefix)
    string $language = null  // Optional language override (auto-detect if null)
): bool
```

### Role Change Email
```php
send_role_change_email(
    string $to,              // Recipient email
    array $changes,          // Array of changes (role, alliances, powereditor)
    string $changed_by,      // Admin who made the change
    string $language = null  // Optional language override (auto-detect if null)
): bool
```

### Test Email
```php
send_test_email(
    string $to,              // Recipient email
    string $language = null  // Optional language override (auto-detect if null)
): bool
```

### Council Rotation Email
```php
send_council_rotation_notification(
    string $to,              // Recipient email
    array $stats,            // Regeneration statistics
    string $regenerated_by,  // Admin who regenerated
    string $language = null  // Optional language override (auto-detect if null)
): bool
```

## Translation Keys

All email translations are stored in:
```
admin/i18n/{language}/translations.json
```

Under the `emails` section:
- `emails.magic_link.*` - Magic link email strings
- `emails.role_change.*` - Role change email strings
- `emails.test.*` - Test email strings
- `emails.council_rotation.*` - Council rotation email strings

## Adding New Translations

To add email support for a new language:

1. Create translation file: `admin/i18n/{lang}/translations.json`
2. Copy the `emails` section from `admin/i18n/en/translations.json`
3. Translate all strings, preserving `{{placeholders}}`
4. Run translation script: `python translate_admin.py {lang}`

## Example Translations

### English (en)
```json
"emails": {
  "magic_link": {
    "subject": "Your Login Link for {{app_name}}",
    "greeting": "Hello {{username}},",
    "button": "🚀 Access Admin Dashboard"
  }
}
```

### Spanish (es)
```json
"emails": {
  "magic_link": {
    "subject": "Su enlace de inicio de sesión para {{app_name}}",
    "greeting": "Hola {{username}},",
    "button": "🚀 Acceso al Panel de Control Administrativo"
  }
}
```

### Portuguese (pt)
```json
"emails": {
  "magic_link": {
    "subject": "Seu link de login para {{app_name}}",
    "greeting": "Olá {{username}},",
    "button": "🚀 Acessar Painel de Administração"
  }
}
```

## Testing

To test email translations:

```php
// Test with browser language detection
send_test_email('test@example.com');

// Test specific language
send_test_email('test@example.com', 'es'); // Spanish
send_test_email('test@example.com', 'pt'); // Portuguese
send_test_email('test@example.com', 'de'); // German
send_test_email('test@example.com', 'ko'); // Korean
```

## Implementation Details

### Language Detection Priority

1. **Explicit language parameter** - If provided in function call
2. **Browser Accept-Language header** - Parsed from HTTP headers
3. **Default fallback** - English (en)

### Translation Loading

The `email_load_i18n($language)` helper function:
- Loads the i18n system
- Parses `Accept-Language` header if language not specified
- Loads translation file for detected language
- Returns the language code used

### Parameter Interpolation

Translation strings support parameter placeholders:

```php
// Translation: "Hello {{username}}, welcome to {{app_name}}"
__('emails.magic_link.greeting', [
    'username' => 'John',
    'app_name' => 'Admin Panel'
]);
// Result: "Hello John, welcome to Admin Panel"
```

## Files Modified

- `admin/mailer.php` - Email functions with i18n support
- `admin/i18n/en/translations.json` - Base English translations
- `admin/i18n/es/translations.json` - Spanish translations
- `admin/i18n/pt/translations.json` - Portuguese translations
- `admin/i18n/de/translations.json` - German translations
- `admin/i18n/ko/translations.json` - Korean translations

## Notes

- All email HTML templates preserve formatting and styling
- Emojis are preserved in all translations
- Parameter placeholders use `{{variable}}` syntax
- Security warnings and notices are fully translated
- Email subjects are translated
- Footer text and legal notices are translated

## Version

- **Implementation Date**: November 15, 2025
- **Total Translation Keys**: 61 email-specific keys
- **Languages Supported**: 5 (en, es, pt, de, ko)
- **Email Templates**: 4 templates fully internationalized
