<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();

// 1. CONTROL DE ACCESO ESTRICTO: Verificar sesión y rol de Administrador (Rol 1)
exigir_rol([1], '../public/login.php');

require_once '../../config/conexion.php';

// 2. CONSULTA SEGURA: Traer lista de usuarios
try {
    $sql = "SELECT u.cod_usuario, u.nombre, u.apellidos, u.email, u.telefono, u.cod_rol, r.nombre as rol_nombre, u.activo,
                   COALESCE(e.cod_cine, al.cod_cine) AS cod_cine
            FROM USUARIO u 
            INNER JOIN ROL r ON u.cod_rol = r.cod_rol 
            LEFT JOIN EMPLEADO e ON e.cod_usuario = u.cod_usuario
            LEFT JOIN ADMIN_LOCAL al ON al.cod_usuario = u.cod_usuario
            ORDER BY u.cod_usuario ASC";
    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $cines = $pdo->query("SELECT cod_cine, nombre FROM CINE WHERE activo = '1' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    $solicitudesBeneficioPendientes = (int) $pdo->query(
        "SELECT COUNT(*) FROM SOLICITUD_BENEFICIO WHERE estado = 'pendiente'"
    )->fetchColumn();
    $recuperacionesPendientes = (int) $pdo->query(
        "SELECT COUNT(*) FROM PASSWORD_RESET_REQUEST WHERE estado = 'pendiente'"
    )->fetchColumn();
} catch (PDOException $e) {
    error_log('No se pudo cargar el panel administrativo: ' . $e->getMessage());
    die("Error seguro: Ocurrió un problema al consultar la base de datos.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Cinex+</title>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
</head>
<body>

<!-- Cabecera -->
<div class="header">
    <h2>Panel de Administración - Cinex+</h2>
    <form action="../../auth/logout.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="btn-logout">Cerrar Sesión</button>
    </form>
</div>

<!-- Tarjetas de Acceso Rápido a Módulos -->
<div class="modules-grid">
    <a href="crud_catalogos.php" class="card-module catalogos">
        <h4>📁 Catálogos</h4>
        <p>Gestionar Géneros y Tarifas</p>
    </a>
    <a href="crud_cines.php" class="card-module cines">
        <h4>🎬 Sucursales (Cines)</h4>
        <p>Administrar sedes físicas</p>
    </a>
    <a href="crud_salas.php" class="card-module salas">
        <h4>🪑 Salas de Cine</h4>
        <p>Configurar salas por sucursal</p>
    </a>

    <a href="crud_peliculas.php" class="card-module peliculas">
        <h4>🎬 Películas</h4>
        <p>Catálogo de películas y sinopsis</p>
    </a>
    <a href="crud_pases.php" class="card-module">
        <h4>🗓️ Programación</h4>
        <p>Crear y administrar funciones</p>
    </a>
    <a href="crud_precios.php" class="card-module">
        <h4>💳 Precios</h4>
        <p>Configurar tarifas en COP por cine</p>
    </a>
    <a href="reportes.php" class="card-module">
        <h4>📊 Reportes</h4>
        <p>Consultar reservas, ventas y películas por periodo</p>
    </a>
    <a href="beneficios.php" class="card-module">
        <h4>🎟️ Solicitudes de descuentos<?php if ($solicitudesBeneficioPendientes): ?> · <?php echo $solicitudesBeneficioPendientes; ?> pendientes<?php endif; ?></h4>
        <p>Revisar soportes de elegibilidad de clientes</p>
    </a>
    <a href="recuperaciones.php" class="card-module">
        <h4>🔐 Recuperar cuentas<?php if ($recuperacionesPendientes): ?> · <?php echo $recuperacionesPendientes; ?> pendientes<?php endif; ?></h4>
        <p>Generar un enlace seguro si el correo no está configurado</p>
    </a>
</div>

<!-- Gestión de Usuarios -->
<div class="container">
    <div class="table-header">
        <h3>Gestión de Usuarios</h3>
        <button class="btn-add" onclick="abrirModalCrear()">+ Crear Nuevo Usuario</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $user): ?>
            <tr>
                <td><?php echo (int)$user['cod_usuario']; ?></td>
                <td><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellidos'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($user['rol_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo $user['activo'] === '1' ? 'Activo' : 'Inactivo'; ?></td>
                <td>
                    <button type="button" class="btn-action btn-edit edit-user"
                        data-id="<?php echo (int) $user['cod_usuario']; ?>"
                        data-nombre="<?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-apellidos="<?php echo htmlspecialchars($user['apellidos'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-email="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-telefono="<?php echo htmlspecialchars($user['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-rol="<?php echo (int) $user['cod_rol']; ?>"
                        data-cine="<?php echo (int) ($user['cod_cine'] ?? 0); ?>">✏️ Editar</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Crear Usuario -->
<div id="modalCrear" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Registrar Usuario</h3>
            <span class="close" onclick="cerrarModalCrear()">&times;</span>
        </div>
        <form action="../../auth/create_user.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group"><label>Nombre:</label><input type="text" name="nombre" required></div>
            <div class="form-group"><label>Apellidos:</label><input type="text" name="apellidos" required></div>
            <div class="form-group"><label>Email:</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Teléfono:</label><input type="text" name="telefono"></div>
            <div class="form-group"><label>Contraseña</label><input type="password" name="password" minlength="8" autocomplete="new-password" required><small>8 caracteres como mínimo, mayúscula, minúscula, número y símbolo; sin espacios, patrones simples ni datos personales.</small></div>
            <div class="form-group">
                <label>Rol:</label>
                <select name="rol" id="createRole" required onchange="actualizarCampoCine('createRole', 'createCinemaGroup')">
                    <option value="3">Cliente</option>
                    <option value="2">Empleado</option>
                    <option value="1">Administrador</option>
                </select>
            </div>
            <div class="form-group" id="createCinemaGroup" hidden>
                <label for="createCinema">Cine asignado:</label>
                <select name="cod_cine" id="createCinema">
                    <option value="">Selecciona un cine</option>
                    <?php foreach ($cines as $cine): ?>
                        <option value="<?php echo (int)$cine['cod_cine']; ?>"><?php echo htmlspecialchars($cine['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$cines): ?><small>Registra un cine antes de crear empleados o administradores locales.</small><?php endif; ?>
            </div>
            <button type="submit" class="btn-submit">Guardar Usuario</button>
        </form>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Usuario</h3>
            <span class="close" onclick="cerrarModalEditar()">&times;</span>
        </div>
        <form action="../../auth/update_user.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="form-group"><label>Nombre:</label><input type="text" name="edit_nombre" id="edit_nombre" required></div>
            <div class="form-group"><label>Apellidos:</label><input type="text" name="edit_apellidos" id="edit_apellidos" required></div>
            <div class="form-group"><label>Email:</label><input type="email" name="edit_email" id="edit_email" required></div>
            <div class="form-group"><label>Teléfono:</label><input type="text" name="edit_telefono" id="edit_telefono"></div>
            <div class="form-group">
                <label>Rol:</label>
                <select name="edit_rol" id="edit_rol" required onchange="actualizarCampoCine('edit_rol', 'editCinemaGroup')">
                    <option value="3">Cliente</option>
                    <option value="2">Empleado</option>
                    <option value="1">Administrador</option>
                </select>
            </div>
            <div class="form-group" id="editCinemaGroup" hidden>
                <label for="edit_cine">Cine asignado:</label>
                <select name="edit_cod_cine" id="edit_cine">
                    <option value="">Selecciona un cine</option>
                    <?php foreach ($cines as $cine): ?>
                        <option value="<?php echo (int)$cine['cod_cine']; ?>"><?php echo htmlspecialchars($cine['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit btn-update">Actualizar Datos</button>
        </form>
    </div>
</div>

<script>
    const modalC = document.getElementById("modalCrear");
    const modalE = document.getElementById("modalEditar");

    function abrirModalCrear() {
        modalC.style.display = "flex";
        actualizarCampoCine('createRole', 'createCinemaGroup');
    }
    function cerrarModalCrear() { modalC.style.display = "none"; }

    function abrirModalEditar(id, nombre, apellidos, email, telefono, rol, cine) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nombre').value = nombre;
        document.getElementById('edit_apellidos').value = apellidos;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_telefono').value = telefono;
        document.getElementById('edit_rol').value = rol;
        document.getElementById('edit_cine').value = cine || '';
        actualizarCampoCine('edit_rol', 'editCinemaGroup');
        modalE.style.display = "flex";
    }
    document.querySelectorAll('.edit-user').forEach(button => button.addEventListener('click', () => {
        abrirModalEditar(
            button.dataset.id,
            button.dataset.nombre,
            button.dataset.apellidos,
            button.dataset.email,
            button.dataset.telefono,
            button.dataset.rol,
            button.dataset.cine
        );
    }));
    function actualizarCampoCine(selectId, groupId) {
        const role = document.getElementById(selectId).value;
        const group = document.getElementById(groupId);
        const cine = group.querySelector('select');
        const required = role === '1' || role === '2';
        group.hidden = !required;
        cine.required = required;
    }
    function cerrarModalEditar() { modalE.style.display = "none"; }
    
    window.onclick = function(event) {
        if (event.target == modalC) cerrarModalCrear();
        if (event.target == modalE) cerrarModalEditar();
    }

    <?php if(isset($_GET['msg'])): ?>
        const mensaje = "<?php echo htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8'); ?>";
        if(mensaje === 'created') Swal.fire('¡Éxito!', 'El usuario se registró correctamente.', 'success');
        else if(mensaje === 'updated') Swal.fire('¡Actualizado!', 'Los datos fueron modificados.', 'success');
        else if(mensaje === 'error_update') Swal.fire('No se pudo actualizar', 'Verifica que el correo no esté en uso y que el cine asignado sea válido.', 'error');
        else if(mensaje === 'duplicate_email') Swal.fire('Correo ya registrado', 'Usa un correo que no pertenezca a otra cuenta.', 'warning');
        else if(mensaje === 'invalid_cinema') Swal.fire('Cine no válido', 'Selecciona un cine activo para este rol.', 'warning');
        else if(mensaje === 'error_create') Swal.fire('No se pudo crear', 'Verifica los datos y la política de contraseña.', 'error');
        else if(mensaje === 'error_db') Swal.fire('Error', 'No se pudo procesar la solicitud.', 'error');
        window.history.replaceState(null, null, window.location.pathname);
    <?php endif; ?>
</script>

</body>
</html>