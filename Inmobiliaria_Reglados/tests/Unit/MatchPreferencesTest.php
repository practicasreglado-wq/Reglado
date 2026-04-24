<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../backend/lib/match_preferences.php';

final class MatchPreferencesTest extends TestCase
{
    public function test_rechaza_categoria_no_permitida(): void
    {
        $this->assertNull(normalizeMatchPreferenceCategory('Casino'));
        $this->assertNull(normalizeMatchPreferenceCategory(''));
        $this->assertNull(normalizeMatchPreferenceCategory(null));
    }

    public function test_acepta_categorias_case_insensitive(): void
    {
        $this->assertSame('Hoteles', normalizeMatchPreferenceCategory('hoteles'));
        $this->assertSame('Fincas', normalizeMatchPreferenceCategory('FINCAS'));
        $this->assertSame('Edificios', normalizeMatchPreferenceCategory('  Edificios  '));
    }

    public function test_sanitize_descarta_claves_no_string(): void
    {
        $result = sanitizeMatchPreferenceAnswers([
            0       => 'valor1',
            'ok'    => 'valor2',
            null    => 'valor3',
        ]);
        $this->assertArrayNotHasKey(0, $result);
        $this->assertArrayHasKey('ok', $result);
        $this->assertSame('valor2', $result['ok']);
    }

    public function test_sanitize_trunca_valores_largos_a_500(): void
    {
        $longValue = str_repeat('A', 1000);
        $result = sanitizeMatchPreferenceAnswers(['key' => $longValue]);
        $this->assertSame(500, mb_strlen($result['key']));
    }

    public function test_sanitize_limita_a_50_claves(): void
    {
        $big = [];
        for ($i = 0; $i < 100; $i++) {
            $big["key_{$i}"] = "value_{$i}";
        }
        $result = sanitizeMatchPreferenceAnswers($big);
        $this->assertCount(50, $result);
    }

    public function test_sanitize_rechaza_claves_mas_de_100_chars(): void
    {
        $longKey = str_repeat('K', 101);
        $result = sanitizeMatchPreferenceAnswers([$longKey => 'valor']);
        $this->assertEmpty($result);
    }

    public function test_sanitize_acepta_numeros_y_bools(): void
    {
        $result = sanitizeMatchPreferenceAnswers([
            'num'    => 42,
            'float'  => 3.14,
            'bool_t' => true,
            'bool_f' => false,
        ]);
        $this->assertSame('42', $result['num']);
        $this->assertSame('3.14', $result['float']);
        $this->assertSame('1', $result['bool_t']);
        // false se convierte a "0" pero cleanedValue vacío se descarta → verificamos que sí está
        $this->assertSame('0', $result['bool_f']);
    }

    public function test_sanitize_descarta_strings_vacios(): void
    {
        $result = sanitizeMatchPreferenceAnswers([
            'empty'     => '',
            'onlyspace' => '   ',
            'valid'     => 'ok',
        ]);
        $this->assertArrayNotHasKey('empty', $result);
        $this->assertArrayNotHasKey('onlyspace', $result);
        $this->assertArrayHasKey('valid', $result);
    }
}
