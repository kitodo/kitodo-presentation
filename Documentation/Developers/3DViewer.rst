========
3D Viewer
========

Setup
=======

-  Add folder with name ``dlf_3d_viewers`` in your default storage

-  Add a subfolder with name of your custom 3D viewer (see :ref:`Custom Viewer`) e.g. ``3dviewer`` or use one viewer folder of our reference implementation in GitHub Repository `slub/dlf-3d-viewers <https://github.com/slub/dlf-3d-viewers>`_.

.. IMPORTANT::
   When creating folders through the Filelist module in TYPO3, follow the usual process. However, when creating folders in the system, ensure that the name is URL-compliant.

.. _Custom Viewer:

Custom Viewer
=======

Viewers can be added and customized depending on the use case. A viewer is a folder with the name of the viewer that contains a ``dlf-3d-viewer.yml`` file and at least one HTML file.
A reference implementation of various 3D viewers for integration into Kitodo.Presentation can be found on GitHub in Repository `slub/dlf-3d-viewers <https://github.com/slub/dlf-3d-viewers>`_.

dlf-3d-viewer.yml
-------

To configure the 3D Viewer for Kitodo.Presentation, a ``dlf-3d-viewer.yml`` file must be present in the viewer directory.

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

