<?php
session_start();
require_once '../config/conexion.php';

// Validar que el usuario tenga rol de Administrador (Rol 1)
if (!isset($_SESSION['rol_id']) || (int)$_SESSION['rol_id'] !== 1) {
    header("Location: ../views/public/login.php");
    exit();
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$titulo = trim($_POST['titulo'] ?? '');
$duracion = filter_input(INPUT_POST, 'duracion_min', FILTER_VALIDATE_INT);
$sinopsis = trim($_POST['sinopsis'] ?? '');
$cod_genero = filter_input(INPUT_POST, 'cod_genero', FILTER_VALIDATE_INT);

try {
    if ($accion === 'crear' && $titulo && $duracion && $cod_genero) {
        $sql = "INSERT INTO PELICULA (titulo, duracion_min, sinopsis, cod_genero) VALUES (:titulo, :duracion, :sinopsis, :genero)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $titulo,
            ':duracion' => $duracion,
            ':sinopsis' => $sinopsis,
            ':genero' => $cod_genero
        ]);
        header("Location: ../views/admin/crud_peliculas.php?msg=creado");
        
    } elseif ($accion === 'editar' && $id && $titulo && $duracion && $cod_genero) {
        $sql = "UPDATE PELICULA SET titulo = :titulo, duracion_min = :duracion, sinopsis = :sinopsis, cod_genero = :genero WHERE cod_pelicula = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $titulo,
            ':duracion' => $duracion,
            ':sinopsis' => $sinopsis,
            ':genero' => $cod_genero,
            ':id' => $id
        ]);
        header("Location: ../views/admin/crud_peliculas.php?msg=editado");
        
    } elseif ($accion === 'eliminar' && $id) {
        $sql = "DELETE FROM PELICULA WHERE cod_pelicula = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        header("Location: ../views/admin/crud_peliculas.php?msg=eliminado");
        
    } else {
        header("Location: ../views/admin/crud_peliculas.php?msg=error");
    }
} catch (PDOException $e) {
    header("Location: ../views/admin/crud_peliculas.php?msg=error_db");
}
exit();
?>