###################
Manual Viewer Setup
###################

This document describes the verified manual backend setup for a standalone viewer page and configuration folder without using the root setup CLI command.

Working Sequence
================

1. Create a root page (``Create a regular page > Edit page properties > Behavior > Use as root page``)
2. Create or edit the TYPO3 site configuration for that root page in ``Site Management > Sites``.
3. Create these children below the root page (the naming may differ):

   * ``Viewer`` with page type ``Standard``
   * ``Kitodo Configuration`` with page type ``Folder``

4. Create a TypoScript template record (``sys_template``) on the root page:

   * ``Site Management > TypoScript > Edit TypoScript Record (Top Dropdownmenu) > [select the root page] > Create a root TypoScript record``

5. Edit the whole TypoScript record.

   Under the tab ``General``:

   * set the title
   * clear any prefilled content in the ``Setup`` field
   * set the ``Constants`` field to the required values:

     .. code-block:: typoscript

        plugin.tx_dlf.persistence.storagePid = <uid of Kitodo Configuration>
        plugin.tx_dlf.basicViewer.rootPid = <uid of root page>
        plugin.tx_dlf.basicViewer.viewerPid = <uid of Viewer page>

   Under the tab ``Advanced Options``:

   * enable ``Rootlevel``
   * include these TypoScript sets:

     * ``Basic Configuration``
     * ``Install Basic Viewer``

6. Apply tenant defaults to the configuration folder through the backend new-tenant module.
