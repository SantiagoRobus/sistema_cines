<?php
session_start();
require_once '../config/conexion.php';

// Validar que solo el administrador pueda crear usuarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) {
    
    // Recibir y limpiar los datos del formulario
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $telefono = trim($_POST['telefono']);
    $cod_rol = (int)$_POST['rol']; // 1=Admin, 2=Empleado, 3=Cliente

    // Encriptar la contraseña antes de guardarla en PostgreSQL
    $hash_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        // Preparar la consulta SQL de inserción
        $sql = "INSERT INTO USUARIO (nombre, apellidos, email, password_hash, telefono, cod_rol, activo) 
                VALUES (:nombre, :apellidos, :email, :hash, :telefono, :rol, '1')";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':nombre' => $nombre,
            ':apellidos' => $apellidos,
            ':email' => $email,
            ':hash' => $hash_password,
            ':telefono' => $telefono,
            ':rol' => $cod_rol
        ]);

        // Si todo sale bien, redirigir con mensaje de éxito
        header("Location: ../views/admin/dashboard_admin.php?msg=created");
        exit();
    } catch (PDOException $e) {
        // En caso de error (ej. el correo ya existe), redirigir con error
        header("Location: ../views/admin/dashboard_admin.php?msg=error_create");
        exit();
    }
} else {
    header("Location: ../views/admin/dashboard_admin.php");
    exit();
}
?>