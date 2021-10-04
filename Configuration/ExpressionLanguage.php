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

/*
 * Register the DocumentTypeProvider to be used in TypoScript conditions.
 *
 * Example:
 *
 * [getDocumentType({$config.storagePid}) === 'newspaper']
 *  page.10.variables {
 * 	 isNewspaper = TEXT
 *	 isNewspaper.value = newspaper_anchor
 *  }
 * [END]
 */
return [
    'typoscript' => [
        Kitodo\Dlf\ExpressionLanguage\DocumentTypeProvider::class,
    ]
];
