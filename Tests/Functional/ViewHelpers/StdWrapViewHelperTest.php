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
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * @covers StdWrapViewHelper
 */
class StdWrapViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    /**
     * @test
     */
    public function renderWithStdWrap(): void
    {
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $request = new ServerRequest();
        $request = $request->withAttribute("currentContentObject", $cObj);

        $view = new StandaloneView();
        $view->setRequest($request);

        $view->assign(
            'metadataWrap',
            [
                'key' => ['wrap' => '<label>|</label>'],
                'value' => ['required' => 1, 'wrap' => '<li>|</li>'],
                'all' => ['wrap' => '<article class="shlb-metadata-text-item metadata-title">|</article>']
            ]
        );

        // A fully filled array with correct values does not make any difference. The rendering result
        // is not been influenced by the viewhelpers data parameter.
        $view->assign('metaSectionCObj', [0 => ['tilte' => 'A test title']]);

        $view->setTemplateSource(
            '<html xmlns:kitodo="http://typo3.org/ns/Kitodo/Dlf/ViewHelpers">
              <kitodo:stdWrap wrap="{metadataWrap.all}" data="{metaConfigObjectData.0}">
                <kitodo:stdWrap wrap="{metadataWrap.key}" data="{metaConfigObjectData.0}">Label</kitodo:stdWrap>
                    <h2>Title</h2><p>Text</p>
                </kitodo:stdWrap>
            </html>'
        );

        self::assertXmlStringEqualsXmlString(
            '<html xmlns:kitodo="http://typo3.org/ns/Kitodo/Dlf/ViewHelpers">
              <article class="shlb-metadata-text-item metadata-title">
                <label>Label</label>
                    <h2>Title</h2><p>Text</p>
                </article>
            </html>',
            $view->render()
        );

        // Without using the data parameter the rendering result is the same as above.
        $view->setTemplateSource(
            '<html xmlns:kitodo="http://typo3.org/ns/Kitodo/Dlf/ViewHelpers">
              <kitodo:stdWrap wrap="{metadataWrap.all}">
                <kitodo:stdWrap wrap="{metadataWrap.key}">Label</kitodo:stdWrap>
                    <h2>Title</h2><p>Text</p>
                </kitodo:stdWrap>
            </html>'
        );

        self::assertXmlStringEqualsXmlString(
            '<html xmlns:kitodo="http://typo3.org/ns/Kitodo/Dlf/ViewHelpers">
              <article class="shlb-metadata-text-item metadata-title">
                <label>Label</label>
                    <h2>Title</h2><p>Text</p>
                </article>
            </html>',
            $view->render()
        );
    }
}
