<?php
session_start();
define('SESSION_TIMEOUT', 1800); // 30 minutos

function checkSession(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /gestao_academica/auth/login.php');
        exit;
    }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: /gestao_academica/auth/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function requireRole(string ...$roles): void {
    checkSession();
    if (!in_array($_SESSION['perfil'], $roles, true)) {
        http_response_code(403);
        die("Acesso negado.");
    }
}
