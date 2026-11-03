<?php
/**
 * Nyitott Múlt – Többnyelvűségi Kezelő (i18n)
 */

declare(strict_types=1);

namespace NyitottMult;

class I18n
{
    private static string $currentLocale = 'hu';
    private static array $translations = [];

    /**
     * Nyelv beállítása (URL query, session vagy alapértelmezett)
     */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (isset($_GET['lang']) && in_array($_GET['lang'], ['hu', 'en'], true)) {
            self::$currentLocale = $_GET['lang'];
            $_SESSION['lang'] = self::$currentLocale;
        } elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['hu', 'en'], true)) {
            self::$currentLocale = $_SESSION['lang'];
        } else {
            self::$currentLocale = defined('DEFAULT_LOCALE') ? DEFAULT_LOCALE : 'hu';
        }

        self::loadLocale(self::$currentLocale);
    }

    /**
     * Aktuális nyelv lekérése
     */
    public static function getLocale(): string
    {
        return self::$currentLocale;
    }

    /**
     * Nyelvi fájl betöltése
     */
    private static function loadLocale(string $locale): void
    {
        $filePath = dirname(__DIR__) . '/locales/' . $locale . '.json';
        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);
            if (is_array($data)) {
                self::$translations[$locale] = $data;
            }
        }
    }

    /**
     * Szöveg lekérése pontozott kulcs alapján (pl. 'nav.home', 'roles.megfigyelt')
     */
    public static function trans(string $key, array $replace = []): string
    {
        $locale = self::$currentLocale;
        if (!isset(self::$translations[$locale])) {
            self::loadLocale($locale);
        }

        $parts = explode('.', $key);
        $current = self::$translations[$locale] ?? [];

        foreach ($parts as $part) {
            if (is_array($current) && isset($current[$part])) {
                $current = $current[$part];
            } else {
                // Visszaesés a magyar nyelvre
                return self::fallbackTrans($key, $replace);
            }
        }

        $text = is_string($current) ? $current : $key;

        foreach ($replace as $placeholder => $value) {
            $text = str_replace('{' . $placeholder . '}', (string)$value, $text);
        }

        return $text;
    }

    private static function fallbackTrans(string $key, array $replace = []): string
    {
        if (self::$currentLocale !== 'hu') {
            if (!isset(self::$translations['hu'])) {
                self::loadLocale('hu');
            }
            $parts = explode('.', $key);
            $current = self::$translations['hu'] ?? [];
            foreach ($parts as $part) {
                if (is_array($current) && isset($current[$part])) {
                    $current = $current[$part];
                } else {
                    return $key;
                }
            }
            $text = is_string($current) ? $current : $key;
            foreach ($replace as $placeholder => $value) {
                $text = str_replace('{' . $placeholder . '}', (string)$value, $text);
            }
            return $text;
        }

        return $key;
    }
}

/**
 * Globális segédfüggvény
 */
function __(string $key, array $replace = []): string
{
    return I18n::trans($key, $replace);
}
