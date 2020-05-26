.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _system_setup:

============
System Setup
============

.. contents::
    :local:
    :depth: 2



***********
TYPO3 Setup
***********

The navigation plugin provides a page selection dropdown input field. The
resulting action url cannot contain a valid cHash value.

The default behaviour of TYPO3 is to call the pageNotFound handler and/or to show an exception:

.. figure:: ../Images/Configuration/typo3_pagenotfoundonchasherror.png
   :width: 800px
   :alt: TYPO3 Error-Message "Reason: Request parameters could not be validated (&cHash empty)"

   TYPO3 Error-Message "Reason: Request parameters could not be validated (&cHash empty)"

This is not the desired behaviour. You should configure in the TYPO3 install tool
$TYPO3_CONF_VARS['FE']['pageNotFoundOnCHashError']=0 to show the requested page
instead. The caching will be disabled in this case. This was the default
behaviour before TYPO3 6.x.

.. figure:: ../Images/Configuration/typo3_install_pagenotfoundonchasherror.png
   :width: 800px
   :alt: TYPO3 Configuration of pageNotFoundOnCHashError in Install Tool

   TYPO3 Configuration of pageNotFoundOnCHashError in Install Tool

The install tool writes this configuration to typo3conf/LocalConfiguration.php::

    'FE' => [
            'pageNotFoundOnCHashError' => '0',
            'pageNotFound_handling' => '',
        ],


.. _configuration-solr:

*****************
Solr Installation
*****************

This extension doesn't include Solr, but just a prepared configuration set.
To setup Apache Solr, perform the following steps:

1. Make sure you have Apache Solr 7.x up and running.
    Download Solr from http://lucene.apache.org/solr/.
    Other versions since 5.0 should be possible but are not tested.

2. Copy the lib/ApacheSolr/configsets/dlf to $SOLR_HOME/configsets/dlf.

3. Using basic authentication is optional but recommended.
    The documentation is available here:
    https://lucene.apache.org/solr/guide/7_4/basic-authentication-plugin.html


.. _configuration-typoscript:

******************************
TypoScript Basic Configuration
******************************

Please include the Template "Basic Configuration (dlf)". This template adds
jQuery to your page by setting the following typoscript:

:typoscript:`page.includeJSlibs.jQuery`
