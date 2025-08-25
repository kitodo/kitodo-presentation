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

use Kitodo\Dlf\Common\Solr\Solr;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI Command for sending the commit and/or optimize command to the index.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
class OptimizeCommand extends Command
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
            ->setDescription('Sending the commit and/or optimize command to the index.')
            ->setHelp('')
            ->addOption(
                'solr',
                's',
                InputOption::VALUE_REQUIRED,
                '[UID|index_name] of the Solr core for commit and/or optimize.'
            )
            ->addOption(
                'commit',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the commit command is sent.'
            )
            ->addOption(
                'optimize',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the optimize command is sent.'
            );
    }

    /**
     * Executes the commit and/or optimize command.
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

        if (empty($input->getOption('solr')) || is_array($input->getOption('solr'))) {
                $io->error('ERROR: Required parameter --solr|-s is missing or array.');
                return Command::FAILURE;
        }

        if (empty($input->getOption('commit')) && empty($input->getOption('optimize'))) {
            $io->error('ERROR: Parameter --commit or --optimize is missing.');
            return Command::FAILURE;
        }

        // Get Solr instance.
        $solr = Solr::getInstance($input->getOption('solr'));
        // Connect to Solr server.
        if (!$solr->ready) {
            $io->error('ERROR: Connection to Solr core ("' . $input->getOption('solr') . '") not possible \n');
            return Command::FAILURE;
        }

        if (!$solr->optimize($input->getOption('commit'), $input->getOption('optimize'))) {
            $io->error('ERROR: Optimizing the Solr core ("' . $input->getOption('solr') . '") not possible \n');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
