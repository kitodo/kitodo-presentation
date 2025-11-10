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

        $dlfArguments = [];
        foreach ($viewData['requestData'] as $key => $data) {
            if (is_array($data)) {
                $tempData = [];
                foreach ($data as $dataKey => $dataValue) {
                    if (!in_array($key . '[' . $dataKey . ']', $this->arguments['excludedParams'])) {
                        $tempData[] = $dataValue;
                    }
                }
                if (count($tempData) > 0) {
                    $dlfArguments[$key] = $tempData;
                }
            } elseif (!in_array($key, $this->arguments['excludedParams'])) {
                $dlfArguments[$key] = $data;
            }
        }

        $additionalParams = $this->arguments['additionalParams'];
        foreach ($additionalParams as $key => $value) {
            $dlfArguments[$key] = $value;
        }

        // double replace encoding in URL value parameters
        if (isset($dlfArguments['id'])) {
            $dlfArguments['id'] = $this->doubleEncode($dlfArguments['id']);
        }

        if (isset($dlfArguments['multiViewSource']) && is_array($dlfArguments['multiViewSource'])) {
            $dlfArguments['multiViewSource'] = array_map([$this, 'doubleEncode'], $dlfArguments['multiViewSource']);
        }

        $childContent = (string) $this->renderChildren();

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        // @phpstan-ignore-next-line
        $uriBuilder->setRequest($request);

        if (!empty($this->arguments['pageUid'])) {
            $uriBuilder->setTargetPageUid((int) $this->arguments['pageUid']);
        }

        $uri = $uriBuilder
            ->setArguments(['tx_dlf' => $dlfArguments])
            ->setArgumentPrefix('tx_dlf')
            ->uriFor('main');

        if (!empty($this->arguments['section'])) {
            $uri .= '#' . $this->arguments['section'];
        }

        $tag = new static();
        $tag->tag->setTagName('a');
        $tag->tag->addAttribute('href', $uri);
        $tag->tag->setContent($childContent);

        return $tag->tag->render();
    }

    /**
     * Double encode specific characters in URL.
     *
     * UriBuilder does not properly encode specified entities in URL parameter
     * For more details, please see the following TYPO3 issue https://forge.typo3.org/issues/107026
     *
     * @param string $url The URL in which specific characters should be encoded
     * @return string The replaced URL
     */
    private function doubleEncode(string $url): string
    {
        if (GeneralUtility::isValidUrl($url)) {
            $url = str_replace("%2F", "%252F", $url);
        }
        return $url;
    }
}
