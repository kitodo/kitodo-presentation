<?php

$extensionPath = t3lib_extMgm::extPath('dlf');

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
	'tx_dlf_em' => $extensionPath.'hooks/class.tx_dlf_em.php',
	'tx_dlf_hacks' => $extensionPath.'hooks/class.tx_dlf_hacks.php',
	'tx_dlf_tceforms' => $extensionPath.'hooks/class.tx_dlf_tceforms.php',
	'tx_dlf_tcemain' => $extensionPath.'hooks/class.tx_dlf_tcemain.php',
	'tx_dlf_modindexing' => $extensionPath.'modules/indexing/index.php',
	'tx_dlf_collection' => $extensionPath.'plugins/collection/class.tx_dlf_collection.php',
	'tx_dlf_metadata' => $extensionPath.'plugins/metadata/class.tx_dlf_metadata.php',
	'tx_dlf_navigation' => $extensionPath.'plugins/navigation/class.tx_dlf_navigation.php',
	'tx_dlf_oai' => $extensionPath.'plugins/oai/class.tx_dlf_oai.php',
	'tx_dlf_pageview' => $extensionPath.'plugins/pageview/class.tx_dlf_pageview.php',
	'tx_dlf_search' => $extensionPath.'plugins/search/class.tx_dlf_search.php',
	'tx_dlf_toc' => $extensionPath.'plugins/toc/class.tx_dlf_toc.php'
);

?>