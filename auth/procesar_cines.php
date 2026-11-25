<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();
require_once '../config/conexion.php';

// Validar que el usuario sea administrador (Rol 1)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['rol_id']) || (int) $_SESSION['rol_id'] !== 1) {
    header("Location: ../views/public/login.php");
    exit();
}
verificar_token_csrf();

$accion = post_texto('accion');
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

$nombre = post_texto('nombre');
$calle = post_texto('calle');
$numero = post_texto('numero');
$telefono = post_texto('telefono');

try {
    if ($accion === 'crear') {
        if ($nombre === '' || $calle === '' || $numero === '') {
            header("Location: ../views/admin/crud_cines.php?msg=error");
            exit();
        }
        $sql = "INSERT INTO CINE (nombre, calle, numero, telefono, activo) VALUES (:nombre, :calle, :numero, :telefono, '1')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nombre' => $nombre, ':calle' => $calle, ':numero' => $numero, ':telefono' => $telefono]);
        header("Location: ../views/admin/crud_cines.php?msg=creado");
        
    } elseif ($accion === 'editar' && $id && $id > 0 && $nombre !== '' && $calle !== '' && $numero !== '') {
        $sql = "UPDATE CINE SET nombre = :nombre, calle = :calle, numero = :numero, telefono = :telefono WHERE cod_cine = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nombre' => $nombre, ':calle' => $calle, ':numero' => $numero, ':telefono' => $telefono, ':id' => $id]);
        header("Location: ../views/admin/crud_cines.php?msg=editado");
        
    } else {
        header("Location: ../views/admin/crud_cines.php?msg=error");
    }
} catch (PDOException $e) {
    error_log('No se pudo procesar el cine: ' . $e->getMessage());
    header("Location: ../views/admin/crud_cines.php?msg=error_db");
}
exit();
?>