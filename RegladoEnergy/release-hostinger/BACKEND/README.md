# RegladoEnergy BACKEND

Backend PHP auxiliar de `RegladoEnergy`. Gestiona el formulario de contacto con adjuntos y el acceso admin a las solicitudes recibidas.

## Requisitos

- PHP 8.1+
- MySQL o MariaDB
- XAMPP, Apache o cualquier servidor PHP

## Archivos principales

- `contact.php`: recibe el formulario de contacto
- `admin_list.php`: devuelve el listado para administracion
- `admin_download.php`: descarga archivos adjuntos
- `auth.php`: valida JWT y permisos de admin
- `db.php`: conexion a la base de datos
- `sql/facturas.sql`: crea la base y la tabla de solicitudes

## Instalacion

1. Crea la base de datos:

```sql
SOURCE sql/facturas.sql;
```

2. Revisa credenciales en `db.php`.

3. Crea `\.env` a partir de `\.env.example`:

```env
JWT_SECRET=tu_mismo_secret_de_apiloging
```

Ese valor debe coincidir exactamente con `JWT_SECRET` de `ApiLoging`.

4. Verifica que exista la carpeta `uploads/` con permisos de escritura.

## Endpoints

### `POST /BACKEND/contact.php`

Recibe un `multipart/form-data` con:

- `nombre`
- `telefono`
- `email`
- `mensaje`
- `archivo` opcional

Guarda la solicitud en la base `facturas` y el adjunto en `uploads/`.

### `GET /BACKEND/admin_list.php`

Requiere:

```txt
Authorization: Bearer <token>
```

Devuelve el listado de solicitudes. Solo accesible para usuarios con rol `admin`.

### `GET /BACKEND/admin_download.php?id=...`

Requiere:

```txt
Authorization: Bearer <token>
```

Descarga el archivo asociado a una solicitud. Solo accesible para usuarios con rol `admin`.

## Seguridad

- `admin_list.php` y `admin_download.php` no confian en el frontend
- ambos validan firma JWT en servidor
- ambos comprueban que el usuario tenga `role === "admin"`
- si el `JWT_SECRET` no coincide con `ApiLoging`, todos los accesos admin fallaran

## Desarrollo local

Ejemplo tipico:

- frontend Vite: `http://localhost:5174`
- backend PHP: `http://localhost/Reglado/RegladoEnergy/BACKEND`

En ese caso, `VITE_CONTACT_ENDPOINT` suele apuntar a:

```env
VITE_CONTACT_ENDPOINT=http://localhost/Reglado/RegladoEnergy/BACKEND/contact.php
```
