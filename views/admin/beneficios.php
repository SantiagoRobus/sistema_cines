<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
exigir_rol([1], '../public/login.php');
require_once '../../config/conexion.php';
require_once '../../config/beneficios.php';

try {
    $pdo->exec(
        "UPDATE NOTIFICACION_ADMIN n SET leida_en = CURRENT_TIMESTAMP
         FROM SOLICITUD_BENEFICIO s
         WHERE n.cod_solicitud = s.cod_solicitud
           AND s.estado = 'pendiente' AND n.leida_en IS NULL"
    );
    $solicitudes = $pdo->query(
        "SELECT s.cod_solicitud, s.tipo_beneficio, s.estado, s.archivo_clave,
                s.creado_en, s.revisado_en, s.motivo_revision,
                u.nombre, u.apellidos, u.email
         FROM SOLICITUD_BENEFICIO s
         JOIN USUARIO u ON u.cod_usuario = s.cod_usuario
         ORDER BY CASE WHEN s.estado = 'pendiente' THEN 0 ELSE 1 END, s.creado_en DESC
         LIMIT 100"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('No se pudieron cargar las solicitudes de beneficio: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar las solicitudes de descuento. Verifica que la migración 003 esté aplicada.');
}
$tipos = tipos_beneficio();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisión de descuentos - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
</head>
<body>
    <header class="header">
        <div>
            <h1>Revisión de descuentos</h1>
            <p>Verifica el soporte y aprueba o rechaza cada solicitud.</p>
        </div>
        <a href="dashboard_admin.php" class="btn-back">Volver al panel</a>
    </header>
    <main class="container">
        <?php if (!$solicitudes): ?>
            <p>No hay solicitudes de beneficios todavía.</p>
        <?php endif; ?>
        <div class="showtime-grid">
            <?php foreach ($solicitudes as $solicitud): ?>
                <article class="showtime-card">
                    <span class="showtime-tag"><?php echo htmlspecialchars(ucfirst($solicitud['estado']), ENT_QUOTES, 'UTF-8'); ?></span>
                    <h2><?php echo htmlspecialchars($tipos[$solicitud['tipo_beneficio']] ?? $solicitud['tipo_beneficio'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($solicitud['nombre'] . ' ' . $solicitud['apellidos'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="showtime-meta"><?php echo htmlspecialchars($solicitud['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="showtime-meta">Solicitud #<?php echo (int) $solicitud['cod_solicitud']; ?> · <?php echo htmlspecialchars($solicitud['creado_en'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php if ($solicitud['estado'] === 'pendiente'): ?>
                        <?php if ($solicitud['archivo_clave']): ?>
                            <a class="btn-back" href="../../auth/download_benefit_proof.php?id=<?php echo (int) $solicitud['cod_solicitud']; ?>">Descargar soporte privado</a>
                        <?php endif; ?>
                        <form action="../../auth/review_benefit.php" method="POST" class="booking-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="cod_solicitud" value="<?php echo (int) $solicitud['cod_solicitud']; ?>">
                            <button type="submit" name="decision" value="aprobar" class="btn-submit">Aprobar por 1 año</button>
                        </form>
                        <form action="../../auth/review_benefit.php" method="POST" class="booking-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="cod_solicitud" value="<?php echo (int) $solicitud['cod_solicitud']; ?>">
                            <input type="hidden" name="decision" value="rechazar">
                            <div class="form-group">
                                <label for="reason-<?php echo (int) $solicitud['cod_solicitud']; ?>">Motivo del rechazo</label>
                                <textarea id="reason-<?php echo (int) $solicitud['cod_solicitud']; ?>" name="motivo" maxlength="300" required></textarea>
                            </div>
                            <button type="submit" class="btn-action btn-delete">Rechazar soporte</button>
                        </form>
                    <?php elseif ($solicitud['motivo_revision']): ?>
                        <p class="notice">Motivo: <?php echo htmlspecialchars($solicitud['motivo_revision'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </main>
    <script>
        const messages = {
            aprobado: ['Beneficio aprobado', 'La acreditación está vigente por un año.', 'success'],
            rechazado: ['Solicitud rechazada', 'El cliente verá el estado actualizado en su panel.', 'info'],
            invalid: ['Revisa los datos', 'Selecciona una decisión y escribe el motivo si rechazas.', 'warning'],
            not_pending: ['Solicitud ya revisada', 'Actualiza la página para ver su estado actual.', 'info'],
            error: ['No se pudo revisar', 'Inténtalo de nuevo y verifica la configuración de la base de datos.', 'error']
        };
        const message = new URLSearchParams(location.search).get('msg');
        if (messages[message]) {
            Swal.fire({ title: messages[message][0], text: messages[message][1], icon: messages[message][2] });
            history.replaceState(null, '', location.pathname);
        }
    </script>
</body>
</html>
