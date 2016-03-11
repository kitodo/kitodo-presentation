<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Plugin 'DFG-Viewer: Newspaper Calendar' for the 'dfgviewer' extension.
 *
 * @author	Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @copyright	Copyright (c) 2016, Alexander Bigga, SLUB Dresden
 * @package	TYPO3
 * @subpackage	tx_dlf
 * @access	public
 */
class tx_dlf_newspaper extends tx_dlf_plugin {

	public $extKey = 'dlf';

	public $scriptRelPath = 'plugins/newspaper-calendar/class.tx_dlf_newspaper.php';

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

		// nothing to do here

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

		// get all children of year anchor
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_documents.uid AS uid, tx_dlf_documents.title AS title, tx_dlf_documents.year AS year',
			'tx_dlf_documents',
			'(tx_dlf_documents.structure='.tx_dlf_helper::getIdFromIndexName('issue', 'tx_dlf_structures', $this->doc->pid).' AND tx_dlf_documents.partof='.intval($this->doc->uid).')'.tx_dlf_helper::whereClause('tx_dlf_documents'),
			'',
			'title ASC',
			''
		);

		// Process results.
		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

			$issues[] = array (
				'uid' => $resArray['uid'],
				'title' => $resArray['title'],
				'year' => $resArray['year']
			);

		}

		// 	we need an array of issues with month number as key
		foreach ($issues as $issue) {

			$calendarIssues[date('n', strtotime($issue['year']))][date('j', strtotime($issue['year']))][] = $issue;

		}

		$allIssuesCount = count($issues);

		// Load template file.
		if (!empty($this->conf['templateFile'])) {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['templateFile']), '###TEMPLATECALENDAR###');

		} else {

			$this->template = $this->cObj->getSubpart($this->cObj->fileResource('EXT:dlf/plugins/newspaper/template.tmpl'), '###TEMPLATECALENDAR###');

		}

		// Get subpart templates
		$subparts['template'] = $this->template;

		$subparts['month'] = $this->cObj->getSubpart($subparts['template'], '###CALMONTH###');

		$subparts['singleissue'] = $this->cObj->getSubpart($subparts['issuelist'], '###SINGLEISSUE###');

		$year = date('Y', strtotime($issues[0]['year']));

		$subPartContent = '';

		for ($i = 0; $i <= 11; $i++) {

			$markerArray = array (
				'###DAYMON_NAME###' => strftime('%a', strtotime('last Monday')),
				'###DAYTUE_NAME###' => strftime('%a', strtotime('last Tuesday')),
				'###DAYWED_NAME###' => strftime('%a', strtotime('last Wednesday')),
				'###DAYTHU_NAME###' => strftime('%a', strtotime('last Thursday')),
				'###DAYFRI_NAME###' => strftime('%a', strtotime('last Friday')),
				'###DAYSAT_NAME###' => strftime('%a', strtotime('last Saturday')),
				'###DAYSUN_NAME###' => strftime('%a', strtotime('last Sunday')),
				'###MONTHNAME###' 	=> strftime('%B', strtotime($year . '-' . ($i + 1) . '-1'))
			);

			// Get week subpart template
			$subWeekTemplate = $this->cObj->getSubpart($subparts['month'], '###CALWEEK###');
			$subWeekPartContent = '';

			$firstOfMonth = strtotime($year . '-' . ($i + 1) . '-1');
			$lastOfMonth = strtotime('last day of', ($firstOfMonth));
			$firstOfMonthStart = strtotime('last Monday', $firstOfMonth);

			// max 6 calendar weeks in a month
			for ($j = 0; $j <= 5; $j++) {

				$firstDayOfWeek = strtotime('+ ' . $j . ' Week', $firstOfMonthStart);

				$weekArray = array(
					'###DAYMON###' => '&nbsp;',
					'###DAYTUE###' => '&nbsp;',
					'###DAYWED###' => '&nbsp;',
					'###DAYTHU###' => '&nbsp;',
					'###DAYFRI###' => '&nbsp;',
					'###DAYSAT###' => '&nbsp;',
					'###DAYSUN###' => '&nbsp;',
				);

				// 7 days per week ;-)
				for ($k = 0; $k <= 6; $k++) {

					$currentDayTime = strtotime('+ '.$k.' Day', $firstDayOfWeek);

					if ( $currentDayTime >= $firstOfMonth && $currentDayTime <= $lastOfMonth ) {

						$dayLinks = '';
						$dayLinksText = '';

						$currentMonth = date('n', $currentDayTime);

						if (is_array($calendarIssues[$currentMonth])) {

							foreach($calendarIssues[$currentMonth] as $id => $day) {

								if ($id == date('j', $currentDayTime)) {

									$dayLinks = $id;

									foreach($day as $id => $issue) {

										$linkConf = array (
											'useCacheHash' => 1,
											'parameter' => $this->conf['targetPid'],
											'additionalParams' => '&' . $this->prefixId . '[id]=' . urlencode($issue['uid']) . '&' . $this->prefixId . '[page]=1',
											'ATagParams' => 'id=' . $issue['id'],
										);
										$dayLinksText[] = $this->cObj->typoLink($id, $linkConf);

										$allIssues[] = array(strftime('%A, %x', $currentDayTime), $this->cObj->typoLink($id, $linkConf));
									}
								}

							}

							// use title attribute for tooltip
							if (is_array($dayLinksText)) {
								$dayLinksList = '<ul>';
								foreach ($dayLinksText as $link) {
									$dayLinksList .= '<li>'.$link.'</li>';
								}
								$dayLinksList .= '</ul>';
							}

							$dayLinkDiv = '<div class="issues"><h4>' . strftime('%d', $currentDayTime) . '</h4><div>'.$dayLinksList.'</div></div>';
						}

						switch (strftime('%u', strtotime('+ '.$k.' Day', $firstDayOfWeek))) {
							case '1': $weekArray['###DAYMON###'] = ((int)$dayLinks === (int)date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
								break;
							case '2': $weekArray['###DAYTUE###'] = ((int)$dayLinks === (int)date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
								break;
							case '3': $weekArray['###DAYWED###'] = ((int)$dayLinks === (int)date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
								break;
							case '4': $weekArray['###DAYTHU###'] = ((int)$dayLinks === (int)date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
								break;
							case '5': $weekArray['###DAYFRI###'] = ((int)$dayLinks === (int)date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
								break;
							case '6': $weekArray['###DAYSAT###'] = ((int)$dayLinks === (int)date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
								break;
							case '7': $weekArray['###DAYSUN###'] = ((int)$dayLinks === (int)date('j', $currentDayTime)) ? $dayLinkDiv : strftime('%d', $currentDayTime);
								break;
						}
					}
				}
				// fill the weeks
				$subWeekPartContent .= $this->cObj->substituteMarkerArray($subWeekTemplate, $weekArray);
			}

			// fill the month markers
			$subPartContent .= $this->cObj->substituteMarkerArray($subparts['month'], $markerArray);

			// fill the week markers
			$subPartContent = $this->cObj->substituteSubpart($subPartContent, '###CALWEEK###', $subWeekPartContent);
		}

		// link to years overview
		$linkConf = array (
			'useCacheHash' => 1,
			'parameter' => $this->conf['targetPid'],
			'additionalParams' => '&' . $this->prefixId . '[id]=' . $this->doc->parentId,
		);
		$allYearsLink = $this->cObj->typoLink($this->pi_getLL('allYears', '', TRUE) . ' ' .$this->doc->getTitle($this->doc->parentId), $linkConf);

		// link to this year itself
		$linkConf = array (
			'useCacheHash' => 1,
			'parameter' => $this->conf['targetPid'],
			'additionalParams' => '&' . $this->prefixId . '[id]=' . $this->doc->uid,
		);
		$yearLink = $this->cObj->typoLink($year, $linkConf);

		// prepare list as alternative of the calendar view
		$issueListTemplate = $this->cObj->getSubpart($subparts['template'], '###ISSUELIST###');

		$subparts['singleissue'] = $this->cObj->getSubpart($issueListTemplate, '###SINGLEISSUE###');

		$allDaysList = array();

		foreach($allIssues as $id => $issue) {

			// only add date output, if not already done (multiple issues per day)
			if (! in_array($issue[0], $allDaysList)) {

				$allDaysList[] = $issue[0];

				$subPartContentList .= $issue[0];

			}

			$subPartContentList .= $this->cObj->substituteMarker($subparts['singleissue'], '###ITEM###', $issue[1]);

		}

		$issueListTemplate = $this->cObj->substituteSubpart($issueListTemplate, '###SINGLEISSUE###', $subPartContentList);

		$this->template = $this->cObj->substituteSubpart($this->template, '###ISSUELIST###', $issueListTemplate);

		if ($allIssuesCount < 6) {

			$listViewActive = 'active';

		} else {

			$calendarViewActive = 'active';

		}

		$markerArray = array (
			'###CALENDARVIEWACTIVE###' => $calendarViewActive,
			'###LISTVIEWACTIVE###' => $listViewActive,
			'###CALYEAR###' => $yearLink,
			'###CALALLYEARS###' => $allYearsLink
		);

		$this->template = $this->cObj->substituteMarkerArray($this->template, $markerArray);

		return $this->cObj->substituteSubpart($this->template, '###CALMONTH###', $subPartContent);

	}

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
		$subparts['template'] = $this->template;

		$subparts['year'] = $this->cObj->getSubpart($subparts['template'], '###LISTYEAR###');

		// get the title of the anchor file
		$titleAnchor = $this->doc->getTitle($this->doc->uid);

		// get all children of anchor. this should be the year anchor documents
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'tx_dlf_documents.uid AS uid, tx_dlf_documents.title AS title',
			'tx_dlf_documents',
			'(tx_dlf_documents.structure='.tx_dlf_helper::getIdFromIndexName('year', 'tx_dlf_structures', $this->doc->pid).' AND tx_dlf_documents.partof='.intval($this->doc->uid).')'.tx_dlf_helper::whereClause('tx_dlf_documents'),
			'',
			'title ASC',
			''
		);

		// Process results.
		while ($resArray = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {

			$years[] = array (
				'title' => $resArray['title'],
				'uid' => $resArray['uid']
			);

		}

		$subYearPartContent = '';

		if (count($years) > 0) {

			foreach ($years as $id => $year) {

				$linkConf = array(
					'useCacheHash' => 1,
					'parameter' => $this->conf['targetPid'],
					'additionalParams' => '&' . $this->prefixId . '[id]=' . urlencode($year['uid']),
					'title' => $titleAnchor . ': ' . $year['title']
				);

				$yearArray = array(
					'###YEARNAME###' => $this->cObj->typoLink($year['title'], $linkConf),
				);

				$subYearPartContent .= $this->cObj->substituteMarkerArray($subparts['year'], $yearArray);

			}
		}

		// fill the week markers
		return $this->cObj->substituteSubpart($subparts['template'], '###LISTYEAR###', $subYearPartContent);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/newspaper-calendar/class.tx_dlf_newspaper-calendar.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dlf/plugins/newspaper-calendar/class.tx_dlf_newspaper-calendar.php']);
}
