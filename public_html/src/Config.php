<?php
/**
 * Nyitott Múlt – Település és Névtár Konfigurációkezelő
 */

declare(strict_types=1);

namespace NyitottMult;

class Config
{
    private static ?array $siteConfig = null;
    private static array $gazetteerCache = [];

    /**
     * Település konfiguráció lekérése (config/site.yaml)
     */
    public static function getSiteConfig(): array
    {
        if (self::$siteConfig !== null) {
            return self::$siteConfig;
        }

        $filePath = SITE_CONFIG_PATH;
        if (!file_exists($filePath)) {
            // Alapértelmezett Orosháza konfiguráció
            self::$siteConfig = self::getDefaultConfig();
            return self::$siteConfig;
        }

        $content = file_get_contents($filePath);
        self::$siteConfig = self::parseSimpleYaml($content);
        return self::$siteConfig;
    }

    /**
     * Helytörténeti névtár fájl lekérése (JSON)
     */
    public static function getGazetteer(string $type): array
    {
        if (isset(self::$gazetteerCache[$type])) {
            return self::$gazetteerCache[$type];
        }

        $path = GAZETTEER_DIR . '/' . $type . '.json';
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            if (is_array($data)) {
                self::$gazetteerCache[$type] = $data;
                return $data;
            }
        }

        return [];
    }

    /**
     * Városrészek listája
     */
    public static function getDistricts(): array
    {
        return self::getGazetteer('districts');
    }

    /**
     * Korabeli utcanevek története
     */
    public static function getStreetHistory(): array
    {
        return self::getGazetteer('street_history');
    }

    /**
     * Korabeli intézmények és vállalatok
     */
    public static function getOrganizations(): array
    {
        return self::getGazetteer('organizations');
    }

    /**
     * Korabeli állambiztonsági egységek
     */
    public static function getStateSecurityUnits(): array
    {
        return self::getGazetteer('state_security');
    }

    /**
     * Fedőnevek mintája
     */
    public static function getCodenames(): array
    {
        return self::getGazetteer('codenames');
    }

    /**
     * Beépített egyszerű YAML értelmező (nincs szükség PECL yaml modulra)
     */
    private static function parseSimpleYaml(string $yaml): array
    {
        // Ha van yaml_parse, azt használjuk
        if (function_exists('yaml_parse')) {
            $parsed = @yaml_parse($yaml);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        // Robusztus fallback Orosháza értékekkel
        return [
            'site' => [
                'id' => 'oroshaza',
                'name' => 'Orosháza titkos múltja',
                'subtitle' => 'Az ügynökakták nyomában',
                'initiator' => [
                    'name' => 'Kátai Attila',
                    'title' => 'AI specialista, projektgazda',
                    'contact' => 'katai.attila@example.hu'
                ]
            ],
            'settlement' => [
                'name' => 'Orosháza',
                'county' => 'Békés vármegye',
                'country' => 'Magyarország',
                'center' => [
                    'lat' => 46.5658,
                    'lng' => 20.6634
                ],
                'initial_zoom' => 13
            ],
            'map' => [
                'tile_provider' => 'openfreemap',
                'tile_url' => 'https://tiles.openfreemap.org/styles/bright/{z}/{x}/{y}.png'
            ],
            'privacy' => [
                'death_plus_years' => 5,
                'living_persons_default_hidden' => true,
                'private_residence_blur_to_district' => true,
                'dual_verification_required_for_public_claims' => true
            ]
        ];
    }

    private static function getDefaultConfig(): array
    {
        return self::parseSimpleYaml('');
    }
}
