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
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use Ubl\Iiif\Context\IRI;

/**
 * Controller class for the plugin 'Metadata'.
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class MetadataController extends AbstractController
{
    /**
     * @var Doc
     */
    private $doc;

    /**
     * @var CollectionRepository
     */
    protected $collectionRepository;

    /**
     * @param CollectionRepository $collectionRepository
     */
    public function injectCollectionRepository(CollectionRepository $collectionRepository)
    {
        $this->collectionRepository = $collectionRepository;
    }

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
     * @var StructureRepository
     */
    protected $structureRepository;

    /**
     * @param StructureRepository $structureRepository
     */
    public function injectStructureRepository(StructureRepository $structureRepository)
    {
        $this->structureRepository = $structureRepository;
    }

    /**
     * @return string|void
     */
    public function mainAction()
    {
        $this->cObj = $this->configurationManager->getContentObject();

        // Load current document.
        $this->loadDocument($this->requestData);
        if ($this->isDocMissing()) {
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

        $this->doc = $this->document->getDoc();

        $useOriginalIiifManifestMetadata = $this->settings['originalIiifMetadata'] == 1 && $this->doc instanceof IiifManifest;
        $metadata = $this->getMetadata();
        // Get titledata?
        if (empty($metadata) || ($this->settings['rootline'] == 1 && $metadata[0]['_id'] != $this->doc->toplevelId)) {
            $data = $useOriginalIiifManifestMetadata ? $this->doc->getManifestMetadata($this->doc->toplevelId, $this->settings['storagePid']) : $this->doc->getTitledata($this->settings['storagePid']);
            $data['_id'] = $this->doc->toplevelId;
            array_unshift($metadata, $data);
        }
        if (empty($metadata)) {
            $this->logger->warning('No metadata found for document with UID ' . $this->document->getUid());
            return '';
        }
        ksort($metadata);

        $this->printMetadata($metadata, $useOriginalIiifManifestMetadata);
    }

    /**
     * Prepares the metadata array for output
     *
     * @access protected
     *
     * @param array $metadata: The metadata array
     * @param bool $useOriginalIiifManifestMetadata: Output IIIF metadata as simple key/value pairs?
     *
     * @return string The metadata array ready for output
     */
    protected function printMetadata(array $metadata, $useOriginalIiifManifestMetadata = false)
    {
        if ($useOriginalIiifManifestMetadata) {
            $iiifData = $this->buildIiifData($metadata);
            $this->view->assign('useIiif', true);
            $this->view->assign('iiifData', $iiifData);
        } else {
            // findBySettings also sorts entries by the `sorting` field
            $metadataResult = $this->metadataRepository->findBySettings([
                'is_listed' => !$this->settings['showFull'],
            ]);

            // Collect raw metadata into an array that will be passed as data to cObj.
            // This lets metadata wraps reference (own or foreign) values via TypoScript "field".
            $metaCObjData = [];

            $buildUrl = [];
            $externalUrl = [];
            $i = 0;
            foreach ($metadata as $section) {
                $metaCObjData[$i] = [];

                foreach ($section as $name => $value) {
                    // NOTE: Labels are to be escaped in Fluid template

                    $metaCObjData[$i][$name] = is_array($value)
                        ? implode($this->settings['separator'], $value)
                        : $value;

                    if ($name == 'title') {
                        // Get title of parent document if needed.
                        if (empty(implode('', $value)) && $this->settings['getTitle'] && $this->document->getPartof()) {
                            $superiorTitle = Doc::getTitle($this->document->getPartof(), true);
                            if (!empty($superiorTitle)) {
                                $metadata[$i][$name] = ['[' . $superiorTitle . ']'];
                            }
                        }
                        if (!empty($value)) {
                            $metadata[$i][$name][0] = $metadata[$i][$name][0];
                            // Link title to pageview.
                            if ($this->settings['linkTitle'] && $section['_id']) {
                                $details = $this->doc->getLogicalStructure($section['_id']);
                                $buildUrl[$i][$name]['buildUrl'] = [
                                    'id' => $this->document->getUid(),
                                    'page' => (!empty($details['points']) ? intval($details['points']) : 1),
                                    'targetPid' => (!empty($this->settings['targetPid']) ? $this->settings['targetPid'] : 0),
                                ];
                            }
                        }
                    } elseif (($name == 'author' || $name == 'holder') && !empty($value) && !empty($value[0]['url'])) {
                        $externalUrl[$i][$name]['externalUrl'] = $value[0];
                    } elseif (($name == 'geonames' || $name == 'wikidata' || $name == 'wikipedia') && !empty($value)) {
                        $externalUrl[$i][$name]['externalUrl'] = [
                            'name' => $value[0],
                            'url' => $value[0]
                        ];
                    } elseif ($name == 'owner' && empty($value)) {
                        // no owner is found by metadata records --> take the one associated to the document
                        $library = $this->document->getOwner();
                        if ($library) {
                            $metadata[$i][$name][0] = $library->getLabel();
                        }
                    } elseif ($name == 'type' && !empty($value)) {
                        // Translate document type.
                        $structure = $this->structureRepository->findOneByIndexName($metadata[$i][$name][0]);
                        if ($structure) {
                            $metadata[$i][$name][0] = $structure->getLabel();
                        }
                    } elseif ($name == 'collection' && !empty($value)) {
                        // Check if collections isn't hidden.
                        $j = 0;
                        foreach ($value as $entry) {
                            $collection = $this->collectionRepository->findOneByIndexName($entry);
                            if ($collection) {
                                $metadata[$i][$name][$j] = $collection->getLabel() ? : '';
                                $j++;
                            }
                        }
                    } elseif ($name == 'language' && !empty($value)) {
                        // Translate ISO 639 language code.
                        foreach ($metadata[$i][$name] as &$langValue) {
                            $langValue = Helper::getLanguageName($langValue);
                        }
                    }

                    if (is_array($metadata[$i][$name])) {
                        $metadata[$i][$name] = array_values(array_filter($metadata[$i][$name], function($metadataValue)
                        {
                            return !empty($metadataValue);
                        }));
                    }
                }
                $i++;
            }

            $this->view->assign('buildUrl', $buildUrl);
            $this->view->assign('externalUrl', $externalUrl);
            $this->view->assign('documentMetadataSections', $metadata);
            $this->view->assign('configMetadata', $metadataResult);
            $this->view->assign('separator', $this->settings['separator']);
            $this->view->assign('metaCObjData', $metaCObjData);
        }
    }

    /**
     * Builds the IIIF data array from metadata array
     *
     * @access private
     *
     * @param array $metadata The metadata array
     *
     * @return array The IIIF data array ready for output
     */
    private function buildIiifData(array $metadata): array
    {
        $iiifData = [];

        foreach ($metadata as $row) {
            foreach ($row as $key => $group) {
                if ($key == '_id') {
                    continue;
                }

                if (!is_array($group)) {
                    $iiifData[$key] = $this->buildIiifDataGroup($key, $group);
                } else {
                    foreach ($group as $label => $value) {
                        if ($label == '_id') {
                            continue;
                        }
                        if (is_array($value)) {
                            $value = implode($this->settings['separator'], $value);
                        }

                        $iiifData[$key]['data'][] = $this->buildIiifDataGroup($label, $value);
                    }
                }
            }
        }

        return $iiifData;
    }

    /**
     * Builds the IIIF data array from label and value
     *
     * @access private
     *
     * @param string $label The label string
     * @param string $value The value string
     *
     * @return array The IIIF data array ready for output
     */
    private function buildIiifDataGroup(string $label, string $value): array
    {
        // NOTE: Labels are to be escaped in Fluid template
        if (IRI::isAbsoluteIri($value) && ($scheme = (new IRI($value))->getScheme()) == 'http' || $scheme == 'https') {
            //TODO: should really label be converted to empty string if equal to value?
            $label = $value == $label ? '' : $label;
            $buildUrl = true;
        } else {
            $buildUrl = false;
        }

        return [
            'label' => $label,
            'value' => $value,
            'buildUrl' => $buildUrl,
        ];
    }

    /**
     * Get metadata for given id array.
     *
     * @access private
     *
     * @return array metadata
     */
    private function getMetadata()
    {
        $metadata = [];
        if ($this->settings['rootline'] < 2) {
            // Get current structure's @ID.
            $ids = [];
            if (!empty($this->doc->physicalStructure[$this->requestData['page']]) && !empty($this->doc->smLinks['p2l'][$this->doc->physicalStructure[$this->requestData['page']]])) {
                foreach ($this->doc->smLinks['p2l'][$doc->physicalStructure[$this->requestData['page']]] as $logId) {
                    $count = $doc->getStructureDepth($logId);
                    $ids[$count][] = $logId;
                }
            }
            ksort($ids);
            reset($ids);
            // Check if we should display all metadata up to the root.
            if ($this->settings['rootline'] == 1) {
                foreach ($ids as $id) {
                    $metadata = $this->getMetadataForIds($id, $metadata);
                }
            } else {
                $id = array_pop($ids);
                if (is_array($id)) {
                    $metadata = $this->getMetadataForIds($id, $metadata);
                }
            }
        }
        return $metadata;
    }

    /**
     * Get metadata for given id array.
     *
     * @access private
     *
     * @param array $id: An array with ids
     * @param array $metadata: An array with metadata
     *
     * @return array metadata
     */
    private function getMetadataForIds($id, $metadata)
    {
        $useOriginalIiifManifestMetadata = $this->settings['originalIiifMetadata'] == 1 && $this->doc instanceof IiifManifest;
        foreach ($id as $sid) {
            if ($useOriginalIiifManifestMetadata) {
                $data = $this->doc->getManifestMetadata($sid, $this->settings['storagePid']);
            } else {
                $data = $this->doc->getMetadata($sid, $this->settings['storagePid']);
            }
            if (!empty($data)) {
                $data['_id'] = $sid;
                $metadata[] = $data;
            }
        }
        return $metadata;
    }
}
