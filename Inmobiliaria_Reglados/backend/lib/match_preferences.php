<?php
declare(strict_types=1);

/**
 * Sanitización de las "preferencias de match" (cuestionario que rellena el
 * comprador para perfilar qué le interesa).
 *
 * Las respuestas vienen del frontend como un objeto libre y se guardan en
 * la tabla `match_preferences` como JSON. Aquí validamos que la categoría
 * esté en una lista cerrada (whitelist) y que las respuestas tengan tipos
 * razonables — el resto de filtrado lo hace el endpoint que consume estos
 * datos para emparejar con propiedades.
 *
 * Tabla relacionada: api/save_preferences.php / api/match_preferences.php.
 */

/** Whitelist de categorías válidas. Cualquier valor fuera se ignora. */
const MATCH_PREFERENCE_CATEGORIES = ["Hoteles", "Fincas", "Parking", "Edificios", "Activos"];

/**
 * Normaliza la categoría: trim, comparación case-insensitive contra la
 * whitelist, devuelve la versión "canónica" (con mayúsculas correctas) o
 * null si no encaja con ninguna de las permitidas.
 */
function normalizeMatchPreferenceCategory(?string $value): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $trimmed = trim($value);

    if ($trimmed === "") {
        return null;
    }

    foreach (MATCH_PREFERENCE_CATEGORIES as $allowed) {
        if (strcasecmp($allowed, $trimmed) === 0) {
            return $allowed;
        }
    }

    return null;
}

/**
 * Limpia el array de respuestas del cuestionario antes de serializarlo a JSON.
 * Aplica topes anti-abuso (50 keys, 100 chars en key, 500 chars en valor) y
 * convierte tipos no-string (números, bools) a representación textual.
 */
function sanitizeMatchPreferenceAnswers($rawAnswers): array
{
    if (!is_array($rawAnswers)) {
        return [];
    }

    // Topes anti-abuso: el formulario real tiene ~5-15 preguntas con respuestas
    // cortas. Estos límites descartan payloads anómalos sin afectar al uso
    // legítimo.
    $MAX_KEYS = 50;
    $MAX_VALUE_LENGTH = 500;
    $MAX_KEY_LENGTH = 100;

    $cleaned = [];

    foreach ($rawAnswers as $key => $value) {
        if (count($cleaned) >= $MAX_KEYS) {
            break;
        }

        if (!is_string($key)) {
            continue;
        }

        $normalizedKey = trim($key);
        if ($normalizedKey === "" || mb_strlen($normalizedKey) > $MAX_KEY_LENGTH) {
            continue;
        }

        $cleanedValue = null;

        if (is_string($value)) {
            $cleanedValue = trim($value);
        } elseif (is_numeric($value)) {
            $cleanedValue = (string) $value;
        } elseif (is_bool($value)) {
            $cleanedValue = $value ? "1" : "0";
        }

        if ($cleanedValue === null || $cleanedValue === "") {
            continue;
        }

        if (mb_strlen($cleanedValue) > $MAX_VALUE_LENGTH) {
            $cleanedValue = mb_substr($cleanedValue, 0, $MAX_VALUE_LENGTH);
        }

        $cleaned[$normalizedKey] = $cleanedValue;
    }

    return $cleaned;
}

/** Serializa las respuestas a JSON. Si falla la codificación devuelve "{}". */
function encodeMatchPreferenceAnswers(array $answers): string
{
    return json_encode($answers, JSON_UNESCAPED_UNICODE) ?: "{}";
}

/**
 * Inverso de encode. Si el JSON está vacío, mal formado, o trae valores no
 * escalares, devuelve [] o filtra los valores inválidos sin lanzar.
 */
function decodeMatchPreferenceAnswers(?string $json): array
{
    if (!is_string($json) || trim($json) === "") {
        return [];
    }

    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
        return [];
    }

    $cleaned = [];
    foreach ($decoded as $key => $value) {
        if (!is_string($key)) {
            continue;
        }

        if (is_string($value)) {
            $cleaned[$key] = $value;
        } elseif (is_numeric($value)) {
            $cleaned[$key] = (string) $value;
        }
    }

    return $cleaned;
}

/**
 * Devuelve las preferencias activas del usuario (categoría + respuestas
 * deserializadas + metadatos). Si no existen, devuelve [].
 */
function fetchUserMatchPreferences(PDO $pdo, int $userId): array
{
    if ($userId <= 0) {
        return [];
    }

    $stmt = $pdo->prepare(
        "SELECT category, answers_json, is_active, last_used_at, created_at, updated_at
         FROM user_match_preferences
         WHERE user_id = :user_id
         LIMIT 1"
    );

    $stmt->execute(["user_id" => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return [];
    }

    return [
        "category" => $row["category"] ?? null,
        "answers" => decodeMatchPreferenceAnswers($row["answers_json"] ?? null),
        "is_active" => (bool) ($row["is_active"] ?? false),
        "last_used_at" => $row["last_used_at"] ?? null,
        "created_at" => $row["created_at"] ?? null,
        "updated_at" => $row["updated_at"] ?? null,
    ];
}

/**
 * Crea o actualiza las preferencias del usuario en una sola query (UPSERT
 * por user_id). Marca is_active = 1 y refresca last_used_at.
 *
 * Lanza InvalidArgumentException si la categoría no está en la whitelist.
 * Devuelve el registro recién insertado/actualizado.
 */
function upsertUserMatchPreferences(PDO $pdo, int $userId, string $category, array $answers): array
{
    $normalizedCategory = normalizeMatchPreferenceCategory($category);

    if ($normalizedCategory === null) {
        throw new InvalidArgumentException("Categoría no permitida.");
    }

    $sanitizedAnswers = sanitizeMatchPreferenceAnswers($answers);
    $answersJson = encodeMatchPreferenceAnswers($sanitizedAnswers);

    $stmt = $pdo->prepare(
        "INSERT INTO user_match_preferences (user_id, category, answers_json, is_active, last_used_at)
         VALUES (:user_id, :category, :answers_json, 1, CURRENT_TIMESTAMP)
         ON DUPLICATE KEY UPDATE
             category = VALUES(category),
             answers_json = VALUES(answers_json),
             is_active = 1,
             last_used_at = CURRENT_TIMESTAMP"
    );

    $stmt->execute([
        "user_id" => $userId,
        "category" => $normalizedCategory,
        "answers_json" => $answersJson,
    ]);

    return fetchUserMatchPreferences($pdo, $userId);
}

/**
 * Borra "blandamente" las preferencias del usuario: pone is_active = 0,
 * limpia las respuestas y resetea last_used_at. Mantiene el registro para no
 * perder histórico.
 */
function deactivateUserMatchPreferences(PDO $pdo, int $userId): void
{
    if ($userId <= 0) {
        return;
    }

    $stmt = $pdo->prepare(
        "UPDATE user_match_preferences
         SET is_active = 0,
             answers_json = '{}',
             last_used_at = NULL
         WHERE user_id = :user_id"
    );

    $stmt->execute(["user_id" => $userId]);
}
