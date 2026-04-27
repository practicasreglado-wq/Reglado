<?php
declare(strict_types=1);

require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/email_layout.php';
require_once dirname(__DIR__) . '/send_mail.php';

/**
 * Notifica a todos los administradores sobre una nueva solicitud de
 * eliminación de propiedad: in-app + email. El botón "Revisar" lleva a la
 * sección de solicitudes pendientes del admin.
 */
function notifyAdminsOfDeletionRequest(
    PDO $pdo,
    int $requestId,
    int $propertyId,
    string $propertyTitle,
    string $requesterName,
    ?string $reason
): int {
    $admins = fetchAdminUsersForDeletion();
    if (empty($admins)) {
        error_log('[property_deletion] No se encontraron admins a notificar.');
        return 0;
    }

    $frontendUrl = rtrim((string) (getenv('FRONTEND_URL') ?: 'http://localhost:5175'), '/');
    $actionPath = '/admin/pending-requests';
    $absoluteUrl = $frontendUrl . $actionPath;

    $title = 'Nueva solicitud de eliminación de propiedad';
    $inAppMessage = sprintf(
        '%s ha pedido eliminar "%s" (#%d). Revisa la solicitud para aprobarla o rechazarla.',
        $requesterName !== '' ? $requesterName : 'Un usuario',
        $propertyTitle !== '' ? $propertyTitle : 'Propiedad sin título',
        $propertyId
    );

    $safeReason = $reason !== null && trim($reason) !== ''
        ? htmlspecialchars(trim($reason), ENT_QUOTES, 'UTF-8')
        : 'Sin motivo especificado.';
    $safeRequester = htmlspecialchars($requesterName !== '' ? $requesterName : 'Un usuario', ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($propertyTitle !== '' ? $propertyTitle : 'Propiedad sin título', ENT_QUOTES, 'UTF-8');

    $emailHtml = renderEmailLayout(
        $title,
        'Requiere tu aprobación',
        <<<HTML
<p style="margin:0 0 16px;color:#0f172a;font-size:15px;line-height:1.5;">
    <strong>{$safeRequester}</strong> ha solicitado eliminar la siguiente propiedad:
</p>
<div style="margin:0 0 20px;padding:14px 16px;border-left:4px solid #dc2626;background:#fef2f2;border-radius:6px;color:#1e293b;font-size:14px;">
    <div><strong>Propiedad:</strong> {$safeTitle} (#{$propertyId})</div>
    <div style="margin-top:8px;"><strong>Motivo:</strong> {$safeReason}</div>
</div>
<p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.5;">
    Entra al panel de solicitudes pendientes para aprobarla (se eliminará la
    propiedad y todos sus registros asociados) o rechazarla (se mantendrá la
    propiedad intacta). En ambos casos se notificará al usuario.
</p>
<p style="margin:0 0 24px;">
    <a href="{$absoluteUrl}" style="display:inline-block;background:#1e3a8a;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">Revisar solicitud</a>
</p>
HTML
    );

    $sent = 0;
    foreach ($admins as $admin) {
        $uid = (int) ($admin['id'] ?? 0);
        if ($uid <= 0) continue;

        try {
            createNotification($pdo, $uid, [
                'title' => $title,
                'message' => $inAppMessage,
                'type' => 'property_deletion_request',
                'related_request_id' => $requestId,
                'action_url' => $actionPath,
            ]);
            $sent++;
        } catch (Throwable $e) {
            error_log('[property_deletion] Falló notificación in-app a admin_id=' . $uid . ': ' . $e->getMessage());
        }

        $email = trim((string) ($admin['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        try {
            sendNotificationEmail($email, $title, $emailHtml);
        } catch (Throwable $e) {
            error_log('[property_deletion] Falló email a ' . $email . ': ' . $e->getMessage());
        }
    }

    return $sent;
}

/**
 * Notifica al usuario solicitante con el resultado (aprobado/rechazado).
 */
function notifyRequesterOfDeletionResolution(
    PDO $pdo,
    int $requesterUserId,
    int $propertyId,
    string $propertyTitle,
    string $status,
    ?string $adminNotes
): void {
    if ($requesterUserId <= 0) return;

    $approved = $status === 'approved';
    $title = $approved
        ? 'Tu solicitud de eliminación ha sido aprobada'
        : 'Tu solicitud de eliminación ha sido rechazada';

    $safeTitle = htmlspecialchars($propertyTitle !== '' ? $propertyTitle : 'Propiedad sin título', ENT_QUOTES, 'UTF-8');
    $safeNotes = $adminNotes !== null && trim($adminNotes) !== ''
        ? htmlspecialchars(trim($adminNotes), ENT_QUOTES, 'UTF-8')
        : null;

    $inAppMessage = $approved
        ? sprintf('La propiedad "%s" se ha eliminado tras tu solicitud.', $propertyTitle ?: 'Propiedad')
        : sprintf('La solicitud de eliminar "%s" ha sido rechazada por el administrador.', $propertyTitle ?: 'Propiedad');

    try {
        createNotification($pdo, $requesterUserId, [
            'title' => $title,
            'message' => $inAppMessage,
            'type' => $approved ? 'property_deletion_approved' : 'property_deletion_rejected',
            'related_request_id' => $propertyId,
            'action_url' => $approved ? '/profile/my-properties-for-sale' : null,
        ]);
    } catch (Throwable $e) {
        error_log('[property_deletion] Falló notificación al requester: ' . $e->getMessage());
    }

    $email = fetchUserEmailById($requesterUserId);
    if ($email === null) return;

    $bannerColor = $approved ? '#16a34a' : '#dc2626';
    $bannerBg = $approved ? '#f0fdf4' : '#fef2f2';
    $intro = $approved
        ? 'Tu solicitud para eliminar la siguiente propiedad ha sido aprobada. El activo y todos sus registros asociados se han eliminado de la plataforma.'
        : 'Tu solicitud para eliminar la siguiente propiedad ha sido rechazada. El activo sigue activo en la plataforma.';
    $notesBlock = $safeNotes
        ? '<div style="margin:0 0 20px;padding:14px 16px;border-left:4px solid #64748b;background:#f8fafc;border-radius:6px;color:#1e293b;font-size:14px;"><strong>Nota del administrador:</strong> ' . $safeNotes . '</div>'
        : '';

    $emailHtml = renderEmailLayout(
        $title,
        $approved ? 'Propiedad eliminada' : 'Solicitud rechazada',
        <<<HTML
<p style="margin:0 0 16px;color:#0f172a;font-size:15px;line-height:1.5;">{$intro}</p>
<div style="margin:0 0 20px;padding:14px 16px;border-left:4px solid {$bannerColor};background:{$bannerBg};border-radius:6px;color:#1e293b;font-size:14px;">
    <strong>{$safeTitle}</strong>
</div>
{$notesBlock}
<p style="margin:0;color:#6b7280;font-size:12px;">Si tienes dudas, contacta con el equipo de Reglado Real Estate.</p>
HTML
    );

    try {
        sendNotificationEmail($email, $title, $emailHtml);
    } catch (Throwable $e) {
        error_log('[property_deletion] Falló email al requester: ' . $e->getMessage());
    }
}

function fetchAdminUsersForDeletion(): array
{
    try {
        $pdoAuth = openRegladoUsersConnection();
        $stmt = $pdoAuth->query("SELECT id, email FROM users WHERE LOWER(role) = 'admin'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('[property_deletion] No se pudieron cargar admins: ' . $e->getMessage());
        return [];
    }
}

function fetchUserEmailById(int $userId): ?string
{
    if ($userId <= 0) return null;
    try {
        $pdoAuth = openRegladoUsersConnection();
        $stmt = $pdoAuth->prepare('SELECT email FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $email = trim((string) ($stmt->fetchColumn() ?: ''));
        return ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : null;
    } catch (Throwable $e) {
        error_log('[property_deletion] fetchUserEmailById: ' . $e->getMessage());
        return null;
    }
}

function fetchUserDisplayById(int $userId): array
{
    if ($userId <= 0) return ['email' => null, 'name' => ''];
    try {
        $pdoAuth = openRegladoUsersConnection();
        $stmt = $pdoAuth->prepare('SELECT email, first_name, last_name, username FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $name = trim(trim((string) ($row['first_name'] ?? '')) . ' ' . trim((string) ($row['last_name'] ?? '')));
        if ($name === '') {
            $name = (string) ($row['username'] ?? '');
        }
        return [
            'email' => $row['email'] ?? null,
            'name' => $name,
        ];
    } catch (Throwable $e) {
        error_log('[property_deletion] fetchUserDisplayById: ' . $e->getMessage());
        return ['email' => null, 'name' => ''];
    }
}

function openRegladoUsersConnection(): PDO
{
    return new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=regladousers;charset=utf8mb4',
            (string) getenv('DB_HOST'),
            (string) getenv('DB_PORT')
        ),
        (string) getenv('DB_USER'),
        (string) getenv('DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}
