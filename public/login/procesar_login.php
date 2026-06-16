<?php
// [MOD] Bootstrap unico como PRIMERA instruccion (sesion + connect + validation + auth).
require_once __DIR__ . '/../../includes/bootstrap.php';

// [SEG] Solo aceptar POST para una accion que crea sesion autenticada.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

// [SEG] Verificar token CSRF antes de procesar credenciales.
verify_csrf();

$username = isset($_POST['username']) ? $_POST['username'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

try {
    // [SEG][OPT] Consulta preparada, solo columnas necesarias.
    $query = "SELECT correo_usuario, password, rol, nombre FROM privado.usuarios WHERE correo_usuario = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si el usuario existe y si la contrasena coincide.
    if ($user && password_verify($password, $user['password'])) {
        // [SEG] Regenerar id de sesion al elevar privilegios (mitiga fijacion de sesion).
        session_regenerate_id(true);

        $_SESSION['username']      = $user['nombre'];
        $_SESSION['mensaje_exito'] = "Inicio de sesion exitoso.";
        $_SESSION['rol']           = $user['rol'];
        $_SESSION['dbuser']        = $user['correo_usuario'];
        // [SEG] NO se guarda el hash bcrypt en $_SESSION['dbpass'] (innecesario y riesgoso).

        // [SEG][MOD] Redirect con ruta relativa robusta (no se usan $host/$uri indefinidos).
        header("Location: ../crud/modificaciones.php");
        exit();
    }

    // Credenciales incorrectas: mensaje generico (no revela si el usuario existe).
    $_SESSION['mensaje_error'] = "Nombre de usuario o contrasena incorrectos.";
    header("Location: login.php");
    exit();
} catch (PDOException $e) {
    // [SEG] No filtrar $e->getMessage() al usuario; mensaje generico.
    error_log('Error en procesar_login: ' . $e->getMessage());
    $_SESSION['mensaje_error'] = "Ocurrio un error al iniciar sesion. Intenta de nuevo.";
    header("Location: login.php");
    exit();
}
