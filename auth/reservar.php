<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['rol_id']) || (int) $_SESSION['rol_id'] !== 3) {
    header('Location: ../views/public/login.php');
    exit();
}

verificar_token_csrf();
require_once '../config/conexion.php';
require_once '../config/mercadopago.php';

$paseId = filter_var($_POST['cod_pase'] ?? null, FILTER_VALIDATE_INT);
$tarifaId = filter_var($_POST['cod_tarifa'] ?? null, FILTER_VALIDATE_INT);
$cantidad = filter_var($_POST['num_entradas'] ?? null, FILTER_VALIDATE_INT);
$usuarioId = (int) $_SESSION['usuario_id'];

if (!$paseId || !$tarifaId || !$cantidad || $cantidad < 1 || $cantidad > 10) {
    header('Location: ../views/cliente/dashboard_cliente.php?msg=invalid');
    exit();
}

$baseUrl = rtrim((string) getenv('APP_BASE_URL'), '/');
if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || !in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
    header('Location: ../views/cliente/dashboard_cliente.php?msg=payment_config');
    exit();
}

try {
    $modo = mercadopago_modo();
} catch (RuntimeException $e) {
    error_log('Mercado Pago no está configurado correctamente: ' . $e->getMessage());
    header('Location: ../views/cliente/dashboard_cliente.php?msg=payment_config');
    exit();
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "SELECT p.cod_pase, p.cod_cine, p.num_sala, p.fecha, p.hora, pe.titulo,
                (SELECT MAX(s.capacidad) FROM SALA s
                 WHERE s.cod_cine = p.cod_cine AND s.num_sala = p.num_sala) AS capacidad
         FROM PASE p JOIN PELICULA pe ON pe.cod_pelicula = p.cod_pelicula
         WHERE p.cod_pase = :pase FOR UPDATE OF p"
    );
    $stmt->execute([':pase' => $paseId]);
    $pase = $stmt->fetch();

    if (!$pase || !$pase['capacidad'] || strtotime($pase['fecha'] . ' ' . $pase['hora']) <= time()) {
        $pdo->rollBack();
        header('Location: ../views/cliente/dashboard_cliente.php?msg=unavailable');
        exit();
    }

    $stmt = $pdo->prepare(
        "UPDATE PAGO_RESERVA pr SET estado = 'expired', actualizado_en = CURRENT_TIMESTAMP
         FROM RESERVA r
         WHERE pr.cod_reserva = r.cod_reserva AND r.cod_pase = :pase
           AND r.estado = 'Pendiente' AND r.fecha_reserva <= CURRENT_TIMESTAMP - INTERVAL '20 minutes'
           AND pr.estado = 'pending'"
    );
    $stmt->execute([':pase' => $paseId]);

    $stmt = $pdo->prepare(
        "UPDATE RESERVA SET estado = 'Cancelada'
         WHERE cod_pase = :pase AND estado = 'Pendiente'
           AND fecha_reserva <= CURRENT_TIMESTAMP - INTERVAL '20 minutes'"
    );
    $stmt->execute([':pase' => $paseId]);

    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(num_entradas), 0)
         FROM RESERVA WHERE cod_pase = :pase
           AND (estado = 'Confirmada' OR estado = 'Pendiente')"
    );
    $stmt->execute([':pase' => $paseId]);
    $ocupados = (int) $stmt->fetchColumn();

    if ($ocupados + $cantidad > (int) $pase['capacidad']) {
        $pdo->rollBack();
        header('Location: ../views/cliente/dashboard_cliente.php?msg=full');
        exit();
    }

    $stmt = $pdo->prepare(
        'SELECT pr.importe, t.descripcion, t.beneficio_requerido
         FROM PRECIO pr JOIN TARIFA t ON t.cod_tarifa = pr.cod_tarifa
         WHERE pr.cod_cine = :cine AND pr.cod_tarifa = :tarifa'
    );
    $stmt->execute([':cine' => $pase['cod_cine'], ':tarifa' => $tarifaId]);
    $tarifa = $stmt->fetch();

    if (!$tarifa || (float) $tarifa['importe'] <= 0) {
        $pdo->rollBack();
        header('Location: ../views/cliente/dashboard_cliente.php?msg=price_missing');
        exit();
    }

    if ($tarifa['beneficio_requerido'] !== null) {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM CLIENTE_BENEFICIO
             WHERE cod_usuario = :cliente AND tipo_beneficio = :tipo
               AND estado = 'aprobado' AND vigente_hasta >= CURRENT_DATE"
        );
        $stmt->execute([
            ':cliente' => $usuarioId,
            ':tipo' => $tarifa['beneficio_requerido'],
        ]);
        if (!$stmt->fetchColumn()) {
            $pdo->rollBack();
            header('Location: ../views/cliente/dashboard_cliente.php?msg=benefit_required');
            exit();
        }
    }

    $importeUnitario = (float) $tarifa['importe'];
    $importeTotal = round($importeUnitario * $cantidad, 2);
    $referencia = uuid_v4();

    $stmt = $pdo->prepare(
        "INSERT INTO RESERVA (cod_cliente, cod_pase, cod_tarifa, num_entradas, precio_total, estado)
         VALUES (:cliente, :pase, :tarifa, :cantidad, :total, 'Pendiente')
         RETURNING cod_reserva"
    );
    $stmt->execute([
        ':cliente' => $usuarioId,
        ':pase' => $paseId,
        ':tarifa' => $tarifaId,
        ':cantidad' => $cantidad,
        ':total' => $importeTotal,
    ]);
    $reservaId = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "INSERT INTO PAGO_RESERVA (cod_reserva, referencia_externa, importe, moneda, estado)
         VALUES (:reserva, :referencia, :importe, 'COP', 'creando')
         RETURNING cod_pago"
    );
    $stmt->execute([
        ':reserva' => $reservaId,
        ':referencia' => $referencia,
        ':importe' => $importeTotal,
    ]);
    $pagoId = (int) $stmt->fetchColumn();
    $pdo->commit();

    $ahora = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $preferencia = mercadopago_solicitud('POST', '/checkout/preferences', [
        'items' => [[
            'title' => $pase['titulo'] . ' - ' . $tarifa['descripcion'],
            'quantity' => $cantidad,
            'unit_price' => $importeUnitario,
            'currency_id' => 'COP',
        ]],
        'external_reference' => $referencia,
        'back_urls' => [
            'success' => $baseUrl . '/auth/mercadopago_return.php',
            'failure' => $baseUrl . '/auth/mercadopago_return.php',
            'pending' => $baseUrl . '/auth/mercadopago_return.php',
        ],
        'auto_return' => 'approved',
        'notification_url' => $baseUrl . '/auth/mercadopago_webhook.php',
        'expires' => true,
        'expiration_date_from' => $ahora->format(DateTimeInterface::ATOM),
        'expiration_date_to' => $ahora->modify('+20 minutes')->format(DateTimeInterface::ATOM),
        'metadata' => ['cod_reserva' => $reservaId],
    ]);

    $checkoutUrl = $modo === 'sandbox'
        ? ($preferencia['sandbox_init_point'] ?? '')
        : ($preferencia['init_point'] ?? '');
    $preferenciaId = $preferencia['id'] ?? '';

    if (!is_string($checkoutUrl) || !str_starts_with($checkoutUrl, 'https://') || !is_string($preferenciaId) || $preferenciaId === '') {
        throw new RuntimeException('Mercado Pago no devolvió un enlace de pago válido.');
    }

    $stmt = $pdo->prepare(
        "UPDATE PAGO_RESERVA SET preferencia_id = :preferencia, checkout_url = :url,
         estado = 'pending', actualizado_en = CURRENT_TIMESTAMP WHERE cod_pago = :id"
    );
    $stmt->execute([':preferencia' => $preferenciaId, ':url' => $checkoutUrl, ':id' => $pagoId]);
    header('Location: ' . $checkoutUrl, true, 303);
    exit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($pagoId, $reservaId)) {
        $pdo->prepare("UPDATE PAGO_RESERVA SET estado = 'error', actualizado_en = CURRENT_TIMESTAMP WHERE cod_pago = :id")
            ->execute([':id' => $pagoId]);
        $pdo->prepare("UPDATE RESERVA SET estado = 'Cancelada' WHERE cod_reserva = :id AND estado = 'Pendiente'")
            ->execute([':id' => $reservaId]);
    }
    error_log('No se pudo iniciar el pago de una reserva: ' . $e->getMessage());
    header('Location: ../views/cliente/dashboard_cliente.php?msg=payment_error');
    exit();
}
