..  _privacy-security:

====================
Privacy and security
====================

What is stored
==============

*  **Recipient reports** contain the e-mail address and the counters per
   reason. They are stored on the page of the newsletter. Use
   :ref:`deletetables <configuration>` to delete old data.
*  **Bounce reports** contain only counters per newsletter, no addresses.
*  **Delete log** (optional) contains the address, the reason and the time of a
   removal.
*  The mail server password of the analyze task is stored in the task. The task
   form never sends it to the browser.

What is logged
==============

The extension writes no e-mail addresses to the TYPO3 log. Warnings and errors
identify records by uid, newsletter or error class only.

Unsubscribe by mail client
==========================

A `List-Unsubscribe` mail is only executed if the sender of the mail and the
recipient uid in the request match the address to be removed. In every other
case nothing is removed, a warning without addresses is logged, and the mail is
never deleted from the mailbox.

Safeguards
==========

*  The extension removes recipients only for hard errors that concern the
   address itself (see :ref:`classification`).
*  The :ref:`circuit breaker <configuration-maxremovals>` limits how many
   recipients can be removed within 24 hours.
*  The scheduler form escapes all values.

Please do not describe security issues in the public issue tracker or in pull
requests. Contact the maintainers privately instead, see the
`repository <https://github.com/ressourcenmangel/rsmbouncemailprocessor>`__.
