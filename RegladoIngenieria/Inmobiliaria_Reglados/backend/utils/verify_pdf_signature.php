<?php
declare(strict_types=1);

function isPdfSigned(string $filePath): bool
{
    if (!is_file($filePath)) {
        return false;
    }

    $content = @file_get_contents($filePath);
    if ($content === false) {
        return false;
    }

    return stripos($content, "/ByteRange") !== false && stripos($content, "/Contents") !== false;
}
