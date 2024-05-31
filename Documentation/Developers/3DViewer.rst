========
3D Viewer
========



Setup
=======

-  Add folder with name ``dlf_3d_viewers`` in your default storage

-  Add a subfolder with name of your 3D viewer e.g. ``3dviewer``

.. IMPORTANT::
   When creating folders through the Filelist module in TYPO3, follow the usual process. However, when creating folders in the system, ensure that the name is URL-compliant.

Viewer
=======

To configure the 3D Viewer for Kitodo.Presentation, a ``dlf-3d-viewer.yml`` file must be present in the viewer directory.

dlf-3d-viewer.yml
-------

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    base (required)
     :description:              Specify the name of the HTML file in which the viewer will be displayed, e.g. ``main.html`` or ``index.html``

   - :field:                    prependUrl (optional)
     :description:              Specify single value or multiple values to prepend with the URL for the viewer resources.

                                prependUrl:
                                      - stylesheet/styles.css
                                      - js/main.js
                                      - js/init.js

   - :field:                    url (optional)
     :description:              Specifiy url to external viewer resources. The default is the path to folder viewer under the ``dlf_3d_viewers``.

Simple Example
^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: yaml
   :caption: defaultStorage/dlf_3d_viewers/3dviewer/dlf-3d-viewer.yml

   viewer:
    base: main.html
    prependUrl:
        - stylesheet/styles.css
        - js/main.js
        - js/init.js

Example with external URL
^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: yaml
   :caption: defaultStorage/dlf_3d_viewers/3dviewer/dlf-3d-viewer.yml

   viewer:
    url: https://raw.githubusercontent.com/example/3dviewer/master/
    base: index.html
    prependUrl:
        - js/init.js
