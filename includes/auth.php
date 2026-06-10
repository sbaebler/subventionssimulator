<?php

function auth_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function auth_eingeloggt(): bool
{
    auth_start();
    return !empty($_SESSION['eingeloggt']);
}

function auth_erforderlich(): void
{
    if (!auth_eingeloggt()) {
        $ziel = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: /login.php?weiter=' . $ziel);
        exit;
    }
}

function auth_anmelden(string $benutzer, string $passwort): bool
{
    if (!defined('AUTH_USER') || !defined('AUTH_PASS_HASH')) {
        return false;
    }
    if ($benutzer !== AUTH_USER) {
        return false;
    }
    if (!password_verify($passwort, AUTH_PASS_HASH)) {
        return false;
    }
    auth_start();
    session_regenerate_id(true);
    $_SESSION['eingeloggt'] = true;
    return true;
}

function auth_abmelden(): void
{
    auth_start();
    $_SESSION = [];
    session_destroy();
}
