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

$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dlf');

return array (
	'tx_dlf_cli' => $extensionPath.'cli/class.tx_dlf_cli.php',
	'tx_dlf_document' => $extensionPath.'common/class.tx_dlf_document.php',
	'tx_dlf_format' => $extensionPath.'common/class.tx_dlf_format.php',
	'tx_dlf_helper' => $extensionPath.'common/class.tx_dlf_helper.php',
	'tx_dlf_indexing' => $extensionPath.'common/class.tx_dlf_indexing.php',
	'tx_dlf_list' => $extensionPath.'common/class.tx_dlf_list.php',
	'tx_dlf_mods' => $extensionPath.'common/class.tx_dlf_mods.php',
	'tx_dlf_module' => $extensionPath.'common/class.tx_dlf_module.php',
	'tx_dlf_plugin' => $extensionPath.'common/class.tx_dlf_plugin.php',
	'tx_dlf_solr' => $extensionPath.'common/class.tx_dlf_solr.php',
	'tx_dlf_teihdr' => $extensionPath.'common/class.tx_dlf_teihdr.php',
	'tx_dlf_em' => $extensionPath.'hooks/class.tx_dlf_em.php',
	'tx_dlf_hacks' => $extensionPath.'hooks/class.tx_dlf_hacks.php',
	'tx_dlf_tceforms' => $extensionPath.'hooks/class.tx_dlf_tceforms.php',
	'tx_dlf_tcemain' => $extensionPath.'hooks/class.tx_dlf_tcemain.php',
	'tx_dlf_modIndexing' => $extensionPath.'modules/indexing/index.php',
	'tx_dlf_modNewclient' => $extensionPath.'modules/newclient/index.php',
	'tx_dlf_collection' => $extensionPath.'plugins/collection/class.tx_dlf_collection.php',
	'tx_dlf_feeds' => $extensionPath.'plugins/feeds/class.tx_dlf_feeds.php',
	'tx_dlf_listview' => $extensionPath.'plugins/listview/class.tx_dlf_listview.php',
	'tx_dlf_metadata' => $extensionPath.'plugins/metadata/class.tx_dlf_metadata.php',
	'tx_dlf_navigation' => $extensionPath.'plugins/navigation/class.tx_dlf_navigation.php',
	'tx_dlf_oai' => $extensionPath.'plugins/oai/class.tx_dlf_oai.php',
	'tx_dlf_pagegrid' => $extensionPath.'plugins/pagegrid/class.tx_dlf_pagegrid.php',
	'tx_dlf_pageview' => $extensionPath.'plugins/pageview/class.tx_dlf_pageview.php',
	'tx_dlf_search' => $extensionPath.'plugins/search/class.tx_dlf_search.php',
	'tx_dlf_search_suggest' => $extensionPath.'plugins/search/class.tx_dlf_search_suggest.php',
	'tx_dlf_statistics' => $extensionPath.'plugins/statistics/class.tx_dlf_statistics.php',
	'tx_dlf_toc' => $extensionPath.'plugins/toc/class.tx_dlf_toc.php',
	'tx_dlf_toolbox' => $extensionPath.'plugins/toolbox/class.tx_dlf_toolbox.php',
	'tx_dlf_toolsPdf' => $extensionPath.'plugins/toolbox/tools/pdf/class.tx_dlf_toolsPdf.php',
	'tx_dlf_toolsFulltext' => $extensionPath.'plugins/toolbox/tools/fulltext/class.tx_dlf_toolsFulltext.php',
	'tx_dlf_validator' => $extensionPath.'plugins/validator/class.tx_dlf_validator.php',
	'tx_dlf_doctype' => $extensionPath.'plugins/doctype/class.tx_dlf_doctype.php'
);
