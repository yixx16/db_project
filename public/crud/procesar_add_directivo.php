<?php
// [MOD] Bootstrap unico como PRIMERA instruccion (sesion + $conn + validaciones + auth).
require_once __DIR__ . '/../../includes/bootstrap.php';

// [SEG] Solo usuarios autenticados pueden insertar; accion destructiva solo por POST + CSRF.
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [SEG] Verificar token CSRF antes de procesar cualquier escritura.
    verify_csrf();

    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $cargo = $_POST['cargo'] ?? '';
    $institucion = $_POST['institucion'] ?? '';
    $cod_nombram = $_POST['cod_nombram'] ?? '';

    // [SEG] Validacion de entrada (texto + ids numericos).
    if (!validateText($nombre) || !validateText($apellido)
        || !validateNumber($cargo) || !validateNumber($institucion) || !validateNumber($cod_nombram)) {
        $_SESSION['mensaje'] = "<p class='text-red-500'>Todos los campos son obligatorios y deben ser válidos.</p>";
        header("Location: add_directivo.php");
        exit();
    }

    try {
        // [MOD] Usa el $conn global provisto por el bootstrap (no reconectar).

        // [SEG] Verificar si el cargo ya esta ocupado en la misma institucion (parametrizado).
        $query = "SELECT COUNT(*) FROM rigen WHERE cod_cargo = :cargo AND cod_ies_padre = :institucion";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':cargo', $cargo, PDO::PARAM_INT);
        $stmt->bindValue(':institucion', $institucion, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $_SESSION['mensaje'] = "<p class='text-red-500'>El cargo seleccionado ya está ocupado en la misma institución.</p>";
            header("Location: add_directivo.php");
            exit();
        }

        // [SEG][OPT] Insertar el directivo y obtener cod_dir con RETURNING (PostgreSQL),
        // evitando el SELECT por nombre vulnerable a inyeccion y condiciones de carrera.
        $conn->beginTransaction();

        $query = "INSERT INTO directivos (nombre, apellido) VALUES (:nombre, :apellido) RETURNING cod_dir";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':apellido', $apellido, PDO::PARAM_STR);
        $stmt->execute();
        $cod_dir = $stmt->fetchColumn();

        // [SEG] Asignar el cargo al directivo en la institucion (INSERT totalmente parametrizado).
        $query = "INSERT INTO rigen (cod_dir, cod_cargo, cod_ies_padre, cod_nombram)
                  VALUES (:cod_dir, :cargo, :institucion, :cod_nombram)";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':cod_dir', $cod_dir, PDO::PARAM_INT);
        $stmt->bindValue(':cargo', $cargo, PDO::PARAM_INT);
        $stmt->bindValue(':institucion', $institucion, PDO::PARAM_INT);
        $stmt->bindValue(':cod_nombram', $cod_nombram, PDO::PARAM_INT);
        $stmt->execute();

        $conn->commit();
        $_SESSION['mensaje'] = "<p class='text-green-500'>Directivo agregado exitosamente.</p>";
        header("Location: directivos.php");
        exit();
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        // [SEG] No exponer $e->getMessage() crudo: mensaje generico al usuario.
        error_log('procesar_add_directivo: ' . $e->getMessage());
        $_SESSION['mensaje'] = "<p class='text-red-500'>No fue posible agregar el directivo. Intente nuevamente.</p>";
        header("Location: add_directivo.php");
        exit();
    }
} else {
    // [SEG] Accion solo accesible por POST.
    header("Location: add_directivo.php");
    exit();
}
