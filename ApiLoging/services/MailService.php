<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    public static function sendVerificationEmail(string $email, string $name, string $verificationUrl): bool
    {
        $subject = 'Confirma tu cuenta';
        $from = getenv('MAIL_FROM') ?: 'no-reply@reglado.local';
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $from,
        ];

        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8');

        $message = '<html><body>';
        $message .= '<p>Hola ' . $safeName . ',</p>';
        $message .= '<p>Para activar tu cuenta, confirma tu correo con este enlace:</p>';
        $message .= '<p><a href="' . $safeUrl . '">' . $safeUrl . '</a></p>';
        $message .= '<p>Si no solicitaste este registro, puedes ignorar este mensaje.</p>';
        $message .= '</body></html>';

        return self::sendHtml($email, $subject, $message, $headers);
    }

    public static function sendEmailChangeConfirmation(string $email, string $name, string $confirmationUrl): bool
    {
        $subject = 'Confirma el cambio de correo';
        $from = getenv('MAIL_FROM') ?: 'no-reply@reglado.local';
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $from,
        ];

        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($confirmationUrl, ENT_QUOTES, 'UTF-8');

        $message = '<html><body>';
        $message .= '<p>Hola ' . $safeName . ',</p>';
        $message .= '<p>Has solicitado cambiar el correo de tu cuenta. Confirma el cambio en este enlace:</p>';
        $message .= '<p><a href="' . $safeUrl . '">' . $safeUrl . '</a></p>';
        $message .= '<p>Si no fuiste tu, ignora este mensaje.</p>';
        $message .= '</body></html>';

        return self::sendHtml($email, $subject, $message, $headers);
    }

    public static function sendPasswordResetEmail(string $email, string $name, string $resetUrl): bool
    {
        $subject = 'Recupera tu contrasena';
        $from = getenv('MAIL_FROM') ?: 'no-reply@reglado.local';
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $from,
        ];

        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        $message = '<html><body>';
        $message .= '<p>Hola ' . $safeName . ',</p>';
        $message .= '<p>Has solicitado recuperar tu contrasena. Usa este enlace para establecer una nueva:</p>';
        $message .= '<p><a href="' . $safeUrl . '">' . $safeUrl . '</a></p>';
        $message .= '<p>Si no solicitaste este cambio, ignora este mensaje.</p>';
        $message .= '</body></html>';

        return self::sendHtml($email, $subject, $message, $headers);
    }

    private static function sendHtml(string $email, string $subject, string $message, array $headers): bool
    {
        $driver = strtolower((string) (getenv('MAIL_DRIVER') ?: 'mail'));
        if ($driver === 'log') {
            return self::logEmail($email, $subject, $message);
        }

        if ($driver === 'smtp') {
            return self::sendWithSmtp($email, $subject, $message);
        }

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
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;

            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
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
