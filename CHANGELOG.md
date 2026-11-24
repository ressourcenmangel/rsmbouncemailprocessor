# Changelog

All notable changes to this extension are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

Upgrading from 2.2.1: see the chapter "Upgrade" of the documentation.

### Breaking

- Requires TYPO3 13.4 or 14.3 (no longer TYPO3 11), PHP 8.3 or 8.4, cute_mailing ^4.2, undkonsorten/taskqueue and the PHP extension imap
- Spam, filter list and connection bounces no longer remove recipients (limits default to 0), relay errors are no longer "user unknown". The old limits can be restored in TypoScript
- The enhanced status code (5.x.y) decides over the SMTP code: only 5.1.1, 5.1.2 and 5.1.10 are "user unknown", 5.2.2 is "mailbox full", all other codes never remove a recipient
- The module identifier changed, the old one is registered as alias, run the core upgrade wizard to migrate module access rights

### Added

- Setting `settings.maxRemovalsPer24h` (default 50): circuit breaker that stops the removal of recipients and fails the processing task
- Redesigned backend module, recipient reports show the removal date and can be filtered by status
- The analyze and process task fail with a clear message if the TypoScript is not loaded
- Unit and functional tests, DDEV setup and TYPO3 documentation

### Fixed

- The recipient list query of the processing task failed on PostgreSQL and MySQL/MariaDB with ONLY_FULL_GROUP_BY
- Wrong "user unknown" for Postfix bounces whose attached original mail contained "550", and for generic failure texts
- The text of multipart mails was multiplied while reading the mail
- A mail that fails in the analyze task no longer stops the run, it is marked as read and never deleted

### Security

- List-Unsubscribe is only executed if the sender and the recipient uid match the address, rejected requests are never deleted
- The stored mailbox password is no longer sent to the browser, the task form escapes its values

### Removed

- IDE settings and README.pdf from the repository
- TypoScript constants `module.tx_rsmbouncemailprocessor.view.*RootPath` and the view paths, they pointed to a non existing directory
