<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/lib/pdf_signature.php';

final class PdfSignatureTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdf_sig_test_' . uniqid('', true);
        mkdir($this->tmpDir, 0700, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            foreach (glob($this->tmpDir . '/*') as $f) {
                @unlink($f);
            }
            @rmdir($this->tmpDir);
        }
    }

    private function writeFixture(string $filename, string $content): string
    {
        $path = $this->tmpDir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($path, $content);
        return $path;
    }

    public function test_rechaza_archivo_inexistente(): void
    {
        $result = pdfSeemsSigned($this->tmpDir . '/no-existe.pdf');
        $this->assertFalse($result['accepted']);
        $this->assertSame('Archivo no accesible', $result['reason']);
    }

    public function test_rechaza_pdf_idéntico_al_original(): void
    {
        $content = "%PDF-1.4\n/Sig /ETSI.CAdES.detached\n";
        $original = $this->writeFixture('original.pdf', $content);
        $upload = $this->writeFixture('upload.pdf', $content);

        $result = pdfSeemsSigned($upload, $original);
        $this->assertFalse($result['accepted']);
        $this->assertStringContainsString('idéntico', $result['reason']);
    }

    public function test_rechaza_pdf_sin_marcadores_de_firma(): void
    {
        $content = "%PDF-1.4\n/XObject /Subtype /Image\n<image data>\n%%EOF";
        $upload = $this->writeFixture('upload.pdf', $content);

        $result = pdfSeemsSigned($upload);
        $this->assertFalse($result['accepted']);
        $this->assertStringContainsString('marcadores de firma', $result['reason']);
    }

    public function test_acepta_pdf_con_adbe_pkcs7(): void
    {
        $content = "%PDF-1.4\n/Sig\n/SubFilter /adbe.pkcs7.detached\n<firma>\n%%EOF";
        $upload = $this->writeFixture('upload.pdf', $content);

        $result = pdfSeemsSigned($upload);
        $this->assertTrue($result['accepted']);
    }

    public function test_acepta_pdf_con_cades(): void
    {
        $content = "%PDF-1.4\n/Sig\n/SubFilter /ETSI.CAdES.detached\n<firma>\n%%EOF";
        $upload = $this->writeFixture('upload.pdf', $content);

        $result = pdfSeemsSigned($upload);
        $this->assertTrue($result['accepted']);
    }
}
