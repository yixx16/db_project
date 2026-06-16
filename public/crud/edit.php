<?php
// [MOD] Bootstrap unico como PRIMERA instruccion, antes de cualquier salida HTML.
require_once __DIR__ . '/../../includes/bootstrap.php';

// [SEG] Guard de acceso: solo usuarios autenticados pueden editar directivos.
require_login();

// [MOD] edit.php es SOLO la vista (formulario). El UPDATE transaccional lo hace
// procesar_edit.php; aqui se eliminó el bloque de procesamiento POST duplicado.

$error = null;
$directivo = null;
$cargos = [];
$cargosDirectivo = [];

// [SEG] Validar cod_dir recibido por GET antes de usarlo.
$cod_dir = isset($_GET['cod_dir']) ? $_GET['cod_dir'] : null;
if (!validateId($cod_dir)) {
    $error = "Directivo no válido.";
} else {
    // [MOD] Guardar el cod_dir en sesion para que procesar_edit.php lo use.
    $_SESSION['cod_dir'] = $cod_dir;

    try {
        // [SEG][OPT] Datos del directivo (solo columnas necesarias, ya parametrizado).
        $query = "SELECT d.nombre, d.apellido, r.cod_cargo, i.nomb_inst, r.cod_ies_padre FROM directivos d
                  JOIN rigen r ON d.cod_dir = r.cod_dir
                  JOIN instituciones i ON r.cod_ies_padre = i.cod_ies_padre
                  WHERE d.cod_dir = :cod_dir";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':cod_dir', $cod_dir, PDO::PARAM_INT);
        $stmt->execute();
        $directivo = $stmt->fetch(PDO::FETCH_ASSOC);

        // [SEG] Null-check: cod_dir inexistente no debe reventar htmlspecialchars.
        if (!$directivo) {
            $error = "Directivo no encontrado.";
        } else {
            $_SESSION['cod_ies_padre'] = $directivo['cod_ies_padre'];

            $cargos = $conn->query("SELECT cod_cargo, cargo FROM cargos")->fetchAll(PDO::FETCH_ASSOC);

            // [OPT] Cargos actuales del directivo en la institucion (parametrizado).
            $query = "SELECT c.cargo FROM rigen r
                      JOIN cargos c ON r.cod_cargo = c.cod_cargo
                      WHERE r.cod_dir = :cod_dir";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(':cod_dir', $cod_dir, PDO::PARAM_INT);
            $stmt->execute();
            $cargosDirectivo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // [SEG] No exponer detalles crudos de la BD.
        error_log('edit.php: ' . $e->getMessage());
        $error = "No fue posible cargar la información del directivo.";
    }
}

// [MOD][UI] Cabecera unificada via render_head() con FontAwesome local.
render_head('Editar Directivo', '    <script>
        function confirmarActualizacion() {
            return confirm("¿Estás seguro de que deseas actualizar la información del directivo?");
        }
    </script>');
?>
    <div class="w-full max-w-lg mx-auto bg-white border border-gray-300 rounded-lg shadow-lg overflow-hidden mt-8">

        <div class="bg-blue-600 p-4">
            <h2 class="text-2xl font-bold text-white text-center">Editar Directivo</h2>
        </div>

        <div class="p-6">

            <?php if ($error !== null): ?>
                <p class="text-red-500 text-center mb-4"><?php echo e($error); ?></p>
                <div class="text-center">
                    <a href="directivos.php" class="px-4 py-2 bg-gray-500 text-white rounded-md shadow hover:bg-gray-600 inline-block">
                        <i class="fas fa-arrow-left mr-2"></i>Volver
                    </a>
                </div>
            <?php else: ?>
            <form action="procesar_edit.php" method="POST" class="space-y-4" onsubmit="return confirmarActualizacion();">
                <?php csrf_field(); /* [SEG] Token CSRF */ ?>
                <div>
                    <label for="nombre" class="block text-sm font-semibold text-gray-700">Nombre</label>
                    <input type="text" name="nombre" id="nombre" value="<?php echo e($directivo['nombre']); ?>" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <div>
                    <label for="apellido" class="block text-sm font-semibold text-gray-700">Apellido</label>
                    <input type="text" name="apellido" id="apellido" value="<?php echo e($directivo['apellido']); ?>" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <div>
                    <label for="institucion" class="block text-sm font-semibold text-gray-700">Institución</label>
                    <input type="text" name="institucion" id="institucion" value="<?php echo e($directivo['nomb_inst']); ?>" disabled
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                <div>
                    <label for="cargo" class="block text-sm font-semibold text-gray-700">Cargo Actual</label>
                    <select name="cargo" id="cargo" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <?php foreach ($cargos as $c): ?>
                            <option value="<?php echo e($c['cod_cargo']); ?>" <?php echo ($c['cod_cargo'] == $directivo['cod_cargo']) ? 'selected' : ''; ?>>
                                <?php echo e($c['cargo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700">Cargos del Directivo en la Institución</label>
                    <ul class="list-disc pl-5">
                        <?php foreach ($cargosDirectivo as $c): ?>
                            <li><?php echo e($c['cargo']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="flex justify-between mt-6">
                    <a href="directivos.php" class="px-4 py-2 bg-gray-500 text-white rounded-md shadow hover:bg-gray-600">
                        <i class="fas fa-arrow-left mr-2"></i>Cancelar
                    </a>
                    <button type="submit" name="submit" value="true" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-md shadow hover:bg-blue-700 transition duration-200">
                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                    </button>
                </div>
                <br>
                <a href="directivos.php" class="px-4 py-2 bg-gray-500 text-white rounded-md shadow hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </form>
            <?php endif; ?>

        </div>

        <div class="bg-gray-100 p-4 text-center">
            <p class="text-xs text-gray-500">Estás editando la información de un directivo. Asegúrate de guardar los cambios antes de salir.</p>
        </div>

    </div>
<?php render_footer(); /* [MOD] Pie unificado */ ?>
