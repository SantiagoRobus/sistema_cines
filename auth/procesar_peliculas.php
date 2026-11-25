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
$directorRaw = $_POST['cod_director'] ?? '';
$clasificacionRaw = $_POST['cod_clasif'] ?? '';
$cod_director = $directorRaw === '' ? null : filter_var($directorRaw, FILTER_VALIDATE_INT);
$cod_clasif = $clasificacionRaw === '' ? null : filter_var($clasificacionRaw, FILTER_VALIDATE_INT);
$actoresRaw = $_POST['cod_actores'] ?? [];
$actores = [];

if (is_array($actoresRaw)) {
    foreach ($actoresRaw as $actorRaw) {
        $actorId = filter_var($actorRaw, FILTER_VALIDATE_INT);
        if ($actorId === false || $actorId < 1) {
            $actores = false;
            break;
        }
        $actores[] = $actorId;
    }
    if (is_array($actores)) {
        $actores = array_values(array_unique($actores));
    }
} else {
    $actores = false;
}

$referenciasValidas = ($cod_director === null || ($cod_director !== false && $cod_director > 0))
    && ($cod_clasif === null || ($cod_clasif !== false && $cod_clasif > 0))
    && is_array($actores)
    && count($actores) <= 30;

try {
    if (
        !in_array($accion, ['crear', 'editar'], true)
        || ($accion === 'editar' && (!$id || $id < 1))
        || $titulo === '' || !$duracion || $duracion < 1 || !$cod_genero || $cod_genero < 1
        || !$referenciasValidas
    ) {
        header("Location: ../views/admin/crud_peliculas.php?msg=error");
        exit();
    }

    if ($cod_director !== null || $cod_clasif !== null) {
        if ($cod_director !== null) {
            $stmt = $pdo->prepare('SELECT 1 FROM DIRECTOR WHERE cod_director = :id');
            $stmt->execute([':id' => $cod_director]);
            if (!$stmt->fetchColumn()) {
                header("Location: ../views/admin/crud_peliculas.php?msg=error");
                exit();
            }
        }
        if ($cod_clasif !== null) {
            $stmt = $pdo->prepare('SELECT 1 FROM CLASIFICACION WHERE cod_clasif = :id');
            $stmt->execute([':id' => $cod_clasif]);
            if (!$stmt->fetchColumn()) {
                header("Location: ../views/admin/crud_peliculas.php?msg=error");
                exit();
            }
        }
    }
    if ($actores) {
        $placeholders = implode(',', array_fill(0, count($actores), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ACTOR WHERE cod_actor IN ($placeholders)");
        $stmt->execute($actores);
        if ((int) $stmt->fetchColumn() !== count($actores)) {
            header("Location: ../views/admin/crud_peliculas.php?msg=error");
            exit();
        }
    }

    $pdo->beginTransaction();
    $parametros = [
        ':titulo' => $titulo,
        ':duracion' => $duracion,
        ':sinopsis' => $sinopsis !== '' ? $sinopsis : null,
        ':genero' => $cod_genero,
        ':director' => $cod_director,
        ':clasificacion' => $cod_clasif,
    ];

    if ($accion === 'crear') {
        $stmt = $pdo->prepare(
            'INSERT INTO PELICULA (titulo, duracion_min, sinopsis, cod_genero, cod_director, cod_clasif)
             VALUES (:titulo, :duracion, :sinopsis, :genero, :director, :clasificacion)
             RETURNING cod_pelicula'
        );
        $stmt->execute($parametros);
        $peliculaId = (int) $stmt->fetchColumn();
    } else {
        $peliculaId = (int) $id;
        $parametros[':id'] = $peliculaId;
        $stmt = $pdo->prepare(
            'UPDATE PELICULA SET titulo = :titulo, duracion_min = :duracion, sinopsis = :sinopsis,
             cod_genero = :genero, cod_director = :director, cod_clasif = :clasificacion
             WHERE cod_pelicula = :id'
        );
        $stmt->execute($parametros);
    }

    $stmt = $pdo->prepare('DELETE FROM PROTAGONIZA WHERE cod_pelicula = :pelicula');
    $stmt->execute([':pelicula' => $peliculaId]);
    $stmt = $pdo->prepare('INSERT INTO PROTAGONIZA (cod_pelicula, cod_actor, orden) VALUES (:pelicula, :actor, :orden)');
    foreach ($actores as $orden => $actorId) {
        $stmt->execute([':pelicula' => $peliculaId, ':actor' => $actorId, ':orden' => $orden + 1]);
    }
    $pdo->commit();
    header("Location: ../views/admin/crud_peliculas.php?msg=" . ($accion === 'crear' ? 'creado' : 'editado'));
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('No se pudo procesar la película: ' . $e->getMessage());
    header("Location: ../views/admin/crud_peliculas.php?msg=error_db");
}
exit();
?>