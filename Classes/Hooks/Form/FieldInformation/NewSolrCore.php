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

/**
 * FieldInformation renderType for TYPO3 FormEngine
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class NewSolrCore extends AbstractNode
{
    /**
     * Shows Solr core name information when creating new core
     *
     * @access public
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     *               Allowed tags are: "<a><br><br/><div><em><i><p><strong><span><code>"
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();
        // Show only when editing existing records.
        if ($this->data['command'] === 'new') {
            // Load localization file.
            Helper::getLanguageService()->includeLLFile('EXT:dlf/Resources/Private/Language/locallang_be.xlf');
            // Create flash message.
            Helper::addMessage(
                Helper::getLanguageService()->getLL('flash.newCoreName'),
                '', // We must not set a title/header, because <h4> isn't allowed in FieldInformation.
                \TYPO3\CMS\Core\Messaging\FlashMessage::INFO
            );
            // Add message to result array.
            $result['html'] = Helper::renderFlashMessages();
        }
        return $result;
    }
}
