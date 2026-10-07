<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Vocabulary\CompanySectorImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Dernière étape du chantier secteur (task 107) : charge `sectors.json` dans `company_sector`.
 * Déterministe et idempotente — aucun LLM, rejouable sans risque. Le fichier étant committé et
 * baké dans l'image, cette commande tourne aussi en preprod/prod sans clé.
 */
#[AsCommand(
    name: 'app:company:sectors:import',
    description: 'Charge les secteurs d’entreprises (sectors.json) en base',
)]
final class CompanySectorsImportCommand extends Command
{
    public function __construct(
        private readonly CompanySectorImporter $importer,
        private readonly ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Chemin du JSON de secteurs.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule sans écrire en base.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $path = $this->filePath($input->getOption('file'));

        if (!is_file($path)) {
            $io->error(\sprintf('Fichier introuvable : %s', $path));

            return Command::FAILURE;
        }

        $sectors = json_decode((string) file_get_contents($path), true);
        if (!\is_array($sectors)) {
            $io->error('JSON de secteurs invalide.');

            return Command::FAILURE;
        }

        $report = $this->importer->import($sectors, $dryRun);

        $io->success(($dryRun ? '[dry-run] ' : '').\sprintf(
            'Import secteurs : %d créés, %d mis à jour, %d ignorés.',
            $report->created,
            $report->updated,
            $report->skipped,
        ));

        return Command::SUCCESS;
    }

    private function filePath(mixed $option): string
    {
        if (\is_string($option) && '' !== $option) {
            return $option;
        }

        return $this->params->get('kernel.project_dir').'/data/vocabulary/sectors.json';
    }
}
