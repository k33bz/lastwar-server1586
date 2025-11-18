<?php
/**
 * i18n (Internationalization) System for Admin Panel
 *
 * Provides translation functions and language management for the admin site.
 * Supports 5 languages: English, Spanish, Portuguese, German, Korean
 *
 * @version 1.0.0
 * @author Server 1586 Team
 */

// Prevent direct access
if (!defined('ADMIN_BASE_PATH')) {
    die('Direct access not permitted');
}

/**
 * Global variables for i18n system
 */
global $i18n_current_language;
global $i18n_translations;
global $i18n_supported_languages;

/**
 * Supported languages configuration
 */
$i18n_supported_languages = [
    'en' => [
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'flag' => '🇺🇸',
        'direction' => 'ltr'
    ],
    'es' => [
        'code' => 'es',
        'name' => 'Spanish',
        'native_name' => 'Español',
        'flag' => '🇪🇸',
        'direction' => 'ltr'
    ],
    'pt' => [
        'code' => 'pt',
        'name' => 'Portuguese',
        'native_name' => 'Português',
        'flag' => '🇧🇷',
        'direction' => 'ltr'
    ],
    'de' => [
        'code' => 'de',
        'name' => 'German',
        'native_name' => 'Deutsch',
        'flag' => '🇩🇪',
        'direction' => 'ltr'
    ],
    'ko' => [
        'code' => 'ko',
        'name' => 'Korean',
        'native_name' => '한국어',
        'flag' => '🇰🇷',
        'direction' => 'ltr'
    ]
];

/**
 * Default language
 */
define('I18N_DEFAULT_LANGUAGE', 'en');

/**
 * Initialize i18n system
 * Should be called early in the request lifecycle (e.g., in header.php)
 *
 * @return void
 */
function i18n_init() {
    global $i18n_current_language;

    // Determine user's language preference
    $i18n_current_language = i18n_detect_language();

    // Load translations for current language
    i18n_load_translations($i18n_current_language);

    // Set session language if not already set
    if (!isset($_SESSION['language'])) {
        $_SESSION['language'] = $i18n_current_language;
    }
}

/**
 * Detect user's preferred language
 * Priority: 1. Session, 2. User profile (TODO), 3. Browser, 4. Default
 *
 * @return string Language code (e.g., 'en', 'es')
 */
function i18n_detect_language() {
    global $i18n_supported_languages;

    // 1. Check session
    if (isset($_SESSION['language']) && isset($i18n_supported_languages[$_SESSION['language']])) {
        return $_SESSION['language'];
    }

    // 2. Check user profile (TODO: implement when user preferences are added)
    // if (isset($_SESSION['user']) && isset($_SESSION['user']['preferred_language'])) {
    //     return $_SESSION['user']['preferred_language'];
    // }

    // 3. Check browser language
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browser_lang = i18n_parse_browser_language();
        if ($browser_lang && isset($i18n_supported_languages[$browser_lang])) {
            return $browser_lang;
        }
    }

    // 4. Default to English
    return I18N_DEFAULT_LANGUAGE;
}

/**
 * Parse browser's Accept-Language header
 *
 * @return string|null Language code or null if not found
 */
function i18n_parse_browser_language() {
    global $i18n_supported_languages;

    $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $langs = explode(',', $accept_language);

    foreach ($langs as $lang) {
        // Extract language code (e.g., "en-US" -> "en")
        $lang_code = strtolower(substr(trim($lang), 0, 2));

        if (isset($i18n_supported_languages[$lang_code])) {
            return $lang_code;
        }
    }

    return null;
}

/**
 * Load translations for a specific language
 *
 * @param string $language Language code
 * @return bool True if loaded successfully, false otherwise
 */
function i18n_load_translations($language) {
    global $i18n_translations;
    global $i18n_supported_languages;

    // Validate language code
    if (!isset($i18n_supported_languages[$language])) {
        error_log("i18n: Unsupported language code: $language");
        $language = I18N_DEFAULT_LANGUAGE;
    }

    // Build path to translation file
    $translation_file = dirname(__DIR__) . "/i18n/$language/translations.json";

    // Load translation file
    if (file_exists($translation_file)) {
        $json = file_get_contents($translation_file);
        $i18n_translations = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("i18n: JSON parse error in $translation_file: " . json_last_error_msg());
            $i18n_translations = [];
            return false;
        }

        return true;
    } else {
        error_log("i18n: Translation file not found: $translation_file");
        $i18n_translations = [];
        return false;
    }
}

/**
 * Get translated string by key
 * Main translation function - use this throughout the codebase
 *
 * @param string $key Translation key (e.g., 'help.president.title')
 * @param array $params Optional parameters for string interpolation
 * @param string|null $language Optional language override
 * @return string Translated string or key if not found
 */
function __($key, $params = [], $language = null) {
    global $i18n_translations;
    global $i18n_current_language;

    // Handle language override
    if ($language !== null && $language !== $i18n_current_language) {
        $saved_lang = $i18n_current_language;
        $saved_translations = $i18n_translations;

        i18n_load_translations($language);
        $result = __($key, $params);

        // Restore previous language
        $i18n_current_language = $saved_lang;
        $i18n_translations = $saved_translations;

        return $result;
    }

    // Get translation from loaded translations
    $translation = i18n_get_nested_value($i18n_translations, $key);

    // Fallback to key if translation not found
    if ($translation === null) {
        error_log("i18n: Missing translation for key: $key (language: $i18n_current_language)");
        $translation = $key;
    }

    // Apply parameters if provided
    if (!empty($params)) {
        $translation = i18n_interpolate($translation, $params);
    }

    return $translation;
}

/**
 * Get nested value from array using dot notation
 * Example: i18n_get_nested_value($array, 'help.president.title')
 *
 * @param array $array Source array
 * @param string $key Dot-notation key
 * @return mixed|null Value or null if not found
 */
function i18n_get_nested_value($array, $key) {
    $keys = explode('.', $key);
    $value = $array;

    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return null;
        }
        $value = $value[$k];
    }

    return $value;
}

/**
 * Interpolate parameters into translated string
 * Supports {{param}} placeholder syntax
 *
 * @param string $string Template string with {{placeholders}}
 * @param array $params Associative array of parameter values
 * @return string Interpolated string
 */
function i18n_interpolate($string, $params) {
    foreach ($params as $key => $value) {
        $string = str_replace("{{" . $key . "}}", $value, $string);
    }
    return $string;
}

/**
 * Get current language code
 *
 * @return string Current language code (e.g., 'en')
 */
function i18n_get_current_language() {
    global $i18n_current_language;
    return $i18n_current_language ?? I18N_DEFAULT_LANGUAGE;
}

/**
 * Set current language
 *
 * @param string $language Language code
 * @return bool True if successful, false otherwise
 */
function i18n_set_language($language) {
    global $i18n_current_language;
    global $i18n_supported_languages;

    if (!isset($i18n_supported_languages[$language])) {
        return false;
    }

    $i18n_current_language = $language;
    $_SESSION['language'] = $language;

    // Reload translations
    return i18n_load_translations($language);
}

/**
 * Get all supported languages
 *
 * @return array Array of language configurations
 */
function i18n_get_supported_languages() {
    global $i18n_supported_languages;
    return $i18n_supported_languages;
}

/**
 * Get current language configuration
 *
 * @return array Language configuration array
 */
function i18n_get_current_language_config() {
    global $i18n_supported_languages;
    $current = i18n_get_current_language();
    return $i18n_supported_languages[$current] ?? $i18n_supported_languages[I18N_DEFAULT_LANGUAGE];
}

/**
 * Render language switcher widget
 * Returns HTML for language selection dropdown
 *
 * @return string HTML for language switcher
 */
function i18n_render_language_switcher() {
    global $i18n_supported_languages;
    $current_lang = i18n_get_current_language();
    $current_config = i18n_get_current_language_config();

    ob_start();
    ?>
    <div class="language-switcher">
        <button type="button" class="language-switcher-button" onclick="toggleLanguageDropdown()" aria-label="Select Language">
            <span class="language-flag"><?php echo $current_config['flag']; ?></span>
            <span class="language-name"><?php echo $current_config['native_name']; ?></span>
            <svg class="dropdown-icon" width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                <path d="M2 4l4 4 4-4"/>
            </svg>
        </button>
        <div class="language-dropdown" id="languageDropdown" style="display: none;">
            <?php foreach ($i18n_supported_languages as $lang_code => $lang_config): ?>
                <a href="?set_language=<?php echo $lang_code; ?>"
                   class="language-option <?php echo $lang_code === $current_lang ? 'active' : ''; ?>"
                   onclick="setLanguage('<?php echo $lang_code; ?>'); return false;">
                    <span class="language-flag"><?php echo $lang_config['flag']; ?></span>
                    <span class="language-name"><?php echo $lang_config['native_name']; ?></span>
                    <?php if ($lang_code === $current_lang): ?>
                        <svg class="checkmark" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M13 3L6 10L3 7"/>
                        </svg>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    function toggleLanguageDropdown() {
        const dropdown = document.getElementById('languageDropdown');
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }

    function setLanguage(language) {
        fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=set_language&language=' + language
        })
        .then(() => {
            window.location.reload();
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const switcher = document.querySelector('.language-switcher');
        if (switcher && !switcher.contains(event.target)) {
            document.getElementById('languageDropdown').style.display = 'none';
        }
    });
    </script>

    <style>
    .language-switcher {
        position: relative;
        display: inline-block;
    }

    .language-switcher-button {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: #ffffff;
        color: #333333;
        border: 1px solid #ddd;
        border-radius: 0.375rem;
        cursor: pointer;
        font-size: 0.875rem;
        font-family: inherit;
        transition: all 0.2s;
    }

    .language-switcher-button:hover {
        background: #f5f5f5;
        border-color: #999;
    }

    .language-flag {
        font-size: 1.125rem;
        line-height: 1;
    }

    .language-name {
        color: #333;
        font-weight: 500;
    }

    .dropdown-icon {
        stroke: currentColor;
        stroke-width: 2;
        fill: none;
    }

    .language-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 0.25rem;
        min-width: 200px;
        background: #ffffff;
        border: 1px solid #ddd;
        border-radius: 0.375rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        overflow: hidden;
    }

    .language-option {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.625rem 0.75rem;
        text-decoration: none;
        color: #333;
        transition: background 0.2s;
        cursor: pointer;
    }

    .language-option:hover {
        background: #f5f5f5;
    }

    .language-option.active {
        background: #e0f2fe;
        color: #0284c7;
    }

    .language-option.active .language-name {
        color: #0284c7;
        font-weight: 600;
    }

    .checkmark {
        margin-left: auto;
        stroke: currentColor;
        stroke-width: 2;
        fill: none;
    }

    /* Dark theme support */
    body.dark-theme .language-switcher-button {
        background: #2d3748;
        color: #e2e8f0;
        border-color: #4a5568;
    }

    body.dark-theme .language-switcher-button:hover {
        background: #374151;
        border-color: #6b7280;
    }

    body.dark-theme .language-name {
        color: #e2e8f0;
    }

    body.dark-theme .language-dropdown {
        background: #2d3748;
        border-color: #4a5568;
    }

    body.dark-theme .language-option {
        color: #e2e8f0;
    }

    body.dark-theme .language-option:hover {
        background: #374151;
    }

    body.dark-theme .language-option.active {
        background: #1e40af;
        color: #93c5fd;
    }

    body.dark-theme .language-option.active .language-name {
        color: #93c5fd;
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Handle language change request
 * Call this at the beginning of header.php to handle POST requests
 *
 * @return void
 */
function i18n_handle_language_change() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_language') {
        if (isset($_POST['language'])) {
            i18n_set_language($_POST['language']);
        }
    }
}
