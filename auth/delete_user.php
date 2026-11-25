<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();
require_once '../config/conexion.php';

// 1. VERIFICACIÓN DE SEGURIDAD: Solo administradores (Rol 1)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['rol_id']) || (int) $_SESSION['rol_id'] !== 1) {
    header("Location: ../views/public/login.php");
    exit();
}
verificar_token_csrf();

// 2. SANITIZACIÓN: Obtener e hiper-validar que el parámetro ID sea estrictamente un entero positivo
$id_eliminar = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

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
        error_log('No se pudo eliminar el usuario: ' . $e->getMessage());
        header("Location: ../views/admin/dashboard_admin.php?msg=error_db");
        exit();
    }
} else {
    // Si enviaron letras o un ID inválido por la URL
    header("Location: ../views/admin/dashboard_admin.php?msg=error_db");
    exit();
}
?>