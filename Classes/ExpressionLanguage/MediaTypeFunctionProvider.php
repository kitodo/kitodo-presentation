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

namespace Kitodo\Dlf\ExpressionLanguage;

use Kitodo\Dlf\Service\MediaPlayerService;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MediaTypeFunctionProvider extends DocumentTypeFunctionProvider
{
    /**
     * Add media detection functions to the ExpressionLanguage
     */
    public function getFunctions(): array
    {
        $functions = parent::getFunctions();

        $functions[] = $this->createMediaFunction(
            'isAudio',
            fn ($service, $doc, $page) => $service->hasAudioSources($doc, $page)
        );
        $functions[] = $this->createMediaFunction(
            'isVideo',
            fn ($service, $doc, $page) => $service->hasVideoSources($doc, $page)
        );

        return $functions;
    }

    /**
     * Factory method to create media detection ExpressionFunctions
     *
     * @param string $name Function name for TypoScript
     * @param \Closure $checker Callback that receives (MediaPlayerService, AbstractDocument, int pageNo)
     */
    private function createMediaFunction(string $name, \Closure $checker): ExpressionFunction
    {
        return new ExpressionFunction(
            $name,
            function () { /* Not implemented, we only use the evaluator */
            },
            function ($arguments, $storagePid) use ($checker) {
                $doc = $this->loadDocumentFromArguments($arguments, $storagePid);
                if ($doc === null) {
                    return false;
                }

                $page = (int)(($arguments['request']?->getQueryParams()['tx_dlf']['page']) ?? 1);
                $mediaService = GeneralUtility::makeInstance(MediaPlayerService::class);

                return $checker($mediaService, $doc, $page);
            }
        );
    }
}
