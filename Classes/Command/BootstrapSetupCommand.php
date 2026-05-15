<?php

declare(strict_types=1);

namespace Kitodo\Dlf\Command;

use Kitodo\Dlf\Service\BootstrapRootSetupService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI command for running the bootstrap root setup manually.
 */
final class BootstrapSetupCommand extends Command
{
    public function __construct(
        private readonly BootstrapRootSetupService $bootstrapRootSetupService,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setDescription('Create the root page tree and viewer setup manually.');
        $this->addOption('identifier', null, InputOption::VALUE_REQUIRED, 'Custom site identifier.');
        $this->addOption('base', null, InputOption::VALUE_REQUIRED, 'Custom site base path, for example /my-instance/.');
        $this->addOption('root-title', null, InputOption::VALUE_REQUIRED, 'Custom root page title.');
        $this->addOption('root-slug', null, InputOption::VALUE_REQUIRED, 'Custom root page slug.');
        $this->addOption('viewer-slug', null, InputOption::VALUE_REQUIRED, 'Custom viewer page slug.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Page setup');

        try {
            $result = $this->bootstrapRootSetupService->runSetup([
                'identifier' => $input->getOption('identifier'),
                'base' => $input->getOption('base'),
                'rootTitle' => $input->getOption('root-title'),
                'rootSlug' => $input->getOption('root-slug'),
                'viewerSlug' => $input->getOption('viewer-slug'),
            ]);
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

        $io->definitionList(
            ['Site identifier' => $result['siteIdentifier']],
            ['Site base' => $result['siteBase']],
            ['Root page' => (string)$result['rootPageId']],
            ['Viewer page' => (string)$result['viewerPageId']],
            ['Configuration page' => (string)$result['configurationPageId']],
            ['Template record' => (string)$result['templateId']],
            ['Solr core' => $result['solrCoreUid'] !== null ? (string)$result['solrCoreUid'] : 'not created']
        );
        $io->success('Default page setup completed.');

        return Command::SUCCESS;
    }
}
