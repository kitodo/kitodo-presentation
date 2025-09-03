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

use Generator;
use Kitodo\Dlf\Domain\Model\Document;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Controller class for the plugin 'Calendar'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class CalendarController extends AbstractController
{
    /**
     * @access protected
     * @var StructureRepository
     */
    protected StructureRepository $structureRepository;

    /**
     * @access public
     *
     * @param StructureRepository $structureRepository
     *
     * @return void
     */
    public function injectStructureRepository(StructureRepository $structureRepository): void
    {
        $this->structureRepository = $structureRepository;
    }

    /**
     * @access protected
     * @var array This holds all issues for the list view.
     */
    protected array $allIssues = [];

    /**
     * The main method of the plugin
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        // Set initial document (anchor or year file) if configured.
        if (empty($this->requestData['id']) && !empty($this->settings['initialDocument'])) {
            $this->requestData['id'] = $this->settings['initialDocument'];
        }

        // Load current document.
        $this->loadDocument();
        if ($this->isDocMissing()) {
            // Quit without doing anything if required variables are not set.
            return $this->htmlResponse();
        }

        $metadata = $this->document->getCurrentDocument()->getToplevelMetadata();
        if (!empty($metadata['type'][0])) {
            $type = $metadata['type'][0];
        } else {
            return $this->htmlResponse();
        }

        return match ($type) {
            'newspaper', 'ephemera' => new ForwardResponse('years'),
            'year' => new ForwardResponse('calendar'),
            default => $this->htmlResponse()
        };
    }

    /**
     * The Calendar Method
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function calendarAction(): ResponseInterface
    {
        // access arguments passed by the mainAction()
        $mainRequestData = $this->request->getArguments();

        // merge both arguments together --> passing id by GET parameter tx_dlf[id] should win
        $this->requestData = array_merge($this->requestData, $mainRequestData);

        // Load current document.
        $this->loadDocument();
        if ($this->isDocMissing()) {
            // Quit without doing anything if required variables are not set.
            return $this->htmlResponse();
        }

        $calendarData = $this->buildCalendar();

        // Prepare list as alternative view.
        $issueData = [];
        foreach ($this->allIssues as $dayTimestamp => $issues) {
            $issueData[$dayTimestamp]['dateString'] = date('l, Y-m-d', $dayTimestamp);
            $issueData[$dayTimestamp]['items'] = [];
            foreach ($issues as $issue) {
                $issueData[$dayTimestamp]['items'][] = $issue;
            }
        }
        $this->view->assign('issueData', $issueData);

        // Link to current year.
        $linkTitleData = $this->document->getCurrentDocument()->getToplevelMetadata();
        $yearLinkTitle = !empty($linkTitleData['mets_orderlabel'][0]) ? $linkTitleData['mets_orderlabel'][0] : $linkTitleData['mets_label'][0];

        $this->view->assign('calendarData', $calendarData);
        $this->view->assign('documentId', $this->document->getUid());
        $this->view->assign('yearLinkTitle', $yearLinkTitle);
        $this->view->assign('parentDocumentId', $this->document->getPartof() ?: $this->document->getCurrentDocument()->tableOfContents[0]['points']);
        $this->view->assign('allYearDocTitle', $this->document->getCurrentDocument()->getTitle($this->document->getPartof()) ?: $this->document->getCurrentDocument()->tableOfContents[0]['label']);

        return $this->htmlResponse();
    }

    /**
     * The Years Method
     *
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function yearsAction(): ResponseInterface
    {
        // access arguments passed by the mainAction()
        $mainRequestData = $this->request->getArguments();

        // merge both arguments together --> passing id by GET parameter tx_dlf[id] should win
        $this->requestData = array_merge($this->requestData, $mainRequestData);

        // Load current document.
        $this->loadDocument();
        if ($this->isDocMissing()) {
            // Quit without doing anything if required variables are not set.
            return $this->htmlResponse();
        }

        // Get all children of anchor. This should be the year anchor documents
        $documents = $this->documentRepository->getChildrenOfYearAnchor(
            $this->document->getUid(),
            $this->structureRepository->findOneBy(['indexName' => 'year'])
        );

        $years = [];
        // Process results.
        if (count($documents) === 0) {
            foreach ($this->document->getCurrentDocument()->tableOfContents[0]['children'] as $id => $year) {
                $yearLabel = empty($year['label']) ? $year['orderlabel'] : $year['label'];

                if (empty($yearLabel)) {
                    // if neither order nor orderlabel is set, use the id...
                    $yearLabel = (string) $id;
                }

                $years[] = [
                    'title' => $yearLabel,
                    'uid' => $year['points'] ?? null,
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
            // create an array that includes years without issues
            if (!empty($this->settings['showEmptyYears'])) {
                $yearFilled = [];
                $min = $yearArray[0]['title'];
                // round the starting decade down to zero for equal rows
                $min = (int) substr_replace($min, "0", -1);
                $max = (int) $yearArray[count($yearArray) - 1]['title'];
                // if we have an actual documentId it should be used, otherwise leave empty
                for ($i = 0; $i < $max - $min + 1; $i++) {
                    $key = array_search($min + $i, array_column($yearArray, 'title'));
                    if (is_int($key)) {
                        $yearFilled[] = $yearArray[$key];
                    } else {
                        $yearFilled[] = ['title' => $min + $i, 'documentId' => ''];
                    }
                }
                $yearArray = $yearFilled;
            }

            $this->view->assign('yearName', $yearArray);
        }

        $this->view->assign('documentId', $this->document->getUid());
        $this->view->assign('allYearDocTitle', $this->document->getCurrentDocument()->getTitle((int) $this->document->getUid()) ?: $this->document->getCurrentDocument()->tableOfContents[0]['label']);

        return $this->htmlResponse();
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
     * @return void
     */
    protected function getCalendarYear(array &$calendarData, array $calendarIssuesByMonth, int $year, int $firstMonth = 1, int $lastMonth = 12): void
    {
        for ($i = $firstMonth; $i <= $lastMonth; $i++) {
            $key = $year . '-' . $i;

            $calendarData[$key] = [
                'DAYMON_NAME' => date('D', strtotime('last Monday')),
                'DAYTUE_NAME' => date('D', strtotime('last Tuesday')),
                'DAYWED_NAME' => date('D', strtotime('last Wednesday')),
                'DAYTHU_NAME' => date('D', strtotime('last Thursday')),
                'DAYFRI_NAME' => date('D', strtotime('last Friday')),
                'DAYSAT_NAME' => date('D', strtotime('last Saturday')),
                'DAYSUN_NAME' => date('D', strtotime('last Sunday')),
                'MONTHNAME'  => date('F', strtotime($year . '-' . $i . '-1')) . ' ' . $year,
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
                        if (array_key_exists($currentMonth, $calendarIssuesByMonth) && is_array($calendarIssuesByMonth[$currentMonth])) {
                            foreach ($calendarIssuesByMonth[$currentMonth] as $id => $day) {
                                if ($id == date('j', $currentDayTime)) {
                                    $dayLinks = $id;
                                    $dayLinksText = array_merge($dayLinksText, $this->getDayLinksText($day, $currentDayTime));
                                }
                            }
                            $dayLinkDiv = $dayLinksText;
                        }
                        $this->fillCalendar($calendarData[$key]['week'][$j], $currentDayTime, $dayLinks, $dayLinkDiv, $firstDayOfWeek, $k);
                    }
                }
            }
        }
    }

    /**
     * Get text links for given day.
     *
     * @access private
     *
     * @param array $day all issues for given day
     * @param int $currentDayTime
     *
     * @return array all issues for given day as text links
     */
    private function getDayLinksText(array $day, int $currentDayTime): array
    {
        $dayLinksText = [];
        foreach ($day as $issue) {
            $dayLinkLabel = empty($issue['title']) ? date('Y-m-d', $currentDayTime) : $issue['title'];

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
        return $dayLinksText;
    }

    /**
     * Fill calendar.
     *
     * @access private
     *
     * @param array &$calendarData calendar passed by reference
     * @param int $currentDayTime
     * @param string $dayLinks
     * @param array $dayLinkDiv
     * @param int $firstDayOfWeek
     * @param int $k
     *
     * @return void
     */
    private function fillCalendar(array &$calendarData, int $currentDayTime, string $dayLinks, array $dayLinkDiv, int $firstDayOfWeek, int $k): void
    {
        $dayKey = match (date('w', strtotime('+ ' . $k . ' Day', $firstDayOfWeek))) {
            '0' => 'DAYSUN',
            '1' => 'DAYMON',
            '2' => 'DAYTUE',
            '3' => 'DAYWED',
            '4' => 'DAYTHU',
            '5' => 'DAYFRI',
            '6' => 'DAYSAT'
        };

        $this->fillDay($calendarData, $currentDayTime, $dayKey, $dayLinks, $dayLinkDiv);
    }

    /**
     * Fill day.
     *
     * @access private
     *
     * @param array &$calendarData calendar passed by reference
     * @param int $currentDayTime
     * @param string $day
     * @param string $dayLinks
     * @param array $dayLinkDiv
     *
     * @return void
     */
    private function fillDay(array &$calendarData, int $currentDayTime, string $day, string $dayLinks, array $dayLinkDiv): void
    {
        $calendarData[$day]['dayValue'] = date('d', $currentDayTime);
        if ((int) $dayLinks === (int) date('j', $currentDayTime)) {
            $calendarData[$day]['issues'] = $dayLinkDiv;
        }
    }

    /**
     * Build calendar for year (default) or season.
     *
     * @access private
     *
     * @return array
     */
    private function buildCalendar(): array
    {
        $issuesByYear = $this->getIssuesByYear();

        $calendarData = [];
        $iteration = 1;
        foreach ($issuesByYear as $year => $issuesByMonth) {
            // Sort by months.
            ksort($issuesByMonth);
            // Default: First month is January, last month is December.
            $firstMonth = 1;
            $lastMonth = 12;
            // Show calendar from first issue up to end of season if applicable.
            if (
                empty($this->settings['showEmptyMonths'])
                && count($issuesByYear) > 1
            ) {
                if ($iteration == 1) {
                    $firstMonth = (int) key($issuesByMonth);
                } elseif ($iteration == count($issuesByYear)) {
                    end($issuesByMonth);
                    $lastMonth = (int) key($issuesByMonth);
                }
            }
            $this->getCalendarYear($calendarData, $issuesByMonth, $year, $firstMonth, $lastMonth);
            $iteration++;
        }

        return $calendarData;
    }

    /**
     * Get issues by year
     *
     * @access private
     *
     * @return array
     */
    private function getIssuesByYear(): array
    {
        //  We need an array of issues with year => month => day number as key.
        $issuesByYear = [];

        foreach ($this->getIssues() as $issue) {
            $dateTimestamp = strtotime($issue['year']);
            if ($dateTimestamp !== false) {
                $_year = date('Y', $dateTimestamp);
                $_month = date('n', $dateTimestamp);
                $_day = date('j', $dateTimestamp);
                $issuesByYear[$_year][$_month][$_day][] = $issue;
            } else {
                $this->logger->warning('Document with UID ' . $issue['uid'] . 'has no valid date of publication');
            }
        }
        // Sort by years.
        ksort($issuesByYear);

        return $issuesByYear;
    }

    /**
     * Gets issues from table of contents or documents.
     *
     * @access private
     *
     * @return Generator
     */
    private function getIssues(): Generator
    {
        $documents = $this->documentRepository->getChildrenOfYearAnchor(
            $this->document->getUid(),
            $this->structureRepository->findOneBy(['indexName' => 'issue'])
        );

        // Process results.
        if ($documents->count() === 0) {
            return $this->getIssuesFromTableOfContents();
        }

        return $this->getIssuesFromDocuments($documents);
    }

    /**
     * Gets issues from table of contents.
     *
     * @access private
     *
     * @return Generator
     */
    private function getIssuesFromTableOfContents(): Generator
    {
        $toc = $this->document->getCurrentDocument()->tableOfContents;

        foreach ($toc[0]['children'] as $year) {
            if (array_key_exists('children', $year)) {
                foreach ($year['children'] as $month) {
                    foreach ($month['children'] as $day) {
                        foreach ($day['children'] as $issue) {
                            $title = $issue['label'] ?: $issue['orderlabel'];
                            if (strtotime($title) !== false) {
                                $title = strftime('%x', strtotime($title));
                            }

                            yield [
                                'uid' => $issue['points'] ?? null,
                                'title' => $title,
                                'year' => $day['orderlabel'],
                            ];
                        }
                    }
                }
            }
        }
    }

    /**
     * Gets issues from documents.
     *
     * @access private
     *
     * @param array|QueryResultInterface $documents to create issues
     *
     * @return Generator
     */
    private function getIssuesFromDocuments($documents): Generator
    {
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
            yield [
                'uid' => $document->getUid(),
                'title' => $title,
                'year' => $document->getYear()
            ];
        }
    }
}
