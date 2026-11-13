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

// Register JavaScript modules for the backend, see https://docs.typo3.org/permalink/t3coreapi:backend-javascript-es6
return [
    'dependencies' => ['backend'],
    'imports' => [
        '@kitodo/dlf/bootstrap/' => 'EXT:dlf/Resources/Public/JavaScript/Bootstrap/',
    ],
];
