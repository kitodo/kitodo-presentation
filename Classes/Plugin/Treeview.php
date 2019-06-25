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

use Exception;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;

/**
 * Plugin 'Basket' for the 'dlf' extension
 *
 * @author Christopher Timm <timm@effective-webwork.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Treeview extends \Kitodo\Dlf\Common\AbstractPlugin {
    public $scriptRelPath = 'Classes/Plugin/Treeview.php';

    /**
     * @param $solr
     * @param $query
     * @param $level
     * @param $collection
     * @return array|string
     * @throws Exception
     * This method preprocesses a hierachical structure based on signatures and returns the data as json
     */
    protected function prepareSignatureData($solr, $query, $level, $collection) {

        $query = Solr::escapeQueryKeepField($query, $this->conf['pages']);

        if ($query == null) {
            throw new Exception('Missing query argument');
            return '';
        }

        $solrField = $this->conf['field'];
        $explodeChar = $this->conf['explode'];

        $params['sort'] = array($solrField => 'asc');

        $solr->params = $params;

        $results = $solr->search_raw($solrField . ':' . $query . '');

        $levelArray = array();
        $titles = array();

        foreach ($results as $document) {

            $exploded = explode($explodeChar, $document->{$solrField});

            $concatLevels = "";
            for ($i = 0; $i <= $level; $i++) {
                $concatLevels .= $exploded[$i] . $explodeChar;
            }

            $levelArray[$document->purl] = $concatLevels;
            $titles[$concatLevels] = $document->title;

        }
        $levelArray = array_unique($levelArray);

        uasort($levelArray, 'strnatcmp');

        foreach ($levelArray as $key => $item) {

            if ($item !== null) {
                $subQuery = $item;
                $title = $titles[$subQuery];

                $subResults = $solr->search_raw($solrField . ':"' . $subQuery . '"');

                $counter = count($subResults);
                if ($counter > 1) {
                    $childrenAvailable = true;
                    $linkAttrArray = array("href" => "");
                } else {
                    $childrenAvailable = false;
                    $linkAttrArray = array("href" => $key);
                }

                if ($level == 0) {
                    if (strpos($titles[$subQuery], $explodeChar)) {
                        if ($this->conf['removeFromTitle']) {
                            // this removes the last part of the signature from the title

                            $endOfTitle = strrchr($titles[$subQuery], $this->conf['removeFromTitle']);
                            $endOfShelfmark = strstr($endOfTitle, $explodeChar);

                            $title = str_replace($endOfShelfmark, '', $titles[$subQuery]);
                        }
                    }
                } else {
                    $title = trim($item, $explodeChar);
                }

                $newLevelArray[] = array("id" => $level . '#' . trim($subQuery), "text" => $title, "children" => $childrenAvailable, "a_attr" => $linkAttrArray, "icon" => false);
            }
        }

        return $newLevelArray;

    }

    /**
     * @param $solr
     * @param $query
     * @param $level
     * @param $collection
     * @return array
     *  This method preprocesses groups by years based on the configuration
     */
    protected function prepareGroupData($solr, $query, $level, $collection) {

        // Add filter query for collection restrictions.
        if ($this->conf['dataCollections']) {

            $collIds = explode(',', $this->conf['dataCollections']);
            $collIndexNames = array ();

            foreach ($collIds as $collId) {

                $collIndexNames[] = Solr::escapeQuery(Helper::getIndexNameFromUid(intval($collId), 'tx_dlf_collections', $this->conf['pages']));
                $collectionNames[] = Helper::getIndexNameFromUid(intval($collId), 'tx_dlf_collections', $this->conf['pages']);
            }

            // Last value is fake and used for distinction in $this->addCurrentCollection()
            $params['filterquery'][]['query'] = 'collection_faceting:("'.implode('" OR "', $collIndexNames).'" OR "FakeValueForDistinction")';

        }
        $params['filterquery'][]['query'] = 'NOT type_sorting:"Periodical"';

        $solr->params = $params;

        if ($level == 0) {
            foreach ($collectionNames as $collectionName) {
                $newLevelArray[] = array("id" => "0##" . $collectionName, "text" => $collectionName, "children" => true, "a_attr" => array("href" => ""), "icon" => false);
            }
        } else {

            $selectParams = array(
                'rows' => 0
            );

            $querySelect = $solr->service->createSelect($selectParams);

            $groupComponent = $querySelect->getGrouping();
            $groupComponent->addField('year_sorting');

            $field = $this->conf['field'];
            $from = $this->conf['yearFrom'];
            $till = $this->conf['yearTill'];
            $gap = $this->conf['yearRange'];

            if ($level == 1) {

                for ($i = $from; $i <= $till; $i = $i + $gap) {
                    $groupComponent->addQuery($field . ':[' . ($i - $gap) . ' TO ' . ($i - 1) . ']');
                }
            } else {
                $groupComponent->addQuery($query);
            }

            $resultset = $solr->service->select($querySelect);
            $groups = $resultset->getGrouping();

            foreach ($groups as $groupKey => $group) {
                $regex = $this->conf['regexOutput'];

                preg_match($regex, $groupKey, $matches);

                if ($level == 1) {

                    unset($matches[0]);
                    $text = implode(' ' . $this->conf['regexOutputDelimiter'] . ' ', $matches);

                    if (!empty($text)) {

                        $newLevelArray[] = array("id" => "1#" . $groupKey . "#".$groupKey,
                            "text" => $text,
                            "children" => true,
                            "a_attr" => array("href" => ""),
                            "icon" => false);
                    }

                } else {

                    foreach ($group as $document) {

                        $outputField = $this->conf['leafFieldOutput'];
                        $text = $document->$outputField;

                        $newLevelArray[] = array("id" => "2#" . $document->record_id . "#".$groupKey,
                            "text" => $text,
                            "children" => false,
                            "a_attr" => array("href" => $document->purl),
                            "icon" => false);
                    }
                }

            }
        }

        return $newLevelArray;

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
    public function main($content, $conf) {

        $this->init($conf);

        $apikey = $this->piVars['apikey'];

        if ($apikey != $this->conf['apikey']) {
            return '';
        }

        $query = $this->piVars['query'];
        $level = $this->piVars['level'];
        $collection = $this->piVars['collection'];

        // Instantiate search object.
        $solr = Solr::getInstance($this->conf['solrcore']);

        if (!$solr->ready) {

            if (TYPO3_DLOG) {

                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('[tx_dlf_search->main('.$content.', [data])] Apache Solr not available', $this->extKey, SYSLOG_SEVERITY_ERROR, $conf);

            }

            return $content;

        }

        // Add filter query for collection restrictions.
        if ($this->conf['collections']) {

            $collIds = explode(',', $this->conf['collections']);

            $collIndexNames = array ();

            foreach ($collIds as $collId) {

                $collIndexNames[] = Solr::escapeQuery(Helper::getIndexNameFromUid(intval($collId), 'tx_dlf_collections', $this->conf['pages']));

            }

            // Last value is fake and used for distinction in $this->addCurrentCollection()
            $params['filterquery'][]['query'] = 'collection_faceting:("'.implode('" OR "', $collIndexNames).'" OR "FakeValueForDistinction")';

        }

        if ($this->conf['dataFormatter']) {
            if ($this->conf['dataFormatter'] == 'signature') {
                $outputArray = $this->prepareSignatureData($solr, $query, $level, $collection);
            }
            if ($this->conf['dataFormatter'] == 'collection') {
                $outputArray = $this->prepareGroupData($solr, $query, $level, $collection);
            }
        }

        $content = json_encode($outputArray);

        // Send headers.
        header('HTTP/1.1 200 OK');
        header('Cache-Control: no-cache');
        header('Content-Length: '.strlen($content));
        header('Content-Type: application/json; charset=utf-8');
        header('Date: '.date('r', $GLOBALS['EXEC_TIME']));
        header('Expires: '.date('r', $GLOBALS['EXEC_TIME'] + $this->conf['expired']));
        header('Access-Control-Allow-Origin: *');

        echo $content;

        // Flush output buffer and end script processing.
        ob_end_flush();

        exit;

    }
}
