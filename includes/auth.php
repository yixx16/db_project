<?php
// includes/auth.php
// Funciones de autenticacion, autorizacion y proteccion CSRF.

/**
 * Garantiza que exista una sesion activa.
 */
function ensure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Exige que el usuario haya iniciado sesion.
 * Si no hay 'dbuser' en sesion, redirige al login y termina.
 *
 * @param string $loginPath Ruta (relativa) a la pagina de login.
 */
function require_login($loginPath = '../login/login.php') {
    ensure_session();
    if (!isset($_SESSION['dbuser'])) {
        header('Location: ' . $loginPath);
        exit();
    }
}

/**
 * Exige que el usuario tenga un rol especifico.
 * Requiere login; si el rol no coincide redirige al index con error.
 *
 * @param string $role      Rol requerido.
 * @param string $loginPath Ruta (relativa) a la pagina de login.
 */
function require_role($role, $loginPath = '../login/login.php') {
    require_login($loginPath);
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== $role) {
        header('Location: ../main/index.php?error=forbidden');
        exit();
    }
}

/**
 * Genera (si no existe) y retorna el token CSRF de la sesion.
 *
 * @return string
 */
function csrf_token() {
    ensure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Imprime un campo oculto con el token CSRF para incluir en formularios.
 */
function csrf_field() {
    $token = csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verifica el token CSRF en peticiones POST.
 * Si falla, responde 403 y termina con un mensaje generico.
 */
function verify_csrf() {
    ensure_session();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sent = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        $stored = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
        if ($stored === '' || !is_string($sent) || !hash_equals($stored, $sent)) {
            http_response_code(403);
            echo 'Solicitud invalida (token de seguridad incorrecto).';
            exit();
        }
    }
}
