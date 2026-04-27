<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once __DIR__ . '/../../backend/lib/error_reporting.php';

final class ErrorReportingTest extends TestCase
{
    public function test_genera_id_hexadecimal_de_8_caracteres(): void
    {
        $exception = new RuntimeException('test');
        $errorId = logAndReferenceError('test_module', $exception);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}$/', $errorId);
    }

    public function test_ids_consecutivos_son_distintos(): void
    {
        $ids = [];
        for ($i = 0; $i < 20; $i++) {
            $ids[] = logAndReferenceError('test', new RuntimeException("err $i"));
        }

        $this->assertCount(20, array_unique($ids), 'IDs deben ser únicos');
    }

    public function test_loguea_el_mensaje_y_el_archivo_de_la_excepcion(): void
    {
        // Capturamos stderr durante el test.
        $exception = new RuntimeException('mensaje secreto');
        $logFile = tempnam(sys_get_temp_dir(), 'phpunit_err_');
        $previousLog = ini_get('error_log');
        ini_set('error_log', $logFile);

        try {
            $errorId = logAndReferenceError('test_module', $exception);
            $logged = file_get_contents($logFile);

            $this->assertStringContainsString($errorId, $logged);
            $this->assertStringContainsString('mensaje secreto', $logged);
            $this->assertStringContainsString('test_module', $logged);
        } finally {
            ini_set('error_log', $previousLog);
            @unlink($logFile);
        }
    }
}
