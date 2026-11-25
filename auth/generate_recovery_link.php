<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (int) ($_SESSION['rol_id'] ?? 0) !== 1) {
    http_response_code(403);
    exit('No autorizado.');
}

verificar_token_csrf();
require_once '../config/conexion.php';
$solicitudId = filter_var($_POST['cod_solicitud'] ?? null, FILTER_VALIDATE_INT);
if (!$solicitudId) {
    header('Location: ../views/admin/recuperaciones.php');
    exit();
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        "SELECT cod_usuario FROM PASSWORD_RESET_REQUEST
         WHERE cod_solicitud = :id AND estado = 'pendiente'
         FOR UPDATE"
    );
    $stmt->execute([':id' => $solicitudId]);
    $usuarioId = $stmt->fetchColumn();
    if (!$usuarioId) {
        $pdo->rollBack();
        header('Location: ../views/admin/recuperaciones.php');
        exit();
    }

    $token = bin2hex(random_bytes(32));
    $pdo->prepare(
        'DELETE FROM PASSWORD_RESET_TOKEN WHERE cod_usuario = :id AND used_at IS NULL'
    )->execute([':id' => $usuarioId]);
    $pdo->prepare(
        "INSERT INTO PASSWORD_RESET_TOKEN (token_hash, cod_usuario, expires_at)
         VALUES (:token, :id, CURRENT_TIMESTAMP + INTERVAL '30 minutes')"
    )->execute([':token' => hash('sha256', $token), ':id' => $usuarioId]);
    $pdo->prepare(
        "UPDATE PASSWORD_RESET_REQUEST SET estado = 'enlace_enviado',
            atendido_en = CURRENT_TIMESTAMP, atendido_por = :admin
         WHERE cod_solicitud = :id"
    )->execute([
        ':admin' => (int) $_SESSION['usuario_id'],
        ':id' => $solicitudId,
    ]);
    $pdo->commit();

    $baseUrl = rtrim((string) getenv('APP_BASE_URL'), '/');
    if (!filter_var($baseUrl, FILTER_VALIDATE_URL) || !in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
        $baseUrl = 'http://127.0.0.1:8000';
    }
    $link = $baseUrl . '/views/public/recuperar_password.php?token=' . urlencode($token);
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Enlace temporal - Cinex+</title>
        <link rel="stylesheet" href="../assets/css/cinex.css">
    </head>
    <body class="auth-page">
        <main class="login-container">
            <h1>Enlace de recuperación listo</h1>
            <p>Verifica la identidad del cliente y entrégale este enlace por un canal privado. Solo funciona una vez y vence en 30 minutos.</p>
            <div class="form-group">
                <label for="recoveryLink">Enlace temporal</label>
                <textarea id="recoveryLink" readonly><?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <button type="button" class="btn-login" id="copyLink">Copiar enlace</button>
            <div class="links"><a href="../views/admin/recuperaciones.php">Volver a solicitudes</a></div>
        </main>
        <script>
            document.getElementById('copyLink').addEventListener('click', async () => {
                await navigator.clipboard.writeText(document.getElementById('recoveryLink').value);
                document.getElementById('copyLink').textContent = 'Copiado';
            });
        </script>
    </body>
    </html>
    <?php
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('No se pudo generar un enlace de recuperación: ' . $e->getMessage());
    header('Location: ../views/admin/recuperaciones.php?msg=error');
}
exit();
