<?php

if (session_status() === PHP_SESSION_NONE) session_start();

function require_login(): void {
    if (empty($_SESSION['username'])) {
        header("Location: login.php");
        exit;
    }
}

function require_login_json(): void {
    if (empty($_SESSION['username'])) {
        http_response_code(401);
        header("Content-Type: application/json");
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
}

/* ESSCI/TSSC sessions are hard-locked to their own partner, server-side,
   regardless of what the client sends — SAMSUNGADMIN (role ALL) is unrestricted. */
function apply_role_lock(array $activeFilters): array {
    if ($_SESSION['role'] !== 'ALL') {
        $activeFilters['partner'] = [$_SESSION['role']];
    }
    return $activeFilters;
}
