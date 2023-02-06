<?php

namespace Kitodo\Dlf\Tests\Unit\ViewHelpers;

use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use Kitodo\Dlf\ViewHelpers\JsFooterViewHelper;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * @covers JsFooterViewHelper
 */
class JsFooterViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    /**
     * @test
     */
    public function pageRendererCallsAddJsFooterInlineCode(): void
    {
        $pageRendererProphecy = $this->getMockBuilder(PageRenderer::class)
            ->onlyMethods(['addJsFooterInlineCode'])
            ->getMock();

        $pageRendererProphecy->expects(self::once())->method('addJsFooterInlineCode')->with(
            'js-dlf-inline-footer', '$(document).ready(function() {});'
        );

        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRendererProphecy);

        $view = new StandaloneView();
        $view->setTemplateSource(
            '<html xmlns:kitodo="http://typo3.org/ns/Kitodo/Dlf/ViewHelpers">
                <kitodo:jsFooter inlineCode="$(document).ready(function() {});" />
            </html>'
        );

        $view->render();
    }
}
