<?php

declare(strict_types=1);

namespace Kitodo\Dlf\Command;

use Kitodo\Dlf\Service\TenantModuleSetupService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * CLI command for running the tenant module setup manually.
 */
final class TenantSetupCommand extends Command
{
    public function __construct(
        private readonly TenantModuleSetupService $tenantModuleSetupService,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setDescription('Run the tenant setup for an existing configuration folder.');
        $this->addOption('config-page', null, InputOption::VALUE_REQUIRED, 'Existing configuration folder uid.');
        $this->addOption('namespaces', null, InputOption::VALUE_NONE, 'Apply namespace defaults.');
        $this->addOption('formats', null, InputOption::VALUE_NONE, 'Alias for --namespaces.');
        $this->addOption('structures', null, InputOption::VALUE_NONE, 'Apply structure defaults.');
        $this->addOption('metadata', null, InputOption::VALUE_NONE, 'Apply metadata defaults.');
        $this->addOption('solr-core', null, InputOption::VALUE_NONE, 'Create a Solr core if missing.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Tenant setup');

        $configurationPage = $input->getOption('config-page');
        if (!is_string($configurationPage) || trim($configurationPage) === '' || !ctype_digit(trim($configurationPage))) { // Validate that the configuration page option is provided and is a numeric string
            $io->error('The --config-page option is required and must be a numeric page uid.');
            return Command::FAILURE;
        }

        $steps = [
            'formats' => (bool)$input->getOption('namespaces') || (bool)$input->getOption('formats'),
            'structures' => (bool)$input->getOption('structures'),
            'metadata' => (bool)$input->getOption('metadata'),
            'solrCore' => (bool)$input->getOption('solr-core'),
        ];
        if (!in_array(true, $steps, true)) {
            $steps = [
                'formats' => true,
                'structures' => true,
                'metadata' => true,
                'solrCore' => true,
            ];
        }

        try {
            $result = $this->tenantModuleSetupService->runSetup((int)trim($configurationPage), $steps);
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

        $io->definitionList(
            ['Configuration page' => (string)$result['configurationPageId']],
            ['Namespaces' => $result['steps']['formats'] ? (string)$result['results']['formats'] : 'skipped'],
            ['Structures' => $result['steps']['structures'] ? (string)$result['results']['structures'] : 'skipped'],
            ['Metadata' => $result['steps']['metadata'] ? (string)$result['results']['metadata'] : 'skipped'],
            ['Solr core' => $result['steps']['solrCore'] ? (($result['results']['solrCore'] !== null) ? (string)$result['results']['solrCore'] : 'not created') : 'skipped']
        );
        $io->success('Tenant setup completed.');

        return Command::SUCCESS;
    }
}
