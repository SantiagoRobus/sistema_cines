<?php
session_start();
require_once '../config/conexion.php';

// Verificamos que sea administrador
if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../views/public/login.php");
    exit();
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? null;

// Convertimos los datos a enteros (int) para mayor seguridad en la base de datos
$num_sala = (int)($_POST['num_sala'] ?? 0);
$capacidad = (int)($_POST['capacidad'] ?? 0);
$cod_cine = (int)($_POST['cod_cine'] ?? 0);

try {
    if ($accion === 'crear') {
        $sql = "INSERT INTO SALA (num_sala, capacidad, cod_cine) VALUES (:num, :cap, :cine)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':num' => $num_sala, ':cap' => $capacidad, ':cine' => $cod_cine]);
        header("Location: ../views/admin/crud_salas.php?msg=creado");
        
    } elseif ($accion === 'editar' && $id) {
        $sql = "UPDATE SALA SET num_sala = :num, capacidad = :cap, cod_cine = :cine WHERE cod_sala = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':num' => $num_sala, ':cap' => $capacidad, ':cine' => $cod_cine, ':id' => $id]);
        header("Location: ../views/admin/crud_salas.php?msg=editado");
        
    } elseif ($accion === 'eliminar' && $id) {
        $sql = "DELETE FROM SALA WHERE cod_sala = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        header("Location: ../views/admin/crud_salas.php?msg=eliminado");
        
    } else {
        header("Location: ../views/admin/crud_salas.php?msg=error");
    }
} catch (PDOException $e) {
    // Si la sala ya tiene reservas o pases programados, PostgreSQL bloqueará el borrado por seguridad.
    header("Location: ../views/admin/crud_salas.php?msg=error_db");
}
exit();
?>