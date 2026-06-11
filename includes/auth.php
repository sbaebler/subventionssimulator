<?php
require_once __DIR__ . '/db.php';

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

function auth_benutzer_id(): int
{
    return (int)($_SESSION['benutzer_id'] ?? 0);
}

function auth_anzeigename(): string
{
    return $_SESSION['anzeigename'] ?? '';
}

function auth_anmelden(string $benutzer, string $passwort): bool
{
    $stmt = db()->prepare(
        'SELECT id, anzeigename, passwort_hash FROM benutzer WHERE benutzername = ? AND aktiv = 1'
    );
    $stmt->execute([$benutzer]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($passwort, $row['passwort_hash'])) {
        return false;
    }

    auth_start();
    session_regenerate_id(true);
    $_SESSION['eingeloggt']   = true;
    $_SESSION['benutzer_id']  = $row['id'];
    $_SESSION['benutzername'] = $benutzer;
    $_SESSION['anzeigename']  = $row['anzeigename'];
    return true;
}

function auth_abmelden(): void
{
    auth_start();
    $_SESSION = [];
    session_destroy();
}
