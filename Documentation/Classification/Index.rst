..  _classification:

===========================
How bounces are classified
===========================

Every bounce mail gets exactly one reason. The reason decides which counter of
the recipient is increased. A recipient is only removed when a counter reaches
its :ref:`delete limit <configuration-deletelimits>`.

The order of the checks
=======================

#. **Recipient and newsletter:** The recipient is read from the header
   `X-TYPO3RCPT` (fallback `X-Failed-Recipients`, `Final-Recipient`), the
   newsletter from `X-TYPO3NLUID`. Without a recipient the bounce counts as
   "no sender found".
#. **Delivery status:** The delivery status fields of the bounce (`Status` and
   `Diagnostic-Code`, RFC 3464) are evaluated first. Only the part of the mail
   before the attached original mail counts.
#. **Text patterns:** If the delivery status is not conclusive, the texts of
   :ref:`settings.reasontext <configuration>` decide.

The enhanced status code decides
================================

If the bounce contains an enhanced status code (`5.x.y`), it decides over the
three digit SMTP code:

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *  -  Enhanced status
       -  Reason
    *  -  `5.1.1`, `5.1.2`, `5.1.10`
       -  User unknown (the address does not exist)
    *  -  `5.2.2`
       -  Mailbox full (quota exceeded)
    *  -  all others
       -  Unknown reason, never removes a recipient

"All others" includes `5.7.x` (policy, relay, spam), `5.1.8` (sender
rejected), `5.4.x` (routing) and `5.0.0`. Without an enhanced status code, the
codes `551` and `553` are taken as they are, `553` counts as unknown reason.
A bare `550` without enhanced status is inconclusive: the text patterns decide.

Relay errors ("5.7.1 unable to relay") are no "user unknown". They are almost
always a configuration problem of the sending side.

Reasons and their defaults
==========================

..  list-table::
    :header-rows: 1
    :widths: 25 20 55

    *  -  Reason
       -  Removes by default (limit)
       -  Why
    *  -  User unknown
       -  yes (2)
       -  The address does not exist.
    *  -  Quota exceeded
       -  yes (5)
       -  The mailbox stays full over time.
    *  -  Header error
       -  yes (2)
       -  The mail header is rejected.
    *  -  Message size
       -  yes (5)
       -  The message is too big for the recipient.
    *  -  Out of office
       -  no
       -  Temporary reply of a person.
    *  -  Unknown reason
       -  no
       -  Not conclusive, often an auto responder.
    *  -  No sender found
       -  no
       -  The recipient of the bounce is not known.
    *  -  Connection refused
       -  no
       -  Network or DNS problem, not the address.
    *  -  Filter list
       -  no
       -  Policy of the receiving server.
    *  -  Possible spam
       -  no
       -  Reputation of the sending server or the content.

Only hard errors that concern the address itself remove recipients by default.
Spam, filter list and connection errors point to the sending side. If the
sending server is blacklisted, these bounces affect valid recipients too, so
removing them would shrink the lists for no reason.
