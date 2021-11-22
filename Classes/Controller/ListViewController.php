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

use Kitodo\Dlf\Domain\Model\Metadata;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;

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
class ListViewController extends AbstractController
{
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
     * Enriched documentList data for the view.
     *
     * @var array
     */
    protected $metadataList = [];

    /**
     * @var MetadataRepository
     */
    protected $metadataRepository;

    /**
     * @param MetadataRepository $metadataRepository
     */
    public function injectMetadataRepository(MetadataRepository $metadataRepository)
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * Renders one entry of the list
     *
     * @access protected
     *
     * @param int $number: The number of the entry
     *
     * @return string The rendered entry ready for output
     */
    protected function getEntry($number)
    {
        $imgAlt = '';
        $metadata = $this->list[$number]['metadata'];
        foreach ($this->metadata as $index_name => $metaConf) {
            if (!empty($metadata[$index_name])) {
                $parsedValues = [];
                $fieldwrap = $this->getFieldWrap($index_name, $metaConf['wrap']);

                do {
                    $value = @array_shift($metadata[$index_name]);
                    // Link title to pageview.
                    if ($index_name == 'title') {
                        // Get title of parent document if needed.
                        if (empty($value) && $this->settings['getTitle']) {
                            $superiorTitle = Document::getTitle($this->list[$number]['uid'], true);
                            if (!empty($superiorTitle)) {
                                $value = '[' . $superiorTitle . ']';
                            }
                        }
                        // Set fake title if still not present.
                        if (empty($value)) {
                            $value = LocalizationUtility::translate('noTitle', 'dlf');
                        }
                        $imgAlt = htmlspecialchars($value);
                        $value = htmlspecialchars($value);

                    } elseif ($index_name == 'owner' && !empty($value)) {
                        // Translate name of holding library.
                        $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_libraries', $this->settings['pages']));
                    } elseif ($index_name == 'type' && !empty($value)) {
                        // Translate document type.
                        $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_structures', $this->settings['pages']));
                    } elseif ($index_name == 'language' && !empty($value)) {
                        // Translate ISO 639 language code.
                        $value = htmlspecialchars(Helper::getLanguageName($value));
                    } elseif (!empty($value)) {
                        $value = htmlspecialchars($value);
                    }

                    if (!empty($value)) {
                        $parsedValues[] = [
                            'value' => $value,
                            'wrap' => $fieldwrap['value.']
                        ];
                    }
                } while (count($metadata[$index_name]));

                if (!empty($parsedValues)) {
                    $field[$index_name] = [
                        'label' => [
                            'value' => htmlspecialchars($metaConf['label']),
                            'wrap' => $fieldwrap['key.'],
                        ],
                        'values' => $parsedValues,
                        'wrap' => $fieldwrap['all.']
                    ];

                    $this->metadataList[$number]['metadata'] = $field;
                }
            }
        }

        // Add thumbnail.
        if (!empty($this->list[$number]['thumbnail'])) {
            $this->metadataList[$number]['thumbnail'] = [
                'alt' => $imgAlt,
                'src' => $this->list[$number]['thumbnail']
            ];
        }

        if (!empty($this->list[$number]['subparts'])) {
            $this->getSubEntries($number);
        }
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
     * Parses a string into a Typoscript array
     *
     * @access protected
     *
     * @param string $string: The string to parse
     *
     * @return array The resulting typoscript array
     */
    protected function parseTS($string = '')
    {
        $parser = GeneralUtility::makeInstance(TypoScriptParser::class);
        $parser->parse($string);
        return $parser->setup;
    }

    /**
     * Renders all sub-entries of one entry
     *
     * @access protected
     *
     * @param int $number: The number of the entry
     *
     * @return string The rendered entries ready for output
     */
    protected function getSubEntries($number)
    {
        foreach ($this->list[$number]['subparts'] as $subpartKey => $subpart) {
            $imgAlt = '';
            foreach ($this->metadata as $index_name => $metaConf) {
                $parsedValues = [];
                $fieldwrap = $this->getFieldWrap($index_name, $metaConf['wrap']);
                do {
                    $value = @array_shift($subpart['metadata'][$index_name]);
                    // Link title to pageview.
                    if ($index_name == 'title') {
                        // Get title of parent document if needed.
                        if (empty($value) && $this->settings['getTitle']) {
                            $superiorTitle = Document::getTitle($subpart['uid'], true);
                            if (!empty($superiorTitle)) {
                                $value = '[' . $superiorTitle . ']';
                            }
                        }
                        // Set fake title if still not present.
                        if (empty($value)) {
                            $value = LocalizationUtility::translate('noTitle', 'dlf');
                        }
                        $imgAlt = htmlspecialchars($value);
                        $value = htmlspecialchars($value);
                    } elseif ($index_name == 'owner' && !empty($value)) {
                        // Translate name of holding library.
                        $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_libraries', $this->settings['pages']));
                    } elseif ($index_name == 'type' && !empty($value)) {
                        // Translate document type.
                        $_value = $value;
                        $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_structures', $this->settings['pages']));
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

                    if (!empty($value)) {
                        $parsedValues[] = [
                            'value' => $value,
                            'wrap' => $fieldwrap['value.']
                        ];
                    }

                } while (is_array($subpart['metadata'][$index_name]) && count($subpart['metadata'][$index_name]) > 0);
                if (!empty($parsedValues)) {
                    $field[$index_name] = [
                        'label' => [
                            'value' => htmlspecialchars($metaConf['label']),
                            'wrap' => $fieldwrap['key.'],
                        ],
                        'values' => $parsedValues,
                        'wrap' => $fieldwrap['all.']
                    ];

                    $this->metadataList[$number]['subparts'][$subpartKey]['metadata'] = $field;
                }
            }

            // Add thumbnail.
            if (!empty($subpart['thumbnail'])) {
                $this->metadataList[$number]['subparts'][$subpartKey]['thumbnail'] = [
                    'alt' => $imgAlt,
                    'src' => $subpart['thumbnail']
                ];
            }
        }
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
        $metadataResult = $this->metadataRepository->findBySettings(['is_listed' => 1, 'is_sortable' => 1]);

        /** @var Metadata $metadata */
        foreach ($metadataResult as $metadata) {
            if ($metadata->getIsListed()) {
                $this->metadata[$metadata->getIndexName()] = [
                    'wrap' => $metadata->getWrap(),
                    'label' => Helper::translate($metadata->getIndexName(), 'tx_dlf_metadata', $this->settings['pages'])
                ];
            }
            if ($metadata->getIsSortable()) {
                $this->sortables[$metadata->getIndexName()] = Helper::translate($metadata->getIndexName(), 'tx_dlf_metadata', $this->settings['pages']);
            }
        }
    }

    /**
     * The main method of the plugin
     *
     * @return void
     */
    public function mainAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');

        $sort = $requestData['sort'];
        $pointer = $requestData['pointer'];
        $logicalPage = $requestData['logicalPage'];

        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('dlf');

        // Load the list.
        $this->list = GeneralUtility::makeInstance(DocumentList::class);
        $currentEntry = $pointer * $this->settings['limit'];
        $lastEntry = ($pointer + 1) * $this->settings['limit'];

        // Check if it's a list of database records or Solr documents.
        if (
            !empty($this->list->metadata['options']['source'])
            && $this->list->metadata['options']['source'] == 'collection'
            && ((!empty($sort['order']) && $sort['order'] != $this->list->metadata['options']['order'])
                || (isset($sort['orderBy']) && $sort['orderBy'] != $this->list->metadata['options']['orderBy']))
        ) {
            // Order list by given field.
            $this->list->sort($sort['order'], $sort['orderBy'] == 'asc' ? true : false);
            // Update list's metadata.
            $listMetadata = $this->list->metadata;
            $listMetadata['options']['order'] = $sort['order'];
            $listMetadata['options']['orderBy'] = $sort['orderBy'];
            $this->list->metadata = $listMetadata;
            // Save updated list.
            $this->list->save();
            // Reset pointer.
            $pointer = 0;
        } elseif (!empty($this->list->metadata['options']['source']) && $this->list->metadata['options']['source'] == 'search') {
            // Update list's metadata
            $listMetadata = $this->list->metadata;
            // Sort the list if applicable.
            if ((!empty($sort['order']) && $sort['order'] != $listMetadata['options']['order'])
                || (isset($sort['orderBy']) && $sort['orderBy'] != $listMetadata['options']['orderBy'])
            ) {
                // Update list's metadata.
                $listMetadata['options']['params']['sort'] = [$sort['order'] . "_sorting" => (bool) $sort['asc'] ? 'asc' : 'desc'];
                $listMetadata['options']['order'] = $sort['order'];
                $listMetadata['options']['orderBy'] = $sort['orderBy'];
                // Reset pointer.
                $pointer = 0;
            }
            // Set some query parameters
            $listMetadata['options']['params']['start'] = $currentEntry;
            $listMetadata['options']['params']['rows'] = $this->settings['limit'];
            // Search only if the query params have changed.
            if ($listMetadata['options']['params'] != $this->list->metadata['options']['params']) {
                // Instantiate search object.
                $solr = Solr::getInstance($this->list->metadata['options']['core']);
                if (!$solr->ready) {
                    $this->logger->error('Apache Solr not available');
                }
                // Set search parameters.
                $solr->cPid = $listMetadata['options']['pid'];
                $solr->params = $listMetadata['options']['params'];
                // Perform search.
                $this->list = $solr->search();
            }
            $this->list->metadata = $listMetadata;
            // Save updated list.
            $this->list->save();
            $currentEntry = 0;
            $lastEntry = $this->settings['limit'];
        }

        // Set some variable defaults.
        if (!empty($pointer) && (($pointer * $this->settings['limit']) + 1) <= $this->list->metadata['options']['numberOfToplevelHits']) {
            $pointer = max(intval($pointer), 0);
        } else {
            $pointer = 0;
        }

        // Load metadata configuration.
        $this->loadConfig();
        for ($currentEntry, $lastEntry; $currentEntry < $lastEntry; $currentEntry++) {
            if (empty($this->list[$currentEntry])) {
                break;
            } else {
                $this->getEntry($currentEntry);
            }
        }

        if ($currentEntry) {
            $currentEntry = ($pointer * $this->settings['limit']) + 1;
            $lastEntry = ($pointer * $this->settings['limit']) + $this->settings['limit'];
        }

        // Pagination of Results
        // pass the currentPage to the fluid template to calculate current index of search result
        if (empty($requestData['@widget_0'])) {
            $widgetPage = ['currentPage' => 1];
        } else {
            $widgetPage = $requestData['@widget_0'];
        }

        // convert documentList to array --> use widget.pagination viewhelper
        $documentList = [];
        foreach ($this->list as $listElement) {
            $documentList[] = $listElement;
        }
        $this->view->assign('widgetPage', $widgetPage);
        $this->view->assign('documentList', $this->list);
        $this->view->assign('documentListArray', $documentList);
        $this->view->assign('metadataList', $this->metadataList);
        $this->view->assign('metadataConfig', $this->metadata);
        $this->view->assign('currentEntry', $currentEntry);
        $this->view->assign('lastEntry', $lastEntry);
        $this->view->assign('sortables', $this->sortables);
        $this->view->assign('logicalPage', $logicalPage);
        $this->view->assign(
            'maxPages',
            intval(ceil($this->list->metadata['options']['numberOfToplevelHits'] / $this->settings['limit']))
        );
        $this->view->assign('forceAbsoluteUrl', !empty($this->extConf['forceAbsoluteUrl']) ? 1 : 0);
    }
}
