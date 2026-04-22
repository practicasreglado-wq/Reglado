<?php
declare(strict_types=1);

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

function auditContextFromAuth(array $auth, ?int $userId = null): array
{
    return [
        'user_id'    => $userId ?? (isset($auth['sub']) ? (int) $auth['sub'] : null),
        'user_email' => $auth['email'] ?? null,
        'user_role'  => $auth['role'] ?? null,
    ];
}
