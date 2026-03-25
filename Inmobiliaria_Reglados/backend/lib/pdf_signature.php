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
        '/SigFlags',
        '/AcroForm',
        '/Annots',
        '/Ink',
        '/Contents',
        'ByteRange',
        'PKCS7',
        'Adobe.PPKLite',
        'DCTDecode',
        'JPXDecode',
        'FlateDecode',
    ];

    foreach ($signals as $signal) {
        if (stripos($content, $signal) !== false) {
            return true;
        }
    }

    if (preg_match_all('/\/Sig[^A-Za-z]/i', $content, $matches) && count($matches[0]) > 1) {
        return true;
    }

    if (stripos($content, 'ByteRange') !== false && stripos($content, '/Contents') !== false) {
        return true;
    }

    if (preg_match('/\/Filter\\s*\\/DCTDecode/i', $content)) {
        return true;
    }

    if (preg_match('/\/Metadata/i', $content)) {
        return true;
    }

    if (preg_match('/\\/Rect/i', $content) && preg_match('/\\/Subtype\\s*\\/Widget/i', $content)) {
        return true;
    }

    return false;
}
