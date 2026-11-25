<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    header('Location: ../views/public/login.php');
    exit();
}

verificar_token_csrf();
require_once '../config/conexion.php';
require_once '../config/beneficios.php';

$solicitudId = filter_var($_POST['cod_solicitud'] ?? null, FILTER_VALIDATE_INT);
$decision = post_texto('decision');
$motivo = post_texto('motivo');

if (
    !$solicitudId ||
    !in_array($decision, ['aprobar', 'rechazar'], true) ||
    ($decision === 'rechazar' && ($motivo === '' || strlen($motivo) > 300))
) {
    header('Location: ../views/admin/beneficios.php?msg=invalid');
    exit();
}

$archivoClave = null;
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "SELECT cod_usuario, tipo_beneficio, archivo_clave
         FROM SOLICITUD_BENEFICIO
         WHERE cod_solicitud = :id AND estado = 'pendiente'
         FOR UPDATE"
    );
    $stmt->execute([':id' => $solicitudId]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$solicitud) {
        $pdo->rollBack();
        header('Location: ../views/admin/beneficios.php?msg=not_pending');
        exit();
    }

    $archivoClave = $solicitud['archivo_clave'];
    if ($decision === 'aprobar') {
        $stmt = $pdo->prepare(
            "INSERT INTO CLIENTE_BENEFICIO
                (cod_usuario, tipo_beneficio, estado, metodo_validacion, vigente_hasta, validado_por)
             VALUES (:cliente, :tipo, 'aprobado', 'documental', CURRENT_DATE + INTERVAL '1 year', :admin)
             ON CONFLICT (cod_usuario) DO UPDATE SET
                tipo_beneficio = EXCLUDED.tipo_beneficio,
                estado = 'aprobado',
                metodo_validacion = 'documental',
                vigente_hasta = EXCLUDED.vigente_hasta,
                validado_por = EXCLUDED.validado_por,
                actualizado_en = CURRENT_TIMESTAMP"
        );
        $stmt->execute([
            ':cliente' => $solicitud['cod_usuario'],
            ':tipo' => $solicitud['tipo_beneficio'],
            ':admin' => (int) $_SESSION['usuario_id'],
        ]);
    }

    $estado = $decision === 'aprobar' ? 'aprobado' : 'rechazado';
    $stmt = $pdo->prepare(
        'UPDATE SOLICITUD_BENEFICIO
         SET estado = :estado, motivo_revision = :motivo,
             revisado_en = CURRENT_TIMESTAMP, revisado_por = :admin, archivo_clave = NULL
         WHERE cod_solicitud = :id'
    );
    $stmt->execute([
        ':estado' => $estado,
        ':motivo' => $decision === 'rechazar' ? $motivo : null,
        ':admin' => (int) $_SESSION['usuario_id'],
        ':id' => $solicitudId,
    ]);
    $pdo->commit();

    if ($archivoClave) {
        try {
            $ruta = directorio_soportes_beneficio() . DIRECTORY_SEPARATOR . basename($archivoClave);
            if (is_file($ruta) && !unlink($ruta)) {
                error_log('No se pudo eliminar un soporte de beneficio ya revisado.');
            }
        } catch (RuntimeException $e) {
            error_log('No se pudo limpiar el soporte revisado: ' . $e->getMessage());
        }
    }
    header('Location: ../views/admin/beneficios.php?msg=' . $estado);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('No se pudo resolver la solicitud de beneficio: ' . $e->getMessage());
    header('Location: ../views/admin/beneficios.php?msg=error');
}
exit();
