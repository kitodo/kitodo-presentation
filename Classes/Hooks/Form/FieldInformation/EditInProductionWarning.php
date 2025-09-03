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

namespace Kitodo\Dlf\Hooks\Form\FieldInformation;

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * FieldInformation renderType for TYPO3 FormEngine
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class EditInProductionWarning extends AbstractNode
{
    /**
     * Generates warning message when editing 'index_name' field
     *
     * @access public
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();
        // Show warning only when editing existing records.
        if ($this->data['command'] !== 'new') {
            // Create flash message.
            Helper::addMessage(
                htmlspecialchars(Helper::getLanguageService()->sL('LLL:EXT:dlf/Resources/Private/Language/locallang_be.xlf:flash.editInProductionWarning')),
                '', // We must not set a title/header, because <h4> isn't allowed in FieldInformation.
                ContextualFeedbackSeverity::WARNING
            );
            // Add message to result array.
            $result['html'] = Helper::renderFlashMessages();
        }
        return $result;
    }
}
