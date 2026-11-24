..  _configuration:

=============
Configuration
=============

All settings are TypoScript setup below
`module.tx_rsmbouncemailprocessor.settings`. The defaults are in
:file:`EXT:rsmbouncemailprocessor/Configuration/TypoScript/setup.typoscript`
and are already active. Override single values in the TypoScript of your root
page:

..  code-block:: typoscript
    :caption: Example

    module.tx_rsmbouncemailprocessor.settings {
        deletelog {
            enabled = 1
            pid = 42
        }
        deletelimits.countuserunknown = 1
    }

..  _configuration-scheduler:

..  important::
    The scheduler runs without a request. The tasks read the TypoScript of the
    **first root page** (the first page with "Use as root page", otherwise the
    page of the first root template) and the TypoScript that extensions register
    globally. With several sites, settings of other sites are not used. If the
    TypoScript cannot be found, the tasks stop with an error message that names
    the missing setting.

..  contents:: Settings
    :local:
    :depth: 1

..  _configuration-deletelimits:

deletelimits
============

..  confval:: settings.deletelimits.<counter>
    :name: confval-deletelimits
    :type: integer

    The number of bounces of one reason, per recipient address, after which the
    recipient is removed from the recipient lists. `0` means: this reason never
    removes a recipient. The limit is reached at *n or more* bounces.

    The recipient reports count every reason separately, the process task
    checks every counter against its limit.

=================================  =======  ==========================================================
Counter                            Default  Meaning
=================================  =======  ==========================================================
`countuserunknown`                 2        The address does not exist.
`countquotaexceeded`               5        The mailbox is full (usually temporary, hence a higher limit).
`countheadererror`                 2        The mail header was rejected.
`countmessagesize`                 5        The message is too big.
`countunknownreason`               0        The reason is unknown, often an auto responder.
`countnosenderfound`               0        The recipient of the bounce could not be determined.
`countoutofoffice`                 0        An out of office reply.
`countconnectionrefused`           0        The connection to the server failed (network or DNS).
`countfilterlist`                  0        The sender is on a filter list of the receiving server.
`countpossiblespam`                0        The server rejected the mail as spam.
=================================  =======  ==========================================================

The last three counters point to the sending side (reputation, blacklisting,
network) or to the policy of the receiving server, not to an invalid address.
They are `0` by default, because removing recipients for these reasons would
remove valid recipients whenever the sending server is blacklisted or
misconfigured. The counters `countunknownreason`, `countnosenderfound` and
`countoutofoffice` should stay `0`.

Other keys are ignored and a warning is logged. To restore the limits of
version 2.x see :ref:`upgrade-defaults`.

..  _configuration-maxremovals:

maxRemovalsPer24h
=================

..  confval:: settings.maxRemovalsPer24h
    :name: confval-maxremovalsper24h
    :type: integer
    :default: 50

    Circuit breaker of the process task. If the recipients that would be
    removed now plus the recipients removed in the last 24 hours exceed this
    number, nobody is removed and the task fails with an error message in the
    scheduler module. This hints at a blacklisting or a misconfiguration.
    Check the cause first, then raise the value to continue. `0` disables the
    check.

..  _configuration-deletelog:

deletelog
=========

..  confval:: settings.deletelog.enabled
    :name: confval-deletelog-enabled
    :type: boolean
    :default: 0

    Writes a log entry for every recipient removed by bounce handling or by a
    `List-Unsubscribe` request. The entries are shown in the :guilabel:`List`
    module.

..  confval:: settings.deletelog.pid
    :name: confval-deletelog-pid
    :type: integer
    :default: 0

    The page (a folder) that stores the log entries. Without a page nothing is
    logged.

recipientreport
===============

..  confval:: settings.recipientreport.fallbackpid
    :name: confval-recipientreport-fallbackpid
    :type: integer
    :default: 0

    Recipient reports are stored on the page of the newsletter. If a bounce
    cannot be assigned to a newsletter, this page is used. With `0` the page of
    the delete log is used. If neither is set, the recipient report of this
    bounce is not saved.

reasontext
==========

..  confval:: settings.reasontext.<reason>.<n>
    :name: confval-reasontext
    :type: string

    Text patterns per bounce reason. If a bounce mail contains one of the
    texts (case insensitive), it gets this reason. The first matching reason wins, in the order listed here. The reason keys are
    `550` (user unknown), `551` (quota exceeded), `552` (connection refused),
    `554` (header error), `XOUT` (out of office), `XFILTER` (filter list),
    `XSIZE` (message size) and `XSPAM` (possible spam).

    The list is extensible: add further texts with a new number, for example
    for another language or a mail server that is not covered yet.

    ..  code-block:: typoscript

        module.tx_rsmbouncemailprocessor.settings.reasontext.550 {
            100 = mailbox nicht gefunden
        }

Do not remove or change the default patterns unless you know what you are
doing. The delivery status of the bounce mail takes precedence over these
texts, see :ref:`classification`.

deletetables
============

Settings of the :ref:`clean task queue task <scheduler-clean>`. Entries older
than the given number of days are deleted, `0` keeps them.

..  list-table::
    :header-rows: 1
    :widths: 80 20

    *  -  Setting
       -  Default
    *  -  `deletetables.tx_taskqueue_domain_model_task.deleteafterdays`
       -  30
    *  -  `deletetables.tx_rsmbouncemailprocessor_domain_model_bouncereport.deleteafterdays`
       -  0
    *  -  `deletetables.tx_rsmbouncemailprocessor_domain_model_deletelog.deleteafterdays`
       -  0

The first setting applies to finished tasks of the taskqueue extension.
