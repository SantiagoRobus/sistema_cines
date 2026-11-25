<?php
require_once '../config/seguridad.php';
iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/public/recuperar_password.php');
    exit();
}

verificar_token_csrf();
require_once '../config/conexion.php';

if (isset($_POST['token'])) {
    $token = $_POST['token'];
    $password = post_texto('password', false);
    $confirmation = post_texto('password_confirmation', false);

    if (
        !is_string($token) ||
        !preg_match('/\A[a-f0-9]{64}\z/', $token) ||
        !is_string($password) ||
        !password_cumple_politica($password) ||
        $password !== $confirmation
    ) {
        header('Location: ../views/public/recuperar_password.php?msg=invalid');
        exit();
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            "SELECT u.cod_usuario, u.nombre, u.apellidos, u.email
             FROM PASSWORD_RESET_TOKEN t
             JOIN USUARIO u ON u.cod_usuario = t.cod_usuario
             WHERE t.token_hash = :token
               AND t.used_at IS NULL
               AND t.expires_at > CURRENT_TIMESTAMP
             FOR UPDATE"
        );
        $stmt->execute([':token' => hash('sha256', $token)]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            !$usuario ||
            !password_cumple_politica($password, [$usuario['nombre'], $usuario['apellidos'], $usuario['email']])
        ) {
            $pdo->rollBack();
            header('Location: ../views/public/recuperar_password.php?msg=invalid');
            exit();
        }
        $usuarioId = $usuario['cod_usuario'];

        $stmt = $pdo->prepare('UPDATE USUARIO SET password_hash = :hash WHERE cod_usuario = :id AND activo = \'1\'');
        $stmt->execute([
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ':id' => $usuarioId,
        ]);

        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            header('Location: ../views/public/recuperar_password.php?msg=invalid');
            exit();
        }

        $stmt = $pdo->prepare(
            'UPDATE PASSWORD_RESET_TOKEN SET used_at = CURRENT_TIMESTAMP
             WHERE cod_usuario = :id AND used_at IS NULL'
        );
        $stmt->execute([':id' => $usuarioId]);
        $pdo->prepare(
            "UPDATE PASSWORD_RESET_REQUEST SET estado = 'resuelto', atendido_en = CURRENT_TIMESTAMP
             WHERE cod_usuario = :id AND estado IN ('pendiente', 'enlace_enviado')"
        )->execute([':id' => $usuarioId]);
        $pdo->commit();

        header('Location: ../views/public/recuperar_password.php?msg=success');
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('No se pudo completar el restablecimiento de contraseña: ' . $e->getMessage());
        header('Location: ../views/public/recuperar_password.php?msg=error');
        exit();
    }
}

$email = filter_var(post_texto('email'), FILTER_VALIDATE_EMAIL);
$baseUrl = rtrim((string) getenv('APP_BASE_URL'), '/');
$mailFrom = (string) getenv('MAIL_FROM');

if ($email !== false) {
    try {
        $stmt = $pdo->prepare("SELECT cod_usuario FROM USUARIO WHERE email = :email AND activo = '1'");
        $stmt->execute([':email' => $email]);
        $usuarioId = $stmt->fetchColumn();

        if ($usuarioId) {
            $correoConfigurado = filter_var($mailFrom, FILTER_VALIDATE_EMAIL)
                && filter_var($baseUrl, FILTER_VALIDATE_URL)
                && in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true);
            $correoEnviado = false;

            if ($correoConfigurado) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $pdo->beginTransaction();
                $pdo->prepare(
                    'DELETE FROM PASSWORD_RESET_TOKEN WHERE cod_usuario = :id AND used_at IS NULL'
                )->execute([':id' => $usuarioId]);
                $pdo->prepare(
                    "INSERT INTO PASSWORD_RESET_TOKEN (token_hash, cod_usuario, expires_at)
                     VALUES (:token, :id, CURRENT_TIMESTAMP + INTERVAL '30 minutes')"
                )->execute([':token' => $tokenHash, ':id' => $usuarioId]);
                $pdo->commit();

                $link = $baseUrl . '/views/public/recuperar_password.php?token=' . urlencode($token);
                $body = "Recibimos una solicitud para cambiar la contraseña de tu cuenta Cinex+.\n\n"
                    . "Usa este enlace en los próximos 30 minutos:\n$link\n\n"
                    . "Si no solicitaste este cambio, ignora este correo.";
                $headers = "From: $mailFrom\r\nContent-Type: text/plain; charset=UTF-8";
                $correoEnviado = mail($email, 'Restablece tu contraseña de Cinex+', $body, $headers);

                if (!$correoEnviado) {
                    $pdo->prepare('DELETE FROM PASSWORD_RESET_TOKEN WHERE token_hash = :token')
                        ->execute([':token' => $tokenHash]);
                    error_log('PHP mail() no pudo enviar un correo de restablecimiento; se requiere revisión administrativa.');
                }
            }

            if (!$correoEnviado) {
                $stmt = $pdo->prepare(
                    "INSERT INTO PASSWORD_RESET_REQUEST (cod_usuario)
                     VALUES (:id)
                     ON CONFLICT (cod_usuario) WHERE estado = 'pendiente' DO NOTHING"
                );
                $stmt->execute([':id' => $usuarioId]);
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('No se pudo procesar la solicitud de restablecimiento: ' . $e->getMessage());
    }
}

header('Location: ../views/public/recuperar_password.php?msg=requested');
exit();
