<?php
// includes/config.php
// Carga de configuracion central: parser de .env, entorno, errores y constantes de BD.

if (!defined('APP_CONFIG_LOADED')) {
    define('APP_CONFIG_LOADED', true);

    /**
     * Parser simple de un archivo .env en la raiz del repo.
     * Lee lineas KEY=VALUE, ignora comentarios (#) y lineas vacias.
     * Inyecta los valores en putenv() y $_ENV (sin sobrescribir lo ya definido).
     */
    $envFile = __DIR__ . '/../.env';
    if (is_file($envFile) && is_readable($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines !== false) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') {
                    continue;
                }
                if (strpos($line, '=') === false) {
                    continue;
                }
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                // Quitar comillas envolventes si existen.
                if (strlen($value) >= 2) {
                    $first = $value[0];
                    $last = $value[strlen($value) - 1];
                    if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                        $value = substr($value, 1, -1);
                    }
                }
                if ($key === '') {
                    continue;
                }
                if (getenv($key) === false) {
                    putenv("$key=$value");
                }
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                }
            }
        }
    }

    /**
     * Helper interno para leer una variable de entorno con valor por defecto.
     */
    if (!function_exists('env_value')) {
        function env_value($key, $default = null) {
            $val = getenv($key);
            if ($val === false || $val === '') {
                if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
                    return $_ENV[$key];
                }
                return $default;
            }
            return $val;
        }
    }

    // Entorno de la aplicacion.
    define('APP_ENV', env_value('APP_ENV', 'development'));

    // Configuracion de errores segun el entorno.
    if (APP_ENV === 'production') {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    } else {
        ini_set('display_errors', '1');
        error_reporting(E_ALL);
    }

    // Constantes de base de datos (leidas de .env; el fallback es solo para
    // desarrollo local sin .env). En Supabase: host del pooler, dbname=postgres,
    // user 'postgres.<project-ref>' y sslmode=require.
    define('DB_HOST', env_value('DB_HOST', 'localhost'));
    define('DB_PORT', (int) env_value('DB_PORT', 5432));
    define('DB_NAME', env_value('DB_NAME', 'postgres'));
    define('DB_USER', env_value('DB_USER', 'postgres'));
    define('DB_PASS', env_value('DB_PASS', ''));
    define('DB_SSLMODE', env_value('DB_SSLMODE', 'prefer'));

    // URL base opcional.
    $baseUrl = env_value('BASE_URL', null);
    if ($baseUrl !== null) {
        define('BASE_URL', $baseUrl);
    }
}
