<?php
session_start();
if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../public/login.php");
    exit();
}

require_once '../../config/conexion.php';

// Leer los datos de los cines
try {
    $stmt = $pdo->query("SELECT * FROM CINE ORDER BY cod_cine ASC");
    $cines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Cines</title>
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
        .modal-content { background-color: #fff; padding: 20px; border-radius: 8px; width: 400px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .close { color: #aaa; font-size: 24px; cursor: pointer; }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        .form-group input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-submit { background: #000; color: #fff; padding: 10px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
    </style>
</head>
<body>

<div class="header">
    <h2>Gestión de Sucursales (Cines)</h2>
    <a href="dashboard_admin.php" class="btn-back">Volver al Panel Principal</a>
</div>

<div class="container">
    <button class="btn-add" onclick="abrirModalCrear()">+ Registrar Nuevo Cine</button>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Dirección (Calle y No.)</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cines as $c): ?>
            <tr>
                <td><?php echo $c['cod_cine']; ?></td>
                <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                <td><?php echo htmlspecialchars($c['calle'] . ' #' . $c['numero']); ?></td>
                <td><?php echo htmlspecialchars($c['telefono']); ?></td>
                <td>
                    <button class="btn-action btn-edit" onclick="abrirModalEditar(
                        <?php echo $c['cod_cine']; ?>, 
                        '<?php echo htmlspecialchars($c['nombre'], ENT_QUOTES); ?>',
                        '<?php echo htmlspecialchars($c['calle'], ENT_QUOTES); ?>',
                        '<?php echo htmlspecialchars($c['numero'], ENT_QUOTES); ?>',
                        '<?php echo htmlspecialchars($c['telefono'], ENT_QUOTES); ?>'
                    )">✏️ Editar</button>
                    <button class="btn-action btn-delete" onclick="confirmarEliminacion(<?php echo $c['cod_cine']; ?>)">🗑️ Eliminar</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Crear -->
<div id="modalCrear" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Registrar Cine</h3>
            <span class="close" onclick="cerrarModal('modalCrear')">&times;</span>
        </div>
        <form action="../../auth/procesar_cines.php" method="POST">
            <input type="hidden" name="accion" value="crear">
            <div class="form-group"><label>Nombre del Cine:</label><input type="text" name="nombre" required></div>
            <div class="form-group"><label>Calle/Avenida:</label><input type="text" name="calle" required></div>
            <div class="form-group"><label>Número:</label><input type="text" name="numero" required></div>
            <div class="form-group"><label>Teléfono:</label><input type="text" name="telefono" required></div>
            <button type="submit" class="btn-submit">Guardar Sucursal</button>
        </form>
    </div>
</div>

<!-- Modal para Editar -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Cine</h3>
            <span class="close" onclick="cerrarModal('modalEditar')">&times;</span>
        </div>
        <form action="../../auth/procesar_cines.php" method="POST">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="idEditar">
            <div class="form-group"><label>Nombre del Cine:</label><input type="text" name="nombre" id="nombreEditar" required></div>
            <div class="form-group"><label>Calle/Avenida:</label><input type="text" name="calle" id="calleEditar" required></div>
            <div class="form-group"><label>Número:</label><input type="text" name="numero" id="numeroEditar" required></div>
            <div class="form-group"><label>Teléfono:</label><input type="text" name="telefono" id="telefonoEditar" required></div>
            <button type="submit" class="btn-submit" style="background:#007bff;">Actualizar Datos</button>
        </form>
    </div>
</div>

<script>
    function abrirModalCrear() {
        document.getElementById('modalCrear').style.display = "flex";
    }
    
    function abrirModalEditar(id, nombre, calle, numero, telefono) {
        document.getElementById('modalEditar').style.display = "flex";
        document.getElementById('idEditar').value = id;
        document.getElementById('nombreEditar').value = nombre;
        document.getElementById('calleEditar').value = calle;
        document.getElementById('numeroEditar').value = numero;
        document.getElementById('telefonoEditar').value = telefono;
    }

    function cerrarModal(idModal) {
        document.getElementById(idModal).style.display = "none";
    }

    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Eliminar esta sucursal?',
            text: "No podrás revertir esto.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../auth/procesar_cines.php?accion=eliminar&id=${id}`;
            }
        });
    }

    // Alertas de SweetAlert2
    <?php if(isset($_GET['msg'])): ?>
        const msg = "<?php echo $_GET['msg']; ?>";
        if(msg === 'creado') Swal.fire('¡Éxito!', 'La sucursal fue registrada.', 'success');
        if(msg === 'editado') Swal.fire('¡Actualizado!', 'Los datos del cine fueron modificados.', 'success');
        if(msg === 'eliminado') Swal.fire('¡Eliminado!', 'El cine fue borrado correctamente.', 'success');
        if(msg === 'error_db') Swal.fire('Error', 'No se pudo eliminar. Verifica que no haya salas o empleados asignados a este cine.', 'error');
        window.history.replaceState(null, null, window.location.pathname);
    <?php endif; ?>
</script>

</body>
</html>