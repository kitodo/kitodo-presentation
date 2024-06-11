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

      composer require kitodo/presentation:^5

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

Version 3.3 -> 4.0
==================

Upgrade-Wizards
---------------

There are two upgrade wizards available. If you upgrade an existing installation, you should use them. Without, you have to
configure all plugins from scratch and the collection record images won't be visible.

Database Upgrade
----------------

Run the database upgrade (``Maintenance > Analyze Database Structure``) and *delete* the columns ``metadata`` and ``metadata_sorting``.
(These columns are not used anymore by Kitodo.Presentation 4. If they are not removed, indexing new documents may fail.)

Set the Storage Pid
-------------------

The Kitodo.Presentation configuration folder must be set by TypoScript constant `plugin.tx_dlf.persistence.storagePid` now.
This setting is available for all plugins in the page tree. The plugin specific `pages` has been removed.

Migrate Plugin Settings
-----------------------

When plugins are configured in TypoScript, the values must now be wrapped in a ``settings`` key.

.. code-block:: typoscript

   // Before
   plugin.tx_dlf_pageview {
     features = OverviewMap
   }

   // After
   plugin.tx_dlf_pageview {
     settings {
       features = OverviewMap
     }
   }

Fluid Rendering
---------------

All plugins now use the Fluid Template Engine instead of marker templates for outputting HTML.

*  If you override a ``.tmpl`` file, it needs to be migrated to an HTML/Fluid template.

*  The TypoScript setting ``templateFile`` has been removed.

See the :ref:`Plugin Reference <fluidplugins>` for more information.

Plugin Feeds
------------

The plugin feeds uses the fluid template engine to render XML now. To enable this output format, you must create
a TypoScript extension template on the page with the feed plugin and include the template "RSS Feed Plugin Configuration".

Plugin OAI-PMH
--------------

The plugin oai-pmh uses the fluid template engine to render XML now. To enable this output format, you must create
a TypoScript extension template on the page with the oai-pmh plugin and include the template "OAI-PMH Plugin Configuration".


Plugin Page Grid
----------------

The plugin use the fluid widget.paginate viewhelper now. The markup has changed. You need to check and adopt your design.

The pagination can be configured by TypoScript. The flexform setting `limit` is changed to default `paginate.itemsPerPage`.

Plugin ListView
---------------

The ListView plugin works in a different manner now. It still can be used to render results from the Search plugin or the
Collection plugin. Both plugins have their own "listview" which basically uses the same Fluid partials.

With the ListView plugin, you still achieve the following situation:

::

   page 1: Search Plugin (main column)
      |
      v
      +--> page 2: ListView Plugin (main column) | Search Plugin (sidebar) e.g with forceAbsoluteUrlHttps
      ^
      |
   page 3: Collection Plugin (main column)

The setting ``targetPid`` has been renamed to ``targetPidPageView``.

Toolbox Plugins
---------------

Previously, the toolbox plugins (located in namespace ``Kitodo\Dlf\Plugin\Tools``) could be used directly.
This is not possible anymore, but instead they must be included via the overarching ``Toolbox`` plugin.

.. code-block:: typoscript

   // Before
   lib.imagemanipulation < tt_content.list.20.dlf_imagemanipulationtool

   // After
   lib.imagemanipulation < tt_content.list.20.dlf_toolbox {
     settings {
       tool = imagemanipulationtool
     }
   }

Update CSP
----------

In Kitodo.Presentation 4.0, the way how static images are loaded has changed.
Please make sure that ``blob:`` URLs are not forbidden by your Content Security Policy.

Other Changes
-------------

*  jQuery and OpenLayers have been updated. If you manually include them, update the paths.


Version 3.2 -> 3.3
==================

Version 3.3 introduce the usage of the OCR Highlighting Plugin for Solr. The plugin can be found at
GitHub: https://github.com/dbmdz/solr-ocrhighlighting. This plugin is now mandatory if you are using the full text feature.

Please note: The full text is stored in Solr index in a XML format (`MiniOCR <https://dbmdz.github.io/solr-ocrhighlighting/0.8.0/formats/#miniocr>`_).
This will rise the demand for storage space. You should therefore monitor the disc usage during reindexing.

Steps to Upgrade
----------------

a. Get the latest release ("jar"-file) from https://github.com/dbmdz/solr-ocrhighlighting/releases. Version 0.8.0 is the minimum version number. Make sure to pick the right file for Solr 7/8.
b. Copy the jar-file (e.g. "solr-ocrhighlighting-0.8.0-solr78.jar") to the contrib/ocrsearch/lib/ directory of your Solr.
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
