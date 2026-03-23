<?php
declare(strict_types=1);

class PdfTextExtractor
{
    public static function extractTextFromFile(string $filePath): string
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException('PDF no disponible para extraer texto');
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException('No se pudo leer el PDF');
        }

        $text = self::extractFromContent($content);
        return self::cleanupWhitespace($text);
    }

    private static function extractFromContent(string $content): string
    {
        $buffers = [$content];

        foreach (self::extractStreams($content) as $stream) {
            $decoded = self::decodeStream($stream['data'], $stream['filters']);
            if ($decoded !== null && $decoded !== '') {
                $buffers[] = $decoded;
            }
        }

        $pieces = [];
        foreach ($buffers as $buffer) {
            $strings = self::extractStrings($buffer);
            if ($strings !== '') {
                $pieces[] = $strings;
            }
        }

        return implode(' ', $pieces);
    }

    private static function extractStreams(string $content): array
    {
        $streams = [];

        if (preg_match_all('/<<(.*?)>>\\s*stream\\s*\\r?\\n?(.*?)\\r?\\n?endstream/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $meta = $match[1];
                $data = $match[2];
                $filters = [];

                if (preg_match_all('/\\/Filter\\s+(\\[.*?\\]|\\/[^\\s>]+|[^\\s>]+)/s', $meta, $fmatches)) {
                    foreach ($fmatches[1] as $filterBlock) {
                        $trimmedBlock = trim($filterBlock, '[]\\s');
                        $parts = preg_split('/\\s+/', $trimmedBlock);
                        foreach ($parts as $part) {
                            $filterName = ltrim($part, '/');
                            if ($filterName !== '') {
                                $filters[] = $filterName;
                            }
                        }
                    }
                }

                $streams[] = [
                    'data' => $data,
                    'filters' => array_values(array_unique($filters)),
                ];
            }
        }

        return $streams;
    }

    private static function decodeStream(string $data, array $filters): ?string
    {
        $decoded = $data;
        if (empty($filters)) {
            $attempt = @gzdecode($decoded);
            if ($attempt !== false) {
                $decoded = $attempt;
            }
        }

        foreach ($filters as $filter) {
            $filter = strtolower($filter);

            switch ($filter) {
                case 'flatedecode':
                case 'flate-decode':
                    $decoded = @gzdecode($decoded);
                    if ($decoded === false) {
                        return null;
                    }
                    break;

                case 'asciihexdecode':
                case 'asciihex':
                    $decoded = self::decodeAsciiHex($decoded);
                    if ($decoded === null) {
                        return null;
                    }
                    break;

                case 'ascii85decode':
                case 'ascii85':
                    $decoded = self::decodeAscii85($decoded);
                    if ($decoded === null) {
                        return null;
                    }
                    break;

                default:
                    return null;
            }
        }

        return $decoded;
    }

    private static function extractStrings(string $buffer): string
    {
        $pieces = [];

        if (preg_match_all('/\\((?:[^\\\\()]|\\\\.)*\\)/s', $buffer, $matches)) {
            foreach ($matches[0] as $raw) {
                $value = substr($raw, 1, -1);
                $pieces[] = self::decodeEscapes($value);
            }
        }

        return implode(' ', $pieces);
    }

    private static function decodeEscapes(string $value): string
    {
        return preg_replace_callback('/\\\\(n|r|t|b|f|\\\\|\\(|\\)|[0-7]{1,3})/', function ($match) {
            $token = $match[1];
            switch ($token) {
                case 'n':
                    return "\n";
                case 'r':
                    return "\r";
                case 't':
                    return "\t";
                case 'b':
                    return chr(8);
                case 'f':
                    return chr(12);
                case '\\\\':
                    return "\\\\";
                case '(':
                    return '(';
                case ')':
                    return ')';
                default:
                    return chr(octdec($token));
            }
        }, $value);
    }

    private static function cleanupWhitespace(string $text): string
    {
        $clean = preg_replace('/[\\x00-\\x1F]+/', ' ', $text);
        $clean = preg_replace('/\\s+/u', ' ', $clean);
        return trim($clean);
    }

    private static function decodeAscii85(string $data): ?string
    {
        $clean = trim($data);
        if (substr($clean, 0, 2) === '<~') {
            $clean = substr($clean, 2);
        }
        if (substr($clean, -2) === '~>') {
            $clean = substr($clean, 0, -2);
        }

        $output = '';
        $value = 0;
        $count = 0;

        $clean = preg_replace('/\\s+/', '', $clean);
        $chars = str_split($clean);

        foreach ($chars as $char) {
            if ($char === 'z') {
                $output .= str_repeat(chr(0), 4);
                continue;
            }

            $digit = ord($char) - 33;
            if ($digit < 0 || $digit > 84) {
                return null;
            }

            $value = $value * 85 + $digit;
            $count++;

            if ($count === 5) {
                $output .= pack('N', $value);
                $count = 0;
                $value = 0;
            }
        }

        if ($count > 0) {
            for ($i = $count; $i < 5; $i++) {
                $value = $value * 85 + 84;
            }
            $bytes = substr(pack('N', $value), 0, $count - 1);
            $output .= $bytes;
        }

        return $output;
    }

    private static function decodeAsciiHex(string $data): ?string
    {
        $clean = preg_replace('/\\s+/', '', $data);
        if (substr($clean, 0, 1) === '<') {
            $clean = substr($clean, 1);
        }
        if (substr($clean, -1) === '>') {
            $clean = substr($clean, 0, -1);
        }

        if (strlen($clean) % 2 !== 0) {
            $clean .= '0';
        }

        if (!preg_match('/^[0-9A-Fa-f]+$/', $clean)) {
            return null;
        }

        return hex2bin($clean);
    }
}
