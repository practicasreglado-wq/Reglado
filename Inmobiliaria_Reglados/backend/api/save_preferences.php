<?php

/**
 * Endpoint de "guardar preferencias" del cuestionario de matching.
 *
 * Es un alias delgado: incluye match_preferences.php que contiene la lógica
 * real (POST → upsert; GET → fetch). Mantenido por compatibilidad con
 * frontends antiguos que usan esta URL.
 */

require_once __DIR__ . "/match_preferences.php";
