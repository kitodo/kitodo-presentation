.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

Configuration Reference
=======================

.. _system_setup:

TYPO3 Setup
-----------

The navigation plugin provides a page selection dropdown input field. The
resulting action url cannot contain a valid cHash value.

The default behaviour of TYPO3 is to call the pageNotFound handler and/or to show an exception:

.. figure:: ../Images/Configuration/typo3_pagenotfoundonchasherror.png
   :width: 800px
   :alt: TYPO3 Error-Message "Reason: Request parameters could not be validated (&cHash empty)"

   TYPO3 Error-Message "Reason: Request parameters could not be validated (&cHash empty)"



This is not the desired behaviour. You should configure in the TYPO3 install tool
$TYPO3_CONF_VARS['FE'][pageNotFoundOnCHashError]=0 to show the requested page
instead. The caching will be disabled in this case. This was the default
behaviour of TYPO3 4.x.

.. figure:: ../Images/Configuration/typo3_install_pagenotfoundonchasherror.png
   :width: 800px
   :alt: TYPO3 Configuration of pageNotFoundOnCHashError in Install Tool

   TYPO3 Configuration of pageNotFoundOnCHashError in Install Tool

The install tool writes this configuration to typo3conf/LocalConfiguration.php::

	'FE' => [
	        'pageNotFoundOnCHashError' => '0',
	        'pageNotFound_handling' => '',
	    ],


.. _configuration-typoscript:

TypoScript Reference
--------------------

Plugin Reference
----------------

.. contents::
	:local:
	:depth: 1

Audioplayer
^^^^^^^^^^^

Basket
^^^^^^

Collection
^^^^^^^^^^

Feeds
^^^^^

Listview
^^^^^^^^

Metadata
^^^^^^^^

Newspaper
^^^^^^^^^

OAI
^^^

Pageview
^^^^^^^^

Search
^^^^^^

Statistics
^^^^^^^^^^

TOC
^^^

Toolbox
^^^^^^^



Possible subsections: Reference of TypoScript options.
The construct below show the recommended structure for
TypoScript properties listing and description.

Properties should be listed in the order in which they
are executed by your extension, but the first should be
alphabetical for easier access.

When detailing data types or standard TypoScript
features, don't hesitate to cross-link to the TypoScript
Reference as shown below. See the :file:`Settings.yml`
file for the declaration of cross-linking keys.


Properties
^^^^^^^^^^

.. container:: ts-properties

	=========================== ===================================== ======================= ====================
	Property                    Data type                             :ref:`t3tsref:stdwrap`  Default
	=========================== ===================================== ======================= ====================
	allWrap_                    :ref:`t3tsref:data-type-wrap`         yes                     :code:`<div>|</div>`
	`subst\_elementUid`_        :ref:`t3tsref:data-type-boolean`      no                      0
	wrapItemAndSub_             :ref:`t3tsref:data-type-wrap`
	=========================== ===================================== ======================= ====================


Property details
^^^^^^^^^^^^^^^^

.. only:: html

	.. contents::
		:local:
		:depth: 1


.. _ts-plugin-tx-extensionkey-stdwrap:

allWrap
"""""""

:typoscript:`plugin.tx_extensionkey.allWrap =` :ref:`t3tsref:data-type-wrap`

Wraps the whole item.


.. _ts-plugin-tx-extensionkey-wrapitemandsub:

wrapItemAndSub
""""""""""""""

:typoscript:`plugin.tx_extensionkey.wrapItemAndSub =` :ref:`t3tsref:data-type-wrap`

Wraps the whole item and any submenu concatenated to it.


.. _ts-plugin-tx-extensionkey-substelementUid:

subst_elementUid
""""""""""""""""

:typoscript:`plugin.tx_extensionkey.subst_elementUid =` :ref:`t3tsref:data-type-boolean`

If set, all appearances of the string ``{elementUid}`` in the total element html-code (after wrapped in allWrap_)
is substituted with the uid number of the menu item. This is useful if you want to insert an identification code
in the HTML in order to manipulate properties with JavaScript.


.. _configuration-faq:

FAQ
---

Possible subsection: FAQ
