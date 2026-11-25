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
    $clasificaciones = $pdo->query("SELECT cod_clasif, descripcion FROM CLASIFICACION ORDER BY descripcion")->fetchAll(PDO::FETCH_ASSOC);
    $directores = $pdo->query("SELECT cod_director, nombre FROM DIRECTOR ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $actores = $pdo->query("SELECT cod_actor, nombre FROM ACTOR ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
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
    <h2>Gestión de Catálogos</h2>
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
                    <button type="button" class="btn-action btn-edit edit-catalog" data-tabla="genero" data-id="<?php echo (int) $g['cod_genero']; ?>" data-valor="<?php echo htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8'); ?>">✏️ Editar</button>
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
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="container">
    <h3>Catálogo de Clasificaciones</h3>
    <button class="btn-add" onclick="abrirModal('modalCrear', 'clasificacion')">+ Nueva Clasificación</button>
    <table>
        <thead><tr><th>ID</th><th>Clasificación</th><th>Acciones</th></tr></thead>
        <tbody>
            <?php foreach ($clasificaciones as $clasificacion): ?>
            <tr>
                <td><?php echo (int) $clasificacion['cod_clasif']; ?></td>
                <td><?php echo htmlspecialchars($clasificacion['descripcion'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><button type="button" class="btn-action btn-edit edit-catalog" data-tabla="clasificacion" data-id="<?php echo (int) $clasificacion['cod_clasif']; ?>" data-valor="<?php echo htmlspecialchars($clasificacion['descripcion'], ENT_QUOTES, 'UTF-8'); ?>">Editar</button></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$clasificaciones): ?><tr><td colspan="3">No hay clasificaciones registradas.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="container">
    <h3>Catálogo de Directores</h3>
    <button class="btn-add" onclick="abrirModal('modalCrear', 'director')">+ Nuevo Director</button>
    <table>
        <thead><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr></thead>
        <tbody>
            <?php foreach ($directores as $director): ?>
            <tr>
                <td><?php echo (int) $director['cod_director']; ?></td>
                <td><?php echo htmlspecialchars($director['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><button type="button" class="btn-action btn-edit edit-catalog" data-tabla="director" data-id="<?php echo (int) $director['cod_director']; ?>" data-valor="<?php echo htmlspecialchars($director['nombre'], ENT_QUOTES, 'UTF-8'); ?>">Editar</button></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$directores): ?><tr><td colspan="3">No hay directores registrados.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="container">
    <h3>Catálogo de Actores</h3>
    <button class="btn-add" onclick="abrirModal('modalCrear', 'actor')">+ Nuevo Actor</button>
    <table>
        <thead><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr></thead>
        <tbody>
            <?php foreach ($actores as $actor): ?>
            <tr>
                <td><?php echo (int) $actor['cod_actor']; ?></td>
                <td><?php echo htmlspecialchars($actor['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><button type="button" class="btn-action btn-edit edit-catalog" data-tabla="actor" data-id="<?php echo (int) $actor['cod_actor']; ?>" data-valor="<?php echo htmlspecialchars($actor['nombre'], ENT_QUOTES, 'UTF-8'); ?>">Editar</button></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$actores): ?><tr><td colspan="3">No hay actores registrados.</td></tr><?php endif; ?>
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
    // Funciones para abrir y cerrar modales dinámicamente
    function abrirModal(idModal, tabla) {
        document.getElementById(idModal).style.display = "flex";
        document.getElementById('tablaCrear').value = tabla;
        const catalogos = {
            genero: ['Género', 'Nombre'],
            tarifa: ['Tarifa', 'Descripción'],
            clasificacion: ['Clasificación', 'Descripción'],
            director: ['Director', 'Nombre'],
            actor: ['Actor', 'Nombre']
        };
        const [etiqueta, campo] = catalogos[tabla];
        document.getElementById('tituloCrear').innerText = `Crear ${etiqueta}`;
        document.getElementById('labelCrear').innerText = `${campo}:`;
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

    // Alertas de éxito o error
    <?php if(isset($_GET['msg'])): ?>
        const msg = "<?php echo $_GET['msg']; ?>";
        if(msg === 'creado') Swal.fire('¡Éxito!', 'Registro guardado.', 'success');
        if(msg === 'editado') Swal.fire('¡Actualizado!', 'Registro modificado.', 'success');
        if(msg === 'error') Swal.fire('Error', 'No se pudo procesar la solicitud.', 'error');
        window.history.replaceState(null, null, window.location.pathname);
    <?php endif; ?>
</script>

</body>
</html>