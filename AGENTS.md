# Repository Guidelines

Guidance for coding agents working on EXT:rsmbouncemailprocessor. Personal or machine-specific
preferences do not belong here, put them in an untracked `CLAUDE.local.md` (already gitignored).

## Context

- One Composer package, `ressourcenmangel/rsmbouncemailprocessor`, namespace `RSM\Rsmbouncemailprocessor\`
  (`Classes/`) and `RSM\Rsmbouncemailprocessor\Tests\` (`Tests/`), extension key `rsmbouncemailprocessor`.
  Package, namespace and key stay as they are, although the company was renamed from ressourcenmangel
  to RYSM GmbH.
- **One branch serves TYPO3 13.4 and 14.3** on PHP 8.3 to 8.4. Every change has to work on both cores;
  `.github/workflows/ci.yml` defines the matrix that decides.
- The extension needs cute_mailing (`undkonsorten/typo3-cute-mailing`, ^4.2) and taskqueue.
- Issues and code review happen on GitHub, <https://github.com/ressourcenmangel/rsmbouncemailprocessor>.
  There is no Gerrit, `Change-Id` trailers do not apply.
- Documentation sources are in `Documentation/` (reStructuredText). Humans: see `CONTRIBUTING.md`.

## Working mode

- Reproduce a bug with a failing test first, then fix it and show the test turning green. Exception:
  purely mechanical fixes without testable behaviour (typo in a label, comment, documentation).
- Commit messages in English, TYPO3 style: `[BUGFIX]`, `[FEATURE]`, `[TASK]`, `[DOCS]`, subject in
  imperative, body explains why. Changed behaviour or breaking changes get `[!!!]` in front.
- `CHANGELOG.md` only gets entries that matter to users (what changes when upgrading).
- Commits created with AI assistance carry a `Co-Authored-By:` trailer naming the tool (see
  `CONTRIBUTING.md`).
- Do not use em dashes or en dashes as punctuation in texts and documentation.
- Only comment code when the comment adds meaning.

## Critical rules for the removal path

The code that removes recipients (`Classes/Task/AnalyzeBounceMail.php`, `ProcessBounceMail.php`)
can damage a customer's lists. Treat it with care:

- **Fail closed.** If a bounce, a sender or a recipient cannot be assigned with certainty, remove nobody.
- Only hard errors that concern the address itself remove recipients (user unknown, mailbox full,
  header error, message size). Spam, filter list, connection and relay errors point to the sending
  side and must not remove recipients by default.
- Do not weaken the circuit breaker `maxRemovalsPer24h` and keep its default.
- Never write e-mail addresses to logs. Log uids, counts and error classes.
- Rejected or failed List-Unsubscribe requests are never deleted from the mailbox.
- Do not describe security issues in public issues or pull requests. Use private vulnerability
  reporting or contact the maintainers directly.

## Commands

Everything runs through `Build/Scripts/runTests.sh` (containers, no local PHP needed). Use `-b docker`
(the default is podman). `-h` lists all suites.

```bash
# install a test system: remove .Build first when switching the TYPO3 version
rm -rf .Build
Build/Scripts/runTests.sh -b docker -s composerUpdateMax -t 14.3 -p 8.3    # or -t 13.4

Build/Scripts/runTests.sh -b docker -s lintPhp -p 8.3
Build/Scripts/runTests.sh -b docker -s unit -p 8.3
Build/Scripts/runTests.sh -b docker -s functional -p 8.3                  # sqlite
Build/Scripts/runTests.sh -b docker -s functional -d mariadb -p 8.3       # or -d postgres

# documentation, must render without warnings (output: Documentation-GENERATED-temp/)
Build/Scripts/runTests.sh -b docker -s executeRstRendering

# local test system with IMAP mailbox (needs DDEV)
ddev start && ddev install-all            # TYPO3 13 and 14, or install-v13 / install-v14
ddev bounces --purge
ddev bounces --email=recipient1@example.test --times=2 550-user-unknown.eml
ddev exec "cd /var/www/html/v14 && vendor/bin/typo3 scheduler:run --task=1 -f"   # analyze
ddev exec "cd /var/www/html/v14 && vendor/bin/typo3 scheduler:run --task=2 -f"   # process

# documentation screenshots (DDEV instance v14), see Build/Screenshots/README.md
Build/Screenshots/screenshots.sh
```

## Project structure

- `Classes/` with `Controller/` (backend module), `Domain/` (models, repositories), `Service/`,
  `Task/` (the three scheduler tasks), `Utility/` (IMAP and mail parsing), `ViewHelpers/`.
- `Configuration/` with `Backend/Modules.php`, `TCA/`, `TypoScript/setup.typoscript` (all settings
  and defaults), `Services.yaml`, `Icons.php`.
- `Resources/Private/` with `Templates/`, `Language/`.
- `Tests/Unit`, `Tests/Functional`, fixtures of bounce mails in `Tests/Fixtures/Bounces/`.
- `Documentation/` (render-guides), `Build/` (`Scripts/runTests.sh`, `phpunit/`, `Screenshots/`),
  `.ddev/` (local test system), `.github/workflows/ci.yml`.
- Root: `ext_emconf.php`, `ext_localconf.php`, `ext_tables.sql`, `composer.json`, `CHANGELOG.md`.

## Known pitfalls

- The test images have no `ext-imap`. Tests that need it are skipped there. The IMAP end-to-end
  path is only tested with DDEV and its Dovecot mailbox.
- GreenMail cannot deliver `message/rfc822` parts via IMAP, which is why the DDEV setup uses Dovecot.
- `runTests.sh` changes `composer.json` temporarily (platform, TYPO3 version). Check `git diff` before
  committing and remove `.Build` when you switch the TYPO3 version.
- Scheduler tasks run without a request. The TypoScript comes from the first root page, with several
  sites only that site is read (`Classes/Task/LazyServicesTrait.php`).
- cute_mailing line separated recipient lists cannot remove recipients, the process task marks such
  reports as removed and logs a warning.
- Tasks are serialized by the scheduler: do not inject services into tasks, fetch them lazily
  (`LazyServicesTrait`).
- The language files in `Resources/Private/Language/` are the English sources; `de.locallang.xlf`
  is the German translation.
