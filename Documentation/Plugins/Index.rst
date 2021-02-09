.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

################
Plugin Reference
################

.. contents::
    :local:
    :depth: 3

Kitodo Plugin Reference
=======================

Common Settings
---------------
pages
^^^^^
Startingpoint of this plugin. This is the Kitodo.Presentation data folder.

templateFile
^^^^^^^^^^^^
The used template file of this plugin.



Audioplayer
-----------

The audioplayer plugin is only active if the selected document has a valid audio filegroup (fileGrpAudio).

Properties
^^^^^^^^^^
:typoscript:`plugin.tx_dlf_audioplayer.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
        Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
        excludeOther_
   :Data Type:
        :ref:`t3tsref:data-type-boolean`
   :Default:
        1

 - :Property:
       elementId_
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
        tx-dlf-audio

 - :Property:
       templateFile_
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
        AudioPlayer.tmpl



excludeOther
""""""""""""
Show only documents from the selected page.

elementId
"""""""""
ID value of the HTML element for the audio player.

Basket
------

:typoscript:`plugin.tx_dlf_basket.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
        Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       pregeneration
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       pdfgenerate
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:

 - :Property:
       pdfdownload
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:

 - :Property:
       pdfprint
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:

 - :Property:
       pdfparams
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
       ##docId##,##startpage##,##endpage##,##startx##,##starty##,##endx##,##endy##,##rotation##

 - :Property:
       pdfparamseparator
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
       `*`

 - :Property:
       basketGoToButton
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       targetBasket
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       templateFile_
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       Basket.tmpl


Calendar
---------

The calendar plugin may be used with newspaper and ephemeras (periodical
published media). The plugin shows itself an overview of all available
years or all issues in a calendar view of a selected year.

You can't place the plugin together with the pageview plugin on one page.
But you can use TypoScript conditions on this page to select the proper
plugin e.g by setting some specific FLUID variables.

This is an example usage of the TypoScript condition ("getDocumentType")::

    [getDocumentType("{$config.storagePid}") == "ephemera" or getDocumentType("{$config.storagePid}") == "newspaper"]
    page.10.variables {
        isNewspaper = TEXT
        isNewspaper.value = newspaper_anchor
    }
    [END]

    [getDocumentType("{$config.storagePid}") == "year"]
    page.10.variables {
        isNewspaper = TEXT
        isNewspaper.value = newspaper_year
    }
    [END]

    [getDocumentType("{$config.storagePid}") == "issue"]
    page.10.variables {
        isNewspaper = TEXT
        isNewspaper.value = newspaper_issue
    }
    [END]

The `{$config.storagePid}` is a TypoScript constant holding the Kitodo.Presentation storage pid.

This way, the FLUID variable "isNewspaper" is set according to the given
value. Inside the FLUID template it's possible to switch to the right plugin
now.

:typoscript:`plugin.tx_dlf_calendar.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
        Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       initialDocument
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:

 - :Property:
       showEmptyMonths
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       1

 - :Property:
       templateFile_
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       Calendar.tmpl

Collection
----------

The collection plugin shows one collection, all collections or selected collections.

:typoscript:`plugin.tx_dlf_collection.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       collections
   :Data Type:
       :ref:`t3tsref:data-type-list`
   :Default:

 - :Property:
       show_userdefined
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:

 - :Property:
       dont_show_single
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       randomize
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       targetPid
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       targetFeed
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       templateFile_
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       Collection.tmpl


Feeds
-----

:typoscript:`plugin.tx_dlf_feeds.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       collections
   :Data Type:
       :ref:`t3tsref:data-type-list`
   :Default:

 - :Property:
        excludeOther_
   :Data Type:
        :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       library
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:

 - :Property:
       limit
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:
       50

 - :Property:
        prependSuperiorTitle
   :Data Type:
        :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       targetPid
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       title
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:

 - :Property:
       description
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:


List View
---------

:typoscript:`plugin.tx_dlf_listview.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       limit
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:
       25

 - :Property:
       targetPid
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
        getTitle
   :Data Type:
        :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       basketButton
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       targetBasket
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       templateFile_
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       ListView.tmpl


Metadata
--------

:typoscript:`plugin.tx_dlf_metadata.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
        excludeOther_
   :Data Type:
        :ref:`t3tsref:data-type-boolean`
   :Default:
       1

 - :Property:
        linkTitle
   :Data Type:
        :ref:`t3tsref:data-type-boolean`
   :Default:
       1

 - :Property:
       targetPid
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
        getTitle
   :Data Type:
        :ref:`t3tsref:data-type-boolean`
   :Default:
       1

 - :Property:
        showFull
   :Data Type:
        :ref:`t3tsref:data-type-boolean`
   :Default:
       1

 - :Property:
       rootline
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:
       0

 - :Property:
       separator
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
       `#`

 - :Property:
       templateFile_
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       Metadata.tmpl

Navigation
----------

:typoscript:`plugin.tx_dlf_navigation.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       pageStep
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:
       5

 - :Property:
       targetPid
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       templateFile_
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       Navigation.tmpl

OAI-PMH
-------

:typoscript:`plugin.tx_dlf_oaipmh.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       library
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:

 - :Property:
       limit
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:
       5

 - :Property:
       expired
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:
       1800

 - :Property:
       show_userdefined
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       stylesheet
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       0

 - :Property:
       unqualified_epicur
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

Page Grid
---------

:typoscript:`plugin.tx_dlf_pagegrid.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       limit
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:
       24

 - :Property:
       placeholder
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       Navigation.tmpl

 - :Property:
       targetPid
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       templateFile_
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       PageGrid.tmpl

Page View
---------

:typoscript:`plugin.tx_dlf_pageview.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       excludeOther_
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       1

 - :Property:
       features
   :Data Type:
       :ref:`t3tsref:data-type-list`
   :Default:
       1

 - :Property:
       elementId_
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
       tx-dlf-map

 - :Property:
       crop
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       useInternalProxy
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       magnifier
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       basketButton
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       targetBasket
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       templateFile_
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       PageView.tmpl

Search
------

:typoscript:`plugin.tx_dlf_search.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       fulltext
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:

 - :Property:
       solrcore
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:

 - :Property:
       limit
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:
       50000

 - :Property:
       extendedSlotCount
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:
       0

 - :Property:
       extendedFields
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:
       0

 - :Property:
       searchIn
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:

 - :Property:
       collections
   :Data Type:
       :ref:`t3tsref:data-type-list`
   :Default:

 - :Property:
       facets
   :Data Type:
       :ref:`t3tsref:data-type-list`
   :Default:

 - :Property:
       limitFacets
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:
       15

 - :Property:
       resetFacets
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:

 - :Property:
       sortingFacets
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:

 - :Property:
       suggest
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       1

 - :Property:
       showSingleResult
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       targetPid
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       targetPidPageView
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       templateFile_
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       Search.tmpl


Statistics
----------

:typoscript:`plugin.tx_dlf_statistics.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       collections
   :Data Type:
       :ref:`t3tsref:data-type-list`
   :Default:

 - :Property:
       description
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:


Table Of Contents
-----------------

:typoscript:`plugin.tx_dlf_tableofcontents.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       excludeOther_
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       1

 - :Property:
       basketButton
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
       0

 - :Property:
       targetBasket
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       targetPid
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       templateFile_
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       TableOfContents.tmpl


Toolbox
-------


:typoscript:`plugin.tx_dlf_toolbox.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default
   :Values:
       Values

 - :Property:
       pages_
   :Data Type:
       :ref:`t3tsref:data-type-page-id`
   :Default:

 - :Property:
       tools
   :Data Type:
       :ref:`t3tsref:data-type-list`
   :Default:
   :Values:
       * tx_dlf_annotationtool
       * tx_dlf_fulltexttool
       * tx_dlf_imagedownloadtool
       * tx_dlf_imagemanipulationtool
       * tx_dlf_pdfdownloadtool
       * tx_dlf_fulltextdownloadtool
       * tx_dlf_searchindocumenttool

 - :Property:
       solrcore
   :Data Type:
       :ref:`t3tsref:data-type-integer`
   :Default:

 - :Property:
       fileGrpsImageDownload
   :Data Type:
       :ref:`t3tsref:data-type-list`
   :Default:
       MIN,DEFAULT,MAX

 - :Property:
       templateFile_
   :Data Type:
       :ref:`t3tsref:data-type-resource`
   :Default:
       Toolbox.tmpl


Fulltext Tool
^^^^^^^^^^^^^
This plugin adds an activation link for fulltext to the toolbox. If no fulltext is available for the current page, a span-tag is rendered instead.

The default behavior is to show the fulltext after click on the toggle link. There is a TypoScript configuration to show the fulltext initially.

Plugin allows also to configure (searchHlParameters) by which URL parameters words will be highlighted in the image. The first defined parameter on the configuration has highest priority, if not found it checks the next ones.

:typoscript:`plugin.tx_dlf_fulltexttool.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default
   :Values:
       Values

 - :Property:
       activateFullTextInitially
   :Data Type:
       :ref:`t3tsref:data-type-boolean`
   :Default:
        0
   :Values:
        0: show fulltext after click on toggle link

        1: show fulltext on document load

 - :Property:
       fullTextScrollElement
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
        html, body

 - :Property:
       searchHlParameters
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
        tx_dlf[highlight_word]


The fulltext is fetched and rendered by JavaScript into the `<div id="tx-dlf-fulltextselection">` of the pageview plugin.

**Please note**: To allow JavaScript fetching the fulltext, the `CORS headers <https://en.wikipedia.org/wiki/Cross-origin_resource_sharing>`_ must be configured appropriate on the providing webserver.


Search in Document Tool
<<<<<<< HEAD
^^^^^^^^^^^^^^^^^^^^^^^
=======
^^^^^^^^^^^^^
>>>>>>> d34cf08f (Change configuration option fromisIndexRemapped to searchURL)
This plugin adds an possibility to search all appearances of the phrase in currently displayed document 

:typoscript:`plugin.tx_dlf_searchindocumenttool.`

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
       Property
   :Data Type:
       Data type
   :Default:
       Default
   :Values:
       Values

 - :Property:
       searchUrl
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:

<<<<<<< HEAD
 - :Property:
       documentIdUrlSchema
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:

 - :Property:
       idInputName
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
       tx_dlf[id]

 - :Property:
       queryInputName
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
       tx_dlf[query]

 - :Property:
       startInputName
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
       tx_dlf[start]
 
 - :Property:
       pageInputName
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
       tx_dlf[page]

 - :Property:
       highlightWordInputName
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
       tx_dlf[highlight_word]

 - :Property:
       encryptedInputName
   :Data Type:
       :ref:`t3tsref:data-type-string`
   :Default:
       tx_dlf[encrypted]
=======
>>>>>>> d34cf08f (Change configuration option fromisIndexRemapped to searchURL)
