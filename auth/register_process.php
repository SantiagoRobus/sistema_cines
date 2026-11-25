<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token_csrf();

    $nombre = post_texto('nombre');
    $apellidos = post_texto('apellidos');
    $email = filter_var(post_texto('email'), FILTER_VALIDATE_EMAIL);
    $password = post_texto('password', false);

    if (
        $nombre === '' ||
        $apellidos === '' ||
        $email === false ||
        !is_string($password) ||
        !password_cumple_politica($password, [$nombre, $apellidos, $email])
    ) {
        header('Location: ../views/public/registro.php?msg=invalid');
        exit();
    }

    require_once '../config/conexion.php';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            "INSERT INTO USUARIO (nombre, apellidos, email, password_hash, cod_rol, activo)
             VALUES (:nombre, :apellidos, :email, :hash, 3, '1')
             RETURNING cod_usuario"
        );
        $stmt->execute([
            ':nombre' => $nombre,
            ':apellidos' => $apellidos,
            ':email' => $email,
            ':hash' => $hash,
        ]);
        $usuarioId = $stmt->fetchColumn();

        $stmt = $pdo->prepare('INSERT INTO CLIENTE (cod_usuario) VALUES (:usuario)');
        $stmt->execute([':usuario' => $usuarioId]);
        $pdo->commit();

        header('Location: ../views/public/registro.php?msg=success');
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('No se pudo registrar el usuario: ' . $e->getMessage());
        header('Location: ../views/public/registro.php?msg=error');
        exit();
    }
} else {
    header('Location: ../views/public/registro.php');
    exit();
}
?>