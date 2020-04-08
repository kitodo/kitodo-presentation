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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ViewHelper to get page info
 *
 * # Example: Basic example
 * <code>
 * <si:pageInfo page="123">
 *	<span>123</span>
 * </code>
 * <output>
 * Will output the page record
 * </output>
 *
 * @package TYPO3
 */
class XpathViewHelper extends \Kitodo\Dlf\ViewHelpers\AbstractViewHelper
{

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('xpath', string, 'Xpath', true);
    }


    /**
     * Render the supplied DateTime object as a formatted date.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $xpath = $arguments['xpath'];
        $this->init();
        // Load current document.
        $this->loadDocument();
        if (
            $this->doc === null
            || $this->doc->numPages < 1
        ) {
            // Quit without doing anything if required variables are not set.
            return $content;
        } else {
            $result = $this->doc->getXpath($xpath);
        }

        if (is_array($result)) {
          foreach ($result as $row) {
            $output .= trim($row) . ' ';
          }
        } else {
          $output = trim($result);
        }

        return htmlspecialchars(trim($output));
    }
}
