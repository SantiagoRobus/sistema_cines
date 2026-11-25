<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$secret = getenv('MP_WEBHOOK_SECRET');
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
$payload = json_decode(file_get_contents('php://input'), true);
$paymentId = $_GET['data_id'] ?? $payload['data']['id'] ?? '';

if (!$secret || !is_string($signature) || !is_string($requestId) || !is_string($paymentId) || !ctype_digit($paymentId)) {
    http_response_code(400);
    exit();
}

$signatureParts = [];
foreach (explode(',', $signature) as $part) {
    $pair = explode('=', trim($part), 2);
    if (count($pair) === 2) {
        $signatureParts[$pair[0]] = $pair[1];
    }
}

$timestamp = $signatureParts['ts'] ?? '';
$receivedSignature = $signatureParts['v1'] ?? '';
if (!ctype_digit($timestamp) || $receivedSignature === '') {
    http_response_code(401);
    exit();
}

$manifest = 'id:' . strtolower($paymentId) . ';request-id:' . $requestId . ';ts:' . $timestamp . ';';
$expectedSignature = hash_hmac('sha256', $manifest, $secret);
if (!hash_equals($expectedSignature, $receivedSignature)) {
    http_response_code(401);
    exit();
}

require_once '../config/conexion.php';
require_once '../config/mercadopago.php';
require_once '../config/pagos.php';

try {
    $payment = mercadopago_solicitud('GET', '/v1/payments/' . rawurlencode($paymentId));
    if (!aplicar_estado_pago($pdo, $payment)) {
        http_response_code(202);
        exit();
    }
    http_response_code(200);
} catch (Exception $e) {
    error_log('No se pudo procesar la notificación de Mercado Pago: ' . $e->getMessage());
    http_response_code(500);
}
