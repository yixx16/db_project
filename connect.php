<?php
// Archivo de conexion a la base de datos.
// Las credenciales se leen de variables de entorno (.env) via includes/config.php.

require_once __DIR__ . '/includes/config.php';

/**
 * Construye el DSN de PDO a partir de las variables de entorno.
 *
 * @return string
 */
function dbDsn() {
    $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
    if (DB_SSLMODE !== '') {
        $dsn .= ';sslmode=' . DB_SSLMODE;
    }
    return $dsn;
}

/**
 * Conectar a la base de datos con un usuario y contrasena dados.
 *
 * @param string $username
 * @param string $password
 * @return PDO
 */
function connect($username, $password) {
    try {
        $conn = new PDO(dbDsn(), $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        if (APP_ENV === 'production') {
            error_log('Error connecting to the database: ' . $e->getMessage());
            echo 'No fue posible conectar con la base de datos. Intente mas tarde.';
        } else {
            echo 'Error connecting to the database: <br>' . $e->getMessage();
        }
        exit();
    }
}

/**
 * Conexion por defecto de la aplicacion (credenciales DB_USER/DB_PASS de .env).
 *
 * @return PDO
 */
function connectDefault() {
    return connect(DB_USER, DB_PASS);
}

/**
 * Verifica las credenciales de un usuario de la aplicacion contra
 * privado.usuarios (con sentencia preparada) y devuelve la conexion por
 * defecto si son validas.
 *
 * @param string $username Correo del usuario.
 * @param string $password Contrasena en texto plano.
 * @return PDO
 */
function connectWithCredentials($username, $password) {
    $conn = connectDefault();
    $stmt = $conn->prepare('SELECT password FROM privado.usuarios WHERE correo_usuario = :correo');
    $stmt->bindParam(':correo', $username, PDO::PARAM_STR);
    $stmt->execute();
    $hash = $stmt->fetchColumn();
    if ($hash !== false && password_verify($password, $hash)) {
        return $conn;
    }
    echo 'Contrasena incorrecta';
    exit();
}

$conn = connectDefault();
