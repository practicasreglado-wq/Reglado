<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    public static function sendVerificationEmail(string $email, string $name, string $verificationUrl): bool
    {
        $subject = 'Confirma tu correo electronico';
        $message = self::buildEmailLayout(
            $name,
            'Confirma tu cuenta',
            'Para activar tu cuenta, confirma tu correo con el siguiente enlace:',
            $verificationUrl,
            'Si no solicitaste este registro, puedes ignorar este mensaje.'
        );

        return self::sendHtml($email, $subject, $message);
    }

    public static function sendEmailChangeConfirmation(string $email, string $name, string $confirmationUrl): bool
    {
        $subject = 'Confirma el cambio de correo';
        $message = self::buildEmailLayout(
            $name,
            'Confirma tu nuevo correo',
            'Has solicitado cambiar el correo de tu cuenta. Confirma el cambio con el siguiente enlace:',
            $confirmationUrl,
            'Si no has sido tu, puedes ignorar este mensaje.'
        );

        return self::sendHtml($email, $subject, $message);
    }

    public static function sendPasswordResetEmail(string $email, string $name, string $resetUrl): bool
    {
        $subject = 'Recupera tu contrasena';
        $message = self::buildEmailLayout(
            $name,
            'Recupera tu contrasena',
            'Has solicitado restablecer tu contrasena. Usa este enlace para establecer una nueva:',
            $resetUrl,
            'Si no solicitaste este cambio, puedes ignorar este mensaje.'
        );

        return self::sendHtml($email, $subject, $message);
    }

    private static function buildEmailLayout(
        string $name,
        string $title,
        string $intro,
        string $actionUrl,
        string $closing
    ): string {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeIntro = htmlspecialchars($intro, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8');
        $safeClosing = htmlspecialchars($closing, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<html>
  <body style="margin:0;padding:24px;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #dbe5f3;">
      <tr>
        <td style="padding:28px 32px;background:linear-gradient(135deg,#16273e 0%,#1f3553 56%,#253f62 100%);color:#ffffff;">
          <div style="font-size:13px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.82;">Grupo Reglado</div>
          <h1 style="margin:10px 0 0;font-size:28px;line-height:1.2;">{$safeTitle}</h1>
        </td>
      </tr>
      <tr>
        <td style="padding:32px;">
          <p style="margin:0 0 16px;font-size:16px;">Hola {$safeName},</p>
          <p style="margin:0 0 24px;font-size:16px;line-height:1.6;">{$safeIntro}</p>
          <p style="margin:0 0 24px;">
            <a href="{$safeUrl}" style="display:inline-block;padding:14px 22px;border-radius:10px;background:#1f3553;color:#ffffff;text-decoration:none;font-weight:700;">
              Abrir enlace
            </a>
          </p>
          <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">Si el boton no funciona, copia y pega este enlace en tu navegador:</p>
          <p style="margin:0 0 24px;font-size:14px;line-height:1.6;word-break:break-word;">
            <a href="{$safeUrl}" style="color:#1d4ed8;text-decoration:none;">{$safeUrl}</a>
          </p>
          <p style="margin:0;font-size:14px;line-height:1.6;color:#475569;">{$safeClosing}</p>
        </td>
      </tr>
      <tr>
        <td style="padding:20px 32px;border-top:1px solid #e2e8f0;font-size:13px;color:#64748b;background:#f8fafc;">
          Este mensaje ha sido enviado por Grupo Reglado.
        </td>
      </tr>
    </table>
  </body>
</html>
HTML;
    }

    private static function sendHtml(string $email, string $subject, string $message): bool
    {
        $driver = strtolower((string) (getenv('MAIL_DRIVER') ?: 'mail'));
        if ($driver === 'log') {
            return self::logEmail($email, $subject, $message);
        }

        if ($driver === 'smtp') {
            return self::sendWithSmtp($email, $subject, $message);
        }

        $from = getenv('MAIL_FROM') ?: 'no-reply@reglado.local';
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $from,
            'Reply-To: ' . $from,
        ];

        return mail($email, $subject, $message, implode("\r\n", $headers));
    }

    private static function sendWithSmtp(string $toEmail, string $subject, string $htmlBody): bool
    {
        $host = getenv('MAIL_HOST') ?: '';
        $port = (int) (getenv('MAIL_PORT') ?: 587);
        $username = getenv('MAIL_USERNAME') ?: '';
        $password = str_replace(' ', '', trim((string) (getenv('MAIL_PASSWORD') ?: '')));
        $fromEmail = getenv('MAIL_FROM') ?: $username;
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Reglado';
        $secure = strtolower((string) (getenv('MAIL_ENCRYPTION') ?: 'tls'));

        if ($host === '' || $username === '' || $password === '' || $fromEmail === '') {
            return false;
        }

        $smtpSecure = '';
        if ($secure === 'tls') {
            $smtpSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($secure === 'ssl') {
            $smtpSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            if ($smtpSecure !== '') {
                $mail->SMTPSecure = $smtpSecure;
            }
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addReplyTo($fromEmail, $fromName);
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = self::buildAltBody($htmlBody);

            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }

    private static function buildAltBody(string $htmlBody): string
    {
        $plain = preg_replace('/<br\\s*\\/?>/i', "\n", $htmlBody);
        $plain = preg_replace('/<\\/p>/i', "\n\n", (string) $plain);
        $plain = strip_tags((string) $plain);

        return trim(html_entity_decode($plain, ENT_QUOTES, 'UTF-8'));
    }

    private static function logEmail(string $email, string $subject, string $message): bool
    {
        $dir = __DIR__ . '/../storage';
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            return false;
        }

        $entry = sprintf(
            "[%s] TO: %s\nSUBJECT: %s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $email,
            $subject,
            strip_tags($message)
        );

        return file_put_contents($dir . '/mail.log', $entry, FILE_APPEND) !== false;
    }
}
