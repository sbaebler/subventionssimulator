<?php

function mailer_senden(string $empfaengerEmail, string $betreff, string $text): bool
{
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>';
    $headers[] = 'Reply-To: ' . MAIL_FROM_ADDRESS;

    $encodedBetreff = mb_encode_mimeheader($betreff, 'UTF-8', 'B');

    $erfolg = mail($empfaengerEmail, $encodedBetreff, $text, implode("\r\n", $headers));
    if (!$erfolg) {
        error_log('[Subventionssimulator] mail() fehlgeschlagen an ' . $empfaengerEmail);
    }
    return $erfolg;
}

function mailer_passwort_reset_senden(string $empfaengerEmail, string $anzeigename, string $rawToken): bool
{
    $link    = rtrim(APP_BASE_URL, '/') . '/passwort-zuruecksetzen.php?token=' . urlencode($rawToken);
    $betreff = APP_NAME . ': Passwort zurücksetzen';
    $text    = "Hallo {$anzeigename}\n\n"
             . "Du hast ein neues Passwort für " . APP_NAME . " angefordert.\n"
             . "Klicke auf den folgenden Link, um ein neues Passwort zu setzen "
             . "(gültig für " . PASSWORT_RESET_GUELTIGKEIT_MINUTEN . " Minuten):\n\n"
             . "{$link}\n\n"
             . "Falls du diese Anfrage nicht gestellt hast, kannst du diese E-Mail ignorieren.\n";

    return mailer_senden($empfaengerEmail, $betreff, $text);
}
