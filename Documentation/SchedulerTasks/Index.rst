..  _scheduler-tasks:

===============
Scheduler tasks
===============

The extension provides three tasks for the :guilabel:`System > Scheduler`
module. The mail server data are entered in the task itself, everything else is
configured with :ref:`TypoScript <configuration>`.

..  figure:: /Images/scheduler-analyze-task.png
    :alt: The form of the task "RSM analyze bounce mail"
    :class: with-shadow

    The form of the task "RSM analyze bounce mail"

Recommended order
=================

#. **RSM analyze bounce mail**, every 30 minutes or more often.
#. **RSM process bounce mail**, once a day, for example early in the morning.
#. **RSM clean task queue**, once a day (optional).

Estimate the interval of the first task from the mailbox size. Example: 2000
mails every 15 minutes and 10 % bounces produce 200 bounces per run. A mailbox
that holds 800 mails would be full after one hour.

..  _scheduler-analyze:

RSM analyze bounce mail
=======================

Reads the unread mails of the mailbox, determines recipient, newsletter and
reason, and writes the bounce reports and recipient reports. The task is
required.

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *  -  Field
       -  Meaning
    *  -  Server URL/IP
       -  URL or IP of the mail server.
    *  -  Port number
       -  Port of the mail server.
    *  -  Username
       -  Login of the bounce mailbox.
    *  -  Password
       -  Password of the mailbox. The stored password is never sent to the
          browser. Leave the field empty to keep it, enter a new one to change
          it.
    *  -  Type of mailserver
       -  `IMAP` or `POP3`.
    *  -  Number of bounce mail to be processed
       -  Maximum number of mails per run. The PHP IMAP functions become slow
          with many mails, a value around 250 has proven itself.
    *  -  Also delete unanalysable mails
       -  Analysed mails are always deleted. If set, mails that could not be
          analysed are deleted as well. Otherwise they are marked as read and
          stay in the mailbox. Recommended, so that the mailbox cannot overflow.

When the connection to the mail server fails, the task reports it in the
scheduler module. Without the PHP extension `imap` the task fails as well.

..  _scheduler-process:

RSM process bounce mail
=======================

Checks the recipient reports against the :ref:`delete limits
<configuration-deletelimits>`, removes the recipients that reached a limit from
the recipient lists of the newsletters, and marks their recipient reports as
removed. If the :ref:`delete log <configuration-deletelog>` is enabled, every
removal is logged. The task has no fields.

An address that bounces again after its removal is processed again.

..  _scheduler-clean:

RSM clean task queue
====================

Optional cleaner. Deletes finished tasks of the taskqueue extension, old bounce
reports and old delete log entries according to :ref:`deletetables
<configuration>`. By default only finished tasks older than 30 days are
deleted. The task has no fields.

When something fails
====================

*  **Missing TypoScript:** If the TypoScript of the extension is not found, the
   analyze and the process task stop with a clear error message instead of
   doing nothing silently. See :ref:`configuration-scheduler`.
*  **Mails that cannot be processed:** A mail whose processing fails does not
   stop the run. It is marked as read, never deleted, and a warning without
   addresses is written to the log. The same applies to mails that could not be
   analysed, unless "Also delete unanalysable mails" is set. Rejected
   unsubscribe requests are never deleted.
*  **Circuit breaker:** If more recipients would be removed than
   :ref:`maxRemovalsPer24h <configuration-maxremovals>` allows, the process
   task fails and removes nobody. The error message appears in the scheduler
   module. Find the cause (blacklisting, mail server problems) before you
   continue.
*  **Recipient lists:** A newsletter whose recipient list cannot be loaded is
   skipped with a warning.
