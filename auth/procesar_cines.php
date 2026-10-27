<?php
session_start();
require_once '../config/conexion.php';

// Validar que el usuario sea administrador (Rol 1)
if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../views/public/login.php");
    exit();
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? null;

$nombre = trim($_POST['nombre'] ?? '');
$calle = trim($_POST['calle'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');

try {
    if ($accion === 'crear') {
        $sql = "INSERT INTO CINE (nombre, calle, numero, telefono, activo) VALUES (:nombre, :calle, :numero, :telefono, '1')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nombre' => $nombre, ':calle' => $calle, ':numero' => $numero, ':telefono' => $telefono]);
        header("Location: ../views/admin/crud_cines.php?msg=creado");
        
    } elseif ($accion === 'editar' && $id) {
        $sql = "UPDATE CINE SET nombre = :nombre, calle = :calle, numero = :numero, telefono = :telefono WHERE cod_cine = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nombre' => $nombre, ':calle' => $calle, ':numero' => $numero, ':telefono' => $telefono, ':id' => $id]);
        header("Location: ../views/admin/crud_cines.php?msg=editado");
        
    } elseif ($accion === 'eliminar' && $id) {
        $sql = "DELETE FROM CINE WHERE cod_cine = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        header("Location: ../views/admin/crud_cines.php?msg=eliminado");
        
    } else {
        header("Location: ../views/admin/crud_cines.php?msg=error");
    }
} catch (PDOException $e) {
    // Si hay un error (por ejemplo, si el cine ya tiene salas o empleados asignados)
    header("Location: ../views/admin/crud_cines.php?msg=error_db");
}
exit();
?>