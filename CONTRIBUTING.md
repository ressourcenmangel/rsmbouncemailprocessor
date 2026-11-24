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
- A local DDEV setup with TYPO3 13 and 14 and an IMAP test mailbox is described in the README.

## AI assistance

AI tools may be used to write code, tests or reviews. The person submitting the change
is responsible for it and has reviewed it. Commits created with AI assistance carry a
`Co-Authored-By` trailer naming the tool.

## Maintainers

- [RYSM GmbH](https://integral.rysm.com/)
- Ralph Brugger ([LinkPool2](https://github.com/LinkPool2)), original author
