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

It is strictly recommended to install this extension by using the composer.

There are two options to install the required packages solarium and symphony/event-dispatcher in non-composer mode:

a. Run the command "composer update" within the directory of the extension.
   All the required packages are downloaded automatically to the vendor subdirectory.

b. Download the required packages manually to vendor/solarium an vendor/symphony/event-dispatcher.
   Please check the require sections (composer.json) of the extension, solarium and event-dispatcher
   to download and install matching versions.

After the installation of the packages in non-composer mode you have to deactivate and (re-)activate the extension.