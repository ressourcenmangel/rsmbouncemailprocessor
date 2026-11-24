..  _start:

=========================
RSM Bounce Mail Processor
=========================

:Extension key:
   rsmbouncemailprocessor

:Package name:
   ressourcenmangel/rsmbouncemailprocessor

:Version:
   |release|

:Language:
   en

:Author:
   RYSM GmbH and Ralph Brugger

:License:
   This document is published under the
   `Open Publication License <https://www.opencontent.org/openpub/>`__.

:Rendered:
   |today|

----

Automatic bounce handling for newsletters sent with cute_mailing: the extension
reads the bounce mailbox, counts the bounce reasons per recipient and removes
recipients whose address is permanently invalid.

----

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: :ref:`Introduction <introduction>`

        What the extension does and what it does not do.

    ..  card:: :ref:`Installation <installation>`

        Requirements, installation and the first steps.

    ..  card:: :ref:`Configuration <configuration>`

        All TypoScript settings, including the delete limits.

    ..  card:: :ref:`Scheduler tasks <scheduler-tasks>`

        The three tasks, their fields and the recommended order.

    ..  card:: :ref:`How bounces are classified <classification>`

        How a bounce mail becomes a reason and when a recipient is removed.

    ..  card:: :ref:`Backend module <backend-module>`

        Bounce reports and recipient reports for editors.

    ..  card:: :ref:`Privacy and security <privacy-security>`

        What is stored and which safeguards exist.

    ..  card:: :ref:`Upgrade <upgrade>`

        Upgrade from version 2.x to 4.0.

    ..  card:: :ref:`Known limitations <known-limitations>`

        What the extension cannot do (yet).

..  toctree::
    :hidden:
    :maxdepth: 2
    :titlesonly:

    Introduction/Index
    Installation/Index
    Configuration/Index
    SchedulerTasks/Index
    Classification/Index
    BackendModule/Index
    PrivacySecurity/Index
    Upgrade/Index
    KnownLimitations/Index
    Changelog/Index
