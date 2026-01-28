<?php

namespace Kitodo\Dlf\Common;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

class AnnotationRequest
{
    /**
     * @var string
     */
    protected string $apiUrl = '';

    /**
     * @param string $apiUrl The url of the annotation server api.
     */
    public function __construct(string $apiUrl)
    {
        $this->apiUrl =  trim($apiUrl, "/ ");
    }


    /**
     * Requests the annotation server
     *
     * @access protected
     *
     * @param string $url The annotation request url.
     *
     * @return mixed[] Array of annotation data
     */
    protected function requestAnnotations(string $url) : array
    {
        $jsonld = Helper::getUrl($url);

        if ($jsonld) {
            $annotationData = json_decode($jsonld, true);

            if ($annotationData) {
                return $annotationData;
            }
        }

        return [];
    }

    /**
     * Returns all annotations of a document.
     *
     * @access public
     *
     * @param string $id Document id (purl)
     *
     * @return mixed[] Array of annotations
     */
    public function getAll(string $id): array
    {
        $annotations = [];

        $annotationData = $this->requestAnnotations($this->apiUrl . '?target=' . urlencode($id . '/*'));

        if (array_key_exists('first', $annotationData)) {
            $annotationPageData = $annotationData['first'];
            $annotations = array_merge($annotations, $annotationPageData["items"]);

            while (array_key_exists('next', $annotationPageData)) {
                $annotationPageData = $this->requestAnnotations($annotationPageData['next']);
                if (array_key_exists('items', $annotationPageData)) {
                    $annotations = array_merge($annotations, $annotationPageData["items"]);
                }
            }
        }

        return $annotations;
    }
}
