<?php
// atributtes.php
// Calcula los contadores agregados usados por la pagina de busqueda (index.php).
// [MOD] Se espera que $conn ya este disponible (provisto por bootstrap.php / connect.php).
// [OPT] Las 6 consultas COUNT(*) se colapsan en 2 consultas con agregacion
//       condicional COUNT(*) FILTER (WHERE ...), conservando las MISMAS
//       variables resultantes que consume index.php.

// [SEG][OPT] Contadores sobre la tabla instituciones (sector publico/privado).
$rowInst = $conn->query(
    "SELECT
        COUNT(*) FILTER (WHERE publica = true)  AS npublic,
        COUNT(*) FILTER (WHERE publica = false) AS npriv
     FROM instituciones"
)->fetch(PDO::FETCH_ASSOC);

// [SEG][OPT] Contadores sobre inst_por_mun (estado activo y tipo de sede).
$rowMun = $conn->query(
    "SELECT
        COUNT(*) FILTER (WHERE activa = true)     AS nactiva,
        COUNT(*) FILTER (WHERE activa = false)    AS ninactiva,
        COUNT(*) FILTER (WHERE seccional = true)  AS nseccional,
        COUNT(*) FILTER (WHERE seccional = false) AS nno_seccional
     FROM inst_por_mun"
)->fetch(PDO::FETCH_ASSOC);

// Variables conservadas para no romper index.php.
$npublic      = $rowInst['npublic'];
$npriv        = $rowInst['npriv'];
$nactiva      = $rowMun['nactiva'];
$ninactiva    = $rowMun['ninactiva'];
$nseccional   = $rowMun['nseccional'];
$nno_seccional = $rowMun['nno_seccional'];
