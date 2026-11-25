<?php

function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function token_csrf(): string
{
    iniciar_sesion_segura();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function post_texto(string $campo, bool $recortar = true): string
{
    $valor = $_POST[$campo] ?? '';

    if (!is_string($valor)) {
        return '';
    }

    return $recortar ? trim($valor) : $valor;
}

function password_cumple_politica(string $password, array $datosPersonales = []): bool
{
    if (
        strlen($password) < 8 ||
        preg_match('/\s/', $password) ||
        !preg_match('/\A[A-Za-z0-9*?!@#$\/(){}=.,;:]+\z/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[*?!@#$\/(){}=.,;:]/', $password) ||
        preg_match('/(.)\1{2,}/', $password)
    ) {
        return false;
    }

    $normalizada = strtolower($password);
    if (preg_match('/[0-9]{8}/', $password)) {
        return false;
    }

    $patronesTeclado = ['qwerty', 'qwert', 'werty', 'asdf', 'zxcv', 'qaz', 'wsx', 'edc', 'rfv', 'tgb', 'yhn', 'ujm', 'mnb', 'lkj', 'poi', '1234', '2345', '3456', '4567', '5678', '6789', '9876', '8765', '7654', '6543', '5432', '4321', 'abcd', 'bcde', 'cdef', 'defg', 'wxyz', 'zyxw'];
    foreach ($patronesTeclado as $patron) {
        if (str_contains($normalizada, $patron)) {
            return false;
        }
    }

    foreach ($datosPersonales as $dato) {
        if (!is_string($dato)) {
            continue;
        }
        if (str_contains($dato, '@')) {
            $dato = explode('@', $dato, 2)[0];
        }
        $dato = strtolower(preg_replace('/[^a-z0-9]/i', '', $dato));
        if (strlen($dato) >= 3 && str_contains($normalizada, $dato)) {
            return false;
        }
    }

    return true;
}

function verificar_token_csrf(): void
{
    iniciar_sesion_segura();
    $token = $_POST['csrf_token'] ?? null;

    if (!is_string($token) || !hash_equals(token_csrf(), $token)) {
        http_response_code(419);
        exit('La sesión del formulario venció. Recarga la página e inténtalo de nuevo.');
    }
}

function exigir_rol(array $roles, string $rutaLogin): void
{
    iniciar_sesion_segura();

    if (!isset($_SESSION['usuario_id'], $_SESSION['rol_id']) || !in_array((int) $_SESSION['rol_id'], $roles, true)) {
        header('Location: ' . $rutaLogin);
        exit();
    }
}
