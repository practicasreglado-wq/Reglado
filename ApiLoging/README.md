# ApiLoging

API de autenticacion en PHP raw con JWT, verificacion de email, recuperacion de contrasena y gestion basica del perfil.

## Requisitos

- PHP 8.1+
- MySQL o MariaDB
- Composer

## Instalacion

1. Instala dependencias:

```bash
composer install
```

2. Copia `\.env.example` a `\.env` y ajusta:

- conexion MySQL
- `JWT_SECRET`
- URLs del frontend
- SMTP si vas a enviar correos reales

3. Crea la base de datos:

Opcion A, instalacion limpia:

```sql
SOURCE database/create_regladousers.sql;
```

Opcion B, si ya tienes una base creada:

```sql
SOURCE database/schema.sql;
```

4. Arranca el servidor:

```bash
php -S localhost:8000
```

## Variables de entorno

Base de datos:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

JWT:

- `JWT_SECRET`
- `JWT_TTL_SECONDS`
- `JWT_ISSUER`

Verificacion de email:

- `EMAIL_VERIFY_URL_BASE`
- `EMAIL_VERIFY_REDIRECT_URL`
- `EMAIL_VERIFICATION_TTL_SECONDS`

Cambio de email:

- `EMAIL_CHANGE_VERIFY_URL_BASE`
- `EMAIL_CHANGE_REDIRECT_URL`

Recuperacion de contrasena:

- `PASSWORD_RESET_URL_BASE`

Correo:

- `MAIL_DRIVER` (`smtp`, `mail`, `log`)
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_ENCRYPTION`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM`
- `MAIL_FROM_NAME`

## Endpoints

Publicos:

- `POST /auth/register`
- `POST /auth/login`
- `GET /auth/verify-email?token=...`
- `POST /auth/resend-verification`
- `POST /auth/request-password-reset`
- `POST /auth/reset-password`

Protegidos con `Authorization: Bearer <token>`:

- `GET /auth/me`
- `POST /auth/logout`
- `POST /auth/update-username`
- `POST /auth/update-name`
- `POST /auth/update-phone`
- `POST /auth/request-email-change`
- `POST /auth/change-password`
- `GET /api/profile`

Confirmacion de cambio de email:

- `GET /auth/confirm-email-change?token=...`

## Flujos soportados

Registro:

```json
{
  "username": "juan123",
  "first_name": "Juan",
  "last_name": "Perez",
  "email": "juan@email.com",
  "phone": "+34 600 000 000",
  "password": "123456",
  "password_confirmation": "123456"
}
```

Respuesta:

- `201` usuario creado y correo de verificacion enviado
- `409` email o username ya existen

Login:

```json
{
  "email": "juan@email.com",
  "password": "123456"
}
```

Si la cuenta no esta verificada, responde `403`.

Recuperacion de contrasena, solicitud:

```json
{
  "email": "juan@email.com"
}
```

Recuperacion de contrasena, cambio final:

```json
{
  "token": "token-del-correo",
  "new_password": "nueva-clave",
  "new_password_confirmation": "nueva-clave"
}
```

Cambio de username:

```json
{
  "username": "nuevo_username"
}
```

Cambio de nombre y apellido:

```json
{
  "first_name": "Juan",
  "last_name": "Garcia"
}
```

Cambio de telefono:

```json
{
  "phone": "+34 600 111 222"
}
```

Solicitud de cambio de email:

```json
{
  "new_email": "nuevo@email.com"
}
```

Cambio de contrasena autenticado:

```json
{
  "current_password": "actual",
  "new_password": "nueva",
  "new_password_confirmation": "nueva"
}
```

## Base de datos

El script `database/create_regladousers.sql` crea la base `regladousers` con:

- `users`
- `email_verification_tokens`
- `email_change_tokens`
- `password_reset_tokens`
- `revoked_tokens`

Si ya tienes una base creada y trabajas por migraciones, usa los scripts de `database/` segun el cambio que necesites aplicar.

## Correo en local

Para pruebas locales puedes usar:

- `MAIL_DRIVER=log` para escribir correos en `storage/mail.log`
- `MAIL_DRIVER=smtp` para Gmail con App Password

Configuracion tipica de Gmail:

- `MAIL_HOST=smtp.gmail.com`
- `MAIL_PORT=587`
- `MAIL_ENCRYPTION=tls`
- `MAIL_USERNAME=tu_cuenta@gmail.com`
- `MAIL_PASSWORD=tu_app_password`
- `MAIL_FROM=tu_cuenta@gmail.com`

## Integracion con frontends

Esta API esta pensada para ser usada por varias webs del ecosistema Reglado:

- `GrupoReglado`
- `RegladoEnergy`
- futuras apps

Cada frontend obtiene el JWT desde esta API y lo envia en `Authorization: Bearer ...`. Cada backend de producto debe validar firma y permisos por su cuenta.
