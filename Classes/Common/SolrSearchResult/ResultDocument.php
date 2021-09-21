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

namespace Kitodo\Dlf\Common\SolrSearchResult;

use Kitodo\Dlf\Common\SolrSearchResult\Highlight;
use Kitodo\Dlf\Common\SolrSearchResult\Page;
use Kitodo\Dlf\Common\SolrSearchResult\Region;

/**
 * ResultDocument class for the 'dlf' extension. It keeps te result of the search in the SOLR index.
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ResultDocument
{

    /**
     * The identifier
     *
     * @var string
     * @access private
     */
    private $id;

    /**
     * The unified identifier
     *
     * @var string|null
     * @access private
     */
    private $uid;

    /**
     * The page on which result was found
     *
     * @var int
     * @access private
     */
    private $page;

    /**
     * All snippets imploded to one string
     *
     * @var string
     * @access private
     */
    private $snippets;

    /**
     * All pages in which search phrase was found
     *
     * @var array(Page)
     * @access private
     */
    private $pages = [];

    /**
     * All regions in which search phrase was found
     *
     * @var array(Region)
     * @access private
     */
    private $regions = [];

    /**
     * All highlights of search phrase
     *
     * @var array(Highlight)
     * @access private
     */
    private $highlights = [];

    /**
     * The snippets for given record
     *
     * @var array
     * @access private
     */
    private $snippetsForRecord = [];

    /**
     * The constructor for result.
     *
     * @access public
     *
     * @param array $record: Array of found document record
     * @param array $highlighting: Array of found highlight elements
     * @param array $fields: Array of fields used for search
     *
     * @return void
     */
    public function __construct($record, $highlighting, $fields)
    {
        $this->id = $record[$fields['id']];
        $this->uid = $record[$fields['uid']];
        $this->page = $record[$fields['page']];

        $highlightingForRecord = $highlighting[$record[$fields['id']]][$fields['fulltext']];
        $this->snippetsForRecord = is_array($highlightingForRecord['snippets']) ? $highlightingForRecord['snippets'] : [];

        $this->parseSnippets();
        $this->parsePages();
        $this->parseRegions();
        $this->parseHighlights();
    }

    /**
     * Get the result's record identifier.
     *
     * @access public
     *
     * @return string The result's record identifier
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the result's record unified identifier.
     *
     * @access public
     *
     * @return string|null The result's record unified identifier
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Get the result's record page.
     *
     * @access public
     *
     * @return int The result's record page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Get all result's record snippets imploded to one string.
     *
     * @access public
     *
     * @return string All result's record snippets imploded to one string
     */
    public function getSnippets()
    {
        return $this->snippets;
    }

    /**
     * Get all result's pages which contain search phrase.
     *
     * @access public
     *
     * @return array(Page) All result's pages which contain search phrase
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Get all result's regions which contain search phrase.
     *
     * @access public
     *
     * @return array(Region) All result's regions which contain search phrase
     */

    public function getRegions()
    {
        return $this->regions;
    }

    /**
     * Get all result's highlights of search phrase.
     *
     * @access public
     *
     * @return array(Highlight) All result's highlights of search phrase
     */
    public function getHighlights()
    {
        return $this->highlights;
    }

    /**
     * Get all result's highlights' ids of search phrase.
     *
     * @access public
     *
     * @return array(string) All result's highlights of search phrase
     */
    public function getHighlightsIds()
    {
        $highlightsIds = [];
        foreach ($this->highlights as $highlight) {
            array_push($highlightsIds, $highlight->getId());
        }
        return $highlightsIds;
    }

    /**
     * Parse snippets array to string for displaying purpose.
     * Snippets are stored in 'text' field of 'snippets' object.
     *
     * @access private
     *
     * @return void
     */
    private function parseSnippets()
    {
        $snippetArray = $this->getArrayByIndex('text');

        $this->snippets = !empty($snippetArray) ? implode(' [...] ', $snippetArray) : '';
    }

    /**
     * Parse pages array to array of Page objects.
     * Pages are stored in 'pages' field of 'snippets' object.
     *
     * @access private
     *
     * @return void
     */
    private function parsePages()
    {
        $pageArray = $this->getArrayByIndex('pages');

        $i = 0;
        foreach ($pageArray as $pages) {
            foreach ($pages as $page) {
                array_push($this->pages, new Page($i, $page));
                $i++;
            }
        }
    }

    /**
     * Parse regions array to array of Region objects.
     * Regions are stored in 'regions' field of 'snippets' object.
     *
     * @access private
     *
     * @return void
     */
    private function parseRegions()
    {
        $regionArray = $this->getArrayByIndex('regions');

        $i = 0;
        foreach ($regionArray as $regions) {
            foreach ($regions as $region) {
                array_push($this->regions, new Region($i, $region));
                $i++;
            }
        }
    }

    /**
     * Parse highlights array to array of Highlight objects.
     * Highlights are stored in 'highlights' field of 'snippets' object.
     *
     * @access private
     *
     * @return void
     */
    private function parseHighlights()
    {
        $highlightArray = $this->getArrayByIndex('highlights');

        foreach ($highlightArray as $highlights) {
            foreach ($highlights as $highlight) {
                foreach ($highlight as $hl) {
                    array_push($this->highlights, new Highlight($hl));
                }
            }
        }
    }

    /**
     * Get array for given index.
     *
     * @access private
     *
     * @param string $index: Name of field for which array is going be created
     *
     * @return array
     */
    private function getArrayByIndex($index)
    {
        $objectArray = [];
        foreach ($this->snippetsForRecord as $snippet) {
            array_push($objectArray, $snippet[$index]);
        }
        return $objectArray;
    }
}
