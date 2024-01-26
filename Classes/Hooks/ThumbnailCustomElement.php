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

namespace Kitodo\Dlf\Hooks;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;

/**
 * Custom Thumbnail element for Form
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class ThumbnailCustomElement extends AbstractFormElement
{

    /**
     * Renders thumbnail custom element.
     *
     * @access public
     *
     * @return array
     */
    public function render(): array
    {
        // Custom TCA properties and other data can be found in $this->data, for example the above
        // parameters are available in $this->data['parameterArray']['fieldConf']['config']['parameters']
        $result = $this->initializeResultArray();
        if (!empty($this->data['databaseRow']['thumbnail'])) {
            $result['html'] = '<img alt="Thumbnail" title="" src="' . $this->data['databaseRow']['thumbnail'] . '" />';
        } else {
            $result['html'] = '';
        }
        return $result;
    }
}
