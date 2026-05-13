############
Tenant Setup
############

This document describes the manual tenant setup via the CLI for an existing Kitodo.Presentation configuration folder.
The setup applies tenant defaults to the configuration folder, such as formats, structures, metadata and optional Solr wiring.

Tenant Setup Command
====================

The tenant setup flow is implemented by:

* ``Classes/Command/TenantSetupCommand.php``
* ``Classes/Service/TenantModuleSetupService.php``
* ``Classes/Service/TenantDefaultsSetupService.php``

Run the command against an existing configuration folder:

::

   php /var/www/typo3/vendor/bin/typo3 kitodo:tenantSetup --config-page=<uid>

The ``--config-page`` option is required and must point to a page of type sysfolder.

If no step flags are provided, the command runs the complete tenant setup:

* namespaces/formats
* structures
* metadata
* Solr core creation

You can also run individual steps only:

::

   php /var/www/typo3/vendor/bin/typo3 kitodo:tenantSetup \
     --config-page=<uid> \
     --namespaces \
     --metadata

When you run individual steps, apply ``--namespaces`` (or ``--formats``) before ``--metadata`` so the metadata step can attach its metadata-format relations to existing format records.

Available Options
-----------------

The command supports these options:

* ``--config-page`` for the target configuration folder UID
* ``--namespaces`` to apply format defaults
* ``--formats`` as an alias for ``--namespaces``
* ``--structures`` to apply structure defaults
* ``--metadata`` to apply metadata defaults
* ``--solr-core`` to create a Solr core record when missing

Validation Rules
----------------

The tenant setup validates the target before any defaults are applied:

* ``--config-page`` must be present and numeric.
* The referenced page must exist.
* The referenced page must be a configuration folder with ``doktype = 254``.
* The configuration folder must have a valid parent root page.

Applied Defaults
================

The tenant defaults service applies the same defaults as the backend new-tenant module.

Depending on the selected flags, it can create:

* namespace/formats records in ``tx_dlf_formats``
* structure records in ``tx_dlf_structures``
* metadata records and metadata-format relations
* translated metadata and structure labels for the configured site languages
* a Solr core record in ``tx_dlf_solrcores``

The setup is additive. Existing matching records are reused instead of duplicated, so repeated runs only fill missing defaults.
Translations are only created for newly inserted default structures and metadata records.
