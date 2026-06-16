<?php
// [MOD] Bootstrap unico como PRIMERA instruccion (garantiza la sesion via ensure_session/session_start).
require_once __DIR__ . '/../../includes/bootstrap.php';

// [SEG] Teardown completo de la sesion: vaciar datos, eliminar la cookie y destruirla.
$_SESSION = array();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_unset();
session_destroy();

// [MOD] Redirect con ruta relativa robusta.
header("Location: ../main/index.php");
exit();
