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
   *  `Double page view with word highlighting <https://ddev-kitodo-presentation.ddev.site/workview?tx_dlf[id]=https%3A%2F%2Fdigital.slub-dresden.de%2Fdata%2Fkitodo%2Faufdesun_351357262%2Faufdesun_351357262_mets.xml&tx_dlf[page]=4&tx_dlf[double]=1&tx_dlf[highlight_word]=Dresden>`__

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

In the *Digital Collections* template, the following full text features are available:

*  Click the reading-glass icon on the left to toggle the fulltext overlay.
   When hovering a text line on the image, the corresponding part should be highlighted in the overlay.

*  When showing an indexed document, click the search icon on the right to toggle in-document search.
   Search results should be highlighted on the page.

*  In single page mode, click the download link and select "Fulltext page (TXT)" to download the raw text.

Overview Map and Zoom Buttons
-----------------------------

Additional OpenLayers controls may be configured in TypoScript:

.. code-block:: typoscript

   plugin.tx_dlf_pageview {
     settings {
       features = OverviewMap,ZoomPanel
     }
   }

These are created in ``dlfViewer::createControls_()``.

Loading Indicator
-----------------

A progress element may be configured to be used as loading indicator for static images.
This requires CORS and possibly a non-mixed context, and the server must send a ``Content-Length`` header.

In TypoScript, set ``progressElementId`` to the ID of the ``<progress>`` element:

.. code-block:: typoscript

   plugin.tx_dlf_pageview {
       settings {
           progressElementId = tx-dlf-page-progress
       }
   }

The element may be placed anywhere on the page.

.. code-block:: html

   <progress id="tx-dlf-page-progress"></progress>

For styling, the CSS class ``loading`` is added whenever the loading indicator is in use:

.. code-block:: css

   #tx-dlf-page-progress {
       visibility: hidden;
   }

   #tx-dlf-page-progress.loading {
       visibility: visible;
   }

Tools for Basket Plugin
-----------------------

There are additional tools for the basket plugin:

*   **Magnifier**: Show zoomed page at mouse location in a separate panel.
*   **Cropping Tool**: Select a region that should be added to the baseket.

To insert links for activating these tools into the default PageView template, the following settings may be used:

.. code-block:: typoscript

   plugin.tx_dlf_pageview {
     settings {
       basket {
         magnifier = 1
         crop = 1
       }

       // The basket must be configured for these settings to take effect
       basketButton = 1
       targetBasket = 123
     }
   }

Magnifier
~~~~~~~~~

*  To use the magnifier, the page must contain an element with the id ``ov_map``. It is included in the default PageView template.

   .. code-block:: html

      <div id="ov_map" style="height: 200px;"></div>


*  The magnifier can be activated manually via JavaScript:

   .. code-block:: javascript

      tx_dlf_viewer.activateMagnifier();

Cropping Tool
~~~~~~~~~~~~~

*  Activate and reset manually:

   .. code-block:: javascript

      tx_dlf_viewer.activateSelection();
      tx_dlf_viewer.resetCropSelection();
