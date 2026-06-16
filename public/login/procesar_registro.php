<?php
// [MOD] Bootstrap unico como PRIMERA instruccion (sesion + connect + validation + auth).
require_once __DIR__ . '/../../includes/bootstrap.php';

// [SEG] Solo aceptar POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registro.php');
    exit();
}

// [SEG] Verificar token CSRF antes de procesar el registro.
verify_csrf();

// Recibe los datos del formulario.
$correo_usuario = isset($_POST['email']) ? $_POST['email'] : '';
$contrasena     = isset($_POST['password']) ? $_POST['password'] : '';
$nombre         = isset($_POST['username']) ? $_POST['username'] : '';

// [SEG] Validacion de entrada (correo, nombre, password >= 8).
if (!validateEmail($correo_usuario) || !validateText($nombre) || !validatePassword($contrasena)) {
    $_SESSION['mensaje_error2'] = "Datos de entrada no validos.";
    header("Location: registro.php");
    exit();
}

try {
    // [SEG][OPT] Verificar existencia del correo con consulta preparada y columna minima.
    $query = "SELECT 1 FROM privado.usuarios WHERE correo_usuario = :correo_usuario";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':correo_usuario', $correo_usuario, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->fetchColumn() !== false) {
        // El correo ya esta registrado.
        $_SESSION['mensaje_error2'] = "El correo electronico ya esta registrado. Intenta con otro.";
        header("Location: registro.php");
        exit();
    }

    // El correo no esta registrado: proceder con el registro.
    $hashed_password = password_hash($contrasena, PASSWORD_BCRYPT);
    $contrasena = null; // Liberar la memoria.

    // [SEG] INSERT parametrizado.
    $query = "INSERT INTO privado.usuarios (correo_usuario, password, nombre)
              VALUES (:correo_usuario, :password, :nombre)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':correo_usuario', $correo_usuario, PDO::PARAM_STR);
    $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
    $stmt->execute();

    $_SESSION['mensaje_exito'] = "Usuario registrado exitosamente.";
    // [MOD] Redirect consistente con ruta relativa robusta (no $host/$uri).
    header("Location: login.php");
    exit();
} catch (PDOException $e) {
    // [SEG] No filtrar $e->getMessage(); mensaje generico + log interno.
    error_log('Error en procesar_registro: ' . $e->getMessage());
    $_SESSION['mensaje_error2'] = "Error al registrar el usuario. Intenta de nuevo.";
    header("Location: registro.php");
    exit();
}
