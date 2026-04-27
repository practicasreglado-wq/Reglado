<?php
declare(strict_types=1);

/**
 * Registro de auditoría: persiste cada acción crítica en `audit_log`.
 *
 * Cualquier operación que cambie estado importante (crear/eliminar propiedad,
 * aprobar rol, cambiar estado de cita, etc.) debe llamar a `auditLog()` con
 * un código de acción consistente. Esos códigos los traduce a español el
 * frontend en `src/views/AdminAuditView.vue` (mapping ACTION_LABELS).
 *
 * El cron `cron/purge_audit_log.php` borra entradas más antiguas que
 * AUDIT_LOG_RETENTION_DAYS para que la tabla no crezca sin tope.
 */

/**
 * Inserta una entrada en `audit_log`.
 *
 * Parámetros:
 *  - $action: código corto y estable, ej. 'property.delete', 'role.promotion.approve'.
 *    Si añades uno nuevo, recuerda mapearlo en ACTION_LABELS de AdminAuditView.vue.
 *  - $context: array con metadatos opcionales:
 *      user_id, user_email, user_role  → quien hizo la acción
 *      resource_type, resource_id      → sobre qué la hizo
 *      success                         → bool, default true
 *      metadata                        → array libre, se serializa a JSON
 *
 * Truncamos el User-Agent a 500 chars porque hay clientes (bots, frameworks)
 * que mandan UAs absurdamente largos y la columna no es ilimitada.
 *
 * Diseñado para no romper nunca el flujo principal: si falla la inserción
 * solo se loguea con error_log(), no se propaga la excepción.
 */
function auditLog(PDO $pdo, string $action, array $context = []): void
{
    try {
        $stmt = $pdo->prepare('
            INSERT INTO audit_log
                (user_id, user_email, user_role, action, resource_type,
                 resource_id, ip_address, user_agent, success, metadata)
            VALUES
                (:uid, :email, :role, :action, :rtype, :rid, :ip, :ua, :ok, :meta)
        ');

        $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (strlen($userAgent) > 500) {
            $userAgent = substr($userAgent, 0, 500);
        }

        $metadata = $context['metadata'] ?? null;
        if (is_array($metadata)) {
            $metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $stmt->execute([
            'uid'    => isset($context['user_id']) ? (int) $context['user_id'] : null,
            'email'  => $context['user_email'] ?? null,
            'role'   => $context['user_role'] ?? null,
            'action' => $action,
            'rtype'  => $context['resource_type'] ?? null,
            'rid'    => isset($context['resource_id']) ? (string) $context['resource_id'] : null,
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua'     => $userAgent !== '' ? $userAgent : null,
            'ok'     => isset($context['success']) ? (int) (bool) $context['success'] : 1,
            'meta'   => $metadata,
        ]);
    } catch (Throwable $e) {
        error_log('[audit] fallo al registrar (' . $action . '): ' . $e->getMessage());
    }
}

/**
 * Atajo para construir las claves user_id/user_email/user_role de $context a
 * partir del payload del JWT que devuelve `requireAuthenticatedUser()`.
 *
 * Si pasas $userId explícito, lo prioriza sobre $auth['sub'] (útil cuando el
 * "actor" del audit no coincide con el usuario autenticado, ej. un admin
 * actuando sobre la cuenta de otro).
 */
function auditContextFromAuth(array $auth, ?int $userId = null): array
{
    return [
        'user_id'    => $userId ?? (isset($auth['sub']) ? (int) $auth['sub'] : null),
        'user_email' => $auth['email'] ?? null,
        'user_role'  => $auth['role'] ?? null,
    ];
}
