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

namespace Kitodo\Dlf\Command;

use Kitodo\Dlf\Common\AbstractDocument;
use Kitodo\Dlf\Command\BaseCommand;
use Kitodo\Dlf\Common\Indexer;
use Kitodo\Dlf\Domain\Model\Document;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * CLI Command for indexing single documents into database and Solr.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class IndexCommand extends BaseCommand
{

    /**
     * Configure the command by defining the name, options and arguments
     *
     * @access public
     *
     * @return void
     */
    public function configure(): void
    {
        $this
            ->setDescription('Index single document into database and Solr.')
            ->setHelp('')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the files will not actually be processed but the location URI is shown.'
            )
            ->addOption(
                'doc',
                'd',
                InputOption::VALUE_REQUIRED,
                'UID or URL of the document.'
            )
            ->addOption(
                'pid',
                'p',
                InputOption::VALUE_REQUIRED,
                'UID of the page the document should be added to.'
            )
            ->addOption(
                'solr',
                's',
                InputOption::VALUE_REQUIRED,
                '[UID|index_name] of the Solr core the document should be added to.'
            )
            ->addOption(
                'owner',
                'o',
                InputOption::VALUE_OPTIONAL,
                '[UID|index_name] of the Library which should be set as owner of the document.'
            )
            ->addOption(
                'softCommit',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, documents are just added to the index by a soft commit.'
            );
    }

    /**
     * Executes the command to index the given document to DB and SOLR.
     *
     * @access protected
     *
     * @param InputInterface $input The input parameters
     * @param OutputInterface $output The Symfony interface for outputs on console
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $this->initializeRepositories((int) $input->getOption('pid'));

        $allowWrite = (int) $this->extConf['solr']['allowWrite'] === 1 ? true : false;

        if ($allowWrite) {
            return $this->executeIndexCommand($input, $io);
        } else {
            $io->error('This system is not allowed to write to the Solr index.');
            return BaseCommand::FAILURE;
        }
    }

    /**
     * Execute index command basing on the user input.
     *
     * @access private
     *
     * @param InputInterface $input
     * @param SymfonyStyle $io
     *
     * @return int BaseCommand::FAILURE or BaseCommand::SUCCESS
     */
    private function executeIndexCommand(InputInterface $input, SymfonyStyle $io): int
    {
        $dryRun = $input->getOption('dry-run') != false ? true : false;

        if ($this->storagePid == 0) {
            $io->error('No valid PID (' . $this->storagePid . ') given.');
            return BaseCommand::FAILURE;
        }

        if (
            !empty($input->getOption('solr'))
            && !is_array($input->getOption('solr'))
        ) {
            $allSolrCores = $this->getSolrCores($this->storagePid);
            $solrCoreUid = $this->getSolrCoreUid($allSolrCores, $input->getOption('solr'));

            // Abort if solrCoreUid is empty or not in the array of allowed solr cores.
            if (empty($solrCoreUid) || !in_array($solrCoreUid, $allSolrCores)) {
                $this->validateSolrCores($allSolrCores, $input->getOption('solr'), $io);
                return BaseCommand::FAILURE;
            }
        } else {
            $io->error('Required parameter --solr|-s is missing or array.');
            return BaseCommand::FAILURE;
        }

        if (
            empty($input->getOption('doc'))
            || is_array($input->getOption('doc'))
            || (
                !MathUtility::canBeInterpretedAsInteger($input->getOption('doc'))
                && !GeneralUtility::isValidUrl($input->getOption('doc'))
            )
        ) {
            $io->error('Required parameter --doc|-d is not a valid document UID or URL.');
            return BaseCommand::FAILURE;
        }

        $this->getOwner($input->getOption('owner'));

        $result = $this->getDocument($input->getOption('doc'));

        if ($result['message'] !== null) {
            $io->error($result['message']);
            return BaseCommand::FAILURE;
        }

        if ($result['doc'] === null) {
            $io->error('Document "' . $input->getOption('doc') . '" could not be loaded.');
            return BaseCommand::FAILURE;
        }

        $document = $result['document'];
        $doc = $result['doc'];

        $document->setSolrcore($solrCoreUid);

        if ($dryRun) {
            $io->section('DRY RUN: Would index ' . $document->getUid() . ' ("' . $document->getLocation() . '") on PID ' . $this->storagePid . ' and Solr core ' . $solrCoreUid . '.');
            $io->success('All done!');
            return BaseCommand::SUCCESS;
        } else {
            $document->setCurrentDocument($doc);

            if ($io->isVerbose()) {
                $io->section('Indexing ' . $document->getUid() . ' ("' . $document->getLocation() . '") on PID ' . $this->storagePid . '.');
            }
            $isSaved = $this->saveToDatabase($document, $input->getOption('softCommit'));

            if ($isSaved) {
                if ($io->isVerbose()) {
                    $io->section('Indexing ' . $document->getUid() . ' ("' . $document->getLocation() . '") on Solr core ' . $solrCoreUid . '.');
                }
                $isSaved = Indexer::add($document, $this->documentRepository, $input->getOption('softCommit'));
            } else {
                $io->error('Document with UID "' . $document->getUid() . '" could not be indexed on PID ' . $this->storagePid . ' . There are missing mandatory fields (at least one of those: ' . $this->extConf['general']['requiredMetadataFields'] . ') in this document.');
                return BaseCommand::FAILURE;
            }

            if ($isSaved) {
                $io->success('All done!');
                return BaseCommand::SUCCESS;
            }

            $io->error('Document with UID "' . $document->getUid() . '" could not be indexed on Solr core ' . $solrCoreUid . ' . There are missing mandatory fields (at least one of those: ' . $this->extConf['general']['requiredMetadataFields'] . ') in this document.');
            $io->info('Document with UID "' . $document->getUid() . '" is already in database. If you want to keep the database and index consistent you need to remove it.');
            return BaseCommand::FAILURE;
        }
    }

    /**
     * Get document from database or XML file,
     * if not found then save error message.
     *
     * @access private
     *
     * @param mixed $inputDoc
     *
     * @return array associative array wih document and doc objects or error message
     */
    private function getDocument($inputDoc): array
    {
        $result = [
            'document' => null,
            'doc' => null,
            'message' => null
        ];

        if (MathUtility::canBeInterpretedAsInteger($inputDoc)) {
            $result['document'] = $this->documentRepository->findByUid($inputDoc);

            if ($result['document'] === null) {
                $result['message'] = 'Document with UID "' . $inputDoc . '" could not be found on PID ' . $this->storagePid . ' .';
                return $result;
            } else {
                $result['doc'] = AbstractDocument::getInstance($result['document']->getLocation(), ['storagePid' => $this->storagePid], true);
                return $result;
            }
        } elseif (GeneralUtility::isValidUrl($inputDoc)) {
            $result['doc']  = AbstractDocument::getInstance($inputDoc, ['storagePid' => $this->storagePid], true);
            $result['document'] = $this->getDocumentFromUrl($result['doc'], $inputDoc);
        }

        return $result;
    }

    /**
     * Get document from given URL. Find it in database, if not found create the new one.
     *
     * @access private
     *
     * @param AbstractDocument $doc
     * @param string $url
     *
     * @return Document
     */
    private function getDocumentFromUrl($doc, string $url): Document
    {
        $document = null;

        if ($doc->recordId) {
            $document = $this->documentRepository->findOneByRecordId($doc->recordId);
        } else {
            $document = $this->documentRepository->findOneByLocation($url);
        }

        if ($document === null) {
            // create new Document object
            $document = GeneralUtility::makeInstance(Document::class);
        }

        // now there must exist a document object
        if ($document) {
            $document->setLocation($url);
        }

        return $document;
    }

    /**
     * Get owner from user input.
     *
     * @access private
     *
     * @param mixed $owner
     *
     * @return void
     */
    private function getOwner($owner): void
    {
        if (!empty($owner)) {
            if (MathUtility::canBeInterpretedAsInteger($owner)) {
                $this->owner = $this->libraryRepository->findByUid(MathUtility::forceIntegerInRange((int) $owner, 1));
            } else {
                $this->owner = $this->libraryRepository->findOneByIndexName((string) $owner);
            }
        } else {
            $this->owner = null;
        }
    }

    /**
     * Validate SOLR core and print matching error message for user.
     *
     * @param array $allSolrCores
     * @param mixed $solr
     * @param SymfonyStyle $io
     *
     * @return void
     */
    private function validateSolrCores(array $allSolrCores, $solr, SymfonyStyle $io): void
    {
        $outputSolrCores = [];
        foreach ($allSolrCores as $indexName => $uid) {
            $outputSolrCores[] = $uid . ' : ' . $indexName;
        }
        if (empty($outputSolrCores)) {
            $io->error('No valid Solr core ("' . $solr . '") given. No valid cores found on PID ' . $this->storagePid . ".\n");
        } else {
            $io->error('No valid Solr core ("' . $solr . '") given. ' . "Valid cores are (<uid>:<index_name>):\n" . implode("\n", $outputSolrCores) . "\n");
        }
    }
}
