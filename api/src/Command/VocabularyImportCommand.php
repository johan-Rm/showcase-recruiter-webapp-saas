<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Vocabulary\VocabularyImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Étape finale du pipeline vocabulaire : charge la taxonomie enrichie (JSON) dans les tables
 * vocabulary_concept / vocabulary_label. Idempotent ; tout entre en statut CANDIDAT.
 */
#[AsCommand(
    name: 'app:vocabulary:import',
    description: 'Charge la taxonomie (concepts + libellés) en base',
)]
final class VocabularyImportCommand extends Command
{
    public function __construct(
        private readonly VocabularyImporter $importer,
        private readonly ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Chemin du JSON de taxonomie.')
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

        $taxonomy = json_decode((string) file_get_contents($path), true);
        if (!\is_array($taxonomy)) {
            $io->error('JSON de taxonomie invalide.');

            return Command::FAILURE;
        }

        $report = $this->importer->import($taxonomy, $dryRun);

        $io->success(($dryRun ? '[dry-run] ' : '').\sprintf(
            'Import vocabulaire : %d concepts créés, %d mis à jour ; %d libellés créés, %d mis à jour,'
            .' %d retirés, %d rendus.',
            $report->conceptsCreated,
            $report->conceptsUpdated,
            $report->labelsCreated,
            $report->labelsUpdated,
            $report->labelsRemoved,
            $report->labelsRestored,
        ));

        return Command::SUCCESS;
    }

    private function filePath(mixed $option): string
    {
        if (\is_string($option) && '' !== $option) {
            return $option;
        }

        // Sous api/ (et non à la racine du repo) : c'est le seul périmètre monté en dev
        // (`./api:/app`) et copié dans l'image de prod. Un chemin hors de api/ est inatteignable.
        return $this->params->get('kernel.project_dir').'/data/vocabulary/taxonomy.json';
    }
}
