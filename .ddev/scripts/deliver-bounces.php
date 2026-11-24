<?php
// Liefert Bounce-Fixtures (Tests/Fixtures/Bounces/*.eml) per SMTP ins GreenMail-Postfach.
// Aufruf: php deliver-bounces.php [--purge] [--email=a@b.c] [--sendout=N] [--rcptuid=N] [--times=N] [datei.eml ...]
// Platzhalter in den Fixtures: {{EMAIL}} {{MSGID}} {{RCPT_B64}} {{SENDOUT_UID}} {{RCPT_UID}}

$opts = ['email' => getenv('BOUNCE_RECIPIENT') ?: 'recipient1@example.test', 'sendout' => getenv('BOUNCE_SENDOUT_UID') ?: '1', 'rcptuid' => '1', 'times' => '1'];
$files = [];
$purge = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--purge') {
        $purge = true;
    } elseif (preg_match('/^--(email|sendout|rcptuid|times)=(.+)$/', $arg, $m)) {
        $opts[$m[1]] = $m[2];
    } else {
        $files[] = $arg;
    }
}

if ($purge) {
    // GreenMail-API: setzt alle Postfaecher zurueck (Benutzer bleiben bestehen)
    $api = 'http://' . (getenv('BOUNCE_HOST') ?: 'greenmail') . ':8080/api/service/reset';
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'content' => '', 'ignore_errors' => true]]);
    echo 'Postfach geleert: ' . trim((string)@file_get_contents($api, false, $ctx)) . "\n";
    if ($files === []) {
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

$host = getenv('BOUNCE_HOST') ?: 'greenmail';
$port = (int)(getenv('BOUNCE_SMTP_PORT') ?: 3025);
$rcpt = getenv('BOUNCE_ADDRESS') ?: 'bounce@example.test';

function smtpExpect($fp, string $code): void
{
    do {
        $line = fgets($fp, 1024);
        if ($line === false) {
            throw new RuntimeException('SMTP: Verbindung verloren');
        }
    } while (isset($line[3]) && $line[3] === '-');
    if (!str_starts_with($line, $code)) {
        throw new RuntimeException('SMTP: erwartet ' . $code . ', erhalten ' . trim($line));
    }
}

$fp = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 10);
if (!$fp) {
    fwrite(STDERR, "GreenMail nicht erreichbar ($host:$port): $errstr\n");
    exit(1);
}
smtpExpect($fp, '220');

$sent = 0;
foreach ($paths as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Fixture nicht gefunden: $path\n");
        exit(1);
    }
    for ($i = 0; $i < (int)$opts['times']; $i++) {
        $mail = strtr((string)file_get_contents($path), [
            '{{EMAIL}}' => $opts['email'],
            '{{MSGID}}' => bin2hex(random_bytes(8)),
            '{{RCPT_B64}}' => base64_encode($opts['email']),
            '{{SENDOUT_UID}}' => $opts['sendout'],
            '{{RCPT_UID}}' => $opts['rcptuid'],
        ]);
        // CRLF normalisieren und Punkte am Zeilenanfang verdoppeln (SMTP Dot-Stuffing)
        $mail = preg_replace('/\r?\n/', "\r\n", $mail);
        $mail = preg_replace('/^\./m', '..', $mail);

        fwrite($fp, "HELO ddev\r\n");
        smtpExpect($fp, '250');
        fwrite($fp, "MAIL FROM:<mailer-daemon@mx.example.test>\r\n");
        smtpExpect($fp, '250');
        fwrite($fp, "RCPT TO:<$rcpt>\r\n");
        smtpExpect($fp, '250');
        fwrite($fp, "DATA\r\n");
        smtpExpect($fp, '354');
        fwrite($fp, rtrim($mail, "\r\n") . "\r\n.\r\n");
        smtpExpect($fp, '250');
        $sent++;
    }
    echo 'zugestellt: ' . basename($path) . "\n";
}
fwrite($fp, "QUIT\r\n");
fclose($fp);
echo "$sent Mail(s) an $rcpt eingeliefert (Empfaenger-Platzhalter: {$opts['email']}, Sendout: {$opts['sendout']})\n";
