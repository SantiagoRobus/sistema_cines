<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (int) ($_SESSION['rol_id'] ?? 0) !== 2) {
    header('Location: ../views/public/login.php');
    exit();
}

verificar_token_csrf();
require_once '../config/conexion.php';

$stmt = $pdo->prepare('SELECT cod_cine FROM EMPLEADO WHERE cod_usuario = :id');
$stmt->execute([':id' => (int) $_SESSION['usuario_id']]);
$cineId = (int) $stmt->fetchColumn();
$paseId = filter_var($_POST['cod_pase'] ?? null, FILTER_VALIDATE_INT);
$tarifaId = filter_var($_POST['cod_tarifa'] ?? null, FILTER_VALIDATE_INT);
$cantidad = filter_var($_POST['num_entradas'] ?? null, FILTER_VALIDATE_INT);
$email = filter_var(post_texto('email_cliente'), FILTER_VALIDATE_EMAIL);

if (!$cineId || !$paseId || !$tarifaId || !$cantidad || $cantidad < 1 || $cantidad > 10 || $email === false) {
    header('Location: ../views/empleado/dashboard_empleado.php?msg=invalid_reservation');
    exit();
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'SELECT p.cod_pase, p.fecha, p.hora,
                (SELECT MAX(s.capacidad) FROM SALA s
                 WHERE s.cod_cine = p.cod_cine AND s.num_sala = p.num_sala) AS capacidad
         FROM PASE p
         WHERE p.cod_pase = :pase AND p.cod_cine = :cine
         FOR UPDATE'
    );
    $stmt->execute([':pase' => $paseId, ':cine' => $cineId]);
    $pase = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pase || !$pase['capacidad'] || strtotime($pase['fecha'] . ' ' . $pase['hora']) <= time()) {
        $pdo->rollBack();
        header('Location: ../views/empleado/dashboard_empleado.php?msg=showtime_unavailable');
        exit();
    }

    $stmt = $pdo->prepare(
        "UPDATE RESERVA SET estado = 'Cancelada'
         WHERE cod_pase = :pase AND estado = 'Pendiente'
           AND fecha_reserva <= CURRENT_TIMESTAMP - INTERVAL '20 minutes'"
    );
    $stmt->execute([':pase' => $paseId]);
    $stmt = $pdo->prepare(
        "UPDATE PAGO_RESERVA pr SET estado = 'expired', actualizado_en = CURRENT_TIMESTAMP
         FROM RESERVA r WHERE pr.cod_reserva = r.cod_reserva AND r.cod_pase = :pase
           AND r.estado = 'Cancelada' AND pr.estado = 'pending'"
    );
    $stmt->execute([':pase' => $paseId]);

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(num_entradas), 0) FROM RESERVA
         WHERE cod_pase = :pase
           AND (estado = 'Confirmada'
             OR (estado = 'Pendiente' AND fecha_reserva > CURRENT_TIMESTAMP - INTERVAL '20 minutes'))"
    );
    $stmt->execute([':pase' => $paseId]);
    $ocupados = (int) $stmt->fetchColumn();
    if ($ocupados + $cantidad > (int) $pase['capacidad']) {
        $pdo->rollBack();
        header('Location: ../views/empleado/dashboard_empleado.php?msg=showtime_full');
        exit();
    }

    $stmt = $pdo->prepare(
        "SELECT u.cod_usuario FROM USUARIO u JOIN CLIENTE c ON c.cod_usuario = u.cod_usuario
         WHERE u.email = :email AND u.activo = '1'"
    );
    $stmt->execute([':email' => $email]);
    $clienteId = $stmt->fetchColumn();
    if (!$clienteId) {
        $pdo->rollBack();
        header('Location: ../views/empleado/dashboard_empleado.php?msg=customer_not_found');
        exit();
    }

    $stmt = $pdo->prepare(
        'SELECT pr.importe, t.beneficio_requerido
         FROM PRECIO pr JOIN TARIFA t ON t.cod_tarifa = pr.cod_tarifa
         WHERE pr.cod_cine = :cine AND pr.cod_tarifa = :tarifa'
    );
    $stmt->execute([':cine' => $cineId, ':tarifa' => $tarifaId]);
    $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$tarifa || (float) $tarifa['importe'] <= 0) {
        $pdo->rollBack();
        header('Location: ../views/empleado/dashboard_empleado.php?msg=price_missing');
        exit();
    }
    if ($tarifa['beneficio_requerido'] !== null) {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM CLIENTE_BENEFICIO
             WHERE cod_usuario = :cliente AND tipo_beneficio = :tipo
               AND estado = 'aprobado' AND vigente_hasta >= CURRENT_DATE"
        );
        $stmt->execute([':cliente' => $clienteId, ':tipo' => $tarifa['beneficio_requerido']]);
        if (!$stmt->fetchColumn()) {
            $pdo->rollBack();
            header('Location: ../views/empleado/dashboard_empleado.php?msg=benefit_not_approved');
            exit();
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO RESERVA
            (cod_cliente, cod_pase, cod_tarifa, cod_empleado, num_entradas, precio_total, estado)
         VALUES (:cliente, :pase, :tarifa, :empleado, :cantidad, :total, 'Confirmada')
         RETURNING cod_reserva"
    );
    $stmt->execute([
        ':cliente' => $clienteId,
        ':pase' => $paseId,
        ':tarifa' => $tarifaId,
        ':empleado' => (int) $_SESSION['usuario_id'],
        ':cantidad' => $cantidad,
        ':total' => round((float) $tarifa['importe'] * $cantidad, 2),
    ]);
    $pdo->commit();
    header('Location: ../views/empleado/dashboard_empleado.php?msg=reservation_created');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('No se pudo crear una reserva presencial: ' . $e->getMessage());
    header('Location: ../views/empleado/dashboard_empleado.php?msg=error');
}
exit();
