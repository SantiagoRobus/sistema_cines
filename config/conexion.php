<?php
// Configuración de PostgreSQL mediante variables de entorno.
date_default_timezone_set('America/Bogota');
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '5453';
$dbname = getenv('DB_NAME') ?: 'sistema_cines';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASSWORD');

if ($password === false) {
    error_log('Falta configurar la variable de entorno DB_PASSWORD.');
    http_response_code(500);
    exit('Falta configurar la contraseña de la base de datos.');
}

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET TIME ZONE 'America/Bogota'");
} catch (PDOException $e) {
    error_log('Error al conectar con PostgreSQL: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa la configuración del servidor.');
}