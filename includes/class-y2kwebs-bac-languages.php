<?php
defined( 'ABSPATH' ) || exit;

/** Regional WordPress translations take precedence; every Spanish locale has a bundled fallback. */
final class Y2KWEBS_BAC_Languages
{
    private const DOMAIN = 'y2kwebs-bac';
    private static array $catalogs = [];

    public static function register(): void
    {
        add_filter('gettext_y2kwebs-bac', [self::class, 'translate'], 10, 3);
    }

    public static function locale(): string
    {
        // Checkout AJAX is served through wp-admin, but must use the store language, not the buyer's admin preference.
        if (is_locale_switched() || (defined('WC_DOING_AJAX') && WC_DOING_AJAX)) {
            return get_locale();
        }
        return determine_locale();
    }

    public static function translate(string $translation, string $text, string $domain): string
    {
        if ($domain !== self::DOMAIN) { return $translation; }
        $locale = self::locale();
        // Core can cache an absent domain before a locale switch. Also select the store catalogue for checkout AJAX.
        $regional = preg_match('/^[A-Za-z0-9_-]+$/D', $locale)
            ? self::catalog(WP_LANG_DIR . '/plugins/' . self::DOMAIN . '-' . $locale . '.mo') : null;
        if ($locale !== determine_locale() || $translation === $text) {
            $translation = $regional ? $regional->translate($text) : $text;
        }
        if ($translation !== $text || !preg_match('/^es(?:[_-]|$)/i', $locale)) {
            return $translation;
        }
        $spanish = self::catalog(dirname(Y2KWEBS_BAC_FILE) . '/languages/' . self::DOMAIN . '-es_ES.mo');
        return $spanish ? $spanish->translate($text) : $translation;
    }

    private static function catalog(string $file): ?\MO
    {
        if (!array_key_exists($file, self::$catalogs)) {
            require_once ABSPATH . WPINC . '/pomo/mo.php';
            $catalog = new \MO();
            self::$catalogs[$file] = is_readable($file) && $catalog->import_from_file($file) ? $catalog : null;
        }
        return self::$catalogs[$file];
    }

}
