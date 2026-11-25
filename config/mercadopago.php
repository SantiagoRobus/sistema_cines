<?php

function mercadopago_modo(): string
{
    $modo = getenv('MP_MODE') ?: 'sandbox';
    $accessToken = getenv('MP_ACCESS_TOKEN');

    if (!in_array($modo, ['sandbox', 'production'], true) || !$accessToken) {
        throw new RuntimeException('Configura el entorno y las credenciales de Mercado Pago.');
    }

    $prefijoEsperado = $modo === 'sandbox' ? 'TEST-' : 'APP_USR-';
    if (!str_starts_with($accessToken, $prefijoEsperado)) {
        throw new RuntimeException('Las credenciales de Mercado Pago no corresponden al entorno configurado.');
    }

    return $modo;
}

function mercadopago_solicitud(string $metodo, string $recurso, ?array $datos = null): array
{
    mercadopago_modo();
    $accessToken = getenv('MP_ACCESS_TOKEN');

    $url = 'https://api.mercadopago.com' . $recurso;
    $headers = ['Authorization: Bearer ' . $accessToken, 'Accept: application/json'];
    $curl = curl_init($url);

    if ($curl === false) {
        throw new RuntimeException('No se pudo inicializar cURL.');
    }

    $opciones = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ];

    if ($datos !== null) {
        $json = json_encode($datos, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $opciones[CURLOPT_POSTFIELDS] = $json;
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'X-Idempotency-Key: ' . bin2hex(random_bytes(16));
        $opciones[CURLOPT_HTTPHEADER] = $headers;
    }

    curl_setopt_array($curl, $opciones);
    $respuesta = curl_exec($curl);
    $estadoHttp = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $errorCurl = curl_error($curl);
    curl_close($curl);

    if ($respuesta === false || $errorCurl !== '') {
        throw new RuntimeException('No se pudo conectar con Mercado Pago.');
    }

    $resultado = json_decode($respuesta, true);
    if (!is_array($resultado) || $estadoHttp < 200 || $estadoHttp >= 300) {
        error_log('Mercado Pago respondió con HTTP ' . $estadoHttp . '.');
        throw new RuntimeException('Mercado Pago no pudo procesar la solicitud.');
    }

    return $resultado;
}

function uuid_v4(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);

    return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-'
        . substr($hex, 16, 4) . '-' . substr($hex, 20);
}
