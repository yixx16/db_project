<?php
// [MOD] Bootstrap unico como PRIMERA instruccion (sesion + $conn + validaciones + auth).
require_once __DIR__ . '/../../includes/bootstrap.php';

// [SEG] Solo usuarios autenticados; la edicion solo procede por POST + CSRF.
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && $_POST['submit'] === 'true') {
    // [SEG] Verificar token CSRF antes de procesar la escritura.
    verify_csrf();

    $cod_dir = isset($_SESSION['cod_dir']) ? $_SESSION['cod_dir'] : null;
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $cargo = $_POST['cargo'] ?? '';
    $cod_ies_padre = isset($_SESSION['cod_ies_padre']) ? $_SESSION['cod_ies_padre'] : null; // Institucion desde la sesion

    // [SEG] Validar identificadores de sesion antes de operar.
    if (!validateId($cod_dir) || !validateId($cod_ies_padre)) {
        $_SESSION['mensaje'] = "<p class='text-red-500'>Sesión de edición no válida.</p>";
        header("Location: directivos.php");
        exit();
    }

    // [SEG] Validar entrada del formulario.
    if (!validateText($nombre) || !validateText($apellido) || !validateNumber($cargo)) {
        $_SESSION['mensaje'] = "<p class='text-red-500'>Todos los campos son obligatorios y deben ser válidos.</p>";
        header("Location: edit.php?cod_dir=" . urlencode($cod_dir));
        exit();
    }

    try {
        // [MOD] Usa el $conn global del bootstrap (no reconectar).
        $conn->beginTransaction();

        // [SEG] Verificar si el cargo ya esta ocupado por otro directivo en la misma institucion (parametrizado).
        $query = "SELECT COUNT(*) FROM rigen r
                  JOIN directivos d ON r.cod_dir = d.cod_dir
                  WHERE r.cod_cargo = :cargo AND r.cod_dir != :cod_dir AND r.cod_ies_padre = :cod_ies_padre";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':cargo', $cargo, PDO::PARAM_INT);
        $stmt->bindValue(':cod_dir', $cod_dir, PDO::PARAM_INT);
        $stmt->bindValue(':cod_ies_padre', $cod_ies_padre, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $conn->rollBack();
            $_SESSION['mensaje'] = "<p class='text-red-500'>El cargo seleccionado ya está ocupado por otro directivo en la misma institución.</p>";
            header("Location: edit.php?cod_dir=" . urlencode($cod_dir));
            exit();
        }

        // [SEG] Actualizar los datos del directivo (parametrizado).
        $query = "UPDATE directivos SET nombre = :nombre, apellido = :apellido WHERE cod_dir = :cod_dir";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':apellido', $apellido, PDO::PARAM_STR);
        $stmt->bindValue(':cod_dir', $cod_dir, PDO::PARAM_INT);
        $stmt->execute();

        // [SEG] Actualizar el cargo del directivo (parametrizado).
        $query = "UPDATE rigen SET cod_cargo = :cargo WHERE cod_dir = :cod_dir";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':cargo', $cargo, PDO::PARAM_INT);
        $stmt->bindValue(':cod_dir', $cod_dir, PDO::PARAM_INT);
        $stmt->execute();

        $conn->commit();
        $_SESSION['mensaje'] = "<p class='text-green-500'>Directivo actualizado exitosamente.</p>";
        header("Location: directivos.php");
        exit();
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        // [SEG] No exponer $e->getMessage() crudo: mensaje generico.
        error_log('procesar_edit: ' . $e->getMessage());
        $_SESSION['mensaje'] = "<p class='text-red-500'>No fue posible actualizar el directivo. Intente nuevamente.</p>";
        header("Location: edit.php?cod_dir=" . urlencode($cod_dir));
        exit();
    }
} else {
    // [SEG] Solo accesible por POST con el flag submit.
    header("Location: directivos.php");
    exit();
}
