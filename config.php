<?php

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
