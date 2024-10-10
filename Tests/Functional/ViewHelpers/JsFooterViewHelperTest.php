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
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function pageRendererCallsAddJsFooterInlineCode(): void
    {
        $pageRendererProphecy = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->getMock();

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
