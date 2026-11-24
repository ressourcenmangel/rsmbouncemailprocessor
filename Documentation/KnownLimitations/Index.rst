..  _known-limitations:

==================
Known limitations
==================

*  **Line separated recipient lists:** The recipient list type "line
   separated" of cute_mailing cannot remove recipients. Bounces of such lists
   are counted and shown in the reports, but nobody is removed. The process
   task marks these reports as removed and logs a warning.
*  **Requests are not signed yet.** Bounces and unsubscribe requests are
   evaluated by the content of the mails. A signed token is planned together
   with cute_mailing.
*  **Mass removal is limited:** The circuit breaker
   :ref:`maxRemovalsPer24h <configuration-maxremovals>` stops the removal when
   too many recipients would be removed within 24 hours.
*  **Several sites:** The scheduler tasks use the TypoScript of the first root
   page only (see :ref:`configuration-scheduler`).
*  **Mails without newsletter header:** Bounces that cannot be assigned to a
   newsletter are stored on the fallback page, if one is configured.
