<?php

namespace Kitodo\Dlf\Hooks;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Kitodo\Dlf\Common\Helper;

/**
 * Hooks and helper for \TYPO3\CMS\Core\Utility\ExtensionManagementUtility
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ExtensionManagementUtility extends \TYPO3\CMS\Core\Utility\ExtensionManagementUtility
{
    /**
     * Add plugin to static template for css_styled_content
     * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43()
     *
     * @access public
     *
     * @param string $key: The extension key
     * @param string $class: The qualified class name
     * @param string $suffix: The uid of the record
     * @param string $type: Determines the type of the frontend plugin
     * @param bool $cached: Should we created a USER object instead of USER_INT?
     *
     * @return void
     */
    public static function addPItoST43($key, $class, $suffix = '', $type = 'list_type', $cached = FALSE)
    {
        $internalName = 'tx_' . $key . '_' . strtolower(Helper::getUnqualifiedClassName($class));
        // General plugin
        $typoscript = 'plugin.' . $internalName . ' = USER' . ($cached ? '' : '_INT') . "\n";
        $typoscript .= 'plugin.' . $internalName . '.userFunc = ' . $class . '->main' . "\n";
        parent::addTypoScript($key, 'setup', $typoscript);
        // Add after defaultContentRendering
        switch ($type) {
            case 'list_type':
                $addLine = 'tt_content.list.20.' . $key . $suffix . ' = < plugin.' . $internalName;
                break;
            default:
                $addLine = '';
        }
        if ($addLine) {
            parent::addTypoScript($key, 'setup', $addLine, 'defaultContentRendering');
        }
    }
}
