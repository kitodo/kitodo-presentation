<?php
declare(strict_types=1);

namespace Kitodo\Dlf\ViewHelpers;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\Routing\UriBuilder as FrontendUriBuilder;
use TYPO3\CMS\Frontend\Uri\TypolinkCodecService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * This view helper generates a link with dlf parameters derived from the current controller’s view data and request information.
 */
final class LinkViewHelper extends AbstractTagBasedViewHelper
{

    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('viewData', 'array', 'View data of the current controller.');
        $this->registerArgument('pageUid', 'int', 'Target page. See TypoLink destination');
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('additionalParams', 'array', 'Additional dlf parameters', false, []);
        $this->registerArgument('excludedParams', 'array', 'Dlf parameters to be removed from the URI.', false, []);
    }

    public function render(): string
    {
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        $request = $renderingContext->getRequest();

        $viewData = $this->arguments['viewData'];

        // UriBuilder does not properly encode specified entities in URL parameter
        // For more details, please see the following TYPO3 issue https://forge.typo3.org/issues/107026
        if (isset($viewData['requestData']['id']) && GeneralUtility::isValidUrl($viewData['requestData']['id'])) {
            $viewData['requestData']['id'] = str_replace("%2F", "%252F", $viewData['requestData']['id']);
        }

        $arguments = [];
        foreach ($viewData['requestData'] as $key => $value) {
            if (!in_array($key, $this->arguments['excludedParams'])) {
                $arguments['tx_dlf'][$key] = $value;
            }
        }

        $additionalParams = $this->arguments['additionalParams'];
        foreach ($additionalParams as $key => $value) {
            $arguments['tx_dlf'][$key] = $value;
        }

        $childContent = (string)$this->renderChildren();

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->setRequest($request);

        if (!empty($this->arguments['pageUid'])) {
            $uriBuilder->setTargetPageUid((int) $this->arguments['pageUid']);
        }

        $uri = $uriBuilder
            ->setArguments($arguments)
            ->setArgumentPrefix('tx_dlf')
            ->uriFor('main');

        if (!empty($this->arguments['section'])) {
            $uri .= '#' . $this->arguments['section'];
        }

        // @phpstan-ignore-next-line
        $tag = new static();
        $tag->tag->setTagName('a');
        $tag->tag->addAttribute('href', $uri);
        $tag->tag->setContent($childContent);

        return $tag->tag->render();
    }
}
