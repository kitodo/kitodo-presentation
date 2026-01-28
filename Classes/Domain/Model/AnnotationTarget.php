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

class AnnotationTarget
{
    /**
     * @var string
     */
    protected string $url;

    /**
     * @var string
     */
    protected string $objectId;

    /**
     * @var string
     */
    protected string $id;

    /**
     * @var string
     */
    protected string $rangeParameterName;

    /**
     * @var string
     */
    protected string $rangeValue;

    public function __construct(string $url)
    {
        $this->url = $url;

        $path = parse_url($url, PHP_URL_PATH);
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        list($objectId, $id) = explode('/', trim($path, '/'));
        list($rangeParameterName, $rangeValue) = explode('=', $fragment);

        $this->objectId = $objectId;
        $this->id = $id;
        $this->rangeParameterName = $rangeParameterName;
        $this->rangeValue = preg_replace('/\s+/', '', $rangeValue);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getObjectId(): string
    {
        return $this->objectId;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRangeParameterName(): string
    {
        return $this->rangeParameterName;
    }

    /**
     * @return string
     */
    public function getRangeValue(): string
    {
        return $this->rangeValue;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        if (empty($this->getObjectId())) {
            return false;
        }

        if (parse_url($this->getUrl(), PHP_URL_FRAGMENT)) {
            return !empty($this->getId()) && $this->isValidRange();
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isValidRange(): bool
    {
        if (empty($this->rangeParameterName) && empty($this->rangeValue)) {
            return true;
        } elseif ($this->isFacsimileRange()) {
            return preg_match("/^(\d+)(,\d+){3}?$/", $this->rangeValue) === 1;
        } elseif ($this->isAudioRange()) {
            return preg_match("/^(?:\d+(?:\.\d*)?|\.\d+){0,1}(?:,(?:\d+(?:\.\d*)?|\.\d+))*$/", $this->rangeValue) === 1;
        } elseif ($this->isScoreRange()) {
            return preg_match("/^((\d+|start|end|all|(\d+|start)(-(\d+|end)){0,1})+)(,(\d+|start|end|all|(\d+|start)(-(\d+|end)){0,1})+){0,}?$/", $this->rangeValue) === 1;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isScoreRange(): bool
    {
        return $this->getRangeParameterName() === 'measureRanges';
    }

    /**
     * @return bool
     */
    public function isAudioRange(): bool
    {
        return $this->getRangeParameterName() === 't';
    }

    /**
     * @return bool
     */
    public function isFacsimileRange(): bool
    {
        return $this->getRangeParameterName() === 'xywh';
    }
}
