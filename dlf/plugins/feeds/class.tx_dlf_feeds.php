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
 * Plugin 'DLF: Feeds' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_feeds extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/feeds/class.tx_dlf_feeds.php';

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
	public function main($content, $conf) {

		$this->init($conf);

		// Don't cache the output.
		$this->setCache(FALSE);

		// Create XML document.
		$rss = new DOMDocument('1.0', 'utf-8');

		// Add mandatory root element.
		$root = $rss->createElement('rss');

		$root->setAttribute('version', '2.0');

		// Add channel element.
		$channel = $rss->createElement('channel');

		$channel->appendChild($rss->createElement('title', htmlspecialchars($this->conf['title'], ENT_NOQUOTES, 'UTF-8')));

		$channel->appendChild($rss->createElement('link', htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->pi_linkTP_keepPIvars_url()), ENT_NOQUOTES, 'UTF-8')));

		if (!empty($this->conf['description'])) {

			$channel->appendChild($rss->createElement('description', htmlspecialchars($this->conf['description'], ENT_QUOTES, 'UTF-8')));

		}

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_libraries.label AS label',
			'tx_dlf_libraries',
			'tx_dlf_libraries.pid='.intval($this->conf['pages']).' AND tx_dlf_libraries.uid='.intval($this->conf['library']).tx_dlf_helper::whereClause('tx_dlf_libraries'),
			'',
			'',
			'1'
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

			$resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

			$channel->appendChild($rss->createElement('copyright', htmlspecialchars($resArray['label'], ENT_NOQUOTES, 'UTF-8')));

		}

		$channel->appendChild($rss->createElement('pubDate', date('r', $GLOBALS['EXEC_TIME'])));

		$channel->appendChild($rss->createElement('generator', htmlspecialchars($this->conf['useragent'], ENT_NOQUOTES, 'UTF-8')));

		// Add item elements.
		if (!$this->conf['excludeOther'] || empty($this->piVars['collection']) || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->conf['collections'], $this->piVars['collection'])) {

			$additionalWhere = '';

			// Check for pre-selected collections.
			if (!empty($this->piVars['collection'])) {

				$additionalWhere = ' AND tx_dlf_collections.uid='.intval($this->piVars['collection']);

			} elseif (!empty($this->conf['collections'])) {

				$additionalWhere = ' AND tx_dlf_collections.uid IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($this->conf['collections']).')';

			}

			$result = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'tx_dlf_documents.uid AS uid,tx_dlf_documents.partof AS partof,tx_dlf_documents.title AS title,tx_dlf_documents.volume AS volume,tx_dlf_documents.author AS author,tx_dlf_documents.record_id AS guid,tx_dlf_documents.tstamp AS tstamp,tx_dlf_documents.crdate AS crdate',
				'tx_dlf_documents',
				'tx_dlf_relations',
				'tx_dlf_collections',
				'AND tx_dlf_documents.pid='.intval($this->conf['pages']).' AND tx_dlf_relations.ident='.$GLOBALS['TYPO3_DB']->fullQuoteStr('docs_colls', 'tx_dlf_relations').' AND tx_dlf_collections.pid='.intval($this->conf['pages']).$additionalWhere.tx_dlf_helper::whereClause('tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_collections'),
				'tx_dlf_documents.uid',
				'tx_dlf_documents.tstamp DESC',
				intval($this->conf['limit'])
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result)) {

				// Add each record as item element.
				while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

					$item = $rss->createElement('item');

					$title = '';

					// Get title of superior document.
					if ((empty($resArray['title']) || !empty($this->conf['prependSuperiorTitle'])) && !empty($resArray['partof'])) {

						$superiorTitle = tx_dlf_document::getTitle($resArray['partof'], TRUE);

						if (!empty($superiorTitle)) {

							$title .= '['.$superiorTitle.']';

						}

					}

					// Get title of document.
					if (!empty($resArray['title'])) {

						$title .= ' '.$resArray['title'];

					}

					// Set default title if empty.
					if (empty($title)) {

						$title = $this->pi_getLL('noTitle');

					}

					// Append volume information.
					if (!empty($resArray['volume'])) {

						$title .= ', '.$this->pi_getLL('volume').' '.$resArray['volume'];

					}

					// Is this document new or updated?
					if ($resArray['crdate'] == $resArray['tstamp']) {

						$title = $this->pi_getLL('new').' '.trim($title);

					} else {

						$title = $this->pi_getLL('update').' '.trim($title);

					}

					$item->appendChild($rss->createElement('title', htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8')));

					// Add link.
					$linkConf = array (
						'parameter' => $this->conf['targetPid'],
						'forceAbsoluteUrl' => 1,
						'additionalParams' => \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($this->prefixId, array ('id' => $resArray['uid']), '', TRUE, FALSE)
					);

					$item->appendChild($rss->createElement('link', htmlspecialchars($this->cObj->typoLink_URL($linkConf), ENT_NOQUOTES, 'UTF-8')));

					// Add author if applicable.
					if (!empty($resArray['author'])) {

						$item->appendChild($rss->createElement('author', htmlspecialchars($resArray['author'], ENT_NOQUOTES, 'UTF-8')));

					}

					// Add online publication date.
					$item->appendChild($rss->createElement('pubDate', date('r', $resArray['crdate'])));

					// Add internal record identifier.
					$item->appendChild($rss->createElement('guid', htmlspecialchars($resArray['guid'], ENT_NOQUOTES, 'UTF-8')));

					$channel->appendChild($item);

				}

			}

		}

		$root->appendChild($channel);

		// Build XML output.
		$rss->appendChild($root);

		$content = $rss->saveXML();

		// Clean output buffer.
		\TYPO3\CMS\Core\Utility\GeneralUtility::cleanOutputBuffers();

		// Send headers.
		header('HTTP/1.1 200 OK');

		header('Cache-Control: no-cache');

		header('Content-Length: '.strlen($content));

		header('Content-Type: application/rss+xml; charset=utf-8');

		header('Date: '.date('r', $GLOBALS['EXEC_TIME']));

		header('Expires: '.date('r', $GLOBALS['EXEC_TIME']));

		echo $content;

		// Flush output buffer and end script processing.
		ob_end_flush();

		exit;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/feeds/class.tx_dlf_feeds.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/feeds/class.tx_dlf_feeds.php']);
}
