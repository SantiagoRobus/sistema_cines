<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (int) ($_SESSION['rol_id'] ?? 0) !== 2) {
    header('Location: ../views/public/login.php');
    exit();
}

verificar_token_csrf();
require_once '../config/conexion.php';
require_once '../config/beneficios.php';

$email = filter_var(post_texto('email'), FILTER_VALIDATE_EMAIL);
$tipo = post_texto('tipo_beneficio');
if ($email === false || !isset(tipos_beneficio()[$tipo])) {
    header('Location: ../views/empleado/dashboard_empleado.php?msg=invalid_benefit');
    exit();
}

try {
    $stmt = $pdo->prepare(
        "SELECT u.cod_usuario FROM USUARIO u
         JOIN CLIENTE c ON c.cod_usuario = u.cod_usuario
         WHERE u.email = :email AND u.activo = '1'"
    );
    $stmt->execute([':email' => $email]);
    $clienteId = $stmt->fetchColumn();
    if (!$clienteId) {
        header('Location: ../views/empleado/dashboard_empleado.php?msg=customer_not_found');
        exit();
    }

    $stmt = $pdo->prepare(
        "INSERT INTO CLIENTE_BENEFICIO
            (cod_usuario, tipo_beneficio, estado, metodo_validacion, vigente_hasta, validado_por)
         VALUES (:cliente, :tipo, 'aprobado', 'presencial', CURRENT_DATE + INTERVAL '1 year', :empleado)
         ON CONFLICT (cod_usuario) DO UPDATE SET
            tipo_beneficio = EXCLUDED.tipo_beneficio,
            estado = 'aprobado',
            metodo_validacion = 'presencial',
            vigente_hasta = EXCLUDED.vigente_hasta,
            validado_por = EXCLUDED.validado_por,
            actualizado_en = CURRENT_TIMESTAMP"
    );
    $stmt->execute([
        ':cliente' => $clienteId,
        ':tipo' => $tipo,
        ':empleado' => (int) $_SESSION['usuario_id'],
    ]);
    header('Location: ../views/empleado/dashboard_empleado.php?msg=benefit_validated');
} catch (PDOException $e) {
    error_log('No se pudo validar el beneficio presencial: ' . $e->getMessage());
    header('Location: ../views/empleado/dashboard_empleado.php?msg=error');
}
exit();
