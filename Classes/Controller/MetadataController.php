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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use \TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Kitodo\Dlf\Common\Document;
use Kitodo\Dlf\Common\DocumentList;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Common\Solr;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use \Kitodo\Dlf\Domain\Model\SearchForm;
use Ubl\Iiif\Context\IRI;

class MetadataController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    public $prefixId = 'tx_dlf';
    public $extKey = 'dlf';

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Core\Log\LogManager
     */
    protected $logger;

    /**
     * SearchController constructor.
     * @param $configurationManager
     */
    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
        $this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
    }

    // TODO: Needs to be placed in an abstract class
    /**
     * Loads the current document into $this->doc
     *
     * @access protected
     *
     * @return void
     */
    protected function loadDocument($requestData)
    {
        // Check for required variable.
        if (
            !empty($requestData['id'])
            && !empty($this->settings['pages'])
        ) {
            // Should we exclude documents from other pages than $this->settings['pages']?
            $pid = (!empty($this->settings['excludeOther']) ? intval($this->settings['pages']) : 0);
            // Get instance of \Kitodo\Dlf\Common\Document.
            $this->doc = Document::getInstance($requestData['id'], $pid);
            if (!$this->doc->ready) {
                // Destroy the incomplete object.
                $this->doc = null;
                $this->logger->error('Failed to load document with UID ' . $requestData['id']);
            } else {
                // Set configuration PID.
                $this->doc->cPid = $this->settings['pages'];
            }
        } elseif (!empty($requestData['recordId'])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_documents');

            // Get UID of document with given record identifier.
            $result = $queryBuilder
                ->select('tx_dlf_documents.uid AS uid')
                ->from('tx_dlf_documents')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_documents.record_id', $queryBuilder->expr()->literal($requestData['recordId'])),
                    Helper::whereExpression('tx_dlf_documents')
                )
                ->setMaxResults(1)
                ->execute();

            if ($resArray = $result->fetch()) {
                $requestData['id'] = $resArray['uid'];
                // Set superglobal $_GET array and unset variables to avoid infinite looping.
                $_GET[$this->prefixId]['id'] = $requestData['id'];
                unset($requestData['recordId'], $_GET[$this->prefixId]['recordId']);
                // Try to load document.
                $this->loadDocument();
            } else {
                $this->logger->error('Failed to load document with record ID "' . $requestData['recordId'] . '"');
            }
        } else {
            $this->logger->error('Invalid UID ' . $requestData['id'] . ' or PID ' . $this->settings['pages'] . ' for document loading');
        }
    }

    /**
     * @return string|void
     */
    public function mainAction()
    {
        $requestData = GeneralUtility::_GPmerged('tx_dlf');
        unset($requestData['__referrer'], $requestData['__trustedProperties']);

        $this->cObj = $this->configurationManager->getContentObject();

        // Load current document.
        $this->loadDocument($requestData);
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return '';
        } else {
            // Set default values if not set.
            if (!isset($this->settings['rootline'])) {
                $this->settings['rootline'] = 0;
            }
            if (!isset($this->settings['originalIiifMetadata'])) {
                $this->settings['originalIiifMetadata'] = 0;
            }
            if (!isset($this->settings['displayIiifDescription'])) {
                $this->settings['displayIiifDescription'] = 1;
            }
            if (!isset($this->settings['displayIiifRights'])) {
                $this->settings['displayIiifRights'] = 1;
            }
            if (!isset($this->settings['displayIiifLinks'])) {
                $this->settings['displayIiifLinks'] = 1;
            }
        }
        $useOriginalIiifManifestMetadata = $this->settings['originalIiifMetadata'] == 1 && $this->doc instanceof IiifManifest;
        $metadata = [];
        if ($this->settings['rootline'] < 2) {
            // Get current structure's @ID.
            $ids = [];
            if (!empty($this->doc->physicalStructure[$requestData['page']]) && !empty($this->doc->smLinks['p2l'][$this->doc->physicalStructure[$requestData['page']]])) {
                foreach ($this->doc->smLinks['p2l'][$this->doc->physicalStructure[$requestData['page']]] as $logId) {
                    $count = $this->doc->getStructureDepth($logId);
                    $ids[$count][] = $logId;
                }
            }
            ksort($ids);
            reset($ids);
            // Check if we should display all metadata up to the root.
            if ($this->settings['rootline'] == 1) {
                foreach ($ids as $id) {
                    foreach ($id as $sid) {
                        if ($useOriginalIiifManifestMetadata) {
                            $data = $this->doc->getManifestMetadata($sid, $this->settings['pages']);
                        } else {
                            $data = $this->doc->getMetadata($sid, $this->settings['pages']);
                        }
                        if (!empty($data)) {
                            $data['_id'] = $sid;
                            $metadata[] = $data;
                        }
                    }
                }
            } else {
                $id = array_pop($ids);
                if (is_array($id)) {
                    foreach ($id as $sid) {
                        if ($useOriginalIiifManifestMetadata) {
                            $data = $this->doc->getManifestMetadata($sid, $this->settings['pages']);
                        } else {
                            $data = $this->doc->getMetadata($sid, $this->settings['pages']);
                        }
                        if (!empty($data)) {
                            $data['_id'] = $sid;
                            $metadata[] = $data;
                        }
                    }
                }
            }
        }
        // Get titledata?
        if (empty($metadata) || ($this->settings['rootline'] == 1 && $metadata[0]['_id'] != $this->doc->toplevelId)) {
            $data = $useOriginalIiifManifestMetadata ? $this->doc->getManifestMetadata($this->doc->toplevelId, $this->settings['pages']) : $this->doc->getTitleData($this->settings['pages']);
            $data['_id'] = $this->doc->toplevelId;
            array_unshift($metadata, $data);
        }
        if (empty($metadata)) {
            $this->logger->warning('No metadata found for document with UID ' . $this->doc->uid);
            return '';
        }
        ksort($metadata);
        // Get hook objects.
        $this->hookObjects = Helper::getHookObjects($this->scriptRelPath);
        // Hook for getting a customized title bar (requested by SBB).
        foreach ($this->hookObjects as $hookObj) {
            if (method_exists($hookObj, 'main_customizeTitleBarGetCustomTemplate')) {
                $hookObj->main_customizeTitleBarGetCustomTemplate($this, $metadata);
            }
        }
        $this->printMetadata($metadata, $useOriginalIiifManifestMetadata);
    }

    /**
     * Prepares the metadata array for output
     *
     * @access protected
     *
     * @param array $metadataArray: The metadata array
     * @param bool $useOriginalIiifManifestMetadata: Output IIIF metadata as simple key/value pairs?
     *
     * @return string The metadata array ready for output
     */
    protected function printMetadata(array $metadataArray, $useOriginalIiifManifestMetadata = false)
    {
        // Save original data array.
        $cObjData = $this->cObj->data;
        // Get list of metadata to show.
        $metaList = [];
        if ($useOriginalIiifManifestMetadata) {
            if ($this->settings['iiifMetadataWrap']) {
                $iiifwrap = $this->parseTS($this->settings['iiifMetadataWrap']);
            } else {
                $iiifwrap['key.']['wrap'] = '<dt>|</dt>';
                $iiifwrap['value.']['required'] = 1;
                $iiifwrap['value.']['wrap'] = '<dd>|</dd>';
            }
            $iiifLink = [];
            $iiifLink['key.']['wrap'] = '<dt>|</dt>';
            $iiifLink['value.']['required'] = 1;
            $iiifLink['value.']['setContentToCurrent'] = 1;
            $iiifLink['value.']['typolink.']['parameter.']['current'] = 1;
            $iiifLink['value.']['typolink.']['forceAbsoluteUrl'] = !empty($this->settings['forceAbsoluteUrl']) ? 1 : 0;
            $iiifLink['value.']['typolink.']['forceAbsoluteUrl.']['scheme'] = !empty($this->settings['forceAbsoluteUrl']) && !empty($this->settings['forceAbsoluteUrlHttps']) ? 'https' : 'http';
            $iiifLink['value.']['wrap'] = '<dd>|</dd>';
            foreach ($metadataArray as $metadata) {
                foreach ($metadata as $key => $group) {
                    $markerArray['METADATA'] = '<span class="tx-dlf-metadata-group">' . $this->pi_getLL($key) . '</span>';
                    // Reset content object's data array.
                    $this->cObj->data = $cObjData;
                    if (!is_array($group)) {
                        if ($key == '_id') {
                            continue;
                        }
                        $this->cObj->data[$key] = $group;
                        if (
                            IRI::isAbsoluteIri($this->cObj->data[$key])
                            && (($scheme = (new IRI($this->cObj->data[$key]))->getScheme()) == 'http' || $scheme == 'https')
                        ) {
                            $field = $this->cObj->stdWrap('', $iiifLink['key.']);
                            $field .= $this->cObj->stdWrap($this->cObj->data[$key], $iiifLink['value.']);
                        } else {
                            $field = $this->cObj->stdWrap('', $iiifwrap['key.']);
                            $field .= $this->cObj->stdWrap($this->cObj->data[$key], $iiifwrap['value.']);
                        }
                        $markerArray['METADATA'] .= $this->cObj->stdWrap($field, $iiifwrap['all.']);
                    } else {
                        // Load all the metadata values into the content object's data array.
                        foreach ($group as $label => $value) {
                            if ($label == '_id') {
                                continue;
                            }
                            if (is_array($value)) {
                                $this->cObj->data[$label] = implode($this->settings['separator'], $value);
                            } else {
                                $this->cObj->data[$label] = $value;
                            }
                            if (IRI::isAbsoluteIri($this->cObj->data[$label]) && (($scheme = (new IRI($this->cObj->data[$label]))->getScheme()) == 'http' || $scheme == 'https')) {
                                $nolabel = $this->cObj->data[$label] == $label;
                                $field = $this->cObj->stdWrap($nolabel ? '' : htmlspecialchars($label), $iiifLink['key.']);
                                $field .= $this->cObj->stdWrap($this->cObj->data[$label], $iiifLink['value.']);
                            } else {
                                $field = $this->cObj->stdWrap(htmlspecialchars($label), $iiifwrap['key.']);
                                $field .= $this->cObj->stdWrap($this->cObj->data[$label], $iiifwrap['value.']);
                            }
                            $markerArray['METADATA'] .= $this->cObj->stdWrap($field, $iiifwrap['all.']);
                        }
                    }
                }
            }
        } else {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_metadata');
            $result = $queryBuilder
                ->select(
                    'tx_dlf_metadata.index_name AS index_name',
                    'tx_dlf_metadata.is_listed AS is_listed',
                    'tx_dlf_metadata.wrap AS wrap',
                    'tx_dlf_metadata.sys_language_uid AS sys_language_uid'
                )
                ->from('tx_dlf_metadata')
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->in('tx_dlf_metadata.sys_language_uid', [-1, 0]),
                            $queryBuilder->expr()->eq('tx_dlf_metadata.sys_language_uid', $GLOBALS['TSFE']->sys_language_uid)
                        ),
                        $queryBuilder->expr()->eq('tx_dlf_metadata.l18n_parent', 0)
                    ),
                    $queryBuilder->expr()->eq('tx_dlf_metadata.pid', intval($this->settings['pages']))
                )
                ->orderBy('tx_dlf_metadata.sorting')
                ->execute();
            while ($resArray = $result->fetch()) {
                if (is_array($resArray) && $resArray['sys_language_uid'] != $GLOBALS['TSFE']->sys_language_content && $GLOBALS['TSFE']->sys_language_contentOL) {
                    $resArray = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_dlf_metadata', $resArray, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);
                }
                if ($resArray) {
                    if ($this->settings['showFull'] || $resArray['is_listed']) {
                        $metaList[$resArray['index_name']] = [
                            'wrap' => $resArray['wrap'],
                            'label' => Helper::translate($resArray['index_name'], 'tx_dlf_metadata', $this->settings['pages'])
                        ];
                    }
                }
            }
            // Get list of collections to show.
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_dlf_collections');
            $collList = [];
            $result = $queryBuilder
                ->select('tx_dlf_collections.index_name AS index_name')
                ->from('tx_dlf_collections')
                ->where(
                    $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($this->settings['pages']))
                )
                ->execute();
            while ($resArray = $result->fetch()) {
                $collList[] = $resArray['index_name'];
            }
            // Parse the metadata arrays.
            foreach ($metadataArray as $metadata) {
                $markerArray['METADATA'] = '';
                // Reset content object's data array.
                $this->cObj->data = $cObjData;
                // Load all the metadata values into the content object's data array.
                foreach ($metadata as $index_name => $value) {
                    if (is_array($value)) {
                        $this->cObj->data[$index_name] = implode($this->settings['separator'], $value);
                    } else {
                        $this->cObj->data[$index_name] = $value;
                    }
                }
                // Process each metadate.
                foreach ($metaList as $index_name => $metaConf) {
                    $parsedValue = '';
                    $fieldwrap = $this->parseTS($metaConf['wrap']);
                    do {
                        $value = @array_shift($metadata[$index_name]);
                        if ($index_name == 'title') {
                            // Get title of parent document if needed.
                            if (empty($value) && $this->settings['getTitle'] && $this->doc->parentId) {
                                $superiorTitle = Document::getTitle($this->doc->parentId, true);
                                if (!empty($superiorTitle)) {
                                    $value = '[' . $superiorTitle . ']';
                                }
                            }
                            if (!empty($value)) {
                                $value = htmlspecialchars($value);
                                // Link title to pageview.
                                if ($this->settings['linkTitle'] && $metadata['_id']) {
                                    $details = $this->doc->getLogicalStructure($metadata['_id']);
                                    $uri = $this->uriBuilder->reset()
                                        ->setArguments([
                                            $this->prefixId => [
                                                'id' => $this->doc->uid,
                                                'page' => (!empty($details['points']) ? intval($details['points']) : 1)
                                            ]
                                        ])
                                        ->setTargetPageUid($this->settings['targetPid'])
                                        ->build();
                                    $value = '<a href="' . $uri . '">' . $value . '</a>';
                                }
                            }
                        } elseif ($index_name == 'owner' && !empty($value)) {
                            // Translate name of holding library.
                            $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_libraries', $this->settings['pages']));
                        } elseif ($index_name == 'type' && !empty($value)) {
                            // Translate document type.
                            $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_structures', $this->settings['pages']));
                        } elseif ($index_name == 'collection' && !empty($value)) {
                            // Check if collections isn't hidden.
                            if (in_array($value, $collList)) {
                                // Translate collection.
                                $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_collections', $this->settings['pages']));
                            } else {
                                $value = '';
                            }
                        } elseif ($index_name == 'language' && !empty($value)) {
                            // Translate ISO 639 language code.
                            $value = htmlspecialchars(Helper::getLanguageName($value));
                        } elseif (!empty($value)) {
                            // Sanitize value for output.
                            $value = htmlspecialchars($value);
                        }
                        // Hook for getting a customized value (requested by SBB).
                        foreach ($this->hookObjects as $hookObj) {
                            if (method_exists($hookObj, 'printMetadata_customizeMetadata')) {
                                $hookObj->printMetadata_customizeMetadata($value);
                            }
                        }
                        // $value might be empty for aggregation metadata fields including other "hidden" fields.
                        $value = $this->cObj->stdWrap($value, $fieldwrap['value.']);
                        if (!empty($value)) {
                            $parsedValue .= $value;
                        }
                    } while (is_array($metadata[$index_name]) && count($metadata[$index_name]) > 0);

                    if (!empty($parsedValue)) {
                        $field = $this->cObj->stdWrap(htmlspecialchars($metaConf['label']), $fieldwrap['key.']);
                        $field .= $parsedValue;
                        $markerArray['METADATA'] .= $this->cObj->stdWrap($field, $fieldwrap['all.']);
                    }
                }
            }
        }
        $this->view->assign('metadata', $markerArray['METADATA']);
    }

    // TODO: Needs to be placed in an abstract class (like before in AbstractPlugin)
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
        $parser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
        $parser->parse($string);
        return $parser->setup;
    }

    protected function pi_getLL($label)
    {
        return $GLOBALS['TSFE']->sL('LLL:EXT:dlf/Resources/Private/Language/Metadata.xml:' . $label);
    }

}