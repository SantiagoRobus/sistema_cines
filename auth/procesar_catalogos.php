<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();
require_once '../config/conexion.php';

// Validar que el usuario sea administrador
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['rol_id']) || (int) $_SESSION['rol_id'] !== 1) {
    header("Location: ../views/public/login.php");
    exit();
}
verificar_token_csrf();

// Recibir los parámetros (ya sea por POST desde los formularios o por GET desde SweetAlert2)
$accion = post_texto('accion');
$tabla = post_texto('tabla');
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$valor = post_texto('valor');
$beneficio = post_texto('beneficio_requerido');
$tiposBeneficio = ['estudiante', 'pensionado', 'servicio_militar', 'discapacidad'];

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
        if ($valor === '' || ($tabla === 'tarifa' && $beneficio !== '' && !in_array($beneficio, $tiposBeneficio, true))) {
            header("Location: ../views/admin/crud_catalogos.php?msg=error");
            exit();
        }
        $sql = $tabla === 'tarifa'
            ? "INSERT INTO TARIFA ($columnaValor, beneficio_requerido) VALUES (:valor, :beneficio)"
            : "INSERT INTO GENERO ($columnaValor) VALUES (:valor)";
        $stmt = $pdo->prepare($sql);
        $parametros = [':valor' => $valor];
        if ($tabla === 'tarifa') {
            $parametros[':beneficio'] = $beneficio !== '' ? $beneficio : null;
        }
        $stmt->execute($parametros);
        header("Location: ../views/admin/crud_catalogos.php?msg=creado");
        
    } elseif ($accion === 'editar' && $id && $id > 0 && $valor !== '') {
        if ($tabla === 'tarifa' && $beneficio !== '' && !in_array($beneficio, $tiposBeneficio, true)) {
            header("Location: ../views/admin/crud_catalogos.php?msg=error");
            exit();
        }
        $sql = $tabla === 'tarifa'
            ? "UPDATE TARIFA SET $columnaValor = :valor, beneficio_requerido = :beneficio WHERE $columnaId = :id"
            : "UPDATE GENERO SET $columnaValor = :valor WHERE $columnaId = :id";
        $stmt = $pdo->prepare($sql);
        $parametros = [':valor' => $valor, ':id' => $id];
        if ($tabla === 'tarifa') {
            $parametros[':beneficio'] = $beneficio !== '' ? $beneficio : null;
        }
        $stmt->execute($parametros);
        header("Location: ../views/admin/crud_catalogos.php?msg=editado");
        
    } elseif ($accion === 'eliminar' && $id && $id > 0) {
        $sql = "DELETE FROM $nombreTablaDB WHERE $columnaId = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        header("Location: ../views/admin/crud_catalogos.php?msg=eliminado");
        
    } else {
        header("Location: ../views/admin/crud_catalogos.php?msg=error");
    }
} catch (PDOException $e) {
    error_log('No se pudo procesar el catálogo: ' . $e->getMessage());
    header("Location: ../views/admin/crud_catalogos.php?msg=error");
}
exit();
?>