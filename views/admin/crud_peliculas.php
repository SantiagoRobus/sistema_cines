<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
exigir_rol([1], '../public/login.php');

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Películas - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
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
        <p class="notice"><strong>Aviso:</strong> Debes registrar al menos un Género en el catálogo antes de agregar películas.</p>
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
                    <button type="button" class="btn-action btn-edit edit-movie"
                        data-id="<?php echo (int) $p['cod_pelicula']; ?>"
                        data-titulo="<?php echo htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-duracion="<?php echo (int) $p['duracion_min']; ?>"
                        data-sinopsis="<?php echo htmlspecialchars($p['sinopsis'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-genero="<?php echo (int) $p['cod_genero']; ?>">✏️ Editar</button>
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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
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
            <div class="form-group"><label>Sinopsis:</label><textarea name="sinopsis" rows="3"></textarea></div>
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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
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
            <div class="form-group"><label>Sinopsis:</label><textarea name="sinopsis" id="sinopsisEditar" rows="3"></textarea></div>
            <button type="submit" class="btn-submit btn-update">Actualizar Película</button>
        </form>
    </div>
</div>

<script>
    const csrfToken = <?php echo json_encode(token_csrf()); ?>;
    function abrirModalCrear() { document.getElementById('modalCrear').style.display = "flex"; }
    function abrirModalEditar(id, titulo, duracion, sinopsis, codGenero) {
        document.getElementById('modalEditar').style.display = "flex";
        document.getElementById('idEditar').value = id;
        document.getElementById('tituloEditar').value = titulo;
        document.getElementById('duracionEditar').value = duracion;
        document.getElementById('sinopsisEditar').value = sinopsis;
        document.getElementById('generoEditar').value = codGenero;
    }
    document.querySelectorAll('.edit-movie').forEach(button => button.addEventListener('click', () => {
        abrirModalEditar(button.dataset.id, button.dataset.titulo, button.dataset.duracion, button.dataset.sinopsis, button.dataset.genero);
    }));
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
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../auth/procesar_peliculas.php';
                form.innerHTML = `<input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="${id}"><input type="hidden" name="csrf_token" value="${csrfToken}">`;
                document.body.appendChild(form);
                form.submit();
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