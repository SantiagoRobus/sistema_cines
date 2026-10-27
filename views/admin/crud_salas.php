<?php
session_start();
if (!isset($_SESSION['rol_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../public/login.php");
    exit();
}

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
    <title>Gestión de Salas</title>
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
        
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background-color: #fff; padding: 20px; border-radius: 8px; width: 400px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .close { color: #aaa; font-size: 24px; cursor: pointer; }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        .form-group input, .form-group select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-submit { background: #000; color: #fff; padding: 10px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
    </style>
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
        <p style="color: red;"><strong>Aviso:</strong> Debes crear al menos un Cine antes de poder registrar Salas.</p>
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
            
            <button type="submit" class="btn-submit" style="background:#007bff;">Actualizar Datos</button>
        </form>
    </div>
</div>

<script>
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
                window.location.href = `../../auth/procesar_salas.php?accion=eliminar&id=${id}`;
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