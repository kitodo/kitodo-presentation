<?php
namespace Kitodo\Dlf\Plugin;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Calendar' for the 'dlf' extension
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Calendar extends \Kitodo\Dlf\Common\AbstractPlugin {
    public $scriptRelPath = 'Classes/Plugin/Calendar.php';

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
    public function calendar($content, $conf) {
        $this->init($conf);
        // Load current document.
        $this->loadDocument();
        if ($this->doc === NULL) {
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
                'tx_dlf_documents.year AS year'
            )
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.structure', Helper::getUidFromIndexName('issue', 'tx_dlf_structures', $this->doc->pid)),
                $queryBuilder->expr()->eq('tx_dlf_documents.partof', intval($this->doc->uid)),
                Helper::whereExpression('tx_dlf_documents')
            )
            ->orderBy('tx_dlf_documents.title_sorting')
            ->execute();

        $issues = [];

        // Process results.
        while ($resArray = $result->fetch()) {
            $issues[] = [
                'uid' => $resArray['uid'],
                'title' => $resArray['title'],
                'year' => $resArray['year']
            ];
        }
        //  We need an array of issues with month number as key.
        $calendarIssues = [];
        foreach ($issues as $issue) {
            $calendarIssues[date('n', strtotime($issue['year']))][date('j', strtotime($issue['year']))][] = $issue;
        }
        $allIssues = [];
        // Get subpart templates.
        $subparts['list'] = $this->templateService->getSubpart($this->template, '###ISSUELIST###');
        $subparts['month'] = $this->templateService->getSubpart($this->template, '###CALMONTH###');
        $subparts['week'] = $this->templateService->getSubpart($subparts['month'], '###CALWEEK###');
        $subparts['singleday'] = $this->templateService->getSubpart($subparts['list'], '###SINGLEDAY###');
        // Build calendar for given year.
        $year = date('Y', strtotime($issues[0]['year']));
        $subPartContent = '';
        for ($i = 0; $i <= 11; $i++) {
            $markerArray = [
                '###DAYMON_NAME###' => strftime('%a', strtotime('last Monday')),
                '###DAYTUE_NAME###' => strftime('%a', strtotime('last Tuesday')),
                '###DAYWED_NAME###' => strftime('%a', strtotime('last Wednesday')),
                '###DAYTHU_NAME###' => strftime('%a', strtotime('last Thursday')),
                '###DAYFRI_NAME###' => strftime('%a', strtotime('last Friday')),
                '###DAYSAT_NAME###' => strftime('%a', strtotime('last Saturday')),
                '###DAYSUN_NAME###' => strftime('%a', strtotime('last Sunday')),
                '###MONTHNAME###'  => strftime('%B', strtotime($year.'-'.($i + 1).'-1'))
            ];
            // Reset week content of new month.
            $subWeekPartContent = '';
            $firstOfMonth = strtotime($year.'-'.($i + 1).'-1');
            $lastOfMonth = strtotime('last day of', ($firstOfMonth));
            $firstOfMonthStart = strtotime('last Monday', $firstOfMonth);
            // There are never more than 6 weeks in a month.
            for ($j = 0; $j <= 5; $j++) {
                $firstDayOfWeek = strtotime('+ '.$j.' Week', $firstOfMonthStart);
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
                    $currentDayTime = strtotime('+ '.$k.' Day', $firstDayOfWeek);
                    if ($currentDayTime >= $firstOfMonth
                        && $currentDayTime <= $lastOfMonth) {
                        $dayLinks = '';
                        $dayLinksText = [];
                        $dayLinksList = '';
                        $currentMonth = date('n', $currentDayTime);
                        if (is_array($calendarIssues[$currentMonth])) {
                            foreach ($calendarIssues[$currentMonth] as $id => $day) {
                                if ($id == date('j', $currentDayTime)) {
                                    $dayLinks = $id;
                                    foreach ($day as $issue) {
                                        $dayLinkLabel = empty($issue['title']) ? strftime('%x', $currentDayTime) : $issue['title'];
                                        $linkConf = [
                                            'useCacheHash' => 1,
                                            'parameter' => $this->conf['targetPid'],
                                            'additionalParams' => '&'.$this->prefixId.'[id]='.urlencode($issue['uid']),
                                            'ATagParams' => ' class="title"',
                                        ];
                                        $dayLinksText[] = $this->cObj->typoLink($dayLinkLabel, $linkConf);
                                        // Save issues for list view.
                                        $allIssues[$currentDayTime][] = $this->cObj->typoLink($dayLinkLabel, $linkConf);
                                    }
                                }
                            }
                            if (!empty($dayLinksText)) {
                                $dayLinksList = '<ul>';
                                foreach ($dayLinksText as $link) {
                                    $dayLinksList .= '<li>'.$link.'</li>';
                                }
                                $dayLinksList .= '</ul>';
                            }
                            $dayLinkDiv = '<div class="issues"><h4>'.strftime('%d', $currentDayTime).'</h4><div>'.$dayLinksList.'</div></div>';
                        }
                        switch (strftime('%w', strtotime('+ '.$k.' Day', $firstDayOfWeek))) {
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
                $subWeekPartContent .= $this->templateService->substituteMarkerArray($subparts['week'], $weekArray);
            }
            // Fill the month markers.
            $subPartContent .= $this->templateService->substituteMarkerArray($subparts['month'], $markerArray);
            // Fill the week markers with the week entries.
            $subPartContent = $this->templateService->substituteSubpart($subPartContent, '###CALWEEK###', $subWeekPartContent);
        }
        // Link to years overview
        $linkConf = [
            'useCacheHash' => 1,
            'parameter' => $this->conf['targetPid'],
            'additionalParams' => '&'.$this->prefixId.'[id]='.urlencode($this->doc->parentId),
        ];
        $allYearsLink = $this->cObj->typoLink($this->pi_getLL('allYears', '', TRUE).' '.$this->doc->getTitle($this->doc->parentId), $linkConf);
        // Link to current year.
        $linkConf = [
            'useCacheHash' => 1,
            'parameter' => $this->conf['targetPid'],
            'additionalParams' => '&'.$this->prefixId.'[id]='.urlencode($this->doc->uid),
        ];
        $yearLink = $this->cObj->typoLink($year, $linkConf);
        $subPartContentList = '';
        // Prepare list as alternative of the calendar view.
        foreach ($allIssues as $dayTime => $issues) {
            $markerArrayDay['###DATE_STRING###'] = strftime('%A, %x', $dayTime);
            $markerArrayDay['###ITEMS###'] = '';
            foreach ($issues as $issue) {
                $markerArrayDay['###ITEMS###'] .= $issue;
            }
            $subPartContentList .= $this->templateService->substituteMarkerArray($subparts['singleday'], $markerArrayDay);
        }
        $this->template = $this->templateService->substituteSubpart($this->template, '###SINGLEDAY###', $subPartContentList);
        if (count($allIssues) < 6) {
            $listViewActive = TRUE;
        } else {
            $listViewActive = FALSE;
        }
        $markerArray = [
            '###CALENDARVIEWACTIVE###' => $listViewActive ? '' : 'active',
            '###LISTVIEWACTIVE###' => $listViewActive ? 'active' : '',
            '###CALYEAR###' => $yearLink,
            '###CALALLYEARS###' => $allYearsLink,
            '###LABEL_CALENDAR###' => $this->pi_getLL('label.view_calendar'),
            '###LABEL_LIST_VIEW###' => $this->pi_getLL('label.view_list'),
        ];
        $this->template = $this->templateService->substituteMarkerArray($this->template, $markerArray);
        return $this->templateService->substituteSubpart($this->template, '###CALMONTH###', $subPartContent);
    }

    /**
     * The Year Method
     *
     * @access public
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return string The content that is displayed on the website
     */
    public function years($content, $conf) {
        $this->init($conf);
        // Load current document.
        $this->loadDocument();
        if ($this->doc === NULL) {
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
                'tx_dlf_documents.title AS title'
            )
            ->from('tx_dlf_documents')
            ->where(
                $queryBuilder->expr()->eq('tx_dlf_documents.structure', Helper::getUidFromIndexName('year', 'tx_dlf_structures', $this->doc->pid)),
                $queryBuilder->expr()->eq('tx_dlf_documents.partof', intval($this->doc->uid)),
                Helper::whereExpression('tx_dlf_documents')
            )
            ->orderBy('tx_dlf_documents.title_sorting')
            ->execute();

        // Process results.
        while ($resArray = $result->fetch()) {
            $years[] = [
                'title' => $resArray['title'],
                'uid' => $resArray['uid']
            ];
        }
        $subYearPartContent = '';
        if (count($years) > 0) {
            foreach ($years as $year) {
                $linkConf = [
                    'useCacheHash' => 1,
                    'parameter' => $this->conf['targetPid'],
                    'additionalParams' => '&'.$this->prefixId.'[id]='.urlencode($year['uid']),
                    'title' => $titleAnchor.': '.$year['title']
                ];
                $yearArray = [
                    '###YEARNAME###' => $this->cObj->typoLink($year['title'], $linkConf),
                ];
                $subYearPartContent .= $this->templateService->substituteMarkerArray($subparts['year'], $yearArray);
            }
        }
        // link to years overview (should be itself here)
        $linkConf = [
            'useCacheHash' => 1,
            'parameter' => $this->conf['targetPid'],
            'additionalParams' => '&'.$this->prefixId.'[id]='.$this->doc->uid,
        ];
        $allYearsLink = $this->cObj->typoLink($this->pi_getLL('allYears', '', TRUE).' '.$this->doc->getTitle($this->doc->uid), $linkConf);
        // Fill markers.
        $markerArray = [
            '###LABEL_CHOOSE_YEAR###' => $this->pi_getLL('label.please_choose_year'),
            '###CALALLYEARS###' => $allYearsLink
        ];
        $this->template = $this->templateService->substituteMarkerArray($this->template, $markerArray);
        // Fill the week markers
        return $this->templateService->substituteSubpart($this->template, '###LISTYEAR###', $subYearPartContent);
    }
}
