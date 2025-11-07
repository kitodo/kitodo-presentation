<?php
declare(strict_types=1);

namespace Kitodo\Dlf\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface as ExtbaseRequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\Routing\UriBuilder as FrontendUriBuilder;
use TYPO3\CMS\Frontend\Uri\TypolinkCodecService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class LinkViewHelper extends AbstractTagBasedViewHelper
{

    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('viewData', 'array', 'Arguments for the controller action, associative array (do not use reserved keywords "action", "controller" or "format" if not referring to these internal variables specifically)', false, []);
        $this->registerArgument('controller', 'string', 'Target controller. If NULL current controllerName is used');
        $this->registerArgument('extensionName', 'string', 'Target Extension Name (without `tx_` prefix and no underscores). If NULL the current extension name is used');
        $this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
        $this->registerArgument('pageUid', 'int', 'Target page. See TypoLink destination');
        $this->registerArgument('pageType', 'int', 'Type of the target page. See typolink.parameter', false, 0);
        $this->registerArgument('noCache', 'bool', 'Set this to disable caching for the target page. You should not need this.');
        $this->registerArgument('language', 'string', 'link to a specific language - defaults to the current language, use a language ID or "current" to enforce a specific language');
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('format', 'string', 'The requested format, e.g. ".html', false, '');
        $this->registerArgument('linkAccessRestrictedPages', 'bool', 'If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.', false, false);
        $this->registerArgument('additionalParams', 'array', 'Additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)', false, []);
        $this->registerArgument('absolute', 'bool', 'If set, the URI of the rendered link is absolute', false, false);
        $this->registerArgument('addQueryString', 'string', 'If set, the current query parameters will be kept in the URL. If set to "untrusted", then ALL query parameters will be added. Be aware, that this might lead to problems when the generated link is cached.', false, false);
        $this->registerArgument('excludedParams', 'array', 'Arguments to be removed from the URI. Only active if $addQueryString = true', false, []);
    }

    public function render(): string
    {
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        $request = $renderingContext->getRequest();

        $viewData = $this->arguments['viewData'];

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
        $uri = $uriBuilder
            ->setArguments($arguments)
            ->setArgumentPrefix('tx_dlf')
            ->uriFor('main');

        $tag = new static();
        $tag->tag->setTagName('a');
        $tag->tag->addAttribute('href', $uri);
        $tag->tag->setContent($childContent);

        return $tag->tag->render();

        throw new \RuntimeException(
            'The rendering context of ViewHelper f:link.action is missing a valid request object.',
            1690365240
        );
    }
/*
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument('viewData', 'array', 'View data of abstract controller', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $content = $renderChildrenClosure();
        $viewData = $arguments['viewData'];

        var_dump($viewData);

        die();

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = $uriBuilder
            ->setArguments($viewData['requestData'])
            ->setArgumentPrefix('tx_dlf')
            ->uriFor('main');

        $tag = new static();
        $tag->tag->setTagName('a');
        $tag->tag->addAttribute('href', $uri);
        $tag->tag->setContent($content);

        return $tag->tag->render();
    }
*/
}
