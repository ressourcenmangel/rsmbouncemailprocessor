..  _upgrade:

=======
Upgrade
=======

Version 4.0 supports TYPO3 13.4 and 14.3 with PHP 8.3 and 8.4. There is no
version for TYPO3 12. Version 2.x (TYPO3 11) is no longer maintained.

From 2.x to 4.0
===============

#. **Upgrade TYPO3 in two steps:** TYPO3 11 to 12, then 12 to 13 or 14. Because
   there is no version of the extension for TYPO3 12, deactivate the extension
   during the intermediate step on TYPO3 12 and activate it again after the
   upgrade to 13 or 14. Follow the
   `TYPO3 upgrade guide <https://docs.typo3.org/m/typo3/guide-installation/main/en-us/Upgrade/Index.html>`__
   for the core.
#. **Update the requirements:** PHP 8.3 or higher, the PHP extension `imap`
   (see :ref:`installation`), `undkonsorten/typo3-cute-mailing` ^4.2 and
   `undkonsorten/taskqueue` ^8.0 or ^9.0. Then update the extension:

   ..  code-block:: bash

       composer require ressourcenmangel/rsmbouncemailprocessor:^4.0

#. **Compare the database:** Run :guilabel:`Admin Tools > Maintenance > Analyze
   Database Structure`. A new column `removed` is added to the recipient
   reports.
#. **Check the scheduler tasks:** Open and save the three tasks once. The
   password field of the analyze task stays empty, the stored password is kept.
   Check that the tasks run.
#. **Module permissions:** The identifier of the backend module has changed.
   The old identifier is registered as an alias, so run the upgrade wizard of
   the core (:guilabel:`Admin Tools > Upgrade > Upgrade Wizard`). It migrates
   the access rights of backend users and groups.
#. **Check the TypoScript:** The setting keys are unchanged. The view paths
   `module.tx_rsmbouncemailprocessor.view.*RootPath` and their constants are
   removed, they pointed to a non existing directory.

..  _upgrade-defaults:

Changed behavior
================

Bounces of the sending side no longer remove recipients. The limits
`countpossiblespam`, `countfilterlist` and `countconnectionrefused` now default
to `0`. Relay errors are no "user unknown" any more, and the enhanced status
code of the bounce decides over the three digit SMTP code (see
:ref:`classification`). Check your own TypoScript and your expectations
against this.

To restore the limits of version 2.x, set them in your TypoScript:

..  code-block:: typoscript

    module.tx_rsmbouncemailprocessor.settings.deletelimits {
        countconnectionrefused = 2
        countfilterlist = 2
        countpossiblespam = 2
    }

New: the circuit breaker :ref:`maxRemovalsPer24h <configuration-maxremovals>`
(default 50). If you have large lists with many bounces, the first run after
the upgrade may reach it. Check the cause, then raise the value.

Further changes are listed in the
`CHANGELOG <https://github.com/ressourcenmangel/rsmbouncemailprocessor/blob/main/CHANGELOG.md>`__.
