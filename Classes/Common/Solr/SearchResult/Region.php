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

namespace Kitodo\Dlf\Common\Solr\SearchResult;

/**
 * Region class for the 'dlf' extension. It keeps region in which search phrase was found.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class Region
{

    /**
     * @access private
     * @var int The identifier of the region
     */
    private int $id;

    /**
     * @access private
     * @var int|null The identifier of the page in which text was found
     */
    private ?int $pageId;

    /**
     * @access private
     * @var int The horizontal beginning position of found region
     */
    private int $xBeginPosition;

    /**
     * @access private
     * @var int The horizontal ending position of found region
     */
    private int $xEndPosition;

    /**
     * @access private
     * @var int The vertical beginning position of found region
     */
    private int $yBeginPosition;

    /**
     * @access private
     * @var int The vertical ending position of found region
     */
    private int $yEndPosition;

    /**
     * @access private
     * @var int The width of found region
     */
    private int $width;

    /**
     * @access private
     * @var int The height of found region
     */
    private int $height;

    /**
     * @access private
     * @var string The text of found region
     */
    private string $text;

    /**
     * The constructor for region.
     *
     * @access public
     *
     * @param int $id: Id of found region properties
     * @param array $region: Array of found region properties
     *
     * @return void
     */
    public function __construct(int $id, array $region)
    {
        $this->id = $id;
        $this->pageId = $region['pageIdx'] ?? null;
        $this->xBeginPosition = $region['ulx'];
        $this->xEndPosition = $region['lrx'];
        $this->yBeginPosition = $region['uly'];
        $this->yEndPosition = $region['lry'];
        $this->width = $region['lrx'] - $region['ulx'];
        $this->height = $region['lry'] - $region['uly'];
        $this->text = $region['text'];
    }

    /**
     * Get the region's identifier.
     *
     * @access public
     *
     * @return int The region's identifier
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the region's page identifier.
     *
     * @access public
     *
     * @return int|null The region's page identifier
     */
    public function getPageId(): ?int
    {
        return $this->pageId;
    }

    /**
     * Get the region's horizontal beginning position.
     *
     * @access public
     *
     * @return int The region's horizontal beginning position
     */
    public function getXBeginPosition(): int
    {
        return $this->xBeginPosition;
    }

    /**
     * Get the region's horizontal ending position.
     *
     * @access public
     *
     * @return int The region's horizontal ending position
     */
    public function getXEndPosition(): int
    {
        return $this->xEndPosition;
    }

    /**
     * Get the region's vertical beginning position.
     *
     * @access public
     *
     * @return int The region's vertical beginning position
     */
    public function getYBeginPosition(): int
    {
        return $this->yBeginPosition;
    }

    /**
     * Get the region's vertical ending position.
     *
     * @access public
     *
     * @return int The region's vertical ending position
     */
    public function getYEndPosition(): int
    {
        return $this->yEndPosition;
    }

    /**
     * Get the region's width.
     *
     * @access public
     *
     * @return int The region's width
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Get the region's height.
     *
     * @access public
     *
     * @return int The region's height
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Get the region's text.
     *
     * @access public
     *
     * @return string The region's text
     */
    public function getText(): string
    {
        return $this->text;
    }
}
