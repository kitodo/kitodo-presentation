.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

================
Plugin Reference
================

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
