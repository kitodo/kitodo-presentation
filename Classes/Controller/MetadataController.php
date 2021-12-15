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

use Kitodo\Dlf\Common\Doc;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\IiifManifest;
use Kitodo\Dlf\Domain\Model\Collection;
use Kitodo\Dlf\Domain\Model\Metadata;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Ubl\Iiif\Context\IRI;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;

class MetadataController extends AbstractController
{
    public $prefixId = 'tx_dlf';

    protected $metadataRepository;

    protected $collectionRepository;

    /**
     * @param CollectionRepository $collectionRepository
     */
    public function injectCollectionRepository(CollectionRepository $collectionRepository)
    {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * @param MetadataRepository $metadataRepository
     */
    public function injectMetadataRepository(MetadataRepository $metadataRepository)
    {
        $this->metadataRepository = $metadataRepository;
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
        if ($this->document === null) {
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
        $useOriginalIiifManifestMetadata = $this->settings['originalIiifMetadata'] == 1 && $this->document->getDoc() instanceof IiifManifest;
        $metadata = [];
        if ($this->settings['rootline'] < 2) {
            // Get current structure's @ID.
            $ids = [];
            if (!empty($this->document->getDoc()->physicalStructure[$requestData['page']]) && !empty($this->document->getDoc()->smLinks['p2l'][$this->document->getDoc()->physicalStructure[$requestData['page']]])) {
                foreach ($this->document->getDoc()->smLinks['p2l'][$this->document->getDoc()->physicalStructure[$requestData['page']]] as $logId) {
                    $count = $this->document->getDoc()->getStructureDepth($logId);
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
                            $data = $this->document->getDoc()->getManifestMetadata($sid, $this->settings['pages']);
                        } else {
                            $data = $this->document->getDoc()->getMetadata($sid, $this->settings['pages']);
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
                            $data = $this->document->getDoc()->getManifestMetadata($sid, $this->settings['pages']);
                        } else {
                            $data = $this->document->getDoc()->getMetadata($sid, $this->settings['pages']);
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
        if (empty($metadata) || ($this->settings['rootline'] == 1 && $metadata[0]['_id'] != $this->document->getDoc()->toplevelId)) {
            $data = $useOriginalIiifManifestMetadata ? $this->document->getDoc()->getManifestMetadata($this->document->getDoc()->toplevelId, $this->settings['pages']) : $this->document->getDoc()->getTitleData($this->settings['pages']);
            $data['_id'] = $this->document->getDoc()->toplevelId;
            array_unshift($metadata, $data);
        }
        if (empty($metadata)) {
            $this->logger->warning('No metadata found for document with UID ' . $this->document->getUid());
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
        if ($useOriginalIiifManifestMetadata) {
            $iiifData = [];
            foreach ($metadataArray as $metadata) {
                foreach ($metadata as $key => $group) {
                    if ($key == '_id') {
                        continue;
                    }
                    if (!is_array($group)) {
                        if (
                            IRI::isAbsoluteIri($group)
                            && (($scheme = (new IRI($group))->getScheme()) == 'http' || $scheme == 'https')
                        ) {
                            // Build link
                            $iiifData[$key] = [
                                'label' => $key,
                                'value' => $group,
                                'buildUrl' => true
                            ];
                        } else {
                            // Data output
                            $iiifData[$key] = [
                                'label' => $key,
                                'value' => $group,
                                'buildUrl' => false
                            ];
                        }
                    } else {
                        foreach ($group as $label => $value) {
                            if ($label == '_id') {
                                continue;
                            }
                            if (is_array($value)) {
                                $value = implode($this->settings['separator'], $value);
                            }
                            if (IRI::isAbsoluteIri($value) && (($scheme = (new IRI($value))->getScheme()) == 'http' || $scheme == 'https')) {
                                $nolabel = $value == $label;
                                $iiifData[$key]['data'][] = [
                                    'label' => $nolabel ? '' : htmlspecialchars($label),
                                    'value' => $value,
                                    'buildUrl' => true
                                ];
                            } else {
                                $iiifData[$key]['data'][] = [
                                    'label' => htmlspecialchars($label),
                                    'value' => $value,
                                    'buildUrl' => false
                                ];
                            }
                        }
                    }
                    $this->view->assign('useIiif', true);
                    $this->view->assign('iiifData', $iiifData);
                }
            }
        } else {

            $metadataResult = $this->metadataRepository->findAll();

            // Get collections to check if they are hidden
            $collections = $this->collectionRepository->getCollectionForMetadata($this->settings['pages']);

            $collList = [];
            /** @var Collection $collection */
            foreach ($collections as $collection) {
                $collList[] = $collection->getIndexName();
            }

            $buildUrl = [];
            $i = 0;
            foreach ($metadataArray as $metadataSection) {
                foreach ($metadataSection as $metadataName => $metadataValue) {
                    if ($metadataName == 'title') {
                        // Get title of parent document if needed.
                        if (empty($metadataValue) && $this->settings['getTitle'] && $this->document->getDoc()->parentId) {
                            $superiorTitle = Doc::getTitle($this->document->getPartof(), true);
                            if (!empty($superiorTitle)) {
                                $metadataArray[$i][$metadataName] = ['[' . $superiorTitle . ']'];
                            }
                        }
                        if (!empty($metadataValue)) {
                            $metadataArray[$i][$metadataName][0] = htmlspecialchars($metadataArray[$i][$metadataName][0]);
                            // Link title to pageview.
                            if ($this->settings['linkTitle'] && $metadataSection['_id']) {
                                $details = $this->document->getDoc()->getLogicalStructure($metadataSection['_id']);
                                $buildUrl[$i][$metadataName]['buildUrl'] = [
                                    'id' => $this->document->getUid(),
                                    'page' => (!empty($details['points']) ? intval($details['points']) : 1),
                                    'targetPid' => (!empty($this->settings['targetPid']) ? $this->settings['targetPid'] : 0)
                                ];
                            }
                        }
                    } elseif ($metadataName == 'owner' && !empty($metadataValue)) {
                        // Translate name of holding library.
                        $metadataArray[$i][$metadataName][0] = htmlspecialchars(Helper::translate($metadataArray[$i][$metadataName][0], 'tx_dlf_libraries', $this->settings['pages']));
                    } elseif ($metadataName == 'type' && !empty($metadataValue)) {
                        // Translate document type.
                        $metadataArray[$i][$metadataName][0] = htmlspecialchars(Helper::translate($metadataArray[$i][$metadataName][0], 'tx_dlf_structures', $this->settings['pages']));
                    } elseif ($metadataName == 'collection' && !empty($metadataValue)) {
                        // Check if collections isn't hidden.
                        $j = 0;
                        foreach ($metadataValue as $metadataEntry) {
                            if (in_array($metadataEntry, $collList)) {
                                // Translate collection.
                                $metadataArray[$i][$metadataName][$j] = htmlspecialchars(Helper::translate($metadataArray[$i][$metadataName][$j], 'tx_dlf_collections', $this->settings['pages']));
                            } else {
                                $metadataArray[$i][$metadataName][$j] = '';
                            }
                            $j++;
                        }
                    } elseif ($metadataName == 'language' && !empty($metadataValue)) {
                        // Translate ISO 639 language code.
                        $metadataArray[$i][$metadataName][0] = htmlspecialchars(Helper::getLanguageName($metadataArray[$i][$metadataName][0]));
                    } elseif (!empty($metadataValue)) {
                        // Sanitize value for output.
                        $metadataArray[$i][$metadataName][0] = htmlspecialchars($metadataArray[$i][$metadataName][0]);
                    }
                }
                $i++;
            }

            $this->view->assign('buildUrl', $buildUrl);
            $this->view->assign('documentMetadataSections', $metadataArray);
            $this->view->assign('configMetadata', $metadataResult);
            $this->view->assign('separator', $this->settings['separator']);

        }
    }
}
