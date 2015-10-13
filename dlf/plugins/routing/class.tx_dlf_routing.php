<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */

/**
 * Search suggestions for the plugin 'DLF: Search' of the 'dlf' extension.
 *
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_routing extends tslib_pibase {

    public $scriptRelPath = 'plugins/routing/class.tx_dlf_routing.php';

    /**
     * The main method of the PlugIn
     *
     * @access	public
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     *
     * @return	void
     */
    public function main($content = '', $conf = array ()) {

        $qucosaId = t3lib_div::_GP('qid');

        $fileId = t3lib_div::_GP('fid');

        $namespace = t3lib_div::_GP('namespace');

        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dlf']);

        if(t3lib_extMgm::isLoaded('realurl')){
            require_once(t3lib_extMgm::extPath('realurl').'class.tx_realurl.php');
            $this->realurl = t3lib_div::makeInstance('tx_realurl');
            $GLOBALS['TSFE']->config['config']['tx_realurl_enable'] = 1;
        }

        // use urlencode instead!
        // $qucosaId = str_replace('-', ":", $qucosaId);

        $path = rtrim($extConf['repositoryServerAdress'],"/").'/fedora/objects/'.$namespace.':'.$qucosaId.'/datastreams/'.$fileId.'/content';

        // get remote header
        $headers = get_headers($path);

        foreach ($headers as $key => $value) {
            // set remote header information
            preg_match('/filename="(.*)"/', $value, $treffer);
            if($treffer[1]) {
                header('Content-Disposition: attachment; filename="'.$treffer[1].'";');
            }
            if(substr($value, 0, 13) == "Content-Type:") {
                header($value);
            }
            if(substr($value, 0, 13) == "Content-Length:") {
                header($value);
            }
        }

        if ($stream = fopen($path, 'r')) {
            fpassthru($stream);
            fclose($stream);
        }

        exit;

    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/routing/class.tx_dlf_routing.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/routing/class.tx_dlf_routing.php']);
}

// $cObj = t3lib_div::makeInstance('tx_dlf_routing');

// $cObj->main();

?>
