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
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Region
{

    /**
     * The identifier of the region
     *
     * @var int
     * @access private
     */
    private $id;

    /**
     * The identifier of the page in which text was found
     *
     * @var int
     * @access private
     */
    private $pageId;

    /**
     * The horizontal beginning position of found region
     *
     * @var int
     * @access private
     */
    private $xBeginPosition;

    /**
     * The horizontal ending position of found region
     *
     * @var int
     * @access private
     */
    private $xEndPosition;

    /**
     * The vertical beginning position of found region
     *
     * @var int
     * @access private
     */
    private $yBeginPosition;

    /**
     * The vertical ending position of found region
     *
     * @var int
     * @access private
     */
    private $yEndPosition;

    /**
     * The width of found region
     *
     * @var int
     * @access private
     */
    private $width;

    /**
     * The height of found region
     *
     * @var int
     * @access private
     */
    private $height;

    /**
     * The text of found region
     *
     * @var string
     * @access private
     */
    private $text;

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
    public function __construct($id, $region)
    {
        $this->id = $id;
        $this->pageId = $region['pageIdx'];
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the region's page identifier.
     *
     * @access public
     *
     * @return int The region's page identifier
     */
    public function getPageId()
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
    public function getXBeginPosition()
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
    public function getXEndPosition()
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
    public function getYBeginPosition()
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
    public function getYEndPosition()
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
    public function getWidth()
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
    public function getHeight()
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
    public function getText()
    {
        return $this->text;
    }
}
