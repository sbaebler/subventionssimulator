<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

const PASSWORT_RESET_GUELTIGKEIT_MINUTEN = 60;

function passwort_reset_anfordern(string $emailOderBenutzername): void
{
    $eingabe = trim($emailOderBenutzername);
    if ($eingabe === '') {
        return;
    }

    $stmt = db()->prepare(
        'SELECT id, anzeigename, email FROM benutzer
         WHERE (benutzername = ? OR email = ?) AND aktiv = 1'
    );
    $stmt->execute([$eingabe, $eingabe]);
    $benutzer = $stmt->fetch();

    // Anti-Enumeration: kein Signal nach aussen, ob ein Treffer gefunden wurde.
    if (!$benutzer || empty($benutzer['email'])) {
        return;
    }

    db()->prepare('DELETE FROM passwort_reset_tokens WHERE benutzer_id = ? AND eingeloest_am IS NULL')
        ->execute([$benutzer['id']]);

    $rawToken  = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);

    $stmt = db()->prepare(
        'INSERT INTO passwort_reset_tokens (benutzer_id, token_hash, laeuft_ab_am)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
    );
    $stmt->execute([$benutzer['id'], $tokenHash, PASSWORT_RESET_GUELTIGKEIT_MINUTEN]);

    mailer_passwort_reset_senden($benutzer['email'], $benutzer['anzeigename'], $rawToken);
}

function passwort_reset_gueltig(string $token): ?array
{
    if ($token === '') {
        return null;
    }
    $tokenHash = hash('sha256', $token);

    $stmt = db()->prepare(
        'SELECT prt.id AS token_id, prt.benutzer_id, b.anzeigename, b.benutzername
         FROM passwort_reset_tokens prt
         JOIN benutzer b ON b.id = prt.benutzer_id
         WHERE prt.token_hash = ?
           AND prt.eingeloest_am IS NULL
           AND prt.laeuft_ab_am >= NOW()
           AND b.aktiv = 1'
    );
    $stmt->execute([$tokenHash]);
    return $stmt->fetch() ?: null;
}

function passwort_reset_einloesen(string $token, string $neuesPasswort): bool
{
    $reset = passwort_reset_gueltig($token);
    if (!$reset) {
        return false;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE benutzer SET passwort_hash = ? WHERE id = ?')
            ->execute([password_hash($neuesPasswort, PASSWORD_DEFAULT), $reset['benutzer_id']]);
        $pdo->prepare('UPDATE passwort_reset_tokens SET eingeloest_am = NOW() WHERE id = ?')
            ->execute([$reset['token_id']]);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[Subventionssimulator] passwort_reset_einloesen() Fehler: ' . $e->getMessage());
        return false;
    }
}
