<?php

declare(strict_types=1);

namespace T3G\Hubspot\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use T3G\Hubspot\Service\ImportCtaService;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;

class ImportCtasCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Import a given CSV sheet')
            ->addArgument(
                'file',
                null,
                'What file should be imported',
                ''
            )
            ->addArgument(
                'overwrite',
                null,
                'CTAs will be updated without taking into account the last time where updated at hubspot',
                false
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        Bootstrap::initializeBackendAuthentication();
        $overwrite = $input->getArgument('overwrite');
        $handle = fopen($input->getArgument('file'), 'r');
        // Skip header line
        fgetcsv($handle, 0, '\\');
        $rows = [];
        while ($row = fgetcsv($handle, 0, '\\')) {
            $rows[] = $row;
        }
        $this->importCtas($rows, $overwrite);
        return 0;
    }

    /**
     * @param array $ctas
     */
    protected function importCtas(array $ctas, $overwrite = false)
    {
        foreach ($ctas as $cta) {
            ImportCtaService::importCta($cta, $overwrite);
        }
    }
}
