<?php

namespace Kitodo\Dlf\Domain\Model;

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

class Annotation
{
    /**
     * The complete data of the annotation
     *
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $targetPages;


    /**
     * @param array $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Returns the full data of the annotation
     *
     * @return array
     */
    public function getRawData()
    {
        return $this->data;
    }

    /**
     * Gets the annotation id
     *
     * @return string
     */
    public function getId()
    {
        return $this->data['id'] ?? '';
    }

    /**
     * Gets the annotation title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->data['title'] ?? '';
    }

    /**
     * Gets the annotation body data
     *
     * @return array
     */
    public function getBody()
    {
        $body = $this->data['body'] ?? '';

        if (is_array($body)) {
            return $body;
        }

        return [$body];
    }

    /**
     * Gets the name of the annotation creator
     * @return string
     */
    public function getCreatorName()
    {
        return $this->data['creator']['displayName'] ?? '';
    }

    /**
     * Gets the creation date of the annotation
     * @return string
     */
    public function getCreated()
    {
        return $this->data['created'] ?? '';
    }

    /**
     * Gets the modification date of the annotation
     * @return string
     */
    public function getModified()
    {
        return $this->data['modified'] ?? '';
    }

    /**
     * Gets the targets
     *
     * @return AnnotationTarget[]
     */
    public function getTargets()
    {
        if (is_string($this->data['target'])) {
            return [new AnnotationTarget($this->data['target'])];
        }

        $annotationTargets = [];
        foreach ($this->data['target'] as $target) {
            $annotationTargets[] = new AnnotationTarget($target);
        }

        return $annotationTargets;
    }

    /**
     * Sets the target pages for which the annotation is relevant
     *
     * @param array $targetPages
     * @return void
     */
    public function setTargetPages($targetPages)
    {
        $this->targetPages = $targetPages;
    }

    /**
     * Gets the target pages for which the annotation is relevant
     *
     * @return array
     */
    public function getTargetPages()
    {
        return $this->targetPages;
    }

    /**
     * Gets the page numbers for which the annotation is relevant
     *
     * @return array
     */
    public function getPageNumbers()
    {
        $pages = [];
        if (is_array($this->targetPages)) {
            foreach ($this->targetPages as $target) {
                $pages = array_merge($pages, $target['pages']);
            }
        }

        return $pages;
    }

    /**
     * Gets the annotation targets ordered by page numbers
     *
     * @return array
     */
    public function getPageTargets()
    {
        $pageTargets = [];
        if (is_array($this->targetPages)) {
            foreach ($this->targetPages as $target) {
                foreach ($target['pages'] as $page) {
                    $pageTargets[$page][$target['target']->getUrl()] = $target['target'];
                }
            }
        }

        return $pageTargets;
    }

    /**
     * Gets the audio ranges from the annotation targets ordered by page number
     *
     * @return array
     */
    public function getPageAudioRanges()
    {
        $ranges = [];
        if (is_array($this->getPageTargets())) {
            foreach ($this->getPageTargets() as $pageNumber => $targets) {
                foreach ($targets as $target) {
                    if ($target->isValid() && $target->isAudioRange()) {
                        $ranges[$pageNumber][] = $target->getRangeValue();
                    }
                }
            }
        }
        return $ranges;
    }

    /**
     * Gets the score ranges from the annotation targets ordered by page number
     *
     * @return array
     */
    public function getPageScoreRanges()
    {
        $ranges = [];
        if (is_array($this->getPageTargets())) {
            foreach ($this->getPageTargets() as $pageNumber => $targets) {
                foreach ($targets as $target) {
                    if ($target->isValid() && $target->isScoreRange()) {
                        $ranges[$pageNumber][] = $target->getRangeValue();
                    }
                }
            }
        }
        return $ranges;
    }

    /**
     * Gets the facsimile ranges from the annotation targets ordered by page number
     *
     * @return array
     */
    public function getPageFacsimileRanges()
    {
        $ranges = [];
        if (is_array($this->getPageTargets())) {
            foreach ($this->getPageTargets() as $pageNumber => $targets) {
                foreach ($targets as $target) {
                    if ($target->isValid() && $target->isFacsimileRange()) {
                        $ranges[$pageNumber][] = $target->getRangeValue();
                    }
                }
            }
        }

        return $ranges;
    }

    /**
     * Returns if the annotation is relevant for verovio
     *
     * @return bool
     */
    public function isVerovioRelevant()
    {
        foreach ($this->targetPages as $target) {
            if (array_key_exists('verovioRelevant', $target) && $target['verovioRelevant']) {
                    return true;
            }
        }

        return false;
    }
}
