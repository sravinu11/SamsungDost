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
        /* Persistent connection: the Neon TLS handshake alone costs ~1s, so
           reusing the connection across requests on the same worker process
           avoids paying that on every single page load. */
        $pdo = new PDO("pgsql:" . $conninfo, null, null, [PDO::ATTR_PERSISTENT => true]);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

/* Always read live from information_schema rather than hardcoding the column
   list — dost2026's columns get edited directly in Neon from time to time,
   and a stale hardcoded list breaks every query that uses it. The lookup
   itself is cached to a temp file for a few minutes so it doesn't cost a
   network round trip to Neon on every single request. */
function get_table_columns(PDO $pdo, string $table): array {
    static $cache = [];
    if (isset($cache[$table])) return $cache[$table];

    $cacheFile = sys_get_temp_dir() . "/dost2026_columns_" . preg_replace('/[^a-z0-9_]/i', '', $table) . ".json";
    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached) && $cached) {
            $cache[$table] = $cached;
            return $cached;
        }
    }

    $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns
                            WHERE table_schema='public' AND table_name=:t
                            ORDER BY ordinal_position");
    $stmt->execute([':t' => $table]);
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $cache[$table] = $cols;
    @file_put_contents($cacheFile, json_encode($cols));
    return $cols;
}
