<?php
session_start();
require_once '../config/conexion.php';

// Validar que el usuario sea administrador
if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../views/public/login.php");
    exit();
}

// Recibir los parámetros (ya sea por POST desde los formularios o por GET desde SweetAlert2)
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$tabla = $_POST['tabla'] ?? $_GET['tabla'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? null;
$valor = $_POST['valor'] ?? '';

// Definir nombres de columnas y tablas en la base de datos según la solicitud
if ($tabla === 'genero') {
    $nombreTablaDB = 'GENERO';
    $columnaId = 'cod_genero';
    $columnaValor = 'nombre';
} elseif ($tabla === 'tarifa') {
    $nombreTablaDB = 'TARIFA';
    $columnaId = 'cod_tarifa';
    $columnaValor = 'descripcion';
} else {
    // Si la tabla no es válida, regresamos con error
    header("Location: ../views/admin/crud_catalogos.php?msg=error");
    exit();
}

try {
    if ($accion === 'crear') {
        $sql = "INSERT INTO $nombreTablaDB ($columnaValor) VALUES (:valor)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':valor' => trim($valor)]);
        header("Location: ../views/admin/crud_catalogos.php?msg=creado");
        
    } elseif ($accion === 'editar' && $id) {
        $sql = "UPDATE $nombreTablaDB SET $columnaValor = :valor WHERE $columnaId = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':valor' => trim($valor), ':id' => $id]);
        header("Location: ../views/admin/crud_catalogos.php?msg=editado");
        
    } elseif ($accion === 'eliminar' && $id) {
        $sql = "DELETE FROM $nombreTablaDB WHERE $columnaId = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        header("Location: ../views/admin/crud_catalogos.php?msg=eliminado");
        
    } else {
        header("Location: ../views/admin/crud_catalogos.php?msg=error");
    }
} catch (PDOException $e) {
    header("Location: ../views/admin/crud_catalogos.php?msg=error");
}
exit();
?>