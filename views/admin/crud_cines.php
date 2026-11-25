<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
exigir_rol([1], '../public/login.php');

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cines - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
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
                    <button type="button" class="btn-action btn-edit edit-cinema"
                        data-id="<?php echo (int) $c['cod_cine']; ?>"
                        data-nombre="<?php echo htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-calle="<?php echo htmlspecialchars($c['calle'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-numero="<?php echo htmlspecialchars($c['numero'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-telefono="<?php echo htmlspecialchars($c['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">✏️ Editar</button>
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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="accion" value="crear">
            <div class="form-group"><label>Nombre del Cine:</label><input type="text" name="nombre" required></div>
            <div class="form-group"><label>Calle/Avenida:</label><input type="text" name="calle" required></div>
            <div class="form-group"><label>Número:</label><input type="text" name="numero" required></div>
            <div class="form-group"><label>Teléfono:</label><input type="text" name="telefono"></div>
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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="idEditar">
            <div class="form-group"><label>Nombre del Cine:</label><input type="text" name="nombre" id="nombreEditar" required></div>
            <div class="form-group"><label>Calle/Avenida:</label><input type="text" name="calle" id="calleEditar" required></div>
            <div class="form-group"><label>Número:</label><input type="text" name="numero" id="numeroEditar" required></div>
            <div class="form-group"><label>Teléfono:</label><input type="text" name="telefono" id="telefonoEditar"></div>
            <button type="submit" class="btn-submit btn-update">Actualizar Datos</button>
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
    document.querySelectorAll('.edit-cinema').forEach(button => button.addEventListener('click', () => {
        abrirModalEditar(button.dataset.id, button.dataset.nombre, button.dataset.calle, button.dataset.numero, button.dataset.telefono);
    }));

    function cerrarModal(idModal) {
        document.getElementById(idModal).style.display = "none";
    }

    // Alertas de SweetAlert2
    <?php if(isset($_GET['msg'])): ?>
        const msg = "<?php echo $_GET['msg']; ?>";
        if(msg === 'creado') Swal.fire('¡Éxito!', 'La sucursal fue registrada.', 'success');
        if(msg === 'editado') Swal.fire('¡Actualizado!', 'Los datos del cine fueron modificados.', 'success');
        if(msg === 'error') Swal.fire('Error', 'Revisa los datos del cine e inténtalo de nuevo.', 'error');
        window.history.replaceState(null, null, window.location.pathname);
    <?php endif; ?>
</script>

</body>
</html>