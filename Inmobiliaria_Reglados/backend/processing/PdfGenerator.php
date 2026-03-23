<?php
declare(strict_types=1);
use Dompdf\Dompdf;

class PdfGenerator
{
    private string $storageDir;

    public function __construct(string $storageDir)
    {
        $this->storageDir = rtrim($storageDir, '/\\');

        if ($this->storageDir === '') {
            throw new RuntimeException('Directorio de textos inválido');
        }

        if (!is_dir($this->storageDir)) {
            if (!mkdir($this->storageDir, 0777, true) && !is_dir($this->storageDir)) {
                throw new RuntimeException('No se pudo crear el directorio de textos');
            }
        }
    }

    private function value(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'No disponible';
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    public function generateDocuments(array $data, int $propertyId): array
    {
        $ndaFile = "nda_{$propertyId}.pdf";
        $loiFile = "loi_{$propertyId}.pdf";

        $ndaPath = $this->storageDir . DIRECTORY_SEPARATOR . $ndaFile;
        $loiPath = $this->storageDir . DIRECTORY_SEPARATOR . $loiFile;

        $this->createPdf($this->getConfidencialidad($data), $ndaPath);
        $this->createPdf($this->getIntencion($data), $loiPath);

        return [
            'confidentiality_file' => $ndaFile,
            'intention_file' => $loiFile,
        ];
    }

    private function createPdf(string $text, string $path): void
    {
        try {
            error_log("PDF START → " . $path);

            $dompdf = new Dompdf();
            $html = "<html><body style='font-family: Arial; font-size:12px;'>"
                . nl2br(htmlspecialchars($text))
                . "</body></html>";

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4');
            $dompdf->render();

            $output = $dompdf->output();

            if (!$output) {
                throw new Exception("PDF vacío");
            }

            if (file_put_contents($path, $output) === false) {
                throw new Exception("No se pudo guardar PDF");
            }

            error_log("PDF OK → " . $path);
        } catch (\Throwable $e) {
            error_log("ERROR PDF: " . $e->getMessage());
            throw $e;
        }
    }

    private function getConfidencialidad(array $data): string
    {
        $fecha = date('d/m/Y');

        $zone = $this->value($data['zona'] ?? null);
        $city = $this->value($data['ciudad'] ?? null);
        $propertyType = $this->value($data['tipo_propiedad'] ?? null);
        $price = $this->value($data['precio'] ?? null);

        return "
ACUERDO DE CONFIDENCIALIDAD

En {$city}, a {$fecha}.

El presente acuerdo regula el uso de la información relacionada con el activo:

• Tipo de propiedad: {$propertyType}
• Zona: {$zone}
• Precio orientativo: {$price} €

Ambas partes acuerdan mantener la información confidencial y no divulgarla sin autorización.
";
    }

    private function getIntencion(array $data): string
    {
        $fecha = date('d/m/Y');
        $city = $this->value($data['ciudad'] ?? null);
        $zone = $this->value($data['zona'] ?? null);
        $propertyType = $this->value($data['tipo_propiedad'] ?? null);
        $price = $this->value($data['precio'] ?? null);

        return "
CARTA DE INTENCIONES

En {$city}, a {$fecha}.

El firmante expresa su interés en negociar la compra del activo:

• Tipo de propiedad: {$propertyType}
• Zona: {$zone}
• Precio orientativo: {$price} €

La presente carta es no vinculante y se encuentra sujeta a due diligence y validación administrativa.
";
    }
}
