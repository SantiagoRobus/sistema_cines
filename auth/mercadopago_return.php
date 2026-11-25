<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if (!isset($_SESSION['usuario_id']) || (int) ($_SESSION['rol_id'] ?? 0) !== 3) {
    header('Location: ../views/public/login.php?msg=payment_return');
    exit();
}

$paymentId = $_GET['payment_id'] ?? $_GET['collection_id'] ?? '';
if (!is_string($paymentId) || !ctype_digit($paymentId)) {
    header('Location: ../views/cliente/dashboard_cliente.php?msg=payment_pending');
    exit();
}

require_once '../config/conexion.php';
require_once '../config/mercadopago.php';
require_once '../config/pagos.php';

try {
    $payment = mercadopago_solicitud('GET', '/v1/payments/' . rawurlencode($paymentId));
    $result = aplicar_estado_pago($pdo, $payment);

    if (!$result) {
        header('Location: ../views/cliente/dashboard_cliente.php?msg=payment_pending');
        exit();
    }

    $stmt = $pdo->prepare(
        'SELECT 1 FROM RESERVA WHERE cod_reserva = :reserva AND cod_cliente = :cliente'
    );
    $stmt->execute([
        ':reserva' => $result['cod_reserva'],
        ':cliente' => (int) $_SESSION['usuario_id'],
    ]);

    if (!$stmt->fetchColumn()) {
        http_response_code(403);
        exit('No puedes consultar esta reserva.');
    }

    $messages = [
        'approved' => 'paid',
        'approved_review' => 'payment_review',
        'rejected' => 'payment_failed',
        'cancelled' => 'payment_failed',
    ];
    $message = $messages[$result['estado']] ?? 'payment_pending';
    header('Location: ../views/cliente/dashboard_cliente.php?msg=' . $message);
} catch (Exception $e) {
    error_log('No se pudo verificar el retorno de Mercado Pago: ' . $e->getMessage());
    header('Location: ../views/cliente/dashboard_cliente.php?msg=payment_pending');
}
exit();
