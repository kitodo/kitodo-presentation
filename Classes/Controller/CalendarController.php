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

use Kitodo\Dlf\Domain\Model\Document;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CalendarController extends AbstractController
{
    /**
     * This holds all issues for the list view.
     *
     * @var array
     * @access protected
     */
    protected $allIssues = [];

    /**
     * The main method of the plugin
     *
     * @return void
     */
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
            return;
        }

        $metadata = $this->doc->getTitledata();
        if (!empty($metadata['type'][0])) {
            $type = $metadata['type'][0];
        } else {
            return;
        }

        switch ($type) {
            case 'newspaper':
            case 'ephemera':
                $this->forward('years', null, null, $requestData);
                break;
            case 'year':
                $this->forward('calendar', null, null, $requestData);
                break;
            case 'issue':
            default:
                break;
        }

    }

    /**
     * The Calendar Method
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return void
     */
    public function calendarAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'], $requestData['__trustedProperties']);

        // access arguments passed by the mainAction()
        $mainrequestData = $this->request->getArguments();

        // merge both arguments together --> passing id by GET parameter tx_dlf[id] should win
        $requestData = array_merge($requestData, $mainrequestData);

        // Load current document.
        $this->loadDocument($requestData);
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return;
        }

        $documents = $this->documentRepository->getChildrenOfYearAnchor($this->doc->uid, 'issue');

        $issues = [];

        // Process results.
        /** @var Document $document */
        foreach ($documents as $document) {
            // Set title for display in calendar view.
            if (!empty($document->getTitle())) {
                $title = $document->getTitle();
            } else {
                $title = !empty($document->getMetsLabel()) ? $document->getMetsLabel() : $document->getMetsOrderlabel();
                if (strtotime($title) !== false) {
                    $title = strftime('%x', strtotime($title));
                }
            }
            $issues[] = [
                'uid' => $document->getUid(),
                'title' => $title,
                'year' => $document->getYear()
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
        $linkTitleData = $this->doc->getTitledata();
        $yearLinkTitle = !empty($linkTitleData['mets_orderlabel'][0]) ? $linkTitleData['mets_orderlabel'][0] : $linkTitleData['mets_label'][0];

        $this->view->assign('documentId', $this->doc->uid);
        $this->view->assign('yearLinkTitle', $yearLinkTitle);
        $this->view->assign('parentDocumentId', $this->doc->parentId);
        $this->view->assign('allYearDocTitle', $this->doc->getTitle($this->doc->parentId));
    }

    /**
     * The Years Method
     *
     * @access public
     *
     * @return void
     */
    public function yearsAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'], $requestData['__trustedProperties']);

        // access arguments passed by the mainAction()
        $mainrequestData = $this->request->getArguments();

        // merge both arguments together --> passing id by GET parameter tx_dlf[id] should win
        $requestData = array_merge($requestData, $mainrequestData);

        // Load current document.
        $this->loadDocument($requestData);
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return;
        }

        // Get all children of anchor. This should be the year anchor documents
        $documents = $this->documentRepository->getChildrenOfYearAnchor($this->doc->uid, 'year');

        $years = [];
        // Process results.
        /** @var Document $document */
        foreach ($documents as $document) {
            $years[] = [
                'title' => !empty($document->getMetsLabel()) ? $document->getMetsLabel() : (!empty($document->getMetsOrderlabel()) ? $document->getMetsOrderlabel() : $document->getTitle()),
                'uid' => $document->getUid()
            ];
        }

        $yearArray = [];
        if (count($years) > 0) {
            foreach ($years as $year) {
                $yearArray[] = [
                    'documentId' => $year['uid'],
                    'title' => $year['title']
                ];
            }
            $this->view->assign('yearName', $yearArray);
        }

        $this->view->assign('documentId', $this->doc->uid);
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
                        $currentMonth = date('n', $currentDayTime);
                        if (is_array($calendarIssuesByMonth[$currentMonth])) {
                            foreach ($calendarIssuesByMonth[$currentMonth] as $id => $day) {
                                if ($id == date('j', $currentDayTime)) {
                                    $dayLinks = $id;
                                    foreach ($day as $issue) {
                                        $dayLinkLabel = empty($issue['title']) ? strftime('%x', $currentDayTime) : $issue['title'];

                                        $dayLinksText[] = [
                                            'documentId' => $issue['uid'],
                                            'text' => $dayLinkLabel
                                        ];

                                        // Save issue for list view.
                                        $this->allIssues[$currentDayTime][] = [
                                            'documentId' => $issue['uid'],
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
