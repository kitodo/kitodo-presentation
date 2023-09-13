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

/**
 * Resumption tokens for OAI-PMH interface.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class Token extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * The resumption token string.
     *
     * @var string
     */
    protected $token;

    /**
     * Data that is used to resume the previous request.
     *
     * @var string
     */
    protected $options;

    /**
     * Originally an identifier for the kind of token ('oai'). Not used at the moment.
     *
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
