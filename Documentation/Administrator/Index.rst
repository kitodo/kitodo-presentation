.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual:

====================
Administrator Manual
====================

.. contents::
   :local:
   :depth: 2



************
Installation
************

It is highly recommended to install this extension by using composer.

However, there are two options to install the required packages solarium/solarium and symfony/event-dispatcher in non-composer mode:

a. Run the command "composer update" within the directory of the extension.
   All required packages are downloaded automatically to the vendor subdirectory.

b. Download the required packages manually to vendor/solarium/solarium and vendor/symfony/event-dispatcher.
   Please check the require sections (composer.json) of the extension, solarium and event-dispatcher
   to download and install matching versions.

After the installation of the packages in non-composer mode you have to deactivate and (re-)activate the extension in the extension manager to trigger the TYPO3 autoloader to rebuild the classmap.
