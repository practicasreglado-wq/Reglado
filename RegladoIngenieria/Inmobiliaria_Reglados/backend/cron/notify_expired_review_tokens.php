<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/lib/notifications.php';
require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/send_mail.php';

loadEnv(dirname(__DIR__) . '/.env');

function logExpiredTokenJob(string $message, array $context = []): void
{
    $logsDir = dirname(__DIR__) . '/logs';

    if (!is_dir($logsDir)) {
        @mkdir($logsDir, 0777, true);
    }

    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;

    if (!empty($context)) {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json !== false) {
            $line .= ' | ' . $json;
        }
    }

    file_put_contents($logsDir . '/expired_tokens_job.log', $line . PHP_EOL, FILE_APPEND);
}

try {
    $sql = "
        SELECT
            t.id,
            t.property_id,
            t.buyer_user_id,
            t.expires_at,
            t.expiration_notified_at,
            t.expiration_email_sent_at,
            u.email,
            u.first_name,
            u.last_name,
            p.tipo_propiedad,
            p.ciudad,
            p.zona
        FROM inmobiliaria.signed_document_review_tokens t
        INNER JOIN regladousers.users u
            ON u.id = t.buyer_user_id
        LEFT JOIN inmobiliaria.propiedades p
            ON p.id = t.property_id
        WHERE t.expires_at IS NOT NULL
        AND t.expires_at < NOW()
        AND (t.expiration_notified_at IS NULL OR t.expiration_email_sent_at IS NULL)
    ";

    $stmt = $pdo->query($sql);
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tokens as $token) {
        $tokenId = (int) ($token['id'] ?? 0);
        $buyerUserId = (int) ($token['buyer_user_id'] ?? 0);
        $propertyId = (int) ($token['property_id'] ?? 0);
        $email = trim((string) ($token['email'] ?? ''));
        $firstName = trim((string) ($token['first_name'] ?? ''));
        $lastName = trim((string) ($token['last_name'] ?? ''));

        if ($tokenId <= 0 || $buyerUserId <= 0) {
            continue;
        }

        $tipo = trim((string) ($token['tipo_propiedad'] ?? ''));
        $ciudad = trim((string) ($token['ciudad'] ?? ''));
        $zona = trim((string) ($token['zona'] ?? ''));

        $parts = array_filter([$tipo, $ciudad, $zona]);

        if (!empty($parts)) {
            $propertyLabel = implode(' - ', $parts);
        } else {
            $propertyLabel = 'el activo seleccionado';
        }

        try {
            $pdo->beginTransaction();

            if (empty($token['expiration_notified_at'])) {
                createNotification($pdo, $buyerUserId, [
                    'title' => 'El enlace de firma ha expirado',
                    'message' => 'El plazo para firmar y enviar la documentación de ' . $propertyLabel . ' ha expirado. Debes volver a intentarlo y subir de nuevo los archivos firmados.',
                    'type' => 'document_token_expired',
                    'related_request_id' => $propertyId > 0 ? $propertyId : null,
                ]);

                $updateNotif = $pdo->prepare("
                    UPDATE inmobiliaria.signed_document_review_tokens
                    SET expiration_notified_at = NOW()
                    WHERE id = :id
                    LIMIT 1
                ");
                $updateNotif->execute([
                    'id' => $tokenId
                ]);
            }

            if ($email !== '' && empty($token['expiration_email_sent_at'])) {
                $fullName = trim($firstName . ' ' . $lastName);
                $displayName = $fullName !== '' ? $fullName : 'usuario';

                $subject = 'El enlace para enviar tu documentación ha expirado';

                $body = '
                <div style="margin:0;padding:24px;background:#f5f7fa;font-family:Arial,sans-serif;color:#1f2937;">
                    <div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #d9e2ec;border-radius:8px;overflow:hidden;">
                        <div style="background:linear-gradient(135deg,#2563eb,#1e40af);padding:20px;text-align:center;color:#ffffff;">
                            <h2 style="margin:0;font-size:22px;">Reglado Real Estate</h2>
                        </div>

                        <div style="padding:32px 24px;">
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                Hola ' . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') . ',
                            </p>

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                El enlace habilitado para aprobar la documentación de ' . htmlspecialchars($propertyLabel, ENT_QUOTES, 'UTF-8') . ' ha expirado.
                            </p>

                            <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                                Para continuar con el proceso, debes volver a acceder a la plataforma y repetir el envío de los archivos firmados.
                            </p>

                            <p style="margin:0;font-size:15px;line-height:1.6;">
                                Si necesitas ayuda, contacta con nuestro equipo.
                            </p>
                        </div>
                    </div>
                </div>';

                sendNotificationEmail($email, $subject, $body);

                $updateMail = $pdo->prepare("
                    UPDATE inmobiliaria.signed_document_review_tokens
                    SET expiration_email_sent_at = NOW()
                    WHERE id = :id
                    LIMIT 1
                ");
                $updateMail->execute([
                    'id' => $tokenId
                ]);
            }

            $pdo->commit();

            logExpiredTokenJob('Token expirado procesado correctamente', [
                'token_id' => $tokenId,
                'buyer_user_id' => $buyerUserId,
                'property_id' => $propertyId,
                'email' => $email,
            ]);
        } catch (Throwable $innerException) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            logExpiredTokenJob('Error procesando token expirado', [
                'token_id' => $tokenId,
                'error' => $innerException->getMessage(),
            ]);
        }
    }

    logExpiredTokenJob('Proceso completado', [
        'tokens_encontrados' => count($tokens),
    ]);
} catch (Throwable $e) {
    logExpiredTokenJob('Error general del job', [
        'error' => $e->getMessage(),
    ]);
}