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

$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dlf');

return array (
	'tx_dlf_cli' => $extensionPath.'cli/class.tx_dlf_cli.php',
	'tx_dlf_alto' => $extensionPath.'common/class.tx_dlf_alto.php',
	'tx_dlf_document' => $extensionPath.'common/class.tx_dlf_document.php',
	'tx_dlf_format' => $extensionPath.'common/class.tx_dlf_format.php',
	'tx_dlf_fulltext' => $extensionPath.'common/class.tx_dlf_fulltext.php',
	'tx_dlf_helper' => $extensionPath.'common/class.tx_dlf_helper.php',
	'tx_dlf_indexing' => $extensionPath.'common/class.tx_dlf_indexing.php',
	'tx_dlf_list' => $extensionPath.'common/class.tx_dlf_list.php',
	'tx_dlf_mods' => $extensionPath.'common/class.tx_dlf_mods.php',
	'tx_dlf_module' => $extensionPath.'common/class.tx_dlf_module.php',
	'tx_dlf_plugin' => $extensionPath.'common/class.tx_dlf_plugin.php',
	'tx_dlf_solr' => $extensionPath.'common/class.tx_dlf_solr.php',
	'tx_dlf_teihdr' => $extensionPath.'common/class.tx_dlf_teihdr.php',
	'tx_dlf_doctype' => $extensionPath.'hooks/class.tx_dlf_doctype.php',
	'tx_dlf_em' => $extensionPath.'hooks/class.tx_dlf_em.php',
	'tx_dlf_hacks' => $extensionPath.'hooks/class.tx_dlf_hacks.php',
	'tx_dlf_tceforms' => $extensionPath.'hooks/class.tx_dlf_tceforms.php',
	'tx_dlf_tcemain' => $extensionPath.'hooks/class.tx_dlf_tcemain.php',
	'tx_dlf_modIndexing' => $extensionPath.'modules/indexing/index.php',
	'tx_dlf_modNewclient' => $extensionPath.'modules/newclient/index.php',
	'tx_dlf_audioplayer' => $extensionPath.'plugins/audioplayer/class.tx_dlf_audioplayer.php',
	'tx_dlf_collection' => $extensionPath.'plugins/collection/class.tx_dlf_collection.php',
	'tx_dlf_feeds' => $extensionPath.'plugins/feeds/class.tx_dlf_feeds.php',
	'tx_dlf_listview' => $extensionPath.'plugins/listview/class.tx_dlf_listview.php',
	'tx_dlf_metadata' => $extensionPath.'plugins/metadata/class.tx_dlf_metadata.php',
	'tx_dlf_navigation' => $extensionPath.'plugins/navigation/class.tx_dlf_navigation.php',
	'tx_dlf_newspaper' => $extensionPath.'plugins/newspaper/class.tx_dlf_newspaper.php',
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
	'tx_dlf_toolsImagemanipulation' => $extensionPath.'plugins/toolbox/tools/imagemanipulation/class.tx_dlf_toolsImagemanipulation.php',
	'tx_dlf_toolsImagedownload' => $extensionPath.'plugins/toolbox/tools/imagedownload/class.tx_dlf_toolsImagedownload.php',
	'tx_dlf_validator' => $extensionPath.'plugins/validator/class.tx_dlf_validator.php'
);
