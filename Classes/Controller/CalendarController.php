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

class CalendarController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
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

    // TODO: Needs to be placed in an abstract class
    /**
     * Loads the current document into $this->doc
     *
     * @access protected
     *
     * @return void
     */
    protected function loadDocument($requestData)
    {
        // Check for required variable.
        if (
            !empty($requestData['id'])
            && !empty($this->settings['pages'])
        ) {
            // Should we exclude documents from other pages than $this->settings['pages']?
            $pid = (!empty($this->settings['excludeOther']) ? intval($this->settings['pages']) : 0);
            // Get instance of \Kitodo\Dlf\Common\Document.
            $this->doc = Document::getInstance($requestData['id'], $pid);
            if (!$this->doc->ready) {
                // Destroy the incomplete object.
                $this->doc = null;
                $this->logger->error('Failed to load document with UID ' . $requestData['id']);
            } else {
                // Set configuration PID.
                $this->doc->cPid = $this->settings['pages'];
            }
        } elseif (!empty($requestData['recordId'])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            // Get UID of document with given record identifier.
            $result = $queryBuilder
                ->select('tx_dlf_documents.uid AS uid')
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.record_id', $queryBuilder->expr()->literal($requestData['recordId'])),
                    Helper::whereExpression('tx_dlf_documents')
                )
                ->setMaxResults(1)
                ->execute();

            if ($resArray = $result->fetch()) {
                $requestData['id'] = $resArray['uid'];
                // Set superglobal $_GET array and unset variables to avoid infinite looping.
                $_GET[$this->prefixId]['id'] = $requestData['id'];
                unset($requestData['recordId'], $_GET[$this->prefixId]['recordId']);
                // Try to load document.
                $this->loadDocument();
            } else {
                $this->logger->error('Failed to load document with record ID "' . $requestData['recordId'] . '"');
            }
        } else {
            $this->logger->error('Invalid UID ' . $requestData['id'] . ' or PID ' . $this->settings['pages'] . ' for document loading');
        }
    }


    public function mainAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'], $requestData['__trustedProperties']);

        // Set initial document (anchor or year file) if configured.
        if (empty($requestData['id']) && !empty($this->settings['initialDocument'])) {
            $requestData['id'] = $this->settings['initialDocument'];
        }

        // Load current document.
        $this->loadDocument($requestData);
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return '';
        }

        $metadata = $this->doc->getTitledata();
        if (!empty($metadata['type'][0])) {
            $type = $metadata['type'][0];
        } else {
            return '';
        }

        switch ($type) {
            case 'newspaper':
            case 'ephemera':
                $this->forward('years', NULL, NULL, $requestData);
            case 'year':
                $this->forward('calendar', NULL, NULL, $requestData);
            case 'issue':
            default:
                break;
        }

        // Nothing to do here.
        return '';
    }

    /**
     * The Calendar Method
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function calendarAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'], $requestData['__trustedProperties']);

        // Load current document.
        $this->loadDocument($requestData);
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return '';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        // Get all children of year anchor.
        $result = $queryBuilder
            ->select(
                'tx_dlf_documents.uid AS uid',
                'tx_dlf_documents.title AS title',
                'tx_dlf_documents.year AS year',
                'tx_dlf_documents.mets_label AS label',
                'tx_dlf_documents.mets_orderlabel AS orderlabel'
            )
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.structure', Helper::getUidFromIndexName('issue', 'tx_dlf_structures', $this->doc->cPid)),
                $queryBuilder->expr()->eq('tx_dlf_documents.partof', intval($this->doc->uid)),
                Helper::whereExpression('tx_dlf_documents')
            )
            ->orderBy('tx_dlf_documents.mets_orderlabel')
            ->execute();

        $issues = [];

        // Process results.
        while ($resArray = $result->fetch()) {
            // Set title for display in calendar view.
            if (!empty($resArray['title'])) {
                $title = $resArray['title'];
            } else {
                $title = !empty($resArray['label']) ? $resArray['label'] : $resArray['orderlabel'];
                if (strtotime($title) !== false) {
                    $title = strftime('%x', strtotime($title));
                }
            }
            $issues[] = [
                'uid' => $resArray['uid'],
                'title' => $title,
                'year' => $resArray['year']
            ];
        }
        //  We need an array of issues with year => month => day number as key.
        $calendarIssuesByYear = [];
        foreach ($issues as $issue) {
            $dateTimestamp = strtotime($issue['year']);
            if ($dateTimestamp !== false) {
                $_year = date('Y', $dateTimestamp);
                $_month = date('n', $dateTimestamp);
                $_day = date('j', $dateTimestamp);
                $calendarIssuesByYear[$_year][$_month][$_day][] = $issue;
            } else {
                $this->logger->warning('Document with UID ' . $issue['uid'] . 'has no valid date of publication');
            }
        }
        // Sort by years.
        ksort($calendarIssuesByYear);
        // Build calendar for year (default) or season.
        $iteration = 1;
        foreach ($calendarIssuesByYear as $year => $calendarIssuesByMonth) {
            // Sort by months.
            ksort($calendarIssuesByMonth);
            // Default: First month is January, last month is December.
            $firstMonth = 1;
            $lastMonth = 12;
            // Show calendar from first issue up to end of season if applicable.
            if (
                empty($this->settings['showEmptyMonths'])
                && count($calendarIssuesByYear) > 1
            ) {
                if ($iteration == 1) {
                    $firstMonth = (int) key($calendarIssuesByMonth);
                } elseif ($iteration == count($calendarIssuesByYear)) {
                    end($calendarIssuesByMonth);
                    $lastMonth = (int) key($calendarIssuesByMonth);
                }
            }
            $this->getCalendarYear($calendarIssuesByMonth, $year, $firstMonth, $lastMonth);
            $iteration++;
        }
        // Prepare list as alternative view.
        $issueData = [];
        foreach ($this->allIssues as $dayTimestamp => $issues) {
            $issueData[$dayTimestamp]['dateString'] = strftime('%A, %x', $dayTimestamp);
            $issueData[$dayTimestamp]['items'] = [];
            foreach ($issues as $issue) {
                $issueData[$dayTimestamp]['items'][] = $issue;
            }
        }
        $this->view->assign('issueData', $issueData);

        // Link to current year.
        $uri = $this->uriBuilder->reset()
            ->setTargetPageUid($GLOBALS['TSFE']->id)
            ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? 1 : 0)
            ->setArguments([$this->prefixId . '[id]' => urlencode($this->doc->uid)])
            ->build();

        $linkTitleData = $this->doc->getTitledata();
        $linkTitle = !empty($linkTitleData['mets_orderlabel'][0]) ? $linkTitleData['mets_orderlabel'][0] : $linkTitleData['mets_label'][0];
        $yearLink = $uri;

        $this->view->assign('yearLink', $yearLink);
        $this->view->assign('yearLinkText', $linkTitle);

        // Link to years overview.
        $uri = $this->uriBuilder->reset()
            ->setTargetPageUid($GLOBALS['TSFE']->id)
            ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? 1 : 0)
            ->setArguments([$this->prefixId . '[id]' => urlencode($this->doc->parentId)])
            ->build();
        $allYearsLink = $uri;

        $this->view->assign('allYearsLink', $allYearsLink);
        $this->view->assign('allYearDocTitle', $this->doc->getTitle($this->doc->parentId));
        $this->view->assign('calendarViewActive', count($this->allIssues) > 5 ? 'active' : '');
        $this->view->assign('listViewActive', count($this->allIssues) < 6 ? 'active' : '');

    }

    /**
     * The Years Method
     *
     * @access public
     *
     * @param integer $id
     *
     * @return string The content that is displayed on the website
     */
    public function yearsAction($id = 0)
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'], $requestData['__trustedProperties']);

        if (empty($requestData) && !empty($id)) {
            $requestData['id'] = $id;
        }

        // Load current document.
        $this->loadDocument($requestData);
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return '';
        }

        // Get the title of the anchor file
        $titleAnchor = $this->doc->getTitle($this->doc->uid);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_documents');

        // Get all children of anchor. This should be the year anchor documents
        $result = $queryBuilder
            ->select(
                'tx_dlf_documents.uid AS uid',
                'tx_dlf_documents.title AS title',
                'tx_dlf_documents.mets_label AS label',
                'tx_dlf_documents.mets_orderlabel AS orderlabel'
            )
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.structure', Helper::getUidFromIndexName('year', 'tx_dlf_structures', $this->doc->cPid)),
                $queryBuilder->expr()->eq('tx_dlf_documents.partof', intval($this->doc->uid)),
                Helper::whereExpression('tx_dlf_documents')
            )
            ->orderBy('tx_dlf_documents.mets_orderlabel')
            ->execute();

        $years = [];
        // Process results.
        while ($resArray = $result->fetch()) {
            $years[] = [
                'title' => !empty($resArray['label']) ? $resArray['label'] : (!empty($resArray['orderlabel']) ? $resArray['orderlabel'] : $resArray['title']),
                'uid' => $resArray['uid']
            ];
        }
        $yearArray = [];
        if (count($years) > 0) {
            foreach ($years as $year) {
                $uri = $this->uriBuilder->reset()
                    ->setTargetPageUid($GLOBALS['TSFE']->id)
                    ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? 1 : 0)
                    ->setArguments([$this->prefixId . '[id]' => urlencode($year['uid'])])
                    ->build();

                $yearArray[] = [
                    'yearNameLinkTitle' => $titleAnchor . ': ' . $year['title'],
                    'yearNameLink' => $uri,
                    'yearName' => $year['title']
                ];
            }
            $this->view->assign('yearName', $yearArray);
        }
        // Link to years overview (should be itself here)
        $uri = $this->uriBuilder->reset()
            ->setTargetPageUid($GLOBALS['TSFE']->id)
            ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? 1 : 0)
            ->setArguments(['tx_dlf[id]' => $this->doc->uid])
            ->build();
        $allYearsLink = $uri;

        $this->view->assign('allYearsLink', $allYearsLink);
        $this->view->assign('allYearDocTitle', $this->doc->getTitle($this->doc->uid));
    }

    /**
     * Build calendar for a certain year
     *
     * @access protected
     *
     * @param array $calendarIssuesByMonth All issues sorted by month => day
     * @param int $year Gregorian year
     * @param int $firstMonth 1 for January, 2 for February, ... 12 for December
     * @param int $lastMonth 1 for January, 2 for February, ... 12 for December
     *
     * @return string Content for template subpart
     */
    protected function getCalendarYear($calendarIssuesByMonth, $year, $firstMonth = 1, $lastMonth = 12)
    {
        $calendarData = [];
        for ($i = $firstMonth; $i <= $lastMonth; $i++) {
            $calendarData[$i] = [
                'DAYMON_NAME' => strftime('%a', strtotime('last Monday')),
                'DAYTUE_NAME' => strftime('%a', strtotime('last Tuesday')),
                'DAYWED_NAME' => strftime('%a', strtotime('last Wednesday')),
                'DAYTHU_NAME' => strftime('%a', strtotime('last Thursday')),
                'DAYFRI_NAME' => strftime('%a', strtotime('last Friday')),
                'DAYSAT_NAME' => strftime('%a', strtotime('last Saturday')),
                'DAYSUN_NAME' => strftime('%a', strtotime('last Sunday')),
                'MONTHNAME'  => strftime('%B', strtotime($year . '-' . $i . '-1')) . ' ' . $year,
                'CALYEAR' => ($i == $firstMonth) ? $year : ''
            ];

            $firstOfMonth = strtotime($year . '-' . $i . '-1');
            $lastOfMonth = strtotime('last day of', ($firstOfMonth));
            $firstOfMonthStart = strtotime('last Monday', $firstOfMonth);
            // There are never more than 6 weeks in a month.
            for ($j = 0; $j <= 5; $j++) {
                $firstDayOfWeek = strtotime('+ ' . $j . ' Week', $firstOfMonthStart);

                $calendarData[$i]['week'][$j] = [
                    'DAYMON' => ['dayValue' => '&nbsp;'],
                    'DAYTUE' => ['dayValue' => '&nbsp;'],
                    'DAYWED' => ['dayValue' => '&nbsp;'],
                    'DAYTHU' => ['dayValue' => '&nbsp;'],
                    'DAYFRI' => ['dayValue' => '&nbsp;'],
                    'DAYSAT' => ['dayValue' => '&nbsp;'],
                    'DAYSUN' => ['dayValue' => '&nbsp;'],
                ];
                // Every week has seven days. ;-)
                for ($k = 0; $k <= 6; $k++) {
                    $currentDayTime = strtotime('+ ' . $k . ' Day', $firstDayOfWeek);
                    if (
                        $currentDayTime >= $firstOfMonth
                        && $currentDayTime <= $lastOfMonth
                    ) {
                        $dayLinks = '';
                        $dayLinksText = [];
                        $dayLinksList = '';
                        $currentMonth = date('n', $currentDayTime);
                        if (is_array($calendarIssuesByMonth[$currentMonth])) {
                            foreach ($calendarIssuesByMonth[$currentMonth] as $id => $day) {
                                if ($id == date('j', $currentDayTime)) {
                                    $dayLinks = $id;
                                    foreach ($day as $issue) {
                                        $dayLinkLabel = empty($issue['title']) ? strftime('%x', $currentDayTime) : $issue['title'];

                                        $uri = $this->uriBuilder->reset()
                                            ->setTargetPageUid($this->settings['targetPid'])
                                            ->setCreateAbsoluteUri(!empty($this->settings['forceAbsoluteUrl']) ? 1 : 0)
                                            ->setArguments([$this->prefixId . '[id]' => urlencode($issue['uid'])])
                                            ->build();

                                        $dayLinksText[] = [
                                            'url' => $uri,
                                            'text' => $dayLinkLabel
                                        ];

                                        // Save issue for list view.
                                        $this->allIssues[$currentDayTime][] = [
                                            'url' => $uri,
                                            'text' => $dayLinkLabel
                                        ];
                                    }
                                }
                            }
                            $dayLinkDiv = $dayLinksText;
                        }
                        switch (strftime('%w', strtotime('+ ' . $k . ' Day', $firstDayOfWeek))) {
                            case '0':
                                $calendarData[$i]['week'][$j]['DAYSUN']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$i]['week'][$j]['DAYSUN']['issues'] = $dayLinkDiv;
                                }
                                break;
                            case '1':
                                $calendarData[$i]['week'][$j]['DAYMON']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$i]['week'][$j]['DAYMON']['issues'] = $dayLinkDiv;
                                }
                                break;
                            case '2':
                                $calendarData[$i]['week'][$j]['DAYTUE']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$i]['week'][$j]['DAYTUE']['issues'] = $dayLinkDiv;
                                }
                                break;
                            case '3':
                                $calendarData[$i]['week'][$j]['DAYWED']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$i]['week'][$j]['DAYWED']['issues'] = $dayLinkDiv;
                                }
                                break;
                            case '4':
                                $calendarData[$i]['week'][$j]['DAYTHU']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$i]['week'][$j]['DAYTHU']['issues'] = $dayLinkDiv;
                                }
                                break;
                            case '5':
                                $calendarData[$i]['week'][$j]['DAYFRI']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$i]['week'][$j]['DAYFRI']['issues'] = $dayLinkDiv;
                                }
                                break;
                            case '6':
                                $calendarData[$i]['week'][$j]['DAYSAT']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$i]['week'][$j]['DAYSAT']['issues'] = $dayLinkDiv;
                                }
                                break;
                        }
                    }
                }
            }
        }
        $this->view->assign('calendarData', $calendarData);
    }
}