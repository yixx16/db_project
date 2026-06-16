<?php
// [MOD] Bootstrap unico como PRIMERA instruccion, antes de cualquier salida HTML.
require_once __DIR__ . '/../../includes/bootstrap.php';

// Recupera mensajes de sesion (y los limpia tras leerlos).
$mensaje_exito = isset($_SESSION['mensaje_exito']) ? $_SESSION['mensaje_exito'] : null;
unset($_SESSION['mensaje_exito']);
$mensaje_error = isset($_SESSION['mensaje_error']) ? $_SESSION['mensaje_error'] : null;
unset($_SESSION['mensaje_error']);

// [MOD][UI] Cabecera unificada via render_head() (assets locales consistentes).
render_head('Inicio de Sesion');

// [MOD] Header compartido.
include_once "../main/header.php";
?>
    <div class="container mx-auto mt-10">
        <div class="bg-gray-800 text-white p-4">
            <h2 class="text-2xl">Inicio de Sesion</h2>
        </div>

        <?php
            // [SEG][UI] Mensajes de sesion escapados con e().
            if ($mensaje_error !== null) {
                echo "<p class='text-red-500'>" . e($mensaje_error) . "</p>";
            }

            if ($mensaje_exito !== null) {
                echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative' role='alert'>
                        <span class='block sm:inline'>" . e($mensaje_exito) . "</span>
                      </div>";
            }
        ?>

        <div class="mt-4">
            <form action="procesar_login.php" method="POST" class="bg-white p-6 rounded shadow-md">
                <?php csrf_field(); // [SEG] Token CSRF. ?>
                <div class="mb-4">
                    <label for="username" class="block text-gray-700">Usuario:</label>
                    <input type="text" id="username" name="username" required class="mt-1 block w-full p-2 border border-gray-300 rounded">
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700">Contrasena:</label>
                    <input type="password" id="password" name="password" required class="mt-1 block w-full p-2 border border-gray-300 rounded">
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Iniciar Sesion</button>
            </form>
        </div>
    </div>
<?php
// [MOD][UI] Pie unificado via render_footer().
render_footer();
