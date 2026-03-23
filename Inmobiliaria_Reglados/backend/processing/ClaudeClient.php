<?php
declare(strict_types=1);

class ClaudeClient
{
    private string $apiKey;
    private string $endpoint;
    private string $model;

    public function __construct(string $apiKey, string $endpoint, string $model)
    {
        if (trim($apiKey) === '') {
            throw new RuntimeException('Claude API key no proporcionada');
        }

        $this->apiKey = $apiKey;
        $this->endpoint = $endpoint;
        $this->model = $model;
    }

    public function analyzeSimpleDocument(string $text): array
    {
        return $this->requestWithPrompt($this->buildSimplePrompt($text));
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
        return $this->extractJson($textResponse);
    }

    private function buildSimplePrompt(string $text): string
    {
        return "
Analiza el siguiente texto inmobiliario y extrae únicamente:
* tipo_propiedad
* ciudad
* zona
* metros_cuadrados
* precio

Devuelve un JSON válido con esos campos.
No inventes datos. Si no aparece un campo, pon null.

Texto:
" . trim($text);
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
