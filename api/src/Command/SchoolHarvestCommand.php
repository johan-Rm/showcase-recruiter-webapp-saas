<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Vocabulary\SchoolHarvester;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Première étape de la chaîne D : récolte les établissements distincts du vivier, regroupés par nom
 * normalisé, et les exporte en JSON (task 115). Déterministe, aucun LLM.
 *
 * La suite — qualification (type d'établissement, pays) et filtre par école — reste **à trancher côté
 * produit** : le cadrage des critères entreprise ne la demande pas aujourd'hui. Ce référentiel est
 * la matière première de cette décision, pas son anticipation.
 */
#[AsCommand(
    name: 'app:school:harvest',
    description: 'Récolte les établissements distincts des formations (regroupés par nom normalisé)',
)]
final class SchoolHarvestCommand extends Command
{
    public function __construct(
        private readonly SchoolHarvester $harvester,
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

        $schools = $this->harvester->harvest();
        $formations = 0;
        $out = [];
        foreach ($schools as $school) {
            $formations += $school->frequency;
            $out[] = [
                'cle' => $school->key,
                'nom' => $school->name,
                'frequence' => $school->frequency,
                'identifiants_jarvi' => $school->jarviIds,
                'graphies' => $school->variants,
            ];
        }

        $path = $this->outputPath($input->getOption('output'));
        $json = json_encode($out, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
        if (false === file_put_contents($path, $json)) {
            $io->error(\sprintf('Écriture impossible : %s', $path));

            return Command::FAILURE;
        }

        $io->success(\sprintf(
            'Récolte : %d établissements distincts pour %d formations → %s',
            \count($schools),
            $formations,
            $path,
        ));

        return Command::SUCCESS;
    }

    private function outputPath(mixed $option): string
    {
        if (\is_string($option) && '' !== $option) {
            return $option;
        }

        // Sortie générée → `var/` (exclu de l'image, non versionné), sous api/ : seul périmètre monté.
        return $this->params->get('kernel.project_dir').'/var/school-harvest.json';
    }
}
