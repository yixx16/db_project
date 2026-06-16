<?php
// Archivo de conexion a la base de datos

require_once __DIR__ . '/includes/config.php';

/**
 * Conectar a la base de datos.
 *
 * @param string $username El nombre de usuario.
 * @param string $password La contrasena del usuario.
 * @return PDO La conexion a la base de datos.
 */
function connect($username, $password) {
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME;
        $conn = new PDO($dsn, $username, $password);
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
 * Conectar a la base de datos como usuario por defecto.
 *
 * @return PDO La conexion a la base de datos.
 */
function connectDefault() {
    return connect(DB_DEFAULT_USER, DB_DEFAULT_PASS);
}

/**
 * Conectar a la base de datos con usuario y contrasena especificos.
 * Verifica las credenciales contra privado.usuarios usando una consulta
 * preparada (parametrizada) para evitar inyeccion SQL.
 *
 * @param string $username El nombre de usuario (correo).
 * @param string $password La contrasena del usuario.
 * @return PDO La conexion a la base de datos.
 */
function connectWithCredentials($username, $password) {
    $conn = connect(DB_ADMIN_USER, DB_ADMIN_PASS);
    $stmt = $conn->prepare("SELECT password FROM privado.usuarios WHERE correo_usuario = :correo");
    $stmt->bindParam(':correo', $username, PDO::PARAM_STR);
    $stmt->execute();
    $hashed_password_db = $stmt->fetchColumn();
    $conn = null;
    if ($hashed_password_db !== false && password_verify($password, $hashed_password_db)) {
        $dbpassword = $hashed_password_db;
        return connect($username, $dbpassword);
    } else {
        echo "Contrasena incorrecta";
        exit();
    }
}


$conn = connectDefault();
