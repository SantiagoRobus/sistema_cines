<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
exigir_rol([1], '../public/login.php');

require_once '../../config/conexion.php';

try {
    // 1. Consultar las salas uniendo (JOIN) la tabla CINE para traer el nombre del cine
    $sqlSalas = "SELECT s.cod_sala, s.num_sala, s.capacidad, s.cod_cine, c.nombre AS nombre_cine 
                 FROM SALA s 
                 INNER JOIN CINE c ON s.cod_cine = c.cod_cine 
                 ORDER BY c.nombre ASC, s.num_sala ASC";
    $stmtSalas = $pdo->query($sqlSalas);
    $salas = $stmtSalas->fetchAll(PDO::FETCH_ASSOC);

    // 2. Consultar la lista de cines para rellenar los menús desplegables (<select>)
    $stmtCines = $pdo->query("SELECT cod_cine, nombre FROM CINE WHERE activo = '1' ORDER BY nombre ASC");
    $cines = $stmtCines->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Salas - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
</head>
<body>

<div class="header">
    <h2>Gestión de Salas</h2>
    <a href="dashboard_admin.php" class="btn-back">Volver al Panel Principal</a>
</div>

<div class="container">
    <!-- Validamos si hay cines creados antes de permitir crear una sala -->
    <?php if (count($cines) > 0): ?>
        <button class="btn-add" onclick="abrirModalCrear()">+ Registrar Nueva Sala</button>
    <?php else: ?>
        <p class="notice"><strong>Aviso:</strong> Debes crear al menos un Cine antes de poder registrar Salas.</p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Sucursal (Cine)</th>
                <th>Número de Sala</th>
                <th>Capacidad (Asientos)</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($salas as $s): ?>
            <tr>
                <td><?php echo $s['cod_sala']; ?></td>
                <td><?php echo htmlspecialchars($s['nombre_cine']); ?></td>
                <td>Sala <?php echo htmlspecialchars($s['num_sala']); ?></td>
                <td><?php echo htmlspecialchars($s['capacidad']); ?></td>
                <td>
                    <!-- Pasamos también el ID del cine (cod_cine) para que el select se posicione correctamente -->
                    <button class="btn-action btn-edit" onclick="abrirModalEditar(
                        <?php echo $s['cod_sala']; ?>, 
                        <?php echo $s['num_sala']; ?>,
                        <?php echo $s['capacidad']; ?>,
                        <?php echo $s['cod_cine']; ?>
                    )">✏️ Editar</button>
                    <button class="btn-action btn-delete" onclick="confirmarEliminacion(<?php echo $s['cod_sala']; ?>)">🗑️ Eliminar</button>
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
            <h3>Registrar Sala</h3>
            <span class="close" onclick="cerrarModal('modalCrear')">&times;</span>
        </div>
        <form action="../../auth/procesar_salas.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="accion" value="crear">
            
            <div class="form-group">
                <label>Sucursal (Cine):</label>
                <!-- Menú desplegable alimentado por la base de datos -->
                <select name="cod_cine" required>
                    <option value="">-- Seleccione un Cine --</option>
                    <?php foreach ($cines as $cine): ?>
                        <option value="<?php echo $cine['cod_cine']; ?>"><?php echo htmlspecialchars($cine['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group"><label>Número de Sala:</label><input type="number" name="num_sala" required min="1"></div>
            <div class="form-group"><label>Capacidad (N° de Asientos):</label><input type="number" name="capacidad" required min="1"></div>
            
            <button type="submit" class="btn-submit">Guardar Sala</button>
        </form>
    </div>
</div>

<!-- Modal para Editar -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Sala</h3>
            <span class="close" onclick="cerrarModal('modalEditar')">&times;</span>
        </div>
        <form action="../../auth/procesar_salas.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="idEditar">
            
            <div class="form-group">
                <label>Sucursal (Cine):</label>
                <select name="cod_cine" id="cineEditar" required>
                    <?php foreach ($cines as $cine): ?>
                        <option value="<?php echo $cine['cod_cine']; ?>"><?php echo htmlspecialchars($cine['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group"><label>Número de Sala:</label><input type="number" name="num_sala" id="numEditar" required min="1"></div>
            <div class="form-group"><label>Capacidad (N° de Asientos):</label><input type="number" name="capacidad" id="capEditar" required min="1"></div>
            
            <button type="submit" class="btn-submit btn-update">Actualizar Datos</button>
        </form>
    </div>
</div>

<script>
    const csrfToken = <?php echo json_encode(token_csrf()); ?>;
    function abrirModalCrear() {
        document.getElementById('modalCrear').style.display = "flex";
    }
    
    function abrirModalEditar(id, num, cap, codCine) {
        document.getElementById('modalEditar').style.display = "flex";
        document.getElementById('idEditar').value = id;
        document.getElementById('numEditar').value = num;
        document.getElementById('capEditar').value = cap;
        document.getElementById('cineEditar').value = codCine; // Esto autoselecciona el cine correcto en el select
    }

    function cerrarModal(idModal) {
        document.getElementById(idModal).style.display = "none";
    }

    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Eliminar esta sala?',
            text: "No podrás revertir esto.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../auth/procesar_salas.php';
                form.innerHTML = `<input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="${id}"><input type="hidden" name="csrf_token" value="${csrfToken}">`;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    <?php if(isset($_GET['msg'])): ?>
        const msg = "<?php echo $_GET['msg']; ?>";
        if(msg === 'creado') Swal.fire('¡Éxito!', 'La sala fue registrada.', 'success');
        if(msg === 'editado') Swal.fire('¡Actualizado!', 'Los datos de la sala fueron modificados.', 'success');
        if(msg === 'eliminado') Swal.fire('¡Eliminado!', 'La sala fue borrada correctamente.', 'success');
        if(msg === 'error_db') Swal.fire('Error', 'No se pudo eliminar la sala. Es posible que tenga horarios o reservas asociadas.', 'error');
        window.history.replaceState(null, null, window.location.pathname);
    <?php endif; ?>
</script>

</body>
</html>