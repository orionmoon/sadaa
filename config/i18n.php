<?php
/**
 * Sadaa (صدى) - Internationalization System
 *
 * Simple PHP-based translation system using language files.
 */

// Supported languages
$GLOBALS['supported_locales'] = ['ar', 'fr', 'en', 'es', 'de', 'tr'];

/**
 * Detect language from cookie or browser Accept-Language header
 */
function detectLocale(): string
{
    $supported = $GLOBALS['supported_locales'];

    // 1. Check cookie first (user explicit choice)
    if (isset($_COOKIE['sadaa_lang']) && in_array($_COOKIE['sadaa_lang'], $supported)) {
        return $_COOKIE['sadaa_lang'];
    }

    // 2. Check Accept-Language header
    $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if ($acceptLang) {
        // Parse Accept-Language header (e.g., "en-US,en;q=0.9,fr;q=0.8")
        $languages = explode(',', $acceptLang);
        foreach ($languages as $lang) {
            // Extract language code (remove quality factor and region)
            $lang = trim($lang);
            if (strpos($lang, ';') !== false) {
                $lang = substr($lang, 0, strpos($lang, ';'));
            }
            // Check full code first (e.g., "en-US")
            $langCode = strtolower(substr($lang, 0, 2));
            if (in_array($langCode, $supported)) {
                return $langCode;
            }
        }
    }

    // 3. Default fallback to English
    return 'en';
}

// Get current language using detection logic
$GLOBALS['current_locale'] = detectLocale();

// Cache for loaded translations
$GLOBALS['translations'] = [];

/**
 * Load translations for a given language
 */
function loadTranslations(string $lang): array
{
    $langFile = __DIR__ . '/../lang/' . $lang . '.php';

    if (file_exists($langFile)) {
        return require $langFile;
    }

    // Fallback to French if language file doesn't exist
    $fallbackFile = __DIR__ . '/../lang/fr.php';
    if (file_exists($fallbackFile)) {
        return require $fallbackFile;
    }

    return [];
}

/**
 * Get translation for a key
 *
 * @param string $key The translation key (supports dot notation: 'nav.dashboard')
 * @param array $params Optional parameters for string interpolation
 * @param string|null $lang Override the current language
 * @return string The translated string or the key if not found
 */
function __(string $key, array $params = [], ?string $lang = null): string
{
    $lang = $lang ?? $GLOBALS['current_locale'];

    // Load translations if not cached
    if (!isset($GLOBALS['translations'][$lang])) {
        $GLOBALS['translations'][$lang] = loadTranslations($lang);
    }

    $translations = $GLOBALS['translations'][$lang];

    // Support dot notation (e.g., 'nav.dashboard')
    $keys = explode('.', $key);
    $value = $translations;

    foreach ($keys as $k) {
        if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
        } else {
            // Key not found, return the key itself
            return $key;
        }
    }

    // If we got an array instead of string, return key
    if (!is_string($value)) {
        return $key;
    }

    // Replace parameters (e.g., :name becomes the value of $params['name'])
    foreach ($params as $param => $replacement) {
        $value = str_replace(':' . $param, $replacement, $value);
    }

    return $value;
}

/**
 * Get an entire section of translations (returns array)
 */
function getTranslationSection(string $section, ?string $lang = null): array
{
    $lang = $lang ?? $GLOBALS['current_locale'];

    if (!isset($GLOBALS['translations'][$lang])) {
        $GLOBALS['translations'][$lang] = loadTranslations($lang);
    }

    return $GLOBALS['translations'][$lang][$section] ?? [];
}

/**
 * Get all translations for JavaScript usage
 * Returns a JSON-encodable array of translations
 */
function getJsTranslations(?string $lang = null): array
{
    $lang = $lang ?? $GLOBALS['current_locale'];

    if (!isset($GLOBALS['translations'][$lang])) {
        $GLOBALS['translations'][$lang] = loadTranslations($lang);
    }

    return $GLOBALS['translations'][$lang]['js'] ?? [];
}

/**
 * Get current locale
 */
function getCurrentLocale(): string
{
    return $GLOBALS['current_locale'];
}

/**
 * Set current locale
 */
function setCurrentLocale(string $lang): void
{
    $GLOBALS['current_locale'] = $lang;
}

/**
 * Check if current language is RTL
 */
function isRtl(?string $lang = null): bool
{
    $lang = $lang ?? $GLOBALS['current_locale'];
    return in_array($lang, ['ar', 'fa', 'ur', 'he']);
}
