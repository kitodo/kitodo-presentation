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
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * @var mixed[]
     */
    protected array $targetPages;


    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Returns the full data of the annotation
     *
     * @access public
     *
     * @return array<string, mixed>
     */
    public function getRawData(): array
    {
        return $this->data;
    }

    /**
     * Gets the annotation id
     *
     * @access public
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->data['id'] ?? '';
    }

    /**
     * Gets the annotation title
     *
     * @access public
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->data['title'] ?? '';
    }

    /**
     * Gets the annotation body data
     *
     * @access public
     *
     * @return mixed[]
     */
    public function getBody(): array
    {
        $body = $this->data['body'] ?? '';

        if (is_array($body)) {
            return $body;
        }

        return [$body];
    }

    /**
     * Gets the name of the annotation creator
     *
     * @access public
     *
     * @return string
     */
    public function getCreatorName(): string
    {
        return $this->data['creator']['displayName'] ?? '';
    }

    /**
     * Gets the creation date of the annotation
     *
     * @access public
     *
     * @return string
     */
    public function getCreated(): string
    {
        return $this->data['created'] ?? '';
    }

    /**
     * Gets the modification date of the annotation
     *
     * @access public
     *
     * @return string
     */
    public function getModified(): string
    {
        return $this->data['modified'] ?? '';
    }

    /**
     * Gets the targets
     *
     * @access public
     *
     * @return AnnotationTarget[]
     */
    public function getTargets(): array
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
     * @access public
     *
     * @param mixed[] $targetPages
     *
     * @return void
     */
    public function setTargetPages(array $targetPages): void
    {
        $this->targetPages = $targetPages;
    }

    /**
     * Gets the target pages for which the annotation is relevant
     *
     * @access public
     *
     * @return mixed[]
     */
    public function getTargetPages(): array
    {
        return $this->targetPages;
    }

    /**
     * Gets the page numbers for which the annotation is relevant
     *
     * @access public
     *
     * @return mixed[]
     */
    public function getPageNumbers(): array
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
     * @access public
     *
     * @return mixed[]
     */
    public function getPageTargets(): array
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
     * @access public
     *
     * @return mixed[]
     */
    public function getPageAudioRanges(): array
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
     * @access public
     *
     * @return mixed[]
     */
    public function getPageScoreRanges(): array
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
     * @access public
     *
     * @return mixed[]
     */
    public function getPageFacsimileRanges(): array
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
     * @access public
     *
     * @return bool
     */
    public function isVerovioRelevant(): bool
    {
        foreach ($this->targetPages as $target) {
            if (array_key_exists('verovioRelevant', $target) && $target['verovioRelevant']) {
                    return true;
            }
        }

        return false;
    }
}
