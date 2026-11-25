<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !in_array((int) ($_SESSION['rol_id'] ?? 0), [2, 3], true) ||
    !isset($_SESSION['usuario_id'])
) {
    header('Location: ../views/public/login.php');
    exit();
}

verificar_token_csrf();
require_once '../config/conexion.php';

$usuarioId = (int) $_SESSION['usuario_id'];
$nombre = post_texto('nombre');
$apellidos = post_texto('apellidos');
$email = filter_var(post_texto('email'), FILTER_VALIDATE_EMAIL);
$telefono = post_texto('telefono');
$actual = post_texto('password_actual', false);
$nueva = post_texto('password_nueva', false);
$destino = (int) $_SESSION['rol_id'] === 2
    ? '../views/empleado/dashboard_empleado.php'
    : '../views/cliente/dashboard_cliente.php';

if ($nombre === '' || $apellidos === '' || $email === false || strlen($nombre) > 50 || strlen($apellidos) > 80 || strlen($telefono) > 15) {
    header('Location: ' . $destino . '?profile=invalid');
    exit();
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT password_hash FROM USUARIO WHERE cod_usuario = :id AND activo = \'1\' FOR UPDATE');
    $stmt->execute([':id' => $usuarioId]);
    $hashActual = $stmt->fetchColumn();
    if (!$hashActual) {
        $pdo->rollBack();
        header('Location: ../views/public/login.php');
        exit();
    }

    $cambiarPassword = $nueva !== '';
    if ($cambiarPassword && (
        !password_verify($actual, $hashActual) ||
        !password_cumple_politica($nueva, [$nombre, $apellidos, $email])
    )) {
        $pdo->rollBack();
        header('Location: ' . $destino . '?profile=invalid');
        exit();
    }

    $sql = 'UPDATE USUARIO SET nombre = :nombre, apellidos = :apellidos, email = :email, telefono = :telefono';
    if ($cambiarPassword) {
        $sql .= ', password_hash = :hash';
    }
    $sql .= ' WHERE cod_usuario = :id';
    $stmt = $pdo->prepare($sql);
    $parametros = [
        ':nombre' => $nombre,
        ':apellidos' => $apellidos,
        ':email' => $email,
        ':telefono' => $telefono !== '' ? $telefono : null,
        ':id' => $usuarioId,
    ];
    if ($cambiarPassword) {
        $parametros[':hash'] = password_hash($nueva, PASSWORD_DEFAULT);
    }
    $stmt->execute($parametros);
    $pdo->commit();

    $_SESSION['nombre'] = $nombre;
    header('Location: ' . $destino . '?profile=' . ($cambiarPassword ? 'password_updated' : 'updated'));
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('No se pudo actualizar el perfil: ' . $e->getMessage());
    header('Location: ' . $destino . '?profile=error');
}
exit();
