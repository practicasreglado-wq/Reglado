<?php
declare(strict_types=1);

/**
 * Envuelve el HTML interno de un correo de Inmobiliaria en una ficha común
 * (header azul con título + cuerpo blanco + pie). El HTML pasado en $bodyHtml
 * NO se escapa: el llamador es responsable de escapar las variables que
 * inserte dentro.
 */
function renderEmailLayout(string $title, string $subtitle, string $bodyHtml): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeSubtitle = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
    $year = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{$safeTitle}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
    <tr>
      <td style="padding:32px 16px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 8px 30px rgba(15,23,42,0.08);">

          <tr>
            <td bgcolor="#0b3d91" style="padding:40px 36px 36px;background-color:#0b3d91;background-image:linear-gradient(135deg,#0b3d91,#123f7a);text-align:center;">
              <span style="display:inline-block;padding:6px 16px;border-radius:999px;background-color:rgba(255,255,255,0.18);color:#ffffff;font-size:12px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;">Reglado Real Estate</span>
              <h1 style="margin:18px 0 8px;font-size:26px;font-weight:800;color:#ffffff;line-height:1.25;">{$safeTitle}</h1>
              <p style="margin:0;font-size:14px;color:#dbeafe;line-height:1.5;">{$safeSubtitle}</p>
            </td>
          </tr>

          <tr>
            <td style="padding:32px 36px;color:#1f2937;font-size:15px;line-height:1.6;">
              {$bodyHtml}
            </td>
          </tr>

          <tr>
            <td style="padding:20px 36px;background:#f8fafc;border-top:1px solid #e2e8f0;">
              <p style="margin:0 0 4px;font-size:13px;font-weight:700;color:#0f172a;">Reglado Real Estate</p>
              <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">Correo automático generado por la plataforma. &copy; {$year} Reglado Real Estate.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}
