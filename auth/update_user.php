<?php
session_start();
require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1) {
    
    // Recibir los datos del formulario de edición
    $cod_usuario = (int)$_POST['edit_id'];
    $nombre = trim($_POST['edit_nombre']);
    $apellidos = trim($_POST['edit_apellidos']);
    $email = trim($_POST['edit_email']);
    $telefono = trim($_POST['edit_telefono']);
    $cod_rol = (int)$_POST['edit_rol'];

    try {
        // Preparar la consulta SQL para actualizar los datos
        // Nota: Por simplicidad y seguridad, no actualizamos la contraseña aquí. 
        // Si el usuario olvida la contraseña, usará el módulo de recuperación después.
        $sql = "UPDATE USUARIO 
                SET nombre = :nombre, apellidos = :apellidos, email = :email, telefono = :telefono, cod_rol = :rol 
                WHERE cod_usuario = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':apellidos' => $apellidos,
            ':email' => $email,
            ':telefono' => $telefono,
            ':rol' => $cod_rol,
            ':id' => $cod_usuario
        ]);

        header("Location: ../views/admin/dashboard_admin.php?msg=updated");
        exit();
    } catch (PDOException $e) {
        header("Location: ../views/admin/dashboard_admin.php?msg=error_update");
        exit();
    }
} else {
    header("Location: ../views/admin/dashboard_admin.php");
    exit();
}
?>