<?php
session_start();
require_once '../config/conexion.php';

// 1. VERIFICACIÓN DE SEGURIDAD: Solo administradores (Rol 1)
if (!isset($_SESSION['rol_id']) || (int)$_SESSION['rol_id'] !== 1) {
    header("Location: ../views/public/login.php");
    exit();
}

// 2. SANITIZACIÓN: Obtener e hiper-validar que el parámetro ID sea estrictamente un entero positivo
$id_eliminar = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id_eliminar && $id_eliminar > 0) {
    
    // 3. REGLA DE NEGOCIO: Evitar que el administrador autenticado se elimine a sí mismo
    if ($id_eliminar === (int)$_SESSION['usuario_id']) {
        header("Location: ../views/admin/dashboard_admin.php?msg=error_self");
        exit();
    }

    try {
        // 4. CONSULTA PREPARADA SEGURA
        $stmt = $pdo->prepare("DELETE FROM USUARIO WHERE cod_usuario = :id");
        $stmt->execute([':id' => $id_eliminar]);
        
        header("Location: ../views/admin/dashboard_admin.php?msg=deleted");
        exit();
    } catch (PDOException $e) {
        // Retornar error controlado sin exponer detalles de la BD
        header("Location: ../views/admin/dashboard_admin.php?msg=error_db");
        exit();
    }
} else {
    // Si enviaron letras o un ID inválido por la URL
    header("Location: ../views/admin/dashboard_admin.php?msg=error_db");
    exit();
}
?>