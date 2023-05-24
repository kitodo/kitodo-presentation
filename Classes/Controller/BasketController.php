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

use Kitodo\Dlf\Common\Doc;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Domain\Model\ActionLog;
use Kitodo\Dlf\Domain\Model\Basket;
use Kitodo\Dlf\Domain\Repository\ActionLogRepository;
use Kitodo\Dlf\Domain\Repository\MailRepository;
use Kitodo\Dlf\Domain\Repository\BasketRepository;
use Kitodo\Dlf\Domain\Repository\PrinterRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Context\Context;

/**
 * Controller class for the plugin 'Basket'.
 *
 * @author Christopher Timm <timm@effective-webwork.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class BasketController extends AbstractController
{
    /**
     * @var BasketRepository
     */
    protected $basketRepository;

    /**
     * @param BasketRepository $basketRepository
     */
    public function injectBasketRepository(BasketRepository $basketRepository)
    {
        $this->basketRepository = $basketRepository;
    }

    /**
     * @var MailRepository
     */
    protected $mailRepository;

    /**
     * @param MailRepository $mailRepository
     */
    public function injectMailRepository(MailRepository $mailRepository)
    {
        $this->mailRepository = $mailRepository;
    }

    /**
     * @var PrinterRepository
     */
    protected $printerRepository;

    /**
     * @param PrinterRepository $printerRepository
     */
    public function injectPrinterRepository(PrinterRepository $printerRepository)
    {
        $this->printerRepository = $printerRepository;
    }

    /**
     * @var ActionLogRepository
     */
    protected $actionLogRepository;

    /**
     * @param ActionLogRepository $actionLogRepository
     */
    public function injectActionLogRepository(ActionLogRepository $actionLogRepository)
    {
        $this->actionLogRepository = $actionLogRepository;
    }

    /**
     * Different actions which depends on the chosen action (form)
     *
     * @return void
     */
    public function basketAction()
    {
        $basket = $this->getBasketData();

        // action remove from basket
        if ($this->requestData['basket_action'] === 'remove') {
            // remove entry from list
            if (isset($this->requestData['selected'])) {
                $basket = $this->removeFromBasket($this->requestData, $basket);
            }
        }
        // action remove from basket
        if ($this->requestData['basket_action'] == 'download') {
            // open selected documents
            if (isset($this->requestData['selected'])) {
                $pdfUrl = $this->settings['pdfgenerate'];
                foreach ($this->requestData['selected'] as $docValue) {
                    if ($docValue['id']) {
                        $docData = $this->getDocumentData($docValue['id'], $docValue);
                        $pdfUrl .= $docData['urlParams'] . $this->settings['pdfparamseparator'];
                        $this->redirectToUri($pdfUrl);
                    }
                }
            }
        }
        // action print from basket
        if ($this->requestData['print_action']) {
            // open selected documents
            if (isset($this->requestData['selected'])) {
                $this->printDocument($basket);
            }
        }
        // action send mail
        if ($this->requestData['mail_action']) {
            if (isset($this->requestData['selected'])) {
                $this->sendMail();
            }
        }

        $this->redirect('main');
    }

    /**
     * Add documents to the basket
     *
     * @return void
     */
    public function addAction()
    {
        $basket = $this->getBasketData();

        if (
            !empty($this->requestData['id'])
            && $this->requestData['addToBasket']
        ) {
            $basket = $this->addToBasket($this->requestData, $basket);
        }

        $this->redirect('main');
    }

    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        $basket = $this->getBasketData();

        $countDocs = 0;
        if ($basket->getDocIds()) {
            $countDocs = count(json_decode($basket->getDocIds(), true));
        }
        $this->view->assign('countDocs', $countDocs);

        $allMails = $this->mailRepository->findAllWithPid($this->settings['storagePid']);

        $mailSelect = [];
        if ($allMails->count() > 0) {
            $mailSelect[0] = htmlspecialchars(LocalizationUtility::translate('basket.chooseMail', 'dlf'));
            foreach ($allMails as $mail) {
                $mailSelect[$mail->getUid()] = htmlspecialchars($mail->getName()) . ' (' . htmlspecialchars($mail->getMail()) . ')';
            }
            $this->view->assign('mailSelect', $mailSelect);
        }

        $allPrinter = $this->printerRepository->findAll();

        $printSelect = [];
        if ($allPrinter->count() > 0) {
            $printSelect[0] = htmlspecialchars(LocalizationUtility::translate('basket.choosePrinter', 'dlf'));
            foreach ($allPrinter as $printer) {
                $printSelect[$printer->getUid()] = htmlspecialchars($printer->getLabel());
            }
            $this->view->assign('printSelect', $printSelect);
        }

        $entries = [];
        if ($basket->getDocIds()) {
            // get each entry
            foreach (json_decode($basket->getDocIds()) as $value) {
                $entries[] = $this->getEntry($value);
            }
            $this->view->assign('entries', $entries);
        }
    }

    /**
     * The basket data from user session.
     *
     * @return Basket The found data from user session.
     */
    protected function getBasketData()
    {
        // get user session
        $sessionId = $GLOBALS['TSFE']->fe_user->id;
        $context = GeneralUtility::makeInstance(Context::class);

        // Checking if a user is logged in
        $userIsLoggedIn = $context->getPropertyFromAspect('frontend.user', 'isLoggedIn');

        if ($userIsLoggedIn) {
            $basket = $this->basketRepository->findOneByFeUserId((int) $GLOBALS['TSFE']->fe_user->user['uid']);
        } else {
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_dlf_basket', '');
            $GLOBALS['TSFE']->fe_user->sesData_change = true;
            $GLOBALS['TSFE']->fe_user->storeSessionData();

            $basket = $this->basketRepository->findOneBySessionId($sessionId);
        }

        // session does not exist
        if ($basket === null) {
            // create new basket in db
            $basket = GeneralUtility::makeInstance(Basket::class);
            $basket->setSessionId($sessionId);
            $basket->setFeUserId($userIsLoggedIn ? $GLOBALS['TSFE']->fe_user->user['uid'] : 0);
        }

        return $basket;
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
        $this->loadDocument((int) $id);
        if ($this->document) {
            // replace url param placeholder
            $urlParams = str_replace("##page##", (int) $data['page'], $this->settings['pdfparams']);
            $urlParams = str_replace("##docId##", $this->document->getRecordId(), $urlParams);
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

            $title = $this->document->getTitle();
            if (empty($title)) {
                $title = LocalizationUtility::translate('basket.noTitle', 'dlf') ? : '';
            }

            // Set page and cutout information
            $info = '';
            if ($data['startX'] != '' && $data['endX'] != '') {
                // cutout
                $info .= htmlspecialchars(LocalizationUtility::translate('basket.cutout', 'dlf')) . ' ';
            }
            if ($data['startpage'] == $data['endpage']) {
                // One page
                $info .= htmlspecialchars(LocalizationUtility::translate('page', 'dlf')) . ' ' . $data['startpage'];
            } else {
                $info .= htmlspecialchars(LocalizationUtility::translate('page', 'dlf')) . ' ' . $data['startpage'] . '-' . $data['endpage'];
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
                'record_id' => $this->document->getRecordId(),
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
     * @param Basket $basket: basket object
     *
     * @return array Basket data and JavaScript output
     */
    protected function addToBasket($_piVars, $basket)
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
            if (!empty(json_decode($basket->getDocIds()))) {
                $items = json_decode($basket->getDocIds());
                $items = get_object_vars($items);
            } else {
                $items = [];
            }
            // get document instance to load further information
            $this->loadDocument((int) $documentItem['id']);
            if ($this->isDocMissing()) {
                // Quit without doing anything if required variables are not set.
                return;
            }
            // set endpage for toc and subentry based on logid
            if (($_piVars['addToBasket'] == 'subentry') or ($_piVars['addToBasket'] == 'toc')) {
                $smLinks = $this->document->getDoc()->smLinks;
                $pageCounter = sizeof($smLinks['l2p'][$_piVars['logId']]);
                $documentItem['endpage'] = ($documentItem['startpage'] + $pageCounter) - 1;
            }
            // add whole document
            if ($_piVars['addToBasket'] == 'list') {
                $documentItem['endpage'] = $this->document->getDoc()->numPages;
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
                $pdfParams = str_replace("##docId##", $this->document->getRecordId(), $pdfParams);
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

            $basket->setDocIds(json_encode($items));
            if ($basket->getUid() === null) {
                $this->basketRepository->add($basket);
            } else {
                $this->basketRepository->update($basket);
            }
        }
        $this->view->assign('pregenerateJs', $output);

        return $basket;
    }

    /**
     * Removes selected documents from basket
     *
     * @access protected
     *
     * @param array $_piVars: plugin variables
     * @param Basket $basket: basket object
     *
     * @return Basket basket
     */
    protected function removeFromBasket($_piVars, $basket)
    {
        if (!empty($basket->getDocIds())) {
            $items = json_decode($basket->getDocIds());
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
            $update = '';
        } else {
            $update = json_encode($items);
        }

        $basket->setDocIds($update);
        $this->basketRepository->update($basket);

        return $basket;
    }

    /**
     * Send mail with pdf download url
     *
     * @access protected
     *
     * @return void
     */
    protected function sendMail()
    {
        // send mail
        $mailId = $this->requestData['mail_action'];

        $mailObject = $this->mailRepository->findByUid(intval($mailId))->getFirst();

        $mailText = htmlspecialchars(LocalizationUtility::translate('basket.mailBody', 'dlf')) . "\n";
        $numberOfPages = 0;
        $pdfUrl = $this->settings['pdfdownload'];
        // prepare links
        foreach ($this->requestData['selected'] as $docValue) {
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
        $hookObjects = Helper::getHookObjects('Classes/Controller/BasketController.php');
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
            ->setSubject(LocalizationUtility::translate('basket.mailSubject', 'dlf'))
            // Set the From address with an associative array
            ->setFrom($from)
            // Set the To addresses with an associative array
            ->setTo([$mailObject->getMail() => $mailObject->getName()])
            ->setBody($mailBody, 'text/html')
            ->send();

        // create entry for action log
        $newActionLog = GeneralUtility::makeInstance(ActionLog::class);
        $newActionLog->setFileName($pdfUrl);
        $newActionLog->setCountPages($numberOfPages);
        $newActionLog->setLabel('Mail: ' . $mailObject->getMail());

        if ($GLOBALS["TSFE"]->loginUser) {
            // internal user
            $newActionLog->setUserId($GLOBALS["TSFE"]->fe_user->user['uid']);
            $newActionLog->setName($GLOBALS["TSFE"]->fe_user->user['username']);
        } else {
            // external user
            $newActionLog->setUserId(0);
            $newActionLog->setName('n/a');
        }

        $this->actionLogRepository->add($newActionLog);

        $this->redirect('main');
    }

    /**
     * Sends document information to an external printer (url)
     *
     * @access protected
     * @param Basket basket object
     *
     * @return void
     */
    protected function printDocument($basket)
    {
        $pdfUrl = $this->settings['pdfprint'];
        $numberOfPages = 0;
        foreach ($this->requestData['selected'] as $docId => $docValue) {
            if ($docValue['id']) {
                $docData = $this->getDocumentData($docValue['id'], $docValue);
                $pdfUrl .= $docData['urlParams'] . $this->settings['pdfparamseparator'];
                $numberOfPages += $docData['pageNums'];
            }
        }
        // get printer data
        $printerId = $this->requestData['print_action'];

        // get id from db and send selected doc download link
        $printer = $this->printerRepository->findOneByUid($printerId);

        // printer is selected
        if ($printer) {
            $pdfUrl = $printer->getPrint();
            $numberOfPages = 0;
            foreach ($this->requestData['selected'] as $docId => $docValue) {
                if ($docValue['id']) {
                    $explodeId = explode("_", $docId);
                    $docData = $this->getDocumentData($explodeId[0], $docValue);
                    $pdfUrl .= $docData['urlParams'] . $this->settings['pdfparamseparator'];
                    $numberOfPages += $docData['pageNums'];
                }
            }
            $pdfUrl = trim($pdfUrl, $this->settings['pdfparamseparator']);
        }

        $actionLog = GeneralUtility::makeInstance(ActionLog::class);
        // protocol
        $actionLog->setPid($this->settings['storagePid']);
        $actionLog->setFileName($pdfUrl);
        $actionLog->setCountPages($numberOfPages);

        if ($GLOBALS["TSFE"]->loginUser) {
            // internal user
            $actionLog->setUserId($GLOBALS["TSFE"]->fe_user->user['uid']);
            $actionLog->setName($GLOBALS["TSFE"]->fe_user->user['username']);
            $actionLog->setLabel('Print: ' . $printer->getLabel());
        } else {
            // external user
            $actionLog->setUserId(0);
            $actionLog->setName('n/a');
            $actionLog->setLabel('Print: ' . $printer->getLabel());
        }
        // add action to protocol
        $this->actionLogRepository->add($actionLog);

        $this->redirectToUri($pdfUrl);
    }
}
