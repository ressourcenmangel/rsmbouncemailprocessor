..  _installation:

============
Installation
============

Requirements
============

=============================  ==========================================
Requirement                    Version
=============================  ==========================================
TYPO3                          13.4 or 14.3
PHP                            8.3 or 8.4
PHP extension `imap`           required, see the note below
cute_mailing                   `undkonsorten/typo3-cute-mailing` ^4.2
taskqueue                      `undkonsorten/taskqueue` ^8.0 or ^9.0
System extension scheduler     same version as TYPO3
=============================  ==========================================

The bounce mailbox must be a POP3 or IMAP account. Configure it as return path
of the newsletters in cute_mailing (see :ref:`installation-cute-mailing`).

..  note::
    The PHP extension `imap` was removed from the PHP core with PHP 8.4. On PHP
    8.4 it is only available from PECL (`pecl install imap`), many distribution
    packages and hosting environments do not ship it. Check
    `php -m | grep imap` on the system that runs the scheduler (usually the
    command line PHP). Without the extension the analyze task fails with an
    error message.

Install the extension
=====================

..  code-block:: bash

    composer require ressourcenmangel/rsmbouncemailprocessor

Activate the extension in a classic (non Composer) installation in
:guilabel:`Admin Tools > Extensions`. Then open :guilabel:`Admin Tools >
Maintenance > Analyze Database Structure` and apply the suggested changes. The
extension creates the tables for the bounce reports, the recipient reports and
the two logs.

TypoScript
==========

The extension registers its default TypoScript setup (`module.tx_rsmbouncemailprocessor`)
itself. There is neither a site set nor a static template to include. To change
settings, add your own TypoScript to the template or site package of the root
page of your (first) site, see :ref:`configuration`.

..  note::
    The scheduler tasks read the TypoScript of the first root page only. With
    several sites, put the settings there (see :ref:`configuration-scheduler`).

..  _installation-cute-mailing:

Configure cute_mailing
======================

Bounces can only be assigned when the newsletters are sent with the bounce
mailbox as return path. Set this in the page TSconfig of cute_mailing (see the
`cute_mailing documentation <https://docs.typo3.org/p/undkonsorten/typo3-cute-mailing/main/en-us/>`__):

..  code-block:: typoscript
    :caption: Page TSconfig

    reply_to = bounce@example.org
    return_path = bounce@example.org

To process unsubscribe requests of mail clients, enable the `List-Unsubscribe`
header with the same mailbox:

..  code-block:: typoscript
    :caption: Page TSconfig

    listunsubscribe_enable = 1
    listunsubscribe_email = bounce@example.org

First steps
===========

#. Create the scheduler tasks (see :ref:`scheduler-tasks`).
#. Check the :ref:`delete limits <configuration-deletelimits>`. With the
   defaults, only addresses that do not exist and persistently full mailboxes
   are removed.
#. Optionally enable the :ref:`delete log <configuration-deletelog>`.
