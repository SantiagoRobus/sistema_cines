<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();
require_once '../config/conexion.php';

// Validar que solo el administrador pueda crear usuarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol_id']) && (int) $_SESSION['rol_id'] === 1) {
    verificar_token_csrf();
    
    // Recibir y limpiar los datos del formulario
    $nombre = post_texto('nombre');
    $apellidos = post_texto('apellidos');
    $email = filter_var(post_texto('email'), FILTER_VALIDATE_EMAIL);
    $password = post_texto('password', false);
    $telefono = post_texto('telefono');
    $cod_rol = filter_var($_POST['rol'] ?? null, FILTER_VALIDATE_INT);
    $cod_cine = filter_var($_POST['cod_cine'] ?? null, FILTER_VALIDATE_INT);

    $requiereCine = in_array($cod_rol, [1, 2], true);
    if (
        $nombre === '' ||
        $apellidos === '' ||
        $email === false ||
        !is_string($password) ||
        !password_cumple_politica($password, [$nombre, $apellidos, $email]) ||
        !in_array($cod_rol, [1, 2, 3], true) ||
        ($requiereCine && (!$cod_cine || $cod_cine < 1))
    ) {
        header("Location: ../views/admin/dashboard_admin.php?msg=error_create");
        exit();
    }

    $hash_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();
        $sql = "INSERT INTO USUARIO (nombre, apellidos, email, password_hash, telefono, cod_rol, activo)
                VALUES (:nombre, :apellidos, :email, :hash, :telefono, :rol, '1')
                RETURNING cod_usuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':apellidos' => $apellidos,
            ':email' => $email,
            ':hash' => $hash_password,
            ':telefono' => $telefono,
            ':rol' => $cod_rol
        ]);
        $usuarioId = $stmt->fetchColumn();

        if ($cod_rol === 3) {
            $stmt = $pdo->prepare('INSERT INTO CLIENTE (cod_usuario) VALUES (:usuario)');
            $stmt->execute([':usuario' => $usuarioId]);
        } elseif ($cod_rol === 2) {
            $stmt = $pdo->prepare('INSERT INTO EMPLEADO (cod_usuario, cod_cine) VALUES (:usuario, :cine)');
            $stmt->execute([':usuario' => $usuarioId, ':cine' => $cod_cine]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO ADMIN_LOCAL (cod_usuario, cod_cine) VALUES (:usuario, :cine)');
            $stmt->execute([':usuario' => $usuarioId, ':cine' => $cod_cine]);
        }
        $pdo->commit();

        // Si todo sale bien, redirigir con mensaje de éxito
        header("Location: ../views/admin/dashboard_admin.php?msg=created");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('No se pudo crear el usuario: ' . $e->getMessage());
        header("Location: ../views/admin/dashboard_admin.php?msg=error_create");
        exit();
    }
} else {
    header("Location: ../views/admin/dashboard_admin.php");
    exit();
}
?>