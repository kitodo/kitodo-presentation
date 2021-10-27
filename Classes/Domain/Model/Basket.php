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

class Basket extends \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
{
    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var string|null
     */
    protected $docIds;

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->session_id;
    }

    /**
     * @param string $session_id
     */
    public function setSessionId(string $session_id): void
    {
        $this->session_id = $session_id;
    }

    /**
     * @return string|null
     */
    public function getDocIds(): ?string
    {
        return $this->docIds;
    }

    /**
     * @param string|null $docIds
     */
    public function setDocIds(?string $docIds): void
    {
        $this->docIds = $docIds;
    }

}
