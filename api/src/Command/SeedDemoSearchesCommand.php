<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Search\Demo\DemoSearchLine;
use App\Domain\Search\Demo\DemoSearchSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Peuple le cockpit entreprise d'une grille de recherches de démonstration — task API-029.
 *
 * Elle existe parce que les fixtures ne peuvent pas le faire : les charger PURGE la base, donc le
 * vivier importé de Jarvi, seul à donner du volume aux cartes. Cette commande, elle, ajoute sans
 * rien effacer, et se rejoue sans dupliquer.
 *
 * Réservée aux environnements de démonstration, comme `app:accounts:seed-team`.
 */
#[AsCommand(
    name: 'app:searches:seed-demo',
    description: 'Crée les recherches de démonstration du cockpit entreprise (volumes, nouveautés, profils vus et retenus)',
)]
final class SeedDemoSearchesCommand extends Command
{
    public function __construct(
        private readonly DemoSearchSeeder $seeder,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('recruiter', null, InputOption::VALUE_REQUIRED, 'Adresse du recruteur qui portera la grille (défaut : le premier propriétaire d\'une entreprise validée)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Autoriser l\'exécution même en production');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ('prod' === $this->environment && !(bool) $input->getOption('force')) {
            $io->error('Refus : commande destinée aux environnements de démonstration. Utilisez --force pour passer outre.');

            return Command::FAILURE;
        }

        $recruiter = $input->getOption('recruiter');
        $report = $this->seeder->seed(\is_string($recruiter) ? $recruiter : null);

        $io->table(
            ['Recherche', 'Profils', 'Nouveaux', 'Vus', 'Retenus', 'État'],
            array_map(
                static fn (DemoSearchLine $line): array => [
                    $line->label,
                    $line->resultCount,
                    $line->newCount,
                    $line->seenCount,
                    $line->favoriteCount,
                    self::state($line),
                ],
                $report->lines,
            ),
        );

        $io->success(\sprintf(
            '%d recherche(s) créée(s), %d déjà présente(s) — %s (%s).',
            $report->created,
            $report->reused,
            $report->ownerEmail,
            $report->organizationName,
        ));

        if (null === $report->colleagueEmail) {
            $io->note('Aucun collègue dans cette entreprise : les recherches reçues en lecture seule ont été portées par le même recruteur.');
        } else {
            $io->note(\sprintf('Recherches reçues en lecture seule : portées par %s.', $report->colleagueEmail));
        }

        return Command::SUCCESS;
    }

    /** L'état lisible d'une carte : ce qui la distingue à l'écran des autres. */
    private static function state(DemoSearchLine $line): string
    {
        $marks = [];
        if ($line->closed) {
            $marks[] = 'close';
        }
        if ($line->byColleague) {
            $marks[] = 'du collègue';
        }
        if ($line->shared) {
            $marks[] = 'partagée';
        }

        return [] === $marks ? $line->state : implode(', ', $marks);
    }
}
