<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
exigir_rol([3], '../public/login.php');
require_once '../../config/conexion.php';
require_once '../../config/beneficios.php';

try {
    $pdo->prepare(
        "UPDATE PAGO_RESERVA pr SET estado = 'expired', actualizado_en = CURRENT_TIMESTAMP
         FROM RESERVA r
         WHERE pr.cod_reserva = r.cod_reserva AND r.cod_cliente = :cliente
           AND r.estado = 'Pendiente' AND r.fecha_reserva <= CURRENT_TIMESTAMP - INTERVAL '20 minutes'
           AND pr.estado = 'pending'"
    )->execute([':cliente' => (int) $_SESSION['usuario_id']]);
    $pdo->prepare(
        "UPDATE RESERVA SET estado = 'Cancelada'
         WHERE cod_cliente = :cliente AND estado = 'Pendiente'
           AND fecha_reserva <= CURRENT_TIMESTAMP - INTERVAL '20 minutes'"
    )->execute([':cliente' => (int) $_SESSION['usuario_id']]);

    $cines = $pdo->query(
        "SELECT cod_cine, nombre, calle, numero, telefono
         FROM CINE WHERE activo = '1' ORDER BY nombre"
    )->fetchAll(PDO::FETCH_ASSOC);
    $cineId = filter_input(INPUT_GET, 'cine', FILTER_VALIDATE_INT);
    $cineId = $cineId && $cineId > 0 ? $cineId : null;
    $cineSeleccionado = null;
    foreach ($cines as $cine) {
        if ((int) $cine['cod_cine'] === $cineId) {
            $cineSeleccionado = $cine;
            break;
        }
    }

    $sqlCartelera =
        "SELECT p.cod_pase, p.cod_cine, p.num_sala, p.fecha, p.hora, p.matinal,
                c.nombre AS cine, c.calle, c.numero, pe.titulo, pe.sinopsis,
                COALESCE((SELECT MAX(s.capacidad) FROM SALA s
                          WHERE s.cod_cine = p.cod_cine AND s.num_sala = p.num_sala), 0) AS capacidad,
                COALESCE((SELECT SUM(r.num_entradas) FROM RESERVA r
                          WHERE r.cod_pase = p.cod_pase
                            AND (r.estado = 'Confirmada'
                              OR (r.estado = 'Pendiente'
                                  AND r.fecha_reserva > CURRENT_TIMESTAMP - INTERVAL '20 minutes'))), 0) AS ocupados
         FROM PASE p
         JOIN CINE c ON c.cod_cine = p.cod_cine
         JOIN PELICULA pe ON pe.cod_pelicula = p.cod_pelicula
         WHERE (p.fecha + p.hora) > CURRENT_TIMESTAMP AND c.activo = '1'";
    $parametrosCartelera = [];
    if ($cineId) {
        $sqlCartelera .= ' AND p.cod_cine = :cine';
        $parametrosCartelera[':cine'] = $cineId;
    }
    $sqlCartelera .= ' ORDER BY p.fecha, p.hora';
    $stmt = $pdo->prepare($sqlCartelera);
    $stmt->execute($parametrosCartelera);
    $pases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT tipo_beneficio, vigente_hasta, metodo_validacion
         FROM CLIENTE_BENEFICIO
         WHERE cod_usuario = :cliente AND estado = 'aprobado' AND vigente_hasta >= CURRENT_DATE"
    );
    $stmt->execute([':cliente' => (int) $_SESSION['usuario_id']]);
    $beneficioActual = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $precios = $pdo->query(
        'SELECT pr.cod_cine, pr.cod_tarifa, pr.importe, t.descripcion, t.beneficio_requerido
         FROM PRECIO pr JOIN TARIFA t ON t.cod_tarifa = pr.cod_tarifa
         ORDER BY t.descripcion'
    )->fetchAll();
    $porCine = [];
    foreach ($precios as $precio) {
        if (
            $precio['beneficio_requerido'] === null ||
            ($beneficioActual && $precio['beneficio_requerido'] === $beneficioActual['tipo_beneficio'])
        ) {
            $porCine[$precio['cod_cine']][] = $precio;
        }
    }

    $stmt = $pdo->prepare(
        'SELECT tipo_beneficio, estado, motivo_revision, creado_en
         FROM SOLICITUD_BENEFICIO WHERE cod_usuario = :cliente
         ORDER BY creado_en DESC LIMIT 1'
    );
    $stmt->execute([':cliente' => (int) $_SESSION['usuario_id']]);
    $solicitudBeneficio = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $stmt = $pdo->prepare(
        "SELECT r.cod_reserva, r.num_entradas, r.precio_total, r.estado, r.fecha_reserva,
                p.fecha, p.hora, pe.titulo, c.nombre AS cine, t.descripcion AS tarifa,
                pr.estado AS estado_pago, pr.checkout_url,
                CASE WHEN r.fecha_reserva > CURRENT_TIMESTAMP - INTERVAL '20 minutes' THEN 1 ELSE 0 END AS ventana_pago
         FROM RESERVA r
         JOIN PASE p ON p.cod_pase = r.cod_pase
         JOIN PELICULA pe ON pe.cod_pelicula = p.cod_pelicula
         JOIN CINE c ON c.cod_cine = p.cod_cine
         JOIN TARIFA t ON t.cod_tarifa = r.cod_tarifa
         LEFT JOIN PAGO_RESERVA pr ON pr.cod_reserva = r.cod_reserva
         WHERE r.cod_cliente = :cliente
         ORDER BY r.fecha_reserva DESC"
    );
    $stmt->execute([':cliente' => (int) $_SESSION['usuario_id']]);
    $reservas = $stmt->fetchAll();
    $stmt = $pdo->prepare(
        'SELECT nombre, apellidos, email, telefono FROM USUARIO WHERE cod_usuario = :id'
    );
    $stmt->execute([':id' => (int) $_SESSION['usuario_id']]);
    $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('No se pudo cargar la cartelera del cliente: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo cargar la cartelera. Inténtalo más tarde.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartelera y reservas - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
</head>
<body>
    <header class="header">
        <h1>Hola, <?php echo htmlspecialchars($_SESSION['nombre'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <form action="../../auth/logout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
    </header>

    <main class="container">
        <div class="table-header">
            <div>
                <h2>Próximas funciones</h2>
                <p>Elige tu sede, explora horarios y reserva en línea.</p>
            </div>
            <button type="button" class="btn-back" id="toggleProfile">Mi perfil</button>
        </div>
        <form method="GET" class="booking-form cinema-filter">
            <div class="form-group">
                <label for="cinemaFilter">¿En qué cine quieres asistir?</label>
                <select id="cinemaFilter" name="cine">
                    <option value="">Todos los cines</option>
                    <?php foreach ($cines as $cine): ?>
                        <option value="<?php echo (int) $cine['cod_cine']; ?>" <?php echo $cineId === (int) $cine['cod_cine'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cine['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="movieSearch">Buscar película</label>
                <input type="search" id="movieSearch" placeholder="Escribe el título">
            </div>
            <div class="form-group">
                <label for="dateFilter">Filtrar por fecha</label>
                <input type="date" id="dateFilter">
            </div>
        </form>
        <?php if ($cineSeleccionado): ?>
            <aside class="notice cinema-info">
                <strong><?php echo htmlspecialchars($cineSeleccionado['nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <span><?php echo htmlspecialchars($cineSeleccionado['calle'] . ' ' . $cineSeleccionado['numero'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if ($cineSeleccionado['telefono']): ?>
                    <a href="tel:<?php echo htmlspecialchars($cineSeleccionado['telefono'], ENT_QUOTES, 'UTF-8'); ?>">
                        Llamar: <?php echo htmlspecialchars($cineSeleccionado['telefono'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endif; ?>
            </aside>
        <?php endif; ?>
        <div class="showtime-grid">
            <?php foreach ($pases as $pase): ?>
                <?php
                    $capacidad = (int) $pase['capacidad'];
                    $disponibles = max(0, $capacidad - (int) $pase['ocupados']);
                    $tarifasCine = $porCine[$pase['cod_cine']] ?? [];
                ?>
                <article class="showtime-card movie-card"
                    data-title="<?php echo htmlspecialchars($pase['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-date="<?php echo htmlspecialchars($pase['fecha'], ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="showtime-tag"><?php echo $pase['matinal'] === 'S' ? 'Función matinal' : 'En cartelera'; ?></span>
                    <h3><?php echo htmlspecialchars($pase['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="showtime-meta"><?php echo htmlspecialchars($pase['cine'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="showtime-meta"><?php echo 'Sala ' . (int) $pase['num_sala'] . ' · ' . htmlspecialchars($pase['fecha'], ENT_QUOTES, 'UTF-8') . ' · ' . htmlspecialchars(substr($pase['hora'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($pase['sinopsis']): ?>
                        <p><?php echo htmlspecialchars($pase['sinopsis'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <p class="showtime-availability"><?php echo $disponibles; ?> entradas disponibles</p>
                    <?php if ($tarifasCine && $disponibles > 0): ?>
                        <form action="../../auth/reservar.php" method="POST" class="booking-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="cod_pase" value="<?php echo (int) $pase['cod_pase']; ?>">
                            <div class="form-group">
                                <label for="tarifa-<?php echo (int) $pase['cod_pase']; ?>">Tarifa</label>
                                <select id="tarifa-<?php echo (int) $pase['cod_pase']; ?>" name="cod_tarifa" required>
                                    <?php foreach ($tarifasCine as $tarifa): ?>
                                        <option value="<?php echo (int) $tarifa['cod_tarifa']; ?>">
                                            <?php echo htmlspecialchars($tarifa['descripcion'], ENT_QUOTES, 'UTF-8'); ?><?php if ($tarifa['beneficio_requerido']): ?> · Descuento validado<?php endif; ?> ·
                                            $<?php echo number_format((float) $tarifa['importe'], 0, ',', '.'); ?> COP
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="cantidad-<?php echo (int) $pase['cod_pase']; ?>">Entradas (máx. 10)</label>
                                <input id="cantidad-<?php echo (int) $pase['cod_pase']; ?>" type="number" name="num_entradas" min="1" max="<?php echo min(10, $disponibles); ?>" value="1" required>
                            </div>
                            <button type="submit" class="btn-submit">Reservar y pagar</button>
                        </form>
                    <?php elseif (!$tarifasCine): ?>
                        <p class="notice">Aún no hay tarifas configuradas para este cine.</p>
                    <?php else: ?>
                        <p class="notice">Función agotada.</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if (!$pases): ?><p>No hay funciones futuras para el cine seleccionado. Prueba otra sede.</p><?php endif; ?>
        </div>
        <p id="noMovieResults" class="notice" hidden>No hay películas que coincidan con esos filtros.</p>
    </main>

    <section class="container" id="profilePanel" hidden>
        <div class="table-header"><h2>Mi perfil</h2></div>
        <form action="../../auth/update_profile.php" method="POST" class="booking-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group"><label for="profileName">Nombre</label><input id="profileName" name="nombre" value="<?php echo htmlspecialchars($perfil['nombre'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="form-group"><label for="profileSurname">Apellidos</label><input id="profileSurname" name="apellidos" value="<?php echo htmlspecialchars($perfil['apellidos'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="form-group"><label for="profileEmail">Correo</label><input id="profileEmail" type="email" name="email" value="<?php echo htmlspecialchars($perfil['email'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="form-group"><label for="profilePhone">Teléfono</label><input id="profilePhone" name="telefono" value="<?php echo htmlspecialchars($perfil['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="form-group"><label for="currentPassword">Contraseña actual para cambiarla</label><input id="currentPassword" type="password" name="password_actual" autocomplete="current-password"></div>
            <div class="form-group"><label for="newPassword">Nueva contraseña (opcional)</label><input id="newPassword" type="password" name="password_nueva" minlength="8" autocomplete="new-password"><small>Si la cambias, debe cumplir la política de seguridad.</small></div>
            <button type="submit" class="btn-submit">Guardar perfil</button>
        </form>
    </section>

    <section class="container">
        <div class="table-header">
            <div>
                <h2>Mis descuentos</h2>
                <p>Envía el soporte para obtener una tarifa especial. Un administrador revisará los documentos; también puedes validarte presencialmente con un empleado.</p>
            </div>
        </div>
        <?php if ($beneficioActual): ?>
            <p class="notice">Beneficio aprobado: <?php echo htmlspecialchars(tipos_beneficio()[$beneficioActual['tipo_beneficio']] ?? $beneficioActual['tipo_beneficio'], ENT_QUOTES, 'UTF-8'); ?> hasta <?php echo htmlspecialchars($beneficioActual['vigente_hasta'], ENT_QUOTES, 'UTF-8'); ?>. Validación <?php echo $beneficioActual['metodo_validacion'] === 'presencial' ? 'presencial' : 'documental'; ?>.</p>
        <?php elseif ($solicitudBeneficio && $solicitudBeneficio['estado'] === 'pendiente'): ?>
            <p class="notice">Tu solicitud de <?php echo htmlspecialchars(tipos_beneficio()[$solicitudBeneficio['tipo_beneficio']] ?? $solicitudBeneficio['tipo_beneficio'], ENT_QUOTES, 'UTF-8'); ?> está pendiente de revisión.</p>
        <?php elseif ($solicitudBeneficio && $solicitudBeneficio['estado'] === 'rechazado'): ?>
            <p class="notice">La última solicitud fue rechazada. Motivo: <?php echo htmlspecialchars($solicitudBeneficio['motivo_revision'] ?? 'Consulta con el cine.', ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <?php if (!$beneficioActual && (!$solicitudBeneficio || $solicitudBeneficio['estado'] !== 'pendiente')): ?>
            <form action="../../auth/request_benefit.php" method="POST" enctype="multipart/form-data" class="booking-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="benefitType">Tipo de beneficio</label>
                    <select id="benefitType" name="tipo_beneficio" required>
                        <?php foreach (tipos_beneficio() as $tipo => $etiqueta): ?>
                            <option value="<?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="benefitProof">Soporte (PDF, JPG o PNG; máximo 5 MB)</label>
                    <input id="benefitProof" type="file" name="soporte" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                    <small>El archivo solo lo verá el equipo administrador y se elimina cuando termine la revisión.</small>
                </div>
                <button type="submit" class="btn-submit">Solicitar validación de descuento</button>
            </form>
        <?php endif; ?>
    </section>

    <section class="container">
        <div class="table-header"><h2>Mis reservas</h2></div>
        <table>
            <thead><tr><th>Película</th><th>Función</th><th>Entradas</th><th>Total</th><th>Estado</th><th>Pago</th></tr></thead>
            <tbody>
                <?php foreach ($reservas as $reserva): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reserva['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($reserva['cine'] . ' · ' . $reserva['fecha'] . ' · ' . substr($reserva['hora'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $reserva['num_entradas']; ?> · <?php echo htmlspecialchars($reserva['tarifa'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>$<?php echo number_format((float) $reserva['precio_total'], 0, ',', '.'); ?> COP</td>
                        <td><?php echo htmlspecialchars($reserva['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <?php if ($reserva['estado'] === 'Pendiente' && $reserva['estado_pago'] === 'pending' && (int) $reserva['ventana_pago'] === 1 && $reserva['checkout_url']): ?>
                                <a class="btn-back" href="<?php echo htmlspecialchars($reserva['checkout_url'], ENT_QUOTES, 'UTF-8'); ?>">Continuar pago</a>
                            <?php elseif ($reserva['estado_pago'] === 'approved_review'): ?>
                                Requiere revisión de pago
                            <?php else: ?>
                                <?php echo htmlspecialchars($reserva['estado_pago'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$reservas): ?><tr><td colspan="6">Todavía no tienes reservas.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </section>

    <script>
        const messages = {
            invalid: ['Datos inválidos', 'Selecciona una función, tarifa y cantidad válida.', 'warning'],
            unavailable: ['Función no disponible', 'La función no existe o ya comenzó.', 'warning'],
            full: ['No hay suficientes entradas', 'La disponibilidad cambió. Selecciona una cantidad menor.', 'warning'],
            price_missing: ['Tarifa no disponible', 'El cine todavía no tiene ese precio configurado.', 'warning'],
            payment_config: ['Pago no configurado', 'El administrador debe configurar las credenciales de prueba de Mercado Pago y APP_BASE_URL antes de aceptar pagos.', 'error'],
            payment_error: ['No se pudo iniciar el pago', 'La reserva fue liberada. Inténtalo de nuevo más tarde.', 'error'],
            paid: ['¡Pago aprobado!', 'Tu reserva está confirmada.', 'success'],
            payment_pending: ['Pago pendiente', 'Mercado Pago todavía no confirma el pago.', 'info'],
            payment_failed: ['Pago no aprobado', 'La reserva fue liberada. Puedes intentarlo de nuevo.', 'error'],
            payment_review: ['Pago en revisión', 'El pago se recibió cuando la reserva había vencido. Contacta al cine para gestionar el reembolso.', 'warning'],
            benefit_required: ['Descuento no validado', 'La tarifa requiere un beneficio aprobado y vigente.', 'warning']
        };
        const message = new URLSearchParams(location.search).get('msg');
        if (messages[message]) {
            Swal.fire({ title: messages[message][0], text: messages[message][1], icon: messages[message][2], confirmButtonColor: '#f6bd4b' });
            history.replaceState(null, '', location.pathname);
        }
        const benefitMessages = {
            requested: ['Solicitud enviada', 'El administrador recibió una notificación y revisará tu soporte.', 'success'],
            pending: ['Solicitud en revisión', 'Ya tienes un documento pendiente de aprobación.', 'info'],
            already: ['Beneficio vigente', 'Tu perfil ya tiene un descuento aprobado.', 'info'],
            invalid: ['Solicitud inválida', 'Selecciona un tipo de beneficio válido.', 'warning'],
            upload_error: ['No se pudo cargar el archivo', 'Usa un PDF, JPG o PNG de máximo 5 MB.', 'warning'],
            error: ['No se pudo enviar la solicitud', 'Inténtalo nuevamente más tarde.', 'error']
        };
        const benefitMessage = benefitMessages[new URLSearchParams(location.search).get('benefit')];
        if (benefitMessage) {
            Swal.fire({ title: benefitMessage[0], text: benefitMessage[1], icon: benefitMessage[2] });
            history.replaceState(null, '', location.pathname);
        }
        document.getElementById('cinemaFilter').addEventListener('change', event => {
            const url = new URL(location.href);
            if (event.target.value) url.searchParams.set('cine', event.target.value);
            else url.searchParams.delete('cine');
            location.href = url;
        });
        const cards = [...document.querySelectorAll('.movie-card')];
        const updateMovieFilters = () => {
            const title = document.getElementById('movieSearch').value.toLocaleLowerCase('es');
            const date = document.getElementById('dateFilter').value;
            let visible = 0;
            cards.forEach(card => {
                const show = card.dataset.title.toLocaleLowerCase('es').includes(title) && (!date || card.dataset.date === date);
                card.hidden = !show;
                if (show) visible++;
            });
            document.getElementById('noMovieResults').hidden = !cards.length || visible > 0;
        };
        document.getElementById('movieSearch').addEventListener('input', updateMovieFilters);
        document.getElementById('dateFilter').addEventListener('change', updateMovieFilters);
        document.getElementById('toggleProfile').addEventListener('click', () => {
            const panel = document.getElementById('profilePanel');
            panel.hidden = !panel.hidden;
            if (!panel.hidden) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        const profileMessage = new URLSearchParams(location.search).get('profile');
        if (profileMessage) {
            const profileMessages = {
                updated: ['Perfil actualizado', 'Tus datos se guardaron correctamente.', 'success'],
                password_updated: ['Contraseña actualizada', 'Ya puedes usar tu nueva contraseña.', 'success'],
                invalid: ['Revisa los datos', 'La contraseña actual no coincide o la nueva contraseña no cumple los requisitos.', 'warning'],
                error: ['No se pudo guardar', 'Verifica que el correo no esté registrado por otra cuenta.', 'error']
            };
            const entry = profileMessages[profileMessage];
            if (entry) Swal.fire({ title: entry[0], text: entry[1], icon: entry[2] });
            document.getElementById('profilePanel').hidden = false;
        }
    </script>
</body>
</html>
