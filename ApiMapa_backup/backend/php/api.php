<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'Conectar.php';

$TIPO_POR_TABLA = [
    'solar'                 => 'solar', // ahora sí existe esta tabla
    'hidrogeno'             => 'hidrogeno',
    'eolica'                => 'eolica',
    'biodiesel'             => 'biodiesel',
    'biometano'             => 'biometano',
    'hidraulica'            => 'hidraulica',
    'subestaciones'         => 'subestaciones',
];

$tipo = isset($_GET['tipo']) ? strtolower($_GET['tipo']) : null;
if (!$tipo || !array_key_exists($tipo, $TIPO_POR_TABLA)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo no válido']);
    exit;
}

try {
    $db = Conectar::conexion();
    $tabla = $TIPO_POR_TABLA[$tipo];
    $stmt = $db->prepare("SELECT * FROM {$tabla}");
    $stmt->execute();
    $datos = $stmt->fetchAll();

    foreach ($datos as &$fila) {
        $fila['tipo'] = $tipo;
    }

    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al procesar la consulta',
        'message' => $e->getMessage()
    ]);
}

