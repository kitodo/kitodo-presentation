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
 *
 *
 * Example Audio/Video Mediaplayer:
 *
 * page.10.variables {
 *     isAudio = TEXT
 *     isAudio.value = 0
 *     isVideo = TEXT
 *     isVideo.value = 0
 * }
 *
 * [isAudio({$plugin.tx_dlf.persistence.storagePid})]
 *     page.10.variables.isAudio.value = 1
 * [END]
 *
 * [isVideo({$plugin.tx_dlf.persistence.storagePid})]
 *     page.10.variables.isVideo.value = 1
 * [END]
 */
return [
    'typoscript' => [
        Kitodo\Dlf\ExpressionLanguage\DocumentTypeProvider::class,
    ]
];
