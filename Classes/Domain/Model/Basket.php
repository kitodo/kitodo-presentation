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

namespace Kitodo\Dlf\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Basket entity class for the 'dlf' extension
 *
 * @author Beatrycze Volk <beatrycze.volk@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Basket extends AbstractEntity
{
    /**
     * The basket label
     *
     * @var string
     * @access protected
     */
    protected $label = '';

    /**
     * The session id
     *
     * @var string
     * @access protected
     */
    protected $sessionId = '';

    /**
     * The ids of documents in the basket
     *
     * @var string
     * @access protected
     */
    protected $docIds = '';

    /**
     * The user id
     *
     * @var int
     * @access protected
     */
    protected $feUserId = 0;

    /**
     * Initializes the basket entity.
     *
     * @access public
     * 
     * @param string $label: The basket label
     * @param string $sessionId: The session id
     *
     * @return void
     */
    public function __construct(
        string $label = '',
        string $sessionId = '',
        string $docIds = '',
        int $feUserId = 0)
    {
        $this->setLabel($label);
        $this->setSessionId($sessionId);
        $this->setDocIds($docIds);
        $this->setFeUserId($feUserId);
    }

    /**
     * Get the basket label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the basket label
     *
     * @param string $label The basket label
     *
     * @return void
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * Get the session id
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set the session id
     *
     * @param string $sessionId The session id
     *
     * @return void
     */
    public function setSessionId(string $sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Get the ids of documents in the basket
     *
     * @return string
     */
    public function getDocIds()
    {
        return $this->docIds;
    }

    /**
     * Set the ids of documents in the basket
     *
     * @param string $docIds The ids of documents in the basket
     *
     * @return void
     */
    public function setDocIds(string $docIds)
    {
        $this->docIds = $docIds;
    }

    /**
     * Get the user id
     *
     * @return int
     */
    public function getFeUserId()
    {
        return $this->feUserId;
    }

    /**
     * Set the user id
     *
     * @param int $feUserId The user id
     *
     * @return void
     */
    public function setFeUserId(int $feUserId)
    {
        $this->feUserId = $feUserId;
    }
}
