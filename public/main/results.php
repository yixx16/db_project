<?php
// public/main/results.php
// Manejador del formulario de busqueda publica de instituciones.
// [MOD] Bootstrap unico como PRIMERA instruccion (antes de cualquier salida).
require_once __DIR__ . '/../../includes/bootstrap.php';   // provee $conn, sesion, helpers

// [SEG] Solo procesa envios POST validos; cualquier otro acceso vuelve al buscador.
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !isset($_POST['submitted'])
    || $_POST['submitted'] !== 'true'
) {
    header('Location: index.php');
    exit;
}

// ---------------------------------------------------------------------------
// [SEG] Lectura y normalizacion de filtros. NADA se concatena en el SQL:
//       se construye un WHERE dinamico con placeholders y un array de params.
// ---------------------------------------------------------------------------
$limitRaw  = $_POST['limit']        ?? '10';
$estado    = ($_POST['estado']    ?? 'all') === 'all' ? null : ($_POST['estado']);
$sede      = ($_POST['sede']      ?? 'all') === 'all' ? null : ($_POST['sede']);
$nombre    = trim((string) ($_POST['nombre_inst'] ?? ''));
$codigo    = trim((string) ($_POST['codigo_inst'] ?? ''));
$departamento  = $_POST['departamento']  ?? 'all';
$caracter_acad = $_POST['caracter_acad'] ?? 'all';
$cod_admin     = $_POST['cod_admin']     ?? 'all';
$cod_norma     = $_POST['cod_norma']     ?? 'all';

$nombre = $nombre === '' ? null : $nombre;
$codigo = $codigo === '' ? null : $codigo;

// [SEG] WHERE dinamico con placeholders nombrados + array de parametros.
$conditions = [];
$params = [];

if ($estado !== null) {
    // 'true' / 'false' -> booleano real para el driver.
    $conditions[] = 'activa = :activa';
    $params[':activa'] = ($estado === 'true');
}
if ($sede !== null) {
    $conditions[] = 'seccional = :seccional';
    $params[':seccional'] = ($sede === 'true');
}
if ($codigo !== null && validateNumber($codigo)) {
    $conditions[] = 'i.cod_inst = :cod_inst';
    $params[':cod_inst'] = $codigo;
}
if ($nombre !== null) {
    // ILIKE parametrizado: el comodin va en el valor, no en el SQL.
    $conditions[] = 'ins.nomb_inst ILIKE :nombre';
    $params[':nombre'] = '%' . $nombre . '%';
}
if ($departamento !== 'all' && validateNumber($departamento)) {
    $conditions[] = 'd.cod_depto = :cod_depto';
    $params[':cod_depto'] = $departamento;
}
if ($caracter_acad !== 'all' && validateNumber($caracter_acad)) {
    $conditions[] = 'ca.cod_acad = :cod_acad';
    $params[':cod_acad'] = $caracter_acad;
}
if ($cod_admin !== 'all' && validateNumber($cod_admin)) {
    $conditions[] = 'i.cod_admin = :cod_admin';
    $params[':cod_admin'] = $cod_admin;
}
if ($cod_norma !== 'all' && validateNumber($cod_norma)) {
    $conditions[] = 'i.cod_norma = :cod_norma';
    $params[':cod_norma'] = $cod_norma;
}

$where = $conditions ? (' WHERE ' . implode(' AND ', $conditions)) : '';

// [OPT] Guardamos solo los PARAMETROS de busqueda en sesion (no HTML).
//       index.php re-ejecuta la consulta parametrizada con LIMIT/OFFSET.
$_SESSION['search_where']  = $where;
$_SESSION['search_params'] = $params;

// [SEG][OPT] Limite de paginacion validado (entero positivo) o "Todos".
if ($limitRaw === 'Todos') {
    $_SESSION['search_limit'] = 'Todos';
} elseif (validateNumber($limitRaw)) {
    $_SESSION['search_limit'] = (int) $limitRaw;
} else {
    $_SESSION['search_limit'] = 10;
}

$_SESSION['search_active'] = true;

// [SEG] Redireccion relativa (sin construir la URL desde HTTP_HOST/PHP_SELF).
header('Location: index.php');
exit;
