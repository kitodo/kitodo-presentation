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
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\IiifManifest;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Ubl\Iiif\Context\IRI;

/**
 * Plugin 'Metadata' for the 'dlf' extension
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @author Siegfried Schweizer <siegfried.schweizer@sbb.spk-berlin.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Metadata extends \Kitodo\Dlf\Common\AbstractPlugin
{
    public $scriptRelPath = 'Classes/Plugin/Metadata.php';

    /**
     * This holds the hook objects
     *
     * @var array
     * @access protected
     */
    protected $hookObjects = [];

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
        // Turn cache on.
        $this->setCache(true);
        // Load current document.
        $this->loadDocument();
        if ($this->doc === null) {
            // Quit without doing anything if required variables are not set.
            return $content;
        } else {
            // Set default values if not set.
            if (!isset($this->conf['rootline'])) {
                $this->conf['rootline'] = 0;
            }
            if (!isset($this->conf['originalIiifMetadata'])) {
                $this->conf['originalIiifMetadata'] = 0;
            }
            if (!isset($this->conf['displayIiifDescription'])) {
                $this->conf['displayIiifDescription'] = 1;
            }
            if (!isset($this->conf['displayIiifRights'])) {
                $this->conf['displayIiifRights'] = 1;
            }
            if (!isset($this->conf['displayIiifLinks'])) {
                $this->conf['displayIiifLinks'] = 1;
            }
        }
        $useOriginalIiifManifestMetadata = $this->conf['originalIiifMetadata'] == 1 && $this->doc instanceof IiifManifest;
        $metadata = [];
        if ($this->conf['rootline'] < 2) {
            // Get current structure's @ID.
            $ids = [];
            if (!empty($this->doc->physicalStructure[$this->piVars['page']]) && !empty($this->doc->smLinks['p2l'][$this->doc->physicalStructure[$this->piVars['page']]])) {
                foreach ($this->doc->smLinks['p2l'][$this->doc->physicalStructure[$this->piVars['page']]] as $logId) {
                    $count = $this->doc->getStructureDepth($logId);
                    $ids[$count][] = $logId;
                }
            }
            ksort($ids);
            reset($ids);
            // Check if we should display all metadata up to the root.
            if ($this->conf['rootline'] == 1) {
                foreach ($ids as $id) {
                    foreach ($id as $sid) {
                        if ($useOriginalIiifManifestMetadata) {
                            $data = $this->doc->getManifestMetadata($sid, $this->conf['pages']);
                        } else {
                            $data = $this->doc->getMetadata($sid, $this->conf['pages']);
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
                            $data = $this->doc->getManifestMetadata($sid, $this->conf['pages']);
                        } else {
                            $data = $this->doc->getMetadata($sid, $this->conf['pages']);
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
        if (empty($metadata) || ($this->conf['rootline'] == 1 && $metadata[0]['_id'] != $this->doc->toplevelId)) {
            $data = $useOriginalIiifManifestMetadata ? $this->doc->getManifestMetadata($this->doc->toplevelId, $this->conf['pages']) : $this->doc->getTitleData($this->conf['pages']);
            $data['_id'] = $this->doc->toplevelId;
            array_unshift($metadata, $data);
        }
        if (empty($metadata)) {
            Helper::devLog('No metadata found for document with UID ' . $this->doc->uid, DEVLOG_SEVERITY_WARNING);
            return $content;
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
        $content .= $this->printMetadata($metadata, $useOriginalIiifManifestMetadata);
        return $this->pi_wrapInBaseClass($content);
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
        // Load template file.
        $this->getTemplate();
        $output = '';
        $subpart['block'] = $this->templateService->getSubpart($this->template, '###BLOCK###');
        // Save original data array.
        $cObjData = $this->cObj->data;
        // Get list of metadata to show.
        $metaList = [];
        if ($useOriginalIiifManifestMetadata) {
            if ($this->conf['iiifMetadataWrap']) {
                $iiifwrap = $this->parseTS($this->conf['iiifMetadataWrap']);
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
            $iiifLink['value.']['typolink.']['forceAbsoluteUrl'] = !empty($this->conf['forceAbsoluteUrl']) ? 1 : 0;
            $iiifLink['value.']['typolink.']['forceAbsoluteUrl.']['scheme'] = !empty($this->conf['forceAbsoluteUrl']) && !empty($this->conf['forceAbsoluteUrlHttps']) ? 'https' : 'http';
            $iiifLink['value.']['wrap'] = '<dd>|</dd>';
            foreach ($metadataArray as $metadata) {
                foreach ($metadata as $key => $group) {
                    $markerArray['###METADATA###'] = '<span class="tx-dlf-metadata-group">' . $this->pi_getLL($key) . '</span>';
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
                        $markerArray['###METADATA###'] .= $this->cObj->stdWrap($field, $iiifwrap['all.']);
                    } else {
                        // Load all the metadata values into the content object's data array.
                        foreach ($group as $label => $value) {
                            if ($label == '_id') {
                                continue;
                            }
                            if (is_array($value)) {
                                $this->cObj->data[$label] = implode($this->conf['separator'], $value);
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
                            $markerArray['###METADATA###'] .= $this->cObj->stdWrap($field, $iiifwrap['all.']);
                        }
                    }
                    $output .= $this->templateService->substituteMarkerArray($subpart['block'], $markerArray);
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
                    $queryBuilder->expr()->eq('tx_dlf_metadata.pid', intval($this->conf['pages']))
                )
                ->orderBy('tx_dlf_metadata.sorting')
                ->execute();
            while ($resArray = $result->fetch()) {
                if (is_array($resArray) && $resArray['sys_language_uid'] != $GLOBALS['TSFE']->sys_language_content && $GLOBALS['TSFE']->sys_language_contentOL) {
                    $resArray = $GLOBALS['TSFE']->sys_page->getRecordOverlay('tx_dlf_metadata', $resArray, $GLOBALS['TSFE']->sys_language_content, $GLOBALS['TSFE']->sys_language_contentOL);
                }
                if ($resArray) {
                    if ($this->conf['showFull'] || $resArray['is_listed']) {
                        $metaList[$resArray['index_name']] = [
                            'wrap' => $resArray['wrap'],
                            'label' => Helper::translate($resArray['index_name'], 'tx_dlf_metadata', $this->conf['pages'])
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
                    $queryBuilder->expr()->eq('tx_dlf_collections.pid', intval($this->conf['pages']))
                )
                ->execute();
            while ($resArray = $result->fetch()) {
                $collList[] = $resArray['index_name'];
            }
            // Parse the metadata arrays.
            foreach ($metadataArray as $metadata) {
                $markerArray['###METADATA###'] = '';
                // Reset content object's data array.
                $this->cObj->data = $cObjData;
                // Load all the metadata values into the content object's data array.
                foreach ($metadata as $index_name => $value) {
                    if (is_array($value)) {
                        $this->cObj->data[$index_name] = implode($this->conf['separator'], $value);
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
                            if (empty($value) && $this->conf['getTitle'] && $this->doc->parentId) {
                                $superiorTitle = Document::getTitle($this->doc->parentId, true);
                                if (!empty($superiorTitle)) {
                                    $value = '[' . $superiorTitle . ']';
                                }
                            }
                            if (!empty($value)) {
                                $value = htmlspecialchars($value);
                                // Link title to pageview.
                                if ($this->conf['linkTitle'] && $metadata['_id']) {
                                    $details = $this->doc->getLogicalStructure($metadata['_id']);
                                    $value = $this->pi_linkTP($value, [$this->prefixId => ['id' => $this->doc->uid, 'page' => (!empty($details['points']) ? intval($details['points']) : 1)]], true, $this->conf['targetPid']);
                                }
                            }
                        } elseif ($index_name == 'owner' && !empty($value)) {
                            // Translate name of holding library.
                            $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_libraries', $this->conf['pages']));
                        } elseif ($index_name == 'type' && !empty($value)) {
                            // Translate document type.
                            $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_structures', $this->conf['pages']));
                        } elseif ($index_name == 'collection' && !empty($value)) {
                            // Check if collections isn't hidden.
                            if (in_array($value, $collList)) {
                                // Translate collection.
                                $value = htmlspecialchars(Helper::translate($value, 'tx_dlf_collections', $this->conf['pages']));
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
                        $markerArray['###METADATA###'] .= $this->cObj->stdWrap($field, $fieldwrap['all.']);
                    }
                }
                $output .= $this->templateService->substituteMarkerArray($subpart['block'], $markerArray);
            }
        }
        return $this->templateService->substituteSubpart($this->template, '###BLOCK###', $output, true);
    }
}
