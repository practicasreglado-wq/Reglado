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

    public function generateDocuments(array $data, int $assetId): array
    {
        $dossier = "dossier_{$assetId}.pdf";
        $conf = "confidencialidad_{$assetId}.pdf";
        $intention = "intencion_{$assetId}.pdf";

        $dossierPath = $this->storageDir . DIRECTORY_SEPARATOR . $dossier;
        $confPath = $this->storageDir . DIRECTORY_SEPARATOR . $conf;
        $intentionPath = $this->storageDir . DIRECTORY_SEPARATOR . $intention;

        $this->createPdf($this->getDossier($data), $dossierPath);
        $this->createPdf($this->getConfidencialidad($data), $confPath);
        $this->createPdf($this->getIntencion($data), $intentionPath);
        
        return [
            'dossier' => $dossier,
            'confidentiality' => $conf,
            'intention' => $intention,
        ];
    }

    private function createPdf(string $text, string $path): void
{
    try {
        error_log("PDF START");

        $dompdf = new Dompdf();

        $html = "<html><body style='white-space: pre-wrap; font-family: Arial; font-size:12px;'>"
            . htmlspecialchars($text)
            . "</body></html>";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();

        $output = $dompdf->output();

        if (!$output) {
            throw new Exception("PDF vacío");
        }

        file_put_contents($path, $output);

        error_log("PDF OK: " . $path);

    } catch (\Throwable $e) {
        error_log("ERROR PDF: " . $e->getMessage());
    }
}

    private function getConfidencialidad(array $data): string
    {
        return "
    ACUERDO DE CONFIDENCIALIDAD

    Activo ubicado en:
    Ciudad: {$this->value($data['ciudad'] ?? null)}
    Zona: {$this->value($data['zona'] ?? null)}

    Tipo de propiedad: {$this->value($data['tipo_propiedad'] ?? null)}
    Precio estimado: {$this->value($data['precio'] ?? null)} €

    Ambas partes se comprometen a mantener la confidencialidad de la información.

    Prohibida su divulgación sin autorización expresa.

    Firmado:

    __________________________
    ";
    }

    private function getIntencion(array $data): string
    {
        return "
    CARTA DE INTENCIÓN DE COMPRA

    Detalles del activo:

    Tipo: {$this->value($data['tipo_propiedad'] ?? null)}
    Ciudad: {$this->value($data['ciudad'] ?? null)}
    Zona: {$this->value($data['zona'] ?? null)}
    Metros: {$this->value($data['metros'] ?? null)}
    Habitaciones: {$this->value($data['habitaciones'] ?? null)}
    Precio: {$this->value($data['precio'] ?? null)} €

    Esta carta no constituye compromiso contractual.

    Firma:

    __________________________
    ";
    }

    private function getDossier(array $data): string
    {
        $v = fn($k) => $this->value($data[$k] ?? null);
        $c = fn($k) => $this->value($data['caracteristicas'][$k] ?? null);

        return "

============================================================
DOSSIER DE INVERSIÓN INMOBILIARIA
============================================================

------------------------------------------------------------
1. RESUMEN EJECUTIVO
------------------------------------------------------------

Se presenta una oportunidad de inversión inmobiliaria en la ciudad de {$v('ciudad')}, 
concretamente en la zona de {$v('zona')}.

El activo corresponde a un {$v('tipo_propiedad')} con una superficie aproximada 
de {$v('metros')} m² y un precio estimado de {$v('precio')} €.

Se trata de una oportunidad con potencial de rentabilidad, orientada tanto a 
inversores patrimonialistas como a estrategias de valor añadido.

------------------------------------------------------------
2. DESCRIPCIÓN DEL ACTIVO
------------------------------------------------------------

Tipo de activo: {$v('tipo_propiedad')}
Ubicación: {$v('zona')}, {$v('ciudad')}
Superficie construida: {$v('metros')} m²
Número de habitaciones: {$v('habitaciones')}
Precio de adquisición: {$v('precio')} €

El activo presenta características que permiten su explotación en diferentes 
modalidades, incluyendo alquiler residencial o revalorización mediante reforma.

------------------------------------------------------------
3. UBICACIÓN Y ENTORNO
------------------------------------------------------------

La propiedad se sitúa en una zona consolidada de {$v('ciudad')}, concretamente en {$v('zona')},
con acceso a servicios, transporte y equipamientos urbanos.

El entorno presenta:
- Buena conectividad
- Demanda residencial activa
- Potencial de revalorización

------------------------------------------------------------
4. CARACTERÍSTICAS DEL ACTIVO
------------------------------------------------------------

Ascensor: {$c('ascensor')}
Garaje: {$c('garaje')}
Estado del inmueble: {$c('estado')}

El inmueble dispone de características que lo hacen atractivo tanto para usuarios finales
como para inversores.

------------------------------------------------------------
5. ANÁLISIS DE MERCADO
------------------------------------------------------------

El mercado inmobiliario en {$v('ciudad')} ha mostrado una tendencia de crecimiento sostenido,
especialmente en zonas como {$v('zona')}.

Factores clave:
- Incremento de la demanda
- Escasez de oferta en zonas céntricas
- Estabilidad del mercado residencial

------------------------------------------------------------
6. ANÁLISIS ECONÓMICO
------------------------------------------------------------

Precio de compra: {$v('precio')} €
Rentabilidad estimada: {$v('rentabilidad')}

El activo presenta potencial para:
- Generación de ingresos recurrentes
- Incremento de valor a medio plazo

------------------------------------------------------------
7. ESTRATEGIAS DE INVERSIÓN
------------------------------------------------------------

Se identifican varias estrategias posibles:

1. Alquiler residencial
2. Reforma y reventa (flipping)
3. Alquiler de media estancia

Cada estrategia deberá evaluarse en función del perfil del inversor.

------------------------------------------------------------
8. ESCENARIOS DE RENTABILIDAD
------------------------------------------------------------

Escenario conservador:
- Rentabilidad estable
- Bajo riesgo

Escenario moderado:
- Optimización mediante pequeñas mejoras

Escenario agresivo:
- Reforma integral y reventa

------------------------------------------------------------
9. RIESGOS
------------------------------------------------------------

- Fluctuaciones del mercado
- Costes imprevistos de reforma
- Periodos de vacancia

No obstante, el activo presenta una base sólida para mitigar dichos riesgos.

------------------------------------------------------------
10. ANÁLISIS TÉCNICO DEL ACTIVO
------------------------------------------------------------

El inmueble puede requerir:
- Adecuación estética
- Mejora de eficiencia energética
- Redistribución interior (opcional)

------------------------------------------------------------
11. PERFIL DE INVERSOR RECOMENDADO
------------------------------------------------------------

Este activo es adecuado para:

- Inversores patrimonialistas
- Inversores orientados a rentabilidad
- Inversores con estrategia de valor añadido

------------------------------------------------------------
12. VENTAJAS COMPETITIVAS
------------------------------------------------------------

- Ubicación estratégica
- Precio competitivo
- Versatilidad de uso

------------------------------------------------------------
13. OPORTUNIDAD DE VALOR
------------------------------------------------------------

La adquisición del activo en condiciones actuales permite capturar valor mediante:

- Gestión activa
- Optimización del activo
- Mejora de condiciones de mercado

------------------------------------------------------------
14. ESCENARIO POST-INVERSIÓN
------------------------------------------------------------

Tras la inversión, el activo puede generar:

- Flujo de caja recurrente
- Incremento de valor patrimonial

------------------------------------------------------------
15. ANÁLISIS DE DEMANDA
------------------------------------------------------------

La zona presenta una demanda constante tanto para compra como para alquiler,
lo que reduce significativamente el riesgo de vacancia.

------------------------------------------------------------
16. ANÁLISIS DE OFERTA
------------------------------------------------------------

La oferta en la zona es limitada, lo que favorece la estabilidad de precios
y la posible revalorización.

------------------------------------------------------------
17. PROYECCIÓN A FUTURO
------------------------------------------------------------

Se espera que la zona continúe en crecimiento debido a:

- Desarrollo urbano
- Mejora de infraestructuras
- Incremento de población

------------------------------------------------------------
18. CONSIDERACIONES LEGALES
------------------------------------------------------------

Se recomienda realizar:
- Due diligence legal
- Verificación registral
- Análisis urbanístico

------------------------------------------------------------
19. CONCLUSIÓN
------------------------------------------------------------

El activo representa una oportunidad sólida dentro del mercado de {$v('ciudad')},
con potencial de rentabilidad y revalorización.

------------------------------------------------------------
20. DISCLAIMER
------------------------------------------------------------

Este documento tiene carácter informativo y no constituye una oferta vinculante.
Los datos están sujetos a verificación.

============================================================

";
    }
}