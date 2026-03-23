<?php
declare(strict_types=1);
use Dompdf\Dompdf;

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

    public function generateDossierPDF(int $propertyId, array $data): string
    {
        $fileName = sprintf('dossier_%d.pdf', $propertyId);
        $path = $this->workingDir . DIRECTORY_SEPARATOR . 'dossiers' . DIRECTORY_SEPARATOR . $fileName;

        $dompdf = new Dompdf();
        $dompdf->loadHtml($this->buildHtml($data));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $content = $dompdf->output();
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('No se pudo generar el dossier PDF');
        }

        return 'dossiers/' . $fileName;
    }

    private function buildHtml(array $data): string
    {
        $v = fn($key) => $this->value($data[$key] ?? null);
        $analysis = $data['analisis'] ?? null;
        $analysisJson = is_string($data['analisis_json'] ?? '') ? json_decode($data['analisis_json'], true) : [];

        $metrics = [
            'rentabilidad_bruta',
            'rentabilidad_neta',
            'cap_rate',
            'roi',
            'payback',
            'ocupacion',
            'ADR',
            'RevPAR',
        ];

        $html = '<html><head><meta charset="utf-8"><style>
            body { font-family: Arial, sans-serif; color: #111; line-height: 1.5; }
            .page { padding: 40px; }
            h1, h2, h3 { margin-bottom: 10px; }
            .cover { text-align: center; margin-top: 120px; }
            .section { margin-bottom: 32px; }
            .section h2 { border-bottom: 2px solid #1f3c88; padding-bottom: 6px; color: #1f3c88; }
            .grid { display: flex; flex-wrap: wrap; gap: 20px; }
            .grid .card { flex: 1 1 250px; padding: 14px; border: 1px solid #e6e6e6; border-radius: 10px; background: #f9fbff; }
            .card strong { display: block; font-size: 0.85rem; color: #4b5563; margin-bottom: 4px; }
            .card span { font-size: 1.1rem; font-weight: 600; color: #111; }
            .list ul { padding-left: 18px; margin: 6px 0 0; }
            .status { font-weight: 700; color: #1f3c88; }
            .footer-note { font-size: 0.8rem; color: #6b7280; margin-top: 40px; }
        </style></head><body>';

        $html .= '<div class="page">';
        $html .= '<div class="cover">';
        $html .= '<h1>Dossier de Inversión</h1>';
        $html .= '<p class="status">' . $this->value($data['tipo_propiedad'] ?? 'Activo Captado') . '</p>';
        $html .= '<p>' . $this->value($data['zona'] ?? 'Zona desconocida') . ' · ' . $this->value($data['ciudad'] ?? 'Ciudad desconocida') . '</p>';
        $html .= '<p style="font-size:1.5rem;font-weight:700;">' . $this->formatCurrency($data['precio'] ?? null) . '</p>';
        $html .= '</div>';

        $html .= $this->buildSection('Datos del activo', [
            ['label' => 'Tipo de activo', 'value' => $v('tipo_propiedad')],
            ['label' => 'Subtipo', 'value' => $v('subtipo')],
            ['label' => 'Dirección', 'value' => $v('direccion')],
            ['label' => 'Ciudad', 'value' => $v('ciudad')],
            ['label' => 'Zona', 'value' => $v('zona')],
            ['label' => 'Superficie', 'value' => $v('metros_cuadrados') . ' m²'],
            ['label' => 'Habitaciones', 'value' => $v('habitaciones')],
            ['label' => 'Estado', 'value' => $v('estado_activo')],
        ]);

        $html .= $this->buildSection('Datos económicos', [
            ['label' => 'Precio', 'value' => $this->formatCurrency($data['precio'] ?? null)],
            ['label' => 'Precio por m²', 'value' => $this->formatCurrency($data['precio_m2'] ?? null)],
            ['label' => 'Ingresos actuales', 'value' => $this->formatCurrency($data['ingresos_actuales'] ?? null)],
            ['label' => 'Ingresos estimados', 'value' => $this->formatCurrency($data['ingresos_estimados'] ?? null)],
            ['label' => 'Gastos estimados', 'value' => $this->formatCurrency($data['gastos_estimados'] ?? null)],
            ['label' => 'EBITDA', 'value' => $this->formatCurrency($data['EBITDA'] ?? null)],
            ['label' => 'Cash Flow', 'value' => $this->formatCurrency($data['cash_flow'] ?? null)],
        ]);

        $html .= $this->buildMetricsSection($metrics, $data);

        if (!empty($analysisJson)) {
            $html .= $this->buildAnalysisSection($analysisJson);
        } else {
            $html .= $this->buildTextSection('Análisis', $analysis ?? 'Análisis no disponible.');
        }

        $html .= $this->buildMarketSection($analysisJson);
        $html .= $this->buildValuationSection($analysisJson);

        $html .= '<p class="footer-note">Documento generado automáticamente. Valida los datos antes de tomar decisiones.</p>';

        $html .= '</div></body></html>';

        return $html;
    }

    private function buildSection(string $title, array $rows): string
    {
        $content = '<div class="section"><h2>' . $title . '</h2><div class="grid">';
        foreach ($rows as $row) {
            if (trim((string) $row['value']) === '') {
                continue;
            }
            $content .= '<div class="card"><strong>' . $row['label'] . '</strong><span>' . $row['value'] . '</span></div>';
        }
        $content .= '</div></div>';
        return $content;
    }

    private function buildMetricsSection(array $keys, array $data): string
    {
        $content = '<div class="section"><h2>Métricas clave</h2><div class="grid">';
        foreach ($keys as $metric) {
            $value = $this->value($data[$metric] ?? null);
            $content .= '<div class="card"><strong>' . ucfirst(str_replace('_', ' ', $metric)) . '</strong><span>' . $value . '</span></div>';
        }
        $content .= '</div></div>';
        return $content;
    }

    private function buildAnalysisSection(array $analysisJson): string
    {
        $analysis = $analysisJson['analisis'] ?? [];
        $items = [
            'Resumen' => $analysis['resumen'] ?? null,
            'Puntos fuertes' => $analysis['puntos_fuertes'] ?? null,
            'Riesgos' => $analysis['riesgos'] ?? null,
            'Oportunidades' => $analysis['oportunidades'] ?? null,
            'Perfil inversor' => $analysis['perfil_inversor'] ?? null,
        ];

        $html = '<div class="section"><h2>Análisis</h2>';
        foreach ($items as $label => $value) {
            if (empty($value)) {
                continue;
            }
            $html .= '<div class="list"><strong>' . $label . '</strong>';
            if (is_array($value)) {
                $html .= '<ul>';
                foreach ($value as $bullet) {
                    $html .= '<li>' . $this->value($bullet) . '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<p>' . $this->value($value) . '</p>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private function buildTextSection(string $title, string $contentText): string
    {
        return '<div class="section"><h2>' . $title . '</h2><p>' . $this->value($contentText) . '</p></div>';
    }

    private function buildMarketSection(array $analysisJson): string
    {
        $market = $analysisJson['mercado'] ?? [];
        if (empty($market)) {
            return '';
        }

        $rows = [
            ['label' => 'Análisis zona', 'value' => $market['analisis_zona'] ?? null],
            ['label' => 'Comparables', 'value' => $market['comparables'] ?? null],
            ['label' => 'Tendencia', 'value' => $market['tendencia'] ?? null],
        ];

        return $this->buildSection('Mercado', $rows);
    }

    private function buildValuationSection(array $analysisJson): string
    {
        $valuation = $analysisJson['valoracion'] ?? [];
        if (empty($valuation)) {
            return '';
        }

        $rows = [
            ['label' => 'Valor estimado', 'value' => $this->formatCurrency($valuation['valor_estimado'] ?? null)],
            ['label' => 'Margen', 'value' => $this->value($valuation['margen'] ?? null)],
            ['label' => 'Oportunidad', 'value' => $this->value($valuation['es_oportunidad'] ?? null)],
        ];

        return $this->buildSection('Valoración', $rows);
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    private function formatCurrency(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'No disponible';
        }

        return number_format((float) $value, 2, ',', '.') . ' €';
    }
}
