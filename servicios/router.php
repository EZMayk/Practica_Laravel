<?php

/**
 * Servicios de prueba independientes (usuarios y estados) para la Práctica 3.3.
 *
 * Uso: php -S 127.0.0.1:9001 servicios/router.php
 *
 * Variable de entorno SERVICIO_MODO para simular fallos:
 *   normal (por defecto) | lento (responde en 5 s, supera el timeout de 3 s) | error (HTTP 500)
 */
header('Content-Type: application/json');

$correlationId = $_SERVER['HTTP_X_CORRELATION_ID'] ?? '-';
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$modo = getenv('SERVICIO_MODO') ?: 'normal';

error_log("[servicio] {$ruta} correlation_id={$correlationId} modo={$modo}");

if ($modo === 'lento') {
    sleep(5);
}

if ($modo === 'error') {
    http_response_code(500);
    echo json_encode(['error' => 'fallo interno simulado']);

    return;
}

if ($ruta === '/usuarios/7') {
    echo json_encode(['id' => 7, 'nombre' => 'Ana']);

    return;
}

if ($ruta === '/estados/101') {
    echo json_encode(['solicitud_id' => 101, 'estado' => 'pendiente']);

    return;
}

http_response_code(404);
echo json_encode(['error' => 'no encontrado']);
