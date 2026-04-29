<?php
declare(strict_types=1);

/**
 * Inicializa la conexión PDO global $pdo contra la BD inmobiliaria.
 *
 * Carga el .env primero (idempotente: si ya estaba cargado, no rehace nada),
 * y abre la conexión usando DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS
 * del entorno. Esto es lo que se incluye desde casi todos los endpoints de
 * api/, los crons y los lib/ que necesitan PDO.
 *
 * Antes había queries cross-database a `regladousers.users`, gestionadas por
 * el reescritor DbAliasPdo. Tras la migración a la Opción C, todos los
 * accesos a usuarios pasan por HTTP a ApiLogin (lib/apiloging_client.php),
 * así que el reescritor solo conserva el alias `inmobiliaria.` por
 * compatibilidad histórica con SQL que aún use ese prefijo.
 */

require_once __DIR__ . '/../lib/env_loader.php';

loadEnv(dirname(__DIR__) . '/.env');

/**
 * Wrapper de PDO que reescribe automáticamente el prefijo `inmobiliaria.`
 * por el nombre real de BD del entorno actual.
 *
 * El reemplazo se aplica en query() / prepare() / exec(), antes de mandar
 * el SQL a MySQL. Si el nombre ya coincide con el del .env (caso local),
 * el strtr es no-op.
 */
class DbAliasPdo extends PDO
{
    /** @var array<string,string> */
    private array $replacements;

    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);

        $this->replacements = [
            'inmobiliaria.'  => dbNameInmobiliaria() . '.',
            '`inmobiliaria`.' => '`' . dbNameInmobiliaria() . '`.',
        ];
    }

    private function rewrite(string $sql): string
    {
        return strtr($sql, $this->replacements);
    }

    #[\ReturnTypeWillChange]
    public function query(string $query, ?int $fetchMode = null, ...$fetchModeArgs)
    {
        $query = $this->rewrite($query);
        if ($fetchMode === null) {
            return parent::query($query);
        }
        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }

    #[\ReturnTypeWillChange]
    public function prepare(string $query, array $options = [])
    {
        $query = $this->rewrite($query);
        return parent::prepare($query, $options);
    }

    #[\ReturnTypeWillChange]
    public function exec(string $statement)
    {
        $statement = $this->rewrite($statement);
        return parent::exec($statement);
    }
}

$pdo = new DbAliasPdo(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        (string) getenv('DB_HOST'),
        (string) (getenv('DB_PORT') ?: '3306'),
        dbNameInmobiliaria()
    ),
    (string) getenv('DB_USER'),
    (string) getenv('DB_PASS'),
    [
        PDO::ATTR_ERRMODE         => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
