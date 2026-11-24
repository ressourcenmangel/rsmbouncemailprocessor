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

- Recipients that could not be removed from a recipient list are logged as warning instead of being ignored silently
- Postfix bounces: the reason is read from the delivery status (Status, Diagnostic-Code, error line) and no longer from the attached original mail, where a "550" in a Message-ID or text caused a wrong "user unknown"; enhanced status 5.7.x is never "user unknown"

### Removed

- IDE settings and README.pdf from the repository
