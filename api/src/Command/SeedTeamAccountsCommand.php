<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Account\TeamAccountSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Amorce les comptes de recette showcase-recruiter-webapp-saas, spécialisés par rôle (candidat / entreprise / admin,
 * plus un accès complet), pour la PREPROD. Idempotent (rejouable, ne purge pas). Refuse de
 * s'exécuter en production sauf `--force`, par sécurité.
 */
#[AsCommand(
    name: 'app:accounts:seed-team',
    description: 'Crée/rafraîchit les comptes de recette (candidat / entreprise / admin) pour la preprod',
)]
final class SeedTeamAccountsCommand extends Command
{
    public function __construct(
        private readonly TeamAccountSeeder $seeder,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe partagé des comptes (généré si absent)')
            ->addOption('credits', null, InputOption::VALUE_REQUIRED, "Crédits offerts à l'entreprise partagée", '100')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Autoriser l\'exécution même en production');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $force = (bool) $input->getOption('force');
        if ('prod' === $this->environment && !$force) {
            $io->error('Refus : commande destinée aux environnements de test. Utilisez --force pour passer outre.');

            return Command::FAILURE;
        }

        $password = $input->getOption('password');
        $generated = false;
        if (!\is_string($password) || '' === $password) {
            $password = bin2hex(random_bytes(6));
            $generated = true;
        }

        $credits = max(0, (int) $input->getOption('credits'));

        $report = $this->seeder->seed($password, $credits);

        $io->success(\sprintf(
            'Amorçage terminé : %d compte(s) créé(s), %d mis à jour ; %d candidat(s), %d recruteur(s) ; entreprise %s.',
            $report->usersCreated,
            $report->usersUpdated,
            $report->candidatesCreated,
            $report->recruitersCreated,
            $report->organizationCreated ? 'partagée créée' : 'partagée déjà présente',
        ));

        if ($generated) {
            $io->warning(\sprintf('Mot de passe partagé généré : %s (à transmettre à l\'équipe, non ré-affiché).', $password));
        } else {
            $io->note('Mot de passe partagé appliqué (fourni via --password).');
        }

        return Command::SUCCESS;
    }
}
