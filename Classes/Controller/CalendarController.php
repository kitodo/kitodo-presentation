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
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller class for the plugin 'Calendar'.
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class CalendarController extends AbstractController
{
    /**
     * @var StructureRepository
     */
    protected $structureRepository;

    /**
     * @param StructureRepository $structureRepository
     */
    public function injectStructureRepository(StructureRepository $structureRepository)
    {
        $this->structureRepository = $structureRepository;
    }

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
        // Set initial document (anchor or year file) if configured.
        if (empty($this->requestData['id']) && !empty($this->settings['initialDocument'])) {
            $this->requestData['id'] = $this->settings['initialDocument'];
        }

        // Load current document.
        $this->loadDocument($this->requestData);
        if ($this->document === null) {
            // Quit without doing anything if required variables are not set.
            return '';
        }

        $metadata = $this->document->getDoc()->getTitledata();
        if (!empty($metadata['type'][0])) {
            $type = $metadata['type'][0];
        } else {
            return;
        }

        switch ($type) {
            case 'newspaper':
            case 'ephemera':
                $this->forward('years', null, null, $this->requestData);
                break;
            case 'year':
                $this->forward('calendar', null, null, $this->requestData);
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
        // access arguments passed by the mainAction()
        $mainrequestData = $this->request->getArguments();

        // merge both arguments together --> passing id by GET parameter tx_dlf[id] should win
        $this->requestData = array_merge($this->requestData, $mainrequestData);

        // Load current document.
        $this->loadDocument($this->requestData);
        if ($this->document === null) {
            // Quit without doing anything if required variables are not set.
            return '';
        }

        $documents = $this->documentRepository->getChildrenOfYearAnchor($this->document->getUid(), $this->structureRepository->findOneByIndexName('issue'));

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
        $calendarData = [];
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
            $this->getCalendarYear($calendarData, $calendarIssuesByMonth, $year, $firstMonth, $lastMonth);
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
        $linkTitleData = $this->document->getDoc()->getTitledata();
        $yearLinkTitle = !empty($linkTitleData['mets_orderlabel'][0]) ? $linkTitleData['mets_orderlabel'][0] : $linkTitleData['mets_label'][0];

        $this->view->assign('calendarData', $calendarData);
        $this->view->assign('documentId', $this->document->getUid());
        $this->view->assign('yearLinkTitle', $yearLinkTitle);
        $this->view->assign('parentDocumentId', $this->document->getPartof());
        $this->view->assign('allYearDocTitle', $this->document->getDoc()->getTitle($this->document->getPartof()));
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
        // access arguments passed by the mainAction()
        $mainrequestData = $this->request->getArguments();

        // merge both arguments together --> passing id by GET parameter tx_dlf[id] should win
        $this->requestData = array_merge($this->requestData, $mainrequestData);

        // Load current document.
        $this->loadDocument($this->requestData);
        if ($this->document === null) {
            // Quit without doing anything if required variables are not set.
            return '';
        }

        // Get all children of anchor. This should be the year anchor documents
        $documents = $this->documentRepository->getChildrenOfYearAnchor($this->document->getUid(), $this->structureRepository->findOneByIndexName('year'));

        $years = [];
        // Process results.
        if (count($documents) === 0) {
            foreach ($this->document->getDoc()->tableOfContents[0]['children'] as $id => $year) {
                $yearLabel = empty($year['label']) ? $year['orderlabel'] : $year['label'];

                if (empty($yearLabel)) {
                    // if neither order nor orderlabel is set, use the id...
                    $yearLabel = (string)$id;
                }

                $years[] = [
                    'title' => $yearLabel,
                    'uid' => $year['points'],
                ];
            }
        } else {
            /** @var Document $document */
            foreach ($documents as $document) {
                $years[] = [
                    'title' => !empty($document->getMetsLabel()) ? $document->getMetsLabel() : (!empty($document->getMetsOrderlabel()) ? $document->getMetsOrderlabel() : $document->getTitle()),
                    'uid' => $document->getUid()
                ];
            }
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

        $this->view->assign('documentId', $this->document->getUid());
        $this->view->assign('allYearDocTitle', $this->document->getDoc()->getTitle($this->document->getUid()));
    }

    /**
     * Build calendar for a certain year
     *
     * @access protected
     *
     * @param array $calendarData Output array containing the result calendar data that is passed to Fluid template
     * @param array $calendarIssuesByMonth All issues sorted by month => day
     * @param int $year Gregorian year
     * @param int $firstMonth 1 for January, 2 for February, ... 12 for December
     * @param int $lastMonth 1 for January, 2 for February, ... 12 for December
     *
     * @return string Content for template subpart
     */
    protected function getCalendarYear(&$calendarData, $calendarIssuesByMonth, $year, $firstMonth = 1, $lastMonth = 12)
    {
        for ($i = $firstMonth; $i <= $lastMonth; $i++) {
            $key = $year . '-' . $i;

            $calendarData[$key] = [
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

                $calendarData[$key]['week'][$j] = [
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
                        $dayLinkDiv = [];
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
                                $calendarData[$key]['week'][$j]['DAYSUN']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$key]['week'][$j]['DAYSUN']['issues'] = $dayLinkDiv;
                                }
                                break;
                            case '1':
                                $calendarData[$key]['week'][$j]['DAYMON']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$key]['week'][$j]['DAYMON']['issues'] = $dayLinkDiv;
                                }
                                break;
                            case '2':
                                $calendarData[$key]['week'][$j]['DAYTUE']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$key]['week'][$j]['DAYTUE']['issues'] = $dayLinkDiv;
                                }
                                break;
                            case '3':
                                $calendarData[$key]['week'][$j]['DAYWED']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$key]['week'][$j]['DAYWED']['issues'] = $dayLinkDiv;
                                }
                                break;
                            case '4':
                                $calendarData[$key]['week'][$j]['DAYTHU']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$key]['week'][$j]['DAYTHU']['issues'] = $dayLinkDiv;
                                }
                                break;
                            case '5':
                                $calendarData[$key]['week'][$j]['DAYFRI']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$key]['week'][$j]['DAYFRI']['issues'] = $dayLinkDiv;
                                }
                                break;
                            case '6':
                                $calendarData[$key]['week'][$j]['DAYSAT']['dayValue'] = strftime('%d', $currentDayTime);
                                if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
                                    $calendarData[$key]['week'][$j]['DAYSAT']['issues'] = $dayLinkDiv;
                                }
                                break;
                        }
                    }
                }
            }
        }
    }
}
