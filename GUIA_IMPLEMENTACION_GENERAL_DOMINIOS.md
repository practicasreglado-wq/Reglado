# Guia De Implementacion En Dominios Reales

Esta guia describe como pasar el ecosistema Reglado desde local a produccion con dominios separados.

## 1. Dominios objetivo

- `GrupoReglado`: `https://gruporeglado.com`
- `ApiLoging`: alojada dentro del mismo dominio de `GrupoReglado`
- `Inmobiliaria_Reglados`: `https://realstate.com`
- `RegladoEnergy`: `https://regladoenergy.com`

## 2. Arquitectura final esperada

### 2.1 GrupoReglado y ApiLoging

La idea correcta para produccion es:

- `GrupoReglado` sirve el frontend en `https://gruporeglado.com`
- `ApiLoging` queda accesible en el mismo dominio, por ejemplo:
  - `https://gruporeglado.com/api`
  - o `https://gruporeglado.com/auth/...`

Si mantienes `ApiLoging` en el mismo dominio que `GrupoReglado`, reduces problemas de CORS y simplificas enlaces de verificacion y recuperacion.

### 2.2 Productos externos

- `RegladoEnergy` en `https://regladoenergy.com`
- `Inmobiliaria_Reglados` en `https://realstate.com`

Ambos seguiran redirigiendo a `GrupoReglado` para login y registro.

## 3. URLs recomendadas

Para dejarlo consistente, la recomendacion es esta:

- frontend `GrupoReglado`: `https://gruporeglado.com`
- API auth: `https://gruporeglado.com`

Es decir:
- login: `https://gruporeglado.com/auth/login`
- registro: `https://gruporeglado.com/auth/register`
- verify email: `https://gruporeglado.com/auth/verify-email`

Y las pantallas del frontend:
- `https://gruporeglado.com/login`
- `https://gruporeglado.com/registro`
- `https://gruporeglado.com/verificacion-exitosa`
- `https://gruporeglado.com/restablecer-contrasena`
- `https://gruporeglado.com/configuracion`

## 4. Cambios necesarios por proyecto

## 4.1 ApiLoging

Archivo:
- [ApiLoging/.env](c:\xampp\htdocs\Reglado\ApiLoging\.env)

Debes ajustar como minimo:

```env
APP_ENV=production

DB_HOST=TU_HOST_MYSQL
DB_PORT=3306
DB_NAME=regladousers
DB_USER=TU_USUARIO
DB_PASS=TU_PASSWORD

JWT_SECRET=TU_SECRETO_LARGO_Y_UNICO
JWT_TTL_SECONDS=86400
JWT_ISSUER=gruporeglado.com

CORS_ALLOWED_ORIGINS=https://gruporeglado.com,https://regladoenergy.com,https://realstate.com
REDIRECT_ALLOWED_ORIGINS=https://gruporeglado.com,https://regladoenergy.com,https://realstate.com

EMAIL_VERIFY_URL_BASE=https://gruporeglado.com/auth/verify-email
EMAIL_VERIFY_REDIRECT_URL=https://gruporeglado.com/verificacion-exitosa
EMAIL_CHANGE_VERIFY_URL_BASE=https://gruporeglado.com/auth/confirm-email-change
EMAIL_CHANGE_REDIRECT_URL=https://gruporeglado.com/configuracion
PASSWORD_RESET_URL_BASE=https://gruporeglado.com/restablecer-contrasena

MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=TU_CORREO
MAIL_PASSWORD=TU_APP_PASSWORD
MAIL_FROM=TU_CORREO
MAIL_FROM_NAME=Grupo Reglado
```

Notas:
- `JWT_SECRET` debe ser fuerte y no cambiar entre proyectos que validan el token.
- `CORS_ALLOWED_ORIGINS` debe incluir solo los dominios reales.
- `REDIRECT_ALLOWED_ORIGINS` debe incluir cualquier dominio al que permitas volver tras login o verificacion.

## 4.2 GrupoReglado

Archivo:
- [GrupoReglado/.env](c:\xampp\htdocs\Reglado\GrupoReglado\.env)

Debes poner:

```env
VITE_AUTH_API_URL=https://gruporeglado.com
VITE_REGLADO_REALSTATE_URL=https://realstate.com
VITE_REGLADO_ENERGY_URL=https://regladoenergy.com
VITE_REGLADO_MAPAS_URL=#
VITE_REGLADO_ENPROCESO_URL=#
```

Notas:
- `VITE_AUTH_API_URL` debe apuntar a la URL publica real de `ApiLoging`.
- Si `Mapas` o `EnProceso` tienen dominio luego, cambia `#` por la URL real.

## 4.3 RegladoEnergy

Archivo:
- [RegladoEnergy/.env](c:\xampp\htdocs\Reglado\RegladoEnergy\.env)

Debes poner:

```env
VITE_AUTH_API_URL=https://gruporeglado.com
VITE_GRUPO_REGLADO_BASE_URL=https://gruporeglado.com
VITE_GRUPO_REGLADO_LOGIN_PATH=/login
VITE_GRUPO_REGLADO_REGISTER_PATH=/registro
VITE_GRUPO_REGLADO_SETTINGS_PATH=/configuracion

VITE_CONTACT_ENDPOINT=https://regladoenergy.com/BACKEND/contact.php
```

Notas:
- `VITE_GRUPO_REGLADO_BASE_URL` es la URL a la que Energy redirige para login y registro.
- `VITE_CONTACT_ENDPOINT` debe apuntar a la URL real del backend PHP de Energy.

### Backend de Energy

Archivo:
- `RegladoEnergy/BACKEND/.env` si lo usas
- o la configuracion equivalente que lea `JWT_SECRET`

Debes garantizar:

```env
APP_ENV=production
JWT_SECRET=EL_MISMO_JWT_SECRET_DE_APILOGING
CORS_ALLOWED_ORIGINS=https://regladoenergy.com,https://gruporeglado.com
```

Ademas:
- el backend admin y contacto debe estar publicado bajo el dominio real de Energy
- si usas rutas distintas a `/BACKEND/...`, actualiza `VITE_CONTACT_ENDPOINT`

## 4.4 Inmobiliaria_Reglados

Archivo:
- [Inmobiliaria_Reglados/.env](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\.env)

Debes poner:

```env
VITE_AUTH_API_URL=https://gruporeglado.com
VITE_GRUPO_REGLADO_BASE_URL=https://gruporeglado.com
VITE_GRUPO_REGLADO_LOGIN_PATH=/login
VITE_GRUPO_REGLADO_REGISTER_PATH=/registro
VITE_INMOBILIARIA_BACKEND_BASE=https://realstate.com/backend
```

### Backend de Inmobiliaria

El backend de inmobiliaria debe:
- validar JWT usando el mismo `JWT_SECRET`
- aceptar peticiones desde `https://realstate.com`
- si hay endpoints llamados desde frontend, permitir `Origin` de su dominio real

Si mantienes la misma estructura:
- `https://realstate.com/backend/get_user_data.php`
- `https://realstate.com/backend/save_preferences.php`

## 5. Parametros que deben coincidir entre proyectos

Estos valores tienen que estar coordinados:

### 5.1 JWT_SECRET

Debe ser el mismo en:
- `ApiLoging`
- backend de `RegladoEnergy`
- backend de `Inmobiliaria_Reglados`

Si no coincide:
- los productos no podran validar el token
- fallaran callbacks, panel admin o carga de perfil

### 5.2 URLs de GrupoReglado

Deben coincidir en:
- `RegladoEnergy`
- `Inmobiliaria_Reglados`

Valor esperado:

```env
VITE_GRUPO_REGLADO_BASE_URL=https://gruporeglado.com
```

### 5.3 Redirect allowlist

En `ApiLoging`, `REDIRECT_ALLOWED_ORIGINS` debe incluir:
- `https://gruporeglado.com`
- `https://regladoenergy.com`
- `https://realstate.com`

Si falta uno:
- el login o la verificacion pueden no devolver al proyecto correcto

### 5.4 CORS allowlist

En `ApiLoging`, `CORS_ALLOWED_ORIGINS` debe incluir:
- `https://gruporeglado.com`
- `https://regladoenergy.com`
- `https://realstate.com`

## 6. Enlaces de correo en produccion

Los correos deben abrir siempre paginas publicas reales:

### Verificacion de cuenta

- enlace de la API: `https://gruporeglado.com/auth/verify-email?token=...`
- redireccion final del usuario: `https://gruporeglado.com/verificacion-exitosa?token=...`

### Cambio de email

- enlace de la API: `https://gruporeglado.com/auth/confirm-email-change?token=...`
- redireccion final: `https://gruporeglado.com/configuracion?token=...`

### Recuperacion de contrasena

- pagina de destino: `https://gruporeglado.com/restablecer-contrasena?token=...`

## 7. Orden recomendado de despliegue

1. Desplegar `ApiLoging` en `gruporeglado.com`
2. Verificar que `https://gruporeglado.com/auth/me` responde bien
3. Desplegar `GrupoReglado`
4. Verificar:
   - login
   - registro
   - correo de verificacion
   - recuperacion de contrasena
5. Desplegar `RegladoEnergy`
6. Verificar callback desde `GrupoReglado`
7. Desplegar `Inmobiliaria_Reglados`
8. Verificar callback y carga de datos locales

## 8. Checklist de pruebas en produccion

### GrupoReglado

- el registro envia correo
- el enlace de verificacion abre `gruporeglado.com`
- el login funciona
- la recuperacion de contrasena funciona
- configuracion actualiza usuario

### RegladoEnergy

- `Iniciar sesion / registrarse` redirige a `gruporeglado.com/login`
- tras login correcto vuelve a `regladoenergy.com`
- el usuario queda logeado
- si es admin, ve el panel admin
- el formulario de contacto funciona

### Inmobiliaria

- `Iniciar sesion` redirige a `gruporeglado.com/login`
- tras login correcto vuelve a `realstate.com`
- el usuario queda logeado
- `dashboard` y `profile` cargan datos
- `iduser` se relaciona correctamente con el usuario de auth

## 9. Problemas tipicos en despliegue

### El login no vuelve al producto

Revisar:
- `VITE_GRUPO_REGLADO_BASE_URL`
- `returnTo`
- `REDIRECT_ALLOWED_ORIGINS`

### El producto vuelve pero no reconoce la sesion

Revisar:
- `JWT_SECRET` igual en todos los backends
- `VITE_AUTH_API_URL` correcto
- `Authorization: Bearer ...` llegando bien

### Error CORS

Revisar:
- `CORS_ALLOWED_ORIGINS` de `ApiLoging`
- allowlist del backend PHP del producto
- que uses `https`, no mezclar `http` y `https`

### El correo llega con enlaces malos

Revisar:
- `EMAIL_VERIFY_URL_BASE`
- `EMAIL_VERIFY_REDIRECT_URL`
- `EMAIL_CHANGE_VERIFY_URL_BASE`
- `EMAIL_CHANGE_REDIRECT_URL`
- `PASSWORD_RESET_URL_BASE`

## 10. Configuracion minima resumida

### ApiLoging

```env
APP_ENV=production
JWT_ISSUER=gruporeglado.com
CORS_ALLOWED_ORIGINS=https://gruporeglado.com,https://regladoenergy.com,https://realstate.com
REDIRECT_ALLOWED_ORIGINS=https://gruporeglado.com,https://regladoenergy.com,https://realstate.com
EMAIL_VERIFY_URL_BASE=https://gruporeglado.com/auth/verify-email
EMAIL_VERIFY_REDIRECT_URL=https://gruporeglado.com/verificacion-exitosa
EMAIL_CHANGE_VERIFY_URL_BASE=https://gruporeglado.com/auth/confirm-email-change
EMAIL_CHANGE_REDIRECT_URL=https://gruporeglado.com/configuracion
PASSWORD_RESET_URL_BASE=https://gruporeglado.com/restablecer-contrasena
```

### GrupoReglado

```env
VITE_AUTH_API_URL=https://gruporeglado.com
VITE_REGLADO_REALSTATE_URL=https://realstate.com
VITE_REGLADO_ENERGY_URL=https://regladoenergy.com
```

### RegladoEnergy

```env
VITE_AUTH_API_URL=https://gruporeglado.com
VITE_GRUPO_REGLADO_BASE_URL=https://gruporeglado.com
VITE_CONTACT_ENDPOINT=https://regladoenergy.com/BACKEND/contact.php
```

### Inmobiliaria

```env
VITE_AUTH_API_URL=https://gruporeglado.com
VITE_GRUPO_REGLADO_BASE_URL=https://gruporeglado.com
VITE_INMOBILIARIA_BACKEND_BASE=https://realstate.com/backend
```
