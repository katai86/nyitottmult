<?php
/**
 * Nyitott Múlt – Adatbázis Kezelő (PDO MySQL / MariaDB)
 */

declare(strict_types=1);

namespace NyitottMult;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static ?string $lastError = null;

    /**
     * Adatbázis kapcsolat lekérése (Singleton)
     */
    public static function getConnection(): ?PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            return self::$instance;
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            error_log('[DB Error] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Kapcsolati hibaüzenet lekérése
     */
    public static function getLastError(): ?string
    {
        return self::$lastError;
    }

    /**
     * Előkészített lekérdezés futtatása paraméterekkel
     */
    public static function query(string $sql, array $params = []): array
    {
        $db = self::getConnection();
        if (!$db) {
            return [];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Egyetlen rekord lekérdezése
     */
    public static function queryOne(string $sql, array $params = []): ?array
    {
        $rows = self::query($sql, $params);
        return !empty($rows) ? $rows[0] : null;
    }

    /**
     * Beszúrás vagy módosítás végrehajtása
     */
    public static function execute(string $sql, array $params = []): bool
    {
        $db = self::getConnection();
        if (!$db) {
            return false;
        }

        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * UUIDv4 generátor
     */
    public static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
