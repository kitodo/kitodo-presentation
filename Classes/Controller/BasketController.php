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

namespace Kitodo\Dlf\Controller;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use \TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use \Kitodo\Dlf\Domain\Model\SearchForm;

class BasketController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    public $prefixId = 'tx_dlf';
    public $extKey = 'dlf';

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Core\Log\LogManager
     */
    protected $logger;

    /**
     * SearchController constructor.
     * @param $configurationManager
     */
    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
        $this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * Different actions which depends on the choosen action (form)
     */
    public function basketAction() {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'],$requestData['__trustedProperties']);

        $basketData = $this->getBasketData();

        // action remove from basket
        if ($requestData['basket_action'] === 'remove') {
            // remove entry from list
            if (isset($requestData['selected'])) {
                $basketData = $this->removeFromBasket($requestData, $basketData);
            }
        }
        // action remove from basket
        if ($requestData['basket_action'] == 'download') {
            // open selected documents
            if (isset($requestData['selected'])) {
                $pdfUrl = $this->settings['pdfgenerate'];
                foreach ($requestData['selected'] as $docValue) {
                    if ($docValue['id']) {
                        $docData = $this->getDocumentData($docValue['id'], $docValue);
                        $pdfUrl .= $docData['urlParams'] . $this->settings['pdfparamseparator'];
                        $this->redirectToUri($pdfUrl);
                    }
                }
            }
        }
        // action print from basket
        if ($requestData['print_action']) {
            // open selected documents
            if (isset($requestData['selected'])) {
                $this->printDocument($requestData, $basketData);
            }
        }
        // action send mail
        if ($requestData['mail_action']) {
            if (isset($requestData['selected'])) {
                $this->sendMail($requestData);
            }
        }

        $this->redirect('main');
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * Add documents to the basket
     */
    public function addAction() {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'],$requestData['__trustedProperties']);

        $basketData = $this->getBasketData();

        if (
            !empty($requestData['id'])
            && $requestData['addToBasket']
        ) {
            $returnData = $this->addToBasket($requestData, $basketData);
            $basketData = $returnData['basketData'];
            $this->view->assign('pregenerateJs', $returnData['jsOutput']);
        }

        $this->redirect('main');
    }

    public function mainAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'],$requestData['__trustedProperties']);

        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($this->extKey);

        $basketData = $this->getBasketData();

        if ($basketData['doc_ids']) {
            if (is_object($basketData['doc_ids'])) {
                $basketData['doc_ids'] = get_object_vars($basketData['doc_ids']);
            }
            $count = sprintf($this->pi_getLL('count'), count($basketData['doc_ids']));
        } else {
            $count = sprintf($this->pi_getLL('count'), 0);
        }
        $this->view->assign('count', $count);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_mail');

        // get mail addresses
        $resultMail = $queryBuilder
            ->select('*')
            ->from('tx_dlf_mail')
            ->where(
                '1=1',
                Helper::whereExpression('tx_dlf_mail')
            )
            ->orderBy('tx_dlf_mail.sorting')
            ->execute();

        $mailSelect = [];
        if ($resultMail->rowCount() > 0) {
            $mailSelect[0] = htmlspecialchars($this->pi_getLL('chooseMail', ''));
            while ($row = $resultMail->fetch()) {
                $mailSelect[$row['uid']] = htmlspecialchars($row['name']) . ' (' . htmlspecialchars($row['mail']) . ')';
            }
            $this->view->assign('mailSelect', $mailSelect);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_printer');

        // get mail addresses
        $resultPrinter = $queryBuilder
            ->select('*')
            ->from('tx_dlf_printer')
            ->where(
                '1=1',
                Helper::whereExpression('tx_dlf_printer')
            )
            ->execute();

        $printSelect = [];
        if ($resultPrinter->rowCount() > 0) {
            $printSelect[0] = htmlspecialchars($this->pi_getLL('choosePrinter', ''));
            while ($row = $resultPrinter->fetch()) {
                $printSelect[$row['uid']] = htmlspecialchars($row['label']);
            }
            $this->view->assign('printSelect', $printSelect);
        }

        $entries = [];
        if (isset($basketData['doc_ids'])) {
            // get each entry
            foreach ($basketData['doc_ids'] as $value) {
                $entries[] = $this->getEntry($value);
            }
            $this->view->assign('entries', $entries);
        }
        // go to basket link
        if ($this->settings['targetBasket'] && $this->settings['basketGoToButton']) {
            $uri = $this->uriBuilder->reset()
                ->setTargetPageUid($this->settings['targetBasket'])
                ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? 1 : 0)
                ->build();
            $this->view->assign('goToBasketUri', $uri);
        }
    }

    protected function getBasketData() {
        // get user session
        $sessionId = $GLOBALS['TSFE']->fe_user->id;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_basket');

        if ($GLOBALS['TSFE']->loginUser) {
            $result = $queryBuilder
                ->select('*')
                ->from('tx_dlf_basket')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_basket.fe_user_id', (int) $GLOBALS['TSFE']->fe_user->user['uid']),
                    Helper::whereExpression('tx_dlf_basket')
                )
                ->setMaxResults(1)
                ->execute();
        } else {
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_dlf_basket', '');
            $GLOBALS['TSFE']->fe_user->sesData_change = true;
            $GLOBALS['TSFE']->fe_user->storeSessionData();
            $result = $queryBuilder
                ->select('*')
                ->from('tx_dlf_basket')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_basket.session_id', $queryBuilder->createNamedParameter($sessionId)),
                    Helper::whereExpression('tx_dlf_basket')
                )
                ->setMaxResults(1)
                ->execute();
        }
        // session already exists
        if ($result->rowCount() === 0) {
            // create new basket in db
            $insertArray['fe_user_id'] = $GLOBALS['TSFE']->loginUser ? $GLOBALS['TSFE']->fe_user->user['uid'] : 0;
            $insertArray['session_id'] = $sessionId;
            $insertArray['doc_ids'] = '';
            $insertArray['label'] = '';
            $insertArray['l18n_diffsource'] = '';
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tx_dlf_basket')
                ->insert(
                    'tx_dlf_basket',
                    $insertArray
                );
        }
        $basketData = $result->fetchAll()[0];
        $basketData['doc_ids'] = json_decode($basketData['doc_ids']);
        return $basketData;
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
    protected function getEntry($data)
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

        $entryArray['BASKETDATA'] = $docData;

        $entryKey = $id . '_' . $startpage;
        if (!empty($startX)) {
            $entryKey .= '_' . $startX;
        }
        if (!empty($endX)) {
            $entryKey .= '_' . $endX;
        }

        $entryArray['id'] = $id;
        $entryArray['CONTROLS'] = [
            'startpage' => $startpage,
            'endpage' => $endpage,
            'startX' => $startX,
            'startY' => $startY,
            'endX' => $endX,
            'endY' => $endY,
            'rotation' => $rotation,
        ];

        $entryArray['NUMBER'] = $docData['record_id'];
        $entryArray['key'] = $entryKey;

        // return one entry
        return $entryArray;
    }

    /**
     * Returns the downloadurl configured in the basket
     *
     * @access protected
     *
     * @param int $id: Document id
     *
     * @return mixed download url or false
     */
    protected function getDocumentData($id, $data)
    {
        // get document instance to load further information
        $document = Document::getInstance($id, 0);
        if ($document) {
            // replace url param placeholder
            $urlParams = str_replace("##page##", (int) $data['page'], $this->settings['pdfparams']);
            $urlParams = str_replace("##docId##", $document->recordId, $urlParams);
            $urlParams = str_replace("##startpage##", (int) $data['startpage'], $urlParams);
            if ($data['startpage'] != $data['endpage']) {
                $urlParams = str_replace("##endpage##", $data['endpage'] === "" ? "" : (int) $data['endpage'], $urlParams);
            } else {
                // remove parameter endpage
                $urlParams = str_replace(",##endpage##", '', $urlParams);
            }
            $urlParams = str_replace("##startx##", $data['startX'] === "" ? "" : (int) $data['startX'], $urlParams);
            $urlParams = str_replace("##starty##", $data['startY'] === "" ? "" : (int) $data['startY'], $urlParams);
            $urlParams = str_replace("##endx##", $data['endX'] === "" ? "" : (int) $data['endX'], $urlParams);
            $urlParams = str_replace("##endy##", $data['endY'] === "" ? "" : (int) $data['endY'], $urlParams);
            $urlParams = str_replace("##rotation##", $data['rotation'] === "" ? "" : (int) $data['rotation'], $urlParams);

            $downloadUrl = $this->settings['pdfgenerate'] . $urlParams;

            $title = $document->getTitle($id, true);
            if (empty($title)) {
                $title = $this->pi_getLL('noTitle', '');
            }

            // Set page and cutout information
            $info = '';
            if ($data['startX'] != '' && $data['endX'] != '') {
                // cutout
                $info .= htmlspecialchars($this->pi_getLL('cutout', '')) . ' ';
            }
            if ($data['startpage'] == $data['endpage']) {
                // One page
                $info .= htmlspecialchars($this->pi_getLL('page', '')) . ' ' . $data['startpage'];
            } else {
                $info .= htmlspecialchars($this->pi_getLL('page', '')) . ' ' . $data['startpage'] . '-' . $data['endpage'];
            }
            $downloadLink = '<a href="' . $downloadUrl . '" target="_blank">' . htmlspecialchars($title) . '</a> (' . $info . ')';
            if ($data['startpage'] == $data['endpage']) {
                $pageNums = 1;
            } else {
                $pageNums = $data['endpage'] - $data['startpage'];
            }
            return [
                'downloadUrl' => $downloadUrl,
                'title' => $title,
                'info' => $info,
                'downloadLink' => $downloadLink,
                'pageNums' => $pageNums,
                'urlParams' => $urlParams,
                'record_id' => $document->recordId,
            ];
        }
        return false;
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
    protected function addToBasket($_piVars, $basketData)
    {
        $output = '';
        if (!$_piVars['startpage']) {
            $page = 0;
        } else {
            $page = (int) $_piVars['startpage'];
        }
        if ($page != null || $_piVars['addToBasket'] == 'list') {
            $documentItem = [
                'id' => (int) $_piVars['id'],
                'startpage' => (int) $_piVars['startpage'],
                'endpage' => !isset($_piVars['endpage']) || $_piVars['endpage'] === "" ? "" : (int) $_piVars['endpage'],
                'startX' => !isset($_piVars['startX']) || $_piVars['startX'] === "" ? "" : (int) $_piVars['startX'],
                'startY' => !isset($_piVars['startY']) || $_piVars['startY'] === "" ? "" : (int) $_piVars['startY'],
                'endX' => !isset($_piVars['endX']) || $_piVars['endX'] === "" ? "" : (int) $_piVars['endX'],
                'endY' => !isset($_piVars['endY']) || $_piVars['endY'] === "" ? "" : (int) $_piVars['endY'],
                'rotation' => !isset($_piVars['rotation']) || $_piVars['rotation'] === "" ? "" : (int) $_piVars['rotation']
            ];
            // update basket
            if (!empty($basketData['doc_ids'])) {
                $items = $basketData['doc_ids'];
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
            $arrayKey = $documentItem['id'] . '_' . $page;
            if (!empty($documentItem['startX'])) {
                $arrayKey .= '_' . $documentItem['startX'];
            }
            if (!empty($documentItem['endX'])) {
                $arrayKey .= '_' . $documentItem['endX'];
            }
            // do not add more than one identical object
            if (!in_array($arrayKey, $items)) {
                $items[$arrayKey] = $documentItem;
                // replace url param placeholder
                $pdfParams = str_replace("##startpage##", $documentItem['startpage'], $this->settings['pdfparams']);
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
                $pdfGenerateUrl = $this->settings['pdfgenerate'] . $pdfParams;
                if ($this->settings['pregeneration']) {
                    // send ajax request to webapp
                    $output .= '
     <script>
      $(document).ready(function(){
       $.ajax({
         url: "' . $pdfGenerateUrl . '",
       }).done(function() {
       });
      });
     </script>';
                }
            }
            $update = ['doc_ids' => json_encode($items)];
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tx_dlf_basket')
                ->update(
                    'tx_dlf_basket',
                    $update,
                    ['uid' => (int) $basketData['uid']]
                );
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
    protected function removeFromBasket($_piVars, $basketData)
    {
        if (!empty($basketData['doc_ids'])) {
            $items = $basketData['doc_ids'];
            $items = get_object_vars($items);
        }
        foreach ($_piVars['selected'] as $value) {
            if (isset($value['id'])) {
                $arrayKey = $value['id'] . '_' . $value['startpage'];
                if (!empty($value['startX'])) {
                    $arrayKey .= '_' . $value['startX'];
                }
                if (!empty($value['endX'])) {
                    $arrayKey .= '_' . $value['endX'];
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

        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dlf_basket')
            ->update(
                'tx_dlf_basket',
                 $update,
                ['uid' => (int) $basketData['uid']]
            );
        $basketData['doc_ids'] = $items;
        return $basketData;
    }

    /**
     * Send mail with pdf download url
     *
     * @access protected
     *
     * @return void
     */
    protected function sendMail($requestData)
    {
        // send mail
        $mailId = $requestData['mail_action'];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_mail');

        // get id from db and send selected doc download link
        $resultMail = $queryBuilder
            ->select('*')
            ->from('tx_dlf_mail')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_mail.uid', intval($mailId)),
                Helper::whereExpression('tx_dlf_mail')
            )
            ->setMaxResults(1)
            ->execute();

        $allResults = $resultMail->fetchAll();
        $mailData = $allResults[0];
        $mailText = htmlspecialchars($this->pi_getLL('mailBody', '')) . "\n";
        $numberOfPages = 0;
        $pdfUrl = $this->settings['pdfdownload'];
        // prepare links
        foreach ($requestData['selected'] as $docValue) {
            if ($docValue['id']) {
                $explodeId = explode("_", $docValue['id']);
                $docData = $this->getDocumentData($explodeId[0], $docValue);
                $pdfUrl .= $docData['urlParams'] . $this->settings['pdfparamseparator'];
                $pages = (abs(intval($docValue['startpage']) - intval($docValue['endpage'])));
                if ($pages === 0) {
                    $numberOfPages = $numberOfPages + 1;
                } else {
                    $numberOfPages = $numberOfPages + $pages;
                }
            }
        }
        // Remove leading/tailing pdfparamseperator
        $pdfUrl = trim($pdfUrl, $this->settings['pdfparamseparator']);
        $mailBody = $mailText . $pdfUrl;
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
        $mail = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
        // Prepare and send the message
        $mail
            // subject
            ->setSubject($this->pi_getLL('mailSubject', ''))
            // Set the From address with an associative array
            ->setFrom($from)
            // Set the To addresses with an associative array
            ->setTo([$mailData['mail'] => $mailData['name']])
            ->setBody($mailBody, 'text/html')
            ->send();
        // protocol
        $insertArray = [
            'pid' => $this->settings['pages'],
            'file_name' => $pdfUrl,
            'count_pages' => $numberOfPages,
            'crdate' => time(),
        ];
        if ($GLOBALS["TSFE"]->loginUser) {
            // internal user
            $insertArray['user_id'] = $GLOBALS["TSFE"]->fe_user->user['uid'];
            $insertArray['name'] = $GLOBALS["TSFE"]->fe_user->user['username'];
            $insertArray['label'] = 'Mail: ' . $mailData['mail'];
        } else {
            // external user
            $insertArray['user_id'] = 0;
            $insertArray['name'] = 'n/a';
            $insertArray['label'] = 'Mail: ' . $mailData['mail'];
        }
        // add action to protocol
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dlf_actionlog')
            ->insert(
                'tx_dlf_actionlog',
                $insertArray
            );
        $this->redirect('main');
    }

    /**
     * Sends document information to an external printer (url)
     *
     * @access protected
     *
     * @return void
     */
    protected function printDocument($requestData, $basketData)
    {
        $pdfUrl = $this->settings['pdfprint'];
        $numberOfPages = 0;
        foreach ($requestData['selected'] as $docId => $docValue) {
            if ($docValue['id']) {
                $docData = $this->getDocumentData($docValue['id'], $docValue);
                $pdfUrl .= $docData['urlParams'] . $this->settings['pdfparamseparator'];
                $numberOfPages += $docData['pageNums'];
            }
        }
        // get printer data
        $printerId = $requestData['print_action'];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_printer');

        // get id from db and send selected doc download link
        $resultPrinter = $queryBuilder
            ->select('*')
            ->from('tx_dlf_printer')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_printer.uid', intval($printerId)),
                Helper::whereExpression('tx_dlf_printer')
            )
            ->setMaxResults(1)
            ->execute();

        $allResults = $resultPrinter->fetchAll();
        $printerData = $allResults[0];
        // printer is selected
        if ($printerData) {
            $pdfUrl = $printerData['print'];
            $numberOfPages = 0;
            foreach ($requestData['selected'] as $docId => $docValue) {
                if ($docValue['id']) {
                    $explodeId = explode("_", $docId);
                    $docData = $this->getDocumentData($explodeId[0], $docValue);
                    $pdfUrl .= $docData['urlParams'] . $this->settings['pdfparamseparator'];
                    $numberOfPages += $docData['pageNums'];
                }
            }
            $pdfUrl = trim($pdfUrl, $this->settings['pdfparamseparator']);
        }
        // protocol
        $insertArray = [
            'pid' => $this->settings['pages'],
            'file_name' => $pdfUrl,
            'count_pages' => $numberOfPages,
            'crdate' => time(),
        ];
        if ($GLOBALS["TSFE"]->loginUser) {
            // internal user
            $insertArray['user_id'] = $GLOBALS["TSFE"]->fe_user->user['uid'];
            $insertArray['name'] = $GLOBALS["TSFE"]->fe_user->user['username'];
            $insertArray['label'] = 'Print: ' . $printerData['label'];
        } else {
            // external user
            $insertArray['user_id'] = 0;
            $insertArray['name'] = 'n/a';
            $insertArray['label'] = 'Print: ' . $printerData['label'];
        }
        // add action to protocol
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dlf_actionlog')
            ->insert(
                'tx_dlf_actionlog',
                $insertArray
            );

        $this->redirectToUri($pdfUrl);
    }

    protected function pi_getLL($label)
    {
        return $GLOBALS['TSFE']->sL('LLL:EXT:dlf/Resources/Private/Language/Basket.xml:' . $label);
    }


}