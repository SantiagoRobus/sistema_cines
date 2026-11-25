<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (int) ($_SESSION['rol_id'] ?? 0) !== 3) {
    header('Location: ../views/public/login.php');
    exit();
}

verificar_token_csrf();
require_once '../config/conexion.php';
require_once '../config/beneficios.php';

$tipo = post_texto('tipo_beneficio');
if (!isset(tipos_beneficio()[$tipo])) {
    header('Location: ../views/cliente/dashboard_cliente.php?benefit=invalid');
    exit();
}

$archivoGuardado = null;
try {
    $clienteId = (int) $_SESSION['usuario_id'];
    $stmt = $pdo->prepare(
        "SELECT 1 FROM SOLICITUD_BENEFICIO WHERE cod_usuario = :cliente AND estado = 'pendiente'"
    );
    $stmt->execute([':cliente' => $clienteId]);
    if ($stmt->fetchColumn()) {
        header('Location: ../views/cliente/dashboard_cliente.php?benefit=pending');
        exit();
    }

    $stmt = $pdo->prepare(
        "SELECT 1 FROM CLIENTE_BENEFICIO
         WHERE cod_usuario = :cliente AND estado = 'aprobado' AND vigente_hasta >= CURRENT_DATE"
    );
    $stmt->execute([':cliente' => $clienteId]);
    if ($stmt->fetchColumn()) {
        header('Location: ../views/cliente/dashboard_cliente.php?benefit=already');
        exit();
    }

    $archivoGuardado = guardar_soporte_beneficio($_FILES['soporte'] ?? []);
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "INSERT INTO SOLICITUD_BENEFICIO
            (cod_usuario, tipo_beneficio, archivo_clave, archivo_tipo, estado)
         VALUES (:cliente, :tipo, :clave, :mime, 'pendiente')
         RETURNING cod_solicitud"
    );
    $stmt->execute([
        ':cliente' => $clienteId,
        ':tipo' => $tipo,
        ':clave' => $archivoGuardado['clave'],
        ':mime' => $archivoGuardado['mime'],
    ]);
    $solicitudId = (int) $stmt->fetchColumn();
    $pdo->prepare('INSERT INTO NOTIFICACION_ADMIN (cod_solicitud) VALUES (:solicitud)')
        ->execute([':solicitud' => $solicitudId]);
    $pdo->commit();

    $mailFrom = (string) getenv('MAIL_FROM');
    if (filter_var($mailFrom, FILTER_VALIDATE_EMAIL)) {
        $admins = $pdo->query("SELECT email FROM USUARIO WHERE cod_rol = 1 AND activo = '1'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins as $emailAdmin) {
            if (filter_var($emailAdmin, FILTER_VALIDATE_EMAIL)) {
                $enviado = mail(
                    $emailAdmin,
                    'Solicitud de beneficio pendiente - Cinex+',
                    "Hay una solicitud de descuento pendiente de revisión en el panel de administración.\nSolicitud: #$solicitudId",
                    "From: $mailFrom\r\nContent-Type: text/plain; charset=UTF-8"
                );
                if (!$enviado) {
                    error_log('No se pudo enviar el correo de notificación de beneficios al administrador.');
                }
            }
        }
    }

    header('Location: ../views/cliente/dashboard_cliente.php?benefit=requested');
} catch (RuntimeException $e) {
    if ($archivoGuardado && is_file($archivoGuardado['ruta'])) {
        unlink($archivoGuardado['ruta']);
    }
    error_log('No se pudo recibir el soporte de beneficio: ' . $e->getMessage());
    header('Location: ../views/cliente/dashboard_cliente.php?benefit=upload_error');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($archivoGuardado && is_file($archivoGuardado['ruta'])) {
        unlink($archivoGuardado['ruta']);
    }
    error_log('No se pudo guardar la solicitud de beneficio: ' . $e->getMessage());
    header('Location: ../views/cliente/dashboard_cliente.php?benefit=error');
}
exit();
