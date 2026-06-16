<?php
// [MOD] Bootstrap unico como PRIMERA instruccion, antes de cualquier salida HTML.
require_once __DIR__ . '/../../includes/bootstrap.php';

// [SEG] Guard de acceso: solo usuarios autenticados pueden eliminar.
require_login();

// [SEG] La accion destructiva SOLO se ejecuta por POST con token CSRF valido.
//       El metodo GET unicamente muestra el formulario de confirmacion.
$mensaje = null;          // ['tipo' => 'green|red|yellow', 'texto' => '...']
$mostrarFormulario = false;
$directivoNombre = '';
$codDir = null;

// [SEG] Identificador validado (sin interpolarlo nunca en SQL).
//       Se lee de $_POST en la mutacion (POST) y de $_GET en la confirmacion,
//       evitando $_REQUEST (que mezcla GET/POST/COOKIE).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codDirRaw = isset($_POST['cod_dir']) ? $_POST['cod_dir'] : null;
} else {
    $codDirRaw = isset($_GET['cod_dir']) ? $_GET['cod_dir'] : null;
}
if ($codDirRaw === null || !validateId($codDirRaw)) {
    $mensaje = ['tipo' => 'red', 'texto' => 'Identificador de directivo invalido.'];
} else {
    $codDir = (int)$codDirRaw;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // [SEG] Verificacion CSRF obligatoria antes de cualquier mutacion.
        verify_csrf();

        $confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';

        if ($confirm === 'yes') {
            try {
                // [SEG] DELETE parametrizado con sentencia preparada.
                $stmt = $conn->prepare("DELETE FROM directivos WHERE cod_dir = :cod_dir");
                $stmt->bindValue(':cod_dir', $codDir, PDO::PARAM_INT);
                $stmt->execute();

                header("Location: directivos.php");
                exit();
            } catch (PDOException $e) {
                // [SEG] Mensaje generico, sin exponer $e->getMessage().
                error_log('delete.php: ' . $e->getMessage());
                $mensaje = ['tipo' => 'red', 'texto' => 'No fue posible eliminar el registro. Intente mas tarde.'];
            }
        } elseif ($confirm === 'no') {
            header("Location: directivos.php");
            exit();
        } else {
            $mensaje = ['tipo' => 'red', 'texto' => 'Accion no reconocida.'];
        }
    } else {
        // [OPT] GET: solo se consultan las columnas necesarias para confirmar.
        try {
            $stmt = $conn->prepare("SELECT nombre, apellido FROM directivos WHERE cod_dir = :cod_dir");
            $stmt->bindValue(':cod_dir', $codDir, PDO::PARAM_INT);
            $stmt->execute();
            $directivo = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($directivo) {
                $directivoNombre = $directivo['nombre'] . ' ' . $directivo['apellido'];
                $mostrarFormulario = true;
            } else {
                $mensaje = ['tipo' => 'red', 'texto' => 'El directivo solicitado no existe.'];
            }
        } catch (PDOException $e) {
            error_log('delete.php: ' . $e->getMessage());
            $mensaje = ['tipo' => 'red', 'texto' => 'No fue posible cargar el directivo. Intente mas tarde.'];
        }
    }
}

// [MOD][UI] Cabecera unificada via render_head() (assets locales, sin CDN Tailwind).
render_head('Eliminar Directivo');
?>
    <div class="container mx-auto mt-10">
        <div class="bg-white p-6 rounded shadow-md">
            <?php if ($mensaje !== null): ?>
                <?php
                // [UI] Mapa de clases por tipo de mensaje (mismas clases Tailwind originales).
                $clases = [
                    'green'  => 'bg-green-100 border border-green-400 text-green-700',
                    'red'    => 'bg-red-100 border border-red-400 text-red-700',
                    'yellow' => 'bg-yellow-100 border border-yellow-400 text-yellow-700',
                ];
                $clase = isset($clases[$mensaje['tipo']]) ? $clases[$mensaje['tipo']] : $clases['red'];
                ?>
                <div class="<?php echo $clase; ?> px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo e($mensaje['texto']); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($mostrarFormulario): ?>
                <!-- [SEG] Confirmacion por POST con token CSRF. -->
                <form method="POST" action="delete.php" class="bg-white p-6 rounded shadow-md">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="cod_dir" value="<?php echo e($codDir); ?>">
                    <p class="text-gray-700">¿Está seguro de que desea eliminar este directivo?: <strong><?php echo e($directivoNombre); ?></strong></p>
                    <div class="mt-4">
                        <button type="submit" name="confirm" value="yes" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Sí</button>
                        <button type="submit" name="confirm" value="no" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 ml-2">No</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (!$mostrarFormulario && $mensaje !== null): ?>
                <div class="mt-4">
                    <a href="directivos.php" class="text-blue-500 hover:text-blue-700">Volver al listado</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
// [MOD] Pie unificado via render_footer().
render_footer();
