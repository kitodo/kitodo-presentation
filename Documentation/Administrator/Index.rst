.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual:

####################
Administrator Manual
####################

.. contents::
   :local:
   :depth: 2



************
Installation
************

Composer Mode
=============

It is highly recommended to install this extension by using composer. This is the only supported way.

Please run the following commands in your webroot where the TYPO3 :file:`composer.json` is located:

.. rst-class:: bignums-xxl

#. Fetch Kitodo.Presentation with Composer

   .. code-block:: shell

      composer require kitodo/presentation:^3.3

#. Install and Activate the Extension

   .. code-block:: shell

      ./vendor/typo3 extension:activate dlf


Classic Mode
============

However, there are two options to install the required packages solarium/solarium and symfony/event-dispatcher in non-composer mode:

a. Run the command :php:`composer update` within the directory of the extension.
   All required packages are downloaded automatically to the vendor subdirectory.

b. Download the required packages manually to vendor/solarium/solarium and vendor/symfony/event-dispatcher.
   Please check the require sections in :file:`composer.json` of the extension,
   solarium and event-dispatcher to download and install matching versions.

After the installation of the packages in non-composer mode you have to deactivate
and (re-)activate the extension in the extension manager to trigger the TYPO3
autoloader to rebuild the classmap.


*******
Upgrade
*******

This section contains version specific instructions on upgrading an existing Kitodo.Presentation installation.

Version 3.2 -> 3.3
==================

Version 3.3 introduce the usage of the OCR Highlighting Plugin for Solr. The plugin can be found at GitHub: https://github.com/dbmdz/solr-ocrhighlighting. This plugin is now mandatory if you are using the full texts feature.

Steps to Upgrade
----------------

a. Get the latest release ("jar"-file) from https://github.com/dbmdz/solr-ocrhighlighting/releases. Version 0.7.1 is the minimum version number.
b. Copy the jar-file (e.g. "solr-ocrhighlighting-0.7.1.jar") to the contrib/ocrsearch/lib/ directory of your Solr.
c. Copy the updated schema.xml to your Solr configsets in $SOLR_HOME/configsets/dlf/
d. Copy the schema.xml from EXT:dlf/Configuration/ApacheSolr/configsets/dlf/conf/ to all of your Solr cores. E.g. $SOLR_HOME/data/dlfCore0/conf/
e. Restart Solr.
f. Reindex all documents. This can be done by the kitodo:reindex CLI command with the '-a' (all) flag. See: :ref:`reindex_collections`.


*******
Logging
*******

The application uses default `TYPO3 logging framework <https://docs.typo3.org/m/typo3/reference-coreapi/9.5/en-us/ApiOverview/Logging/Index.html>`_.
It writes logs to the typo3_xyz.log file placed in  :file:`<project-root>/var/log/` (Composer
based installation).

You influence the Loglevel by overwriting the :code:`writerConfiguration` in
:code:`$GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration']`. Have a look at the
documentation: https://docs.typo3.org/m/typo3/reference-coreapi/9.5/en-us/ApiOverview/Logging/Configuration/Index.html#writer-configuration
