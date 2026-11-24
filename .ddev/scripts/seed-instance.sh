#!/bin/bash
# Seed-Daten fuer eine Instanz: Ordner, tt_address-Empfaenger, Empfaengerliste, Newsletter, Sendout.
# Aufruf im Container: seed-instance.sh <13|14>
# Idempotent: feste UIDs, REPLACE INTO. Die Bounce-Fixtures passen zu recipient1..5@example.test, Sendout 1.
set -euo pipefail

VERSION="v${1:?Aufruf: seed-instance.sh <13|14>}"
NOW=$(date +%s)
LIST_TYPE='Undkonsorten\\CuteMailingTtAddress\\Domain\\Model\\TtAddressRecipientList'

mysql -h db -u root -p"${TYPO3_DB_PASSWORD}" "${VERSION}" <<SQL
DELETE FROM tt_address WHERE uid BETWEEN 1 AND 5;
REPLACE INTO pages (uid, pid, title, doktype, slug, tstamp, crdate, sorting) VALUES
  (2, 1, 'Newsletter', 254, '/newsletter', ${NOW}, ${NOW}, 256),
  (3, 1, 'Empfaenger', 254, '/empfaenger', ${NOW}, ${NOW}, 512);
REPLACE INTO tt_address (uid, pid, name, first_name, last_name, email, tstamp, crdate) VALUES
  (1, 3, 'Test Eins', 'Test', 'Eins', 'recipient1@example.test', ${NOW}, ${NOW}),
  (2, 3, 'Test Zwei', 'Test', 'Zwei', 'recipient2@example.test', ${NOW}, ${NOW}),
  (3, 3, 'Test Drei', 'Test', 'Drei', 'recipient3@example.test', ${NOW}, ${NOW}),
  (4, 3, 'Test Vier', 'Test', 'Vier', 'recipient4@example.test', ${NOW}, ${NOW}),
  (5, 3, 'Test Fuenf', 'Test', 'Fuenf', 'recipient5@example.test', ${NOW}, ${NOW});
REPLACE INTO tx_cutemailing_domain_model_recipientlist (uid, pid, name, record_type, recipient_list_page, tstamp, crdate) VALUES
  (1, 2, 'tt_address Testliste', '${LIST_TYPE}', 3, ${NOW}, ${NOW});
REPLACE INTO tx_cutemailing_domain_model_newsletter (uid, pid, title, subject, sender, sender_name, return_path, newsletter_page, recipient_list, tstamp, crdate) VALUES
  (1, 2, 'Bounce-Test Newsletter', 'Bounce-Test', 'newsletter@example.test', 'RSM Bounce Test', 'bounce@example.test', 1, 1, ${NOW}, ${NOW});
REPLACE INTO tx_cutemailing_domain_model_sendout (uid, pid, newsletter, total, tstamp, crdate) VALUES
  (1, 2, 1, 5, ${NOW}, ${NOW});
SQL
echo "Seed fuer ${VERSION}: 5 tt_address-Empfaenger (Ordner 3), Liste 1, Newsletter 1, Sendout 1 (Ordner 2)"
