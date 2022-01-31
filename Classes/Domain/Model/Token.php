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

class Token extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $options;

    /**
     * @var string
     */
    protected $ident;

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return \Kitodo\Dlf\Common\DocumentList
     */
    public function getOptions(): \Kitodo\Dlf\Common\DocumentList
    {
        return unserialize($this->options);
    }

    /**
     * @param \Kitodo\Dlf\Common\DocumentList $options
     */
    public function setOptions(\Kitodo\Dlf\Common\DocumentList $options): void
    {
        $this->options = serialize($options);
    }

    /**
     * @return string
     */
    public function getIdent(): string
    {
        return $this->ident;
    }

    /**
     * @param string $ident
     */
    public function setIdent(string $ident): void
    {
        $this->ident = $ident;
    }

}