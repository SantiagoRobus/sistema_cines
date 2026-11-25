<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (int) ($_SESSION['rol_id'] ?? 0) !== 2) {
    header('Location: ../views/public/login.php');
    exit();
}

verificar_token_csrf();
require_once '../config/conexion.php';
$reservaId = filter_var($_POST['cod_reserva'] ?? null, FILTER_VALIDATE_INT);
if (!$reservaId) {
    header('Location: ../views/empleado/dashboard_empleado.php?msg=invalid_reservation');
    exit();
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "SELECT r.estado, pr.estado AS estado_pago
         FROM RESERVA r
         JOIN PASE p ON p.cod_pase = r.cod_pase
         JOIN EMPLEADO e ON e.cod_cine = p.cod_cine
         LEFT JOIN PAGO_RESERVA pr ON pr.cod_reserva = r.cod_reserva
         WHERE r.cod_reserva = :reserva AND e.cod_usuario = :empleado
         FOR UPDATE OF p, r"
    );
    $stmt->execute([
        ':reserva' => $reservaId,
        ':empleado' => (int) $_SESSION['usuario_id'],
    ]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reserva || $reserva['estado'] !== 'Confirmada' || $reserva['estado_pago'] === 'approved') {
        $pdo->rollBack();
        header('Location: ../views/empleado/dashboard_empleado.php?msg=reservation_locked');
        exit();
    }

    $stmt = $pdo->prepare("UPDATE RESERVA SET estado = 'Cancelada' WHERE cod_reserva = :id");
    $stmt->execute([':id' => $reservaId]);
    $pdo->commit();
    header('Location: ../views/empleado/dashboard_empleado.php?msg=reservation_cancelled');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('No se pudo cancelar la reserva: ' . $e->getMessage());
    header('Location: ../views/empleado/dashboard_empleado.php?msg=error');
}
exit();
