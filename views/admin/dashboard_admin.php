<?php
session_start();

// 1. CONTROL DE ACCESO ESTRICTO: Verificar sesión y rol de Administrador (Rol 1)
if (!isset($_SESSION['rol_id']) || (int)$_SESSION['rol_id'] !== 1) {
    header("Location: ../public/login.php");
    exit();
}

require_once '../../config/conexion.php';

// 2. CONSULTA SEGURA: Traer lista de usuarios
try {
    $sql = "SELECT u.cod_usuario, u.nombre, u.apellidos, u.email, u.telefono, u.cod_rol, r.nombre as rol_nombre, u.activo 
            FROM USUARIO u 
            INNER JOIN ROL r ON u.cod_rol = r.cod_rol 
            ORDER BY u.cod_usuario ASC";
    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Evita revelar detalles técnicos del servidor o PostgreSQL
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
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #2c2c2c; 
            color: #333; 
            margin: 0; 
            padding: 20px; 
        }
        
        /* Cabecera Principal */
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: #000; 
            color: #fff; 
            padding: 15px 25px; 
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.3); 
            margin-bottom: 25px; 
        }
        .header h2 { margin: 0; font-size: 20px; }
        .btn-logout { 
            background: #d90000; 
            color: #fff; 
            text-decoration: none; 
            padding: 8px 16px; 
            border-radius: 4px; 
            font-size: 14px; 
            font-weight: bold; 
            transition: background 0.2s; 
        }
        .btn-logout:hover { background: #b30000; }

        /* Panel de Tarjetas / Módulos de Navegación */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .card-module {
            background: #fff;
            padding: 18px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            text-decoration: none;
            color: #111;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 5px solid #000;
        }
        .card-module:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
        }
        .card-module.catalogos { border-left-color: #17a2b8; }
        .card-module.cines { border-left-color: #e83e8c; }
        .card-module.salas { border-left-color: #fd7e14; }

        .card-module h4 { margin: 0 0 8px 0; font-size: 16px; color: #111; }
        .card-module p { margin: 0; font-size: 12px; color: #666; }

        /* Contenedor de Tabla */
        .container { 
            background: #fff; 
            padding: 25px; 
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .table-header h3 { margin: 0; font-size: 18px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background-color: #f8f9fa; color: #444; }

        /* Botones de Acción */
        .btn-add { background: #28a745; color: #fff; padding: 10px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; }
        .btn-action { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; color: #fff; font-size: 12px; margin-right: 4px; }
        .btn-edit { background: #007bff; }
        .btn-delete { background: #dc3545; }

        /* Estilos de Modales */
        .modal { display: none; position: fixed; z-index: 10; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); justify-content: center; align-items: center; }
        .modal-content { background-color: #fff; padding: 25px; border-radius: 8px; width: 380px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .close { color: #aaa; font-size: 24px; font-weight: bold; cursor: pointer; }
        .form-group { margin-bottom: 12px; display: flex; flex-direction: column; }
        .form-group label { margin-bottom: 4px; font-size: 13px; font-weight: bold; }
        .form-group input, .form-group select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        .btn-submit { background: #000; color: #fff; padding: 10px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>

<!-- Cabecera -->
<div class="header">
    <h2>Panel de Administración - Cinex+</h2>
    <a href="../../auth/logout.php" class="btn-logout">Cerrar Sesión</a>
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

    <a href="crud_peliculas.php" class="card-module peliculas" style="border-left-color: #6f42c1;">
    <h4>🎬 Películas</h4>
    <p>Catálogo de películas y sinopsis</p>
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
                    <button onclick="abrirModalEditar(
                        <?php echo (int)$user['cod_usuario']; ?>, 
                        '<?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?>',
                        '<?php echo htmlspecialchars($user['apellidos'], ENT_QUOTES, 'UTF-8'); ?>',
                        '<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>',
                        '<?php echo htmlspecialchars($user['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>',
                        <?php echo (int)$user['cod_rol']; ?>
                    )" class="btn-action btn-edit">✏️ Editar</button>
                    
                    <?php if ((int)$user['cod_usuario'] !== (int)$_SESSION['usuario_id']): ?>
                        <button onclick="confirmarEliminacion(<?php echo (int)$user['cod_usuario']; ?>)" class="btn-action btn-delete">🗑️ Eliminar</button>
                    <?php endif; ?>
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
            <div class="form-group"><label>Nombre:</label><input type="text" name="nombre" required></div>
            <div class="form-group"><label>Apellidos:</label><input type="text" name="apellidos" required></div>
            <div class="form-group"><label>Email:</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Teléfono:</label><input type="text" name="telefono"></div>
            <div class="form-group"><label>Contraseña:</label><input type="password" name="password" required></div>
            <div class="form-group">
                <label>Rol:</label>
                <select name="rol" required>
                    <option value="3">Cliente</option>
                    <option value="2">Empleado</option>
                    <option value="1">Administrador</option>
                </select>
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
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="form-group"><label>Nombre:</label><input type="text" name="edit_nombre" id="edit_nombre" required></div>
            <div class="form-group"><label>Apellidos:</label><input type="text" name="edit_apellidos" id="edit_apellidos" required></div>
            <div class="form-group"><label>Email:</label><input type="email" name="edit_email" id="edit_email" required></div>
            <div class="form-group"><label>Teléfono:</label><input type="text" name="edit_telefono" id="edit_telefono"></div>
            <div class="form-group">
                <label>Rol:</label>
                <select name="edit_rol" id="edit_rol" required>
                    <option value="3">Cliente</option>
                    <option value="2">Empleado</option>
                    <option value="1">Administrador</option>
                </select>
            </div>
            <button type="submit" class="btn-submit" style="background:#007bff;">Actualizar Datos</button>
        </form>
    </div>
</div>

<script>
    const modalC = document.getElementById("modalCrear");
    const modalE = document.getElementById("modalEditar");

    function abrirModalCrear() { modalC.style.display = "flex"; }
    function cerrarModalCrear() { modalC.style.display = "none"; }

    function abrirModalEditar(id, nombre, apellidos, email, telefono, rol) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nombre').value = nombre;
        document.getElementById('edit_apellidos').value = apellidos;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_telefono').value = telefono;
        document.getElementById('edit_rol').value = rol;
        modalE.style.display = "flex";
    }
    function cerrarModalEditar() { modalE.style.display = "none"; }
    
    window.onclick = function(event) {
        if (event.target == modalC) cerrarModalCrear();
        if (event.target == modalE) cerrarModalEditar();
    }

    function confirmarEliminacion(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esta acción!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `../../auth/delete_user.php?id=${id}`;
            }
        });
    }

    <?php if(isset($_GET['msg'])): ?>
        const mensaje = "<?php echo htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8'); ?>";
        if(mensaje === 'deleted') Swal.fire('¡Eliminado!', 'El usuario ha sido borrado.', 'success');
        else if(mensaje === 'error_self') Swal.fire('Error', 'No puedes eliminar tu propia cuenta.', 'error');
        else if(mensaje === 'created') Swal.fire('¡Éxito!', 'El usuario se registró correctamente.', 'success');
        else if(mensaje === 'updated') Swal.fire('¡Actualizado!', 'Los datos fueron modificados.', 'success');
        else if(mensaje === 'error_db') Swal.fire('Error', 'No se pudo procesar la solicitud.', 'error');
        window.history.replaceState(null, null, window.location.pathname);
    <?php endif; ?>
</script>

</body>
</html>