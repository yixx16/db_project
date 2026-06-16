<?php
// includes/bootstrap.php
// Punto de entrada unico de cada pagina. DEBE incluirse antes de cualquier salida HTML.

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../connect.php';     // provee $conn global
require_once __DIR__ . '/../validation.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/render.php';
require_once __DIR__ . '/layout.php';
