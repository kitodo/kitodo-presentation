<?php
namespace Kitodo\Dlf\Plugins;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\Helper;

/**
 * Plugin 'Basket' for the 'dlf' extension
 *
 * @author Christopher Timm <timm@effective-webwork.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Basket extends \Kitodo\Dlf\Common\AbstractPlugin {
    public $scriptRelPath = 'Classes/Plugins/Basket.php';

    /**
     * The main method of the PlugIn
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function main($content, $conf) {
        $this->init($conf);
        // Don't cache the output.
        $this->setCache(FALSE);
        // Load template file.
        $this->getTemplate();
        $subpartArray['entry'] = $this->cObj->getSubpart($this->template, '###ENTRY###');
        $markerArray['###JS###'] = '';
        // get user session
        $sessionId = $GLOBALS['TSFE']->fe_user->id;
        if ($GLOBALS['TSFE']->loginUser) {
            $insertArray['fe_user_id'] = $GLOBALS['TSFE']->fe_user->user['uid'];
            $query = $GLOBALS['TYPO3_DB']->SELECTquery(
                '*',
                'tx_dlf_basket',
                'tx_dlf_basket.fe_user_id='.intval($insertArray['fe_user_id'])
                    .Helper::whereClause('tx_dlf_basket'),
                '',
                '',
                '1'
            );
        } else {
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_dlf_basket', '');
            $GLOBALS['TSFE']->fe_user->sesData_change = TRUE;
            $GLOBALS['TSFE']->fe_user->storeSessionData();
            $query = $GLOBALS['TYPO3_DB']->SELECTquery(
                '*',
                'tx_dlf_basket',
                'tx_dlf_basket.session_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($sessionId, 'tx_dlf_basket')
                    .Helper::whereClause('tx_dlf_basket'),
                '',
                '',
                '1'
            );
        }
        $result = $GLOBALS['TYPO3_DB']->sql_query($query);
        // session already exists
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($result) == 0) {
            // create new basket in db
            $insertArray['session_id'] = $sessionId;
            $insertArray['doc_ids'] = '';
            $insertArray['label'] = '';
            $insertArray['l18n_diffsource'] = '';
            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dlf_basket', $insertArray);
            $result = $GLOBALS['TYPO3_DB']->sql_query($query);
        }
        $basketData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
        $piVars = $this->piVars;
        // action add to basket
        if (!empty($this->piVars['id'])
            && $this->piVars['addToBasket']) {
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
        // action print from basket
        if ($this->piVars['print_action']) {
            // open selected documents
            unset($piVars['print_action']);
            if (isset($this->piVars['selected'])) {
                $basketData = $this->printDocument($piVars, $basketData);
            }
        }
        // action send mail
        if ($this->piVars['mail_action']) {
            if (isset($this->piVars['selected'])) {
                $this->sendMail($this->piVars);
            }
        }
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
            '1=1'
                .Helper::whereClause('tx_dlf_mail'),
            '',
            'tx_dlf_mail.sorting',
            ''
        );
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($resultMail) > 0) {
            $mailForm = '<select name="tx_dlf[mail_action]">';
            $mailForm .= '<option value="">'.$this->pi_getLL('chooseMail', '', TRUE).'</option>';
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultMail)) {
                $mailForm .= '<option value="'.$row['uid'].'">'.$row['name'].' ('.$row['mail'].')</option>';
            }
            $mailForm .= '</select><input type="submit">';
        }
        // mail action form
        $markerArray['###MAILACTION###'] = $mailForm;
        // remove action form
        $markerArray['###REMOVEACTION###'] = '
   <select name="tx_dlf[basket_action]">
    <option value="">'.$this->pi_getLL('chooseAction', '', TRUE).'</option>
    <option value="open">'.$this->pi_getLL('download', '', TRUE).'</option>
    <option value="remove">'.$this->pi_getLL('remove', '', TRUE).'</option>
   </select>
   <input type="submit">
  ';
        // get mail addresses
        $resultPrinter = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'tx_dlf_printer',
            '1=1'
                .Helper::whereClause('tx_dlf_printer'),
            '',
            '',
            ''
        );
        $printForm = '';
        if ($GLOBALS['TYPO3_DB']->sql_num_rows($resultPrinter) > 0) {
            $printForm = '<select name="tx_dlf[print_action]">';
            $printForm .= '<option value="">'.$this->pi_getLL('choosePrinter', '', TRUE).'</option>';
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultPrinter)) {
                $printForm .= '<option value="'.$row['uid'].'">'.$row['label'].'</option>';
            }
            $printForm .= '</select><input type="submit" />';
        }
        // print action form
        $markerArray['###PRINTACTION###'] = $printForm;
        $entries = '';
        if (isset($basketData['doc_ids'])) {
            // get each entry
            foreach ($basketData['doc_ids'] as $value) {
                $entries .= $this->getEntry($value, $subpartArray);
            }
        } else {
            $entries = '';
        }
        // basket go to
        if ($this->conf['targetBasket'] && $this->conf['basketGoToButton'] && $this->piVars['id']) {
            $label = $this->pi_getLL('goBasket', '', TRUE);
            $basketConf = [
                'parameter' => $this->conf['targetBasket'],
                'title' => $label
            ];
            $markerArray['###BASKET###'] = $this->cObj->typoLink($label, $basketConf);
        } else {
            $markerArray['###BASKET###'] = '';
        }
        $content = $this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($this->template, '###ENTRY###', $entries, TRUE), $markerArray);
        return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Return one basket entry
     *
     * @access protected
     *
     * @param array $data: DocumentData
     * @param array $template: Template information
     *
     * @return string One basket entry
     */
    protected function getEntry($data, $template) {
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
        return $this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($template['entry'], '###ENTRY###', '', TRUE), $markerArray);
    }

    /**
     * Adds documents to the basket
     *
     * @access protected
     *
     * @param array $_piVars: piVars
     * @param array $basketData: basket data
     *
     * @return array Basket data and Javascript output
     */
    protected function addToBasket($_piVars, $basketData) {
        $output = '';
        if (!$_piVars['startpage']) {
            $page = 0;
        } else {
            $page = intval($_piVars['startpage']);
        }
        if ($page != NULL || $_piVars['addToBasket'] == 'list') {
            $documentItem = [
                'id' => intval($_piVars['id']),
                'startpage' => intval($_piVars['startpage']),
                'endpage' => !isset($_piVars['endpage']) || $_piVars['endpage'] === "" ? "" : intval($_piVars['endpage']),
                'startX' => !isset($_piVars['startX']) || $_piVars['startX'] === "" ? "" : intval($_piVars['startX']),
                'startY' => !isset($_piVars['startY']) || $_piVars['startY'] === "" ? "" : intval($_piVars['startY']),
                'endX' => !isset($_piVars['endX']) || $_piVars['endX'] === "" ? "" : intval($_piVars['endX']),
                'endY' => !isset($_piVars['endY']) || $_piVars['endY'] === "" ? "" : intval($_piVars['endY']),
                'rotation' => !isset($_piVars['rotation']) || $_piVars['rotation'] === "" ? "" : intval($_piVars['rotation'])
            ];
            // update basket
            if (!empty($basketData['doc_ids'])) {
                $items = json_decode($basketData['doc_ids']);
                $items = get_object_vars($items);
            } else {
                $items = [];
            }
            // get document instance to load further information
            $document = Document::getInstance($documentItem['id'], 0);
            // set endpage for toc and subentry based on logid
            if (($_piVars['addToBasket'] == 'subentry') or ($_piVars['addToBasket'] == 'toc')) {
                $smLinks = $document->smLinks;
                $pageCounter = sizeof($smLinks['l2p'][$_piVars['logId']]);
                $documentItem['endpage'] = ($documentItem['startpage'] + $pageCounter) - 1;
            }
            // add whole document
            if ($_piVars['addToBasket'] == 'list') {
                $documentItem['endpage'] = $document->numPages;
            }
            $arrayKey = $documentItem['id'].'_'.$page;
            if (!empty($documentItem['startX'])) {
                $arrayKey .= '_'.$documentItem['startX'];
            }
            if (!empty($documentItem['endX'])) {
                $arrayKey .= '_'.$documentItem['endX'];
            }
            // do not add more than one identical object
            if (!in_array($arrayKey, $items)) {
                $items[$arrayKey] = $documentItem;
                // replace url param placeholder
                $pdfParams = str_replace("##startpage##", $documentItem['startpage'], $this->conf['pdfparams']);
                $pdfParams = str_replace("##docId##", $document->recordId, $pdfParams);
                $pdfParams = str_replace("##startx##", $documentItem['startX'], $pdfParams);
                $pdfParams = str_replace("##starty##", $documentItem['startY'], $pdfParams);
                $pdfParams = str_replace("##endx##", $documentItem['endX'], $pdfParams);
                $pdfParams = str_replace("##endy##", $documentItem['endY'], $pdfParams);
                $pdfParams = str_replace("##rotation##", $documentItem['rotation'], $pdfParams);
                if ($documentItem['startpage'] != $documentItem['endpage']) {
                    $pdfParams = str_replace("##endpage##", $documentItem['endpage'], $pdfParams);
                } else {
                    // remove parameter endpage
                    $pdfParams = str_replace(",##endpage##", '', $pdfParams);
                }
                $pdfGenerateUrl = $this->conf['pdfgenerate'].$pdfParams;
                if ($this->conf['pregeneration']) {
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
            }
            $update = ['doc_ids' => json_encode($items)];
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_dlf_basket', 'uid='.intval($basketData['uid']), $update);
            $basketData['doc_ids'] = $items;
        }
        return ['basketData' => $basketData, 'jsOutput' => $output];
    }

    /**
     * Removes selected documents from basket
     *
     * @access protected
     *
     * @param array $_piVars: plugin variables
     * @param array $basketData: array with document information
     *
     * @return array basket data
     */
    protected function removeFromBasket($_piVars, $basketData) {
        if (!empty($basketData['doc_ids'])) {
            $items = $basketData['doc_ids'];
            $items = get_object_vars($items);
        }
        foreach ($_piVars['selected'] as $value) {
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
            $update = ['doc_ids' => ''];
        } else {
            $update = ['doc_ids' => json_encode($items)];
        }
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_dlf_basket', 'uid='.intval($basketData['uid']), $update);
        $basketData['doc_ids'] = $items;
        return $basketData;
    }

    /**
     * Open selected documents from basket
     *
     * @access protected
     *
     * @param array $_piVars: plugin variables
     * @param array $basketData: array with document information
     *
     * @return array basket data
     */
    protected function openFromBasket($_piVars, $basketData) {
        $pdfUrl = $this->conf['pdfgenerate'];
        foreach ($this->piVars['selected'] as $docValue) {
            if ($docValue['id']) {
                $docData = $this->getDocumentData($docValue['id'], $docValue);
                $pdfUrl .= $docData['urlParams'].$this->conf['pdfparamseparator'];
            }
        }
        header('Location: '.$pdfUrl);
        ob_end_flush();
        exit;
    }

    /**
     * Returns the downloadurl configured in the basket
     *
     * @access protected
     *
     * @param integer $id: Document id
     *
     * @return mixed download url or false
     */
    protected function getDocumentData($id, $data) {
        // get document instance to load further information
        $document = Document::getInstance($id, 0);
        if ($document) {
            // replace url param placeholder
            $urlParams = str_replace("##page##", intval($data['page']), $this->conf['pdfparams']);
            $urlParams = str_replace("##docId##", $document->recordId, $urlParams);
            $urlParams = str_replace("##startpage##", intval($data['startpage']), $urlParams);
            if ($data['startpage'] != $data['endpage']) {
                $urlParams = str_replace("##endpage##", $data['endpage'] === "" ? "" : intval($data['endpage']), $urlParams);
            } else {
                // remove parameter endpage
                $urlParams = str_replace(",##endpage##", '', $urlParams);
            }
            $urlParams = str_replace("##startx##", $data['startX'] === "" ? "" : intval($data['startX']), $urlParams);
            $urlParams = str_replace("##starty##", $data['startY'] === "" ? "" : intval($data['startY']), $urlParams);
            $urlParams = str_replace("##endx##", $data['endX'] === "" ? "" : intval($data['endX']), $urlParams);
            $urlParams = str_replace("##endy##", $data['endY'] === "" ? "" : intval($data['endY']), $urlParams);
            $urlParams = str_replace("##rotation##", $data['rotation'] === "" ? "" : intval($data['rotation']), $urlParams);
            $downloadUrl = $this->conf['pdfgenerate'].$urlParams;
            $title = $document->getTitle($id, TRUE);
            if (empty($title)) {
                $title = $this->pi_getLL('noTitle', '', TRUE);
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
            $downloadLink = '<a href="'.$downloadUrl.'" target="_blank">'.$title.'</a> ('.$info.')';
            if ($data['startpage'] == $data['endpage']) {
                $pageNums = 1;
            } else {
                $pageNums = $data['endpage'] - $data['startpage'];
            }
            return [
                'downloadUrl' => $downloadUrl,
                'downloadLink' => $downloadLink,
                'pageNums' => $pageNums,
                'urlParams' => $urlParams,
                'record_id' => $document->recordId,
            ];
        }
        return FALSE;
    }

    /**
     * Send mail with pdf download url
     *
     * @access protected
     *
     * @return void
     */
    protected function sendMail() {
        // send mail
        $mailId = $this->piVars['mail_action'];
        // get id from db and send selected doc downloadlink
        $resultMail = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'tx_dlf_mail',
            'tx_dlf_mail.uid='.intval($mailId)
                .Helper::whereClause('tx_dlf_mail'),
            '',
            '',
            '1'
        );
        $mailData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultMail);
        $mailText = $this->pi_getLL('mailBody', '', TRUE)."\n";
        $numberOfPages = 0;
        $pdfUrl = $this->conf['pdfdownload'];
        // prepare links
        foreach ($this->piVars['selected'] as $docValue) {
            if ($docValue['id']) {
                $explodeId = explode("_", $docValue['id']);
                $docData = $this->getDocumentData($explodeId[0], $docValue);
                $pdfUrl .= $docData['urlParams'].$this->conf['pdfparamseparator'];
                $pages = (abs(intval($docValue['startpage']) - intval($docValue['endpage'])));
                if ($pages === 0) {
                    $numberOfPages = $numberOfPages + 1;
                } else {
                    $numberOfPages = $numberOfPages + $pages;
                }
            }
        }
        // Remove leading/tailing pdfparamseperator
        $pdfUrl = trim($pdfUrl, $this->conf['pdfparamseparator']);
        $mailBody = $mailText.$pdfUrl;
        // Get hook objects.
        $hookObjects = Helper::getHookObjects($this->scriptRelPath);
        // Hook for getting a customized mail body.
        foreach ($hookObjects as $hookObj) {
            if (method_exists($hookObj, 'customizeMailBody')) {
                $mailBody = $hookObj->customizeMailBody($mailText, $pdfUrl);
            }
        }
        $from = \TYPO3\CMS\Core\Utility\MailUtility::getSystemFrom();
        // send mail
        $mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
        // Prepare and send the message
        $mail
            // subject
            ->setSubject($this->pi_getLL('mailSubject', '', TRUE))
            // Set the From address with an associative array
            ->setFrom($from)
            // Set the To addresses with an associative array
            ->setTo([$mailData['mail'] => $mailData['name']])
            ->setBody($mailBody, 'text/html')
            ->send();
        // protocol
        $insertArray = [
            'pid' => $this->conf['pages'],
            'file_name' => $pdfUrl,
            'count_pages' => $numberOfPages,
            'crdate' => time(),
        ];
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

    /**
     * Sends document information to an external printer (url)
     *
     * @access protected
     *
     * @return void
     */
    protected function printDocument() {
        $pdfUrl = $this->conf['pdfprint'];
        $numberOfPages = 0;
        foreach ($this->piVars['selected'] as $docId => $docValue) {
            if ($docValue['id']) {
                $docData = $this->getDocumentData($docValue['id'], $docValue);
                $pdfUrl .= $docData['urlParams'].$this->conf['pdfparamseparator'];
                $numberOfPages += $docData['pageNums'];
            }
        }
        // get printer data
        $printerId = $this->piVars['print_action'];
        // get id from db and send selected doc downloadlink
        $resultPrinter = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'tx_dlf_printer',
            'tx_dlf_printer.uid='.intval($printerId)
                .Helper::whereClause('tx_dlf_basket'),
            '',
            '',
            '1'
        );
        $printerData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resultPrinter);
        // printer is selected
        if ($printerData) {
            $pdfUrl = $printerData['print'];
            $numberOfPages = 0;
            foreach ($this->piVars['selected'] as $docId => $docValue) {
                if ($docValue['id']) {
                    $explodeId = explode("_", $docId);
                    $docData = $this->getDocumentData($explodeId[0], $docValue);
                    $pdfUrl .= $docData['urlParams'].$this->conf['pdfparamseparator'];
                    $numberOfPages += $docData['pageNums'];
                }
            }
            $pdfUrl = trim($pdfUrl, $this->conf['pdfparamseparator']);
        }
        // protocol
        $insertArray = [
            'pid' => $this->conf['pages'],
            'file_name' => $pdfUrl,
            'count_pages' => $numberOfPages,
            'crdate' => time(),
        ];
        if ($GLOBALS["TSFE"]->loginUser) {
            // internal user
            $insertArray['user_id'] = $GLOBALS["TSFE"]->fe_user->user['uid'];
            $insertArray['name'] = $GLOBALS["TSFE"]->fe_user->user['username'];
            $insertArray['label'] = 'Print: '.$printerData['label'];
        } else {
            // external user
            $insertArray['user_id'] = 0;
            $insertArray['name'] = 'n/a';
            $insertArray['label'] = 'Print: '.$printerData['label'];
        }
        // add action to protocol
        $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dlf_actionlog', $insertArray);
        header('Location: '.$pdfUrl);
        ob_end_flush();
        exit;
    }
}
