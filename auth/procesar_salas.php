<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();
require_once '../config/conexion.php';

// Verificamos que sea administrador
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['rol_id']) || (int) $_SESSION['rol_id'] !== 1) {
    header("Location: ../views/public/login.php");
    exit();
}
verificar_token_csrf();

$accion = post_texto('accion');
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

// Convertimos los datos a enteros (int) para mayor seguridad en la base de datos
$num_sala = filter_var($_POST['num_sala'] ?? null, FILTER_VALIDATE_INT);
$capacidad = filter_var($_POST['capacidad'] ?? null, FILTER_VALIDATE_INT);
$cod_cine = filter_var($_POST['cod_cine'] ?? null, FILTER_VALIDATE_INT);

try {
    if ($accion === 'crear' && $num_sala > 0 && $capacidad > 0 && $cod_cine > 0) {
        $sql = "INSERT INTO SALA (num_sala, capacidad, cod_cine) VALUES (:num, :cap, :cine)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':num' => $num_sala, ':cap' => $capacidad, ':cine' => $cod_cine]);
        header("Location: ../views/admin/crud_salas.php?msg=creado");
        
    } elseif ($accion === 'editar' && $id && $id > 0 && $num_sala > 0 && $capacidad > 0 && $cod_cine > 0) {
        $sql = "UPDATE SALA SET num_sala = :num, capacidad = :cap, cod_cine = :cine WHERE cod_sala = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':num' => $num_sala, ':cap' => $capacidad, ':cine' => $cod_cine, ':id' => $id]);
        header("Location: ../views/admin/crud_salas.php?msg=editado");
        
    } elseif ($accion === 'eliminar' && $id && $id > 0) {
        $sql = "DELETE FROM SALA WHERE cod_sala = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        header("Location: ../views/admin/crud_salas.php?msg=eliminado");
        
    } else {
        header("Location: ../views/admin/crud_salas.php?msg=error");
    }
} catch (PDOException $e) {
    // Si la sala ya tiene reservas o pases programados, PostgreSQL bloqueará el borrado por seguridad.
    error_log('No se pudo procesar la sala: ' . $e->getMessage());
    header("Location: ../views/admin/crud_salas.php?msg=error_db");
}
exit();
?>