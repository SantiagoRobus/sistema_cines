<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if ((int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('No autorizado.');
}

require_once '../config/conexion.php';
require_once '../config/beneficios.php';
$solicitudId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    $stmt = $pdo->prepare(
        "SELECT archivo_clave, archivo_tipo
         FROM SOLICITUD_BENEFICIO
         WHERE cod_solicitud = :id AND estado = 'pendiente'"
    );
    $stmt->execute([':id' => $solicitudId]);
    $archivo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$archivo || !$archivo['archivo_clave']) {
        http_response_code(404);
        exit('El soporte no está disponible.');
    }

    $clave = basename($archivo['archivo_clave']);
    $ruta = directorio_soportes_beneficio() . DIRECTORY_SEPARATOR . $clave;
    if (!is_file($ruta)) {
        http_response_code(404);
        exit('El soporte no está disponible.');
    }

    header('Content-Type: ' . $archivo['archivo_tipo']);
    header('Content-Length: ' . filesize($ruta));
    header('Content-Disposition: attachment; filename="soporte-' . (int) $solicitudId . '.' . pathinfo($clave, PATHINFO_EXTENSION) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    readfile($ruta);
} catch (PDOException $e) {
    error_log('No se pudo recuperar el soporte de beneficio: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo recuperar el soporte.');
}
