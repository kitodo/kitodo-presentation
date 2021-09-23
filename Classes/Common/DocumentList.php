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

namespace Kitodo\Dlf\Common;

use Kitodo\Dlf\Common\SolrSearchResult\ResultDocument;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Solarium\QueryType\Select\Result\Result;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * List class for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Frank Ulrich Weber <fuw@zeutschel.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 * @property array $metadata This holds the list's metadata
 */
class DocumentList implements \ArrayAccess, \Countable, \Iterator, LoggerAwareInterface, SingletonInterface
{
    use LoggerAwareTrait;

    /**
     * This holds the number of documents in the list
     * @see \Countable
     *
     * @var int
     * @access protected
     */
    protected $count = 0;

    /**
     * This holds the list entries in sorted order
     * @see \ArrayAccess
     *
     * @var array
     * @access protected
     */
    protected $elements = [];

    /**
     * This holds the list's metadata
     *
     * @var array
     * @access protected
     */
    protected $metadata = [];

    /**
     * This holds the current list position
     * @see \Iterator
     *
     * @var int
     * @access protected
     */
    protected $position = 0;

    /**
     * This holds the full records of already processed list elements
     *
     * @var array
     * @access protected
     */
    protected $records = [];

    /**
     * Instance of \Kitodo\Dlf\Common\Solr class
     *
     * @var \Kitodo\Dlf\Common\Solr
     * @access protected
     */
    protected $solr;

    /**
     * This holds the Solr metadata configuration
     *
     * @var array
     * @access protected
     */
    protected $solrConfig = [];

    /**
     * This adds an array of elements at the given position to the list
     *
     * @access public
     *
     * @param array $elements: Array of elements to add to list
     * @param int $position: Numeric position for including
     *
     * @return void
     */
    public function add(array $elements, $position = -1)
    {
        $position = MathUtility::forceIntegerInRange($position, 0, $this->count, $this->count);
        if (!empty($elements)) {
            array_splice($this->elements, $position, 0, $elements);
            $this->count = count($this->elements);
        }
    }

    /**
     * This counts the elements
     * @see \Countable::count()
     *
     * @access public
     *
     * @return int The number of elements in the list
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * This returns the current element
     * @see \Iterator::current()
     *
     * @access public
     *
     * @return array The current element
     */
    public function current()
    {
        if ($this->valid()) {
            return $this->getRecord($this->elements[$this->position]);
        } else {
            $this->logger->notice('Invalid position "' . $this->position . '" for list element');
            return;
        }
    }

    /**
     * This returns the full record of any list element
     *
     * @access protected
     *
     * @param mixed $element: The list element
     *
     * @return mixed The element's full record
     */
    protected function getRecord($element)
    {
        if (
            is_array($element)
            && array_keys($element) == ['u', 'h', 's', 'p']
        ) {
            // Return already processed record if possible.
            if (!empty($this->records[$element['u']])) {
                return $this->records[$element['u']];
            }
            $record = [
                'uid' => $element['u'],
                'page' => 1,
                'preview' => '',
                'subparts' => $element['p']
            ];
            // Check if it's a list of database records or Solr documents.
            if (
                !empty($this->metadata['options']['source'])
                && $this->metadata['options']['source'] == 'collection'
            ) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('tx_dlf_documents');

                // Get document's thumbnail and metadata from database.
                $result = $queryBuilder
                    ->select(
                        'tx_dlf_documents.uid AS uid',
                        'tx_dlf_documents.thumbnail AS thumbnail',
                        'tx_dlf_documents.metadata AS metadata'
                    )
                    ->from('tx_dlf_documents')
                    ->where(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->eq('tx_dlf_documents.uid', intval($record['uid'])),
                            $queryBuilder->expr()->eq('tx_dlf_documents.partof', intval($record['uid']))
                        ),
                        Helper::whereExpression('tx_dlf_documents')
                    )
                    ->execute();

                // Process results.
                while ($resArray = $result->fetch()) {
                    // Prepare document's metadata.
                    $metadata = unserialize($resArray['metadata']);
                    if (
                        !empty($metadata['type'][0])
                        && MathUtility::canBeInterpretedAsInteger($metadata['type'][0])
                    ) {
                        $metadata['type'][0] = Helper::getIndexNameFromUid($metadata['type'][0], 'tx_dlf_structures', $this->metadata['options']['pid']);
                    }
                    if (
                        !empty($metadata['owner'][0])
                        && MathUtility::canBeInterpretedAsInteger($metadata['owner'][0])
                    ) {
                        $metadata['owner'][0] = Helper::getIndexNameFromUid($metadata['owner'][0], 'tx_dlf_libraries', $this->metadata['options']['pid']);
                    }
                    if (
                        !empty($metadata['collection'])
                        && is_array($metadata['collection'])
                    ) {
                        foreach ($metadata['collection'] as $i => $collection) {
                            if (MathUtility::canBeInterpretedAsInteger($collection)) {
                                $metadata['collection'][$i] = Helper::getIndexNameFromUid($metadata['collection'][$i], 'tx_dlf_collections', $this->metadata['options']['pid']);
                            }
                        }
                    }
                    // Add metadata to list element.
                    if ($resArray['uid'] == $record['uid']) {
                        $record['thumbnail'] = $resArray['thumbnail'];
                        $record['metadata'] = $metadata;
                    } elseif (($key = array_search(['u' => $resArray['uid']], $record['subparts'], true)) !== false) {
                        $record['subparts'][$key] = [
                            'uid' => $resArray['uid'],
                            'page' => 1,
                            'preview' => (!empty($record['subparts'][$key]['h']) ? $record['subparts'][$key]['h'] : ''),
                            'thumbnail' => $resArray['thumbnail'],
                            'metadata' => $metadata
                        ];
                    }
                }
            } elseif (
                !empty($this->metadata['options']['source'])
                && $this->metadata['options']['source'] == 'search'
            ) {
                if ($this->solrConnect()) {
                    $result = $this->getSolrResult($record);
                    $record = $this->getSolrRecord($record, $result);
                }
            }
            // Save record for later usage.
            $this->records[$element['u']] = $record;
        } else {
            $this->logger->notice('No UID of list element to fetch full record');
            $record = $element;
        }
        return $record;
    }

    /**
     * It gets SOLR result
     *
     * @access private
     *
     * @param array $record: for searched document
     *
     * @return Result
     */
    private function getSolrResult($record)
    {
        $fields = Solr::getFields();

        $query = $this->solr->service->createSelect();
        // Restrict the fields to the required ones
        $query->setFields($fields['uid'] . ',' . $fields['id'] . ',' . $fields['toplevel'] . ',' . $fields['thumbnail'] . ',' . $fields['page']);
        foreach ($this->solrConfig as $solr_name) {
            $query->addField($solr_name);
        }
        // Set additional query parameters.
        // Set reasonable limit for safety reasons.
        // We don't expect to get more than 10.000 hits per UID.
        $query->setStart(0)->setRows(10000);
        // Take over existing filter queries.
        $filterQueries = isset($this->metadata['options']['params']['filterquery']) ? $this->metadata['options']['params']['filterquery'] : [];
        // Extend filter query to get all documents with the same UID.
        foreach ($filterQueries as $key => $value) {
            if (isset($value['query'])) {
                $filterQuery[$key] = $value['query'] . ' OR ' . $fields['toplevel'] . ':true';
                $filterQuery = [
                    'key' => $key,
                    'query' => $value['query'] . ' OR ' . $fields['toplevel'] . ':true'
                ];
                $query->addFilterQuery($filterQuery);
            }
        }
        // Add filter query to get all documents with the required uid.
        $query->createFilterQuery('uid')->setQuery($fields['uid'] . ':' . Solr::escapeQuery($record['uid']));
        // Add sorting.
        $query->addSort('score', $this->metadata['options']['params']['sort']['score']);
        // Set query.
        $query->setQuery($this->metadata['options']['select'] . ' OR ' . $fields['toplevel'] . ':true');

        // If it is a fulltext search, enable highlighting.
        if ($this->metadata['fulltextSearch']) {
            $query->getHighlighting();
        };

        $solrRequest = $this->solr->service->createRequest($query);

        // If it is a fulltext search, enable highlighting.
        if ($this->metadata['fulltextSearch']) {
            // field for which highlighting is going to be performed,
            // is required if you want to have OCR highlighting
            $solrRequest->addParam('hl.ocr.fl', $fields['fulltext']);
            // return the coordinates of highlighted search as absolute coordinates
            $solrRequest->addParam('hl.ocr.absoluteHighlights', 'on');
            // max amount of snippets for a single page
            $solrRequest->addParam('hl.snippets', 20);
            // we store the fulltext on page level and can disable this option
            $solrRequest->addParam('hl.ocr.trackPages', 'off');
        }
        // Perform search for all documents with the same uid that either fit to the search or marked as toplevel.
        $response = $this->solr->service->executeRequest($solrRequest);
        return $this->solr->service->createResult($query, $response);
    }

    /**
     * It processes SOLR result into record, which is
     * going to be displayed in the frontend list.
     *
     * @access private
     *
     * @param array $record: for searched document
     * @param Result $result: found in the SOLR index
     *
     * @return array
     */
    private function getSolrRecord($record, $result)
    {
        // If it is a fulltext search, fetch the highlighting results.
        if ($this->metadata['fulltextSearch']) {
            $data = $result->getData();
            $highlighting = $data['ocrHighlighting'];
        }

        // Process results.
        foreach ($result as $resArray) {
            // Prepare document's metadata.
            $metadata = [];
            foreach ($this->solrConfig as $index_name => $solr_name) {
                if (!empty($resArray->$solr_name)) {
                    $metadata[$index_name] = (is_array($resArray->$solr_name) ? $resArray->$solr_name : [$resArray->$solr_name]);
                }
            }
            // Add metadata to list elements.
            if ($resArray->toplevel) {
                $record['thumbnail'] = $resArray->thumbnail;
                $record['metadata'] = $metadata;
            } else {
                $highlight = '';
                if (!empty($highlighting)) {
                    $resultDocument = new ResultDocument($resArray, $highlighting, Solr::getFields());
                    $highlight = $resultDocument->getSnippets();
                }

                $record['subparts'][$resArray->id] = [
                    'uid' => $resArray->uid,
                    'page' => $resArray->page,
                    'preview' => $highlight,
                    'thumbnail' => $resArray->thumbnail,
                    'metadata' => $metadata
                ];
            }
        }
        return $record;
    }

    /**
     * This returns the current position
     * @see \Iterator::key()
     *
     * @access public
     *
     * @return int The current position
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * This moves the element at the given position up or down
     *
     * @access public
     *
     * @param int $position: Numeric position for moving
     * @param int $steps: Amount of steps to move up or down
     *
     * @return void
     */
    public function move($position, $steps)
    {
        $position = intval($position);
        // Check if list position is valid.
        if (
            $position < 0
            || $position >= $this->count
        ) {
            $this->logger->warning('Invalid position "' . $position . '" for element moving');
            return;
        }
        $steps = intval($steps);
        // Check if moving given amount of steps is possible.
        if (($position + $steps) < 0
            || ($position + $steps) >= $this->count
        ) {
            $this->logger->warning('Invalid steps "' . $steps . '" for moving element at position "' . $position . '"');
            return;
        }
        $element = $this->remove($position);
        $this->add([$element], $position + $steps);
    }

    /**
     * This moves the element at the given position up
     *
     * @access public
     *
     * @param int $position: Numeric position for moving
     *
     * @return void
     */
    public function moveUp($position)
    {
        $this->move($position, -1);
    }

    /**
     * This moves the element at the given position down
     *
     * @access public
     *
     * @param int $position: Numeric position for moving
     *
     * @return void
     */
    public function moveDown($position)
    {
        $this->move($position, 1);
    }

    /**
     * This increments the current list position
     * @see \Iterator::next()
     *
     * @access public
     *
     * @return void
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * This checks if an offset exists
     * @see \ArrayAccess::offsetExists()
     *
     * @access public
     *
     * @param mixed $offset: The offset to check
     *
     * @return bool Does the given offset exist?
     */
    public function offsetExists($offset)
    {
        return isset($this->elements[$offset]);
    }

    /**
     * This returns the element at the given offset
     * @see \ArrayAccess::offsetGet()
     *
     * @access public
     *
     * @param mixed $offset: The offset to return
     *
     * @return array The element at the given offset
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->getRecord($this->elements[$offset]);
        } else {
            $this->logger->notice('Invalid offset "' . $offset . '" for list element');
            return;
        }
    }

    /**
     * This sets the element at the given offset
     * @see \ArrayAccess::offsetSet()
     *
     * @access public
     *
     * @param mixed $offset: The offset to set (non-integer offsets will be appended)
     * @param mixed $value: The value to set
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($offset)) {
            $this->elements[$offset] = $value;
        } else {
            $this->elements[] = $value;
        }
        // Re-number the elements.
        $this->elements = array_values($this->elements);
        $this->count = count($this->elements);
    }

    /**
     * This removes the element at the given position from the list
     *
     * @access public
     *
     * @param int $position: Numeric position for removing
     *
     * @return array The removed element
     */
    public function remove($position)
    {
        $position = intval($position);
        if (
            $position < 0
            || $position >= $this->count
        ) {
            $this->logger->warning('Invalid position "' . $position . '" for element removing');
            return;
        }
        $removed = array_splice($this->elements, $position, 1);
        $this->count = count($this->elements);
        return $this->getRecord($removed[0]);
    }

    /**
     * This removes elements at the given range from the list
     *
     * @access public
     *
     * @param int $position: Numeric position for start of range
     * @param int $length: Numeric position for length of range
     *
     * @return array The indizes of the removed elements
     */
    public function removeRange($position, $length)
    {
        $position = intval($position);
        if (
            $position < 0
            || $position >= $this->count
        ) {
            $this->logger->warning('Invalid position "' . $position . '" for element removing');
            return;
        }
        $removed = array_splice($this->elements, $position, $length);
        $this->count = count($this->elements);
        return $removed;
    }

    /**
     * This clears the current list
     *
     * @access public
     *
     * @return void
     */
    public function reset()
    {
        $this->elements = [];
        $this->records = [];
        $this->metadata = [];
        $this->count = 0;
        $this->position = 0;
    }

    /**
     * This resets the list position
     * @see \Iterator::rewind()
     *
     * @access public
     *
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * This saves the current list
     *
     * @access public
     *
     * @param int $pid: PID for saving in database
     *
     * @return void
     */
    public function save($pid = 0)
    {
        $pid = max(intval($pid), 0);
        // If no PID is given, save to the user's session instead
        if ($pid > 0) {
            // TODO: Liste in Datenbank speichern (inkl. Sichtbarkeit, Beschreibung, etc.)
        } else {
            Helper::saveToSession([$this->elements, $this->metadata], get_class($this));
        }
    }

    /**
     * Connects to Solr server.
     *
     * @access protected
     *
     * @return bool true on success or false on failure
     */
    protected function solrConnect()
    {
        // Get Solr instance.
        if (!$this->solr) {
            // Connect to Solr server.
            $solr = Solr::getInstance($this->metadata['options']['core']);
            if ($solr->ready) {
                $this->solr = $solr;
                // Get indexed fields.
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('tx_dlf_metadata');

                // Load index configuration.
                $result = $queryBuilder
                    ->select(
                        'tx_dlf_metadata.index_name AS index_name',
                        'tx_dlf_metadata.index_tokenized AS index_tokenized',
                        'tx_dlf_metadata.index_indexed AS index_indexed'
                    )
                    ->from('tx_dlf_metadata')
                    ->where(
                        $queryBuilder->expr()->eq('tx_dlf_metadata.is_listed', 1),
                        $queryBuilder->expr()->eq('tx_dlf_metadata.pid', intval($this->metadata['options']['pid'])),
                        Helper::whereExpression('tx_dlf_metadata')
                    )
                    ->orderBy('tx_dlf_metadata.sorting', 'ASC')
                    ->execute();

                while ($resArray = $result->fetch()) {
                    $this->solrConfig[$resArray['index_name']] = $resArray['index_name'] . '_' . ($resArray['index_tokenized'] ? 't' : 'u') . 's' . ($resArray['index_indexed'] ? 'i' : 'u');
                }
                // Add static fields.
                $this->solrConfig['type'] = 'type';
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * This sorts the current list by the given field
     *
     * @access public
     *
     * @param string $by: Sort the list by this field
     * @param bool $asc: Sort ascending?
     *
     * @return void
     */
    public function sort($by, $asc = true)
    {
        $newOrder = [];
        $nonSortable = [];
        foreach ($this->elements as $num => $element) {
            // Is this element sortable?
            if (!empty($element['s'][$by])) {
                $newOrder[$element['s'][$by] . str_pad($num, 6, '0', STR_PAD_LEFT)] = $element;
            } else {
                $nonSortable[] = $element;
            }
        }
        // Reorder elements.
        if ($asc) {
            ksort($newOrder, SORT_LOCALE_STRING);
        } else {
            krsort($newOrder, SORT_LOCALE_STRING);
        }
        // Add non sortable elements to the end of the list.
        $newOrder = array_merge(array_values($newOrder), $nonSortable);
        // Check if something is missing.
        if ($this->count == count($newOrder)) {
            $this->elements = $newOrder;
        } else {
            $this->logger->error('Sorted list elements do not match unsorted elements');
        }
    }

    /**
     * This unsets the element at the given offset
     * @see \ArrayAccess::offsetUnset()
     *
     * @access public
     *
     * @param mixed $offset: The offset to unset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->elements[$offset]);
        // Re-number the elements.
        $this->elements = array_values($this->elements);
        $this->count = count($this->elements);
    }

    /**
     * This checks if the current list position is valid
     * @see \Iterator::valid()
     *
     * @access public
     *
     * @return bool Is the current list position valid?
     */
    public function valid()
    {
        return isset($this->elements[$this->position]);
    }

    /**
     * This returns $this->metadata via __get()
     *
     * @access protected
     *
     * @return array The list's metadata
     */
    protected function _getMetadata()
    {
        return $this->metadata;
    }

    /**
     * This sets $this->metadata via __set()
     *
     * @access protected
     *
     * @param array $metadata: Array of new metadata
     *
     * @return void
     */
    protected function _setMetadata(array $metadata = [])
    {
        $this->metadata = $metadata;
    }

    /**
     * This is the constructor
     *
     * @access public
     *
     * @param array $elements: Array of documents initially setting up the list
     * @param array $metadata: Array of initial metadata
     *
     * @return void
     */
    public function __construct(array $elements = [], array $metadata = [])
    {
        if (
            empty($elements)
            && empty($metadata)
        ) {
            // Let's check the user's session.
            $sessionData = Helper::loadFromSession(get_class($this));
            // Restore list from session data.
            if (is_array($sessionData)) {
                if (is_array($sessionData[0])) {
                    $this->elements = $sessionData[0];
                }
                if (is_array($sessionData[1])) {
                    $this->metadata = $sessionData[1];
                }
            }
        } else {
            // Add metadata to the list.
            $this->metadata = $metadata;
            // Add initial set of elements to the list.
            $this->elements = $elements;
        }

        $this->count = count($this->elements);
    }

    /**
     * This magic method is invoked each time a clone is called on the object variable
     *
     * @access protected
     *
     * @return void
     */
    protected function __clone()
    {
        // This method is defined as protected because singleton objects should not be cloned.
    }

    /**
     * This magic method is called each time an invisible property is referenced from the object
     *
     * @access public
     *
     * @param string $var: Name of variable to get
     *
     * @return mixed Value of $this->$var
     */
    public function __get($var)
    {
        $method = '_get' . ucfirst($var);
        if (
            !property_exists($this, $var)
            || !method_exists($this, $method)
        ) {
            $this->logger->warning('There is no getter function for property "' . $var . '"');
            return;
        } else {
            return $this->$method();
        }
    }

    /**
     * This magic method is called each time an invisible property is checked for isset() or empty()
     *
     * @access public
     *
     * @param string $var: Name of variable to check
     *
     * @return bool true if variable is set and not empty, false otherwise
     */
    public function __isset($var)
    {
        return !empty($this->__get($var));
    }

    /**
     * This magic method is called each time an invisible property is referenced from the object
     *
     * @access public
     *
     * @param string $var: Name of variable to set
     * @param mixed $value: New value of variable
     *
     * @return void
     */
    public function __set($var, $value)
    {
        $method = '_set' . ucfirst($var);
        if (
            !property_exists($this, $var)
            || !method_exists($this, $method)
        ) {
            $this->logger->warning('There is no setter function for property "' . $var . '"');
        } else {
            $this->$method($value);
        }
    }

    /**
     * This magic method is executed prior to any serialization of the object
     * @see __wakeup()
     *
     * @access public
     *
     * @return array Properties to be serialized
     */
    public function __sleep()
    {
        return ['elements', 'metadata'];
    }

    /**
     * This magic method is executed after the object is deserialized
     * @see __sleep()
     *
     * @access public
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->count = count($this->elements);
    }
}
