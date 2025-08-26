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
namespace Kitodo\Dlf\Task;

use Kitodo\Dlf\Common\Helper;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Base class for Scheduler task classes.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class BaseTask extends AbstractTask
{

    /**
     * @access protected
     * @var bool
     */
    protected bool $dryRun = false;

    /**
     * @access protected
     * @var string
     */
    protected string $doc = 'https://';

    /**
     * @access protected
     * @var int
     */
    protected int $lib = - 1;

    /**
     * @access protected
     * @var int
     */
    protected int $pid = - 1;

    /**
     * @access protected
     * @var array
     */
    protected array $coll = [];

    /**
     * @access protected
     * @var int
     */
    protected int $solr = 0;

    /**
     * @access protected
     * @var string
     */
    protected string $owner = '';

    /**
     * @access protected
     * @var bool
     */
    protected bool $all = false;

    /**
     * @access protected
     * @var string
     */
    protected string $from = '';

    /**
     * @access protected
     * @var string
     */
    protected string $until = '';

    /**
     * @access protected
     * @var string
     */
    protected string $set = '';

    /**
     * @access protected
     * @var bool
     */
    protected bool $softCommit = false;

    /**
     * @access protected
     * @var bool
     */
    protected bool $commit = false;

    /**
     * @access protected
     * @var bool
     */
    protected bool $optimize = false;

    public function execute()
    {
        return true;
    }

    /**
     *
     * @return bool
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     *
     * @param bool $dryRun
     */
    public function setDryRun(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    /**
     *
     * @return string
     */
    public function getDoc(): string
    {
        return $this->doc;
    }

    /**
     *
     * @param string $doc
     */
    public function setDoc(string $doc): void
    {
        $this->doc = $doc;
    }

    /**
     *
     * @return int
     */
    public function getLib(): int
    {
        return $this->lib;
    }

    /**
     *
     * @param int $lib
     */
    public function setLib(int $lib): void
    {
        $this->lib = $lib;
    }

    /**
     *
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     *
     * @param int $pid
     */
    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    /**
     *
     * @return array
     */
    public function getColl(): array
    {
        return $this->coll;
    }

    /**
     *
     * @param array $coll
     */
    public function setColl(array $coll): void
    {
        $this->coll = $coll;
    }

    /**
     *
     * @return int
     */
    public function getSolr(): int
    {
        return $this->solr;
    }

    /**
     *
     * @param int $solr
     */
    public function setSolr(int $solr): void
    {
        $this->solr = $solr;
    }

    /**
     *
     * @return string
     */
    public function getOwner(): string
    {
        return $this->owner;
    }

    /**
     *
     * @param string $owner
     */
    public function setOwner(string $owner): void
    {
        $this->owner = $owner;
    }

    /**
     *
     * @return bool
     */
    public function isAll(): bool
    {
        return $this->all;
    }

    /**
     *
     * @param bool $all
     */
    public function setAll(bool $all): void
    {
        $this->all = $all;
    }

    /**
     *
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     *
     * @param string $from
     */
    public function setFrom(string $from): void
    {
        $this->from = $from;
    }

    /**
     *
     * @return string
     */
    public function getUntil(): string
    {
        return $this->until;
    }

    /**
     *
     * @param string $until
     */
    public function setUntil(string $until): void
    {
        $this->until = $until;
    }

    /**
     *
     * @return string
     */
    public function getSet(): string
    {
        return $this->set;
    }

    /**
     *
     * @param string $set
     */
    public function setSet(string $set): void
    {
        $this->set = $set;
    }

    /**
     *
     * @return bool
     */
    public function isSoftCommit(): bool
    {
        return $this->softCommit;
    }

    /**
     *
     * @param bool $softCommit
     */
    public function setSoftCommit(bool $softCommit): void
    {
        $this->softCommit = $softCommit;
    }

    /**
     *
     * @return bool
     */
    public function isCommit(): bool
    {
        return $this->commit;
    }

    /**
     *
     * @param bool $commit
     */
    public function setCommit(bool $commit): void
    {
        $this->commit = $commit;
    }

    /**
     *
     * @return bool
     */
    public function isOptimize(): bool
    {
        return $this->optimize;
    }
    /**
     *
     * @param bool $optimize
     */
    public function setOptimize(bool $optimize): void
    {
        $this->optimize = $optimize;
    }

    /**
     * Generates and adds flash messages based on a string separated by PHP_EOL.
     *
     * @access protected
     *
     * @param string $message Messages separated by PHP_EOL
     * @param ContextualFeedbackSeverity $severity
     *
     * @return void
     */
    protected function outputFlashMessages(string $message, ContextualFeedbackSeverity $severity): void
    {
        $messages = explode(PHP_EOL, $message);

        foreach ($messages as $message) {
            if (empty($message) || (substr_count($message, '=') == strlen($message))) {
                continue;
            }

            if ($severity !== ContextualFeedbackSeverity::ERROR) {
                $severity = ContextualFeedbackSeverity::OK;
            }

            Helper::addMessage(
                $message,
                '',
                $severity,
                true,
                'core.template.flashMessages'
            );
        }
    }
}
