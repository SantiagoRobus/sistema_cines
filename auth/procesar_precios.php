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
$cine = filter_var($_POST['cod_cine'] ?? null, FILTER_VALIDATE_INT);
$tarifa = filter_var($_POST['cod_tarifa'] ?? null, FILTER_VALIDATE_INT);
$importeRaw = $_POST['importe'] ?? '';
$importe = is_string($importeRaw) && preg_match('/\A\d{1,8}(?:\.\d{1,2})?\z/', $importeRaw) ? (float) $importeRaw : 0;
$cineAnterior = filter_var($_POST['cine_anterior'] ?? null, FILTER_VALIDATE_INT);
$tarifaAnterior = filter_var($_POST['tarifa_anterior'] ?? null, FILTER_VALIDATE_INT);

if (!in_array($accion, ['crear', 'editar'], true) || !$cine || !$tarifa || $importe <= 0 || ($accion === 'editar' && (!$cineAnterior || !$tarifaAnterior))) {
    header('Location: ../views/admin/crud_precios.php?msg=invalid');
    exit();
}

try {
    if ($accion === 'crear') {
        $stmt = $pdo->prepare(
            'INSERT INTO PRECIO (cod_cine, cod_tarifa, importe)
             VALUES (:cine, :tarifa, :importe)
             ON CONFLICT (cod_cine, cod_tarifa)
             DO UPDATE SET importe = EXCLUDED.importe'
        );
        $stmt->execute([':cine' => $cine, ':tarifa' => $tarifa, ':importe' => $importe]);
    } else {
        $stmt = $pdo->prepare(
            'UPDATE PRECIO SET cod_cine = :cine, cod_tarifa = :tarifa, importe = :importe
             WHERE cod_cine = :cine_anterior AND cod_tarifa = :tarifa_anterior'
        );
        $stmt->execute([
            ':cine' => $cine,
            ':tarifa' => $tarifa,
            ':importe' => $importe,
            ':cine_anterior' => $cineAnterior,
            ':tarifa_anterior' => $tarifaAnterior,
        ]);
    }

    header('Location: ../views/admin/crud_precios.php?msg=' . ($accion === 'crear' ? 'saved' : 'updated'));
} catch (PDOException $e) {
    error_log('No se pudo guardar una tarifa de cine: ' . $e->getMessage());
    header('Location: ../views/admin/crud_precios.php?msg=error');
}
exit();
