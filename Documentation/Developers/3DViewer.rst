========
3D Viewer
========

On this page, you will find all the information to use the 3D Viewer in Kitodo.Presentation. The `model-viewer <https://modelviewer.dev>`_ is installed as the build-in standard viewer and supports ‘glTF/GLB 3D models’ as the model file format. Alternatively you can use one or multiple custom viewer implementations or our reference implementations from the GitHub repository `slub/dlf-3d-viewers <https://github.com/slub/dlf-3d-viewers>`_.

.. contents::
    :local:
    :depth: 2

Setup
=======

-  Add folder with name ``dlf_3d_viewers`` in your default storage

-  Add a subfolder with name of your custom 3D viewer (see :ref:`Custom Viewer`) e.g. ``3dviewer`` or use one or more viewer folders of our reference implementation in GitHub Repository `slub/dlf-3d-viewers <https://github.com/slub/dlf-3d-viewers>`_.

.. IMPORTANT::
   When creating folders through the Filelist module in TYPO3, follow the usual process. However, when creating folders in the system, ensure that the name is URL-compliant.

Configuration
=======

By default, the viewers from the folder ``dlf_3d_viewers`` are all active and can be accessed and tested via URL.

For this, only the parameter ``tx_dlf[viewer]`` with the name of the viewer and the encoded URL to the model via the parameter ``tx_dlf[model]`` need to be passed to the URL that the plugin ``plugin.tx_dlf_view3d`` renders.

.. note::
   For example in the DFG Viewer, this is the page whose ID is set via the constant ``config.kitodoPageView``.

.. _Automatic selection of the viewer:

Automatic selection of the viewer
-------

Under the configuration of the ``dlf`` extension, you will find a tab to configure 3D viewers for automatic selection.

With the configuration field "Viewer model format mapping," you can define a list of considered viewers from the ``dlf_3d_viewers`` folder along with their associated model formats.
If there are multiple viewers that support the same model format, you can decide here which one is responsible for the specific format.

Additionally, a default viewer can be set, which serves as a fallback for all model formats that have not been mapped.

.. _Custom Viewer:

Custom Viewer
=======

Viewers can be added and customized depending on the use case. A viewer is a folder with the name of the viewer that contains a ``dlf-3d-viewer.yml`` file and at least one HTML file.
A reference implementation of various 3D viewers for integration into Kitodo.Presentation can be found on GitHub in Repository `slub/dlf-3d-viewers <https://github.com/slub/dlf-3d-viewers>`_.

dlf-3d-viewer.yml
-------

To configure the 3D viewer for Kitodo.Presentation, a ``dlf-3d-viewer.yml`` file must be present in the viewer directory.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Key
     :description:              Description

   - :field:                    base
     :description:              Specify the name of the HTML file in which the viewer will be displayed. (Default is ``index.html``)

   - :field:                    supportedModelFormats (required)
     :description:              Specify single or multiple supported model formats of the viewer.

Example
^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: yaml
   :caption: defaultStorage/dlf_3d_viewers/3dviewer/dlf-3d-viewer.yml

   viewer:
    base: main.html
    supportedModelFormats:
      - glf
      - ply

Placeholders
-------

Placeholders can be used within the file which is define under the ``base`` key of ``dlf-3d-viewer.yml``. The notation for placeholders is ``{{placeholderName}}``. The following placeholders are available:

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Name
     :description:              Description

   - :field:                    viewerPath
     :description:              Path to the viewer directory located inside the ``dlf_3d_viewers`` folder. For example "fileadmin/dlf_3d_viewers/3dviewer/".

   - :field:                    modelUrl
     :description:              The fileserver where your resource is hosted. For example "https://example.com/my-model.glb".

   - :field:                    modelPath
     :description:              Part of the ``modelUrl`` where your resource is hosted. For example, if your resource ist hosted at "https://example.com/my-model.glb", the value would be "https://example.com/static/models/".

   - :field:                    modelResource
     :description:              Resource part of the ``modelUrl`` with the filename to be loaded from the endpoint. For example, if your resource ist hosted at "https://example.com/my-model.glb", the value would be "my-model.glb".

