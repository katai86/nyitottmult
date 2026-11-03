<?php
/**
 * Nyitott Múlt – Audit Naplózó Rendszer (Audit Log)
 */

declare(strict_types=1);

namespace NyitottMult;

class AuditLogger
{
    /**
     * Változás naplózása az audit_logs táblába
     */
    public static function log(
        string $action,
        string $tableName,
        string $recordId,
        ?array $changes = null,
        ?string $userId = null
    ): void {
        try {
            $id = Database::generateUuid();
            $effectiveUserId = $userId ?: Auth::id();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            // IP cím utolsó oktettjének maszkolása a GDPR adatminimalizálás miatt
            $ipParts = explode('.', $ipAddress);
            if (count($ipParts) === 4) {
                $ipParts[3] = 'xxx';
                $ipAddress = implode('.', $ipParts);
            }

            $sql = "INSERT INTO audit_logs (id, user_id, action, table_name, record_id, changes, ip_address, created_at)
                    VALUES (:id, :uid, :action, :tbl, :rec, :changes, :ip, NOW())";

            Database::execute($sql, [
                'id' => $id,
                'uid' => $effectiveUserId,
                'action' => $action,
                'tbl' => $tableName,
                'rec' => $recordId,
                'changes' => $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
                'ip' => $ipAddress
            ]);
        } catch (\Throwable $e) {
            error_log('[AuditLog Error] ' . $e->getMessage());
        }
    }
}
