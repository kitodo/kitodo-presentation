<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Sebastian Meyer <sebastian.meyer@slub-dresden.de>
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

$MCONF['name'] = 'txdlfmodules';

$MCONF['access'] = 'user,group';

$MCONF['script'] = '_DISPATCH';

$MCONF['defaultMod'] = 'txdlfdocuments';

// Check path to typo3/ directory (depending on local/global installation of extension)
// TODO: This is quite ugly and should be changed to something nicer!
if (file_exists(dirname(__FILE__).'/../../../../'.TYPO3_mainDir.'alt_db_navframe.php')) {

	$MCONF['navFrameScript'] = '../../../../'.TYPO3_mainDir.'alt_db_navframe.php';

} elseif (file_exists(dirname(__FILE__).'/../../../alt_db_navframe.php')) {

	$MCONF['navFrameScript'] = '../../../alt_db_navframe.php';

}

$MLANG['default']['tabs_images']['tab'] = '../res/icon_txdlfmodules.png';

$MLANG['default']['ll_ref'] = 'LLL:EXT:dlf/modules/locallang_mod.xml';

?>