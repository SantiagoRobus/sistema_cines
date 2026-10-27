<?php
// Incluimos la conexión a PostgreSQL
require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpiamos los datos del formulario
    $email = trim($_POST['email']);
    $new_password = trim($_POST['password']);

    try {
        // 1. Validar si el correo existe en la base de datos y el usuario está activo
        $stmt = $pdo->prepare("SELECT cod_usuario FROM USUARIO WHERE email = :email AND activo = '1'");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // 2. Encriptar la nueva contraseña con BCRYPT por seguridad
            $hash = password_hash($new_password, PASSWORD_BCRYPT);

            // 3. Actualizar la contraseña en PostgreSQL
            $updateStmt = $pdo->prepare("UPDATE USUARIO SET password_hash = :hash WHERE cod_usuario = :id");
            $updateStmt->execute([
                ':hash' => $hash,
                ':id' => $usuario['cod_usuario']
            ]);

            // Redirigir con mensaje de éxito
            header("Location: ../views/public/recuperar_password.php?msg=success");
            exit();
        } else {
            // Si el correo no existe en la base de datos
            header("Location: ../views/public/recuperar_password.php?msg=not_found");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: ../views/public/recuperar_password.php?msg=error");
        exit();
    }
} else {
    header("Location: ../views/public/recuperar_password.php");
    exit();
}
?>