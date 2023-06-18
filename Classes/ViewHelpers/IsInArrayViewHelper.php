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

class IsInArrayViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('needle', 'mixed', 'The searched value', true);
        $this->registerArgument('haystack', 'array', 'The array', true);
    }

    /**
     * Checks if a value exists in an array.
     *
     * @return bool
     */
    public function render()
    {
        $needle = $this->arguments['needle'];
        $haystack = $this->arguments['haystack'];

        return in_array($needle, $haystack);
    }
}
