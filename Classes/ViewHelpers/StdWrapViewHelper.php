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

use \RuntimeException;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Standard wrapper view helper
 * 
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class StdWrapViewHelper extends AbstractViewHelper
{
    /**
     * @access protected
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes arguments.
     *
     * @access public
     *
     * @return void
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('wrap', 'string', 'The wrap information', true);
        $this->registerArgument('data', 'array', 'Data for the content object', false);
    }

    /**
     * Wraps the given value
     *
     * @access public
     *
     * @thorws RuntimeException if view helper is used outside of request context
     * @return string
     */
    public function render(): string
    {
        $wrap = $this->arguments['wrap'];
        $data = $this->arguments['data'] ?? [];

        /** @var \TYPO3\CMS\Fluid\Core\Rendering\RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        if (!$renderingContext->getRequest()) {
            throw new RuntimeException('Required request not found in RenderingContext');
        }
        $request = $renderingContext->getRequest();
        $cObj = $request->getAttribute('currentContentObject');

        $insideContent = $this->renderChildren();

        $prevData = $cObj->data;
        $cObj->data = $data;
        try {
            $result = $cObj->stdWrap($insideContent, $wrap);
        } finally {
            $cObj->data = $prevData;
        }

        return $result;
    }
}
