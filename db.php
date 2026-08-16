<?php

require_once __DIR__ . "/config.php";

function get_pdo(): PDO {
    global $db_host, $db_port, $db_name, $db_user, $db_password;
    static $pdo = null;
    if ($pdo === null) {
        if ($db_host === '' || $db_user === '') {
            throw new RuntimeException(
                "Database is not configured. Locally, create config.secrets.php (see config.php) " .
                "with your Neon credentials. On Render, set DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASSWORD " .
                "as environment variables."
            );
        }
        $endpoint_id = explode(".", $db_host)[0];
        $conninfo = "host=$db_host port=$db_port dbname=$db_name user=$db_user "
            . "password=$db_password sslmode=require options='endpoint=$endpoint_id'";
        $pdo = new PDO("pgsql:" . $conninfo);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}
