<?php
// Liefert Bounce-Fixtures (Tests/Fixtures/Bounces/*.eml) per IMAP APPEND ins Dovecot-Postfach.
// Aufruf: php deliver-bounces.php [--purge] [--email=a@b.c] [--from=a@b.c] [--sendout=N] [--rcptuid=N] [--times=N] [datei.eml ...]
// Platzhalter in den Fixtures: {{EMAIL}} {{FROM}} {{MSGID}} {{RCPT_B64}} {{SENDOUT_UID}} {{RCPT_UID}}

$opts = ['email' => getenv('BOUNCE_RECIPIENT') ?: 'recipient1@example.test', 'sendout' => getenv('BOUNCE_SENDOUT_UID') ?: '1', 'rcptuid' => '1', 'times' => '1'];
$files = [];
$purge = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--purge') {
        $purge = true;
    } elseif (preg_match('/^--(email|from|sendout|rcptuid|times)=(.+)$/', $arg, $m)) {
        $opts[$m[1]] = $m[2];
    } else {
        $files[] = $arg;
    }
}

$host = getenv('BOUNCE_HOST') ?: 'dovecot';
$port = (int)(getenv('BOUNCE_IMAP_PORT') ?: 143);
$user = getenv('BOUNCE_USER') ?: 'bounce';
$pass = getenv('BOUNCE_PASSWORD') ?: 'bounce';
// IMAP ohne TLS (nur lokales DDEV-Netz)
$mailbox = sprintf('{%s:%d/imap/notls}INBOX', $host, $port);

$imap = @imap_open($mailbox, $user, $pass, 0, 1);
if (!$imap) {
    fwrite(STDERR, "Dovecot nicht erreichbar ($host:$port): " . imap_last_error() . "\n");
    exit(1);
}
// Hinweis "insecure server advertised AUTH=PLAIN" (kein TLS, bewusst) nicht als Notice ausgeben
imap_errors();
imap_alerts();

if ($purge) {
    // alle Mails loeschen und expungen
    $count = imap_num_msg($imap);
    if ($count > 0) {
        imap_delete($imap, '1:*');
        imap_expunge($imap);
    }
    echo "Postfach geleert: $count Mail(s) entfernt\n";
    if ($files === []) {
        imap_close($imap);
        exit(0);
    }
}

$dir = '/var/www/' . getenv('EXTENSION_KEY') . '/Tests/Fixtures/Bounces';
$paths = [];
if ($files === []) {
    $paths = glob($dir . '/*.eml');
} else {
    foreach ($files as $f) {
        $paths[] = is_file($f) ? $f : $dir . '/' . basename($f);
    }
}

$rcpt = getenv('BOUNCE_ADDRESS') ?: $user;

$sent = 0;
foreach ($paths as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Fixture nicht gefunden: $path\n");
        exit(1);
    }
    for ($i = 0; $i < (int)$opts['times']; $i++) {
        $mail = strtr((string)file_get_contents($path), [
            '{{EMAIL}}' => $opts['email'],
            // Absender der Mail, Standard: die Empfaengeradresse (--from fuer gefaelschte Absender)
            '{{FROM}}' => $opts['from'] ?? $opts['email'],
            '{{MSGID}}' => bin2hex(random_bytes(8)),
            '{{RCPT_B64}}' => base64_encode($opts['email']),
            '{{SENDOUT_UID}}' => $opts['sendout'],
            '{{RCPT_UID}}' => $opts['rcptuid'],
        ]);
        // CRLF normalisieren, dann per APPEND in die INBOX legen
        $mail = preg_replace('/\r?\n/', "\r\n", $mail);
        if (!imap_append($imap, $mailbox, $mail)) {
            fwrite(STDERR, 'IMAP APPEND fehlgeschlagen: ' . imap_last_error() . "\n");
            exit(1);
        }
        $sent++;
    }
    echo 'zugestellt: ' . basename($path) . "\n";
}
imap_close($imap);
echo "$sent Mail(s) an $rcpt eingeliefert (Empfaenger-Platzhalter: {$opts['email']}, Sendout: {$opts['sendout']})\n";
