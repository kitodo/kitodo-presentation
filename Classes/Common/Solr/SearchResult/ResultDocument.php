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

namespace Kitodo\Dlf\Common\Solr\SearchResult;

use Solarium\QueryType\Select\Result\Document;

/**
 * ResultDocument class for the 'dlf' extension. It keeps the result of the search in the SOLR index.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class ResultDocument
{

    /**
     * @access private
     * @var string The identifier
     */
    private ?string $id;

    /**
     * @access private
     * @var string|null The unified identifier
     */
    private ?string $uid;

    /**
     * @access private
     * @var int|null The page on which result was found
     */
    private ?int $page;

    /**
     * @access private
     * @var string|null All snippets imploded to one string
     */
    private ?string $snippets;

    /**
     * @access private
     * @var string|null The thumbnail URL
     */
    private ?string $thumbnail;

    /**
     * @access private
     * @var string|null The title of the document / structure element (e.g. chapter)
     */
    private ?string $title;

    /**
     * @access private
     * @var bool It's a toplevel element?
     */
    private bool $toplevel = false;

    /**
     * @access private
     * @var string|null The structure type
     */
    private ?string $type;

    /**
     * @access private
     * @var Page[] All pages in which search phrase was found
     */
    private array $pages = [];

    /**
     * @access private
     * @var Region[] All regions in which search phrase was found
     */
    private array $regions = [];

    /**
     * @access private
     * @var Highlight[] All highlights of search phrase
     */
    private array $highlights = [];

    /**
     * @access private
     * @var array The snippets for given record
     */
    private array $snippetsForRecord = [];

    /**
     * The constructor for result.
     *
     * @access public
     *
     * @param Document $record found document record
     * @param array $highlighting array of found highlight elements
     * @param array $fields array of fields used for search
     *
     * @return void
     */
    public function __construct(Document $record, array $highlighting, array $fields)
    {
        $this->id = $record[$fields['id']];
        $this->uid = $record[$fields['uid']];
        $this->page = $record[$fields['page']];
        $this->thumbnail = $record[$fields['thumbnail']];
        $this->title = $record[$fields['title']];
        $this->toplevel = $record[$fields['toplevel']] ?? false;
        $this->type = $record[$fields['type']];

        if (!empty($highlighting[$this->id])) {
            $highlightingForRecord = $highlighting[$this->id][$fields['fulltext']];
            $this->snippetsForRecord = is_array($highlightingForRecord['snippets']) ? $highlightingForRecord['snippets'] : [];
        }

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
    public function getId(): ?string
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
    public function getUid(): ?string
    {
        return $this->uid;
    }

    /**
     * Get the result's record page.
     *
     * @access public
     *
     * @return int|null The result's record page
     */
    public function getPage(): ?int
    {
        return $this->page;
    }

    /**
     * Get all result's record snippets imploded to one string.
     *
     * @access public
     *
     * @return string|null All result's record snippets imploded to one string
     */
    public function getSnippets(): ?string
    {
        return $this->snippets;
    }

    /**
     * Get the thumbnail URL
     *
     * @access public
     *
     * @return string|null
     */
    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    /**
     * Get the title
     *
     * @access public
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Get the toplevel flag
     *
     * @access public
     *
     * @return bool
     */
    public function getToplevel(): bool
    {
        return $this->toplevel;
    }

    /**
     * Get the structure type
     *
     * @access public
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Get all result's pages which contain search phrase.
     *
     * @access public
     *
     * @return array(Page) All result's pages which contain search phrase
     */
    public function getPages(): array
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
    public function getRegions(): array
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
    public function getHighlights(): array
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
    public function getHighlightsIds(): array
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
    private function parseSnippets(): void
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
    private function parsePages(): void
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
    private function parseRegions(): void
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
    private function parseHighlights(): void
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
    private function getArrayByIndex(string $index): array
    {
        $objectArray = [];
        foreach ($this->snippetsForRecord as $snippet) {
            if (!empty($snippet[$index])) {
                array_push($objectArray, $snippet[$index]);
            }
        }
        return $objectArray;
    }
}
