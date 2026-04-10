<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function sendNotificationEmail(string $to, string $subject, string $htmlBody, ?string $replyTo = null, array $attachments = []): void
{
    $mailer = new PHPMailer(true);

    $mailer->CharSet = 'UTF-8';
    $mailer->Encoding = 'base64';   

    $host = getenv('SMTP_HOST') ?: '';
    $port = (int) (getenv('SMTP_PORT') ?: 0);
    $user = getenv('SMTP_USER') ?: '';
    $pass = getenv('SMTP_PASS') ?: '';
    $from = getenv('SMTP_FROM') ?: $user;
    $fromName = getenv('SMTP_FROM_NAME') ?: 'Reglado Consultores';
    $encryption = resolveSmtpEncryption((string) getenv('SMTP_SECURE'));

    if ($host === '' || $from === '' || $user === '' || $pass === '') {
        throw new RuntimeException('Configuración SMTP incompleta.');
    }

    $mailer->isSMTP();
    $mailer->setLanguage('es');
    $mailer->Host = $host;
    $mailer->SMTPAuth = true;
    $mailer->Username = $user;
    $mailer->Password = $pass;
    $mailer->Port = $port > 0 ? $port : 587;

    if ($encryption !== null) {
        $mailer->SMTPSecure = $encryption;
    }

    $mailer->setFrom($from, $fromName);
    $mailer->addAddress($to);

    if ($replyTo) {
        $mailer->addReplyTo($replyTo);
    }

    $mailer->Subject = trim($subject) === '' ? 'Notificación Reglado' : $subject;
    $mailer->isHTML(true);
    $mailer->Body = $htmlBody;
    $mailer->AltBody = strip_tags($htmlBody);

    foreach ($attachments as $attachment) {
        $filePath = trim((string) ($attachment['path'] ?? ''));
        if ($filePath === '' || !is_file($filePath)) {
            continue;
        }

        $displayName = trim((string) ($attachment['name'] ?? ''));
        $mailer->addAttachment($filePath, $displayName ?: basename($filePath));
    }

    $mailer->send();
}

function resolveSmtpEncryption(string $value): ?string
{
    $clean = trim($value);
    if ($clean === '') {
        return null;
    }

    if (str_contains($clean, '::')) {
        [$class, $constant] = explode('::', $clean, 2);
        $class = trim($class);
        $constant = trim($constant);

        if ($class === 'PHPMailer') {
            $class = 'PHPMailer\\PHPMailer\\PHPMailer';
        }

        $full = $class . '::' . $constant;
        if (defined($full)) {
            return constant($full);
        }
    }

    return $clean;
}
