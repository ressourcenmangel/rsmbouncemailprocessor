..  _introduction:

============
Introduction
============

Newsletters sent over many years collect bounces: addresses that do not exist
any more, full mailboxes, spam filters. A high bounce rate harms the reputation
of the sending server with mailbox providers. This extension handles the
bounces automatically, so that the lists stay clean.

What the extension does
=======================

*  Reads the bounce mailbox of the newsletter via IMAP or POP3.
*  Finds the recipient and the newsletter (sendout) of every bounce from the
   headers set by cute_mailing (`X-TYPO3RCPT`, `X-TYPO3NLUID`).
*  Determines the reason of the bounce, using the delivery status of the mail
   and a set of text patterns that can be extended with TypoScript
   (see :ref:`classification`).
*  Counts the bounce reasons per newsletter (:ref:`bounce report
   <backend-module>`) and per recipient (recipient report).
*  Removes a recipient from the recipient lists once a bounce reason reaches
   its :ref:`delete limit <configuration-deletelimits>`.
*  Processes unsubscribe requests sent by mail clients through the
   `List-Unsubscribe` header and removes the address.
*  Can write a delete log and offers a cleaner task for old data.

What it does not do
===================

*  It does not send newsletters. This is the job of `cute_mailing
   <https://extensions.typo3.org/extension/cute_mailing/>`__, which this
   extension requires.
*  It does not read bounces of mails that were not sent by cute_mailing,
   because the newsletter cannot be determined for them.
*  It does not remove recipients from recipient lists that cannot remove
   recipients (see :ref:`known-limitations`).

Screenshots
===========

..  figure:: /Images/bounce-reports.png
    :alt: The bounce report list of the backend module
    :class: with-shadow

    The bounce reports of the sent newsletters

..  figure:: /Images/recipient-reports.png
    :alt: The recipient report list of the backend module
    :class: with-shadow

    The recipient reports with the removal status
