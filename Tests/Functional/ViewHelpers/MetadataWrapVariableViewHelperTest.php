<?php

namespace Kitodo\Dlf\Tests\Unit\ViewHelpers;

use Kitodo\Dlf\Tests\Functional\FunctionalTestCase;
use Kitodo\Dlf\ViewHelpers\MetadataWrapVariableViewHelper;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;

/**
 * @covers MetadataWrapVariableViewHelper
 */
class MetadataWrapVariableViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    /**
     * @test
     */
    public function renderingContextCallsGetVariableProviderAdd(): void
    {
        $view = new StandaloneView();

        $view->assign('configObject',
            [ 'wrap' => 'all.wrap = <article class="shlb-metadata-text-item metadata-title">|</article>
                 key.wrap = <label>|</label>
                 value.required = 1
                 value.wrap = <li>|</li>'
            ]
        );
        $view->setTemplateSource(
            '<html xmlns:kitodo="http://typo3.org/ns/Kitodo/Dlf/ViewHelpers">
                {configObject.wrap -> kitodo:metadataWrapVariable(name: \'metadataWrap\')}
            </html>'
        );
        $view->render();

        $this->assertEquals(
            [
                'key' => ['wrap' => '<label>|</label>'],
                'value' => ['required' => 1, 'wrap' => '<li>|</li>'],
                'all' => ['wrap' => '<article class="shlb-metadata-text-item metadata-title">|</article>']
            ],
            $view->getRenderingContext()->getVariableProvider()->get('metadataWrap')
        );
    }
}
