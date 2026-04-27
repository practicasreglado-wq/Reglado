<?php
declare(strict_types=1);

/**
 * SHIM de compatibilidad. Antes este archivo tenía la lógica de progreso de
 * descarga; se movió a document_access.php para unificar todo el gating del
 * comprador. Si en algún archivo viejo todavía hay un
 * `require_once .../document_download_progress.php`, esto evita el fatal y
 * carga document_access.php transparentemente.
 *
 * Para código nuevo, requerir directamente document_access.php.
 */
require_once __DIR__ . '/document_access.php';
