<?php
require_once '../../config/seguridad.php';
iniciar_sesion_segura();
exigir_rol([1], '../public/login.php');
require_once '../../config/conexion.php';

try {
    $solicitudes = $pdo->query(
        "SELECT r.cod_solicitud, r.creado_en, u.nombre, u.apellidos, u.email
         FROM PASSWORD_RESET_REQUEST r
         JOIN USUARIO u ON u.cod_usuario = r.cod_usuario
         WHERE r.estado = 'pendiente'
         ORDER BY r.creado_en
         LIMIT 100"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('No se pudieron cargar las solicitudes de recuperación: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar las solicitudes. Verifica que la migración 004 esté aplicada.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de cuentas - Cinex+</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../../assets/css/cinex.css">
</head>
<body>
    <header class="header">
        <h1>Recuperación de cuentas</h1>
        <a href="dashboard_admin.php" class="btn-back">Volver al panel</a>
    </header>
    <main class="container">
        <p>Si el envío de correo no está configurado, verifica la identidad del cliente antes de generar un enlace de un solo uso. Compártelo con el titular por un canal verificado.</p>
        <?php if (!$solicitudes): ?><p>No hay solicitudes pendientes.</p><?php endif; ?>
        <div class="showtime-grid">
            <?php foreach ($solicitudes as $solicitud): ?>
                <article class="showtime-card">
                    <span class="showtime-tag">Pendiente</span>
                    <h2><?php echo htmlspecialchars($solicitud['nombre'] . ' ' . $solicitud['apellidos'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($solicitud['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="showtime-meta">Solicitud #<?php echo (int) $solicitud['cod_solicitud']; ?> · <?php echo htmlspecialchars($solicitud['creado_en'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <form action="../../auth/generate_recovery_link.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="cod_solicitud" value="<?php echo (int) $solicitud['cod_solicitud']; ?>">
                        <button type="submit" class="btn-submit">Verificar identidad y generar enlace</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
