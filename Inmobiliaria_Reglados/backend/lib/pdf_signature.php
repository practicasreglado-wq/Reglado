<?php
declare(strict_types=1);

function pdfSeemsSigned($uploadedPath, $originalPath = null): array
{
    return [
        'accepted' => true,
        'reason' => 'Validación desactivada (modo debug)'
    ];
}

/*function pdfSeemsSigned(string $uploadedPath, ?string $originalPath = null): array
{
    if (!is_file($uploadedPath) || !is_readable($uploadedPath)) {
        return ['accepted' => false, 'reason' => 'Archivo no accesible'];
    }

    $uploaded = @file_get_contents($uploadedPath);
    if ($uploaded === false || $uploaded === '') {
        return ['accepted' => false, 'reason' => 'No se pudo leer el PDF subido'];
    }

    $uploadedSize = filesize($uploadedPath) ?: 0;
    $uploadedHash = hash_file('sha256', $uploadedPath);

    $original = null;
    $originalSize = 0;
    $originalHash = null;
    if ($originalPath && is_file($originalPath) && is_readable($originalPath)) {
        $original = @file_get_contents($originalPath);
        if ($original !== false && $original !== '') {
            $originalSize = filesize($originalPath) ?: 0;
            $originalHash = hash_file('sha256', $originalPath);
        }
    }

    if ($originalHash !== null && hash_equals($uploadedHash, $originalHash)) {
        return ['accepted' => false, 'reason' => 'El documento es idéntico al original'];
    }

    $strongPatterns = [
        '/\/Sig\b/i',
        '/\/AcroForm\b/i',
        '/\/Widget\b/i',
        '/\/FT\s*\/Sig\b/i',
        '/adbe\.pkcs7/i',
        '/ETSI\.CAdES/i',
        '/DocuSign/i',
        '/Adobe Acrobat Sign/i',
    ];

    $mediumPatterns = [
        '/\/Annots\b/i',
        '/\/AP\b/i',
        '/\/XObject\b/i',
        '/\/Subtype\s*\/Image\b/i',
        '/\/Subtype\s*\/Form\b/i',
        '/\/SigFlags\b/i',
    ];

    $strongHits = 0;
    foreach ($strongPatterns as $pattern) {
        if (preg_match($pattern, $uploaded)) {
            $strongHits++;
        }
    }

    $mediumHits = 0;
    foreach ($mediumPatterns as $pattern) {
        if (preg_match($pattern, $uploaded)) {
            $mediumHits++;
        }
    }

    $sizeDelta = 0;
    $sizeReason = '';
    if ($originalSize > 0) {
        $sizeDelta = abs($uploadedSize - $originalSize);
        $ratio = $sizeDelta / max($originalSize, 1);
        if ($ratio >= 0.08) {
            $sizeReason = sprintf('diferencia de tamaño %.1f%%', $ratio * 100);
            $strongHits++;
        } elseif ($ratio >= 0.035) {
            $mediumHits++;
        }
    }

    $hasIncrementalUpdate = false;
    if ($original !== null && strlen($uploaded) > strlen($original)) {
        $tail = substr($uploaded, max(0, strlen($uploaded) - 3000));
        $hasIncrementalUpdate = stripos($tail, 'startxref') !== false;
        if ($hasIncrementalUpdate) {
            $mediumHits++;
        }
    }

    $accepted = false;
    $reasonParts = [];

    if ($strongHits >= 1) {
        $accepted = true;
        $reasonParts[] = 'patrones fuertes detectados';
    }

    if ($accepted === false && $original !== null) {
        if ($sizeDelta === 0 && $mediumHits <= 1 && !$hasIncrementalUpdate) {
            return ['accepted' => false, 'reason' => 'sin diferencias con el original ni indicios claros'];
        }

        if ($sizeDelta >= 500 && ($mediumHits >= 1 || $hasIncrementalUpdate)) {
            $accepted = true;
            $reasonParts[] = 'tamano significativamente distinto';
        }
    }

    if ($accepted === false && $mediumHits >= 2) {
        $accepted = true;
        $reasonParts[] = 'varias anotaciones detectadas';
    }

    if ($accepted === false) {
        return ['accepted' => false, 'reason' => 'no se encontraron indicios suficientes de firma'];
    }

    if ($sizeReason !== '') {
        $reasonParts[] = $sizeReason;
    }

    return [
        'accepted' => true,
        'reason' => implode(', ', array_filter($reasonParts)),
    ];
}*/
