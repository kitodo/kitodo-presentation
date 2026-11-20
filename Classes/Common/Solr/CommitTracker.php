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

namespace Kitodo\Dlf\Common\Solr;

/**
 * Keeps track of the number of documents added to a Solr core without a commit, 
 * so that the index is committed only after a certain number of documents, e.g., 100.
 *
 * Usage:
 *
 *     $commitTracker = new CommitTracker($solrCoreUid, $commitEvery, $softCommit);
 *     foreach ($documents as $document) {
 *         $commit = $commitTracker->addDocument();
 *         Indexer::add($document, $documentRepository, $softCommit, $commit);
 *     }
 *     $commitTracker->commitRemaining();
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class CommitTracker
{
    /**
     * @access private
     * @var int Number of documents added to the index since the last commit
     */
    private int $uncommittedDocuments = 0;

    /**
     * @access public
     *
     * @param int $solrCoreUid UID of the Solr core the documents are added to
     * @param int $commitEvery Number of documents per commit, 1 commits after each document
     * @param bool $softCommit If true, the remaining documents are committed by a soft commit
     *
     * @throws \InvalidArgumentException if the number of documents per commit is not positive
     */
    public function __construct(
        private readonly int $solrCoreUid,
        private readonly int $commitEvery,
        private readonly bool $softCommit = false
    ) {
        if ($commitEvery < 1) {
            throw new \InvalidArgumentException('Number of documents per commit must be positive, ' . $commitEvery . ' given', 1763634001);
        }
    }

    /**
     * Register a document that is about to be added to the index and decide whether it has to be added with a
     * commit, which also commits all documents added without a commit before.
     *
     * @access public
     *
     * @return bool true if the document has to be added with a commit
     */
    public function addDocument(): bool
    {
        $this->uncommittedDocuments++;
        if ($this->uncommittedDocuments < $this->commitEvery) {
            return false;
        }
        $this->uncommittedDocuments = 0;
        return true;
    }

    /**
     * Get the number of documents added to the index since the last commit.
     *
     * @access public
     *
     * @return int
     */
    public function getUncommittedDocuments(): int
    {
        return $this->uncommittedDocuments;
    }

    /**
     * Commit the documents added to the index since the last commit, if any.
     *
     * @access public
     *
     * @return bool true on success or if nothing had to be committed
     */
    public function commitRemaining(): bool
    {
        if ($this->uncommittedDocuments === 0) {
            return true;
        }
        try {
            $solr = Solr::getInstance($this->solrCoreUid);
            if (!$solr->ready) {
                return false;
            }
            $update = $solr->service->createUpdate();
            $update->addCommit($this->softCommit);
            $solr->service->update($update);
        } catch (\Exception $e) {
            return false;
        }
        $this->uncommittedDocuments = 0;
        return true;
    }
}
