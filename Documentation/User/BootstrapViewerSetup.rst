######################
Bootstrap Viewer Setup
######################

This document describes the manual bootstrap setup for a new Kitodo.Presentation root page tree and its standalone viewer.

Bootstrap Root Setup
====================

The bootstrap root setup is implemented by:

* ``Classes/Command/BootstrapSetupCommand.php``
* ``Classes/Service/BootstrapRootSetupService.php``
* ``Resources/Private/Data/BootstrapSiteConfig.yaml``

Run the command with generated defaults:

::

   php /var/www/typo3/vendor/bin/typo3 kitodo:setup

You can also override the generated values:

::

   php /var/www/typo3/vendor/bin/typo3 kitodo:setup \
     --identifier=my-instance \
     --base=/my-instance/ \
     --root-title="My Instance" \
     --root-slug=/ \
     --viewer-slug=/viewer

The command creates:

* a new root page
* a ``Viewer`` page below the root page
* a ``Kitodo Configuration`` sysfolder below the root page
* a site configuration in ``config/sites/<identifier>/config.yaml``
* a root ``sys_template`` record with the required static TypoScript includes

The root page stays the bootstrap landing page. The child ``Viewer`` page is the standalone viewer shell.

The command also rewrites the template constants so the basic viewer TypoScript receives the generated page IDs:

* ``plugin.tx_dlf.persistence.storagePid``
* ``plugin.tx_dlf.basicViewer.rootPid``
* ``plugin.tx_dlf.basicViewer.viewerPid``

Generated Defaults
------------------

If no options are provided, the service generates unique values for:

* the site identifier
* the site base
* the root page title
* the viewer page slug

For the first bootstrap site, the service prefers ``/`` as the site base if it is still unused. Later setups use ``/<identifier>/``.
The generated site configuration also ships with two enabled languages by default: German on the site base and English below ``/en/``.

Validation Rules
----------------

The bootstrap command validates the custom options as follows:

* ``--identifier`` may only contain lowercase letters, digits and hyphens.
* ``--base`` must start with ``/`` and is normalized to end with ``/``.
* ``--root-slug`` and ``--viewer-slug`` must start with ``/``.
* ``--viewer-slug`` must not be ``/``.
* Custom root slugs are only supported when the site base is ``/``.
* The site identifier must not exist already.
* The site base must not exist already.
* The root page title must not exist already among root pages.

Viewer Wiring
=============

The standalone viewer is configured through:

* ``Configuration/TypoScript/BasicViewer/setup.typoscript``
* ``Resources/Private/Templates/Bootstrap/Landing.html``
* ``Resources/Private/Templates/Bootstrap/Viewer.html``
* ``Resources/Public/Stylesheets/default-viewer.css``
