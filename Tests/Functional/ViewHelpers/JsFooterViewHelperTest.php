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

namespace Kitodo\Dlf\Tests\Unit\ViewHelpers;

use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use Kitodo\Dlf\ViewHelpers\JsFooterViewHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Fluid\View\FluidViewFactory;

#[CoversClass(JsFooterViewHelper::class)]
class JsFooterViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    /**
     * Test rendering includes JavaScript footer inline code.
     */
    #[Test]
    public function pageRendererCallsAddJsFooterInlineCode(): void
    {
        $pageRendererProphecy = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->getMock();

        $pageRendererProphecy->expects(self::once())->method('addJsFooterInlineCode')->with(
            'js-dlf-inline-footer',
            '$(document).ready(function() {});'
        );

        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRendererProphecy);

        $view = $this->get(FluidViewFactory::class)->create(new ViewFactoryData());
        $view->getRenderingContext()->getTemplatePaths()->setTemplateSource(
            '<html xmlns:kitodo="http://typo3.org/ns/Kitodo/Dlf/ViewHelpers">
                <kitodo:jsFooter inlineCode="$(document).ready(function() {});" />
            </html>'
        );

        $view->render();
    }
}
