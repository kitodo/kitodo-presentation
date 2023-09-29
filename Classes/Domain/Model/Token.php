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
 * Resumption tokens for OAI-PMH interface.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class Token extends AbstractEntity
{
    /**
     * @access protected
     * @var string The resumption token string.
     */
    protected $token;

    /**
     * @access protected
     * @var string Data that is used to resume the previous request.
     */
    protected $options;

    /**
     * @access protected
     * @var string Originally an identifier for the kind of token ('oai'). Not used at the moment.
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
     * @return array
     */
    public function getOptions(): array
    {
        return unserialize($this->options);
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
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
