<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
// Proteger la ruta para administradores
exigir_rol([1], '../public/login.php');

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Catálogos - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
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
                    <button type="button" class="btn-action btn-edit edit-catalog" data-tabla="genero" data-id="<?php echo (int) $g['cod_genero']; ?>" data-valor="<?php echo htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8'); ?>">✏️ Editar</button>
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
                    <button type="button" class="btn-action btn-edit edit-catalog" data-tabla="tarifa" data-id="<?php echo (int) $t['cod_tarifa']; ?>" data-valor="<?php echo htmlspecialchars($t['descripcion'], ENT_QUOTES, 'UTF-8'); ?>" data-beneficio="<?php echo htmlspecialchars($t['beneficio_requerido'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">✏️ Editar</button>
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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="accion" value="crear">
            <input type="hidden" name="tabla" id="tablaCrear">
            <div class="form-group">
                <label id="labelCrear">Nombre/Descripción:</label>
                <input type="text" name="valor" required>
            </div>
            <div class="form-group" id="beneficioCrearGroup" hidden>
                <label for="beneficioCrear">Requisito de descuento</label>
                <select name="beneficio_requerido" id="beneficioCrear">
                    <option value="">Tarifa general</option>
                    <option value="estudiante">Estudiante con beneficio aprobado</option>
                    <option value="pensionado">Pensionado con beneficio aprobado</option>
                    <option value="servicio_militar">Servicio militar con beneficio aprobado</option>
                    <option value="discapacidad">Persona con discapacidad con beneficio aprobado</option>
                </select>
                <small>Solo los clientes cuya acreditación esté vigente podrán reservar esta tarifa.</small>
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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="tabla" id="tablaEditar">
            <input type="hidden" name="id" id="idEditar">
            <div class="form-group">
                <label>Nuevo Valor:</label>
                <input type="text" name="valor" id="valorEditar" required>
            </div>
            <div class="form-group" id="beneficioEditarGroup" hidden>
                <label for="beneficioEditar">Requisito de descuento</label>
                <select name="beneficio_requerido" id="beneficioEditar">
                    <option value="">Tarifa general</option>
                    <option value="estudiante">Estudiante con beneficio aprobado</option>
                    <option value="pensionado">Pensionado con beneficio aprobado</option>
                    <option value="servicio_militar">Servicio militar con beneficio aprobado</option>
                    <option value="discapacidad">Persona con discapacidad con beneficio aprobado</option>
                </select>
            </div>
            <button type="submit" class="btn-submit btn-update">Actualizar</button>
        </form>
    </div>
</div>

<script>
    const csrfToken = <?php echo json_encode(token_csrf()); ?>;
    // Funciones para abrir y cerrar modales dinámicamente
    function abrirModal(idModal, tabla) {
        document.getElementById(idModal).style.display = "flex";
        document.getElementById('tablaCrear').value = tabla;
        document.getElementById('tituloCrear').innerText = tabla === 'genero' ? 'Crear Nuevo Género' : 'Crear Nueva Tarifa';
        document.getElementById('beneficioCrearGroup').hidden = tabla !== 'tarifa';
        document.getElementById('beneficioCrear').value = '';
    }
    
    function abrirModalEditar(tabla, id, valor, beneficio = '') {
        document.getElementById('modalEditar').style.display = "flex";
        document.getElementById('tablaEditar').value = tabla;
        document.getElementById('idEditar').value = id;
        document.getElementById('valorEditar').value = valor;
        document.getElementById('beneficioEditarGroup').hidden = tabla !== 'tarifa';
        document.getElementById('beneficioEditar').value = beneficio;
    }

    document.querySelectorAll('.edit-catalog').forEach(button => button.addEventListener('click', () => {
        abrirModalEditar(button.dataset.tabla, button.dataset.id, button.dataset.valor, button.dataset.beneficio || '');
    }));

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
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../auth/procesar_catalogos.php';
                form.innerHTML = `<input type="hidden" name="accion" value="eliminar"><input type="hidden" name="tabla" value="${tabla}"><input type="hidden" name="id" value="${id}"><input type="hidden" name="csrf_token" value="${csrfToken}">`;
                document.body.appendChild(form);
                form.submit();
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