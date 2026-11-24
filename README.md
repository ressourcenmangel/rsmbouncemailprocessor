# EXT:rsmbouncemailprocessor

Bounce handling for newsletters sent with [cute_mailing](https://extensions.typo3.org/extension/cute_mailing/):
reads the bounce mailbox via IMAP or POP3, counts the bounce reasons per newsletter and recipient,
removes recipients whose address is invalid, processes `List-Unsubscribe` requests and offers
backend reports.

## Requirements

* TYPO3 13.4 or 14.3, PHP 8.3 or 8.4
* PHP extension `imap` (from PHP 8.4 on only available via PECL)
* cute_mailing ^4.2, taskqueue, TYPO3 system extension scheduler

## Installation

```
composer require ressourcenmangel/rsmbouncemailprocessor
```

Then update the database schema (Admin Tools > Maintenance), create the scheduler tasks
"RSM analyze bounce mail" and "RSM process bounce mail" and check the delete limits.
Details are in the documentation.

## Documentation

* [Documentation/](Documentation/Index.rst) (reStructuredText sources)
* After the first release: <https://docs.typo3.org/p/ressourcenmangel/rsmbouncemailprocessor/main/en-us/>
* [CONTRIBUTING.md](CONTRIBUTING.md) (issues, tests, local DDEV setup)
* [CHANGELOG.md](CHANGELOG.md)

## Credits

This extension was created by Ralph Brugger ([LinkPool2](https://github.com/LinkPool2)) for [RYSM GmbH](https://integral.rysm.com/) (formerly ressourcenmangel).
It is maintained by RYSM GmbH together with Ralph Brugger.
Many thanks to Eike Starkmann from [undkonsorten](https://www.undkonsorten.com/) for developing and extending cute_mailing!
