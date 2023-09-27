<?php

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

// Register backend module.
if (\TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Kitodo.Dlf',
        'tools', // Main area
        'newTenantModule', // Name of the module
        'bottom', // Position of the module
        [// Allowed controller action combinations
            \Kitodo\Dlf\Controller\Backend\NewTenantController::class => 'index,error,addFormat,addMetadata,addSolrCore,addStructure',
        ],
        [// Additional configuration
            'access'    => 'admin',
            'icon'      => 'EXT:dlf/Resources/Public/Icons/Extension.svg',
            'labels'    => 'LLL:EXT:dlf/Resources/Private/Language/locallang_mod_newtenant.xlf',
            'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement'
        ],
    );
}
