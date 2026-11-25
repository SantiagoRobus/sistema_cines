<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
exigir_rol([1], '../public/login.php');
require_once '../../config/conexion.php';

try {
    $pases = $pdo->query(
        'SELECT p.cod_pase, p.cod_cine, p.num_sala, p.cod_pelicula, p.fecha, p.hora, p.matinal,
                c.nombre AS cine, pe.titulo AS pelicula
         FROM PASE p
         JOIN CINE c ON c.cod_cine = p.cod_cine
         JOIN PELICULA pe ON pe.cod_pelicula = p.cod_pelicula
         ORDER BY p.fecha DESC, p.hora DESC'
    )->fetchAll();
    $cines = $pdo->query("SELECT cod_cine, nombre FROM CINE WHERE activo = '1' ORDER BY nombre")->fetchAll();
    $salas = $pdo->query(
        "SELECT s.cod_cine, s.num_sala, s.capacidad, c.nombre AS cine
         FROM SALA s JOIN CINE c ON c.cod_cine = s.cod_cine
         WHERE c.activo = '1' ORDER BY c.nombre, s.num_sala"
    )->fetchAll();
    $peliculas = $pdo->query('SELECT cod_pelicula, titulo FROM PELICULA ORDER BY titulo')->fetchAll();
} catch (PDOException $e) {
    error_log('No se pudo cargar la programación: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo cargar la programación del cine.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programación - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
</head>
<body>
    <header class="header">
        <h1>Programación de funciones</h1>
        <a href="dashboard_admin.php" class="btn-back">Volver al panel</a>
    </header>

    <main class="container">
        <div class="table-header">
            <h2>Funciones</h2>
            <button type="button" class="btn-add" id="addShowtime">+ Programar función</button>
        </div>
        <?php if (!$cines || !$salas || !$peliculas): ?>
            <p class="notice">Primero registra un cine, una sala y al menos una película.</p>
        <?php endif; ?>
        <table>
            <thead>
                <tr><th>Película</th><th>Cine</th><th>Sala</th><th>Fecha</th><th>Hora</th><th>Tipo</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($pases as $pase): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pase['pelicula'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($pase['cine'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $pase['num_sala']; ?></td>
                        <td><?php echo htmlspecialchars($pase['fecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(substr($pase['hora'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $pase['matinal'] === 'S' ? 'Matinal' : 'General'; ?></td>
                        <td>
                            <button type="button" class="btn-action btn-edit edit-showtime"
                                data-id="<?php echo (int) $pase['cod_pase']; ?>"
                                data-cine="<?php echo (int) $pase['cod_cine']; ?>"
                                data-sala="<?php echo (int) $pase['num_sala']; ?>"
                                data-pelicula="<?php echo (int) $pase['cod_pelicula']; ?>"
                                data-fecha="<?php echo htmlspecialchars($pase['fecha'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-hora="<?php echo htmlspecialchars(substr($pase['hora'], 0, 5), ENT_QUOTES, 'UTF-8'); ?>"
                                data-matinal="<?php echo htmlspecialchars($pase['matinal'] ?? 'N', ENT_QUOTES, 'UTF-8'); ?>">Editar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$pases): ?><tr><td colspan="7">Aún no hay funciones programadas.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </main>

    <div class="modal" id="showtimeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Programar función</h2>
                <button type="button" class="close" id="closeModal" aria-label="Cerrar">&times;</button>
            </div>
            <form action="../../auth/procesar_pases.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="id" id="paseId">
                <div class="form-group">
                    <label for="cine">Cine</label>
                    <select name="cod_cine" id="cine" required>
                        <option value="">Selecciona un cine</option>
                        <?php foreach ($cines as $cine): ?>
                            <option value="<?php echo (int) $cine['cod_cine']; ?>"><?php echo htmlspecialchars($cine['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sala">Sala</label>
                    <select name="num_sala" id="sala" required>
                        <option value="">Selecciona una sala</option>
                        <?php foreach ($salas as $sala): ?>
                            <option value="<?php echo (int) $sala['num_sala']; ?>" data-cine="<?php echo (int) $sala['cod_cine']; ?>">
                                <?php echo htmlspecialchars($sala['cine'] . ' · Sala ' . $sala['num_sala'] . ' (' . $sala['capacidad'] . ' puestos)', ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="pelicula">Película</label>
                    <select name="cod_pelicula" id="pelicula" required>
                        <option value="">Selecciona una película</option>
                        <?php foreach ($peliculas as $pelicula): ?>
                            <option value="<?php echo (int) $pelicula['cod_pelicula']; ?>"><?php echo htmlspecialchars($pelicula['titulo'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label for="fecha">Fecha</label><input type="date" name="fecha" id="fecha" required></div>
                <div class="form-group"><label for="hora">Hora</label><input type="time" name="hora" id="hora" required></div>
                <div class="form-group">
                    <label for="matinal">Tipo de función</label>
                    <select name="matinal" id="matinal"><option value="N">General</option><option value="S">Matinal</option></select>
                </div>
                <button type="submit" class="btn-submit">Guardar función</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('showtimeModal');
        const form = modal.querySelector('form');
        const cinema = document.getElementById('cine');
        const room = document.getElementById('sala');

        function filterRooms(selectedRoom = '') {
            const cinemaId = cinema.value;
            [...room.options].forEach(option => {
                if (!option.value) return;
                option.hidden = option.dataset.cine !== cinemaId;
            });
            if (selectedRoom) room.value = selectedRoom;
            else if (room.selectedOptions[0]?.hidden) room.value = '';
        }

        document.getElementById('addShowtime').addEventListener('click', () => {
            form.reset();
            document.getElementById('modalTitle').textContent = 'Programar función';
            document.getElementById('accion').value = 'crear';
            document.getElementById('paseId').value = '';
            filterRooms();
            modal.style.display = 'flex';
        });
        document.getElementById('closeModal').addEventListener('click', () => modal.style.display = 'none');
        cinema.addEventListener('change', () => filterRooms());

        document.querySelectorAll('.edit-showtime').forEach(button => button.addEventListener('click', () => {
            form.reset();
            document.getElementById('modalTitle').textContent = 'Editar función';
            document.getElementById('accion').value = 'editar';
            document.getElementById('paseId').value = button.dataset.id;
            cinema.value = button.dataset.cine;
            filterRooms(button.dataset.sala);
            document.getElementById('pelicula').value = button.dataset.pelicula;
            document.getElementById('fecha').value = button.dataset.fecha;
            document.getElementById('hora').value = button.dataset.hora;
            document.getElementById('matinal').value = button.dataset.matinal;
            modal.style.display = 'flex';
        }));

        const msg = new URLSearchParams(location.search).get('msg');
        const messages = {
            created: ['Función programada', 'La función quedó disponible en la cartelera.', 'success'],
            updated: ['Función actualizada', 'Se guardaron los cambios.', 'success'],
            invalid: ['Revisa los datos', 'Selecciona un cine, sala y película válidos y una fecha correcta.', 'warning'],
            error: ['No se pudo guardar', 'Verifica que la función no tenga reservas y que los datos sean correctos.', 'error']
        };
        if (messages[msg]) {
            Swal.fire({ title: messages[msg][0], text: messages[msg][1], icon: messages[msg][2], confirmButtonColor: '#f6bd4b' });
            history.replaceState(null, '', location.pathname);
        }
    </script>
</body>
</html>
