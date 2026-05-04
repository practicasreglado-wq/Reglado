# Reglado — Ecosistema

Monorepo del ecosistema **Reglado Group**. Aquí conviven los frontends, los
backends PHP y el chatbot. La identidad de usuarios es central: vive en
`ApiLoging` y el resto de productos consume su JWT.

## Proyectos

### Frontends (Vue 3 + Vite)

| Carpeta | Descripción |
|---|---|
| [GrupoReglado/](GrupoReglado/) | Portal principal y punto común de login, registro y configuración. |
| [RegladoEnergy/](RegladoEnergy/) | Web corporativa de Reglado Energy. Tiene backend PHP propio en `RegladoEnergy/BACKEND/`. |
| [RegladoIngenieria/](RegladoIngenieria/) | Web corporativa de Reglado Ingeniería. Tiene backend PHP propio en `RegladoIngenieria/BACKEND/`. |
| [RegladoMaps/](RegladoMaps/) | Visualización interactiva de activos y recursos en mapa. |
| [Inmobiliaria_Reglados/](Inmobiliaria_Reglados/) | Producto inmobiliario (gestionado por equipo aparte). |

### Backends PHP

| Carpeta | Descripción |
|---|---|
| [ApiLoging/](ApiLoging/) | **API central de identidad**: usuarios, login, JWT, roles, verificación email, SSO. BD: `regladousers`. |
| [ApiMapa/](ApiMapa/) | Backend de datos geográficos / mapas. |

### Otros

| Carpeta | Descripción |
|---|---|
| [Chatbot/](Chatbot/) | Chatbot del ecosistema (Node.js + widget JS). |

## Documentación

Toda la documentación técnica del ecosistema está en [docs/](docs/):

- [DOCUMENTACION_GENERAL_TECNICA.md](docs/DOCUMENTACION_GENERAL_TECNICA.md) — visión general, arquitectura y flujos de auth.
- [GUIA_IMPLEMENTACION_GENERAL_DOMINIOS.md](docs/GUIA_IMPLEMENTACION_GENERAL_DOMINIOS.md) — guía de despliegue y dominios.
- [ECOSYSTEM_AUTH_SSO_HUB.md](docs/ECOSYSTEM_AUTH_SSO_HUB.md) — hub SSO multi-origen.
- [ECOSYSTEM_AUTH_MULTI_ORIGIN.md](docs/ECOSYSTEM_AUTH_MULTI_ORIGIN.md) — políticas CORS / orígenes permitidos.
- [HARDENING_APILOGING_PENDIENTE.md](docs/HARDENING_APILOGING_PENDIENTE.md) — pendientes de hardening del backend central.
- [HARDENING_FRONTENDS_PENDIENTE.md](docs/HARDENING_FRONTENDS_PENDIENTE.md) — pendientes de hardening de los frontends.
- [PENDIENTE_INMOBILIARIA_APILOGING.md](docs/PENDIENTE_INMOBILIARIA_APILOGING.md) — integración Inmobiliaria ↔ ApiLoging.
- [UPGRADE_PHP_DEV.md](docs/UPGRADE_PHP_DEV.md) — notas del upgrade de PHP en dev.

## Stack y versiones

| Capa | Versión | Dónde aplica / notas |
|---|---|---|
| PHP | **8.0+** (probado en 8.0.30 / XAMPP) | Todos los backends PHP: `ApiLoging/`, `ApiMapa/`, `RegladoEnergy/BACKEND/`, `RegladoIngenieria/BACKEND/`, `Inmobiliaria_Reglados/backend/`. Upgrade a PHP 8.1+ pospuesto — ver [docs/UPGRADE_PHP_DEV.md](docs/UPGRADE_PHP_DEV.md). |
| Composer | requerido por `ApiLoging/` | Dependencias: `firebase/php-jwt ^6.11`, `phpmailer/phpmailer ^7.0`, `geoip2/geoip2 ^2.13` |
| MySQL / MariaDB | XAMPP estándar | BD principal `regladousers` (auth) más BDs por proyecto |
| Node.js | **≥ 18** (probado en 24.x) | Para `npm run dev` y `npm run build` de los frontends |
| Vue | `^3.5.0` (Vue Router `^4.4.0`) | Frontends Vue 3 con Composition API |
| Vite | `^6.4.2` (baseline) | Acordada el 2026-04-30. **Excepción**: `Inmobiliaria_Reglados` está en `^7.3.1` (mantenida por equipo externo) |
| @vitejs/plugin-vue | `^5.2.0` (baseline) | Inmobiliaria en `^6.0.4` |

> Para proyectos nuevos del ecosistema, usar la baseline `vite ^6.4.2` + `@vitejs/plugin-vue ^5.2.0` para mantener el lockstep con Energy, Grupo, Maps e Ingeniería.

## Cómo levantar el entorno

**Pre-requisito:** MySQL arrancado desde el Control Panel de XAMPP (no se gestiona con scripts).

### Servidores y comandos

| # | Servicio | Ruta | Comando | Puerto |
|---|---|---|---|---|
| 1 | ApiLoging | `ApiLoging/` | `php -S localhost:8000` | 8000 |
| 2 | GrupoReglado | `GrupoReglado/` | `npm run dev` | 5173 |
| 3 | RegladoEnergy | `RegladoEnergy/` | `npm run dev` | 5174 |
| 4 | Inmobiliaria_Reglados | `Inmobiliaria_Reglados/` | `npm run dev -- --port 5175` | 5175 |
| 5 | RegladoMaps | `RegladoMaps/` | `npm run dev` | 5176 |
| 6 | RegladoIngenieria | `RegladoIngenieria/` | `npm run dev -- --port 5177` | 5177 |
| 7 | RegladoEnergy BACKEND | `RegladoEnergy/BACKEND/` | `php -S localhost:8001` | 8001 |
| 8 | Inmobiliaria backend | `Inmobiliaria_Reglados/backend/` | `php -S localhost:8002` | 8002 |
| 9 | RegladoIngenieria BACKEND | `RegladoIngenieria/BACKEND/` | `php -S localhost:8003` | 8003 |

### Arrancar todo en orden (bash, copy-paste)

Lanza los 9 servidores en background con pausas entre cada uno y loguea cada servicio en `/tmp/reglado_NN_*.log`. **Mantén el orden**: ApiLoging primero (los frontends dependen de él para auth) y los `BACKEND` PHP después de sus respectivos frontends:

```bash
cd c:/xampp/htdocs/Reglado/ApiLoging && php -S localhost:8000 > /tmp/reglado_01_apiloging.log 2>&1 &
sleep 1
cd c:/xampp/htdocs/Reglado/GrupoReglado && npm run dev > /tmp/reglado_02_grupo.log 2>&1 &
sleep 1
cd c:/xampp/htdocs/Reglado/RegladoEnergy && npm run dev > /tmp/reglado_03_energy.log 2>&1 &
sleep 1
cd c:/xampp/htdocs/Reglado/Inmobiliaria_Reglados && npm run dev -- --port 5175 > /tmp/reglado_04_inmo.log 2>&1 &
sleep 1
cd c:/xampp/htdocs/Reglado/RegladoMaps && npm run dev > /tmp/reglado_05_maps.log 2>&1 &
sleep 1
cd c:/xampp/htdocs/Reglado/RegladoIngenieria && npm run dev -- --port 5177 > /tmp/reglado_06_inge.log 2>&1 &
sleep 1
cd c:/xampp/htdocs/Reglado/RegladoEnergy/BACKEND && php -S localhost:8001 > /tmp/reglado_07_energy_api.log 2>&1 &
sleep 1
cd c:/xampp/htdocs/Reglado/Inmobiliaria_Reglados/backend && php -S localhost:8002 > /tmp/reglado_08_inmo_api.log 2>&1 &
sleep 1
cd c:/xampp/htdocs/Reglado/RegladoIngenieria/BACKEND && php -S localhost:8003 > /tmp/reglado_09_inge_api.log 2>&1 &
sleep 3
```

### Verificar que los 9 puertos están sirviendo

```bash
for port in 8000 5173 5174 5175 5176 5177 8001 8002 8003; do
  pid=$(netstat -ano 2>/dev/null | grep "LISTENING" | grep ":$port " | awk '{print $NF}' | head -1)
  [ -n "$pid" ] && echo "✓ puerto $port (pid $pid)" || echo "✗ puerto $port LIBRE"
done
```

Cualquier puerto que aparezca como `LIBRE` indica que ese servicio no arrancó — revisa su log en `/tmp/reglado_NN_*.log`.

**Acceso al portal**: <http://localhost:5173/>

> Atajo desde Claude Code: la skill `skill-levantar-reglado` ejecuta exactamente estos pasos con verificación incluida.

## Estructura del repo

```
Reglado/
├── ApiLoging/              # backend identidad (PHP)
├── ApiMapa/                # backend mapas (PHP)
├── Chatbot/                # chatbot (Node.js)
├── GrupoReglado/           # frontend portal principal (Vue)
├── RegladoEnergy/          # frontend Energy + BACKEND/ (Vue + PHP)
├── RegladoIngenieria/      # frontend Ingeniería + BACKEND/ (Vue + PHP)
├── RegladoMaps/            # frontend mapas (Vue)
├── Inmobiliaria_Reglados/  # producto inmobiliario (gestión externa)
└── docs/                   # documentación del ecosistema
```

> Carpetas locales que **no** se versionan (están en `.gitignore`):
> `Pruebas/`, `RegladoBienesRaices/`, `n8nJson/`, `SkillsClaude/`,
> `ReleasesEstables/`, `scripts/`, `.agent/`.
