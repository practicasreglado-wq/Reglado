<?php
declare(strict_types=1);

use Dompdf\Dompdf;

/**
 * Genera el dossier comercial PDF completo de una propiedad.
 *
 * El dossier es el documento "premium" que solo se desbloquea para el
 * comprador tras firmar NDA + LOI. Contiene la información detallada del
 * activo (datos financieros, fotos, planos, situación urbanística...) que
 * NO se muestra públicamente en la web.
 *
 * Estructura de archivos:
 *   $uploadDir/
 *     dossiers/
 *       dossier_<tipo>_<ciudad>_<zona>.pdf    ← uno por propiedad
 *
 * El nombre del archivo se "slugifica" (sin acentos, sin espacios) para que
 * funcione en cualquier sistema de archivos (Linux/Windows) y se pueda
 * referenciar limpiamente desde la URL de descarga.
 *
 * Diferencia con PdfGenerator: ese genera múltiples docs auxiliares (NDA,
 * LOI, ficha) en formatos sencillos. DossierService solo hace el dossier
 * comercial pero con plantilla más elaborada y multi-página.
 */
class DossierService
{
    private string $workingDir;

    public function __construct(string $uploadDir)
    {
        $this->workingDir = rtrim($uploadDir, '/\\');

        if ($this->workingDir === '') {
            throw new RuntimeException('Directorio de uploads inválido');
        }

        $this->ensureDirectory($this->workingDir);
        $this->ensureDirectory($this->workingDir . DIRECTORY_SEPARATOR . 'dossiers');
    }

    public function generateDossierPDF(int $propertyId, array $dossier, array $ficha = []): string
    {
        $propertyType = $this->slugifyFilenamePart($ficha['tipo_propiedad'] ?? null);
        $city = $this->slugifyFilenamePart($ficha['ciudad'] ?? null);
        $zone = $this->slugifyFilenamePart($ficha['zona'] ?? null);

        $fileName = "dossier_{$propertyType}_{$city}_{$zone}.pdf";      
        $path = $this->workingDir . DIRECTORY_SEPARATOR . 'dossiers' . DIRECTORY_SEPARATOR . $fileName;

        $dompdf = new Dompdf();
        $dompdf->loadHtml($this->buildHtml($dossier, $ficha));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $content = $dompdf->output();
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('No se pudo generar el dossier PDF');
        }

        return 'dossiers/' . $fileName;
    }

    private function slugifyFilenamePart(mixed $value): string
{
    $text = trim((string) ($value ?? ''));

    if ($text === '') {
        return 'sin_dato';
    }

    $replacements = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c',
        'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a',
        'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
        'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
        'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o',
        'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
        'Ñ' => 'n', 'Ç' => 'c',
    ];

    $text = strtr($text, $replacements);
    $text = strtolower($text);

    $text = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $text);
    $text = preg_replace('/\s+/', '_', $text);
    $text = preg_replace('/_+/', '_', $text);

    $text = trim((string) $text, '._- ');

    if ($text === '') {
        return 'sin_dato';
    }

    return $text;
}

    private function buildHtml(array $dossier, array $ficha = []): string
    {
        $html = '<html><head><meta charset="utf-8"><style>
            body { font-family: Arial, sans-serif; color: #111; line-height: 1.45; font-size: 13px; }
            .page { padding: 34px; }
            .cover { text-align: center; padding-top: 90px; padding-bottom: 70px; }
            .cover h1 { font-size: 30px; margin-bottom: 10px; color: #1f3c88; }
            .cover .subtitle { font-size: 18px; font-weight: bold; margin-bottom: 8px; }
            .cover .location { font-size: 14px; color: #444; margin-bottom: 12px; }
            .cover .price { font-size: 24px; font-weight: bold; margin-top: 20px; }
            .section { margin-bottom: 28px; }
            .section h2 {
                font-size: 18px;
                border-bottom: 2px solid #1f3c88;
                padding-bottom: 6px;
                color: #1f3c88;
                margin-bottom: 14px;
            }
            .grid { width: 100%; }
            .card {
                border: 1px solid #d9e1f2;
                background: #f8fbff;
                padding: 12px;
                margin-bottom: 10px;
                border-radius: 6px;
            }
            .card strong {
                display: block;
                font-size: 11px;
                text-transform: uppercase;
                color: #5a6472;
                margin-bottom: 4px;
            }
            .card span {
                font-size: 14px;
                font-weight: 600;
                color: #111;
            }
            .text-block {
                border: 1px solid #e5e7eb;
                background: #fff;
                padding: 12px;
                border-radius: 6px;
            }
            ul { margin: 8px 0 0; padding-left: 20px; }
            li { margin-bottom: 6px; }
            .footer-note {
                margin-top: 40px;
                font-size: 11px;
                color: #6b7280;
                border-top: 1px solid #ddd;
                padding-top: 12px;
            }
        </style></head><body>';

        $html .= '<div class="page">';

        $html .= '<div class="cover">';
        $html .= '<h1>Dossier de Inversión</h1>';
        $html .= '<div class="subtitle">' . $this->value($ficha['tipo_propiedad'] ?? 'Activo inmobiliario') . '</div>';
        $html .= '<div class="location">' . $this->value($ficha['direccion'] ?? $dossier['ubicacion_completa'] ?? 'Ubicación no disponible') . '</div>';
        $html .= '<div class="location">' . $this->value($ficha['zona'] ?? 'Zona no disponible') . ' · ' . $this->value($ficha['ciudad'] ?? 'Ciudad no disponible') . '</div>';
        $html .= '<div class="price">' . $this->formatCurrency($ficha['precio'] ?? $dossier['precio_inicial'] ?? null) . '</div>';
        $html .= '</div>';

        $html .= $this->buildTextSection('Resumen ejecutivo', $dossier['resumen_ejecutivo'] ?? null);

        $html .= $this->buildSection('Identificación del activo', [
            ['label' => 'Tipo de propiedad', 'value' => $ficha['tipo_propiedad'] ?? null],
            ['label' => 'Categoría', 'value' => $ficha['categoria'] ?? null],
            ['label' => 'Dirección', 'value' => $ficha['direccion'] ?? null],
            ['label' => 'Ciudad', 'value' => $ficha['ciudad'] ?? null],
            ['label' => 'Zona', 'value' => $ficha['zona'] ?? null],
            ['label' => 'Metros construidos ficha', 'value' => $this->formatSurface($ficha['metros_cuadrados'] ?? null)],
        ]);

        $html .= $this->buildSection('Ubicación', [
            ['label' => 'Ubicación completa', 'value' => $dossier['ubicacion_completa'] ?? null],
            ['label' => 'Código postal', 'value' => $dossier['codigo_postal'] ?? null],
        ]);

        $html .= $this->buildSection('Superficies', [
            ['label' => 'Superficie parcela', 'value' => $this->formatSurface($dossier['superficie_parcela'] ?? null)],
            ['label' => 'Superficie construida', 'value' => $this->formatSurface($dossier['superficie_construida'] ?? null)],
        ]);

        $html .= $this->buildSection('Urbanismo', [
            ['label' => 'Uso principal', 'value' => $dossier['uso_principal'] ?? null],
            ['label' => 'Uso alternativo', 'value' => $dossier['uso_alternativo'] ?? null],
            ['label' => 'Altura', 'value' => $dossier['altura'] ?? null],
            ['label' => 'Norma zonal', 'value' => $dossier['norma_zonal'] ?? null],
        ]);

        $html .= $this->buildSection('Datos económicos', [
            ['label' => 'Precio inicial', 'value' => $this->formatCurrency($dossier['precio_inicial'] ?? null)],
            ['label' => 'Precio mínimo de cierre', 'value' => $this->formatCurrency($dossier['precio_minimo_cierre'] ?? null)],
            ['label' => 'Repercusión techo', 'value' => $this->formatCurrencyPerSquareMeter($dossier['repercusion_techo'] ?? null)],
            ['label' => 'Honorarios comprador', 'value' => $this->formatPercent($dossier['honorarios_comprador_pct'] ?? null)],
        ]);

        $html .= $this->buildSection('Situación jurídica y estado', [
            ['label' => 'Tipo de propiedad jurídica', 'value' => $dossier['propiedad_tipo'] ?? null],
            ['label' => 'Se entrega vacío', 'value' => $this->formatBoolean($dossier['se_entrega_vacio'] ?? null)],
        ]);

        $html .= $this->buildSection('Mercado', [
            ['label' => 'Precio obra nueva zona mínimo', 'value' => $this->formatCurrencyPerSquareMeter($dossier['precio_obra_nueva_zona_min'] ?? null)],
            ['label' => 'Precio obra nueva zona máximo', 'value' => $this->formatCurrencyPerSquareMeter($dossier['precio_obra_nueva_zona_max'] ?? null)],
        ]);

        $html .= $this->buildListSection('Riesgos', $dossier['riesgos'] ?? []);
        $html .= $this->buildListSection('Oportunidades', $dossier['oportunidades'] ?? []);
        $html .= $this->buildListSection('Observaciones', $dossier['observaciones'] ?? []);

        $html .= '<div class="footer-note">';
        $html .= 'Documento generado automáticamente. ';
        $html .= 'Debe validarse jurídica, urbanística, técnica y económicamente antes de adoptar decisiones de inversión.';
        $html .= '</div>';

        $html .= '</div></body></html>';

        return $html;
    }

    private function buildSection(string $title, array $rows): string
    {
        $content = '<div class="section"><h2>' . $title . '</h2><div class="grid">';
        foreach ($rows as $row) {
            $value = $row['value'] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $content .= '<div class="card">';
            $content .= '<strong>' . $row['label'] . '</strong>';
            $content .= '<span>' . $this->value($value) . '</span>';
            $content .= '</div>';
        }
        $content .= '</div></div>';

        return $content;
    }

    private function buildTextSection(string $title, mixed $text): string
    {
        if ($text === null || trim((string) $text) === '') {
            return '';
        }

        return '<div class="section"><h2>' . $title . '</h2><div class="text-block">' . nl2br(htmlspecialchars((string) $text)) . '</div></div>';
    }

    private function buildListSection(string $title, array $items): string
    {
        $filtered = array_values(array_filter(array_map(function ($item) {
            return is_string($item) ? trim($item) : $item;
        }, $items), function ($item) {
            return $item !== null && $item !== '';
        }));

        if ($filtered === []) {
            return '';
        }

        $html = '<div class="section"><h2>' . $title . '</h2><div class="text-block"><ul>';
        foreach ($filtered as $item) {
            $html .= '<li>' . htmlspecialchars((string) $item) . '</li>';
        }
        $html .= '</ul></div></div>';

        return $html;
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0750, true)) {
                @chmod($dir, 0750);
            }
        }
    }

    private function formatCurrency(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'No disponible';
        }

        return number_format((float) $value, 2, ',', '.') . ' €';
    }

    private function formatSurface(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'No disponible';
        }

        return number_format((float) $value, 0, ',', '.') . ' m²';
    }

    private function formatCurrencyPerSquareMeter(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'No disponible';
        }

        return number_format((float) $value, 2, ',', '.') . ' €/m²';
    }

    private function formatPercent(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'No disponible';
        }

        return number_format((float) $value, 2, ',', '.') . ' %';
    }

    private function formatBoolean(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'No disponible';
        }

        return (bool) $value ? 'Sí' : 'No';
    }

    private function value(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'No disponible';
        }

        if (is_array($value)) {
            return implode(', ', $value);
        }

        return htmlspecialchars((string) $value);
    }
}