<?php
session_start();
if (!isset($_SESSION['rol_id']) || (int)$_SESSION['rol_id'] !== 1) {
    header("Location: ../public/login.php");
    exit();
}

require_once '../../config/conexion.php';

try {
    // Consultar películas con JOIN a GENERO
    $sqlPeliculas = "SELECT p.cod_pelicula, p.titulo, p.duracion_min, p.sinopsis, p.cod_genero, g.nombre AS genero_nombre 
                     FROM PELICULA p 
                     INNER JOIN GENERO g ON p.cod_genero = g.cod_genero 
                     ORDER BY p.cod_pelicula ASC";
    $stmtPeliculas = $pdo->query($sqlPeliculas);
    $peliculas = $stmtPeliculas->fetchAll(PDO::FETCH_ASSOC);

    // Consultar géneros para el select
    $stmtGeneros = $pdo->query("SELECT cod_genero, nombre FROM GENERO ORDER BY nombre ASC");
    $generos = $stmtGeneros->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error seguro al cargar películas.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Películas - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #2c2c2c; color: #333; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #000; color: #fff; padding: 15px 25px; border-radius: 8px; margin-bottom: 25px; }
        .container { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background-color: #f8f9fa; }
        .btn-add { background: #28a745; color: #fff; padding: 10px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-action { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; color: #fff; font-size: 12px; margin-right: 4px; }
        .btn-edit { background: #007bff; }
        .btn-delete { background: #dc3545; }
        .btn-back { background: #555; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 4px; font-size: 14px; }

        .modal { display: none; position: fixed; z-index: 10; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); justify-content: center; align-items: center; }
        .modal-content { background-color: #fff; padding: 25px; border-radius: 8px; width: 420px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .close { color: #aaa; font-size: 24px; cursor: pointer; font-weight: bold; }
        .form-group { margin-bottom: 12px; display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 4px; font-size: 13px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        .btn-submit { background: #000; color: #fff; padding: 10px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>

<div class="header">
    <h2>Catálogo de Películas</h2>
    <a href="dashboard_admin.php" class="btn-back">Volver al Panel Principal</a>
</div>

<div class="container">
    <?php if (count($generos) > 0): ?>
        <button class="btn-add" onclick="abrirModalCrear()">+ Registrar Nueva Película</button>
    <?php else: ?>
        <p style="color: red;"><strong>Aviso:</strong> Debes registrar al menos un Género en el catálogo antes de agregar películas.</p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Género</th>
                <th>Duración</th>
                <th>Sinopsis</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($peliculas as $p): ?>
            <tr>
                <td><?php echo (int)$p['cod_pelicula']; ?></td>
                <td><strong><?php echo htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <td><?php echo htmlspecialchars($p['genero_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int)$p['duracion_min']; ?> min</td>
                <td><?php echo htmlspecialchars($p['sinopsis'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                    <button class="btn-action btn-edit" onclick="abrirModalEditar(
                        <?php echo (int)$p['cod_pelicula']; ?>,
                        '<?php echo htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8'); ?>',
                        <?php echo (int)$p['duracion_min']; ?>,
                        '<?php echo htmlspecialchars($p['sinopsis'], ENT_QUOTES, 'UTF-8'); ?>',
                        <?php echo (int)$p['cod_genero']; ?>
                    )">✏️ Editar</button>
                    <button class="btn-action btn-delete" onclick="confirmarEliminacion(<?php echo (int)$p['cod_pelicula']; ?>)">🗑️ Eliminar</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Crear -->
<div id="modalCrear" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Registrar Película</h3>
            <span class="close" onclick="cerrarModal('modalCrear')">&times;</span>
        </div>
        <form action="../../auth/procesar_peliculas.php" method="POST">
            <input type="hidden" name="accion" value="crear">
            <div class="form-group"><label>Título:</label><input type="text" name="titulo" required></div>
            <div class="form-group">
                <label>Género:</label>
                <select name="cod_genero" required>
                    <option value="">-- Seleccione un Género --</option>
                    <?php foreach ($generos as $g): ?>
                        <option value="<?php echo (int)$g['cod_genero']; ?>"><?php echo htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Duración (Minutos):</label><input type="number" name="duracion_min" required min="1"></div>
            <div class="form-group"><label>Sinopsis:</label><textarea name="sinopsis" rows="3" required></textarea></div>
            <button type="submit" class="btn-submit">Guardar Película</button>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Película</h3>
            <span class="close" onclick="cerrarModal('modalEditar')">&times;</span>
        </div>
        <form action="../../auth/procesar_peliculas.php" method="POST">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="idEditar">
            <div class="form-group"><label>Título:</label><input type="text" name="titulo" id="tituloEditar" required></div>
            <div class="form-group">
                <label>Género:</label>
                <select name="cod_genero" id="generoEditar" required>
                    <?php foreach ($generos as $g): ?>
                        <option value="<?php echo (int)$g['cod_genero']; ?>"><?php echo htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Duración (Minutos):</label><input type="number" name="duracion_min" id="duracionEditar" required min="1"></div>
            <div class="form-group"><label>Sinopsis:</label><textarea name="sinopsis" id="sinopsisEditar" rows="3" required></textarea></div>
            <button type="submit" class="btn-submit" style="background:#007bff;">Actualizar Película</button>
        </form>
    </div>
</div>

<script>
    function abrirModalCrear() { document.getElementById('modalCrear').style.display = "flex"; }
    function abrirModalEditar(id, titulo, duracion, sinopsis, codGenero) {
        document.getElementById('modalEditar').style.display = "flex";
        document.getElementById('idEditar').value = id;
        document.getElementById('tituloEditar').value = titulo;
        document.getElementById('duracionEditar').value = duracion;
        document.getElementById('sinopsisEditar').value = sinopsis;
        document.getElementById('generoEditar').value = codGenero;
    }
    function cerrarModal(id) { document.getElementById(id).style.display = "none"; }

    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Eliminar película?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../auth/procesar_peliculas.php?accion=eliminar&id=${id}`;
            }
        });
    }

    <?php if(isset($_GET['msg'])): ?>
        const msg = "<?php echo htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8'); ?>";
        if(msg === 'creado') Swal.fire('¡Éxito!', 'Película registrada correctamente.', 'success');
        if(msg === 'editado') Swal.fire('¡Actualizado!', 'Datos de la película modificados.', 'success');
        if(msg === 'eliminado') Swal.fire('¡Eliminado!', 'Película borrada.', 'success');
        if(msg === 'error_db') Swal.fire('Error', 'No se pudo procesar en la base de datos.', 'error');
        window.history.replaceState(null, null, window.location.pathname);
    <?php endif; ?>
</script>

</body>
</html>