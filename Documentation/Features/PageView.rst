=========
Page View
=========

This document describes features of the pageview plugin and how to test them.

Some of the sample URLs rely on `the Kitodo.Presentation DDEV system <https://github.com/kitodo/ddev-kitodo-presentation>`__.

Image Sources
=============

The viewer supports various image formats. Each of them should work in single page and double page mode.

*  Static Images

   *  https://digital.slub-dresden.de/data/kitodo/dresbides_272362328/dresbides_272362328_mets.xml
   *  `Single page view <https://ddev-kitodo-presentation.ddev.site/workview?tx_dlf[id]=https%3A%2F%2Fdigital.slub-dresden.de%2Fdata%2Fkitodo%2Fdresbides_272362328%2Fdresbides_272362328_mets.xml&tx_dlf[page]=14>`__
   *  `Double page view <https://ddev-kitodo-presentation.ddev.site/workview?tx_dlf[id]=https%3A%2F%2Fdigital.slub-dresden.de%2Fdata%2Fkitodo%2Fdresbides_272362328%2Fdresbides_272362328_mets.xml&tx_dlf[page]=14&tx_dlf[double]=1>`__

*  Static Images with Full Text

   *  https://digital.slub-dresden.de/data/kitodo/aufdesun_351357262/aufdesun_351357262_mets.xml
   *  `Single page view <https://ddev-kitodo-presentation.ddev.site/workview?tx_dlf[id]=https%3A%2F%2Fdigital.slub-dresden.de%2Fdata%2Fkitodo%2Faufdesun_351357262%2Faufdesun_351357262_mets.xml&tx_dlf[page]=4>`__
   *  `Single page view with word highlighting <https://ddev-kitodo-presentation.ddev.site/workview?tx_dlf[id]=https%3A%2F%2Fdigital.slub-dresden.de%2Fdata%2Fkitodo%2Faufdesun_351357262%2Faufdesun_351357262_mets.xml&tx_dlf[page]=4&tx_dlf[highlight_word]=Dresden>`__

*  IIIF Image API

   *  https://iiif.ub.uni-leipzig.de/0000002544/presentation.xml
   *  `Single page view <https://ddev-kitodo-presentation.ddev.site/workview?tx_dlf[id]=https%3A%2F%2Fiiif.ub.uni-leipzig.de%2F0000002544%2Fpresentation.xml&tx_dlf[page]=5>`__
   *  `Double page view <https://ddev-kitodo-presentation.ddev.site/workview?tx_dlf[id]=https%3A%2F%2Fiiif.ub.uni-leipzig.de%2F0000002544%2Fpresentation.xml&tx_dlf[page]=4&tx_dlf[double]=1>`__

*  Zoomify

   *  https://fotothek.slub-dresden.de/zooms/df/dk/0000000/df_dk_0000338/ImageProperties.xml
   *  :download:`minimal_mets_zoomify.xml <./minimal_mets_zoomify.xml>`

*  IIPImage

   *  http://merovingio.c2rmf.cnrs.fr/fcgi-bin/iipsrv.fcgi?FIF=heic0601a.tif
   *  :download:`minimal_mets_iipimage.xml <./minimal_mets_iipimage.xml>`

For Zoomify and IIPImage, the sample METS files may be used via a local file server (e.g., by copying them to the `public/` folder of the DDEV system).

Features
========

Basic Features
--------------

*  Zoom and rotate

   *  Buttons from *Digital Collections* template
   *  Stepless: Drag the image while pressing ``Shift``.
   *  Zoom via mouse wheel
   *  Zoom via ``+`` and ``-`` keys

*  Pan the image

   *  Pan by mouse dragging
   *  Pan via arrow keys

Image Manipulation
------------------

In the *Digital Collections* template, activate the tool by clicking the slider button.

Full Text
---------

In the *Digital Collections* template, click the reading-glass icon.

Overview Map and Zoom Buttons
-----------------------------

Additional OpenLayers controls may be configured in TypoScript:

.. code-block:: typoscript

   plugin.tx_dlf_pageview {
       features = OverviewMap,ZoomPanel
   }

These are created in ``dlfViewer::createControls_()``.

Tools for Basket Plugin
-----------------------

Magnifier
~~~~~~~~~

*  To use the magnifier, the page must contain an element with the id ``ov_map``.

   .. code-block:: typoscript

      <div id="ov_map" style="height: 200px;"></div>

*  Insert a link for activating the magnifier to the page:

   .. code-block:: typoscript

      plugin.tx_dlf_pageview {
          magnifier = 1
      }

   This presupposes that there is a target element on the page:

   .. code-block:: html

      <div class="tx-dlf-navigation-magnifier">###MAGNIFIER###</div>

*  The magnifier can be activated manually via JavaScript:

   .. code-block:: javascript

      tx_dlf_viewer.activateMagnifier();

Cropping Tool
~~~~~~~~~~~~~

The cropping tool is to select a region that should be added to the basket.

*  Insert links to the page:

   .. code-block:: typoscript

      plugin.tx_dlf_pageview {
          crop = 1
      }

   Add target elements:

   .. code-block:: html

      <div class="tx-dlf-navigation-edit">###EDITBUTTON###</div>
      <div class="tx-dlf-navigation-editRemove">###EDITREMOVE###</div>

*  Alternatively, activate and reset manually:

   .. code-block:: javascript

      tx_dlf_viewer.activateSelection();
      tx_dlf_viewer.resetCropSelection();
