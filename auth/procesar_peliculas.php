<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();
require_once '../config/conexion.php';

// Validar que el usuario tenga rol de Administrador (Rol 1)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['rol_id']) || (int) $_SESSION['rol_id'] !== 1) {
    header("Location: ../views/public/login.php");
    exit();
}
verificar_token_csrf();

$accion = post_texto('accion');
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

$titulo = post_texto('titulo');
$duracion = filter_input(INPUT_POST, 'duracion_min', FILTER_VALIDATE_INT);
$sinopsis = post_texto('sinopsis');
$cod_genero = filter_input(INPUT_POST, 'cod_genero', FILTER_VALIDATE_INT);

try {
    if ($accion === 'crear' && $titulo !== '' && $duracion && $duracion > 0 && $cod_genero && $cod_genero > 0) {
        $sql = "INSERT INTO PELICULA (titulo, duracion_min, sinopsis, cod_genero) VALUES (:titulo, :duracion, :sinopsis, :genero)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $titulo,
            ':duracion' => $duracion,
            ':sinopsis' => $sinopsis,
            ':genero' => $cod_genero
        ]);
        header("Location: ../views/admin/crud_peliculas.php?msg=creado");
        
    } elseif ($accion === 'editar' && $id && $id > 0 && $titulo !== '' && $duracion && $duracion > 0 && $cod_genero && $cod_genero > 0) {
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
        
    } elseif ($accion === 'eliminar' && $id && $id > 0) {
        $sql = "DELETE FROM PELICULA WHERE cod_pelicula = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        header("Location: ../views/admin/crud_peliculas.php?msg=eliminado");
        
    } else {
        header("Location: ../views/admin/crud_peliculas.php?msg=error");
    }
} catch (PDOException $e) {
    error_log('No se pudo procesar la película: ' . $e->getMessage());
    header("Location: ../views/admin/crud_peliculas.php?msg=error_db");
}
exit();
?>