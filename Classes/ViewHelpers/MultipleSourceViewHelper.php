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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class MultipleSourceViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('docArrayKey', 'string', 'The array key of the doc array', true);
    }

    /**
     * Checks whether docArray starts with the prefix multipleSource_ and returns the numeric postfix.
     *
     * @return int
     */
    public function render()
    {
        if (preg_match('/^multipleSource_(\d+)$/', $this->arguments['docArrayKey'], $matches)) {
            return (int)$matches[1];
        }
        return -1;
    }
}
