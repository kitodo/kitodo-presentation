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

namespace Kitodo\Dlf\Plugin;

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Calendar' for the 'dlf' extension
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Calendar extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Calendar.php';

    /**
     * This holds all issues for the list view.
     *
     * @var array
     * @access protected
     */
    protected $allIssues = [];

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
    public function main($content, $conf)
    {
        $this->init($conf);

        // Set initial document (anchor or year file) if configured.
        if (empty($this->piVars['id']) && !empty($this->conf['initialDocument'])) {
            $this->piVars['id'] = $this->conf['initialDocument'];
        }

        // Load current document.
        $this->loadDocument();
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return $content;
        }

        $metadata = $this->doc->getTitledata();
        if (!empty($metadata['type'][0])) {
            $type = $metadata['type'][0];
        } else {
            return $content;
        }

        switch ($type) {
            case 'newspaper':
            case 'ephemera':
                return $this->years($content, $conf);
            case 'year':
                return $this->calendar($content, $conf);
            case 'issue':
            default:
                break;
        }

        // Nothing to do here.
        return $content;
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
    public function calendar($content, $conf)
    {
        $this->init($conf);
        // Load current document.
        $this->loadDocument();
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return $content;
        }
        // Load template file.
        $this->getTemplate('###TEMPLATECALENDAR###');

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
                Helper::devLog('Document with UID ' . $issue['uid'] . 'has no valid date of publication', DEVLOG_SEVERITY_WARNING);
            }
        }
        // Sort by years.
        ksort($calendarIssuesByYear);
        // Build calendar for year (default) or season.
        $subPartContent = '';
        $iteration = 1;
        foreach ($calendarIssuesByYear as $year => $calendarIssuesByMonth) {
            // Sort by months.
            ksort($calendarIssuesByMonth);
            // Default: First month is January, last month is December.
            $firstMonth = 1;
            $lastMonth = 12;
            // Show calendar from first issue up to end of season if applicable.
            if (
                empty($this->conf['showEmptyMonths'])
                && count($calendarIssuesByYear) > 1
            ) {
                if ($iteration == 1) {
                    $firstMonth = (int) key($calendarIssuesByMonth);
                } elseif ($iteration == count($calendarIssuesByYear)) {
                    end($calendarIssuesByMonth);
                    $lastMonth = (int) key($calendarIssuesByMonth);
                }
            }
            $subPartContent .= $this->getCalendarYear($calendarIssuesByMonth, $year, $firstMonth, $lastMonth);
            $iteration++;
        }
        // Prepare list as alternative view.
        $subPartContentList = '';
        // Get subpart templates.
        $subParts['list'] = $this->templateService->getSubpart($this->template, '###ISSUELIST###');
        $subParts['singleday'] = $this->templateService->getSubpart($subParts['list'], '###SINGLEDAY###');
        foreach ($this->allIssues as $dayTimestamp => $issues) {
            $markerArrayDay['###DATE_STRING###'] = strftime('%A, %x', $dayTimestamp);
            $markerArrayDay['###ITEMS###'] = '';
            foreach ($issues as $issue) {
                $markerArrayDay['###ITEMS###'] .= $issue;
            }
            $subPartContentList .= $this->templateService->substituteMarkerArray($subParts['singleday'], $markerArrayDay);
        }
        $this->template = $this->templateService->substituteSubpart($this->template, '###SINGLEDAY###', $subPartContentList);
        // Link to current year.
        $linkConf = [
            'useCacheHash' => 1,
            'parameter' => $GLOBALS['TSFE']->id,
            'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
            'additionalParams' => '&' . $this->prefixId . '[id]=' . urlencode($this->doc->uid),
        ];
        $linkTitleData = $this->doc->getTitledata();
        $linkTitle = !empty($linkTitleData['mets_orderlabel'][0]) ? $linkTitleData['mets_orderlabel'][0] : $linkTitleData['mets_label'][0];
        $yearLink = $this->cObj->typoLink($linkTitle, $linkConf);
        // Link to years overview.
        $linkConf = [
            'useCacheHash' => 1,
            'parameter' => $GLOBALS['TSFE']->id,
            'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
            'additionalParams' => '&' . $this->prefixId . '[id]=' . urlencode($this->doc->parentId),
        ];
        $allYearsLink = $this->cObj->typoLink(htmlspecialchars($this->pi_getLL('allYears', '')) . ' ' . $this->doc->getTitle($this->doc->parentId), $linkConf);
        // Fill marker array.
        $markerArray = [
            '###CALENDARVIEWACTIVE###' => count($this->allIssues) > 5 ? 'active' : '',
            '###LISTVIEWACTIVE###' => count($this->allIssues) < 6 ? 'active' : '',
            '###CALYEAR###' => $yearLink,
            '###CALALLYEARS###' => $allYearsLink,
            '###LABEL_CALENDAR###' => htmlspecialchars($this->pi_getLL('label.view_calendar')),
            '###LABEL_LIST_VIEW###' => htmlspecialchars($this->pi_getLL('label.view_list')),
        ];
        $this->template = $this->templateService->substituteMarkerArray($this->template, $markerArray);
        return $this->templateService->substituteSubpart($this->template, '###CALMONTH###', $subPartContent);
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
        // Get subpart templates.
        $subPartContent = '';
        $subParts['month'] = $this->templateService->getSubpart($this->template, '###CALMONTH###');
        $subParts['week'] = $this->templateService->getSubpart($subParts['month'], '###CALWEEK###');
        for ($i = $firstMonth; $i <= $lastMonth; $i++) {
            $markerArray = [
                '###DAYMON_NAME###' => strftime('%a', strtotime('last Monday')),
                '###DAYTUE_NAME###' => strftime('%a', strtotime('last Tuesday')),
                '###DAYWED_NAME###' => strftime('%a', strtotime('last Wednesday')),
                '###DAYTHU_NAME###' => strftime('%a', strtotime('last Thursday')),
                '###DAYFRI_NAME###' => strftime('%a', strtotime('last Friday')),
                '###DAYSAT_NAME###' => strftime('%a', strtotime('last Saturday')),
                '###DAYSUN_NAME###' => strftime('%a', strtotime('last Sunday')),
                '###MONTHNAME###'  => strftime('%B', strtotime($year . '-' . $i . '-1')) . ' ' . $year,
                '###CALYEAR###' => ($i == $firstMonth) ? '<div class="year">' . $year . '</div>' : ''
            ];
            // Fill the month markers.
            $subPartContentMonth = $this->templateService->substituteMarkerArray($subParts['month'], $markerArray);
            // Reset week content of new month.
            $subPartContentWeek = '';
            $firstOfMonth = strtotime($year . '-' . $i . '-1');
            $lastOfMonth = strtotime('last day of', ($firstOfMonth));
            $firstOfMonthStart = strtotime('last Monday', $firstOfMonth);
            // There are never more than 6 weeks in a month.
            for ($j = 0; $j <= 5; $j++) {
                $firstDayOfWeek = strtotime('+ ' . $j . ' Week', $firstOfMonthStart);
                $weekArray = [
                    '###DAYMON###' => '&nbsp;',
                    '###DAYTUE###' => '&nbsp;',
                    '###DAYWED###' => '&nbsp;',
                    '###DAYTHU###' => '&nbsp;',
                    '###DAYFRI###' => '&nbsp;',
                    '###DAYSAT###' => '&nbsp;',
                    '###DAYSUN###' => '&nbsp;',
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
                                        $linkConf = [
                                            'useCacheHash' => 1,
                                            'parameter' => $this->conf['targetPid'],
                                            'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
                                            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
                                            'additionalParams' => '&' . $this->prefixId . '[id]=' . urlencode($issue['uid']),
                                            'ATagParams' => ' class="title"',
                                        ];
                                        $dayLinksText[] = $this->cObj->typoLink($dayLinkLabel, $linkConf);
                                        // Save issue for list view.
                                        $this->allIssues[$currentDayTime][] = $this->cObj->typoLink($dayLinkLabel, $linkConf);
                                    }
                                }
                            }
                            if (!empty($dayLinksText)) {
                                $dayLinksList = '<ul>';
                                foreach ($dayLinksText as $link) {
                                    $dayLinksList .= '<li>' . $link . '</li>';
                                }
                                $dayLinksList .= '</ul>';
                            }
                            $dayLinkDiv = '<div class="issues"><h4>' . strftime('%d', $currentDayTime) . '</h4><div>' . $dayLinksList . '</div></div>';
                        }
                        switch (strftime('%w', strtotime('+ ' . $k . ' Day', $firstDayOfWeek))) {
                            case '0':
                                $weekArray['###DAYSUN###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;
                            case '1':
                                $weekArray['###DAYMON###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;
                            case '2':
                                $weekArray['###DAYTUE###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;
                            case '3':
                                $weekArray['###DAYWED###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;
                            case '4':
                                $weekArray['###DAYTHU###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;
                            case '5':
                                $weekArray['###DAYFRI###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;
                            case '6':
                                $weekArray['###DAYSAT###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;
                        }
                    }
                }
                // Fill the weeks.
                $subPartContentWeek .= $this->templateService->substituteMarkerArray($subParts['week'], $weekArray);
            }
            // Fill the week markers with the week entries.
            $subPartContent .= $this->templateService->substituteSubpart($subPartContentMonth, '###CALWEEK###', $subPartContentWeek);
        }
        return $subPartContent;
    }

    /**
     * The Years Method
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function years($content, $conf)
    {
        $this->init($conf);
        // Load current document.
        $this->loadDocument();
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return $content;
        }
        // Load template file.
        $this->getTemplate('###TEMPLATEYEAR###');
        // Get subpart templates
        $subparts['year'] = $this->templateService->getSubpart($this->template, '###LISTYEAR###');
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
        $subYearPartContent = '';
        if (count($years) > 0) {
            foreach ($years as $year) {
                $linkConf = [
                    'useCacheHash' => 1,
                    'parameter' => $GLOBALS['TSFE']->id,
                    'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
                    'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
                    'additionalParams' => '&' . $this->prefixId . '[id]=' . urlencode($year['uid']),
                    'title' => $titleAnchor . ': ' . $year['title']
                ];
                $yearArray = [
                    '###YEARNAME###' => $this->cObj->typoLink($year['title'], $linkConf),
                ];
                $subYearPartContent .= $this->templateService->substituteMarkerArray($subparts['year'], $yearArray);
            }
        }
        // Link to years overview (should be itself here)
        $linkConf = [
            'useCacheHash' => 1,
            'parameter' => $GLOBALS['TSFE']->id,
            'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
            'additionalParams' => '&' . $this->prefixId . '[id]=' . $this->doc->uid,
        ];
        $allYearsLink = $this->cObj->typoLink(htmlspecialchars($this->pi_getLL('allYears', '')) . ' ' . $this->doc->getTitle($this->doc->uid), $linkConf);
        // Fill markers.
        $markerArray = [
            '###LABEL_CHOOSE_YEAR###' => htmlspecialchars($this->pi_getLL('label.please_choose_year')),
            '###CALALLYEARS###' => $allYearsLink
        ];
        $this->template = $this->templateService->substituteMarkerArray($this->template, $markerArray);
        // Fill the week markers
        return $this->templateService->substituteSubpart($this->template, '###LISTYEAR###', $subYearPartContent);
    }
}
