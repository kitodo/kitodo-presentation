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

use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'List View' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Henrik Lochmann <dev@mentalmotive.com>
 * @author Frank Ulrich Weber <fuw@zeutschel.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class ListView extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/ListView.php';

    /**
     * This holds the field wrap of the metadata
     *
     * @var array
     * @access private
     */
    private $fieldwrap = [];

    /**
     * This holds the list
     *
     * @var \Kitodo\Dlf\Common\DocumentList
     * @access protected
     */
    protected $list;

    /**
     * Array of sorted metadata
     *
     * @var array
     * @access protected
     */
    protected $metadata = [];

    /**
     * Array of sortable metadata
     *
     * @var array
     * @access protected
     */
    protected $sortables = [];

    /**
     * Renders the page browser
     *
     * @access protected
     *
     * @return string The rendered page browser ready for output
     */
    protected function getPageBrowser()
    {
        // Get overall number of pages.
        $maxPages = intval(ceil($this->list->metadata['options']['numberOfToplevelHits'] / $this->conf['limit']));
        // Return empty pagebrowser if there is just one page.
        if ($maxPages < 2) {
            return '';
        }
        // Get separator.
        $separator = '<span class="separator">' . htmlspecialchars($this->pi_getLL('separator', ' - ')) . '</span>';
        // Add link to previous page.
        if ($this->piVars['pointer'] > 0) {
            $output = $this->pi_linkTP_keepPIvars(htmlspecialchars($this->pi_getLL('prevPage', '<')), ['pointer' => $this->piVars['pointer'] - 1], true) . $separator;
        } else {
            $output = '<span>' . htmlspecialchars($this->pi_getLL('prevPage', '<')) . '</span>' . $separator;
        }
        $i = 0;
        $skip = null;
        // Add links to pages.
        while ($i < $maxPages) {
            if ($i < 3 || ($i > $this->piVars['pointer'] - 3 && $i < $this->piVars['pointer'] + 3) || $i > $maxPages - 4) {
                if ($this->piVars['pointer'] != $i) {
                    $output .= $this->pi_linkTP_keepPIvars(htmlspecialchars(sprintf($this->pi_getLL('page', '%d'), $i + 1)), ['pointer' => $i], true) . $separator;
                } else {
                    $output .= '<span class="active">' . htmlspecialchars(sprintf($this->pi_getLL('page', '%d'), $i + 1)) . '</span>' . $separator;
                }
                $skip = true;
            } elseif ($skip === true) {
                $output .= '<span class="skip">' . htmlspecialchars($this->pi_getLL('skip', '...')) . '</span>' . $separator;
                $skip = false;
            }
            $i++;
        }
        // Add link to next page.
        if ($this->piVars['pointer'] < $maxPages - 1) {
            $output .= $this->pi_linkTP_keepPIvars(htmlspecialchars($this->pi_getLL('nextPage', '>')), ['pointer' => $this->piVars['pointer'] + 1], true);
        } else {
            $output .= '<span>' . htmlspecialchars($this->pi_getLL('nextPage', '>')) . '</span>';
        }
        return $output;
    }

    /**
     * Renders one entry of the list
     *
     * @access protected
     *
     * @param int $number: The number of the entry
     * @param string $template: Parsed template subpart
     *
     * @return string The rendered entry ready for output
     */
    protected function getEntry($number, $template)
    {
        $markerArray['###NUMBER###'] = ($this->piVars['pointer'] * $this->conf['limit']) + $number + 1;
        $markerArray['###METADATA###'] = '';
        $markerArray['###THUMBNAIL###'] = '';
        $markerArray['###PREVIEW###'] = '';
        $subpart = '';
        $imgAlt = '';
        $noTitle = $this->pi_getLL('noTitle');
        $metadata = $this->list[$number]['metadata'];
        foreach ($this->metadata as $index_name => $metaConf) {
            if (!empty($metadata[$index_name])) {
                $parsedValue = '';
                $fieldwrap = $this->getFieldWrap($index_name, $metaConf['wrap']);
                do {
                    $value = @array_shift($metadata[$index_name]);
                    // Link title to pageview.
                    if ($index_name == 'title') {
                        // Get title of parent document if needed.
                        if (empty($value) && $this->conf['getTitle']) {
                            $superiorTitle = Document::getTitle($this->list[$number]['uid'], true);
                            if (!empty($superiorTitle)) {
                                $value = '[' . $superiorTitle . ']';
                            }
                        }
                        // Set fake title if still not present.
                        if (empty($value)) {
                            $value = $noTitle;
                        }
                        $imgAlt = htmlspecialchars($value);
                        $additionalParams = [
                            'id' => $this->list[$number]['uid'],
                            'page' => $this->list[$number]['page']
                        ];
                        if (!empty($this->piVars['logicalPage'])) {
                            $additionalParams['logicalPage'] = $this->piVars['logicalPage'];
                        }
                        $conf = [
                            'useCacheHash' => 1,
                            'parameter' => $this->conf['targetPid'],
                            'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
                            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
                            'additionalParams' => GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', true, false)
                        ];
                        $value = $this->cObj->typoLink(htmlspecialchars($value), $conf);
                    } elseif ($index_name == 'owner' && !empty($value)) {
                        // Translate name of holding library.
                        $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_libraries', $this->conf['pages']));
                    } elseif ($index_name == 'type' && !empty($value)) {
                        // Translate document type.
                        $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_structures', $this->conf['pages']));
                    } elseif ($index_name == 'language' && !empty($value)) {
                        // Translate ISO 639 language code.
                        $value = htmlspecialchars(Helper::getLanguageName($value));
                    } elseif (!empty($value)) {
                        $value = htmlspecialchars($value);
                    }
                    $value = $this->cObj->stdWrap($value, $fieldwrap['value.']);
                    if (!empty($value)) {
                        $parsedValue .= $value;
                    }
                } while (count($metadata[$index_name]));
                if (!empty($parsedValue)) {
                    $field = $this->cObj->stdWrap(htmlspecialchars($metaConf['label']), $fieldwrap['key.']);
                    $field .= $parsedValue;
                    $markerArray['###METADATA###'] .= $this->cObj->stdWrap($field, $fieldwrap['all.']);
                }
            }
        }
        // Add thumbnail.
        if (!empty($this->list[$number]['thumbnail'])) {
            $markerArray['###THUMBNAIL###'] = '<img alt="' . $imgAlt . '" src="' . $this->list[$number]['thumbnail'] . '" />';
        }
        // Add preview.
        if (!empty($this->list[$number]['preview'])) {
            $markerArray['###PREVIEW###'] = $this->list[$number]['preview'];
        }
        if (!empty($this->list[$number]['subparts'])) {
            $subpart = $this->getSubEntries($number, $template);
        }
        // Basket button.
        $markerArray['###BASKETBUTTON###'] = '';
        if (!empty($this->conf['basketButton']) && !empty($this->conf['targetBasket'])) {
            $additionalParams = ['id' => $this->list[$number]['uid'], 'startpage' => $this->list[$number]['page'], 'addToBasket' => 'list'];
            $conf = [
                'useCacheHash' => 1,
                'parameter' => $this->conf['targetBasket'],
                'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
                'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
                'additionalParams' => GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', true, false)
            ];
            $link = $this->cObj->typoLink(htmlspecialchars($this->pi_getLL('addBasket', '')), $conf);
            $markerArray['###BASKETBUTTON###'] = $link;
        }
        return $this->templateService->substituteMarkerArray($this->templateService->substituteSubpart($template['entry'], '###SUBTEMPLATE###', $subpart, true), $markerArray);
    }

    /**
     * Returns the parsed fieldwrap of a metadata
     *
     * @access private
     *
     * @param string $index_name: The index name of a metadata
     * @param string $wrap: The configured metadata wrap
     *
     * @return array The parsed fieldwrap
     */
    private function getFieldWrap($index_name, $wrap)
    {
        if (isset($this->fieldwrap[$index_name])) {
            return $this->fieldwrap[$index_name];
        } else {
            return $this->fieldwrap[$index_name] = $this->parseTS($wrap);
        }
    }

    /**
     * Renders sorting dialog
     *
     * @access protected
     *
     * @return string The rendered sorting dialog ready for output
     */
    protected function getSortingForm()
    {
        // Return nothing if there are no sortable metadata fields.
        if (!count($this->sortables)) {
            return '';
        }
        // Set class prefix.
        $prefix = Helper::getUnqualifiedClassName(get_class($this));
        // Configure @action URL for form.
        $linkConf = [
            'parameter' => $GLOBALS['TSFE']->id,
            'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http']
        ];
        if (!empty($this->piVars['logicalPage'])) {
            $linkConf['additionalParams'] = GeneralUtility::implodeArrayForUrl($this->prefixId, ['logicalPage' => $this->piVars['logicalPage']], '', true, false);
        }
        // Build HTML form.
        $sorting = '<form action="' . $this->cObj->typoLink_URL($linkConf) . '" method="get"><div><input type="hidden" name="id" value="' . $GLOBALS['TSFE']->id . '" />';
        foreach ($this->piVars as $piVar => $value) {
            if ($piVar != 'order' && $piVar != 'DATA' && !empty($value)) {
                $sorting .= '<input type="hidden" name="' . $this->prefixId . '[' . preg_replace('/[^A-Za-z0-9_-]/', '', $piVar) . ']" value="' . htmlspecialchars($value) . '" />';
            }
        }
        // Select sort field.
        $uniqId = uniqid($prefix . '-');
        $sorting .= '<label for="' . $uniqId . '">' . htmlspecialchars($this->pi_getLL('orderBy', '')) . '</label><select id="' . $uniqId . '" name="' . $this->prefixId . '[order]" onchange="javascript:this.form.submit();">';
        // Add relevance sorting if this is a search result list.
        if ($this->list->metadata['options']['source'] == 'search') {
            $sorting .= '<option value="score"' . (($this->list->metadata['options']['order'] == 'score') ? ' selected="selected"' : '') . '>' . htmlspecialchars($this->pi_getLL('relevance', '')) . '</option>';
        }
        foreach ($this->sortables as $index_name => $label) {
            $sorting .= '<option value="' . htmlspecialchars($index_name) . '"' . (($this->list->metadata['options']['order'] == $index_name) ? ' selected="selected"' : '') . '>' . htmlspecialchars($label) . '</option>';
        }
        $sorting .= '</select>';
        // Select sort direction.
        $uniqId = uniqid($prefix . '-');
        $sorting .= '<label for="' . $uniqId . '">' . htmlspecialchars($this->pi_getLL('direction', '')) . '</label><select id="' . $uniqId . '" name="' . $this->prefixId . '[asc]" onchange="javascript:this.form.submit();">';
        $sorting .= '<option value="1" ' . ($this->list->metadata['options']['order.asc'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($this->pi_getLL('direction.asc', '')) . '</option>';
        $sorting .= '<option value="0" ' . (!$this->list->metadata['options']['order.asc'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($this->pi_getLL('direction.desc', '')) . '</option>';
        $sorting .= '</select></div></form>';
        return $sorting;
    }

    /**
     * Renders all sub-entries of one entry
     *
     * @access protected
     *
     * @param int $number: The number of the entry
     * @param string $template: Parsed template subpart
     *
     * @return string The rendered entries ready for output
     */
    protected function getSubEntries($number, $template)
    {
        $content = '';
        $noTitle = $this->pi_getLL('noTitle');
        $highlight_word = preg_replace('/\s\s+/', ';', $this->list->metadata['searchString']);
        foreach ($this->list[$number]['subparts'] as $subpart) {
            $markerArray['###SUBMETADATA###'] = '';
            $markerArray['###SUBTHUMBNAIL###'] = '';
            $markerArray['###SUBPREVIEW###'] = '';
            $imgAlt = '';
            foreach ($this->metadata as $index_name => $metaConf) {
                $parsedValue = '';
                $fieldwrap = $this->getFieldWrap($index_name, $metaConf['wrap']);
                do {
                    $value = @array_shift($subpart['metadata'][$index_name]);
                    // Link title to pageview.
                    if ($index_name == 'title') {
                        // Get title of parent document if needed.
                        if (empty($value) && $this->conf['getTitle']) {
                            $superiorTitle = Document::getTitle($subpart['uid'], true);
                            if (!empty($superiorTitle)) {
                                $value = '[' . $superiorTitle . ']';
                            }
                        }
                        // Set fake title if still not present.
                        if (empty($value)) {
                            $value = $noTitle;
                        }
                        $imgAlt = htmlspecialchars($value);
                        $additionalParams = [
                            'id' => $subpart['uid'],
                            'page' => $subpart['page'],
                            'highlight_word' => $highlight_word
                        ];
                        if (!empty($this->piVars['logicalPage'])) {
                            $additionalParams['logicalPage'] = $this->piVars['logicalPage'];
                        }
                        $conf = [
                            // we don't want cHash in case of search parameters
                            'useCacheHash' => empty($this->list->metadata['searchString']) ? 1 : 0,
                            'parameter' => $this->conf['targetPid'],
                            'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
                            'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
                            'additionalParams' => GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', true, false)
                        ];
                        $value = $this->cObj->typoLink(htmlspecialchars($value), $conf);
                    } elseif ($index_name == 'owner' && !empty($value)) {
                        // Translate name of holding library.
                        $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_libraries', $this->conf['pages']));
                    } elseif ($index_name == 'type' && !empty($value)) {
                        // Translate document type.
                        $_value = $value;
                        $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_structures', $this->conf['pages']));
                        // Add page number for single pages.
                        if ($_value == 'page') {
                            $value .= ' ' . intval($subpart['page']);
                        }
                    } elseif ($index_name == 'language' && !empty($value)) {
                        // Translate ISO 639 language code.
                        $value = htmlspecialchars(Helper::getLanguageName($value));
                    } elseif (!empty($value)) {
                        $value = htmlspecialchars($value);
                    }
                    $value = $this->cObj->stdWrap($value, $fieldwrap['value.']);
                    if (!empty($value)) {
                        $parsedValue .= $value;
                    }
                } while (is_array($subpart['metadata'][$index_name]) && count($subpart['metadata'][$index_name]) > 0);
                if (!empty($parsedValue)) {
                    $field = $this->cObj->stdWrap(htmlspecialchars($metaConf['label']), $fieldwrap['key.']);
                    $field .= $parsedValue;
                    $markerArray['###SUBMETADATA###'] .= $this->cObj->stdWrap($field, $fieldwrap['all.']);
                }
            }
            // Add thumbnail.
            if (!empty($subpart['thumbnail'])) {
                $markerArray['###SUBTHUMBNAIL###'] = '<img alt="' . $imgAlt . '" src="' . $subpart['thumbnail'] . '" />';
            }
            // Add preview.
            if (!empty($subpart['preview'])) {
                $markerArray['###SUBPREVIEW###'] = $subpart['preview'];
            }
            // Basket button
            $markerArray['###SUBBASKETBUTTON###'] = '';
            if (!empty($this->conf['basketButton']) && !empty($this->conf['targetBasket'])) {
                $additionalParams = ['id' => $this->list[$number]['uid'], 'startpage' => $subpart['page'], 'endpage' => $subpart['page'], 'logId' => $subpart['sid'], 'addToBasket' => 'subentry'];
                $conf = [
                    'useCacheHash' => 1,
                    'parameter' => $this->conf['targetBasket'],
                    'forceAbsoluteUrl' => !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0,
                    'forceAbsoluteUrl.' => ['scheme' => !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http'],
                    'additionalParams' => GeneralUtility::implodeArrayForUrl($this->prefixId, $additionalParams, '', true, false)
                ];
                $link = $this->cObj->typoLink(htmlspecialchars($this->pi_getLL('addBasket', '')), $conf);
                $markerArray['###SUBBASKETBUTTON###'] = $link;
            }
            $content .= $this->templateService->substituteMarkerArray($template['subentry'], $markerArray);
        }
        return $this->templateService->substituteSubpart($this->templateService->getSubpart($this->template, '###SUBTEMPLATE###'), '###SUBENTRY###', $content, true);
    }

    /**
     * Get metadata configuration from database
     *
     * @access protected
     *
     * @return void
     */
    protected function loadConfig()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_dlf_metadata');

        $result = $queryBuilder
            ->select(
                'tx_dlf_metadata.index_name AS index_name',
                'tx_dlf_metadata.wrap AS wrap',
                'tx_dlf_metadata.is_listed AS is_listed',
                'tx_dlf_metadata.is_sortable AS is_sortable'
            )
            ->from('tx_dlf_metadata')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('tx_dlf_metadata.is_listed', 1),
                    $queryBuilder->expr()->eq('tx_dlf_metadata.is_sortable', 1)
                ),
                $queryBuilder->expr()->eq('tx_dlf_metadata.pid', intval($this->conf['pages'])),
                Helper::whereExpression('tx_dlf_metadata')
            )
            ->orderBy('tx_dlf_metadata.sorting')
            ->execute();

        while ($resArray = $result->fetch()) {
            if ($resArray['is_listed']) {
                $this->metadata[$resArray['index_name']] = [
                    'wrap' => $resArray['wrap'],
                    'label' => Helper::translate($resArray['index_name'], 'tx_dlf_metadata', $this->conf['pages'])
                ];
            }
            if ($resArray['is_sortable']) {
                $this->sortables[$resArray['index_name']] = Helper::translate($resArray['index_name'], 'tx_dlf_metadata', $this->conf['pages']);
            }
        }
    }

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
        // Don't cache the output.
        $this->setCache(false);
        // Load the list.
        $this->list = GeneralUtility::makeInstance(DocumentList::class);
        $currentEntry = $this->piVars['pointer'] * $this->conf['limit'];
        $lastEntry = ($this->piVars['pointer'] + 1) * $this->conf['limit'];
        // Check if it's a list of database records or Solr documents.
        if (
            !empty($this->list->metadata['options']['source'])
            && $this->list->metadata['options']['source'] == 'collection'
            && ((!empty($this->piVars['order']) && $this->piVars['order'] != $this->list->metadata['options']['order'])
                || (isset($this->piVars['asc']) && $this->piVars['asc'] != $this->list->metadata['options']['order.asc']))
        ) {
            // Order list by given field.
            $this->list->sort($this->piVars['order'], (bool) $this->piVars['asc']);
            // Update list's metadata.
            $listMetadata = $this->list->metadata;
            $listMetadata['options']['order'] = $this->piVars['order'];
            $listMetadata['options']['order.asc'] = (bool) $this->piVars['asc'];
            $this->list->metadata = $listMetadata;
            // Save updated list.
            $this->list->save();
            // Reset pointer.
            $this->piVars['pointer'] = 0;
        } elseif (!empty($this->list->metadata['options']['source']) && $this->list->metadata['options']['source'] == 'search') {
            // Update list's metadata
            $listMetadata = $this->list->metadata;
            // Sort the list if applicable.
            if ((!empty($this->piVars['order']) && $this->piVars['order'] != $listMetadata['options']['order'])
                || (isset($this->piVars['asc']) && $this->piVars['asc'] != $listMetadata['options']['order.asc'])
            ) {
                // Update list's metadata.
                $listMetadata['options']['params']['sort'] = [$this->piVars['order'] . "_sorting" => (bool) $this->piVars['asc'] ? 'asc' : 'desc'];
                $listMetadata['options']['order'] = $this->piVars['order'];
                $listMetadata['options']['order.asc'] = (bool) $this->piVars['asc'];
                // Reset pointer.
                $this->piVars['pointer'] = 0;
            }
            // Set some query parameters
            $listMetadata['options']['params']['start'] = $currentEntry;
            $listMetadata['options']['params']['rows'] = $this->conf['limit'];
            // Search only if the query params have changed.
            if ($listMetadata['options']['params'] != $this->list->metadata['options']['params']) {
                // Instantiate search object.
                $solr = Solr::getInstance($this->list->metadata['options']['core']);
                if (!$solr->ready) {
                    $this->logger->error('Apache Solr not available');
                    return $content;
                }
                // Set search parameters.
                $solr->cPid = $listMetadata['options']['pid'];
                $solr->params = $listMetadata['options']['params'];
                // Perform search.
                $this->list = $solr->search();
            }
            // Add list description
            $listMetadata['description'] = '<p class="tx-dlf-search-numHits">' . htmlspecialchars(sprintf($this->pi_getLL('hits', ''), $this->list->metadata['options']['numberOfHits'], $this->list->metadata['options']['numberOfToplevelHits'])) . '</p>';
            $this->list->metadata = $listMetadata;
            // Save updated list.
            $this->list->save();
            $currentEntry = 0;
            $lastEntry = $this->conf['limit'];
        }
        // Load template file.
        $this->getTemplate();
        $subpartArray['entry'] = $this->templateService->getSubpart($this->template, '###ENTRY###');
        $subpartArray['subentry'] = $this->templateService->getSubpart($this->template, '###SUBENTRY###');
        // Set some variable defaults.
        if (!empty($this->piVars['pointer']) && (($this->piVars['pointer'] * $this->conf['limit']) + 1) <= $this->list->metadata['options']['numberOfToplevelHits']) {
            $this->piVars['pointer'] = max(intval($this->piVars['pointer']), 0);
        } else {
            $this->piVars['pointer'] = 0;
        }
        // Load metadata configuration.
        $this->loadConfig();
        for ($currentEntry, $lastEntry; $currentEntry < $lastEntry; $currentEntry++) {
            if (empty($this->list[$currentEntry])) {
                break;
            } else {
                $content .= $this->getEntry($currentEntry, $subpartArray);
            }
        }
        $markerArray['###LISTTITLE###'] = $this->list->metadata['label'];
        $markerArray['###LISTDESCRIPTION###'] = $this->list->metadata['description'];
        if (!empty($this->list->metadata['thumbnail'])) {
            $markerArray['###LISTTHUMBNAIL###'] = '<img alt="" src="' . $this->list->metadata['thumbnail'] . '" />';
        } else {
            $markerArray['###LISTTHUMBNAIL###'] = '';
        }
        if ($currentEntry) {
            $currentEntry = ($this->piVars['pointer'] * $this->conf['limit']) + 1;
            $lastEntry = ($this->piVars['pointer'] * $this->conf['limit']) + $this->conf['limit'];
            $markerArray['###COUNT###'] = htmlspecialchars(sprintf($this->pi_getLL('count'), $currentEntry, $lastEntry < $this->list->metadata['options']['numberOfToplevelHits'] ? $lastEntry : $this->list->metadata['options']['numberOfToplevelHits'], $this->list->metadata['options']['numberOfToplevelHits']));
        } else {
            $markerArray['###COUNT###'] = htmlspecialchars($this->pi_getLL('nohits', ''));
        }
        $markerArray['###PAGEBROWSER###'] = $this->getPageBrowser();
        $markerArray['###SORTING###'] = $this->getSortingForm();
        $content = $this->templateService->substituteMarkerArray($this->templateService->substituteSubpart($this->template, '###ENTRY###', $content, true), $markerArray);
        return $this->pi_wrapInBaseClass($content);
    }
}
