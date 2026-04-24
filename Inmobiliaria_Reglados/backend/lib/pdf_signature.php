<?php
declare(strict_types=1);

/*function pdfSeemsSigned($uploadedPath, $originalPath = null): array
{
    return [
        'accepted' => true,
        'reason' => 'Validación desactivada (modo debug)'
    ];
}*/

function pdfSeemsSigned(string $uploadedPath, ?string $originalPath = null): array
{
    if (!is_file($uploadedPath) || !is_readable($uploadedPath)) {
        return ['accepted' => false, 'reason' => 'Archivo no accesible'];
    }

    $uploaded = @file_get_contents($uploadedPath);
    if ($uploaded === false || $uploaded === '') {
        return ['accepted' => false, 'reason' => 'No se pudo leer el PDF subido'];
    }

    $uploadedHash = hash_file('sha256', $uploadedPath);

    if ($originalPath && is_file($originalPath) && is_readable($originalPath)) {
        $originalHash = hash_file('sha256', $originalPath);
        if ($originalHash && hash_equals($uploadedHash, $originalHash)) {
            return ['accepted' => false, 'reason' => 'El documento es idéntico al original'];
        }
    }

    // Patrones que SOLO aparecen en PDFs con firma digital real. Descartados a
    // propósito /AcroForm, /Widget, /Annots, /XObject, /Subtype /Image,
    // /Subtype /Form y la heurística de tamaño — están presentes en cualquier
    // PDF con formularios o imágenes y no demuestran nada sobre la firma.
    $signaturePatterns = [
        'sig_field'        => '/\/FT\s*\/Sig\b/i',   // Campo de formulario con tipo firma
        'sig_dict'         => '/\/Sig\b/i',           // Diccionario Signature
        'sig_flags'        => '/\/SigFlags\b/i',      // Bandera de AcroForm indicando firma
        'pkcs7'            => '/adbe\.pkcs7/i',       // SubFilter Adobe PKCS#7
        'cades'            => '/ETSI\.CAdES/i',       // SubFilter ETSI CAdES (firma española)
        'docusign'         => '/DocuSign/i',
        'adobe_sign'       => '/Adobe Acrobat Sign/i',
    ];

    $matched = [];
    foreach ($signaturePatterns as $name => $pattern) {
        if (preg_match($pattern, $uploaded)) {
            $matched[] = $name;
        }
    }

    if (empty($matched)) {
        return [
            'accepted' => false,
            'reason'   => 'el PDF no contiene marcadores de firma digital',
        ];
    }

    return [
        'accepted' => true,
        'reason'   => 'marcadores de firma detectados: ' . implode(', ', $matched),
    ];
}
