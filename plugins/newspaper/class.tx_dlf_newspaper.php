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

/**
 * Plugin 'DLF: Newspaper' for the 'dlf' extension.
 *
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @copyright	Copyright (c) 2016, Alexander Bigga, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_newspaper extends tx_dlf_plugin {

    public $extKey = 'dlf';

    public $scriptRelPath = 'plugins/newspaper/class.tx_dlf_newspaper.php';

    private $allIssues = array ();

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

        // Nothing to do here.
        return $content;

    }

    /**
     * The Calendar Method
     *
     * @access	public
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     *
     * @return	string		The content that is displayed on the website
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
        if (!empty($this->conf['templateFile'])) {

            $this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATECALENDAR###');

        } else {

            $this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/newspaper/template.tmpl'), '###TEMPLATECALENDAR###');

        }

        // Get all children of year anchor.
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_documents.uid AS uid, tx_dlf_documents.mets_label AS label, tx_dlf_documents.mets_orderlabel AS orderlabel, tx_dlf_documents.title AS title, tx_dlf_documents.year AS year',
            'tx_dlf_documents',
            '(tx_dlf_documents.structure='.tx_dlf_helper::getIdFromIndexName('issue', 'tx_dlf_structures', $this->doc->pid).' AND tx_dlf_documents.partof='.intval($this->doc->uid).')'.tx_dlf_helper::whereClause('tx_dlf_documents'),
            '',
            'tx_dlf_documents.mets_orderlabel ASC',
            ''
        );

        // Process results.
        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

            if (!empty($resArray['title'])) {
                $title = $resArray['title'];
            } else {
                $title = !empty($resArray['label']) ? $resArray['label'] : $resArray['orderlabel'];
                if (strtotime($title) !== FALSE) {
                    $title = strftime('%x', strtotime($title));
                }
            }

            $issues[] = array (
                'uid' => $resArray['uid'],
                'title' => $title,
                'year' => $resArray['year']
            );

        }

        // We need an array of issues with month number as key.
        $calendarIssuesByYear = array ();
        foreach ($issues as $issue) {
            $dateTs = strtotime($issue['year']);
            $calendarIssuesByYear[date('Y', $dateTs)][date('n', $dateTs)][date('j', $dateTs)][] = $issue;
        }

        $subPartContent = '';
        $firstMonth = 1;
        $lastMonth = 12;
        foreach ($calendarIssuesByYear as $year => $calendarIssues) {
            // show calendar from first issue in case of seasons
            if (empty($this->conf['showEmptyMonths'])) {
                $firstMonth = key($calendarIssues);
                end($calendarIssues);
                $lastMonth = key($calendarIssues);
            }
            $subPartContent .= $this->getCalendarYear($calendarIssues, $year, $firstMonth, $lastMonth);
        }

        // Link to years overview
        $linkConf = array (
            'useCacheHash' => 1,
            'parameter' => $this->conf['targetPid'],
            'additionalParams' => '&'.$this->prefixId.'[id]='.urlencode($this->doc->parentId),
        );

        $allYearsLink = $this->cObj->typoLink($this->pi_getLL('allYears', '', TRUE).' '.$this->doc->getTitle($this->doc->parentId), $linkConf);

        // Link to current year.
        $linkConf = array (
            'useCacheHash' => 1,
            'parameter' => $this->conf['targetPid'],
            'additionalParams' => '&'.$this->prefixId.'[id]='.urlencode($this->doc->uid),
        );

        $titleData = $this->doc->getTitledata();
        $title = empty($titleData['title'][0]) ? $titleData['volume'][0] : $titleData['title'][0];

        $yearLink = $this->cObj->typoLink($title, $linkConf);

        // Get subpart templates.
        $subparts['list'] = $this->cObj->getSubpart($this->template, '###ISSUELIST###');
        $subparts['singleday'] = $this->cObj->getSubpart($subparts['list'], '###SINGLEDAY###');

        // Prepare list as alternative of the calendar view.
        foreach ($this->allIssues as $dayTime => $issues) {

            $markerArrayDay['###DATE_STRING###'] = strftime('%A, %x', $dayTime);

            $markerArrayDay['###ITEMS###'] = '';

            foreach ($issues as $issue) {

                $markerArrayDay['###ITEMS###'] .= $issue;

            }

            $subPartContentList .= $this->cObj->substituteMarkerArray($subparts['singleday'], $markerArrayDay);

        }

        $this->template = $this->cObj->substituteSubpart($this->template, '###SINGLEDAY###', $subPartContentList);

        if (count($this->allIssues) < 6) {

            $listViewActive = TRUE;

        } else {

            $listViewActive = FALSE;

        }

        $markerArray = array (
            '###CALENDARVIEWACTIVE###' => $listViewActive ? '' : 'active',
            '###LISTVIEWACTIVE###' => $listViewActive ? 'active' : '',
            '###CALYEAR###' => $yearLink,
            '###CALALLYEARS###' => $allYearsLink,
            '###LABEL_CALENDAR###' => $this->pi_getLL('label.view_calendar'),
            '###LABEL_LIST_VIEW###' => $this->pi_getLL('label.view_list'),
        );

        $this->template = $this->cObj->substituteMarkerArray($this->template, $markerArray);

        return $this->cObj->substituteSubpart($this->template, '###CALMONTH###', $subPartContent);

    }

    /**
     * Get all month calendars for a given year
     *
     * @access private
     *
     * @param array $calendarIssues all issues
     * @param int $year calendar for which year
     * @param int $firstMonth - January - 1, February - 2, ..., December - 12
     * @param int $lastMonth - January - 1, February - 2, ..., December - 12
     */
    private function getCalendarYear($calendarIssues, $year, $firstMonth = 1, $lastMonth = 12) {

        // Get subpart templates.
        $subparts['list'] = $this->cObj->getSubpart($this->template, '###ISSUELIST###');

        $subparts['month'] = $this->cObj->getSubpart($this->template, '###CALMONTH###');

        $subparts['week'] = $this->cObj->getSubpart($subparts['month'], '###CALWEEK###');

        $subparts['singleday'] = $this->cObj->getSubpart($subparts['list'], '###SINGLEDAY###');

        for ($i = $firstMonth; $i <= $lastMonth; $i++) {

            $markerArray = array (
                '###DAYMON_NAME###' => strftime('%a', strtotime('last Monday')),
                '###DAYTUE_NAME###' => strftime('%a', strtotime('last Tuesday')),
                '###DAYWED_NAME###' => strftime('%a', strtotime('last Wednesday')),
                '###DAYTHU_NAME###' => strftime('%a', strtotime('last Thursday')),
                '###DAYFRI_NAME###' => strftime('%a', strtotime('last Friday')),
                '###DAYSAT_NAME###' => strftime('%a', strtotime('last Saturday')),
                '###DAYSUN_NAME###' => strftime('%a', strtotime('last Sunday')),
                '###MONTHNAME###' => strftime('%B', strtotime($year.'-'.$i.'-1')) . ' ' . $year,
                '###CALYEAR###' => ($i == $firstMonth) ? '<div class="year">' . $year . '</div>' : ''
            );

            // Reset week content of new month.
            $subWeekPartContent = '';

            $firstOfMonth = strtotime($year.'-'.$i.'-1');
            $lastOfMonth = strtotime('last day of', ($firstOfMonth));
            $firstOfMonthStart = strtotime('last Monday', $firstOfMonth);

            // There are never more than 6 weeks in a month.
            for ($j = 0; $j <= 5; $j++) {

                $firstDayOfWeek = strtotime('+ '.$j.' Week', $firstOfMonthStart);

                $weekArray = array (
                    '###DAYMON###' => '&nbsp;',
                    '###DAYTUE###' => '&nbsp;',
                    '###DAYWED###' => '&nbsp;',
                    '###DAYTHU###' => '&nbsp;',
                    '###DAYFRI###' => '&nbsp;',
                    '###DAYSAT###' => '&nbsp;',
                    '###DAYSUN###' => '&nbsp;',
                );

                // Every week has seven days. ;-)
                for ($k = 0; $k <= 6; $k++) {

                    $currentDayTime = strtotime('+ '.$k.' Day', $firstDayOfWeek);

                    if ($currentDayTime >= $firstOfMonth && $currentDayTime <= $lastOfMonth) {

                        $dayLinks = '';

                        $dayLinksText = array ();

                        $dayLinksList = '';

                        $currentMonth = date('n', $currentDayTime);

                        if (is_array($calendarIssues[$currentMonth])) {

                            foreach ($calendarIssues[$currentMonth] as $id => $day) {

                                if ($id == date('j', $currentDayTime)) {

                                    $dayLinks = $id;

                                    foreach ($day as $issue) {

                                        $dayLinkLabel = empty($issue['title']) ? strftime('%x', $currentDayTime) : $issue['title'];

                                        $linkConf = array (
                                            'useCacheHash' => 1,
                                            'parameter' => $this->conf['targetPid'],
                                            'additionalParams' => '&'.$this->prefixId.'[id]='.urlencode($issue['uid']),
                                            'ATagParams' => ' class="title"',
                                        );

                                        $dayLinksText[] = $this->cObj->typoLink($dayLinkLabel, $linkConf);

                                        // Save issues for list view.
                                        $this->allIssues[$currentDayTime][] = $this->cObj->typoLink($dayLinkLabel, $linkConf);
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

                            case '0': $weekArray['###DAYSUN###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;

                            case '1': $weekArray['###DAYMON###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;

                            case '2': $weekArray['###DAYTUE###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;

                            case '3': $weekArray['###DAYWED###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;

                            case '4': $weekArray['###DAYTHU###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;

                            case '5': $weekArray['###DAYFRI###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;

                            case '6': $weekArray['###DAYSAT###'] = ((int) $dayLinks === (int) date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
                                break;

                        }

                    }

                }

                // Fill the weeks.
                $subWeekPartContent .= $this->cObj->substituteMarkerArray($subparts['week'], $weekArray);

            }

            // Fill the month markers.
            $subPartContent .= $this->cObj->substituteMarkerArray($subparts['month'], $markerArray);

            // Fill the week markers with the week entries.
            $subPartContent = $this->cObj->substituteSubpart($subPartContent, '###CALWEEK###', $subWeekPartContent);
        }
        return $subPartContent;
    }

    /**
     * The Year Method
     *
     * @access	public
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     *
     * @return	string		The content that is displayed on the website
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
        if (!empty($this->conf['templateFile'])) {

            $this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATEYEAR###');

        } else {

            $this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/newspaper/template.tmpl'), '###TEMPLATEYEAR###');

        }

        // Get subpart templates
        $subparts['year'] = $this->cObj->getSubpart($this->template, '###LISTYEAR###');

        // get the title of the anchor file
        $titleAnchor = $this->doc->getTitle($this->doc->uid);

        // get all children of anchor. this should be the year anchor documents
        $result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'tx_dlf_documents.uid AS uid, tx_dlf_documents.mets_label AS label, tx_dlf_documents.mets_orderlabel AS orderlabel',
            'tx_dlf_documents',
            '(tx_dlf_documents.structure='.tx_dlf_helper::getIdFromIndexName('year', 'tx_dlf_structures', $this->doc->pid).' AND tx_dlf_documents.partof='.intval($this->doc->uid).')'.tx_dlf_helper::whereClause('tx_dlf_documents'),
            '',
            'tx_dlf_documents.mets_orderlabel ASC',
            ''
        );

        // Process results.
        while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
            $years[] = array (
                'title' => !empty($resArray['label']) ? $resArray['label'] : $resArray['orderlabel'],
                'uid' => $resArray['uid']
            );
        }

        $subYearPartContent = '';

        if (count($years) > 0) {

            foreach ($years as $year) {

                $linkConf = array (
                    'useCacheHash' => 1,
                    'parameter' => $this->conf['targetPid'],
                    'additionalParams' => '&'.$this->prefixId.'[id]='.urlencode($year['uid']),
                    'title' => $titleAnchor.': '.$year['title']
                );

                $yearArray = array (
                    '###YEARNAME###' => $this->cObj->typoLink($year['title'], $linkConf),
                );

                $subYearPartContent .= $this->cObj->substituteMarkerArray($subparts['year'], $yearArray);

            }
        }

        // link to years overview (should be itself here)
        $linkConf = array (
            'useCacheHash' => 1,
            'parameter' => $this->conf['targetPid'],
            'additionalParams' => '&'.$this->prefixId.'[id]='.$this->doc->uid,
        );
        $allYearsLink = $this->cObj->typoLink($this->pi_getLL('allYears', '', TRUE).' '.$this->doc->getTitle($this->doc->uid), $linkConf);

        // Fill markers.
        $markerArray = array (
            '###LABEL_CHOOSE_YEAR###' => $this->pi_getLL('label.please_choose_year'),
            '###CALALLYEARS###' => $allYearsLink
        );

        $this->template = $this->cObj->substituteMarkerArray($this->template, $markerArray);

        // fill the week markers
        return $this->cObj->substituteSubpart($this->template, '###LISTYEAR###', $subYearPartContent);

    }

}
