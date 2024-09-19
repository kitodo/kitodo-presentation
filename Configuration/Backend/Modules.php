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

if (!defined('TYPO3')) {
    die('Access denied.');
}

// register backend modules
return [
    'newTenantModule' => [
        'extensionName'         => 'Dlf',
        'parent'                => 'tools',
        'position'              => 'bottom',
        'access'                => 'admin',
        'labels'                => 'LLL:EXT:dlf/Resources/Private/Language/locallang_mod_newtenant.xlf',
        'icon'                  => 'EXT:dlf/Resources/Public/Icons/Extension.svg',
        'navigationComponentId' => '@typo3/backend/page-tree/page-tree-element',
        'controllerActions'     => [
            \Kitodo\Dlf\Controller\Backend\NewTenantController::class => [
                'index','error','addFormat','addMetadata','addSolrCore','addStructure'
            ],
        ],
    ],
];
