<?php
/**
 * Nyitott Múlt – Adatvédelmi és Közzétehetőségi Motor
 * 
 * 2026. évi LI. törvény 14. § és információs önrendelkezési jog:
 * - Halál éve + 5 év elévülési szabály
 * - Élő vagy ismeretlen élethelyzetű személyek kötelező elfedése (anonimizálása)
 * - Magánlakás koordináták és címek kötelező térbeli elmosása
 * - Kétlépcsős lektori jóváhagyási ellenőrzés (Dual Verification)
 */

declare(strict_types=1);

namespace NyitottMult;

class PrivacyEngine
{
    /**
     * Ellenőrzi, hogy egy személy a törvény szerint nyilvánosan közzétehető-e
     */
    public static function isPersonPubliclyVisible(?string $status, ?int $deathYear): bool
    {
        // Csak igazoltan elhunyt személy vizsgálható
        if ($status !== 'deceased' || $deathYear === null) {
            return false;
        }

        $currentYear = (int)date('Y');
        $releaseYear = $deathYear + PRIVACY_DEATH_PLUS_YEARS;

        return $releaseYear <= $currentYear;
    }

    /**
     * Egy entitás adatainak anonimizálása és szűrése a nyilvánosság számára
     */
    public static function sanitizeEntityForPublic(array $entity, ?array $privacyRecord = null): array
    {
        // Ha szervezet, helyszín vagy fedőnév, nincs személyi adatvédelmi korlátozás
        if (in_array($entity['type'], ['organization', 'place', 'codename'], true)) {
            return $entity;
        }

        // Ha a törvény vagy lektori jóváhagyás alapján kifejezetten nyilvánosnak minősített (pl. ÁBTL által közzétett levéltári irat)
        if (!empty($privacyRecord['is_publicly_visible'])) {
            return $entity;
        }

        $status = $privacyRecord['status'] ?? 'unknown';
        $deathYear = isset($privacyRecord['death_year']) ? (int)$privacyRecord['death_year'] : null;

        $isPublic = self::isPersonPubliclyVisible($status, $deathYear);

        if (!$isPublic) {
            // Anonimizálás
            $entity['canonical_name'] = __('privacy.protected_person');
            $entity['aliases'] = json_encode([]);
            $entity['metadata'] = json_encode([
                'note' => __('privacy.protected_notice'),
                'is_protected' => true
            ]);
            $entity['time_range_start'] = null;
            $entity['time_range_end'] = null;
            $entity['is_protected'] = true;
        } else {
            $entity['is_protected'] = false;
        }

        return $entity;
    }

    /**
     * Térbeli objektum (helyszín, lakás) koordinátáinak és címének biztonsági elmosása
     */
    public static function sanitizeGeometryForPublic(array $geometry): array
    {
        // Ha magánlakás, a pontos koordináta nem adható ki nyilvánosan!
        if (!empty($geometry['is_private_residence'])) {
            // A publikus koordinátát adjuk vissza (ami a városrész közepe)
            $geometry['latitude'] = (float)$geometry['public_latitude'];
            $geometry['longitude'] = (float)$geometry['public_longitude'];
            $geometry['address_text'] = __('privacy.private_residence_blurred');
            $geometry['is_blurred'] = true;
        } else {
            $geometry['is_blurred'] = false;
        }

        return $geometry;
    }

    /**
     * Ellenőrzi, hogy egy állítás (Claim) rendelkezik-e a szükséges 2 független jóváhagyással
     */
    public static function hasDualVerification(string $claimId): bool
    {
        $sql = "SELECT COUNT(DISTINCT verifier_id) as verifier_count 
                FROM claim_verifications 
                WHERE claim_id = :claim_id AND decision = 'approved'";

        $result = Database::queryOne($sql, ['claim_id' => $claimId]);
        return ($result && (int)$result['verifier_count'] >= 2);
    }
}
