<?php
// [MOD] Bootstrap unico como PRIMERA instruccion, antes de cualquier salida HTML.
//       Reemplaza session_start() tardio + include suelto de connect.php.
require_once __DIR__ . '/../../includes/bootstrap.php';

// [SEG] Esta pagina muestra datos de auditoria sensibles: solo administradores.
//       require_role ya exige login y verifica $_SESSION['rol'] === 'admin'.
require_role('admin');

try {
    // [SEG] Sentencia preparada. [OPT] SELECT de columnas explicitas (no SELECT *).
    $query = "SELECT id, operacion, fecha, usuario, registro
              FROM privado.registro_autoria
              ORDER BY id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // [SEG] Mensaje generico: no se filtra $e->getMessage() al usuario.
    $registros = [];
    $errorMsg = 'No se pudieron cargar los registros de autoria.';
}

// [MOD][UI] Cabecera unificada via render_head() (assets locales consistentes).
render_head('Registros de Autoria');
?>
<?php
    // [MOD] Partial reutilizable de cabecera.
    include_once "../main/header.php";
?>
    <div class="container mx-auto mt-10">
        <div class="bg-gray-800 text-white p-4 flex justify-between items-center">
            <h2 class="text-2xl">Registros de Autoria</h2>
            <a href="../main/index.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                <i class="fas fa-home"></i> Inicio
            </a>
        </div>
        <div class="bg-white p-6 rounded shadow-md mt-4">
            <?php if (!empty($errorMsg)): ?>
                <!-- [SEG][UI] Mensaje de error inline y generico. -->
                <p class="text-red-600"><?php echo e($errorMsg); ?></p>
            <?php elseif (empty($registros)): ?>
                <p class="text-gray-600">No hay registros de autoria para mostrar.</p>
            <?php else: ?>
            <!-- [UI] Contenedor responsive con scroll horizontal. -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b">ID</th>
                            <th class="py-2 px-4 border-b">Operacion</th>
                            <th class="py-2 px-4 border-b">Fecha</th>
                            <th class="py-2 px-4 border-b">Usuario</th>
                            <th class="py-2 px-4 border-b">Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $registro): ?>
                            <tr class="bg-white border-b hover:bg-gray-100">
                                <!-- [SEG] Toda salida dinamica escapada con e(). -->
                                <td class="py-2 px-4 border-b"><?php echo e($registro['id']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo e($registro['operacion']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo e($registro['fecha']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo e($registro['usuario']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo e($registro['registro']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php
// [MOD] Pie unificado via render_footer() (cierra body/html).
render_footer();
