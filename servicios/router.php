<?php

/**
 * Simula cobertura y estimación en procesos independientes.
 * Iniciar con SERVICIO_TIPO=cobertura en :9001 o SERVICIO_TIPO=estimacion en :9002.
 */
header('Content-Type: application/json');

$tipo = getenv('SERVICIO_TIPO') ?: 'cobertura';
$modo = getenv('SERVICIO_MODO') ?: 'normal';
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$correlationId = $_SERVER['HTTP_X_CORRELATION_ID'] ?? '-';

error_log("[{$tipo}] {$ruta} correlation_id={$correlationId} modo={$modo}");

if ($modo === 'lento') {
    sleep(5);
}

if ($modo === 'error') {
    http_response_code(500);
    echo json_encode(['error' => 'fallo interno simulado']);

    return;
}

if (($tipo === 'cobertura' && $ruta !== '/cobertura')
    || ($tipo === 'estimacion' && $ruta !== '/estimacion')
    || ! in_array($tipo, ['cobertura', 'estimacion'], true)) {
    http_response_code(404);
    echo json_encode(['error' => 'no encontrado']);

    return;
}

$lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);

if ($lat === false || $lat === null || $lng === false || $lng === null) {
    http_response_code(400);
    echo json_encode(['error' => 'coordenadas inválidas']);

    return;
}

if ($tipo === 'cobertura') {
    echo json_encode(['disponible' => $lat >= -1.1 && $lat <= -0.8 && $lng >= -80.8 && $lng <= -80.5]);

    return;
}

$zonaCentral = $lat >= -1.0 && $lat <= -0.9 && $lng >= -80.7 && $lng <= -80.6;
echo json_encode([
    'costo_estimado' => $zonaCentral ? 2.5 : 4.0,
    'tiempo_minutos' => $zonaCentral ? 30 : 45,
]);
