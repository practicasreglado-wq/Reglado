<?php
declare(strict_types=1);

function pdfSeemsSigned(string $filePath): bool
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        return false;
    }

    $content = @file_get_contents($filePath);
    if ($content === false) {
        return false;
    }

    $signals = [
        '/Sig',
        '/AcroForm',
        'ByteRange',
        'PKCS7',
        'Adobe.PPKLite',
        '/Contents',
    ];

    foreach ($signals as $signal) {
        if (stripos($content, $signal) !== false) {
            return true;
        }
    }

    return false;
}
