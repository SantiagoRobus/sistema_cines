<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['rol_id']) || (int) $_SESSION['rol_id'] !== 1) {
    header('Location: ../views/public/login.php');
    exit();
}

verificar_token_csrf();
require_once '../config/conexion.php';

$accion = $_POST['accion'] ?? '';
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$cine = filter_input(INPUT_POST, 'cod_cine', FILTER_VALIDATE_INT);
$sala = filter_input(INPUT_POST, 'num_sala', FILTER_VALIDATE_INT);
$pelicula = filter_input(INPUT_POST, 'cod_pelicula', FILTER_VALIDATE_INT);
$fecha = $_POST['fecha'] ?? '';
$hora = $_POST['hora'] ?? '';
$matinal = $_POST['matinal'] ?? 'N';

if ($accion === 'eliminar' && $id && $id > 0) {
    try {
        $stmt = $pdo->prepare('DELETE FROM PASE WHERE cod_pase = :id');
        $stmt->execute([':id' => $id]);
        header('Location: ../views/admin/crud_pases.php?msg=deleted');
    } catch (PDOException $e) {
        error_log('No se pudo eliminar el pase: ' . $e->getMessage());
        header('Location: ../views/admin/crud_pases.php?msg=error');
    }
    exit();
}

$fechaValida = is_string($fecha) && preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $fecha)
    && DateTimeImmutable::createFromFormat('!Y-m-d', $fecha)?->format('Y-m-d') === $fecha;
$horaValida = is_string($hora) && preg_match('/\A(?:[01]\d|2[0-3]):[0-5]\d\z/', $hora);

if (
    !in_array($accion, ['crear', 'editar'], true) ||
    ($accion === 'editar' && (!$id || $id < 1)) ||
    !$cine || $cine < 1 || !$sala || $sala < 1 || !$pelicula || $pelicula < 1 ||
    !$fechaValida || !$horaValida || !in_array($matinal, ['S', 'N'], true)
) {
    header('Location: ../views/admin/crud_pases.php?msg=invalid');
    exit();
}

try {
    $stmt = $pdo->prepare(
        "SELECT 1 FROM SALA s JOIN CINE c ON c.cod_cine = s.cod_cine
         WHERE s.cod_cine = :cine AND s.num_sala = :sala AND c.activo = '1'"
    );
    $stmt->execute([':cine' => $cine, ':sala' => $sala]);
    $salaExiste = $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT 1 FROM PELICULA WHERE cod_pelicula = :pelicula');
    $stmt->execute([':pelicula' => $pelicula]);
    $peliculaExiste = $stmt->fetchColumn();

    if (!$salaExiste || !$peliculaExiste) {
        header('Location: ../views/admin/crud_pases.php?msg=invalid');
        exit();
    }

    if ($accion === 'crear') {
        $stmt = $pdo->prepare(
            'INSERT INTO PASE (cod_cine, num_sala, cod_pelicula, fecha, hora, matinal)
             VALUES (:cine, :sala, :pelicula, :fecha, :hora, :matinal)'
        );
    } else {
        $stmt = $pdo->prepare(
            'UPDATE PASE SET cod_cine = :cine, num_sala = :sala, cod_pelicula = :pelicula,
             fecha = :fecha, hora = :hora, matinal = :matinal WHERE cod_pase = :id'
        );
    }

    $parametros = [
        ':cine' => $cine,
        ':sala' => $sala,
        ':pelicula' => $pelicula,
        ':fecha' => $fecha,
        ':hora' => $hora,
        ':matinal' => $matinal,
    ];
    if ($accion === 'editar') {
        $parametros[':id'] = $id;
    }
    $stmt->execute($parametros);

    header('Location: ../views/admin/crud_pases.php?msg=' . ($accion === 'crear' ? 'created' : 'updated'));
} catch (PDOException $e) {
    error_log('No se pudo guardar el pase: ' . $e->getMessage());
    header('Location: ../views/admin/crud_pases.php?msg=error');
}
exit();
