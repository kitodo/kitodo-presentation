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
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class Page
{

    /**
     * @access private
     * @var int The identifier of the page
     */
    private int $id;

    /**
     * @access private
     * @var string The name of the page
     */
    private string $name;

    /**
     * @access private
     * @var int The width of found page
     */
    private int $width;

    /**
     * @access private
     * @var int The height of found page
     */
    private int $height;

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
    public function __construct(int $id, array $page)
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
    public function getId(): int
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
    public function getName(): string
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
    public function getWidth(): int
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
    public function getHeight(): int
    {
        return $this->height;
    }
}
