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

namespace Kitodo\Dlf\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Add inline JavaScript code to footer
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class JsFooterViewHelper extends AbstractViewHelper
{
    /**
     * Initialize arguments.
     *
     * @access public
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('inlineCode', 'string', 'Inline JavaScript', true);
    }

    /**
     * @access public
     *
     * @static
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): void
    {
        $inlineCode = $arguments['inlineCode'];

        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsFooterInlineCode('js-dlf-inline-footer', $inlineCode);
    }
}
