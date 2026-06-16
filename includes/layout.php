<?php
// includes/layout.php
// Helpers de layout: cabecera y pie de pagina con assets unificados.

/**
 * Emite la cabecera HTML del documento con assets unificados.
 * Todas las paginas viven un nivel bajo public/, por lo que ../media/ es valido.
 *
 * @param string $title Titulo de la pagina (se escapa).
 * @param string $extra HTML adicional a insertar dentro de <head>.
 */
function render_head(string $title, string $extra = '') {
    echo '<!DOCTYPE html>' . "\n";
    echo '<html lang="es">' . "\n";
    echo '<head>' . "\n";
    echo '    <meta charset="UTF-8">' . "\n";
    echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    echo '    <title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>' . "\n";
    echo '    <script src="../media/style.js"></script>' . "\n";
    echo '    <link rel="stylesheet" href="../media/style.css">' . "\n";
    if ($extra !== '') {
        echo $extra . "\n";
    }
    echo '</head>' . "\n";
    echo '<body class="bg-gray-100">' . "\n";
}

/**
 * Emite el pie del documento HTML.
 */
function render_footer() {
    echo '</body>' . "\n";
    echo '</html>' . "\n";
}
