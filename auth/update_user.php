<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();
require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    header('Location: ../views/public/login.php');
    exit();
}

verificar_token_csrf();

$usuarioId = filter_var($_POST['edit_id'] ?? null, FILTER_VALIDATE_INT);
$nombre = post_texto('edit_nombre');
$apellidos = post_texto('edit_apellidos');
$email = filter_var(post_texto('edit_email'), FILTER_VALIDATE_EMAIL);
$telefono = post_texto('edit_telefono');
$rolId = filter_var($_POST['edit_rol'] ?? null, FILTER_VALIDATE_INT);
$cineId = filter_var($_POST['edit_cod_cine'] ?? null, FILTER_VALIDATE_INT);
$requiereCine = in_array($rolId, [1, 2], true);

if (
    !$usuarioId ||
    $nombre === '' ||
    $apellidos === '' ||
    $email === false ||
    strlen($nombre) > 50 ||
    strlen($apellidos) > 80 ||
    strlen($telefono) > 15 ||
    !in_array($rolId, [1, 2, 3], true) ||
    ($requiereCine && (!$cineId || $cineId < 1)) ||
    ($usuarioId === (int) $_SESSION['usuario_id'] && $rolId !== 1)
) {
    header('Location: ../views/admin/dashboard_admin.php?msg=error_update');
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT 1 FROM USUARIO WHERE cod_usuario = :id FOR UPDATE');
    $stmt->execute([':id' => $usuarioId]);
    if (!$stmt->fetchColumn()) {
        $pdo->rollBack();
        header('Location: ../views/admin/dashboard_admin.php?msg=error_update');
        exit();
    }

    if ($requiereCine) {
        $stmt = $pdo->prepare("SELECT 1 FROM CINE WHERE cod_cine = :cine AND activo = '1'");
        $stmt->execute([':cine' => $cineId]);
        if (!$stmt->fetchColumn()) {
            $pdo->rollBack();
            header('Location: ../views/admin/dashboard_admin.php?msg=invalid_cinema');
            exit();
        }
    }

    $stmt = $pdo->prepare(
        'SELECT 1 FROM USUARIO WHERE email = :email AND cod_usuario <> :id'
    );
    $stmt->execute([':email' => $email, ':id' => $usuarioId]);
    if ($stmt->fetchColumn()) {
        $pdo->rollBack();
        header('Location: ../views/admin/dashboard_admin.php?msg=duplicate_email');
        exit();
    }

    $stmt = $pdo->prepare(
        'UPDATE USUARIO
         SET nombre = :nombre, apellidos = :apellidos, email = :email,
             telefono = :telefono, cod_rol = :rol
         WHERE cod_usuario = :id'
    );
    $stmt->execute([
        ':nombre' => $nombre,
        ':apellidos' => $apellidos,
        ':email' => $email,
        ':telefono' => $telefono !== '' ? $telefono : null,
        ':rol' => $rolId,
        ':id' => $usuarioId,
    ]);

    if ($rolId === 3) {
        // Keep historical employee/admin profile rows because reservations may reference them.
        $pdo->prepare(
            'INSERT INTO CLIENTE (cod_usuario) VALUES (:id)
             ON CONFLICT (cod_usuario) DO NOTHING'
        )->execute([':id' => $usuarioId]);
    } elseif ($rolId === 2) {
        $pdo->prepare(
            'INSERT INTO EMPLEADO (cod_usuario, cod_cine) VALUES (:id, :cine)
             ON CONFLICT (cod_usuario) DO UPDATE SET cod_cine = EXCLUDED.cod_cine'
        )->execute([':id' => $usuarioId, ':cine' => $cineId]);
    } else {
        $pdo->prepare(
            'INSERT INTO ADMIN_LOCAL (cod_usuario, cod_cine) VALUES (:id, :cine)
             ON CONFLICT (cod_usuario) DO UPDATE SET cod_cine = EXCLUDED.cod_cine'
        )->execute([':id' => $usuarioId, ':cine' => $cineId]);
    }

    $pdo->commit();
    header('Location: ../views/admin/dashboard_admin.php?msg=updated');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('No se pudo actualizar el usuario (SQLSTATE ' . $e->getCode() . '): ' . $e->getMessage());
    header('Location: ../views/admin/dashboard_admin.php?msg=' . ($e->getCode() === '23505' ? 'duplicate_email' : 'error_update'));
}
exit();
?>