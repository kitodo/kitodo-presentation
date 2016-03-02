<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
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
 * Document Type Check for usage as Typoscript Condition.
 * @see dlf/ext_localconf.php->user_dlf_docTypeCheck()
 *
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_doctype {

	/**
	 * This holds the current document
	 *
	 * @var	tx_dlf_document
	 * @access protected
	 */
	protected $doc;

	/**
	 * This holds the extension key
	 *
	 * @var	string
	 * @access protected
	 */
	protected $extKey = 'dlf';

	/**
	 * This holds the current DLF plugin parameters
	 * @see __contruct()
	 *
	 * @var	array
	 * @access protected
	 */
	protected $piVars = array ();

	/**
	 * This holds the DLF parameter prefix
	 *
	 * @var	string
	 * @access protected
	 */
	protected $prefixId = 'tx_dlf';

	/**
	 * Check the current document's type.
	 *
	 * @access	public
	 *
	 * @return	string		The type of the current document
	 */
	public function getDocType() {

		// Load current document.
		$this->loadDocument();

		if ($this->doc === NULL) {

			// Quit without doing anything if document not available.
			return '';

		}

		$toc = $this->doc->tableOfContents;

		/*
		 * Get the document type
		 *
		 * 1. newspaper
		 *    case 1) - type=newspaper
		 *    		  - children array([0], [1], [2], ...) -> type = year --> Newspaper Anchor File
		 *    case 2) - type=newspaper
		 *    		  - children array([0]) --> type = year
		 * 			  - children array([0], [1], [2], ...) --> type = month --> Year Anchor File
		 *    case 3) - type=newspaper
		 *    		  - children array([0]) --> type = year
		 * 			  - children array([0]) --> type = month
		 * 			  - children array([0], [1], [2], ...) --> type = day --> Issue
		 */
		switch ($toc[0]['type']) {

			case 'newspaper':

				$nodes_year = $this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div[@TYPE="newspaper"]/mets:div[@TYPE="year"]');

				if (count($nodes_year) > 1) {

					// Multiple years means this is a newspaper's anchor file.
					return 'newspaper';

				} else {

					$nodes_month = $this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div[@TYPE="newspaper"]/mets:div[@TYPE="year"]/mets:div[@TYPE="month"]');

					$nodes_day = $this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div[@TYPE="newspaper"]/mets:div[@TYPE="year"]/mets:div[@TYPE="month"]/mets:div[@TYPE="day"]');

					$nodes_issue = $this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div[@TYPE="newspaper"]/mets:div[@TYPE="year"]//mets:div[@TYPE="issue"]');

					$nodes_issue_current = $this->doc->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]/mets:div[@TYPE="newspaper"]/mets:div[@TYPE="year"]//mets:div[@TYPE="issue"]/@DMDID');

					if (count($nodes_year) == 1 && count($nodes_issue) == 0) {

						// It's possible to have only one year in the newspaper's anchor file.
						return 'newspaper';

					} elseif (count($nodes_year) == 1 && count($nodes_month) > 1) {

						// One year, multiple months means this is a year's anchor file.
						return 'year';

					} elseif (count($nodes_year) == 1 && count($nodes_month) == 1 && count($nodes_day) > 1) {

						// One year, one month, one or more days means this is a year's anchor file.
						return 'year';

					} elseif (count($nodes_year) == 1 && count($nodes_month) == 1 && count($nodes_day) == 1 && count($nodes_issue_current) == 0) {

						// One year, one month, a single day, one or more issues (but not the current one) means this is a year's anchor file.
						return 'year';

					} else {

						// In all other cases we assume it's newspaper's issue.
						return 'issue';

					}

				}

				break;

			default:

				return $toc[0]['type'];

		}

	}

	/**
	 * Loads the current document into $this->doc
	 *
	 * @access	protected
	 *
	 * @return	void
	 */
	protected function loadDocument() {

		// Check for required variable.
		if (!empty($this->piVars['id'])) {

			// Get instance of tx_dlf_document.
			$this->doc =& tx_dlf_document::getInstance($this->piVars['id']);

			if (!$this->doc->ready) {

				// Destroy the incomplete object.
				$this->doc = NULL;

				if (TYPO3_DLOG) {

					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_doctype->loadDocument()] Failed to load document with UID "'.$this->piVars['id'].'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

				}

			}

		} elseif (!empty($this->piVars['recordId'])) {

			// Get UID of document with given record identifier.
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'tx_dlf_documents.uid',
				'tx_dlf_documents',
				'tx_dlf_documents.record_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['recordId'], 'tx_dlf_documents').tx_dlf_helper::whereClause('tx_dlf_documents'),
				'',
				'',
				'1'
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) == 1) {

				list ($this->piVars['id']) = $GLOBALS['TYPO3_DB']->sql_fetch_row($result);

				// Set superglobal $_GET array.
				$_GET[$this->prefixId]['id'] = $this->piVars['id'];

				// Unset variable to avoid infinite looping.
				unset ($this->piVars['recordId'], $_GET[$this->prefixId]['recordId']);

				// Try to load document.
				$this->loadDocument();

			} else {

				if (TYPO3_DLOG) {

					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_doctype->loadDocument()] Failed to load document with record ID "'.$this->piVars['recordId'].'"', $this->extKey, SYSLOG_SEVERITY_WARNING);

				}

			}

		}

	}

	/**
	 * Initializes the hook by setting initial variables.
	 *
	 * @access public
	 *
	 * @return	void
	 */
	public function __construct() {

		// Load current plugin parameters.
		$this->piVars = \TYPO3\CMS\Core\Utility\GeneralUtility::_GPmerged($this->prefixId);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/hooks/class.tx_dlf_doctype.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/hooks/class.tx_dlf_doctype.php']);
}
