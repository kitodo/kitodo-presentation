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

use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\MetsDocument;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller class for plugin 'Table Of Contents'.
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class TableOfContentsController extends AbstractController
{
    /**
     * This holds the active entries according to the currently selected page
     *
     * @var array
     * @access protected
     */
    protected $activeEntries = [];

    /**
     * This holds the list of authors for autocomplete
     *
     * @var array
     * @access protected
     */
    protected $authors = [];

    /**
     * This holds the list of titles for autocomplete
     *
     * @var array
     * @access protected
     */
    protected $titles = [];

    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        // Load current document.
        $this->loadDocument($this->requestData);
        if (
            $this->document === null
            || $this->document->getDoc() === null
        ) {
            // Quit without doing anything if required variables are not set.
            return;
        } else {
            if (!empty($this->requestData['logicalPage'])) {
                $this->requestData['page'] = $this->document->getDoc()->getPhysicalPage($this->requestData['logicalPage']);
                // The logical page parameter should not appear again
                unset($this->requestData['logicalPage']);
            }
            if ($this->document->getDoc()->tableOfContents[0]['type'] == 'collection') {
                $this->view->assign('currentList', $this->requestData['id']);
                if (isset($this->requestData['transform'])) {
                    $this->view->assign('transform', $this->requestData['transform']);
                } else {
                    $this->view->assign('transform', 'something');
                }
                $this->view->assign('type', 'collection');
                $this->view->assign('types', $this->getTypes($this->document->getDoc()->tableOfContents));
                $this->view->assign('toc', $this->makeMenuFor3DObjects());
                $this->sortAuthors();
                $this->view->assign('authors', $this->authors);
                natcasesort($this->titles);
                $this->view->assign('titles', $this->titles);
            } else {
                $this->view->assign('type', 'other');
                $this->view->assign('toc', $this->makeMenuArray());
            }
        }
    }

    /**
     * This builds a menu array for HMENU
     *
     * @access protected
     * @return array HMENU array
     */
    protected function makeMenuArray()
    {
        // Set default values for page if not set.
        // $this->requestData['page'] may be integer or string (physical structure @ID)
        if (
            (int) $this->requestData['page'] > 0
            || empty($this->requestData['page'])
        ) {
            $this->requestData['page'] = MathUtility::forceIntegerInRange((int) $this->requestData['page'], 1, $this->document->getDoc()->numPages, 1);
        } else {
            $this->requestData['page'] = array_search($this->requestData['page'], $this->document->getDoc()->physicalStructure);
        }
        $this->requestData['double'] = MathUtility::forceIntegerInRange($this->requestData['double'], 0, 1, 0);
        $menuArray = [];
        // Does the document have physical elements or is it an external file?
        if (
            !empty($this->document->getDoc()->physicalStructure)
            || !MathUtility::canBeInterpretedAsInteger($this->requestData['id'])
        ) {
            // Get all logical units the current page or track is a part of.
            if (
                !empty($this->requestData['page'])
                && !empty($this->document->getDoc()->physicalStructure)
            ) {
                $this->activeEntries = array_merge((array) $this->document->getDoc()->smLinks['p2l'][$this->document->getDoc()->physicalStructure[0]],
                    (array) $this->document->getDoc()->smLinks['p2l'][$this->document->getDoc()->physicalStructure[$this->requestData['page']]]);
                if (
                    !empty($this->requestData['double'])
                    && $this->requestData['page'] < $this->document->getDoc()->numPages
                ) {
                    $this->activeEntries = array_merge($this->activeEntries,
                        (array) $this->document->getDoc()->smLinks['p2l'][$this->document->getDoc()->physicalStructure[$this->requestData['page'] + 1]]);
                }
            }
            // Go through table of contents and create all menu entries.
            foreach ($this->document->getDoc()->tableOfContents as $entry) {
                $menuArray[] = $this->getMenuEntry($entry, true);
            }
        } else {
            // Go through table of contents and create top-level menu entries.
            foreach ($this->document->getDoc()->tableOfContents as $entry) {
                $menuArray[] = $this->getMenuEntry($entry, false);
            }
            // Build table of contents from database.
            $result = $this->documentRepository->getTableOfContentsFromDb($this->document->getUid(), $this->document->getPid(), $this->settings);

            $allResults = $result->fetchAll();

            if (count($allResults) > 0) {
                $menuArray[0]['ITEM_STATE'] = 'CURIFSUB';
                $menuArray[0]['_SUB_MENU'] = [];
                foreach ($allResults as $resArray) {
                    $entry = [
                        'label' => !empty($resArray['mets_label']) ? $resArray['mets_label'] : $resArray['title'],
                        'type' => $resArray['type'],
                        'volume' => $resArray['volume'],
                        'orderlabel' => $resArray['mets_orderlabel'],
                        'pagination' => '',
                        'targetUid' => $resArray['uid']
                    ];
                    $menuArray[0]['_SUB_MENU'][] = $this->getMenuEntry($entry, false);
                }
            }
        }
        return $menuArray;
    }

    /**
     * This builds a menu for list of 3D objects
     *
     * @access protected
     *
     * @param string $content: The PlugIn content
     * @param array $conf: The PlugIn configuration
     *
     * @return array HMENU array
     */
    protected function makeMenuFor3DObjects()
    {
        $menuArray = [];

        // Go through table of contents and create all menu entries.
        foreach ($this->document->getDoc()->tableOfContents as $entry) {
            $menuArray[] = $this->getMenuEntryWithImage($entry, true);
        }
        return $menuArray;
    }

    /**
     * This builds an array for one menu entry
     *
     * @access protected
     *
     * @param array $entry : The entry's array from \Kitodo\Dlf\Common\Doc->getLogicalStructure
     * @param bool $recursive : Whether to include the child entries
     *
     * @return array HMENU array for menu entry
     */
    protected function getMenuEntry(array $entry, $recursive = false)
    {
        $entry = $this->resolveMenuEntry($entry);

        $entryArray = [];
        // Set "title", "volume", "type" and "pagination" from $entry array.
        $entryArray['title'] = !empty($entry['label']) ? $entry['label'] : $entry['orderlabel'];
        $entryArray['volume'] = $entry['volume'];
        $entryArray['orderlabel'] = $entry['orderlabel'];
        $entryArray['type'] = Helper::translate($entry['type'], 'tx_dlf_structures', $this->settings['storagePid']);
        $entryArray['pagination'] = htmlspecialchars($entry['pagination']);
        $entryArray['_OVERRIDE_HREF'] = '';
        $entryArray['doNotLinkIt'] = 1;
        $entryArray['ITEM_STATE'] = 'NO';
        // Build menu links based on the $entry['points'] array.
        if (
            !empty($entry['points'])
            && MathUtility::canBeInterpretedAsInteger($entry['points'])
        ) {
            $entryArray['page'] = $entry['points'];

            $entryArray['doNotLinkIt'] = 0;
            if ($this->settings['basketButton']) {
                $entryArray['basketButton'] = [
                    'logId' => $entry['id'],
                    'startpage' => $entry['points']
                ];
            }
        } elseif (
            !empty($entry['points'])
            && is_string($entry['points'])
        ) {
            $entryArray['id'] = $entry['points'];
            $entryArray['page'] = 1;
            $entryArray['doNotLinkIt'] = 0;
            if ($this->settings['basketButton']) {
                $entryArray['basketButton'] = [
                    'logId' => $entry['id'],
                    'startpage' => $entry['points']
                ];
            }
        } elseif (!empty($entry['targetUid'])) {
            $entryArray['id'] = $entry['targetUid'];
            $entryArray['page'] = 1;
            $entryArray['doNotLinkIt'] = 0;
            if ($this->settings['basketButton']) {
                $entryArray['basketButton'] = [
                    'logId' => $entry['id'],
                    'startpage' => $entry['targetUid']
                ];
            }
        }
        // Set "ITEM_STATE" to "CUR" if this entry points to current page.
        if (in_array($entry['id'], $this->activeEntries)) {
            $entryArray['ITEM_STATE'] = 'CUR';
        }
        // Build sub-menu if available and called recursively.
        if (
            $recursive === true
            && !empty($entry['children'])
        ) {
            // Build sub-menu only if one of the following conditions apply:
            // 1. Current menu node is in rootline
            // 2. Current menu node points to another file
            // 3. Current menu node has no corresponding images
            if (
                $entryArray['ITEM_STATE'] == 'CUR'
                || is_string($entry['points'])
                || empty($this->document->getDoc()->smLinks['l2p'][$entry['id']])
            ) {
                $entryArray['_SUB_MENU'] = [];
                foreach ($entry['children'] as $child) {
                    // Set "ITEM_STATE" to "ACT" if this entry points to current page and has sub-entries pointing to the same page.
                    if (in_array($child['id'], $this->activeEntries)) {
                        $entryArray['ITEM_STATE'] = 'ACT';
                    }
                    $entryArray['_SUB_MENU'][] = $this->getMenuEntry($child, true);
                }
            }
            // Append "IFSUB" to "ITEM_STATE" if this entry has sub-entries.
            $entryArray['ITEM_STATE'] = ($entryArray['ITEM_STATE'] == 'NO' ? 'IFSUB' : $entryArray['ITEM_STATE'] . 'IFSUB');
        }
        return $entryArray;
    }

    /**
     * If $entry references an external METS file (as mptr),
     * try to resolve its database UID and return an updated $entry.
     *
     * This is so that when linking from a child document back to its parent,
     * that link is via UID, so that subsequently the parent's TOC is built from database.
     *
     * @param array $entry
     * @return array
     */
    protected function resolveMenuEntry($entry)
    {
        // If the menu entry points to the parent document,
        // resolve to the parent UID set on indexation.
        $doc = $this->document->getDoc();
        if (
            $doc instanceof MetsDocument
            && $entry['points'] === $doc->parentHref
            && !empty($this->document->getPartof())
        ) {
            unset($entry['points']);
            $entry['targetUid'] = $this->document->getPartof();
        }

        return $entry;
    }

    /**
     * This builds an array for one 3D menu entry
     *
     * @access protected
     *
     * @param array $entry : The entry's array from \Kitodo\Dlf\Common\Doc->getLogicalStructure
     * @param bool $recursive : Whether to include the child entries
     *
     * @return array HMENU array for 3D menu entry
     */
    protected function getMenuEntryWithImage(array $entry, $recursive = false)
    {
        $entryArray = [];

        // don't filter if the entry type is collection
        if ($entry['type'] != 'collection') {
            if (!$this->isFound($entry)) {
                return $entryArray;
            }
        }

        // Set "title", "volume", "type" and "pagination" from $entry array.
        $entryArray['title'] = !empty($entry['label']) ? $entry['label'] : $entry['orderlabel'];
        $entryArray['orderlabel'] = $entry['orderlabel'];
        $entryArray['type'] = Helper::translate($entry['type'], 'tx_dlf_structures', $this->settings['storagePid']);
        $entryArray['pagination'] = htmlspecialchars($entry['pagination']);
        $entryArray['doNotLinkIt'] = 1;
        $entryArray['ITEM_STATE'] = 'HEADER';

        if ($entry['children'] === null) {
            $entryArray['description'] = $entry['description'];
            $id = $this->document->getDoc()->smLinks['l2p'][$entry['id']][0];
            $entryArray['image'] = $this->document->getDoc()->getFileLocation($this->document->getDoc()->physicalStructureInfo[$id]['files']['THUMBS']);
            $entryArray['doNotLinkIt'] = 0;
            // index.php?tx_dlf%5Bid%5D=http%3A%2F%2Flink_to_METS_file.xml
            $entryArray['urlId'] = GeneralUtility::_GET('id');
            $entryArray['urlXml'] = $entry['points'];
            $entryArray['ITEM_STATE'] = 'ITEM';

            $this->addAuthorToAutocomplete($entryArray['author']);
            $this->addTitleToAutocomplete($entryArray['title']);
        }

        // Build sub-menu if available and called recursively.
        if (
            $recursive == true
            && !empty($entry['children'])
        ) {
            // Build sub-menu only if one of the following conditions apply:
            // 1. Current menu node points to another file
            // 2. Current menu node has no corresponding images
            if (
                is_string($entry['points'])
                || empty($this->document->getDoc()->smLinks['l2p'][$entry['id']])
            ) {
                $entryArray['_SUB_MENU'] = [];
                foreach ($entry['children'] as $child) {
                    $menuEntry = $this->getMenuEntryWithImage($child);
                    if (!empty($menuEntry)) {
                        $entryArray['_SUB_MENU'][] = $menuEntry;
                    }
                }
            }
        }
        return $entryArray;
    }

    /**
     * Check or possible combinations of requested params.
     *
     * @param array $entry : The entry's array from \Kitodo\Dlf\Common\Doc->getLogicalStructure
     *
     * @return bool true if found, false otherwise
     */
    private function isFound($entry) {
        if (!empty($this->requestData['title'] && !empty($this->requestData['types']) && !empty($this->requestData['author']))) {
            return $this->isTitleFound($entry) && $this->isTypeFound($entry) && $this->isAuthorFound($entry);
        } else if (!empty($this->requestData['title']) && !empty($this->requestData['author'])) {
            return $this->isTitleFound($entry) && $this->isAuthorFound($entry);
        } else if (!empty($this->requestData['title']) && !empty($this->requestData['types'])) {
            return $this->isTitleFound($entry) && $this->isTypeFound($entry);
        } else if (!empty($this->requestData['author']) && !empty($this->requestData['types'])) {
            return $this->isAuthorFound($entry) && $this->isTypeFound($entry);
        } else if (!empty($this->requestData['title'])) {
            return $this->isTitleFound($entry);
        } else if (!empty($this->requestData['types'])) {
            return $this->isTypeFound($entry);
        } else if (!empty($this->requestData['author'])) {
            return $this->isAuthorFound($entry);
        } else {
            // no parameters so entry is matching
            return true;
        }
    }

    /**
     * Check if author is found.
     *
     * @param array $entry : The entry's array from \Kitodo\Dlf\Common\Doc->getLogicalStructure
     *
     * @return bool true if found, false otherwise
     */
    private function isAuthorFound($entry) {
        $value = strtolower($entry['author']);
        $author = strtolower($this->requestData['author']);
        return str_contains($value, $author);
    }

    /**
     * Check if title is found.
     *
     * @param array $entry : The entry's array from \Kitodo\Dlf\Common\Doc->getLogicalStructure
     *
     * @return bool true if found, false otherwise
     */
    private function isTitleFound($entry) {
        $value = strtolower($entry['label']);
        $title = strtolower($this->requestData['title']);
        return str_contains($value, $title);
    }

    /**
     * Check if type is found.
     *
     * @param array $entry : The entry's array from \Kitodo\Dlf\Common\Doc->getLogicalStructure
     *
     * @return bool true if found, false otherwise
     */
    private function isTypeFound($entry) {
        return str_contains($entry['identifier'], $this->requestData['types']);
    }

    /**
     * Add author to the authors autocomplete array.
     *
     * @param string $author : author to be inserted to the authors autocomplete array
     *
     * @return void
     */
    private function addAuthorToAutocomplete($author) {
        if ($author != NULL && !(in_array($author, $this->authors))) {
            // additional check if actually not more than 1 author is included
            if (strpos($author, ',') !== false) {
                $authors = explode(",", $author);
                foreach ($authors as $value) {
                    if (!(in_array(trim($value), $this->authors))) {
                        $this->authors[] = trim($value);
                    }
                }
            } else {
                $this->authors[] = $author;
            }
        }
    }

    /**
     * Sort authors by surname - second part of the string
     *
     * @return void
     */
    private function sortAuthors() {
        usort($this->authors, function($firstAuthor, $secondAuthor) {
            $firstAuthor = substr(strrchr($firstAuthor, ' '), 1);
            $secondAuthor = substr(strrchr($secondAuthor, ' '), 1);
            return strcmp($firstAuthor, $secondAuthor);
        });
    }

    /**
     * Add title to the titles autocomplete array.
     *
     * @param string $title : title to be inserted to the titles autocomplete array
     *
     * @return void
     */
    private function addTitleToAutocomplete($title) {
        if (!(in_array($title, $this->titles)) && $title != NULL) {
            $this->titles[] = $title;
        }
    }

    /**
     * Get all types.
     *
     * @param array $entry : The entry's array from \Kitodo\Dlf\Common\Doc->getLogicalStructure
     *
     * @return array of object types
     */
    private function getTypes($entry) {
        $types = [];
        $index = 0;

        if (!empty($entry[0]['children'])) {
            foreach ($entry[0]['children'] as $child) {
                $type = $this->getType($child);
                if (!(in_array($type, $types)) && $type != NULL) {
                    $types[$index] = $type;
                    $index++;
                }
            }
        }
        natcasesort($types);
        return $types;
    }

    /**
     * Get single type for given entry.
     *
     * @param array $entry : The entry's array from \Kitodo\Dlf\Common\Doc->getLogicalStructure
     *
     * @return string type name without number
     */
    private function getType($entry) {
        $type = $entry['identifier'];
        if (!empty($type)) {
            return strtok($type, ',');
        }
        return $type;
    }
}
