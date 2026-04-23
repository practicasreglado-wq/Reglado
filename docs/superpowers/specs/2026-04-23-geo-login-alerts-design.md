# Geo Login Alerts

**Fecha**: 2026-04-23
**Proyecto**: ApiLoging (backend único; los frontends del ecosistema reciben el efecto de forma transparente).
**Estado**: Diseño aprobado.
**Fuera de alcance**: `Inmobiliaria_Reglados` y `RegladoBienesRaices`.

## Contexto

Actualmente un atacante con credenciales válidas puede iniciar sesión desde cualquier lugar sin que el usuario legítimo se entere más allá del kick-old de single-session. Con la política de rate limit simplificada (solo `login_lockout` por fallos), un atacante que conoce la contraseña nunca dispara un bloqueo. Necesitamos una señal fuera de banda que avise al usuario y le permita cortar el acceso.

## Objetivo

Detectar logins desde un **país distinto al último login legítimo** del usuario, avisarle por email con dos acciones claras (sí fui yo / no fui yo), y si elige "no fui yo":

1. Matar la sesión del atacante en el mismo instante.
2. Marcar la cuenta para que el próximo login exija cambio de contraseña antes de continuar.

## Decisiones clave

| Decisión | Elección | Razón |
| --- | --- | --- |
| Heurística del trigger | **A** — cambio de país vs último login legítimo. | Evita que un país de confianza se use para atacar. |
| Geolocalización | **MaxMind GeoLite2-Country** (archivo .mmdb local). | Sin terceros, sin rate limits, GDPR-friendly, multiplataforma (Linux/Hostinger OK). |
| Acción al rechazar | **B** — matar sesión + forzar reset de contraseña. | Única acción que neutraliza credenciales comprometidas; ban de IP sería security theater. |
| TTL del token de confirmación | **7 días**. | Un viaje o un fin de semana no invalida el botón. |
| Primer login de un usuario | **Sin alerta**. | No hay historial contra el que comparar; el usuario acaba de registrarse y lo sabe. |
| Alerta al admin | **No**, solo `security_events` local. | Evitar ruido; el admin puede revisar eventos si sospecha algo. |

## Cambios en schema

Archivo nuevo: `ApiLoging/database/migrate_login_locations.sql` (se incorpora también al `schema.sql` canónico para instalaciones limpias).

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

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS require_password_reset TINYINT(1) NOT NULL DEFAULT 0;
```

Valores del campo `status`:

| Valor | Significado |
| --- | --- |
| `neutral` | País igual al último legítimo. Sin alerta enviada. |
| `pending` | País distinto, alerta enviada, usuario aún no respondió. |
| `confirmed` | Usuario clicó "sí, fui yo". Cuenta como login legítimo. |
| `rejected` | Usuario clicó "no, no fui yo". Excluido del cálculo del "último legítimo". |

Un registro con `status = 'pending'` cuyo `token_expires_at` ya pasó sin que el usuario clique se queda tal cual (no lo convertimos a "expired" ni lo borramos). Como la consulta del último legítimo filtra por `status IN ('neutral','confirmed')`, los pending obsoletos no afectan a nada.

## Dependencias nuevas

### Composer

Añadir al `composer.json`:
```json
"require": {
  "geoip2/geoip2": "^3.0"
}
```

### Fichero .mmdb

- Ruta: `ApiLoging/data/GeoLite2-Country.mmdb` (~4 MB).
- **No va al repo.** Añadir `ApiLoging/data/*.mmdb` al `.gitignore`.
- Descarga: panel de maxmind.com → "Download databases" → fila "GeoLite Country" → "Download GZIP" → extraer.
- En local (dev): copiar manualmente a la ruta anterior.
- En Hostinger (producción): subir por FTP a la misma ruta relativa.

### Actualización mensual (opcional, diferida)

Documentar en el README de `ApiLoging/data/` cómo refrescar el fichero (manualmente o con un script usando la license key). No bloquea MVP; datos caducos de país son de baja criticidad.

## Módulos nuevos

### `services/GeoLocationService.php`

```php
class GeoLocationService
{
    private static ?GeoIp2\Database\Reader $reader = null;

    public static function lookup(string $ip): ?array
    {
        if (self::$reader === null) {
            $path = __DIR__ . '/../data/GeoLite2-Country.mmdb';
            if (!is_readable($path)) {
                error_log('GEOIP_DB_MISSING path=' . $path);
                return null;
            }
            try {
                self::$reader = new GeoIp2\Database\Reader($path);
            } catch (Throwable $e) {
                error_log('GEOIP_DB_OPEN_FAIL message=' . $e->getMessage());
                return null;
            }
        }

        try {
            $record = self::$reader->country($ip);
            return [
                'country_code' => $record->country->isoCode,
                'country_name' => $record->country->name,
            ];
        } catch (Throwable $e) {
            // IPs privadas/localhost/inválidas caen aquí.
            return null;
        }
    }
}
```

Degradación grácil: si el `.mmdb` no está o la IP no resuelve, devuelve `null`. El caller registra la ubicación con `country_code = NULL` y no dispara alerta.

### Modelo `User` — métodos nuevos

```php
// Último país con status legítimo (neutral o confirmed).
public static function getLastLegitLoginCountry(int $userId): ?string;

// Inserta un login_locations; devuelve el id insertado.
public static function recordLoginLocation(
    int $userId, string $ip, ?string $countryCode, ?string $countryName,
    string $userAgent, string $status,
    ?string $tokenHash = null, ?string $tokenExpiresAt = null
): int;

public static function findLoginLocationByTokenHash(string $tokenHash): ?array;

public static function updateLoginLocationStatus(int $locationId, string $status): void;

public static function setRequirePasswordReset(int $userId, bool $required): void;
```

## Flujo de login (modificado)

En `AuthController::login`, tras la emisión del JWT (y antes del `Response::json`), añadir una llamada a un helper privado:

```php
// (existente: password_verify, email_verified, banned, rate limit, rotateSession, JwtService::generate)

self::handleLoginLocation((int) $user['id'], Security::getClientIp(), $_SERVER['HTTP_USER_AGENT'] ?? '');

Response::json([ ...payload existente... ]);
```

Helper privado:

```php
private static function handleLoginLocation(int $userId, string $ip, string $userAgent): void
{
    // Envuelvo TODO en try/catch para que ningún fallo de geo/mail/config rompa
    // el login: a estas alturas el JWT ya fue emitido al usuario y cualquier
    // Response::json de error mataría el script antes del envío. La alerta es
    // best-effort; si no se puede enviar, registramos como neutral y seguimos.
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
            // Config ausente: no podemos enviar alerta con links válidos.
            // Degradamos a neutral; no queremos registros "pending" que nadie podrá confirmar.
            error_log('LOGIN_ALERT_URL_BASE missing — recording as neutral');
            User::recordLoginLocation($userId, $ip, $countryCode, $countryName, $userAgent, 'neutral');
            return;
        }

        [$plainToken, $tokenHash, $expiresAt] = self::buildLoginAlertToken();
        User::recordLoginLocation(
            $userId, $ip, $countryCode, $countryName, $userAgent,
            'pending', $tokenHash, $expiresAt
        );

        $user = User::findById($userId);
        $yesUrl = self::appendQuery($alertUrlBase, ['token' => $plainToken, 'decision' => 'me']);
        $noUrl  = self::appendQuery($alertUrlBase, ['token' => $plainToken, 'decision' => 'not-me']);
        MailService::sendLoginAlert($user, $countryName, $ip, $yesUrl, $noUrl);
        SecurityLogger::log('login_alert_sent', $userId, [
            'country' => $countryCode, 'ip' => $ip,
        ]);
    } catch (Throwable $e) {
        // Ojo: NO llamar a Response::json aquí. Solo loggear.
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

/**
 * Resuelve LOGIN_ALERT_URL_BASE con fallback a localhost en APP_ENV=local.
 * Devuelve null si falta en entornos no-local; el caller decide si
 * degradar la alerta (sin Response::json para no matar el login).
 */
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

**Nota de diseño**: el `handleLoginLocation` va síncrono, antes de `Response::json`. Esto significa que en el caso "nuevo país" el login se retrasa lo que tarde SMTP en enviar el email (típicamente 200-800ms). Para el usuario legítimo regular (mismo país) no hay mail, no hay retraso medible.

Si más adelante esto se nota en producción, se puede mover a un post-response con `fastcgi_finish_request()` o a una cola asíncrona. MVP: síncrono.

La alerta es **best-effort**: si el email falla, el .mmdb está caído, o la env var no está configurada, el login **no se bloquea**. El objetivo es añadir una capa de defensa, no convertir una caída de mail/geo/config en una caída de login.

## Email de alerta

`MailService::sendLoginAlert(array $user, ?string $countryName, string $ip, string $yesUrl, string $noUrl): bool`

El método recibe las dos URLs pre-construidas por el caller. El MailService no conoce la estructura del endpoint de confirmación, solo inserta los links en el template HTML.

Template HTML (ejemplo simplificado):

```
Asunto: Nueva ubicación detectada en tu cuenta Reglado

Hola, {nombre}:

Detectamos un inicio de sesión en tu cuenta desde un país distinto al habitual:

  País:      {countryName} ({countryCode})
  IP:        {ip}
  Fecha:     {fechaHora CET}

¿Has sido tú?

[ Sí, he sido yo ]    [ No, no he sido yo ]

Si no fuiste tú, haz clic en "No, no he sido yo" cuanto antes. Cerraremos
la sesión sospechosa y te pediremos cambiar la contraseña la próxima vez
que accedas.

Si sí fuiste tú, no necesitas hacer nada: esto solo es un aviso.

Este aviso se envía cuando detectamos logins desde un país nuevo.
Gestionado por Reglado Group.
```

Las dos URLs las construye `handleLoginLocation` (ver sección anterior) a partir de `LOGIN_ALERT_URL_BASE`:
- `{LOGIN_ALERT_URL_BASE}?token={plainToken}&decision=me`
- `{LOGIN_ALERT_URL_BASE}?token={plainToken}&decision=not-me`

`LOGIN_ALERT_URL_BASE` es una env var nueva. En APP_ENV=local tiene fallback a `http://localhost:8000/auth/confirm-login-location`. En staging/production debe estar explícitamente definida (p. ej. `https://auth.regladogroup.com/auth/confirm-login-location`); si falta, `resolveLoginAlertBaseUrl` devuelve null y la alerta se degrada a 'neutral' silenciosamente (el login no se rompe).

Si `countryName` es null (no resolvió), usamos el literal "desconocido" en el texto.

## Endpoint `GET /auth/confirm-login-location`

Registrado en `index.php`:

```php
if ($uri === '/auth/confirm-login-location' && $method === 'GET') {
    AuthController::confirmLoginLocation();
}
```

Lógica:

```php
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
        && ($location['status'] !== 'pending'
            || $location['token_used_at'] !== null
            || strtotime((string) $location['token_expires_at']) < time());

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
        'ip' => $location['ip'], 'country' => $location['country_code'],
    ]);
    self::renderLoginLocationPage('rejected');
}
```

`renderLoginLocationPage($state)` escupe HTML sencillo con el logo Reglado y un mensaje según el estado:

| Estado | Título | Cuerpo |
| --- | --- | --- |
| `invalid` | Enlace inválido | "El enlace que has abierto no es correcto." |
| `expired` | Enlace expirado | "Este enlace ya fue usado o ha caducado." |
| `confirmed` | Gracias por confirmar | "Hemos registrado que reconoces este inicio de sesión. No tienes que hacer nada más." |
| `rejected` | Sesión cerrada | "Cerramos la sesión sospechosa. La próxima vez que accedas, te pediremos cambiar la contraseña." |

Escribe HTTP `text/html` directamente con `exit` (no `Response::json`). Endpoint abierto al email del usuario, sin JWT.

## Enforcement del `require_password_reset` en login

En `AuthController::login`, tras el check de ban y antes de emitir JWT:

```php
if ((int) ($user['require_password_reset'] ?? 0) === 1) {
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
```

En `AuthController::resetPassword`, tras `updatePasswordHash` y `markPasswordResetAsUsed`, limpiar el flag:

```php
User::setRequirePasswordReset((int) $user['id'], false);
```

Frontend `GrupoReglado/src/services/auth.js` — añadir traducción:
```js
"password reset required": "Por seguridad, necesitas cambiar tu contraseña. Te hemos enviado un email con las instrucciones.",
```

`LoginView.vue` ya muestra automáticamente cualquier error traducido; no requiere cambios adicionales.

## Política de privacidad

Añadir a `PrivacidadTemplate.vue` (GrupoReglado y demás proyectos que lo usan) un párrafo en la sección de datos tratados:

> Registramos la IP y el país desde los que inicias sesión. Los usamos para detectar accesos sospechosos y, si detectamos un inicio de sesión desde un país distinto al habitual, te enviamos un correo para que confirmes que has sido tú. La IP se conserva solo asociada a ese evento de acceso.

Esta edición no es bloqueante del deploy pero conviene hacerla en la misma release para mantener el cumplimiento GDPR.

## Arquitectura — unidades y límites

| Unidad | Responsabilidad | Depende de |
| --- | --- | --- |
| `GeoLocationService` | Traducir IP a (country_code, country_name). | `.mmdb`, geoip2/geoip2. |
| `User` (métodos nuevos) | CRUD de `login_locations` y `users.require_password_reset`. | `Database`. |
| `AuthController::handleLoginLocation` | Orquestar: geolocalizar → decidir → persistir → mail. | `GeoLocationService`, `User`, `MailService`. |
| `AuthController::confirmLoginLocation` | Procesar el clic del email (me / not-me). | `User`, `SecurityLogger`. |
| `MailService::sendLoginAlert` | Enviar el HTML con los dos links. | SMTP existente. |

Cada unidad es testable en aislamiento: `GeoLocationService::lookup` es puro (IP → array), el controller orquesta sin lógica de negocio propia más allá del flujo, el modelo hace SQL directo.

## Rollout

1. Subir código a Hostinger (vía ZIP/FTP según tu flujo habitual).
2. Aplicar `migrate_login_locations.sql` contra la BBDD de producción.
3. Subir `GeoLite2-Country.mmdb` por FTP a `ApiLoging/data/`.
4. `composer install` en el servidor (o subir el `vendor/` pre-instalado si no tienes composer allí).
5. Configurar la env var `LOGIN_ALERT_URL_BASE=https://auth.regladogroup.com/auth/confirm-login-location` (o la URL real) en `.env`.
6. Test: desde una IP distinta (VPN), hacer login con un usuario de test. Verificar recepción del email.
7. Monitorizar `security_events` la primera semana: `WHERE event_type IN ('login_alert_sent','login_location_confirmed','login_location_rejected')`.

## Cómo testarlo manualmente (local)

1. Copiar `GeoLite2-Country.mmdb` a `ApiLoging/data/`.
2. `composer install` en `ApiLoging/`.
3. Aplicar migración contra BBDD local.
4. Arrancar `php -S localhost:8765 -t ApiLoging/`.
5. Crear un usuario de test (igual que hicimos en los tasks anteriores).
6. Login desde "España" (localhost → IP privada → country_code NULL → status 'neutral', sin alerta): OK, solo registro.
7. Forzar una IP pública: modificar temporalmente `Security::getClientIp` para devolver `8.8.8.8` (US). Nuevo login → como no hay último legítimo, status queda 'neutral' igual (la regla exige QUE HAYA un último legítimo para disparar alerta; el primer login no dispara).
8. Cambiar el stub a `217.76.144.0` (IP española, ES). Login → 'neutral' (mismo país que el anterior si el anterior era US… espera, no coincide).

   Aclaración: la regla es `isNewCountry = countryCode != null && lastLegitCountry != null && countryCode != lastLegitCountry`. En el primer login con country_code no-NULL, `lastLegitCountry` es NULL → no dispara. A partir del segundo login con country distinto, dispara.

9. Simular login desde dos países distintos de forma consecutiva:
   - Login 1: IP stub = `8.8.8.8` → US. `lastLegit = NULL`. Se guarda 'neutral' con country_code='US'.
   - Login 2: IP stub = `217.76.144.0` → ES. `lastLegit = 'US'`. `'ES' != 'US'` → alerta enviada, record 'pending'.
10. Abrir el email, clicar "No, no he sido yo". Verificar:
    - La fila de `login_locations` pasa a `rejected`.
    - `users.current_session_id` es NULL.
    - `users.require_password_reset` es 1.
    - Un próximo login con password correcta devuelve 403 `password reset required` y se envía email de reset.

## Cómo NO falsos-positivear

- El fichero `.mmdb` se refresca de vez en cuando, pero country_code de una IP cambia muy rara vez. Incluso con un fichero de 6 meses estás bien.
- Si un usuario viaja con VPN de otro país y tiene sesión abierta, al siguiente login recibirá alerta. Es el comportamiento deseado.
- Si un usuario cambia de ISP sin viajar (p.ej. de fibra a móvil), el país sigue siendo el mismo. No hay alerta.
- Si el `.mmdb` falla o la IP no resuelve (IPs privadas en dev local), status queda 'neutral'. No hay alerta ni error al usuario.

## Archivos tocados (resumen)

**Backend (ApiLoging):**
- `ApiLoging/database/migrate_login_locations.sql` (nuevo)
- `ApiLoging/database/schema.sql` (actualizar con columna y tabla)
- `ApiLoging/composer.json` (añadir geoip2/geoip2)
- `ApiLoging/.gitignore` (nuevo, excluir `data/*.mmdb`)
- `ApiLoging/data/` (directorio nuevo; `.mmdb` no versionado)
- `ApiLoging/services/GeoLocationService.php` (nuevo)
- `ApiLoging/models/User.php` (métodos nuevos)
- `ApiLoging/controllers/AuthController.php` (login hook, endpoint nuevo, reset check)
- `ApiLoging/index.php` (ruta nueva)
- `ApiLoging/services/MailService.php` (método `sendLoginAlert`)

**Frontend:**
- `GrupoReglado/src/services/auth.js` (traducción "password reset required")
- `GrupoReglado/src/pages/PoliticaPrivacidadView.vue` (texto nuevo) — o el componente PrivacidadTemplate equivalente.
- (Resto de frontends usan el mismo texto legal si comparten `PrivacidadTemplate`.)

**Documentación:**
- `ApiLoging/data/README.md` (explicar qué va ahí y cómo actualizar).
