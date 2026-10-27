<?php
session_start();
// Proteger la ruta para administradores
if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../public/login.php");
    exit();
}

require_once '../../config/conexion.php';

// Leer los datos de ambas tablas
try {
    $stmtGeneros = $pdo->query("SELECT * FROM GENERO ORDER BY cod_genero ASC");
    $generos = $stmtGeneros->fetchAll(PDO::FETCH_ASSOC);

    $stmtTarifas = $pdo->query("SELECT * FROM TARIFA ORDER BY cod_tarifa ASC");
    $tarifas = $stmtTarifas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Catálogos</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #000; color: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #eee; }
        .btn-add { background: #28a745; color: #fff; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-action { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; color: #fff; font-size: 12px; margin-right: 5px; }
        .btn-edit { background: #007bff; }
        .btn-delete { background: #dc3545; }
        .btn-back { background: #555; color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 4px; }
        
        /* Estilos de los Modales */
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background-color: #fff; padding: 20px; border-radius: 8px; width: 350px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .close { color: #aaa; font-size: 24px; cursor: pointer; }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        .form-group input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-submit { background: #000; color: #fff; padding: 10px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
    </style>
</head>
<body>

<div class="header">
    <h2>Gestión de Catálogos (Géneros y Tarifas)</h2>
    <a href="dashboard_admin.php" class="btn-back">Volver al Panel</a>
</div>

<!-- ================= SECCIÓN 1: CRUD GÉNEROS ================= -->
<div class="container">
    <h3>Catálogo de Géneros</h3>
    <button class="btn-add" onclick="abrirModal('modalCrear', 'genero')">+ Nuevo Género</button>
    <table>
        <thead>
            <tr><th>ID</th><th>Nombre del Género</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php foreach ($generos as $g): ?>
            <tr>
                <td><?php echo $g['cod_genero']; ?></td>
                <td><?php echo htmlspecialchars($g['nombre']); ?></td>
                <td>
                    <!-- Botones con iconos para cumplir el Punto 7 -->
                    <button class="btn-action btn-edit" onclick="abrirModalEditar('genero', <?php echo $g['cod_genero']; ?>, '<?php echo htmlspecialchars($g['nombre'], ENT_QUOTES); ?>')">✏️ Editar</button>
                    <button class="btn-action btn-delete" onclick="confirmarEliminacion('genero', <?php echo $g['cod_genero']; ?>)">🗑️ Eliminar</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ================= SECCIÓN 2: CRUD TARIFAS ================= -->
<div class="container">
    <h3>Catálogo de Tarifas</h3>
    <button class="btn-add" onclick="abrirModal('modalCrear', 'tarifa')">+ Nueva Tarifa</button>
    <table>
        <thead>
            <tr><th>ID</th><th>Descripción de la Tarifa</th><th>Acciones</th></tr>
        </thead>
        <tbody>
            <?php foreach ($tarifas as $t): ?>
            <tr>
                <td><?php echo $t['cod_tarifa']; ?></td>
                <td><?php echo htmlspecialchars($t['descripcion']); ?></td>
                <td>
                    <button class="btn-action btn-edit" onclick="abrirModalEditar('tarifa', <?php echo $t['cod_tarifa']; ?>, '<?php echo htmlspecialchars($t['descripcion'], ENT_QUOTES); ?>')">✏️ Editar</button>
                    <button class="btn-action btn-delete" onclick="confirmarEliminacion('tarifa', <?php echo $t['cod_tarifa']; ?>)">🗑️ Eliminar</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ================= MODALES UNIFICADOS ================= -->
<!-- Modal para Crear -->
<div id="modalCrear" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="tituloCrear">Crear Registro</h3>
            <span class="close" onclick="cerrarModal('modalCrear')">&times;</span>
        </div>
        <form action="../../auth/procesar_catalogos.php" method="POST">
            <input type="hidden" name="accion" value="crear">
            <input type="hidden" name="tabla" id="tablaCrear">
            <div class="form-group">
                <label id="labelCrear">Nombre/Descripción:</label>
                <input type="text" name="valor" required>
            </div>
            <button type="submit" class="btn-submit">Guardar</button>
        </form>
    </div>
</div>

<!-- Modal para Editar -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Registro</h3>
            <span class="close" onclick="cerrarModal('modalEditar')">&times;</span>
        </div>
        <form action="../../auth/procesar_catalogos.php" method="POST">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="tabla" id="tablaEditar">
            <input type="hidden" name="id" id="idEditar">
            <div class="form-group">
                <label>Nuevo Valor:</label>
                <input type="text" name="valor" id="valorEditar" required>
            </div>
            <button type="submit" class="btn-submit" style="background:#007bff;">Actualizar</button>
        </form>
    </div>
</div>

<script>
    // Funciones para abrir y cerrar modales dinámicamente
    function abrirModal(idModal, tabla) {
        document.getElementById(idModal).style.display = "flex";
        document.getElementById('tablaCrear').value = tabla;
        document.getElementById('tituloCrear').innerText = tabla === 'genero' ? 'Crear Nuevo Género' : 'Crear Nueva Tarifa';
    }
    
    function abrirModalEditar(tabla, id, valor) {
        document.getElementById('modalEditar').style.display = "flex";
        document.getElementById('tablaEditar').value = tabla;
        document.getElementById('idEditar').value = id;
        document.getElementById('valorEditar').value = valor;
    }

    function cerrarModal(idModal) {
        document.getElementById(idModal).style.display = "none";
    }

    function confirmarEliminacion(tabla, id) {
        Swal.fire({
            title: '¿Eliminar registro?',
            text: "No podrás revertir esto.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../auth/procesar_catalogos.php?accion=eliminar&tabla=${tabla}&id=${id}`;
            }
        });
    }

    // Alertas de éxito o error
    <?php if(isset($_GET['msg'])): ?>
        const msg = "<?php echo $_GET['msg']; ?>";
        if(msg === 'creado') Swal.fire('¡Éxito!', 'Registro guardado.', 'success');
        if(msg === 'editado') Swal.fire('¡Actualizado!', 'Registro modificado.', 'success');
        if(msg === 'eliminado') Swal.fire('¡Eliminado!', 'Registro borrado.', 'success');
        if(msg === 'error') Swal.fire('Error', 'No se pudo procesar la solicitud.', 'error');
        window.history.replaceState(null, null, window.location.pathname);
    <?php endif; ?>
</script>

</body>
</html>