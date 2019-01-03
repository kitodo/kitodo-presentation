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

.. container:: ts-properties-tx-dlf-audioplayer

:typoscript:`plugin.tx_dlf_audioplayer.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	excludeOther_               :ref:`t3tsref:data-type-boolean`      1
	elementId_                  :ref:`t3tsref:data-type-string`       tx-dlf-audio
	templateFile_               :ref:`t3tsref:data-type-resource`     template.tmpl
	=========================== ===================================== ====================

excludeOther
""""""""""""
Show only documents from the selected page.

elementId
"""""""""
ID value of the HTML element for the audio player.

Basket
------

.. container:: ts-properties-tx-dlf-basket

:typoscript:`plugin.tx_dlf_basket.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	pregeneration               :ref:`t3tsref:data-type-boolean`      0
	pdfgenerate                 :ref:`t3tsref:data-type-string`
	pdfdownload                 :ref:`t3tsref:data-type-string`
	pdfprint                    :ref:`t3tsref:data-type-string`
	pdfparams                   :ref:`t3tsref:data-type-string`       ##docId##,##startpage##,##endpage##,##startx##,##starty##,##endx##,##endy##,##rotation##
	pdfparamseparator           :ref:`t3tsref:data-type-string`       `*`
	basketGoToButton            :ref:`t3tsref:data-type-boolean`      0
	targetBasket                :ref:`t3tsref:data-type-page-id`
	templateFile_               :ref:`t3tsref:data-type-resource`     template.tmpl
	=========================== ===================================== ====================


Collection
----------

The collection plugin shows one collection, all collections or selected collections.

.. container:: ts-properties-tx-dlf-collection

:typoscript:`plugin.tx_dlf_collection.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	collections                 :ref:`t3tsref:data-type-list`
	show_userdefined            :ref:`t3tsref:data-type-integer`
	dont_show_single            :ref:`t3tsref:data-type-boolean`      0
	randomize                   :ref:`t3tsref:data-type-boolean`      0
	targetPid                   :ref:`t3tsref:data-type-page-id`
	targetFeed                  :ref:`t3tsref:data-type-page-id`
	templateFile_               :ref:`t3tsref:data-type-resource`     template.tmpl
	=========================== ===================================== ====================


Feeds
-----

.. container:: ts-properties-tx-dlf-feed

:typoscript:`plugin.tx_dlf_feed.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	collections                 :ref:`t3tsref:data-type-list`
	excludeOther                :ref:`t3tsref:data-type-boolean`      0
	library                     :ref:`t3tsref:data-type-integer`
	limit                       :ref:`t3tsref:data-type-integer`      50
	prependSuperiorTitle        :ref:`t3tsref:data-type-boolean`      0
	targetPid                   :ref:`t3tsref:data-type-page-id`
	title                       :ref:`t3tsref:data-type-string`
	description                :ref:`t3tsref:data-type-string`
	=========================== ===================================== ====================

Listview
--------

.. container:: ts-properties-tx-dlf-listview

:typoscript:`plugin.tx_dlf_listview.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	limit                       :ref:`t3tsref:data-type-integer`      25
	targetPid                   :ref:`t3tsref:data-type-page-id`
	getTitle                    :ref:`t3tsref:data-type-boolean`      0
	basketButton                :ref:`t3tsref:data-type-boolean`      0
	targetBasket                :ref:`t3tsref:data-type-page-id`
	templateFile_               :ref:`t3tsref:data-type-resource`     template.tmpl
	=========================== ===================================== ====================

Metadata
--------

.. container:: ts-properties-tx-dlf-metadata

:typoscript:`plugin.tx_dlf_metadata.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	excludeOther                :ref:`t3tsref:data-type-boolean`      1
	linkTitle                   :ref:`t3tsref:data-type-boolean`      1
	targetPid                   :ref:`t3tsref:data-type-page-id`
	getTitle                    :ref:`t3tsref:data-type-boolean`      1
	showFull                    :ref:`t3tsref:data-type-boolean`      1
	rootline                    :ref:`t3tsref:data-type-integer`      0
	separator                   :ref:`t3tsref:data-type-string`       #
	templateFile_               :ref:`t3tsref:data-type-resource`     template.tmpl
	=========================== ===================================== ====================

Navigation
---------

.. container:: ts-properties-tx-dlf-navigation

:typoscript:`plugin.tx_dlf_navigation.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	pageStep                    :ref:`t3tsref:data-type-integer`      5
	targetPid                   :ref:`t3tsref:data-type-page-id`
	templateFile_               :ref:`t3tsref:data-type-resource`     template.tmpl
	=========================== ===================================== ====================

Newspaper
---------

.. container:: ts-properties-tx-dlf-newspaper

:typoscript:`plugin.tx_dlf_newspaper.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	templateFile_               :ref:`t3tsref:data-type-resource`     template.tmpl
	=========================== ===================================== ====================

OAI
---

.. container:: ts-properties-tx-dlf-oai

:typoscript:`plugin.tx_dlf_oai.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	library                     :ref:`t3tsref:data-type-integer`
	limit                       :ref:`t3tsref:data-type-integer`      5
	expired                     :ref:`t3tsref:data-type-integer`      1800
	show_userdefined            :ref:`t3tsref:data-type-boolean`      0
	stylesheet                  :ref:`t3tsref:data-type-resource`
	unqualified_epicur          :ref:`t3tsref:data-type-boolean`      0
	=========================== ===================================== ====================

Pagegrid
--------

.. container:: ts-properties-tx-dlf-pagegrid

:typoscript:`plugin.tx_dlf_pagegrid.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	limit                       :ref:`t3tsref:data-type-integer`      24
	placeholder                 :ref:`t3tsref:data-type-resource`
	targetPid                   :ref:`t3tsref:data-type-page-id`
	templateFile_               :ref:`t3tsref:data-type-resource`     template.tmpl
	=========================== ===================================== ====================

Pageview
--------

.. container:: ts-properties-tx-dlf-pageview

:typoscript:`plugin.tx_dlf_pageview.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	excludeOther                :ref:`t3tsref:data-type-boolean`      1
	features                    :ref:`t3tsref:data-type-list`
	elementId                   :ref:`t3tsref:data-type-string`       tx-dlf-map
	crop                        :ref:`t3tsref:data-type-boolean`      0
	useInternalProxy            :ref:`t3tsref:data-type-boolean`      0
	magnifier                   :ref:`t3tsref:data-type-boolean`      0
	basketButton                :ref:`t3tsref:data-type-boolean`      0
	targetBasket                :ref:`t3tsref:data-type-page-id`
	templateFile_               :ref:`t3tsref:data-type-resource`     template.tmpl
	=========================== ===================================== ====================

Search
------

.. container:: ts-properties-tx-dlf-search

:typoscript:`plugin.tx_dlf_search.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	fulltext                    :ref:`t3tsref:data-type-boolean`
	solrcore                    :ref:`t3tsref:data-type-integer`
	limit                       :ref:`t3tsref:data-type-integer`      50000
	extendedSlotCount           :ref:`t3tsref:data-type-integer`      0
	extendedFields              :ref:`t3tsref:data-type-list`         0
	searchIn                    :ref:`t3tsref:data-type-string`
	collections                 :ref:`t3tsref:data-type-list`
	facets                      :ref:`t3tsref:data-type-list`
	limitFacets                 :ref:`t3tsref:data-type-integer`      15
	resetFacets                 :ref:`t3tsref:data-type-boolean`      0
   sortingFacets               :ref:`t3tsref:data-type-string`
	suggest                     :ref:`t3tsref:data-type-boolean`      1
	showSingleResult            :ref:`t3tsref:data-type-boolean`      0
	targetPid                   :ref:`t3tsref:data-type-page-id`
	targetPidPageView           :ref:`t3tsref:data-type-page-id`
	templateFile_               :ref:`t3tsref:data-type-resource`     template.tmpl
	=========================== ===================================== ====================

Statistics
----------

.. container:: ts-properties-tx-dlf-statistics

:typoscript:`plugin.tx_dlf_statistics.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	collections                 :ref:`t3tsref:data-type-list`
	description                 :ref:`t3tsref:data-type-string`
	=========================== ===================================== ====================

TOC
---

.. container:: ts-properties-tx-dlf-toc

:typoscript:`plugin.tx_dlf_toc.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	excludeOther                :ref:`t3tsref:data-type-boolean`      1
	basketButton                :ref:`t3tsref:data-type-boolean`      0
	targetBasket                :ref:`t3tsref:data-type-page-id`
	targetPid                   :ref:`t3tsref:data-type-page-id`
	templateFile_               :ref:`t3tsref:data-type-resource`     template.tmpl
	=========================== ===================================== ====================

Toolbox
-------

.. container:: ts-properties-tx-dlf-toolbox

:typoscript:`plugin.tx_dlf_toolbox.`

	=========================== ===================================== ====================
	Property                    Data type                             Default
	=========================== ===================================== ====================
	pages_                      :ref:`t3tsref:data-type-page-id`
	tools                       :ref:`t3tsref:data-type-list`
	fileGrpsImageDownload       :ref:`t3tsref:data-type-list`         MIN,DEFAULT,MAX
	templateFile_               :ref:`t3tsref:data-type-resource`     template.tmpl
	=========================== ===================================== ====================
