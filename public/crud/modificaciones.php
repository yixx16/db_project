<?php
// [MOD] Bootstrap unico como PRIMERA instruccion (arregla el session_start() tardio
//       que estaba DESPUES del HTML, evitando "headers already sent").
require_once __DIR__ . '/../../includes/bootstrap.php';

// [SEG] Dashboard protegido: requiere sesion iniciada.
require_login();

// [MOD][UI] Cabecera unificada via render_head() (assets locales).
render_head('Inicio');
?>
<?php include_once "../main/header.php"; ?>
    <div class="container mx-auto">
        <div class="mt-4">
            <div class="flex justify-between items-center p-4 bg-gray-800 text-white">
                <div class="flex items-center">
                    <!-- [UI] alt descriptivo en la imagen de usuario. -->
                    <img src="../media/usuario.png" alt="Avatar de usuario" class="w-8 h-8 rounded-full mr-2">
                    <h2 class="text-2xl"><?php echo isset($_SESSION['username']) ? "Hola, " . e($_SESSION['username']) : "Página de Usuario"; ?></h2>
                </div>
                <div>
                    <?php if (isset($_SESSION['username'])): ?>
                        <a href="../login/logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Cerrar sesión</a>
                    <?php else: ?>
                        <a href="../login/login.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Iniciar Sesión</a>
                        <a href="../login/registro.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 ml-2">Registrar</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white shadow-md p-4 mt-4">
                <?php if (isset($_SESSION['mensaje_exito'])): ?>
                    <!-- [SEG] Mensaje de sesion escapado con e(). -->
                    <p class="text-green-500"><?php echo e($_SESSION['mensaje_exito']); unset($_SESSION['mensaje_exito']); ?></p>
                <?php endif; ?>
            </div>

            <!-- Mostrar el rol del usuario -->
            <div class="bg-white shadow-md p-4 mt-4">
                <p class="text-gray-700">Rol: <?php echo isset($_SESSION['rol']) ? e($_SESSION['rol']) : "No definido"; ?></p>
            </div>

            <!-- Botones centrados y agrandados -->
            <div class="mt-4 flex justify-center space-x-4">
                <a href="directivos.php" class="bg-blue-200 flex flex-col items-center p-10 rounded-lg shadow-lg transform transition-all duration-200 hover:scale-105 hover:bg-blue-300">
                    <img src="../media/directivos.png" alt="Directivos" class="w-32 h-32 mb-4">
                    <span class="text-2xl font-semibold">Directivos</span>
                </a>
                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                    <a href="registros_autoria.php" class="bg-blue-200 flex flex-col items-center p-10 rounded-lg shadow-lg transform transition-all duration-200 hover:scale-105 hover:bg-blue-300">
                        <img src="../media/postgres.png" alt="Registros de Autoria" class="w-32 h-32 mb-4">
                        <span class="text-2xl font-semibold">Registros de Autoria</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php
// [MOD] Pie unificado via render_footer().
render_footer();
