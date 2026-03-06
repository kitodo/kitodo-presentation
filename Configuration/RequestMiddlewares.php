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

return [
    'frontend' => [
        'dlf/page-view-proxy' => [
            'target' => \Kitodo\Dlf\Middleware\PageViewProxy::class,
            // Ensure this runs before the router/dispatcher so it can handle the request
            'before' => [
                // replace with the actual TYPO3 router middleware id if known
                'typo3/cms-frontend/router'
            ],
            //'after' => [],
            'priority' => 50,
        ],
        'dlf/search-in-document' => [
            'target' => \Kitodo\Dlf\Middleware\SearchInDocument::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ]
        ],
        'dlf/search-suggest' => [
            'target' => \Kitodo\Dlf\Middleware\SearchSuggest::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ]
        ],
        'dlf/embedded3DViewer' => [
            'target' => \Kitodo\Dlf\Middleware\Embedded3dViewer::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ]
        ],
        'dlf/domDocumentValidation' => [
            'target' => \Kitodo\Dlf\Middleware\DOMDocumentValidation::class,
            'after' => [
                'typo3/cms-frontend/prepare-tsfe-rendering'
            ]
        ]
    ],
];
