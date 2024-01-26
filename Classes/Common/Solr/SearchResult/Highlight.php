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
 * Highlight class for the 'dlf' extension. It keeps highlight for found search phrase.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class Highlight
{

    /**
     * @access private
     * @var string The identifier in form 'w_h_x_y'
     */
    private string $id;

    /**
     * @access private
     * @var int The horizontal beginning position of found highlight
     */
    private int $xBeginPosition;

    /**
     * @access private
     * @var int The horizontal ending position of found highlight
     */
    private int $xEndPosition;

    /**
     * @access private
     * @var int The vertical beginning position of found highlight
     */
    private int $yBeginPosition;

    /**
     * @access private
     * @var int The vertical ending position of found highlight
     */
    private int $yEndPosition;

    /**
     * The constructor for highlight.
     *
     * @access public
     *
     * @param array $highlight: Array of found highlight properties
     *
     * @return void
     */
    public function __construct(array $highlight)
    {
        // there is also possibility to access parentRegionIdx
        // $this->parentRegionId = $highlight['parentRegionIdx'];
        $this->xBeginPosition = $highlight['ulx'];
        $this->xEndPosition = $highlight['lrx'];
        $this->yBeginPosition = $highlight['uly'];
        $this->yEndPosition = $highlight['lry'];
        $this->id = $this->xBeginPosition . '_' . $this->yBeginPosition;
    }

    /**
     * Get the highlight's identifier.
     *
     * @access public
     *
     * @return string The highlight's identifier
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the highlight's horizontal beginning position.
     *
     * @access public
     *
     * @return int The highlight's horizontal beginning position
     */
    public function getXBeginPosition(): int
    {
        return $this->xBeginPosition;
    }

    /**
     * Get the highlight's horizontal ending position.
     *
     * @access public
     *
     * @return int The highlight's horizontal ending position
     */
    public function getXEndPosition(): int
    {
        return $this->xEndPosition;
    }

    /**
     * Get the highlight's vertical beginning position.
     *
     * @access public
     *
     * @return int The highlight's vertical beginning position
     */
    public function getYBeginPosition(): int
    {
        return $this->yBeginPosition;
    }

    /**
     * Get the highlight's vertical ending position.
     *
     * @access public
     *
     * @return int The highlight's vertical ending position
     */
    public function getYEndPosition(): int
    {
        return $this->yEndPosition;
    }
}
