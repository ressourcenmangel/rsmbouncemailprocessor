..  _backend-module:

==============
Backend module
==============

The module :guilabel:`Bounce Mails` (TYPO3 13: :guilabel:`Web > Bounce Mails`, TYPO3 14: :guilabel:`Content > Bounce Mails`) shows the results of the analyze
task. It has two tabs: the bounce reports and the recipient reports. Both are
read from the page you select in the page tree. Reports are stored on the page
of the newsletter, so select that page or a page above it. Without reports the
module asks you to select a page.

Bounce reports
==============

One entry per newsletter: the number of mails sent, the number of bounces, the
bounce rate and the distribution of the reasons. The time of the last analysis
is shown as well.

..  figure:: /Images/bounce-reports.png
    :alt: The bounce report list
    :class: with-shadow

    The bounce reports

A report can be deleted with :guilabel:`Delete` after a confirmation. This only
deletes the report, not the recipients. The next run of the analyze task
creates new reports for new bounces.

Recipient reports
=================

One entry per recipient address with the counters per reason and the sum. If
the process task has removed the address from the recipient lists, the entry
shows the removal date. If the address bounces again, the removal is reset.

..  figure:: /Images/recipient-reports.png
    :alt: The recipient report list with removal status
    :class: with-shadow

    The recipient reports with the status "removed"

The list can be filtered:

*  **Email contains:** part of the address.
*  **Minimum bounces:** only recipients with at least this number of bounces.
*  **Status:** all, not removed or removed.

:guilabel:`Reset` clears the filter. If a filter has no match, a hint is shown
and the filter stays available.

A recipient report can be deleted after a confirmation. This does not touch the
recipient lists.

Delete log
==========

With :ref:`settings.deletelog <configuration-deletelog>` enabled, every removal
is logged on the configured page. The entries are shown in the
:guilabel:`List` module: one table for removals by bounce handling (with
the reason and its counter value) and one for removals by `List-Unsubscribe`.
The retention is controlled by :ref:`deletetables <configuration>`.
