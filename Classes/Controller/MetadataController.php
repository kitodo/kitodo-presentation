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

use Kitodo\Dlf\Common\AbstractDocument;
use Kitodo\Dlf\Common\Helper;
use Kitodo\Dlf\Common\IiifManifest;
use Kitodo\Dlf\Domain\Repository\CollectionRepository;
use Kitodo\Dlf\Domain\Repository\MetadataRepository;
use Kitodo\Dlf\Domain\Repository\StructureRepository;
use Psr\Http\Message\ResponseInterface;
use Ubl\Iiif\Context\IRI;

/**
 * Controller class for the plugin 'Metadata'.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class MetadataController extends AbstractController
{
    /**
     * @access private
     * @var AbstractDocument
     */
    private $currentDocument;

    /**
     * @access private
     * @var bool
     */
    private $useOriginalIiifManifestMetadata;

    /**
     * @access protected
     * @var CollectionRepository
     */
    protected CollectionRepository $collectionRepository;

    /**
     * @access public
     *
     * @param CollectionRepository $collectionRepository
     *
     * @return void
     */
    public function injectCollectionRepository(CollectionRepository $collectionRepository): void
    {
        $this->collectionRepository = $collectionRepository;
    }

    /**
     * @access protected
     * @var MetadataRepository
     */
    protected MetadataRepository $metadataRepository;

    /**
     * @access public
     *
     * @param MetadataRepository $metadataRepository
     *
     * @return void
     */
    public function injectMetadataRepository(MetadataRepository $metadataRepository): void
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * @access protected
     * @var StructureRepository
     */
    protected StructureRepository $structureRepository;

    /**
     * @access public
     *
     * @param StructureRepository $structureRepository
     *
     * @return void
     */
    public function injectStructureRepository(StructureRepository $structureRepository): void
    {
        $this->structureRepository = $structureRepository;
    }

    /**
     * @access public
     *
     * @return ResponseInterface the response
     */
    public function mainAction(): ResponseInterface
    {
        // Load current document.
        $this->loadDocument();
        if ($this->isDocMissing()) {
            // Quit without doing anything if required variables are not set.
            return $this->htmlResponse();
        }

        $this->setPage();

        $this->currentDocument = $this->document->getCurrentDocument();
        $this->useOriginalIiifManifestMetadata = $this->settings['originalIiifMetadata'] == 1 && $this->currentDocument instanceof IiifManifest;

        $metadata = $this->getMetadata();
        $topLevelId = $this->currentDocument->toplevelId;
        // Get toplevel metadata?
        if (!$metadata || ($this->settings['rootline'] == 1 && $metadata[0]['_id'] != $topLevelId)) {
            $data = [];
            if ($this->useOriginalIiifManifestMetadata) {
                // @phpstan-ignore-next-line
                $data = $this->currentDocument->getManifestMetadata($topLevelId, $this->settings['storagePid']);
            } else {
                $data = $this->currentDocument->getToplevelMetadata($this->settings['storagePid']);
            }
            $data['_id'] = $topLevelId;
            array_unshift($metadata, $data);
        }

        if (empty(array_filter($metadata))) {
            $this->logger->warning('No metadata found for document with UID ' . $this->document->getUid());
            return $this->htmlResponse();
        }
        ksort($metadata);

        $this->printMetadata($metadata);

        return $this->htmlResponse();
    }

    /**
     * Prepares the metadata array for output
     *
     * @access protected
     *
     * @param array $metadata The metadata array
     *
     * @return void
     */
    protected function printMetadata(array $metadata): void
    {
        if ($this->useOriginalIiifManifestMetadata) {
            $this->view->assign('useIiif', true);
            $this->view->assign('iiifData', $this->buildIiifData($metadata));
        } else {
            // findBySettings also sorts entries by the `sorting` field
            $metadataResult = $this->metadataRepository->findBySettings(
                [
                    'is_listed' => !$this->settings['showFull'],
                ]
            );

            foreach ($metadata as $sectionKey => $sectionValue) {
                foreach ($sectionValue as $metadataName => $metadataValue) {
                    $this->replaceMetadataOfSection($sectionKey, $metadataName, $metadataValue, $metadata);
                }
            }

            $metadata = $this->removeEmptyEntries($metadata);

            $this->view->assign('buildUrl', $this->buildUrlFromMetadata($metadata));
            $this->view->assign('externalUrl', $this->buildExternalUrlFromMetadata($metadata));
            $this->view->assign('documentMetadataSections', $metadata);
            $this->view->assign('configMetadata', $metadataResult);
            $this->view->assign('separator', $this->settings['separator']);
            $this->view->assign('metaConfigObjectData', $this->buildMetaConfigObjectData($metadata));
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
        $scheme = (new IRI($value))->getScheme();
        if (IRI::isAbsoluteIri($value) && ($scheme == 'http' || $scheme == 'https')) {
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
     * Collects raw metadata into an array that will be passed as data to cObj.
     * This lets metadata wraps reference (own or foreign) values via TypoScript "field".
     *
     * @access private
     *
     * @param array $metadata The metadata array
     *
     * @return array The raw metadata array ready for output
     */
    private function buildMetaConfigObjectData(array $metadata): array
    {
        $metaConfigObjectData = [];

        foreach ($metadata as $i => $section) {
            $metaConfigObjectData[$i] = [];

            foreach ($section as $name => $value) {
                $metaConfigObjectData[$i][$name] = is_array($value)
                    ? implode($this->settings['separator'], $value)
                    : $value;
            }
        }

        return $metaConfigObjectData;
    }

    /**
     * Builds URLs array for given metadata array.
     *
     * @access private
     *
     * @param array $metadata The metadata array
     *
     * @return array URLs
     */
    private function buildUrlFromMetadata(array $metadata): array
    {
        $buildUrl = [];

        foreach ($metadata as $i => $section) {
            if ($this->settings['linkTitle'] && $section['_id'] && isset($section['title']) && !empty($section['title'])) {
                $details = $this->currentDocument->getLogicalStructure($section['_id'][0]);
                $buildUrl[$i]['title'] = [
                    'id' => $this->document->getUid(),
                    'page' => (!empty($details['points']) ? (int) $details['points'] : 1),
                    'targetPid' => (!empty($this->settings['targetPid']) ? $this->settings['targetPid'] : 0),
                ];
            }
        }

        return $buildUrl;
    }

    /**
     * Builds external URLs array for given metadata array.
     *
     * @access private
     *
     * @param array $metadata The metadata array
     *
     * @return array external URLs
     */
    private function buildExternalUrlFromMetadata(array $metadata): array
    {
        $externalUrl = [];

        foreach ($metadata as $i => $section) {
            foreach ($section as $name => $value) {
                if (($name == 'author' || $name == 'holder') && !empty($value) && !empty($value[0]['url'])) {
                    $externalUrl[$i][$name]['externalUrl'] = $value[0];
                } elseif (($name == 'geonames' || $name == 'wikidata' || $name == 'wikipedia') && !empty($value)) {
                    $externalUrl[$i][$name]['externalUrl'] = [
                        'name' => $value[0],
                        'url' => $value[0]
                    ];
                }
            }
        }

        return $externalUrl;
    }

    /**
     * Replace metadata of section.
     *
     * @access private
     *
     * @param int $i The index of metadata array
     * @param string $name The name of section in metadata array
     * @param mixed $value The value of section in metadata array
     * @param array $metadata The metadata array passed as reference
     *
     * @return void
     */
    private function replaceMetadataOfSection(int $i, string $name, $value, array &$metadata): void
    {
        // if the value has subentries, do not replace the values
        if (is_array($value) && !empty($value) && is_array($value[0])) {
            return;
        }

        if ($name == 'title') {
            // Get title of parent document if needed.
            $this->parseParentTitle($i, $value, $metadata);
        } elseif ($name == 'owner' && empty($value)) {
            // no owner is found by metadata records --> take the one associated to the document
            $this->parseOwner($i, $metadata);
        } elseif ($name == 'type' && !empty($value)) {
            // Translate document type.
            $this->parseType($i, $metadata);
        } elseif ($name == 'collection' && !empty($value)) {
            // Check if collections isn't hidden.
            $this->parseCollections($i, $value, $metadata);
        } elseif ($name == 'language' && !empty($value)) {
            // Translate ISO 639 language code.
            foreach ($metadata[$i][$name] as &$langValue) {
                $langValue = Helper::getLanguageName($langValue);
            }
        }
    }

    /**
     * Parse title of parent document if needed.
     *
     * @access private
     *
     * @param int $i The index of metadata array
     * @param mixed $value The value of section in metadata array
     * @param array $metadata The metadata array passed as reference
     *
     * @return void
     */
    private function parseParentTitle(int $i, $value, array &$metadata) : void
    {
        if (empty(implode('', $value)) && $this->settings['getTitle'] && $this->document->getPartof()) {
            $superiorTitle = AbstractDocument::getTitle($this->document->getPartof(), true);
            if (!empty($superiorTitle)) {
                $metadata[$i]['title'] = ['[' . $superiorTitle . ']'];
            }
        }
    }

    /**
     * Parse owner if no owner is found by metadata records. Take the one associated to the document.
     *
     * @access private
     *
     * @param int $i The index of metadata array
     * @param array $metadata The metadata array passed as reference
     *
     * @return void
     */
    private function parseOwner(int $i, array &$metadata) : void
    {
        $library = $this->document->getOwner();
        if ($library) {
            $metadata[$i]['owner'][0] = $library->getLabel();
        }
    }

    /**
     * Parse type - translate document type.
     *
     * @access private
     *
     * @param int $i The index of metadata array
     * @param array $metadata The metadata array passed as reference
     *
     * @return void
     */
    private function parseType(int $i, array &$metadata) : void
    {
        $structure = $this->structureRepository->findOneByIndexName($metadata[$i]['type'][0]);
        if ($structure) {
            $metadata[$i]['type'][0] = $structure->getLabel();
        }
    }

    /**
     * Parse collections - check if collections isn't hidden.
     *
     * @access private
     *
     * @param int $i The index of metadata array
     * @param mixed $value The value of section in metadata array
     * @param array $metadata The metadata array passed as reference
     *
     * @return void
     */
    private function parseCollections(int $i, $value, array &$metadata) : void
    {
        $j = 0;
        foreach ($value as $entry) {
            $collection = $this->collectionRepository->findOneByIndexName($entry);
            if ($collection) {
                $metadata[$i]['collection'][$j] = $collection->getLabel() ? : '';
                $j++;
            }
        }
    }

    /**
     * Get metadata for given id array.
     *
     * @access private
     *
     * @return array metadata
     */
    private function getMetadata(): array
    {
        $metadata = [];
        if ($this->settings['rootline'] < 2) {
            // Get current structure's @ID.
            $ids = [];
            if (!empty($this->currentDocument->physicalStructure) && isset($this->requestData['page'])) {
                $page = $this->currentDocument->physicalStructure[$this->requestData['page']];
                if (!empty($page) && !empty($this->currentDocument->smLinks['p2l'][$page])) {
                    foreach ($this->currentDocument->smLinks['p2l'][$page] as $logId) {
                        $count = $this->currentDocument->getStructureDepth($logId);
                        $ids[$count][] = $logId;
                    }
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
     * @param array $id An array with ids
     * @param array $metadata An array with metadata
     *
     * @return array metadata
     */
    private function getMetadataForIds(array $id, array $metadata): array
    {
        foreach ($id as $sid) {
            if ($this->useOriginalIiifManifestMetadata) {
                // @phpstan-ignore-next-line
                $data = $this->currentDocument->getManifestMetadata($sid, $this->settings['storagePid']);
            } else {
                $data = $this->currentDocument->getMetadata($sid, $this->settings['storagePid']);
            }
            if (!empty($data)) {
                $data['_id'] = $sid;
                $metadata[] = $data;
            }
        }
        return $metadata;
    }

    /**
     * Recursively remove empty entries.
     *
     * @param $metadata
     * @return array
     */
    private function removeEmptyEntries($metadata): array
    {
        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $metadata[$key] = $this->removeEmptyEntries($value);
            }

            if (empty($metadata[$key])) {
                unset($metadata[$key]);
            }
        }
        return $metadata;
    }
}
