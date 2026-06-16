<?php
// [MOD] Bootstrap unico como PRIMERA instruccion, antes de cualquier salida HTML.
require_once __DIR__ . '/../../includes/bootstrap.php';

// Recupera el mensaje de error de registro (y lo limpia tras leerlo).
$mensaje_error2 = isset($_SESSION['mensaje_error2']) ? $_SESSION['mensaje_error2'] : null;
unset($_SESSION['mensaje_error2']);

// [MOD][UI] Cabecera unificada via render_head() (assets locales consistentes).
render_head('Registro');

// [MOD] Header compartido.
include_once "../main/header.php";
?>
    <div class="container mx-auto mt-10">
        <div class="bg-gray-800 text-white p-4">
            <h2 class="text-2xl">Registro de Usuario</h2>

            <?php
                // [SEG][UI] Mensaje de error escapado con e().
                if ($mensaje_error2 !== null) {
                    echo "<p class='text-red-500'>" . e($mensaje_error2) . "</p>";
                }
            ?>
        </div>

        <div class="mt-4">
        <form action="procesar_registro.php" method="POST" class="bg-white p-6 rounded shadow-md">
            <?php csrf_field(); // [SEG] Token CSRF. ?>
            <div class="mb-4">
                <label for="username" class="block text-gray-700">Nombre Completo:</label>
                <input type="text" id="username" name="username" required class="mt-1 block w-full p-2 border border-gray-300 rounded">
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700">Correo Electronico:</label>
                <input type="email" id="email" name="email" required class="mt-1 block w-full p-2 border border-gray-300 rounded">
            </div>
            <div class="mb-4">
                 <label for="password" class="block text-gray-700">Contrasena:</label>
                 <input type="password" id="password" name="password" required minlength="8" class="mt-1 block w-full p-2 border border-gray-300 rounded">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Registrar</button>
        </form>

        </div>
    </div>
<?php
// [MOD][UI] Pie unificado via render_footer().
render_footer();
