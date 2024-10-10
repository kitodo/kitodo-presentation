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
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * @covers MetadataWrapVariableViewHelper
 */
class MetadataWrapVariableViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function renderingContextCallsGetVariableProviderAdd(): void
    {
        $view = new StandaloneView();

        $view->assign(
            'configObject',
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

        self::assertEquals(
            [
                'key' => ['wrap' => '<label>|</label>'],
                'value' => ['required' => 1, 'wrap' => '<li>|</li>'],
                'all' => ['wrap' => '<article class="shlb-metadata-text-item metadata-title">|</article>']
            ],
            $view->getRenderingContext()->getVariableProvider()->get('metadataWrap')
        );
    }
}
