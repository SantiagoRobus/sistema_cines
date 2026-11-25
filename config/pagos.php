<?php

function aplicar_estado_pago(PDO $pdo, array $pago): ?array
{
    $referencia = $pago['external_reference'] ?? '';
    $idPago = (string) ($pago['id'] ?? '');
    $estadoPago = (string) ($pago['status'] ?? '');
    $moneda = (string) ($pago['currency_id'] ?? '');
    $importe = $pago['transaction_amount'] ?? null;

    if (!is_string($referencia) || !preg_match('/\A[0-9a-f-]{36}\z/i', $referencia) || !ctype_digit($idPago) || !is_numeric($importe)) {
        return null;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'SELECT r.cod_pase FROM PAGO_RESERVA pr
             JOIN RESERVA r ON r.cod_reserva = pr.cod_reserva
             WHERE pr.referencia_externa = :referencia'
        );
        $stmt->execute([':referencia' => $referencia]);
        $codPase = $stmt->fetchColumn();

        if (!$codPase) {
            $pdo->rollBack();
            return null;
        }

        $stmt = $pdo->prepare(
            'SELECT p.cod_pase,
                    (SELECT MAX(s.capacidad) FROM SALA s
                     WHERE s.cod_cine = p.cod_cine AND s.num_sala = p.num_sala) AS capacidad
             FROM PASE p WHERE p.cod_pase = :pase FOR UPDATE'
        );
        $stmt->execute([':pase' => $codPase]);
        $pase = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare(
            "SELECT pr.cod_pago, pr.cod_reserva, pr.importe, pr.moneda, r.cod_pase, r.estado AS estado_reserva,
                    r.num_entradas,
                    CASE WHEN r.fecha_reserva > CURRENT_TIMESTAMP - INTERVAL '20 minutes' THEN 1 ELSE 0 END AS vigente
             FROM PAGO_RESERVA pr JOIN RESERVA r ON r.cod_reserva = pr.cod_reserva
             WHERE pr.referencia_externa = :referencia FOR UPDATE OF pr, r"
        );
        $stmt->execute([':referencia' => $referencia]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reserva || $reserva['moneda'] !== $moneda || number_format((float) $reserva['importe'], 2, '.', '') !== number_format((float) $importe, 2, '.', '')) {
            $pdo->rollBack();
            return null;
        }

        if ($estadoPago === 'approved' && $reserva['estado_reserva'] === 'Pendiente') {
            $stmt = $pdo->prepare(
                "SELECT COALESCE(SUM(num_entradas), 0)
                 FROM RESERVA
                 WHERE cod_pase = :pase AND cod_reserva <> :reserva
                   AND (estado = 'Confirmada'
                     OR (estado = 'Pendiente' AND fecha_reserva > CURRENT_TIMESTAMP - INTERVAL '20 minutes'))"
            );
            $stmt->execute([':pase' => $reserva['cod_pase'], ':reserva' => $reserva['cod_reserva']]);
            $ocupados = (int) $stmt->fetchColumn();
            $vigente = (int) $reserva['vigente'] === 1;

            if ($pase && $vigente && $pase['capacidad'] !== null && $ocupados + (int) $reserva['num_entradas'] <= (int) $pase['capacidad']) {
                $pdo->prepare("UPDATE RESERVA SET estado = 'Confirmada' WHERE cod_reserva = :id")
                    ->execute([':id' => $reserva['cod_reserva']]);
                $estadoInterno = 'approved';
            } else {
                $pdo->prepare("UPDATE RESERVA SET estado = 'Cancelada' WHERE cod_reserva = :id")
                    ->execute([':id' => $reserva['cod_reserva']]);
                $estadoInterno = 'approved_review';
            }
        } elseif ($estadoPago === 'approved' && $reserva['estado_reserva'] !== 'Confirmada') {
            $estadoInterno = 'approved_review';
        } elseif (in_array($estadoPago, ['rejected', 'cancelled'], true) && $reserva['estado_reserva'] === 'Pendiente') {
            $pdo->prepare("UPDATE RESERVA SET estado = 'Cancelada' WHERE cod_reserva = :id")
                ->execute([':id' => $reserva['cod_reserva']]);
            $estadoInterno = $estadoPago;
        } else {
            $estadoInterno = $estadoPago;
        }

        $stmt = $pdo->prepare(
            'UPDATE PAGO_RESERVA SET pago_proveedor_id = :pago, estado = :estado,
             actualizado_en = CURRENT_TIMESTAMP WHERE cod_pago = :id'
        );
        $stmt->execute([
            ':pago' => $idPago,
            ':estado' => $estadoInterno,
            ':id' => $reserva['cod_pago'],
        ]);
        $pdo->commit();

        return [
            'cod_reserva' => (int) $reserva['cod_reserva'],
            'estado' => $estadoInterno,
        ];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
