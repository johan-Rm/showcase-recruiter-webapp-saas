<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Vocabulary\ConceptCooccurrenceReader;
use App\Domain\Vocabulary\ConceptSimilarityCalculator;
use App\Domain\Vocabulary\ConceptSimilarityImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Calcule les concepts PROCHES par co-occurrence dans les profils (task 111) — déterministe, sans
 * LLM. Tourne APRÈS `app:vocabulary:import` : elle a besoin des concepts en base pour y rattacher
 * les termes des profils.
 *
 * Rejouable : chaque exécution remplace intégralement le jeu de relations.
 */
#[AsCommand(
    name: 'app:vocabulary:similarity',
    description: 'Calcule les concepts proches par co-occurrence (Jaccard + NPMI)',
)]
final class VocabularySimilarityCommand extends Command
{
    public function __construct(
        private readonly ConceptCooccurrenceReader $reader,
        private readonly ConceptSimilarityImporter $importer,
        private readonly int $minCooccurrences,
        private readonly float $minNpmi,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('min-cooccurrences', null, InputOption::VALUE_REQUIRED, 'Profils communs minimum.')
            ->addOption('min-npmi', null, InputOption::VALUE_REQUIRED, 'Score NPMI minimum (0–1).')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Calcule et affiche sans écrire en base.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $minCooccurrences = (int) ($input->getOption('min-cooccurrences') ?? $this->minCooccurrences);
        $minNpmi = (float) ($input->getOption('min-npmi') ?? $this->minNpmi);

        $profiles = $this->reader->conceptsByProfile();
        if ([] === $profiles) {
            $io->warning('Aucun profil ne porte de concept : lancer app:vocabulary:import d’abord.');

            return Command::FAILURE;
        }

        $similarities = (new ConceptSimilarityCalculator($minCooccurrences, $minNpmi))->calculate($profiles);

        if ((bool) $input->getOption('dry-run')) {
            $io->success(\sprintf(
                'Simulation : %d relations depuis %d profils (seuils %d co-occurrences, NPMI %.2f).',
                \count($similarities),
                \count($profiles),
                $minCooccurrences,
                $minNpmi,
            ));

            return Command::SUCCESS;
        }

        $written = $this->importer->replaceAll($similarities);
        $io->success(\sprintf(
            'Proximités : %d relations écrites depuis %d profils (seuils %d co-occurrences, NPMI %.2f).',
            $written,
            \count($profiles),
            $minCooccurrences,
            $minNpmi,
        ));

        return Command::SUCCESS;
    }
}
