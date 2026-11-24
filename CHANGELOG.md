# Changelog

All notable changes to this extension are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- Setting `settings.maxRemovalsPer24h` (default 50): the processing task fails without removing anyone when the recipients that would be removed now plus those removed in the last 24 hours exceed the limit
- DDEV setup for local testing with TYPO3 13 and 14

### Changed

- Spam, filter list and connection bounces and relay errors no longer remove recipients (limits default to 0, relay errors are not "user unknown"), see README for the old behaviour
- Bounce reasons: the enhanced status code (5.x.y) decides over the 3-digit SMTP code. Only 5.1.1, 5.1.2 and 5.1.10 are "user unknown", 5.2.2 is "mailbox full", all other enhanced codes (for example 5.1.8, 5.4.x, 5.7.x) are an unknown reason and never remove a recipient. A bare "550" without enhanced status is inconclusive and left to the reason texts
- Requires cute_mailing ^4.2, undkonsorten/taskqueue and the PHP extension imap (composer and ext_emconf), extension category is now "module"

### Fixed

- Processing task: the recipient list query failed on PostgreSQL and MySQL/MariaDB with ONLY_FULL_GROUP_BY (existing since 2.2.1)
- Recipients that could not be removed from a recipient list are logged as warning instead of being ignored silently
- Postfix bounces: the reason is read from the delivery status (Status, Diagnostic-Code, error line) and no longer from the attached original mail, where a "550" in a Message-ID or text caused a wrong "user unknown"; enhanced status 5.7.x is never "user unknown"
- Generic failure texts ("could not be delivered to one or more recipients" and similar) no longer count as user unknown, the delivery status (Status, Diagnostic-Code) of every bounce is evaluated first
- Analyze task: a mail whose processing fails no longer stops the whole run, it is set to seen (never deleted) and a warning without addresses is logged
- Analyze task: the text of multipart mails was multiplied with each part when reading the mail (existing since 2.2.1)
- List-Unsubscribe: rejected or failed unsubscribe mails are never deleted (not even with "delete always"), a recipient that cannot be removed is logged as warning without address
- Processing task: recipient lists that cannot remove recipients (for example the cute_mailing line separated list) no longer let reports pile up, they are marked as removed with one warning per report and are not counted by the circuit breaker
- Processing task: only the counter columns are accepted as keys of `settings.deletelimits` (other keys are ignored with a warning), limits are read as integers
- Analyze and process task fail with a clear error when the TypoScript of the extension is not loaded (`settings.reasontext` or `settings.deletelimits` missing) instead of silently doing nothing, include the TypoScript on the root page of the site
- Backend module: the module identifier of 2.x is registered as alias so the core upgrade wizard migrates the module access rights of backend users and groups

### Security

- Unsubscribes via List-Unsubscribe are only executed if the sender of the mail and the recipient uid match the address to unsubscribe, otherwise nothing is removed and a warning without addresses is logged
- Scheduler form of the analyze task: values are HTML escaped, the stored mailbox password is no longer sent to the browser (empty input keeps the stored password)

### Removed

- IDE settings and README.pdf from the repository
