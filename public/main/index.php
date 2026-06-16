<?php
// public/main/index.php
// Pagina de busqueda publica de instituciones (formulario de filtros + resultados).
// [MOD] Bootstrap unico como PRIMERA instruccion: arranca sesion temprano
//       (elimina el session_start() tardio tras emitir HTML) y provee $conn y helpers.
require_once __DIR__ . '/../../includes/bootstrap.php';

// [MOD] Contadores agregados para las etiquetas de filtros.
require_once __DIR__ . '/../../atributtes.php';

// ---------------------------------------------------------------------------
// [OPT][SEG] Resultados: en vez de leer HTML pre-renderizado desde la sesion,
//            re-ejecutamos la consulta parametrizada con LIMIT/OFFSET y
//            renderizamos cada fila con el helper render_fila_institucion().
// ---------------------------------------------------------------------------
$baseQuery = "SELECT
                  ins.nomb_inst,
                  i.pagina_web,
                  i.cod_inst,
                  i.cod_ies_padre,
                  ins.publica,
                  ca.nomb_acad,
                  i.activa,
                  i.seccional,
                  aa.nomb_admin,
                  nc.nomb_norma,
                  d.nomb_depto,
                  m.nomb_munic
              FROM inst_por_mun i
              LEFT JOIN cobertura c           ON i.cod_inst      = c.cod_inst
              LEFT JOIN municipios m          ON c.cod_munic     = m.cod_munic
              LEFT JOIN departamentos d       ON m.cod_depto     = d.cod_depto
              LEFT JOIN instituciones ins     ON i.cod_ies_padre = ins.cod_ies_padre
              LEFT JOIN caracter_academico ca ON ins.cod_acad    = ca.cod_acad
              LEFT JOIN acto_administrativo aa ON i.cod_admin     = aa.cod_admin
              LEFT JOIN norma_creacion nc     ON i.cod_norma     = nc.cod_norma";

// Filtros provenientes del manejador results.php (o ninguno en la carga inicial).
$searchActive = !empty($_SESSION['search_active']);
$where  = $searchActive ? ($_SESSION['search_where']  ?? '') : '';
$params = $searchActive ? ($_SESSION['search_params'] ?? []) : [];
$limitSel = $searchActive ? ($_SESSION['search_limit'] ?? 10) : 10;

// [OPT] Total de coincidencias mediante COUNT (sin traer todas las filas).
$countSql = "SELECT COUNT(*) FROM inst_por_mun i
              LEFT JOIN cobertura c           ON i.cod_inst      = c.cod_inst
              LEFT JOIN municipios m          ON c.cod_munic     = m.cod_munic
              LEFT JOIN departamentos d       ON m.cod_depto     = d.cod_depto
              LEFT JOIN instituciones ins     ON i.cod_ies_padre = ins.cod_ies_padre
              LEFT JOIN caracter_academico ca ON ins.cod_acad    = ca.cod_acad
              LEFT JOIN acto_administrativo aa ON i.cod_admin     = aa.cod_admin
              LEFT JOIN norma_creacion nc     ON i.cod_norma     = nc.cod_norma" . $where;

$stmtCount = $conn->prepare($countSql);
foreach ($params as $ph => $val) {
    $type = is_bool($val) ? PDO::PARAM_BOOL : PDO::PARAM_STR;
    $stmtCount->bindValue($ph, $val, $type);
}
$stmtCount->execute();
$totalResults = (int) $stmtCount->fetchColumn();

// [OPT] Paginacion en SQL con LIMIT/OFFSET.
$inicio = 1;
if ($limitSel === 'Todos') {
    $limitNum = $totalResults;
    $dataSql = $baseQuery . $where . " ORDER BY i.cod_inst";
} else {
    $limitNum = (int) $limitSel;
    $dataSql = $baseQuery . $where . " ORDER BY i.cod_inst LIMIT :__limit OFFSET :__offset";
}
$stmt = $conn->prepare($dataSql);
foreach ($params as $ph => $val) {
    // Bind explicito: booleanos como BOOL, el resto como string (PostgreSQL los castea).
    $type = is_bool($val) ? PDO::PARAM_BOOL : PDO::PARAM_STR;
    $stmt->bindValue($ph, $val, $type);
}
if ($limitSel !== 'Todos') {
    $stmt->bindValue(':__limit', $limitNum, PDO::PARAM_INT);
    $stmt->bindValue(':__offset', 0, PDO::PARAM_INT);
}
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Pagina unica (OFFSET 0): el ultimo registro mostrado es el numero de filas traidas.
$fin = count($rows);

// La busqueda se consume una sola vez: limpiar para no reutilizar al recargar.
unset(
    $_SESSION['search_active'],
    $_SESSION['search_where'],
    $_SESSION['search_params'],
    $_SESSION['search_limit']
);

// [MOD][UI] Cabecera unificada via render_head() (assets locales consistentes).
render_head('Consulta de Instituciones');
?>
    <div class="container mx-auto">
        <?php include __DIR__ . '/header.php'; ?>
        <script>
            // [UI] Validacion JS centralizada en un unico lugar.
            function validarNumero(input) {
                const valor = input.value;
                const msg = input.parentElement.querySelector('.input-error');
                if (valor !== '' && !/^[1-9]\d*$/.test(valor)) {
                    if (msg) { msg.textContent = 'Ingresa solo enteros positivos.'; msg.classList.remove('hidden'); }
                } else if (msg) {
                    msg.textContent = ''; msg.classList.add('hidden');
                }
            }

            function validarTexto(input) {
                const valor = input.value;
                const msg = input.parentElement.querySelector('.input-error');
                if (valor !== '' && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(valor)) {
                    if (msg) { msg.textContent = 'Ingresa solo letras.'; msg.classList.remove('hidden'); }
                } else if (msg) {
                    msg.textContent = ''; msg.classList.add('hidden');
                }
            }
        </script>
        <div class="mt-4">
            <div class="flex justify-between items-center p-4 bg-gray-800 text-white">
                <h2 class="text-2xl">Consulta de Instituciones</h2>
                <div>
                    <a href="../login/login.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Iniciar Sesión</a>
                    <a href="../login/registro.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 ml-2">Registrar</a>
                </div>
            </div>

            <!-- [UI] flex-col en movil, fila en pantallas md+ -->
            <div class="flex flex-col md:flex-row mt-4">
                <!-- Sección de filtros -->
                <form id="filterForm" method="POST" action="results.php" class="w-full md:w-1/4 bg-white shadow-md p-4">
                    <h3 class="text-lg font-bold mb-4">Seleccione los filtros para la búsqueda</h3>

                        <div class="flex items-center space-x-2">
                            <label for="records">Mostrar:</label>
                             <select name="limit" class="border border-gray-300 p-2 rounded">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="Todos">Todos</option>
                            </select>
                            <span class="text-gray-700">registros</span>
                        </div>
                    <br>
                    <div class="mb-4">
                        <label class="block text-gray-700">Nombre de la Institución</label>
                        <input type="text" name="nombre_inst" oninput="validarTexto(this)" class="w-full border border-gray-300 p-2 rounded">
                        <span class="input-error hidden text-red-500 text-sm"></span>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">Código de la Institución</label>
                        <input type="text" name="codigo_inst" oninput="validarNumero(this)" class="w-full border border-gray-300 p-2 rounded">
                        <span class="input-error hidden text-red-500 text-sm"></span>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">Departamento</label>
                        <select name="departamento" class="w-full border border-gray-300 p-2 rounded">
                            <option value="all">Todos</option>
                            <?php
                            // [SEG] Salida escapada con e().
                            $result = $conn->query("SELECT cod_depto, nomb_depto FROM departamentos");
                            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='" . e($row['cod_depto']) . "'>" . e($row['nomb_depto']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <h4 class="font-bold">Tipo de institución</h4>
                        <div class="mt-2">
                            <label class="block text-gray-700">Estado de la Institución</label>
                            <div class="mt-1">
                                <label class="inline-flex items-center">
                                    <input type="radio" class="form-radio" name="estado" value="all" checked>
                                    <span class="ml-2">Todos</span>
                                </label>
                                <label class="inline-flex items-center ml-4">
                                    <input type="radio" class="form-radio" name="estado" value="true">
                                    <span class="ml-2">Activo (<?php echo e($nactiva); ?>)</span>
                                </label>
                                <label class="inline-flex items-center ml-4">
                                    <input type="radio" class="form-radio" name="estado" value="false">
                                    <span class="ml-2">Inactiva (<?php echo e($ninactiva); ?>)</span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="block text-gray-700">Tipo de sede</label>
                            <div class="mt-1">
                                <label class="inline-flex items-center ml-4">
                                    <input type="radio" class="form-radio" name="sede" value="all" checked>
                                    <span class="ml-2">Todos</span>
                                </label>
                                <br>
                                <label class="inline-flex items-center ml-4">
                                    <input type="radio" class="form-radio" name="sede" value="false">
                                    <span class="ml-2">Principal (<?php echo e($nno_seccional); ?>)</span>
                                </label>
                                <br>
                                <label class="inline-flex items-center ml-4">
                                    <input type="radio" class="form-radio" name="sede" value="true">
                                    <span class="ml-2">Seccional (<?php echo e($nseccional); ?>)</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-2">
                            <label class="block text-gray-700">Caracter Academico</label>
                            <div class="mt-1">
                                <label class="inline-flex items-center ml-4">
                                    <input type="radio" class="form-radio" name="caracter_acad" value="all" checked>
                                    <span class="ml-2">Todos</span>
                                </label>

                                <?php
                                $result = $conn->query("SELECT cod_acad, nomb_acad FROM caracter_academico");
                                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                    echo "
                                    <br>
                                    <label class='inline-flex items-center ml-4'>
                                    <input type='radio' class='form-radio' name='caracter_acad' value='" . e($row['cod_acad']) . "'>
                                    <span class='ml-2'>" . e($row['nomb_acad']) . "</span>
                                    </label>";
                                }
                                ?>
                            </div>
                        </div>
                        <div class="mt-2">
                        <label class="block text-gray-700">Acto Administrativo</label>
                        <select name="cod_admin" class="w-full border border-gray-300 p-2 rounded">
                            <option value="all">Todos</option>
                        <?php
                                $result = $conn->query("SELECT cod_admin, nomb_admin FROM acto_administrativo");
                                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='" . e($row['cod_admin']) . "'>" . e($row['nomb_admin']) . "</option>";
                                }
                                ?>
                        </select>
                        </div>
                        <div class="mt-2">
                        <label class="block text-gray-700">Norma de creacion</label>
                        <select name="cod_norma" class="w-full border border-gray-300 p-2 rounded">
                            <option value="all">Todos</option>
                        <?php
                                $result = $conn->query("SELECT cod_norma, nomb_norma FROM norma_creacion");
                                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<option value='" . e($row['cod_norma']) . "'>" . e($row['nomb_norma']) . "</option>";
                                }
                                ?>
                        </select>
                        </div>

                    </div>
                    <input type="hidden" name="submitted" value="true">
                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded">Buscar</button>
                </form>

                <!-- Sección de resultados -->
                <div class="w-full md:w-3/4 md:ml-4">
                    <!-- [UI] Contenedor responsive con scroll horizontal -->
                    <div class="overflow-x-auto">
                    <table class="min-w-full bg-white mt-4">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b">#</th>
                                <th class="py-2 px-4 border-b">Nombre IES</th>
                                <th class="py-2 px-4 border-b">Código IES</th>
                                <th class="py-2 px-4 border-b">IES padre</th>
                                <th class="py-2 px-4 border-b">Sector</th>
                                <th class="py-2 px-4 border-b">Caracter Academico</th>
                                <th class="py-2 px-4 border-b">Activa</th>
                                <th class="py-2 px-4 border-b">Seccional</th>
                                <th class="py-2 px-4 border-b">Acto Administrativo</th>
                                <th class="py-2 px-4 border-b">Norma de creacion</th>
                                <th class="py-2 px-4 border-b">Departamento/Municipio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // [MOD] Render de filas con el helper (evita duplicar el <tr>).
                            if (empty($rows)) {
                                echo "<tr><td colspan='11' class='py-2 px-4 border-b text-center'>No se encontraron resultados</td></tr>";
                            } else {
                                $i = $inicio;
                                foreach ($rows as $row) {
                                    echo render_fila_institucion($row, $i);
                                    $i++;
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                    </div>
                    <div class="bg-white shadow-md p-4 mt-4 flex justify-between items-center">
                        <div class="flex space-x-2">
                            <div>
                                <span class="text-gray-700"><?php echo 'Mostrando ' . e($totalResults === 0 ? 0 : $inicio) . ' a ' . e($fin) . ' de ' . e($totalResults) . ' instituciones coincidentes'; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow-md p-4 mt-4">
                        <p class="text-gray-700 text-sm">NOTA: La información aquí contenida corresponde a los datos de caracterización de la personería jurídica otorgada a la Institución de Educación Superior y a los programas académicos que oferta la Institución de Educación Superior a través del sistema SACES (Soporte al Aseguramiento de la Calidad de la Educación Superior).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
// [MOD][UI] Pie unificado via render_footer().
render_footer();
