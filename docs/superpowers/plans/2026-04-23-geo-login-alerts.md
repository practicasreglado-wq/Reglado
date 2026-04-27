# Geo Login Alerts Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Detectar logins desde un país distinto al último login legítimo del usuario, enviarle un email con dos botones (sí/no fui yo), y en caso negativo matar la sesión + forzar cambio de contraseña en el próximo login.

**Architecture:** Un hook síncrono en `AuthController::login` que, tras emitir el JWT, consulta MaxMind GeoLite2 offline para detectar nuevo país y, si procede, genera un token de confirmación, persiste una fila en `login_locations` con estado `pending` y dispara un email con dos URLs firmadas. Los clics abren un endpoint GET público (`/auth/confirm-login-location`) que resuelve el estado. Si el usuario rechaza, el endpoint limpia `current_session_id` y activa `users.require_password_reset`, que es chequeado en el próximo login.

**Tech Stack:** PHP 8 raw (ApiLoging) + MariaDB + `firebase/php-jwt` + `phpmailer/phpmailer` + nuevo `geoip2/geoip2`. Frontend Vue 3 solo recibe una traducción nueva.

**Base dir:** `c:/xampp/htdocs/Reglado/`. Todos los paths son relativos a esta raíz.

**Spec de referencia:** [docs/superpowers/specs/2026-04-23-geo-login-alerts-design.md](../specs/2026-04-23-geo-login-alerts-design.md)

**Nota de ejecución:** el usuario prefiere ejecutar `git commit` manualmente tras probar cada bloque de cambios. Los comandos `git add` + `git commit` aparecen en cada task como **referencia** del commit que debería hacerse al final, pero quien ejecuta este plan **no debe lanzarlos** a menos que el usuario lo pida explícitamente. Los verificadores de código (syntax checks, curl de smoke) sí se ejecutan.

---

## Task 1: DB migration + directorio data + gitignore

**Files:**
- Create: `ApiLoging/database/migrate_login_locations.sql`
- Modify: `ApiLoging/database/schema.sql`
- Create: `ApiLoging/data/` (directorio)
- Create: `ApiLoging/data/README.md`
- Modify: `.gitignore` (raíz)

- [ ] **Step 1.1: Crear migración SQL**

Crear `ApiLoging/database/migrate_login_locations.sql`:

```sql
-- Migración: geo login alerts.
--
-- login_locations registra cada login con su país y un status que decide
-- si dispara alerta y si cuenta como referencia para el siguiente login.
-- Estados: 'neutral' (mismo país que el anterior), 'pending' (alerta
-- enviada, aún no respondida), 'confirmed' (usuario dijo "fui yo"),
-- 'rejected' (usuario dijo "no fui yo", excluido del cálculo del último
-- legítimo).
--
-- users.require_password_reset es el flag que activamos al rechazar una
-- alerta; el login bloqueará ese flag hasta que el usuario reset su password.

CREATE TABLE IF NOT EXISTS login_locations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  ip VARCHAR(45) NOT NULL,
  country_code CHAR(2) NULL,
  country_name VARCHAR(100) NULL,
  user_agent VARCHAR(512) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'neutral',
  token_hash CHAR(64) NULL,
  token_expires_at DATETIME NULL,
  token_used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_login_locations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_login_locations_user_created ON login_locations (user_id, created_at);
CREATE INDEX idx_login_locations_token_hash ON login_locations (token_hash);

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS require_password_reset TINYINT(1) NOT NULL DEFAULT 0;
```

- [ ] **Step 1.2: Actualizar schema.sql**

En `ApiLoging/database/schema.sql`, localizar la definición `CREATE TABLE IF NOT EXISTS users (...)` (después de los índices de `idx_users_banned_at` y antes del siguiente CREATE TABLE) y:

1. Añadir dentro del bloque de `CREATE TABLE users`, tras `current_session_id CHAR(64) NULL,`:
```sql
  require_password_reset TINYINT(1) NOT NULL DEFAULT 0,
```

2. Al final del archivo (tras los CREATE INDEX existentes) añadir:
```sql
CREATE TABLE IF NOT EXISTS login_locations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  ip VARCHAR(45) NOT NULL,
  country_code CHAR(2) NULL,
  country_name VARCHAR(100) NULL,
  user_agent VARCHAR(512) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'neutral',
  token_hash CHAR(64) NULL,
  token_expires_at DATETIME NULL,
  token_used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_login_locations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_login_locations_user_created ON login_locations (user_id, created_at);
CREATE INDEX idx_login_locations_token_hash ON login_locations (token_hash);
```

- [ ] **Step 1.3: Ejecutar migración**

```bash
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers < c:/xampp/htdocs/Reglado/ApiLoging/database/migrate_login_locations.sql 2>&1 | grep -v Warning
```

Expected: sin salida (sin errores).

- [ ] **Step 1.4: Verificar estructura**

```bash
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "DESCRIBE login_locations;" 2>&1 | grep -v Warning
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "DESCRIBE users;" 2>&1 | grep -v Warning | grep require_password_reset
```

Expected:
- `login_locations` con 11 columnas (id, user_id, ip, country_code, country_name, user_agent, status, token_hash, token_expires_at, token_used_at, created_at).
- `require_password_reset	tinyint(1)	NO		0`.

- [ ] **Step 1.5: Crear directorio data + README**

```bash
mkdir -p c:/xampp/htdocs/Reglado/ApiLoging/data
```

Crear `ApiLoging/data/README.md`:

```markdown
# ApiLoging/data

Datos binarios no versionados.

## GeoLite2-Country.mmdb

Base de datos de geolocalización IP→país usada por `GeoLocationService`.

### Descarga inicial

1. Cuenta gratis en https://www.maxmind.com/en/geolite2/signup
2. Panel → "Download databases" → fila **GeoLite Country** → "Download GZIP".
3. Extraer; copiar `GeoLite2-Country.mmdb` a este directorio.

### Actualización

El archivo se puede refrescar mensualmente; países cambian raramente, así que
no es urgente. Si el archivo no existe o está corrupto, `GeoLocationService`
degrada grácilmente: registra logins con `country_code = NULL` y no dispara
alertas.

### Deploy a Hostinger

Subir el mismo `.mmdb` por FTP a `ApiLoging/data/` en producción. No está
en el repo.
```

- [ ] **Step 1.6: Actualizar .gitignore**

En `.gitignore` (raíz del repo), añadir al final:

```
ApiLoging/data/*.mmdb
```

- [ ] **Step 1.7: Verificar que .mmdb no queda trackeado**

```bash
cd c:/xampp/htdocs/Reglado && git check-ignore ApiLoging/data/GeoLite2-Country.mmdb
```

Expected: la salida imprime el path (significa que está ignorado). Si no imprime nada, el patrón no está funcionando.

- [ ] **Step 1.8: Commit (referencia — no ejecutar sin pedir OK)**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/database/migrate_login_locations.sql ApiLoging/database/schema.sql ApiLoging/data/README.md .gitignore
git commit -m "feat(ApiLoging): schema login_locations + require_password_reset + data dir"
```

---

## Task 2: Modelo User — métodos para login_locations y require_password_reset

**Files:**
- Modify: `ApiLoging/models/User.php`

- [ ] **Step 2.1: Añadir los 5 métodos nuevos**

Al final de la clase `User` (justo antes del cierre `}` de `listAll`), añadir:

```php
    /**
     * Devuelve el country_code del último login legítimo (status neutral o
     * confirmed). Los pending y rejected se excluyen a propósito: así una
     * alerta no respondida o una rechazada no envenena la referencia del
     * siguiente login.
     */
    public static function getLastLegitLoginCountry(int $userId): ?string
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            "SELECT country_code FROM login_locations
             WHERE user_id = ?
               AND status IN ('neutral', 'confirmed')
               AND country_code IS NOT NULL
             ORDER BY created_at DESC, id DESC
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? (string) $row['country_code'] : null;
    }

    /**
     * Inserta un registro de login_locations. status puede ser 'neutral' o
     * 'pending'. En pending, se persisten token_hash y token_expires_at.
     * Devuelve el id del registro creado.
     */
    public static function recordLoginLocation(
        int $userId,
        string $ip,
        ?string $countryCode,
        ?string $countryName,
        string $userAgent,
        string $status,
        ?string $tokenHash = null,
        ?string $tokenExpiresAt = null
    ): int {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO login_locations
               (user_id, ip, country_code, country_name, user_agent, status, token_hash, token_expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId, $ip, $countryCode, $countryName,
            mb_substr($userAgent, 0, 512),
            $status, $tokenHash, $tokenExpiresAt,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function findLoginLocationByTokenHash(string $tokenHash): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM login_locations WHERE token_hash = ? LIMIT 1');
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function updateLoginLocationStatus(int $locationId, string $status): void
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'UPDATE login_locations SET status = ?, token_used_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$status, $locationId]);
    }

    public static function setRequirePasswordReset(int $userId, bool $required): void
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET require_password_reset = ? WHERE id = ?');
        $stmt->execute([$required ? 1 : 0, $userId]);
    }
```

- [ ] **Step 2.2: Verificación sintáctica**

```bash
php -l c:/xampp/htdocs/Reglado/ApiLoging/models/User.php 2>&1 | grep -v "Warning: Module"
```

Expected: `No syntax errors detected`.

- [ ] **Step 2.3: Commit (referencia)**

```bash
git add ApiLoging/models/User.php
git commit -m "feat(ApiLoging): métodos User para login_locations y require_password_reset"
```

---

## Task 3: Composer + GeoLocationService

**Files:**
- Modify: `ApiLoging/composer.json`
- Create: `ApiLoging/services/GeoLocationService.php`
- Modify: `ApiLoging/index.php` (incluir el require del nuevo servicio)

- [ ] **Step 3.1: Añadir geoip2/geoip2 a composer.json**

Sustituir `ApiLoging/composer.json` completo por:

```json
{
  "name": "reglado/auth-server",
  "description": "Raw PHP auth server with JWT and global logout via token revocation",
  "type": "project",
  "require": {
    "firebase/php-jwt": "^6.11",
    "phpmailer/phpmailer": "^7.0",
    "geoip2/geoip2": "^3.0"
  }
}
```

- [ ] **Step 3.2: Instalar dependencia**

```bash
cd c:/xampp/htdocs/Reglado/ApiLoging && composer install 2>&1 | tail -10
```

Expected: líneas tipo `Installing geoip2/geoip2 (v3.x.x)` y al final `Package operations: X installs, 0 updates, 0 removals`. Si dice `Nothing to modify in lock` también es OK (si composer update vs install).

Si composer no encuentra el lock actualizado, usar:

```bash
cd c:/xampp/htdocs/Reglado/ApiLoging && composer update geoip2/geoip2 2>&1 | tail -10
```

- [ ] **Step 3.3: Crear GeoLocationService.php**

Crear `ApiLoging/services/GeoLocationService.php`:

```php
<?php

/**
 * Traduce una IP a (country_code, country_name) usando el archivo local
 * GeoLite2-Country.mmdb de MaxMind. Sin red, sin terceros.
 *
 * Degradación grácil:
 *   - Si el .mmdb no existe o es ilegible, devuelve null (se loguea una vez).
 *   - Si la IP es privada/localhost/reservada/inválida, devuelve null.
 *
 * El caller debe tratar null como "país desconocido" y no disparar alerta.
 */
class GeoLocationService
{
    /** @var \GeoIp2\Database\Reader|null */
    private static $reader = null;
    private static bool $initialized = false;

    /**
     * @return array{country_code: ?string, country_name: ?string}|null
     */
    public static function lookup(string $ip): ?array
    {
        if (!self::ensureReader()) {
            return null;
        }

        try {
            $record = self::$reader->country($ip);
            return [
                'country_code' => $record->country->isoCode,
                'country_name' => $record->country->name,
            ];
        } catch (Throwable $e) {
            // IPs privadas/no geolocalizables caen aquí. Es normal en dev.
            return null;
        }
    }

    private static function ensureReader(): bool
    {
        if (self::$initialized) {
            return self::$reader !== null;
        }
        self::$initialized = true;

        $path = __DIR__ . '/../data/GeoLite2-Country.mmdb';
        if (!is_readable($path)) {
            error_log('GEOIP_DB_MISSING path=' . $path);
            return false;
        }

        try {
            self::$reader = new \GeoIp2\Database\Reader($path);
            return true;
        } catch (Throwable $e) {
            error_log('GEOIP_DB_OPEN_FAIL message=' . $e->getMessage());
            self::$reader = null;
            return false;
        }
    }
}
```

- [ ] **Step 3.4: Registrar el require en index.php**

En `ApiLoging/index.php`, localizar el bloque de `require_once` al principio (después de `vendor/autoload.php`). Añadir tras la línea `require_once __DIR__ . '/services/SecurityLogger.php';` (o la última `services/*.php` existente):

```php
require_once __DIR__ . '/services/GeoLocationService.php';
```

- [ ] **Step 3.5: Verificación sintáctica**

```bash
php -l c:/xampp/htdocs/Reglado/ApiLoging/services/GeoLocationService.php 2>&1 | grep -v "Warning: Module"
php -l c:/xampp/htdocs/Reglado/ApiLoging/index.php 2>&1 | grep -v "Warning: Module"
```

Expected ambos: `No syntax errors detected`.

- [ ] **Step 3.6: Commit (referencia)**

```bash
git add ApiLoging/composer.json ApiLoging/composer.lock ApiLoging/services/GeoLocationService.php ApiLoging/index.php
git commit -m "feat(ApiLoging): GeoLocationService + dependencia geoip2/geoip2"
```

---

## Task 4: Copiar .mmdb y verificar

Este task es operacional. El usuario ya descargó el archivo (ver memoria de la sesión brainstorming).

- [ ] **Step 4.1: Pedir al usuario la ruta del .mmdb**

Preguntar al usuario dónde guardó el `GeoLite2-Country.mmdb` descargado de maxmind.com. Alternativamente, ejecutar:

```bash
find c:/Users/sonic -iname "GeoLite2-Country.mmdb" 2>/dev/null | head -3
find c:/tmp -iname "GeoLite2-Country.mmdb" 2>/dev/null | head -3
```

Guardar la ruta como `$MMDB_SRC`.

- [ ] **Step 4.2: Copiar el archivo**

```bash
cp "$MMDB_SRC" c:/xampp/htdocs/Reglado/ApiLoging/data/GeoLite2-Country.mmdb
ls -la c:/xampp/htdocs/Reglado/ApiLoging/data/
```

Expected: el archivo aparece con tamaño aproximado 4-7 MB.

- [ ] **Step 4.3: Verificar lookup con script ad-hoc**

```bash
cd c:/xampp/htdocs/Reglado/ApiLoging && php -r '
require "vendor/autoload.php";
require "services/GeoLocationService.php";

// IP de Google DNS, US.
$r = GeoLocationService::lookup("8.8.8.8");
print_r($r);

// IP de Telefónica España (bloque histórico).
$r = GeoLocationService::lookup("80.58.0.1");
print_r($r);

// IP privada (debe devolver null).
$r = GeoLocationService::lookup("192.168.1.1");
var_dump($r);
' 2>&1 | grep -v "Warning: Module"
```

Expected:
- Primera: `Array ( [country_code] => US [country_name] => United States )`.
- Segunda: `Array ( [country_code] => ES [country_name] => Spain )`.
- Tercera: `NULL`.

Si la segunda IP cambia de país en el futuro (reasignación de bloques), sustituir por otra IP española conocida (ej. `8.8.4.4` es US; para ES buscar una IP fresca).

- [ ] **Step 4.4: Sin commit**

El `.mmdb` está en `.gitignore`, no se commitea.

---

## Task 5: MailService::sendLoginAlert

**Files:**
- Modify: `ApiLoging/services/MailService.php`

- [ ] **Step 5.1: Añadir el método sendLoginAlert**

En `ApiLoging/services/MailService.php`, tras el método `sendPasswordResetEmail` (línea ~51), añadir:

```php
    public static function sendLoginAlert(
        array $user,
        ?string $countryName,
        string $ip,
        string $yesUrl,
        string $noUrl
    ): bool {
        $subject = 'Nueva ubicación detectada en tu cuenta Reglado';
        $message = self::buildLoginAlertLayout(
            (string) ($user['name'] ?? ''),
            $countryName ?? 'desconocido',
            $ip,
            $yesUrl,
            $noUrl
        );
        return self::sendHtml((string) $user['email'], $subject, $message);
    }

    private static function buildLoginAlertLayout(
        string $name,
        string $country,
        string $ip,
        string $yesUrl,
        string $noUrl
    ): string {
        $safeName    = htmlspecialchars($name,    ENT_QUOTES, 'UTF-8');
        $safeCountry = htmlspecialchars($country, ENT_QUOTES, 'UTF-8');
        $safeIp      = htmlspecialchars($ip,      ENT_QUOTES, 'UTF-8');
        $safeYesUrl  = htmlspecialchars($yesUrl,  ENT_QUOTES, 'UTF-8');
        $safeNoUrl   = htmlspecialchars($noUrl,   ENT_QUOTES, 'UTF-8');
        $when        = htmlspecialchars(date('d/m/Y H:i'), ENT_QUOTES, 'UTF-8');
        $year        = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Nueva ubicación detectada</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
    <tr>
      <td style="padding:32px 16px;">
        <table role="presentation" align="center" width="560" cellspacing="0" cellpadding="0" style="background:#ffffff;border-radius:12px;box-shadow:0 4px 24px rgba(15,23,42,.08);overflow:hidden;">
          <tr>
            <td style="padding:32px 40px 8px;">
              <h1 style="margin:0 0 16px;font-size:22px;color:#0f172a;">Nueva ubicación detectada</h1>
              <p style="margin:0 0 16px;line-height:1.55;">Hola, {$safeName}:</p>
              <p style="margin:0 0 16px;line-height:1.55;">Detectamos un inicio de sesión en tu cuenta Reglado desde un país distinto al habitual:</p>
              <table role="presentation" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:16px 0;font-size:14px;">
                <tr><td style="padding:4px 12px 4px 0;color:#64748b;">País:</td><td style="padding:4px 0;font-weight:600;">{$safeCountry}</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#64748b;">IP:</td><td style="padding:4px 0;font-family:monospace;">{$safeIp}</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#64748b;">Fecha:</td><td style="padding:4px 0;">{$when}</td></tr>
              </table>
              <p style="margin:24px 0 16px;line-height:1.55;font-weight:600;">¿Has sido tú?</p>
              <table role="presentation" cellspacing="0" cellpadding="0" style="margin:16px 0;">
                <tr>
                  <td style="padding-right:12px;">
                    <a href="{$safeYesUrl}" style="display:inline-block;padding:12px 24px;background:#16a34a;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">Sí, he sido yo</a>
                  </td>
                  <td>
                    <a href="{$safeNoUrl}" style="display:inline-block;padding:12px 24px;background:#dc2626;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">No, no he sido yo</a>
                  </td>
                </tr>
              </table>
              <p style="margin:24px 0 8px;line-height:1.55;font-size:13px;color:#475569;">Si no has sido tú, pulsa "No, no he sido yo" cuanto antes. Cerraremos la sesión sospechosa y te pediremos cambiar la contraseña la próxima vez que accedas.</p>
              <p style="margin:8px 0 0;line-height:1.55;font-size:13px;color:#475569;">Si sí fuiste tú, no hace falta que hagas nada.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 40px 32px;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8;">
              Este aviso se envía automáticamente cuando detectamos logins desde un país nuevo.<br>
              &copy; {$year} Reglado Group
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
```

- [ ] **Step 5.2: Verificación sintáctica**

```bash
php -l c:/xampp/htdocs/Reglado/ApiLoging/services/MailService.php 2>&1 | grep -v "Warning: Module"
```

Expected: `No syntax errors detected`.

- [ ] **Step 5.3: Commit (referencia)**

```bash
git add ApiLoging/services/MailService.php
git commit -m "feat(ApiLoging): MailService::sendLoginAlert con dos botones sí/no"
```

---

## Task 6: Hook de geo en AuthController::login

**Files:**
- Modify: `ApiLoging/controllers/AuthController.php`

- [ ] **Step 6.1: Añadir la llamada a handleLoginLocation en login()**

En `AuthController::login`, localizar la línea final del try de login (donde se hace el último `Response::json([...])` antes del catch). La secuencia actual es:

```php
        $sid = User::rotateSession((int) $user['id']);
        $token = JwtService::generate($user, $sid);
        SecurityLogger::log('login_success', (int) $user['id']);

        Response::json([
            'token' => $token,
            'user' => [
                ...
            ],
        ]);
```

Insertar la llamada a `handleLoginLocation` justo antes del `Response::json`:

```php
        $sid = User::rotateSession((int) $user['id']);
        $token = JwtService::generate($user, $sid);
        SecurityLogger::log('login_success', (int) $user['id']);

        self::handleLoginLocation((int) $user['id'], Security::getClientIp(), (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

        Response::json([
            'token' => $token,
            'user' => [
                ...
            ],
        ]);
```

- [ ] **Step 6.2: Añadir el método handleLoginLocation y sus helpers**

Al final de la clase AuthController (justo antes del cierre `}` final), añadir:

```php
    /**
     * Evalúa si el login viene de un país distinto al último legítimo y, si
     * procede, dispara un email de alerta con dos botones.
     *
     * Todo envuelto en try/catch: a estas alturas el JWT ya fue emitido al
     * usuario, y cualquier Response::json de error mataría el script antes
     * de que la respuesta llegue. La alerta es best-effort; si falla por
     * cualquier motivo (geo caído, mail caído, config ausente), se registra
     * como neutral y seguimos.
     */
    private static function handleLoginLocation(int $userId, string $ip, string $userAgent): void
    {
        try {
            $geo = GeoLocationService::lookup($ip);
            $countryCode = $geo['country_code'] ?? null;
            $countryName = $geo['country_name'] ?? null;

            $lastLegitCountry = User::getLastLegitLoginCountry($userId);

            $isNewCountry = $countryCode !== null
                         && $lastLegitCountry !== null
                         && $countryCode !== $lastLegitCountry;

            if (!$isNewCountry) {
                User::recordLoginLocation($userId, $ip, $countryCode, $countryName, $userAgent, 'neutral');
                return;
            }

            $alertUrlBase = self::resolveLoginAlertBaseUrl();
            if ($alertUrlBase === null) {
                error_log('LOGIN_ALERT_URL_BASE missing, falling back to neutral');
                User::recordLoginLocation($userId, $ip, $countryCode, $countryName, $userAgent, 'neutral');
                return;
            }

            [$plainToken, $tokenHash, $expiresAt] = self::buildLoginAlertToken();
            User::recordLoginLocation(
                $userId, $ip, $countryCode, $countryName, $userAgent,
                'pending', $tokenHash, $expiresAt
            );

            $user = User::findById($userId);
            if (!$user) {
                return;
            }
            $yesUrl = self::appendQuery($alertUrlBase, ['token' => $plainToken, 'decision' => 'me']);
            $noUrl  = self::appendQuery($alertUrlBase, ['token' => $plainToken, 'decision' => 'not-me']);
            MailService::sendLoginAlert($user, $countryName, $ip, $yesUrl, $noUrl);
            SecurityLogger::log('login_alert_sent', $userId, [
                'country' => $countryCode, 'ip' => $ip,
            ]);
        } catch (Throwable $e) {
            error_log('LOGIN_ALERT_FAIL user=' . $userId . ' message=' . $e->getMessage());
        }
    }

    private static function buildLoginAlertToken(): array
    {
        $ttlSeconds = 7 * 24 * 3600;
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlSeconds);
        return [$plainToken, $tokenHash, $expiresAt];
    }

    private static function resolveLoginAlertBaseUrl(): ?string
    {
        $base = (string) (getenv('LOGIN_ALERT_URL_BASE') ?: '');
        if ($base !== '') return $base;
        $appEnv = strtolower((string) (getenv('APP_ENV') ?: 'local'));
        return $appEnv === 'local' ? 'http://localhost:8000/auth/confirm-login-location' : null;
    }

    private static function appendQuery(string $url, array $params): string
    {
        $sep = str_contains($url, '?') ? '&' : '?';
        return $url . $sep . http_build_query($params);
    }
```

- [ ] **Step 6.3: Verificación sintáctica**

```bash
php -l c:/xampp/htdocs/Reglado/ApiLoging/controllers/AuthController.php 2>&1 | grep -v "Warning: Module"
```

Expected: `No syntax errors detected`.

- [ ] **Step 6.4: Commit (referencia)**

```bash
git add ApiLoging/controllers/AuthController.php
git commit -m "feat(ApiLoging): hook geo en login, registra y alerta por nuevo país"
```

---

## Task 7: require_password_reset en login + limpieza en resetPassword

**Files:**
- Modify: `ApiLoging/controllers/AuthController.php`

- [ ] **Step 7.1: Bloqueo en login cuando require_password_reset=1**

En `AuthController::login`, localizar el bloque existente tras el check de banned:

```php
        if (!empty($user['banned_at'])) {
            SecurityLogger::log('login_blocked_banned', (int) $user['id'], ['email' => $email]);
            Response::json(['error' => 'account banned'], 403);
        }

        RateLimiter::resetFailure('login_lockout', $normalizedEmail);
```

Insertar el nuevo check ENTRE el bloque de banned y el resetFailure:

```php
        if (!empty($user['banned_at'])) {
            SecurityLogger::log('login_blocked_banned', (int) $user['id'], ['email' => $email]);
            Response::json(['error' => 'account banned'], 403);
        }

        if ((int) ($user['require_password_reset'] ?? 0) === 1) {
            // Emitimos un token de reset y lo enviamos por email; el usuario
            // sigue bloqueado hasta que complete /auth/reset-password.
            [$plainToken, $tokenHash, $expiresAt] = self::buildVerificationToken();
            User::createPasswordResetToken((int) $user['id'], $tokenHash, $expiresAt);
            $resetUrl = self::buildPasswordResetUrl($plainToken);
            $sent = MailService::sendPasswordResetEmail((string) $user['email'], (string) $user['name'], $resetUrl);
            SecurityLogger::log('login_blocked_reset_required', (int) $user['id']);
            if (!$sent) {
                Response::json(['error' => 'could not send password reset email'], 500);
            }
            Response::json(['error' => 'password reset required'], 403);
        }

        RateLimiter::resetFailure('login_lockout', $normalizedEmail);
```

- [ ] **Step 7.2: Limpiar el flag al completar resetPassword**

En `AuthController::resetPassword`, localizar el try interno:

```php
        try {
            User::updatePasswordHash((int) $user['id'], password_hash($newPassword, PASSWORD_BCRYPT));
            User::markPasswordResetAsUsed((int) $user['reset_id']);
            SecurityLogger::log('password_reset_completed', (int) $user['id']);
            self::respondWithRotatedSession((int) $user['id'], 'password updated');
        } catch (Throwable $e) {
```

Insertar la limpieza del flag antes de `respondWithRotatedSession`:

```php
        try {
            User::updatePasswordHash((int) $user['id'], password_hash($newPassword, PASSWORD_BCRYPT));
            User::markPasswordResetAsUsed((int) $user['reset_id']);
            User::setRequirePasswordReset((int) $user['id'], false);
            SecurityLogger::log('password_reset_completed', (int) $user['id']);
            self::respondWithRotatedSession((int) $user['id'], 'password updated');
        } catch (Throwable $e) {
```

- [ ] **Step 7.3: Verificación sintáctica**

```bash
php -l c:/xampp/htdocs/Reglado/ApiLoging/controllers/AuthController.php 2>&1 | grep -v "Warning: Module"
```

Expected: `No syntax errors detected`.

- [ ] **Step 7.4: Commit (referencia)**

```bash
git add ApiLoging/controllers/AuthController.php
git commit -m "feat(ApiLoging): require_password_reset bloquea login y se limpia en reset"
```

---

## Task 8: Endpoint confirmLoginLocation + ruta + HTML pages

**Files:**
- Modify: `ApiLoging/controllers/AuthController.php`
- Modify: `ApiLoging/index.php`

- [ ] **Step 8.1: Añadir el método confirmLoginLocation y su renderer**

Tras `handleLoginLocation` (o al final de la clase), añadir:

```php
    /**
     * Endpoint público que procesa el clic de los dos botones del email de
     * alerta. Se abre desde el cliente de email, así que responde HTML
     * (no JSON) y no requiere autenticación (se autentica por el token en la
     * URL).
     */
    public static function confirmLoginLocation(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        $decision = trim((string) ($_GET['decision'] ?? ''));

        if ($token === '' || !in_array($decision, ['me', 'not-me'], true)) {
            self::renderLoginLocationPage('invalid');
        }

        $tokenHash = hash('sha256', $token);
        $location = User::findLoginLocationByTokenHash($tokenHash);

        $expired = $location
            && (
                $location['status'] !== 'pending'
                || $location['token_used_at'] !== null
                || strtotime((string) ($location['token_expires_at'] ?? '')) < time()
            );

        if (!$location || $expired) {
            self::renderLoginLocationPage('expired');
        }

        $userId = (int) $location['user_id'];

        if ($decision === 'me') {
            User::updateLoginLocationStatus((int) $location['id'], 'confirmed');
            SecurityLogger::log('login_location_confirmed', $userId);
            self::renderLoginLocationPage('confirmed');
        }

        // decision === 'not-me'
        User::updateLoginLocationStatus((int) $location['id'], 'rejected');
        User::clearSession($userId);
        User::setRequirePasswordReset($userId, true);
        SecurityLogger::log('login_location_rejected', $userId, [
            'ip' => $location['ip'],
            'country' => $location['country_code'],
        ]);
        self::renderLoginLocationPage('rejected');
    }

    /**
     * Escupe una página HTML simple con el estado. No usa Response::json
     * porque se abre desde el cliente de email del usuario, no desde un
     * cliente de API.
     */
    private static function renderLoginLocationPage(string $state): void
    {
        $variants = [
            'invalid' => [
                'title' => 'Enlace inválido',
                'body' => 'El enlace que has abierto no es correcto. Revisa el correo y vuelve a intentarlo.',
                'tone' => 'warning',
            ],
            'expired' => [
                'title' => 'Enlace expirado',
                'body' => 'Este enlace ya fue usado o ha caducado. Si recibiste otro aviso más reciente, úsalo.',
                'tone' => 'warning',
            ],
            'confirmed' => [
                'title' => '¡Gracias!',
                'body' => 'Hemos registrado que reconoces este inicio de sesión. No tienes que hacer nada más.',
                'tone' => 'success',
            ],
            'rejected' => [
                'title' => 'Sesión cerrada',
                'body' => 'Cerramos la sesión sospechosa. La próxima vez que accedas a tu cuenta te pediremos cambiar la contraseña por seguridad.',
                'tone' => 'danger',
            ],
        ];

        $v = $variants[$state] ?? $variants['invalid'];
        $safeTitle = htmlspecialchars($v['title'], ENT_QUOTES, 'UTF-8');
        $safeBody  = htmlspecialchars($v['body'],  ENT_QUOTES, 'UTF-8');
        $color = [
            'success' => '#16a34a',
            'danger'  => '#dc2626',
            'warning' => '#d97706',
        ][$v['tone']] ?? '#0f172a';

        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{$safeTitle} — Reglado Group</title>
  <style>
    body { margin:0; padding:0; background:#f1f5f9; font-family:Arial,Helvetica,sans-serif; color:#0f172a; }
    .wrap { max-width:480px; margin:64px auto; padding:32px; background:#fff; border-radius:12px; box-shadow:0 4px 24px rgba(15,23,42,.08); }
    h1 { margin:0 0 16px; font-size:24px; color:{$color}; }
    p { line-height:1.55; margin:0 0 12px; color:#334155; }
    .footer { margin-top:32px; font-size:12px; color:#94a3b8; border-top:1px solid #e2e8f0; padding-top:16px; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>{$safeTitle}</h1>
    <p>{$safeBody}</p>
    <div class="footer">Reglado Group · Gestión de seguridad de cuenta</div>
  </div>
</body>
</html>
HTML;
        exit;
    }
```

- [ ] **Step 8.2: Registrar la ruta en index.php**

En `ApiLoging/index.php`, tras la ruta de `/auth/admin/set-ban` (o cualquier ruta existente, antes de la 404 final), añadir:

```php
if ($uri === '/auth/confirm-login-location' && $method === 'GET') {
    AuthController::confirmLoginLocation();
}
```

- [ ] **Step 8.3: Verificación sintáctica**

```bash
php -l c:/xampp/htdocs/Reglado/ApiLoging/controllers/AuthController.php 2>&1 | grep -v "Warning: Module"
php -l c:/xampp/htdocs/Reglado/ApiLoging/index.php 2>&1 | grep -v "Warning: Module"
```

Expected: ambos `No syntax errors detected`.

- [ ] **Step 8.4: Smoke test del endpoint**

Arrancar servidor si no está:
```bash
cd c:/xampp/htdocs/Reglado/ApiLoging && php -S localhost:8765 -t . > /tmp/phpserver.log 2>&1 &
echo "PID: $!"
sleep 1
```

Testear respuestas HTML:
```bash
# Sin token → invalid page
curl -s -o /tmp/resp.html -w "status=%{http_code} type=%{content_type}\n" http://localhost:8765/auth/confirm-login-location
grep -E "Enlace inválido|Enlace expirado" /tmp/resp.html

# Con token que no existe → expired page
curl -s -o /tmp/resp.html -w "status=%{http_code} type=%{content_type}\n" "http://localhost:8765/auth/confirm-login-location?token=abc&decision=me"
grep -E "Enlace expirado" /tmp/resp.html
```

Expected: status=200, type=text/html, y los grep matchean.

- [ ] **Step 8.5: Commit (referencia)**

```bash
git add ApiLoging/controllers/AuthController.php ApiLoging/index.php
git commit -m "feat(ApiLoging): endpoint /auth/confirm-login-location con HTML pages"
```

---

## Task 9: Frontend GrupoReglado — traducción "password reset required"

**Files:**
- Modify: `GrupoReglado/src/services/auth.js`

- [ ] **Step 9.1: Añadir la traducción**

En el mapa `AUTH_MESSAGE_MAP` de `GrupoReglado/src/services/auth.js` (alrededor de la línea 45, tras "password too weak"), añadir (el mapa ya tiene traducciones de single-session del parche anterior; solo falta ésta):

```js
  "password reset required": "Por seguridad, necesitas cambiar tu contraseña. Te hemos enviado un email con las instrucciones.",
```

Colocar tras la línea `"user_id is required": "Falta el identificador del usuario.",` o en cualquier posición válida dentro del objeto.

- [ ] **Step 9.2: Verificación sintáctica**

```bash
cd c:/xampp/htdocs/Reglado/GrupoReglado && node --check src/services/auth.js && echo OK
```

Expected: `OK`.

- [ ] **Step 9.3: Build para asegurar que no hay regresiones**

```bash
cd c:/xampp/htdocs/Reglado/GrupoReglado && npx vite build 2>&1 | tail -3
```

Expected: `built in Xs` sin errores.

- [ ] **Step 9.4: Commit (referencia)**

```bash
git add GrupoReglado/src/services/auth.js
git commit -m "feat(GrupoReglado): traducción password reset required"
```

---

## Task 10: Política de privacidad — GrupoReglado

**Files:**
- Modify: `GrupoReglado/src/pages/PoliticaPrivacidadView.vue` (o el componente que use)

- [ ] **Step 10.1: Localizar dónde se declara el tratamiento de datos**

```bash
grep -n "IP\|ubicación\|geolocalización\|seguridad" c:/xampp/htdocs/Reglado/GrupoReglado/src/pages/PoliticaPrivacidadView.vue | head -20
```

Identificar la sección donde se describen los datos que recoges (algo como "Datos que tratamos", "Información recopilada", etc.).

- [ ] **Step 10.2: Añadir párrafo nuevo**

En la sección correspondiente, añadir un bloque con el siguiente texto. El markup exacto dependerá de cómo esté estructurada la vista; si usa `<p>` puro, añadir:

```html
<p>
  Registramos la dirección IP y el país desde los que inicias sesión en tu cuenta.
  Usamos esta información para detectar accesos sospechosos; si detectamos un inicio
  de sesión desde un país distinto al habitual, te enviamos un correo electrónico
  para que confirmes que has sido tú. La IP se conserva únicamente asociada a ese
  evento de acceso y se gestiona de acuerdo con el resto de esta política.
</p>
```

Si el componente usa un array de secciones con título+contenido (tipo `{ title, paragraphs }`), añadir el párrafo al array correspondiente.

Si hay template separado (PrivacidadTemplate.vue compartido con otros proyectos), editar ahí.

- [ ] **Step 10.3: Build**

```bash
cd c:/xampp/htdocs/Reglado/GrupoReglado && npx vite build 2>&1 | tail -3
```

Expected: `built in Xs`.

- [ ] **Step 10.4: Commit (referencia)**

```bash
git add GrupoReglado/src/pages/PoliticaPrivacidadView.vue
git commit -m "docs(GrupoReglado): añade clausula de geolocalización en política de privacidad"
```

---

## Task 11: Validación end-to-end manual

Este task no modifica código. Verifica que el ciclo completo funciona.

**Prerrequisitos:** servidor PHP corriendo (`php -S localhost:8765 -t ApiLoging/`), BD con migraciones aplicadas, .mmdb en `ApiLoging/data/`, `.env` con credenciales SMTP ya funcionando (las hemos usado en otras features).

- [ ] **Step 11.1: Crear usuario de prueba**

```bash
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "DELETE FROM rate_limits WHERE scope_name LIKE 'login%';" 2>&1 | grep -v Warning

php -r '
$dsn = "mysql:host=127.0.0.1;dbname=regladousers;charset=utf8mb4";
$db = new PDO($dsn, "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$hash = password_hash("Test1234", PASSWORD_BCRYPT);
$db->prepare("INSERT INTO users (username, email, password, name, first_name, last_name, phone, role, is_email_verified, email_verified_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())")->execute(["test_geo_e2e", "<USER_EMAIL>", $hash, "Test Geo", "Test", "Geo", "600111222", "user"]);
echo "user_id: " . $db->lastInsertId() . "\n";
' 2>&1 | grep -v Warning
```

Sustituir `<USER_EMAIL>` por una dirección real a la que tengas acceso (tu propio email) para recibir la alerta. Guardar el id devuelto como `USER_ID`.

- [ ] **Step 11.2: Test — primer login (sin alerta)**

```bash
BASE="http://localhost:8765"
TOKEN=$(curl -s -X POST $BASE/auth/login -H 'Content-Type: application/json' -d '{"email":"<USER_EMAIL>","password":"Test1234"}' | python -c "import sys,json; print(json.load(sys.stdin).get('token',''))")
echo "Token OK: ${TOKEN:0:20}..."

# Verificar login_locations: debe haber 1 registro con status='neutral' (primer login, lastLegit=NULL).
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "SELECT id, country_code, status FROM login_locations WHERE user_id = $USER_ID ORDER BY id DESC LIMIT 3;" 2>&1 | grep -v Warning
```

Expected: 1 fila con `status=neutral`. `country_code` será NULL porque la IP es `127.0.0.1` (privada). No se envía email.

- [ ] **Step 11.3: Test — simular login desde país conocido (US)**

Para simular una IP pública, inyectaremos temporalmente un `X-Forwarded-For` que el código lee. Antes, comprobar si `Security::getClientIp` lo respeta:

```bash
grep -A 20 "getClientIp" c:/xampp/htdocs/Reglado/ApiLoging/utils/Security.php | head -25
```

Si Security::getClientIp NO lee headers forwarded, hacer un stub temporal: crear un script `inject_ip.php` en la raíz de ApiLoging:

```bash
cat > /tmp/stub_login.php << 'EOF'
<?php
// Stub para forzar una IP concreta en las pruebas. Llama al login vía HTTP
// con el header normal y le asigna REMOTE_ADDR="8.8.8.8" artificialmente.
$_SERVER["REMOTE_ADDR"] = "8.8.8.8";
include __DIR__ . "/index.php";
EOF
```

Alternativa más sencilla: insertar manualmente una fila simulada de login desde US en `login_locations` para que el siguiente login real (desde localhost, país=NULL) no dispare alerta, pero el siguiente simulado (ES) sí:

```bash
# Insertar una fila US con status=neutral como si fuese un login anterior real.
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "
  INSERT INTO login_locations (user_id, ip, country_code, country_name, user_agent, status, created_at)
  VALUES ($USER_ID, '8.8.8.8', 'US', 'United States', 'test-script', 'neutral', NOW() - INTERVAL 1 HOUR);
" 2>&1 | grep -v Warning
```

- [ ] **Step 11.4: Test — forzar login desde "España" con stub**

Editar temporalmente `ApiLoging/utils/Security.php` método `getClientIp` para devolver una IP española:

```bash
grep -n "getClientIp" c:/xampp/htdocs/Reglado/ApiLoging/utils/Security.php
```

Inspeccionar el método y añadir al principio de él una línea de debug:

```php
    public static function getClientIp(): string
    {
        if (!empty(getenv('FAKE_CLIENT_IP'))) { return (string) getenv('FAKE_CLIENT_IP'); }
        // ... resto del método original
    }
```

Luego arrancar el servidor con la env var:

```bash
# Matar el servidor anterior si está corriendo
lsof -i :8765 | awk 'NR>1 {print $2}' | xargs -r kill 2>/dev/null
sleep 1

cd c:/xampp/htdocs/Reglado/ApiLoging && FAKE_CLIENT_IP="80.58.0.1" php -S localhost:8765 -t . > /tmp/phpserver.log 2>&1 &
echo "PID: $!"
sleep 1
```

Login:
```bash
curl -s -X POST http://localhost:8765/auth/login -H 'Content-Type: application/json' -d '{"email":"<USER_EMAIL>","password":"Test1234"}' | python -m json.tool | head -5
```

Verificar:
```bash
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "SELECT id, country_code, status, token_hash IS NOT NULL AS has_token FROM login_locations WHERE user_id = $USER_ID ORDER BY id DESC LIMIT 5;" 2>&1 | grep -v Warning
```

Expected: última fila con `country_code='ES'`, `status='pending'`, `has_token=1`. En tu bandeja de entrada debe llegar el email de alerta "Nueva ubicación detectada" con dos botones.

- [ ] **Step 11.5: Test — clic en "No, no he sido yo"**

Abrir el email, copiar la URL del botón rojo (o construirla manualmente con el token). Pegarla en el navegador (o ejecutar con curl):

```bash
# Obtener el token del último pending
TOKEN_HASH=$(/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -N -e "SELECT token_hash FROM login_locations WHERE user_id = $USER_ID AND status = 'pending' ORDER BY id DESC LIMIT 1;" 2>&1 | tail -1)
echo "Hash del último pending: $TOKEN_HASH"

# No podemos derivar el plaintext desde el hash. Abrir el email y copiar la URL "No, no he sido yo".
# Alternativa para test: sustituir token_hash en BD por hash de un string conocido.
echo "Abre el email y clica el botón rojo."
```

Tras clicar:

```bash
# Verificar que el login_location pasó a rejected
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "SELECT id, status, token_used_at FROM login_locations WHERE user_id = $USER_ID ORDER BY id DESC LIMIT 1;" 2>&1 | grep -v Warning

# Verificar que la sesión se limpió
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "SELECT id, IFNULL(current_session_id, 'NULL') AS sid, require_password_reset FROM users WHERE id = $USER_ID;" 2>&1 | grep -v Warning
```

Expected: `status=rejected`, `token_used_at` no null, `sid=NULL`, `require_password_reset=1`.

- [ ] **Step 11.6: Test — siguiente login pide password reset**

```bash
curl -i -X POST http://localhost:8765/auth/login -H 'Content-Type: application/json' -d '{"email":"<USER_EMAIL>","password":"Test1234"}' 2>&1 | head -20
```

Expected: `HTTP/1.1 403` con body `{"error":"password reset required"}`. En tu bandeja debe llegar el email de reset (segundo email).

- [ ] **Step 11.7: Test — reset completa el ciclo**

Abrir el email de reset, clicar el botón → abre el frontend en `/restablecer-contrasena?token=...`. Completar con nueva password `NewPass99`.

Alternativa por curl:
```bash
# Necesitas capturar el plainToken del email manualmente y pegarlo aquí:
curl -s -X POST http://localhost:8765/auth/reset-password -H 'Content-Type: application/json' -d '{"token":"<PLAIN_TOKEN_DEL_EMAIL>","new_password":"NewPass99","new_password_confirmation":"NewPass99"}' | python -m json.tool
```

Verificar flag limpio:
```bash
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "SELECT require_password_reset FROM users WHERE id = $USER_ID;" 2>&1 | grep -v Warning
```

Expected: `require_password_reset=0`. Login con la nueva password debería devolver 200 normal.

- [ ] **Step 11.8: Cleanup**

```bash
# Borrar usuario de prueba (cascade borra login_locations)
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "DELETE FROM users WHERE email = '<USER_EMAIL>';" 2>&1 | grep -v Warning

# Revertir el stub en Security.php (quitar la línea FAKE_CLIENT_IP)
# ...editar manualmente o git checkout.

# Parar servidor
lsof -i :8765 | awk 'NR>1 {print $2}' | xargs -r kill 2>/dev/null
echo "Cleanup OK"
```

- [ ] **Step 11.9: No hay commit**

Task 11 es validación. El único cambio temporal (stub de FAKE_CLIENT_IP en Security.php) hay que revertirlo antes de cerrar la rama.

---

## Verificación final

Tras completar Tasks 1–11:

- [ ] `git status` limpia (excepto lo que queda de Tasks 1-10 por commitear).
- [ ] Todos los tests del Task 11 pasan.
- [ ] `php -l` sin errores en los 4 archivos PHP modificados de ApiLoging.
- [ ] `vite build` sin errores en GrupoReglado.
- [ ] `Security.php` NO tiene el stub temporal `FAKE_CLIENT_IP`.
- [ ] `.mmdb` no aparece en `git status` (está en .gitignore).
- [ ] Nueve commits pendientes de revisión en staging (Tasks 1-3, 5-10). Task 4 y 11 no commitean.

Si todo OK → feature lista para que el usuario la pruebe manualmente en navegador real antes de mergear. Deploy a Hostinger queda fuera del plan — ver sección "Rollout" del spec.
