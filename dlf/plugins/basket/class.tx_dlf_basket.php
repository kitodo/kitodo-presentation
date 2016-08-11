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
 * Plugin 'DLF: List View' for the 'dlf' extension.
 *
 * @author	Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author	Henrik Lochmann <dev@mentalmotive.com>
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_basket extends tx_dlf_plugin {

	public $scriptRelPath = 'plugins/basket/class.tx_dlf_basket.php';

	/**
	 * The main method of the PlugIn
	 *
	 * @access	public
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 *
	 * @return	string		The content that is displayed on the website
	 */
	public function main($content, $conf) {

		$this->init($conf);

		// Don't cache the output.
		$this->setCache(FALSE);

		// Load the list.
		// $this->list = t3lib_div::makeInstance('tx_dlf_basket');

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATE###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/basket/template.tmpl'), '###TEMPLATE###');

		}

		$subpartArray['entry'] = $this->cObj->getSubpart($this->template, '###ENTRY###');

		$markerArray['###JS###'] = '';

		// get user session
		$sessionId = $GLOBALS["TSFE"]->fe_user->id;

		if ($GLOBALS["TSFE"]->loginUser) {
			$insertArray['fe_user_id'] = $GLOBALS["TSFE"]->fe_user->user['uid'];

			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'tx_dlf_basket',
				'tx_dlf_basket.fe_user_id="'.$insertArray['fe_user_id'].'"',
				'',
				'',
				'1'
			);
		} else {
			$GLOBALS["TSFE"]->fe_user->setKey("ses","tx_dlf_basket", "");
			$GLOBALS["TSFE"]->fe_user->sesData_change = true;
			$GLOBALS["TSFE"]->fe_user->storeSessionData();

			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'tx_dlf_basket',
				'tx_dlf_basket.session_id="'.$sessionId.'"',
				'',
				'',
				'1'
			);

		}

		// session already exists
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) == 1) {
			// basket found get data
			$basketData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);

		} else {
			// create new basket in db
			$insertArray['session_id'] = $sessionId;

			$query  = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dlf_basket', $insertArray);
			$res    = $GLOBALS['TYPO3_DB']->sql_query($query);

			$basketData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

		}

		$piVars = $this->piVars;

		// action add to basket
		if (!empty($this->piVars['id']) && $this->piVars['addToBasket']) {
			$returnData = $this->addToBasket($this->piVars, $basketData);
			$basketData = $returnData['basketData'];
			$markerArray['###JS###'] = $returnData['jsOutput'];
		} else {
			$basketData['doc_ids'] = json_decode($basketData['doc_ids']);
		}

		// action remove from basket
		if ($this->piVars['basket_action'] == 'remove') {
			// remove entry from list
			unset($piVars['basket_action']);
			if (isset($this->piVars['selected'])) {
				$basketData = $this->removeFromBasket($piVars, $basketData);
			}
		}

		// action remove from basket
		if ($this->piVars['basket_action'] == 'open') {
			// open selected documents
			unset($piVars['basket_action']);
			if (isset($this->piVars['selected'])) {
				$basketData = $this->openFromBasket($piVars, $basketData);
			}
		}

		// action send mail
		if ($this->piVars['mail_action']) {
			if (isset($this->piVars['selected'])) {
				$this->sendMail($this->piVars);
			}
		}

		if ($this->piVars['print_action']) {
			if (isset($this->piVars['selected'])) {
				$this->printDocument();
			}
		}

		$additionalParams = array ('remove' => true);
		$conf = array (
			'useCacheHash' => 1,
			'parameter' => $this->conf['targetBasket'],
			'additionalParams' => t3lib_div::implodeArrayForUrl($this->prefixId, $additionalParams, '', TRUE, FALSE)
		);
		// $link = $this->cObj->typoLink_URL('Go to basket', $conf);

		// set marker
		$markerArray['###ACTION###'] = $this->pi_getPageLink($GLOBALS['TSFE']->id);

		$markerArray['###LISTTITLE###'] = $this->pi_getLL('basket', '', TRUE);

		if ($basketData['doc_ids']) {

			if (is_object($basketData['doc_ids'])) {
				$basketData['doc_ids'] = get_object_vars($basketData['doc_ids']);
			}

			$markerArray['###COUNT###'] = sprintf($this->pi_getLL('count'), count($basketData['doc_ids']));
		} else {
			$markerArray['###COUNT###'] = sprintf($this->pi_getLL('count'), 0);
		}

		// get mail addresses
		$resultMail = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_dlf_mail',
			'',
			'',
			'',
			''
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($resultMail) > 0) {
			$mails = array();
			$mailForm = '<select name="tx_dlf[mail_action]">';
			$mailForm .= '<option value=""></option>';
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultMail)){
				$mails[] = $row;
				$mailForm .= '<option value="'.$row['uid'].'">'.$row['name'].' ('.$row['mail'].')</option>';
  			}
  			$mailForm .= '</select><input type="submit">';
		}

		// mail action form
		$markerArray['###MAILACTION###'] = $mailForm;

		// remove action form
		$markerArray['###REMOVEACTION###'] = '
			<select name="tx_dlf[basket_action]">
				<option value=""></option>
				<option value="open">'.$this->pi_getLL('download', '', TRUE).'</option>
				<option value="remove">'.$this->pi_getLL('remove', '', TRUE).'</option>
			</select>
			<input type="submit">
		';

		// get mail addresses
		$resultPrinter = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_dlf_printer',
			'',
			'',
			'',
			''
		);

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($resultPrinter) > 0) {
			$printers = array();
			$printForm = '<select name="tx_dlf[print_action]">';
			$printForm .= '<option value=""></option>';
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultPrinter)){
				$printers[] = $row;
				$printForm .= '<option value="'.$row['uid'].'">'.$row['name'].' ('.$row['address'].')</option>';
  			}
  			$printForm .= '</select><input type="submit">';
		}


		// print action form
		$markerArray['###PRINTACTION###'] = $printForm;
		

		$entries = '';

		if (isset($basketData['doc_ids'])) {
			// get each entry
			foreach ($basketData['doc_ids'] as $key => $value) {
				$entries .= $this->getEntry($value, $subpartArray);
			}
		} else {
			$entries = '';
		}
		

		$content = $this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($this->template, '###ENTRY###', $entries, TRUE), $markerArray);

		return $this->pi_wrapInBaseClass($content);

	}

	/**
	 * Return one basket entry
	 * @param  array $data     DocumentData
	 * @param  array $template Template information
	 * @return Template
	 */
	public function getEntry($data, $template)
	{
		if (is_object($data)) {
			$data = get_object_vars($data);
		}

		$id = $data['id'];
		$startpage = $data['startpage'];
		$endpage = $data['endpage'];
		$startX = $data['startX'];
		$startY = $data['startY'];
		$endX = $data['endX'];
		$endY = $data['endY'];
		$rotation = $data['rotation'];

		$docData = $this->getDocumentData($id, $data);
		$markerArray['###BASKETDATA###'] = $docData['downloadLink'];

		$arrayKey = $id.'_'.$startpage;

		if (isset($startX)) {
			$arrayKey .= '_'.$startX;
		}

		if (isset($endX)) {
			$arrayKey .= '_'.$endX;
		}
		

		$controlMark = '<input value="'.$id.'" name="tx_dlf[selected]['.$arrayKey.'][id]" type="checkbox">';
		$controlMark .= '<input value="'.$startpage.'" name="tx_dlf[selected]['.$arrayKey.'][startpage]" type="hidden">';
		$controlMark .= '<input value="'.$endpage.'" name="tx_dlf[selected]['.$arrayKey.'][endpage]" type="hidden">';

		// add hidden fields for detail information
		if ($startX) {
			$controlMark .= '<input type="hidden" name="tx_dlf[selected]['.$arrayKey.'][startX]" value="'.$startX.'">';
			$controlMark .= '<input type="hidden" name="tx_dlf[selected]['.$arrayKey.'][startY]"  value="'.$startY.'">';
			$controlMark .= '<input type="hidden" name="tx_dlf[selected]['.$arrayKey.'][endX]"  value="'.$endX.'">';
			$controlMark .= '<input type="hidden" name="tx_dlf[selected]['.$arrayKey.'][endY]"  value="'.$endY.'">';
			$controlMark .= '<input type="hidden" name="tx_dlf[selected]['.$arrayKey.'][rotation]"  value="'.$rotation.'">';
		}

		// return one entry
		$markerArray['###CONTROLS###'] = $controlMark;

		$markerArray['###NUMBER###'] = $docData['record_id'];

		return $this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($template['entry'], '###ENTRY###', $subpart, TRUE), $markerArray);
	}

	/**
	 * Adds documents to the basket
	 * @param array $_piVars    piVars
	 * @param array $basketData basket data
	 */
	public function addToBasket($_piVars, $basketData)
	{
		if (!$_piVars['startpage']) {
			$page = 0;
		} else {
			$page = $_piVars['startpage'];
		}

		if ($page != null || $_piVars['addToBasket'] == 'list') {

			$documentItem = array(
				'id' => $_piVars['id'],
				'startpage' => $_piVars['startpage'],
				'endpage' => $_piVars['endpage'],
				'startX' => $_piVars['startX'],
				'startY' => $_piVars['startY'],
				'endX' => $_piVars['endX'],
				'endY' => $_piVars['endY'],
				'rotation' => $_piVars['rotation']
			);

			// update basket
			if (!empty($basketData['doc_ids'])) {
				$items = json_decode($basketData['doc_ids']);
				$items = get_object_vars($items);
			} else {
				$items = array();
			}

			// get document instance to load further information
			$document = tx_dlf_document::getInstance($_piVars['id'],0);

			// add whole document
			if ($_piVars['addToBasket'] == 'list') {
				$documentItem['endpage'] = $document->numPages;
			}

			if ($_piVars['addToBasket'] == 'toc') {
				$firstLevel = $document->tableOfContents[0]['children'][0]['children'];

				if ($firstLevel) {
					for ($i=0; $i < sizeof($firstLevel); $i++) { 
						// search for points
						if ($firstLevel[$i]['points'] == $_piVars['startpage']) {
							$documentItem['endpage'] = $firstLevel[$i+1]['points'] - 1;
							break;
						}
					}
				}
			}

			$arrayKey = $_piVars['id'].'_'.$page;

			if (!empty($_piVars['startX'])) {
				$arrayKey .= '_'.$_piVars['startX'];
			}

			if (!empty($_piVars['endX'])) {
				$arrayKey .= '_'.$_piVars['endX'];
			}
		
			// do not add more than one identical object
			if (!in_array($arrayKey, $items)) {
				$items[$arrayKey] = $documentItem;

				// replace url param placeholder
				
				$pdfParams = str_replace("##startpage##", $documentItem['startpage'], $this->conf['pdfparams']);
				$pdfParams = str_replace("##endpage##", $documentItem['endpage'], $pdfParams);
				$pdfParams = str_replace("##docId##", $document->recordId, $pdfParams);
				$pdfParams = str_replace("##startx##", $documentItem['startX'], $pdfParams);
				$pdfParams = str_replace("##starty##", $documentItem['startY'], $pdfParams);
				$pdfParams = str_replace("##endx##", $documentItem['endX'], $pdfParams);
				$pdfParams = str_replace("##endy##", $documentItem['endY'], $pdfParams);
				$pdfParams = str_replace("##rotation##", $documentItem['rotation'], $pdfParams);

				$pdfGenerateUrl = $this->conf['pdfgenerate'].$pdfParams;

				// send ajax request to webapp
				$output .= '
				<script>
					$(document).ready(function(){
						$.ajax({
						  url: "'.$pdfGenerateUrl.'",
						}).done(function() {
						});
					});
				</script>';

				
			}

			$update = array('doc_ids' => json_encode($items));
			$result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_dlf_basket', 'uid='.intval($basketData['uid']), $update);

			$basketData['doc_ids'] = $items;

			// return array('basketData' => $basketData, 'jsOutput' => $output);

		}
			return array('basketData' => $basketData, 'jsOutput' => $output);
		
	}

	/**
	 * Removes selected documents from basket
	 * @param  array $_piVars    plugin variables
	 * @param  array $basketData array with document information
	 * @return array             basket data
	 */
	public function removeFromBasket($_piVars, $basketData)
	{
		if (!empty($basketData['doc_ids'])) {
			$items = $basketData['doc_ids'];
			$items = get_object_vars($items);
		}

		foreach ($_piVars['selected'] as $key => $value) {
			var_dump($value);
			if (isset($value['id'])) {

				$arrayKey = $value['id'].'_'.$value['startpage'];

				if (isset($value['startX'])) {
					$arrayKey .= '_'.$value['startX'];
				}

				if (isset($value['endX'])) {
					$arrayKey .= '_'.$value['endX'];
				}

				if (isset($items[$arrayKey])) {
					unset($items[$arrayKey]);
				}
			}
		}


		if (empty($items)) {
			$update = array('doc_ids' => '');
		} else {
			$update = array('doc_ids' => json_encode($items));
		}

		$result = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_dlf_basket', 'uid='.intval($basketData['uid']), $update);

		$basketData['doc_ids'] = $items;

		return $basketData;

	}


		/**
		 * Open selected documents from basket
		 * @param  array $_piVars    plugin variables
		 * @param  array $basketData array with document information
		 * @return array             basket data
		 */
		public function openFromBasket($_piVars, $basketData)
		{
			$pdfUrl = $this->conf['pdfgenerate'];
			foreach ($this->piVars['selected'] as $docId => $docValue) {
				if ($docValue['id']) {
					$filename .= $docValue['id'].'_';
					$docData = $this->getDocumentData($docId, $docValue);
					$pdfUrl .= $docData['urlParams'].$this->conf['pdfparamseparator'];
					$numberOfPages += $docData['pageNums'];
				}

				header("Location:".$pdfUrl);
				exit;
			}
			

		}

	/**
	 * Returns the downloadurl configured in the basket
	 * @param  integer $id Document id
	 * @return mixed     download url or false
	 */
	public function getDocumentData($id, $data)
	{
		// get document instance to load further information
		$document = tx_dlf_document::getInstance($id,0);

		if ($document) {
			// replace url param placeholder
			$urlParams = str_replace("##page##", $data['page'], $this->conf['pdfparams']);
			$urlParams = str_replace("##docId##", $document->recordId, $urlParams);
			$urlParams = str_replace("##startpage##", $data['startpage'], $urlParams);

			if ($data['startpage'] != $data['endpage']) {
				$urlParams = str_replace("##endpage##", $data['endpage'], $urlParams);
			} else {
				// remove parameter endpage
				$urlParams = str_replace(",##endpage##", '', $urlParams);
			}

			$urlParams = str_replace("##startx##", $data['startX'], $urlParams);
			$urlParams = str_replace("##starty##", $data['startY'], $urlParams);
			$urlParams = str_replace("##endx##", $data['endX'], $urlParams);
			$urlParams = str_replace("##endy##", $data['endY'], $urlParams);
			$urlParams = str_replace("##rotation##", $data['rotation'], $urlParams);

			// TODO check if ? is already set
			$downloadUrl = $this->conf['pdfdownload'].$urlParams;

			$title = $document->getTitle($id);
			if (!empty($title)) {
				$title = $document->getTitle($id);
			} else {
				$title = '[No title]'; // TODO translation
			}

			// Set page and cutout information 
			$info = '';
			if ($data['startX'] != '' && $data['endX'] != '') {
				// cutout
				$info .= $this->pi_getLL('cutout', '', TRUE).' ';

			}

			if ($data['startpage'] == $data['endpage']) {
				// One page
				$info .= $this->pi_getLL('page', '', TRUE).' '.$data['startpage'];
			} else {
				$info .= $this->pi_getLL('page', '', TRUE).' '.$data['startpage'].'-'.$data['endpage'];
			}

			$downloadLink = '<a href="'.$downloadUrl.'" target="_new">'.$title.'</a> ('.$info.')';

			if ($data['page'] == 0) {
				$pageNums = $document->numPages;
			} else {
				$pageNums = 1;
			}

			return array(
				'downloadUrl' => $downloadUrl,
				'downloadLink' => $downloadLink,
				'pageNums'	=> $pageNums,
				'urlParams' => $urlParams,
				'record_id' => $document->recordId,
			);
		}

		return false;
		
	}

	/**
	 * Send mail
	 */
	public function sendMail()
	{
		// send mail
		$mailId = $this->piVars['mail_action'];

		// get id from db and send selected doc downloadlink
		$resultMail = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_dlf_mail',
			'tx_dlf_mail.uid="'.$mailId.'"',
			'',
			'',
			'1'
		);

		$mailData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultMail);

		$body = $this->pi_getLL('mailBody', '', TRUE)."\n";
		$numberOfPages = 0;
		$filesProtocol = '';

		// prepare links
		foreach ($this->piVars['selected'] as $docId => $docValue) {
			if ($docValue['id']) {
				$docData = $this->getDocumentData($docId, $docValue);
				$body .= $docData['downloadLink']."\n";
				$filesProtocol .= $docData['downloadUrl']."\n";
				$numberOfPages += $docData['pageNums'];
			}
		}

		// send mail
		$mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
		// Prepare and send the message
		$mail
			// subject
			->setSubject($this->pi_getLL('mailSubject', '', TRUE))

			// Set the From address with an associative array
			// ->setFrom(array('john@doe.com' => 'John Doe'))

			// Set the To addresses with an associative array
			->setTo(array($mailData['mail'] => $mailData['name']))

			->setBody($body, 'text/html')

			->send()
		;

		// protocol
		$insertArray = array(
			'pid' => $this->conf['pages'],
			'file_name' => $filesProtocol,
			'count_pages' => $numberOfPages,
			'crdate' => time(),
		);

		if ($GLOBALS["TSFE"]->loginUser) {
			// internal user
			$insertArray['user_id'] = $GLOBALS["TSFE"]->fe_user->user['uid'];
			$insertArray['name'] = $GLOBALS["TSFE"]->fe_user->user['username'];
			$insertArray['label'] = 'Mail: '.$mailData['mail'];
		} else {
			// external user
			$insertArray['user_id'] = 0;
			$insertArray['name'] = 'n/a';
			$insertArray['label'] = 'Mail: '.$mailData['mail'];
		}

		// add action to protocol
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dlf_actionlog', $insertArray);
		
	}


	public function printDocument()
	{
		// get printer data
		$printerId = $this->piVars['print_action'];

		// get id from db and send selected doc downloadlink
		$resultPrinter = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_dlf_printer',
			'tx_dlf_printer.uid="'.$printerId.'"',
			'',
			'',
			'1'
		);

		$printerData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultPrinter);

		// download pdf
		$pdfUrl = $this->conf['pdfdownload'];
		$filename = 'Document_';
		$numberOfPages = 0;
		foreach ($this->piVars['selected'] as $docId => $docValue) {
			if ($docValue['id']) {
				$filename .= $docValue['id'].'_';
				$docData = $this->getDocumentData($docId, $docValue);
				$pdfUrl .= $docData['urlParams'].$this->conf['pdfparamseparator'];
				$numberOfPages += $docData['pageNums'];
			}
		}
		$pdfUrl = trim($pdfUrl, '*');
		$time = time();

		// testing purpose only
		// $pdfUrl = 'http://pdf.sub.uni-hamburg.de/gcs/cs?action=pdf&pagesize=original&images=HANSb322286/00000001.tif$';

		if (!file_exists(PATH_site.'/fileadmin/goobitemp/')) {
		    mkdir(PATH_site.'/fileadmin/goobitemp/');
		}

		$filePath = PATH_site.'/fileadmin/goobitemp/'.$filename.$time.".pdf";

		$tempFile = fopen($filePath, "w+");
		fwrite($tempFile, file_get_contents($pdfUrl));
		fclose($tempFile);

		// print pdf
		shell_exec('lpr -P '.$printerData['name'].' '.$filePath);

		// protocol
		$insertArray = array(
			'pid' => $this->conf['pages'],
			'file_name' => $pdfUrl,
			'count_pages' => $numberOfPages,
			'crdate' => time(),
		);

		if ($GLOBALS["TSFE"]->loginUser) {
			// internal user
			$insertArray['user_id'] = $GLOBALS["TSFE"]->fe_user->user['uid'];
			$insertArray['name'] = $GLOBALS["TSFE"]->fe_user->user['username'];
			$insertArray['label'] = 'Print: '.$printerData['name'];
		} else {
			// external user
			$insertArray['user_id'] = 0;
			$insertArray['name'] = 'n/a';
			$insertArray['label'] = 'Print: '.$mailData['mail'];
		}

		// add action to protocol
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dlf_actionlog', $insertArray);
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/basket/class.tx_dlf_basket.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/basket/class.tx_dlf_basket.php']);
}

?>
