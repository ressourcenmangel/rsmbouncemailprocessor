# Changelog

All notable changes to this extension are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- Setting `settings.maxRemovalsPerRun` (default 50): the processing task fails without removing anyone when more recipients would be removed in one run
- DDEV setup for local testing with TYPO3 13 and 14

### Changed

- Spam, filter list and connection bounces and relay errors no longer remove recipients (limits default to 0, relay errors are not "user unknown"), see README for the old behaviour

### Fixed

- Processing task: the recipient list query failed on PostgreSQL and MySQL/MariaDB with ONLY_FULL_GROUP_BY (existing since 2.2.1)
- Recipients that could not be removed from a recipient list are logged as warning instead of being ignored silently
- Postfix bounces: the reason is read from the delivery status (Status, Diagnostic-Code, error line) and no longer from the attached original mail, where a "550" in a Message-ID or text caused a wrong "user unknown"; enhanced status 5.7.x is never "user unknown"
- Generic failure texts ("could not be delivered to one or more recipients" and similar) no longer count as user unknown, the delivery status (Status, Diagnostic-Code) of every bounce is evaluated first

### Security

- Unsubscribes via List-Unsubscribe are only executed if the sender of the mail and the recipient uid match the address to unsubscribe, otherwise nothing is removed and a warning without addresses is logged

### Removed

- IDE settings and README.pdf from the repository
