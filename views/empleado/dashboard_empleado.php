<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
exigir_rol([2], '../public/login.php');
require_once '../../config/conexion.php';
require_once '../../config/beneficios.php';

try {
    $stmt = $pdo->prepare(
        'SELECT c.cod_cine, c.nombre AS cine, c.calle, c.numero, c.telefono,
                u.nombre, u.apellidos, u.email, u.telefono AS telefono_usuario
         FROM EMPLEADO e
         JOIN CINE c ON c.cod_cine = e.cod_cine
         JOIN USUARIO u ON u.cod_usuario = e.cod_usuario
         WHERE e.cod_usuario = :empleado'
    );
    $stmt->execute([':empleado' => (int) $_SESSION['usuario_id']]);
    $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$perfil) {
        http_response_code(403);
        exit('El usuario no tiene un cine asignado.');
    }

    $stmt = $pdo->prepare(
        "SELECT p.cod_pase, p.fecha, p.hora, p.num_sala, pe.titulo,
                COALESCE((SELECT MAX(s.capacidad) FROM SALA s
                          WHERE s.cod_cine = p.cod_cine AND s.num_sala = p.num_sala), 0) AS capacidad,
                COALESCE((SELECT SUM(r.num_entradas) FROM RESERVA r
                          WHERE r.cod_pase = p.cod_pase
                            AND (r.estado = 'Confirmada'
                              OR (r.estado = 'Pendiente'
                                  AND r.fecha_reserva > CURRENT_TIMESTAMP - INTERVAL '20 minutes'))), 0) AS ocupados
         FROM PASE p JOIN PELICULA pe ON pe.cod_pelicula = p.cod_pelicula
         WHERE p.cod_cine = :cine AND (p.fecha + p.hora) > CURRENT_TIMESTAMP
         ORDER BY p.fecha, p.hora"
    );
    $stmt->execute([':cine' => $perfil['cod_cine']]);
    $pases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT r.cod_reserva, r.num_entradas, r.precio_total, r.estado, r.fecha_reserva,
                pe.titulo, p.fecha, p.hora, u.nombre, u.apellidos, u.email,
                pago.estado AS estado_pago
         FROM RESERVA r
         JOIN PASE p ON p.cod_pase = r.cod_pase
         JOIN PELICULA pe ON pe.cod_pelicula = p.cod_pelicula
         JOIN USUARIO u ON u.cod_usuario = r.cod_cliente
         LEFT JOIN PAGO_RESERVA pago ON pago.cod_reserva = r.cod_reserva
         WHERE p.cod_cine = :cine AND (p.fecha + p.hora) > CURRENT_TIMESTAMP
         ORDER BY p.fecha, p.hora, r.fecha_reserva DESC"
    );
    $stmt->execute([':cine' => $perfil['cod_cine']]);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        'SELECT pr.cod_tarifa, pr.importe, t.descripcion, t.beneficio_requerido
         FROM PRECIO pr JOIN TARIFA t ON t.cod_tarifa = pr.cod_tarifa
         WHERE pr.cod_cine = :cine ORDER BY t.descripcion'
    );
    $stmt->execute([':cine' => $perfil['cod_cine']]);
    $tarifas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('No se pudo cargar el panel del empleado: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo cargar la información del cine. Verifica que las migraciones 002 y 003 estén aplicadas.');
}
$hayFuncionesConCupo = false;
foreach ($pases as $pase) {
    if ((int) $pase['capacidad'] > (int) $pase['ocupados']) {
        $hayFuncionesConCupo = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de empleado - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
</head>
<body>
    <header class="header">
        <div>
            <h1>Hola, <?php echo htmlspecialchars($perfil['nombre'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars($perfil['cine'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($perfil['calle'] . ' ' . $perfil['numero'], ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($perfil['telefono']): ?> · <?php echo htmlspecialchars($perfil['telefono'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
            </p>
        </div>
        <div class="employee-header-actions">
            <button type="button" class="btn-back" id="toggleProfile">Mi perfil</button>
            <form action="../../auth/logout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn-logout">Cerrar sesión</button>
            </form>
        </div>
    </header>

    <main class="container">
        <div class="table-header">
            <div>
                <h2>Funciones y ocupación</h2>
                <p>La ocupación se calcula por entradas confirmadas y pagos pendientes; este sistema aún no almacena asientos individuales.</p>
            </div>
            <button type="button" class="btn-add" id="toggleCounter">+ Venta en taquilla</button>
        </div>
        <div class="showtime-grid">
            <?php foreach ($pases as $pase): ?>
                <?php
                    $capacidad = (int) $pase['capacidad'];
                    $ocupados = (int) $pase['ocupados'];
                    $porcentaje = $capacidad > 0 ? min(100, (int) round($ocupados * 100 / $capacidad)) : 0;
                ?>
                <article class="showtime-card">
                    <span class="showtime-tag">Sala <?php echo (int) $pase['num_sala']; ?></span>
                    <h3><?php echo htmlspecialchars($pase['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="showtime-meta"><?php echo htmlspecialchars($pase['fecha'] . ' · ' . substr($pase['hora'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="occupancy-bar" role="progressbar" aria-valuenow="<?php echo $porcentaje; ?>" aria-valuemin="0" aria-valuemax="100">
                        <span class="occupancy-fill" style="width: <?php echo $porcentaje; ?>%"></span>
                    </div>
                    <p class="showtime-availability"><?php echo $ocupados; ?> / <?php echo $capacidad; ?> entradas ocupadas</p>
                </article>
            <?php endforeach; ?>
            <?php if (!$pases): ?><p>No hay funciones futuras en esta sede.</p><?php endif; ?>
        </div>
    </main>

    <section class="container" id="counterPanel" hidden>
        <div class="table-header"><div><h2>Registrar venta en taquilla</h2><p>El cliente debe tener una cuenta. La venta confirmada queda asociada a tu usuario.</p></div></div>
        <form action="../../auth/employee_create_reservation.php" method="POST" class="booking-form employee-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group">
                <label for="counterShowtime">Función</label>
                <select id="counterShowtime" name="cod_pase" required>
                    <option value="">Selecciona una función disponible</option>
                    <?php foreach ($pases as $pase): ?>
                        <?php if ((int) $pase['capacidad'] - (int) $pase['ocupados'] > 0): ?>
                            <option value="<?php echo (int) $pase['cod_pase']; ?>"><?php echo htmlspecialchars($pase['titulo'] . ' · ' . $pase['fecha'] . ' ' . substr($pase['hora'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label for="customerEmail">Correo del cliente</label><input id="customerEmail" type="email" name="email_cliente" required></div>
            <div class="form-group">
                <label for="counterTariff">Tarifa</label>
                <select id="counterTariff" name="cod_tarifa" required>
                    <option value="">Selecciona una tarifa</option>
                    <?php foreach ($tarifas as $tarifa): ?>
                        <option value="<?php echo (int) $tarifa['cod_tarifa']; ?>">
                            <?php echo htmlspecialchars($tarifa['descripcion'] . ' · $' . number_format((float) $tarifa['importe'], 0, ',', '.') . ' COP' . ($tarifa['beneficio_requerido'] ? ' · requiere beneficio aprobado' : ''), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label for="counterQuantity">Entradas (máx. 10)</label><input id="counterQuantity" type="number" name="num_entradas" min="1" max="10" value="1" required></div>
            <button type="submit" class="btn-submit" <?php echo !$hayFuncionesConCupo || !$tarifas ? 'disabled' : ''; ?>>Confirmar venta</button>
        </form>
    </section>

    <section class="container">
        <div class="table-header"><div><h2>Reservas de funciones futuras</h2><p>Las compras aprobadas en línea no pueden cancelarse desde taquilla.</p></div></div>
        <table>
            <thead><tr><th>Reserva</th><th>Cliente</th><th>Película / función</th><th>Entradas</th><th>Total</th><th>Estado</th><th>Pago</th><th>Acción</th></tr></thead>
            <tbody>
                <?php foreach ($reservas as $reserva): ?>
                    <tr>
                        <td>#<?php echo (int) $reserva['cod_reserva']; ?></td>
                        <td><?php echo htmlspecialchars($reserva['nombre'] . ' ' . $reserva['apellidos'] . ' · ' . $reserva['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($reserva['titulo'] . ' · ' . $reserva['fecha'] . ' ' . substr($reserva['hora'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $reserva['num_entradas']; ?></td>
                        <td>$<?php echo number_format((float) $reserva['precio_total'], 0, ',', '.'); ?> COP</td>
                        <td><?php echo htmlspecialchars($reserva['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($reserva['estado_pago'] ?? 'Taquilla', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ($reserva['estado'] === 'Confirmada' && $reserva['estado_pago'] !== 'approved'): ?>
                                <form action="../../auth/employee_cancel_reservation.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="cod_reserva" value="<?php echo (int) $reserva['cod_reserva']; ?>">
                                    <button type="submit" class="btn-action btn-delete">Cancelar venta</button>
                                </form>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$reservas): ?><tr><td colspan="8">Todavía no hay reservas para estas funciones.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="container" id="profilePanel" hidden>
        <div class="table-header"><h2>Actualizar mis datos</h2></div>
        <form action="../../auth/update_profile.php" method="POST" class="booking-form employee-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group"><label for="profileName">Nombre</label><input id="profileName" name="nombre" value="<?php echo htmlspecialchars($perfil['nombre'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="form-group"><label for="profileSurname">Apellidos</label><input id="profileSurname" name="apellidos" value="<?php echo htmlspecialchars($perfil['apellidos'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="form-group"><label for="profileEmail">Correo</label><input id="profileEmail" type="email" name="email" value="<?php echo htmlspecialchars($perfil['email'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="form-group"><label for="profilePhone">Teléfono</label><input id="profilePhone" name="telefono" value="<?php echo htmlspecialchars($perfil['telefono_usuario'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="form-group"><label for="currentPassword">Contraseña actual para cambiarla</label><input id="currentPassword" type="password" name="password_actual" autocomplete="current-password"></div>
            <div class="form-group"><label for="newPassword">Nueva contraseña (opcional)</label><input id="newPassword" type="password" name="password_nueva" minlength="8" autocomplete="new-password"></div>
            <button type="submit" class="btn-submit">Guardar perfil</button>
        </form>
    </section>

    <section class="container">
        <div class="table-header">
            <div><h2>Validar beneficio presencial</h2><p>Verifica el documento original antes de aprobar el descuento. No se guarda copia.</p></div>
        </div>
        <form action="../../auth/validate_benefit_in_person.php" method="POST" class="booking-form employee-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group"><label for="benefitEmail">Correo del cliente</label><input id="benefitEmail" type="email" name="email" required></div>
            <div class="form-group">
                <label for="benefitType">Documento revisado</label>
                <select id="benefitType" name="tipo_beneficio" required>
                    <?php foreach (tipos_beneficio() as $tipo => $etiqueta): ?>
                        <option value="<?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-submit">Validar por 1 año</button>
        </form>
    </section>
    <script>
        document.getElementById('toggleProfile').addEventListener('click', () => {
            const panel = document.getElementById('profilePanel');
            panel.hidden = !panel.hidden;
            if (!panel.hidden) panel.scrollIntoView({ behavior: 'smooth' });
        });
        document.getElementById('toggleCounter').addEventListener('click', () => {
            const panel = document.getElementById('counterPanel');
            panel.hidden = !panel.hidden;
            if (!panel.hidden) panel.scrollIntoView({ behavior: 'smooth' });
        });
        const messages = {
            reservation_created: ['Venta confirmada', 'La reserva quedó asociada a esta sede.', 'success'],
            reservation_cancelled: ['Venta cancelada', 'Se liberó la capacidad de la función.', 'success'],
            reservation_locked: ['No se puede cancelar', 'Solo puedes cancelar reservas confirmadas de taquilla; pagos aprobados deben ser revisados por administración.', 'warning'],
            invalid_reservation: ['Datos inválidos', 'Completa correo, función, tarifa y cantidad correctamente.', 'warning'],
            showtime_unavailable: ['Función no disponible', 'La función no existe, inició o pertenece a otra sede.', 'warning'],
            showtime_full: ['Función agotada', 'No hay suficientes entradas disponibles.', 'warning'],
            customer_not_found: ['Cliente no encontrado', 'Verifica el correo y que tenga una cuenta activa de cliente.', 'warning'],
            benefit_not_approved: ['Beneficio sin validar', 'El cliente debe tener un beneficio vigente aprobado para esta tarifa.', 'warning'],
            price_missing: ['Precio no configurado', 'No existe ese precio para tu cine.', 'warning'],
            benefit_validated: ['Beneficio aprobado', 'Se verificó presencialmente y tendrá vigencia de un año.', 'success'],
            invalid_benefit: ['Datos inválidos', 'Revisa el correo y el tipo de beneficio.', 'warning'],
            profile_updated: ['Perfil actualizado', 'Tus datos se guardaron correctamente.', 'success'],
            profile_password_updated: ['Contraseña actualizada', 'Ya puedes usar tu nueva contraseña.', 'success'],
            profile_invalid: ['Revisa tus datos', 'La contraseña actual no coincide o la nueva contraseña no cumple los requisitos.', 'warning'],
            profile_error: ['No se pudo guardar', 'Verifica que el correo no esté registrado por otra cuenta.', 'error'],
            error: ['No se pudo procesar', 'Inténtalo de nuevo y avisa a administración si continúa.', 'error']
        };
        const message = new URLSearchParams(location.search).get('msg');
        if (messages[message]) {
            Swal.fire({ title: messages[message][0], text: messages[message][1], icon: messages[message][2] });
            history.replaceState(null, '', location.pathname);
        }
        const profile = new URLSearchParams(location.search).get('profile');
        if (profile) {
            const entry = messages['profile_' + profile];
            if (entry) Swal.fire({ title: entry[0], text: entry[1], icon: entry[2] });
            document.getElementById('profilePanel').hidden = false;
        }
    </script>
</body>
</html>
