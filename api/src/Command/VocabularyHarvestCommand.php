<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Vocabulary\VocabularyHarvester;
use App\Enum\VocabularyType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Étape 1 du pipeline vocabulaire : récolte le vocabulaire brut (compétences + intitulés) depuis
 * la réplica et l'exporte en JSON. Sa sortie nourrit l'enrichissement LLM hors ligne, qui produit
 * la taxonomie chargée ensuite par app:vocabulary:import. Le LLM ne tourne jamais ici.
 */
#[AsCommand(
    name: 'app:vocabulary:harvest',
    description: 'Récolte le vocabulaire (compétences, postes) depuis la réplica Jarvi',
)]
final class VocabularyHarvestCommand extends Command
{
    public function __construct(
        private readonly VocabularyHarvester $harvester,
        private readonly ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('output', null, InputOption::VALUE_REQUIRED, 'Chemin du JSON de sortie.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $out = ['competences' => [], 'postes' => []];
        foreach ($this->harvester->harvest() as $term) {
            $key = VocabularyType::COMPETENCE === $term->type ? 'competences' : 'postes';
            $out[$key][] = ['terme' => $term->term, 'frequence' => $term->frequency];
        }
        foreach (['competences', 'postes'] as $key) {
            usort($out[$key], static fn (array $a, array $b): int => $b['frequence'] <=> $a['frequence']);
        }

        $path = $this->outputPath($input->getOption('output'));
        $json = json_encode($out, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
        if (false === file_put_contents($path, $json)) {
            $io->error(\sprintf('Écriture impossible : %s', $path));

            return Command::FAILURE;
        }

        $io->success(\sprintf(
            'Récolte : %d compétences, %d postes distincts → %s',
            \count($out['competences']),
            \count($out['postes']),
            $path,
        ));

        return Command::SUCCESS;
    }

    private function outputPath(mixed $option): string
    {
        if (\is_string($option) && '' !== $option) {
            return $option;
        }

        // Sortie générée → `var/` (exclu de l'image, non versionné). Doit rester sous api/ :
        // c'est le seul périmètre monté en dev et présent dans l'image.
        return $this->params->get('kernel.project_dir').'/var/vocabulary-harvest.json';
    }
}
