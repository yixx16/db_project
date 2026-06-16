<?php
// [MOD] Bootstrap unico como PRIMERA instruccion, antes de cualquier salida HTML.
require_once __DIR__ . '/../../includes/bootstrap.php';

// [SEG] Guard de acceso: solo usuarios autenticados pueden agregar directivos.
require_login();

// [SEG][OPT] Cargar catalogos para los selects con $conn global (bootstrap),
// solo columnas necesarias (ya eran las correctas).
$cargos = $conn->query("SELECT cod_cargo, cargo FROM cargos")->fetchAll(PDO::FETCH_ASSOC);
$nombramientos = $conn->query("SELECT cod_nombram, nomb_nombramiento FROM acto_nombr")->fetchAll(PDO::FETCH_ASSOC);
$instituciones = $conn->query("SELECT cod_ies_padre, nomb_inst FROM instituciones")->fetchAll(PDO::FETCH_ASSOC);

// [MOD] Mensaje flash desde la sesion (lo fija procesar_add_directivo.php).
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
unset($_SESSION['mensaje']);

// [MOD][UI] Cabecera unificada via render_head() (assets locales consistentes).
render_head('Agregar Directivo', '    <script>
        function confirmarInsercion() {
            return confirm("¿Estás seguro de que deseas agregar este directivo?");
        }
    </script>');
?>
    <?php include_once "../main/header.php"; ?>
    <div class="container mx-auto mt-4">
        <div class="bg-white p-6 rounded shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold">Agregar Directivo</h2>
                <a href="../main/index.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Inicio</a>
            </div>
            <?php
            // [SEG] El mensaje flash contiene marcado controlado por la app; se emite tal cual.
            if ($mensaje !== '') {
                echo $mensaje;
            }
            ?>
            <form id="addDirectivoForm" action="procesar_add_directivo.php" method="POST" onsubmit="return confirmarInsercion();">
                <?php csrf_field(); /* [SEG] Token CSRF */ ?>
                <div class="mb-4">
                    <label for="nombre" class="block text-gray-700">Nombre:</label>
                    <input type="text" name="nombre" id="nombre" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>
                <div class="mb-4">
                    <label for="apellido" class="block text-gray-700">Apellido:</label>
                    <input type="text" name="apellido" id="apellido" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>
                <div class="mb-4">
                    <label for="cargo" class="block text-gray-700">Cargo:</label>
                    <select name="cargo" id="cargo" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="">Seleccione un cargo</option>
                        <?php foreach ($cargos as $row): /* [SEG] e() escapa valores dinamicos */ ?>
                            <option value="<?php echo e($row['cod_cargo']); ?>"><?php echo e($row['cargo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="cod_nombram" class="block text-gray-700">Acto nombramiento:</label>
                    <select name="cod_nombram" id="cod_nombram" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="">Seleccione un acto de nombramiento</option>
                        <?php foreach ($nombramientos as $row): ?>
                            <option value="<?php echo e($row['cod_nombram']); ?>"><?php echo e($row['nomb_nombramiento']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="institucion" class="block text-gray-700">Institución:</label>
                    <select name="institucion" id="institucion" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="">Seleccione una institución</option>
                        <?php foreach ($instituciones as $row): ?>
                            <option value="<?php echo e($row['cod_ies_padre']); ?>"><?php echo e($row['nomb_inst']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Agregar Directivo</button>
                </div>
            </form>
        </div>
    </div>
<?php render_footer(); /* [MOD] Pie unificado */ ?>
