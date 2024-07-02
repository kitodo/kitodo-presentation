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

class ImplodeViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('value', 'array', 'The array to be imploded', true);
        $this->registerArgument('delimiter', 'string', 'The delimiter ', true);
    }

    /**
     * Checks if a value exists in an array.
     *
     * @return string
     */
    public function render()
    {
        $value = $this->arguments['value'];
        $delimiter = $this->arguments['delimiter'];

        return implode($delimiter, $value);
    }
}
