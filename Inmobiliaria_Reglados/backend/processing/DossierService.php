<?php
declare(strict_types=1);

class DossierService
{
    private string $uploadDir;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = rtrim($uploadDir, '/\\');

        if ($this->uploadDir === '') {
            throw new RuntimeException('Directorio de uploads no válido');
        }

        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0777, true) && !is_dir($this->uploadDir)) {
                throw new RuntimeException('No se pudo crear el directorio de uploads');
            }
        }
    }

    public function generateDocuments(int $assetId): array
    {
        $suffix = bin2hex(random_bytes(6));
        $files = [
            'dossier' => sprintf('dossier_%d_%s.pdf', $assetId, $suffix),
            'confidentiality' => sprintf('confidentiality_%d_%s.pdf', $assetId, $suffix),
            'intention' => sprintf('intention_%d_%s.pdf', $assetId, $suffix),
        ];

        foreach ($files as $label => $name) {
            $path = $this->uploadDir . DIRECTORY_SEPARATOR . $name;
            $body = sprintf("PDF simulado para %s (activo %d)\n", $label, $assetId);

            if (file_put_contents($path, $body) === false) {
                throw new RuntimeException('No se pudo crear el archivo ' . $name);
            }
        }

        return $files;
    }
}
