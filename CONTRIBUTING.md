# Contributing

Thanks for helping to improve this extension.

## Issues

Please report bugs and feature requests in the
[issue tracker](https://github.com/ressourcenmangel/rsmbouncemailprocessor/issues).
For bounce detection problems, an anonymised copy of the bounce mail helps a lot.

## Pull requests

- Create pull requests against `main`.
- Use the TYPO3 commit message format: `[FEATURE]`, `[BUGFIX]`, `[TASK]`, `[DOCS]`,
  and the prefix `[!!!]` for breaking changes or changed behaviour.
- Add or adapt tests. Run them with `Build/Scripts/runTests.sh -s unit` and
  `Build/Scripts/runTests.sh -s functional` (see `-h` for TYPO3 and PHP versions).
- Add a short entry to `CHANGELOG.md` for changes that matter to users.
- Render the documentation with `Build/Scripts/runTests.sh -b docker -s executeRstRendering`; it must finish without warnings (output in `Documentation-GENERATED-temp/`, ignored by git).
- A local DDEV setup with TYPO3 13 and 14 and an IMAP test mailbox is described below.

## Local development

The DDEV setup runs two parallel TYPO3 instances (13.4 and 14.3) with cute_mailing, the tt_address
connector, the scheduler and this extension (Composer path repository, symlink to the repository:
code changes take effect immediately). Requirements: Docker and DDEV. The instances live in Docker
volumes, not in the repository.

```
ddev start
ddev install-all      # or ddev install-v13 / ddev install-v14 (each a complete reinstall)
```

| Service | URL |
| --- | --- |
| TYPO3 13.4 | https://v13.rsmbouncemailprocessor.ddev.site/typo3/ |
| TYPO3 14.3 | https://v14.rsmbouncemailprocessor.ddev.site/typo3/ |
| Mailpit (outgoing mail) | https://rsmbouncemailprocessor.ddev.site:8026 |

Local test credentials (only for this setup, never use them in production):

* TYPO3 backend and install tool: `admin` / `Password:joh316`
* Bounce mailbox (Dovecot 2.4, IMAP only): host `dovecot`, port `143`, no SSL/TLS, plain text login,
  user `bounce`, password `bounce` (change with the environment variables `BOUNCE_USER` and
  `BOUNCE_PASSWORD`). The mailbox lives in RAM and is empty after `ddev restart`.

Outgoing TYPO3 mail (SMTP `localhost:1025`) ends up in Mailpit. The instances contain seed data:
folder 2 (newsletter, recipient list 1, newsletter 1, sendout 1) and folder 3 (tt_address recipients
`recipient1@example.test` to `recipient5@example.test`). The scheduler tasks "RSM analyze bounce mail"
(uid 1, IMAP against Dovecot) and "RSM process bounce mail" (uid 2) exist. Task parameters of the
analyze task: server `dovecot`, port `143`, user `bounce`, password `bounce`, service `imap`.

### Bounce test

```
ddev bounces --purge                                    # empty the mailbox (IMAP delete + expunge)
ddev bounces --email=recipient1@example.test --times=2 550-user-unknown.eml
ddev bounces --email=recipient2@example.test postfix-550.eml 551-mailbox-full.eml out-of-office.eml
ddev bounces                                            # deliver all fixtures of Tests/Fixtures/Bounces
ddev exec "cd /var/www/html/v13 && vendor/bin/typo3 scheduler:run --task=1 -f"   # analyze mails, create reports
ddev exec "cd /var/www/html/v13 && vendor/bin/typo3 scheduler:run --task=2 -f"   # check limits, remove recipients
```

`ddev bounces` delivers by IMAP APPEND and replaces the placeholders of the fixtures (`--email`,
`--sendout`, default 1, `--rcptuid`). `--from=` sets a different sender (default: `--email`), for
example for a forged unsubscribe mail with `list-unsubscribe.eml`. After 2 "550 user unknown" bounces
for `recipient1@example.test`, task 2 removes the address from the tt_address list and marks the
recipient report as `removed`. Use `v14` instead of `v13` for TYPO3 14. Both instances read the same
mailbox and consume the mails, so deliver them again before testing in the second instance.
Check the result in the backend module or with
`ddev mysql -e "select * from v13.tx_rsmbouncemailprocessor_domain_model_bouncereport"`.

### Screenshots for the documentation

See `Build/Screenshots/README.md`.

## AI assistance

AI tools may be used to write code, tests or reviews. The person submitting the change
is responsible for it and has reviewed it. Commits created with AI assistance carry a
`Co-Authored-By` trailer naming the tool.

## Maintainers

- [RYSM GmbH](https://integral.rysm.com/)
- Ralph Brugger ([LinkPool2](https://github.com/LinkPool2)), original author
