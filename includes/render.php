<?php
// includes/render.php
// Helpers de escape y renderizado de filas HTML.

/**
 * Escapa una cadena para salida HTML segura.
 *
 * @param mixed $s
 * @return string
 */
function e($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/**
 * Renderiza (retorna, no imprime) la fila <tr> de una institucion.
 * Mantiene exactamente las mismas columnas y clases que results.php/index.php.
 * Todos los valores se escapan con e().
 *
 * Se espera que $row['publica'], $row['activa'] y $row['seccional'] ya esten
 * normalizados a texto (Publico/Privado, Activa/Inactiva, Seccional/Principal);
 * si llegan como valores booleanos/numericos crudos tambien se interpretan.
 *
 * @param array $row Fila asociativa de la consulta.
 * @param int   $i   Numero de fila a mostrar.
 * @return string
 */
function render_fila_institucion(array $row, int $i) {
    $get = function ($key) use ($row) {
        return isset($row[$key]) ? $row[$key] : '';
    };

    // Normalizacion defensiva de los campos booleanos.
    $publica = $get('publica');
    if ($publica === 1 || $publica === '1' || $publica === true || $publica === 't') {
        $publica = 'Publico';
    } elseif ($publica === 0 || $publica === '0' || $publica === false || $publica === 'f') {
        $publica = 'Privado';
    }

    $activa = $get('activa');
    if ($activa === 1 || $activa === '1' || $activa === true || $activa === 't') {
        $activa = 'Activa';
    } elseif ($activa === 0 || $activa === '0' || $activa === false || $activa === 'f') {
        $activa = 'Inactiva';
    }

    $seccional = $get('seccional');
    if ($seccional === 1 || $seccional === '1' || $seccional === true || $seccional === 't') {
        $seccional = 'Seccional';
    } elseif ($seccional === 0 || $seccional === '0' || $seccional === false || $seccional === 'f') {
        $seccional = 'Principal';
    }

    // Enlace seguro a la pagina web (sin enlace si esta vacia).
    $pagina = trim((string) $get('pagina_web'));
    $nombre = e($get('nomb_inst'));
    if ($pagina !== '') {
        $href = e('https://' . $pagina);
        $nombreCelda = "<a href='{$href}' class='text-blue-500' target='_blank' rel='noopener noreferrer'>{$nombre}</a>";
    } else {
        $nombreCelda = $nombre;
    }

    $depMun = e($get('nomb_depto')) . '/' . e($get('nomb_munic'));

    $html = "<tr>"
        . "<td class='py-2 px-4 border-b'>" . e($i) . "</td>"
        . "<td class='py-2 px-4 border-b'>{$nombreCelda}</td>"
        . "<td class='py-2 px-4 border-b'>" . e($get('cod_inst')) . "</td>"
        . "<td class='py-2 px-4 border-b'>" . e($get('cod_ies_padre')) . "</td>"
        . "<td class='py-2 px-4 border-b'>" . e($publica) . "</td>"
        . "<td class='py-2 px-4 border-b'>" . e($get('nomb_acad')) . "</td>"
        . "<td class='py-2 px-4 border-b'>" . e($activa) . "</td>"
        . "<td class='py-2 px-4 border-b'>" . e($seccional) . "</td>"
        . "<td class='py-2 px-4 border-b'>" . e($get('nomb_admin')) . "</td>"
        . "<td class='py-2 px-4 border-b'>" . e($get('nomb_norma')) . "</td>"
        . "<td class='py-2 px-4 border-b'>{$depMun}</td>"
        . "</tr>";

    return $html;
}
