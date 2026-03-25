<?php
declare(strict_types=1);

class ClaudeClient
{
    private string $apiKey;
    private string $endpoint;
    private string $model;
    private array $requiredFichaFields = [
        'tipo_propiedad',
        'categoria',
        'ciudad',
        'zona',
        'direccion',
        'metros_cuadrados',
        'precio',
    ];

    public function __construct(string $apiKey, string $endpoint, string $model)
    {
        if (trim($apiKey) === '') {
            throw new RuntimeException('Claude API key no proporcionada');
        }

        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
        $this->model = $model;
    }

    public function analyzeStructuredPropertyText(string $text): array
    {
        return $this->requestWithPrompt($this->buildStructuredPrompt($text));
    }

    private function requestWithPrompt(string $prompt): array
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => 3000,
            'temperature' => 0.2,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        $response = $this->performRequest($payload);

        if (!isset($response['content'][0]['text'])) {
            throw new RuntimeException('Respuesta inválida de Claude');
        }

        $textResponse = $response['content'][0]['text'];
        $decoded = $this->extractJson($textResponse);
        return $this->validateResponse($decoded);
    }

    private function validateResponse(array $decoded): array
    {
        if (!isset($decoded['ficha_web']) || !is_array($decoded['ficha_web'])) {
            throw new RuntimeException('Claude no devolvió el bloque ficha_web');
        }

        if (!isset($decoded['dossier_inversion']) || !is_array($decoded['dossier_inversion'])) {
            $decoded['dossier_inversion'] = [];
        }

        $ficha = array_map(function ($value) {
            if (is_string($value)) {
                return trim($value);
            }
            return $value;
        }, $decoded['ficha_web']);

        foreach ($this->requiredFichaFields as $field) {
            if (!array_key_exists($field, $ficha) || $ficha[$field] === null || $ficha[$field] === '') {
                throw new RuntimeException("Campo requerido faltante en ficha_web: {$field}");
            }
        }

        $decoded['ficha_web'] = $ficha;
        return $decoded;
    }

    private function buildStructuredPrompt(string $text): string
    {
            return <<<PROMPT
        Analiza el siguiente texto inmobiliario y devuelve exclusivamente un JSON válido, sin explicaciones, sin markdown y sin texto adicional.

        Debes extraer la información en esta estructura exacta:

        {
        "ficha_web": {
            "tipo_propiedad": string|null,
            "categoria": string|null,
            "ciudad": string|null,
            "zona": string|null,
            "direccion": string|null,
            "metros_cuadrados": number|null,
            "precio": number|null
        },
        "dossier_inversion": {
            "ubicacion_completa": string|null,
            "codigo_postal": string|null,
            "superficie_parcela": number|null,
            "superficie_construida": number|null,
            "uso_principal": string|null,
            "uso_alternativo": string|null,
            "altura": string|null,
            "norma_zonal": string|null,
            "precio_inicial": number|null,
            "precio_minimo_cierre": number|null,
            "repercusion_techo": number|null,
            "propiedad_tipo": string|null,
            "se_entrega_vacio": boolean|null,
            "precio_obra_nueva_zona_min": number|null,
            "precio_obra_nueva_zona_max": number|null,
            "honorarios_comprador_pct": number|null,
            "resumen_ejecutivo": string|null,
            "riesgos": [],
            "oportunidades": [],
            "observaciones": []
        }
        }

        Reglas obligatorias:
        - No inventes datos.
        - Si un dato no aparece claramente, usa null.
        - Los arrays deben existir siempre, aunque estén vacíos.
        - "precio", "precio_inicial", "precio_minimo_cierre", "repercusion_techo", "metros_cuadrados", "superficie_parcela", "superficie_construida", "precio_obra_nueva_zona_min", "precio_obra_nueva_zona_max", "honorarios_comprador_pct" deben ser números sin símbolos.
        - "se_entrega_vacio" debe ser true, false o null.
        - "categoria" debe ser una categoría apta para la web, por ejemplo: "edificios", "hoteles", "fincas", "parking", "activos".
        - "direccion" debe ser corta para la ficha web.
        - "ubicacion_completa" debe ser la dirección completa de dossier.
        - "resumen_ejecutivo" debe ser breve, profesional y útil para inversión.

        Texto a analizar:
        PROMPT
            . "\n" . trim($text);
    }

    private function performRequest(array $payload): array
    {
        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('Error en Claude: ' . $error);
        }

        $decoded = json_decode($raw, true);

        if ($status >= 400) {
            throw new RuntimeException('Claude API ' . $status . ': ' . json_encode($decoded));
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('Respuesta no válida de Claude');
        }

        return $decoded;
    }

    private function extractJson(string $text): array
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false) {
            throw new RuntimeException('Claude no devolvió JSON');
        }

        $json = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('JSON inválido de Claude');
        }

        return $decoded;
    }
}
