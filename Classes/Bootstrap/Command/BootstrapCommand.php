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

namespace Kitodo\Dlf\Bootstrap\Command;

use Kitodo\Dlf\Bootstrap\BootstrapService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * CLI command creating a demo site from a scenario, see BootstrapService.
 *
 * @package TYPO3
 * @subpackage dlf
 *
 * @access public
 */
#[AsCommand(name: 'kitodo:bootstrap', description: 'Create (or replace) a demo site showcasing Kitodo.Presentation plugins.')]
class BootstrapCommand extends Command
{
    public function __construct(
        protected readonly BootstrapService $bootstrapService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'scenario',
            InputArgument::REQUIRED,
            'Name of the scenario, one of: ' . implode(', ', array_keys($this->bootstrapService->getScenarios()))
        );
        $this->addOption(
            'base-url',
            null,
            InputOption::VALUE_REQUIRED,
            'Scheme and host of the demo site, e.g. "http://localhost:1380". Required if another site uses the same host, since sites with a matching host take precedence.',
            ''
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // DataHandler requires an authenticated admin user and a backend request.
        Bootstrap::initializeBackendAuthentication();
        GeneralUtility::makeInstance(Context::class)->setAspect('backend.user', new UserAspect($GLOBALS['BE_USER']));
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $scenario = (string) $input->getArgument('scenario');
        $pageUids = $this->bootstrapService->bootstrap($scenario, (string) $input->getOption('base-url'));

        $io->success('Created demo site for scenario "' . $scenario . '" with root page ' . $pageUids['root'] . '.');
        return Command::SUCCESS;
    }
}
