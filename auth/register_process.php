<?php
require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $codigo_secreto = trim($_POST['codigo_secreto']);

    // Asignación de roles basada en el código secreto
    $rol = 3; // Por defecto, todos son Clientes (Rol 3)
    if ($codigo_secreto === 'EMPLEADO-CINE') {
        $rol = 2; // Se asigna Rol de Empleado
    } elseif ($codigo_secreto === 'ADMIN-MASTER') {
        $rol = 1; // Se asigna Rol de Administrador
    }

    // Encriptar la contraseña
    $hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $sql = "INSERT INTO USUARIO (nombre, apellidos, email, password_hash, cod_rol, activo) 
                VALUES (:nombre, :apellidos, :email, :hash, :rol, '1')";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':nombre' => $nombre,
            ':apellidos' => $apellidos,
            ':email' => $email,
            ':hash' => $hash,
            ':rol' => $rol
        ]);

        // Registro exitoso
        header("Location: ../views/public/registro.php?msg=success");
        exit();
    } catch (PDOException $e) {
        // Error (probablemente el email ya está registrado por la restricción UNIQUE)
        header("Location: ../views/public/registro.php?msg=error");
        exit();
    }
} else {
    header("Location: ../views/public/registro.php");
    exit();
}
?>