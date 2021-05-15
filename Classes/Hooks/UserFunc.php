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

use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\IiifManifest;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper for custom "userFunc"
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class UserFunc implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * This holds the extension's parameter prefix
     * @see \Kitodo\Dlf\Common\AbstractPlugin
     *
     * @var string
     * @access protected
     */
    protected $prefixId = 'tx_dlf';

    /**
     * Helper to display document's thumbnail
     * @see dlf/Configuration/TCA/tx_dlf_documents.php
     *
     * @access public
     *
     * @param array &$params: An array with parameters
     *
     * @return string HTML <img> tag for thumbnail
     */
    public function displayThumbnail(&$params)
    {
        // Simulate TCA field type "passthrough".
        $output = '<input type="hidden" name="' . $params['itemFormElName'] . '" value="' . $params['itemFormElValue'] . '" />';
        if (!empty($params['itemFormElValue'])) {
            $output .= '<img alt="Thumbnail" title="' . $params['itemFormElValue'] . '" src="' . $params['itemFormElValue'] . '" />';
        }
        return $output;
    }
}
