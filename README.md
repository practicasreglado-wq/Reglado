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

## Cómo levantar el entorno

Usa la skill `skill-levantar-reglado` (arranca los 9 servidores en orden
estricto con verificación final de puertos). MySQL se arranca a mano desde el
Control Panel de XAMPP.

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
