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
 * Page class for the 'dlf' extension. It keeps page in which search phrase was found.
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Page
{

    /**
     * The identifier of the page
     *
     * @var int
     * @access private
     */
    private $id;

    /**
     * The name of the page
     *
     * @var string
     * @access private
     */
    private $name;

    /**
     * The width of found page
     *
     * @var int
     * @access private
     */
    private $width;

    /**
     * The height of found page
     *
     * @var int
     * @access private
     */
    private $height;

    /**
     * The constructor for region.
     *
     * @access public
     *
     * @param int $id: Id of found page properties
     * @param array $page: Array of found page properties
     *
     * @return void
     */
    public function __construct($id, $page)
    {
        $this->id = $id;
        $this->name = $page['id'];
        $this->width = $page['width'];
        $this->height = $page['height'];
    }

    /**
     * Get the page's identifier.
     *
     * @access public
     *
     * @return int The page's identifier
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the page's name.
     *
     * @access public
     *
     * @return string The page's name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the page's width.
     *
     * @access public
     *
     * @return int The page's width
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Get the page's height.
     *
     * @access public
     *
     * @return int The page's height
     */
    public function getHeight()
    {
        return $this->height;
    }
}
