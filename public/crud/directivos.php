<?php
// [MOD] Bootstrap unico como PRIMERA instruccion, antes de cualquier salida HTML.
require_once __DIR__ . '/../../includes/bootstrap.php';

// [SEG] Guard de acceso: solo usuarios autenticados pueden listar directivos.
require_login();

// [OPT] Logica de datos antes de emitir HTML para poder paginar en SQL y conocer $total.
// Configuracion de la paginacion.
$limit = 10; // Numero de registros por pagina.
$page = isset($_GET['page']) && validateNumber($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $limit; // Offset para la consulta.

// [SEG] Filtro de busqueda definido ANTES de usarse en el value del input.
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$total = 0;
$totalPages = 0;
$rows = [];

try {
    // [SEG][OPT] Consulta de conteo parametrizada (ILIKE con bindValue).
    $countQuery = "SELECT COUNT(*) AS total FROM directivos d
                   JOIN rigen r ON d.cod_dir = r.cod_dir
                   JOIN instituciones i ON r.cod_ies_padre = i.cod_ies_padre";
    if ($search !== '') {
        $countQuery .= " WHERE d.nombre ILIKE :search";
    }
    $countStmt = $conn->prepare($countQuery);
    if ($search !== '') {
        $countStmt->bindValue(':search', "%$search%");
    }
    $countStmt->execute();
    $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = (int)ceil($total / $limit);

    // [SEG][OPT] Consulta paginada en SQL, solo columnas necesarias, prepared statement.
    $query = "SELECT d.cod_dir, d.nombre, d.apellido, c.cargo, i.nomb_inst FROM directivos d
              JOIN rigen r ON d.cod_dir = r.cod_dir
              JOIN cargos c ON c.cod_cargo = r.cod_cargo
              JOIN instituciones i ON r.cod_ies_padre = i.cod_ies_padre";
    if ($search !== '') {
        $query .= " WHERE d.nombre ILIKE :search";
    }
    $query .= " LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($query);
    if ($search !== '') {
        $stmt->bindValue(':search', "%$search%");
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // [SEG] Mensaje generico, sin filtrar $e->getMessage() al cliente.
    error_log('directivos.php: ' . $e->getMessage());
    http_response_code(500);
    echo 'Ocurrio un error al consultar los directivos. Intente mas tarde.';
    exit();
}

// [MOD][UI] Cabecera unificada via render_head(). Font Awesome lo provee el
//           style.css local (es Font Awesome 5.15.3), sin CDN redundante.
render_head('Directivos');
?>
    <div class="container mx-auto mt-4">
        <?php include_once "../main/header.php"; ?>
        <h1 class="text-2xl font-bold mb-4">Directivos</h1>
        <form method="GET" action="directivos.php" class="mb-4">
            <input type="text" name="search" placeholder="Buscar por nombre" value="<?php echo e($search); ?>" class="border p-2 rounded">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Buscar</button>
        </form>
        <!-- [UI] Contenedor responsive con scroll horizontal. -->
        <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">#</th>
                    <th class="py-2 px-4 border-b">ID</th>
                    <th class="py-2 px-4 border-b">Nombre</th>
                    <th class="py-2 px-4 border-b">Apellido</th>
                    <th class="py-2 px-4 border-b">Cargo</th>
                    <th class="py-2 px-4 border-b">Institución</th>
                    <th class="py-2 px-4 border-b">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = $offset + 1; // Para numerar los registros correctamente.
                foreach ($rows as $row) {
                    // [SEG] Toda salida dinamica escapada con e().
                    $codDir = e($row['cod_dir']);
                    echo "<tr class='bg-white border-b hover:bg-gray-100'>"
                        . "<td class='py-2 px-4'>" . e($i) . "</td>"
                        . "<td class='py-2 px-4'>" . $codDir . "</td>"
                        . "<td class='py-2 px-4'>" . e($row['nombre']) . "</td>"
                        . "<td class='py-2 px-4'>" . e($row['apellido']) . "</td>"
                        . "<td class='py-2 px-4'>" . e($row['cargo']) . "</td>"
                        . "<td class='py-2 px-4'>" . e($row['nomb_inst']) . "</td>"
                        . "<td class='py-2 px-4'>"
                        . "<a href='edit.php?cod_dir={$codDir}' class='text-blue-500 hover:text-blue-700 mr-2'><i class='fas fa-edit'></i></a>"
                        . "<a href='delete.php?cod_dir={$codDir}' class='text-red-500 hover:text-red-700'><i class='fas fa-trash'></i></a>"
                        . "</td>"
                        . "</tr>";
                    $i++;
                }
                ?>
                <?php if ($total == 0): ?>
                    <tr>
                        <td colspan="7" class="py-2 px-4 border-b text-center">No se encontraron resultados</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
        <div class="mt-4 flex justify-between items-center">
            <a href="add_directivo.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                <i class="fas fa-plus"></i> Añadir Directivo
            </a>
            <div>
                <?php
                $range = 2; // Numero de paginas a mostrar a cada lado de la pagina actual.
                $start = max(1, $page - $range);
                $end = min($totalPages, $page + $range);

                if ($page > 1) {
                    echo '<a href="?page=1&search=' . urlencode($search) . '" class="px-4 py-2 border bg-white text-blue-500">Primero</a>';
                    echo '<a href="?page=' . ($page - 1) . '&search=' . urlencode($search) . '" class="px-4 py-2 border bg-white text-blue-500">Anterior</a>';
                }

                for ($p = $start; $p <= $end; $p++) {
                    if ($p == $page) {
                        echo '<span class="px-4 py-2 border bg-blue-500 text-white">' . $p . '</span>';
                    } else {
                        echo '<a href="?page=' . $p . '&search=' . urlencode($search) . '" class="px-4 py-2 border bg-white text-blue-500">' . $p . '</a>';
                    }
                }

                if ($page < $totalPages) {
                    echo '<a href="?page=' . ($page + 1) . '&search=' . urlencode($search) . '" class="px-4 py-2 border bg-white text-blue-500">Siguiente</a>';
                    echo '<a href="?page=' . $totalPages . '&search=' . urlencode($search) . '" class="px-4 py-2 border bg-white text-blue-500">Último</a>';
                }
                ?>
            </div>
        </div>
    </div>
<?php
// [MOD] Pie unificado via render_footer().
render_footer();
