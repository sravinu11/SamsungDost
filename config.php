<?php

/* Compress responses when the client supports it — cuts transfer time for
   the JSON payloads and the dashboard's HTML/JS bundle. Must be set before
   any output is produced, so this has to happen at the very top. */
if (!ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', '1');
}

/* Local-only overrides (never committed — see .gitignore). On Render, the
   equivalent values are set as real environment variables in the dashboard. */
$secretsFile = __DIR__ . "/config.secrets.php";
if (file_exists($secretsFile)) require $secretsFile;

function env(string $key, string $default = ''): string {
    $v = getenv($key);
    if ($v !== false && $v !== '') return $v;
    if (!empty($_SERVER[$key])) return $_SERVER[$key];
    if (!empty($_ENV[$key])) return $_ENV[$key];
    return $default;
}

$db_host     = env('DB_HOST');
$db_port     = env('DB_PORT', '5432');
$db_name     = env('DB_NAME', 'neondb');
$db_user     = env('DB_USER');
$db_password = env('DB_PASSWORD');
