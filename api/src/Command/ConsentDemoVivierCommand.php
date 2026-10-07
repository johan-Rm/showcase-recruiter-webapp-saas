<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Candidate\DemoConsentGranter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Ouvre le vivier d'un environnement de démonstration, et le referme — task API-032.
 *
 * Un profil importé de Jarvi n'est pas exposable tant que la personne n'a pas consenti : sur une
 * preprod, les recherches annoncent donc zéro profil alors que le vivier est complet. Cette commande
 * lève la barrière **en le disant** — la source posée est `DEMONSTRATION`, jamais celle d'un parcours
 * candidat.
 *
 * Réservée aux environnements de démonstration, comme `app:accounts:seed-team`.
 */
#[AsCommand(
    name: 'app:candidates:consent-demo',
    description: 'Rend le vivier exposable sur un environnement de démonstration (réversible par --revoke)',
)]
final class ConsentDemoVivierCommand extends Command
{
    public function __construct(
        private readonly DemoConsentGranter $granter,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('revoke', null, InputOption::VALUE_NONE, 'Refermer les profils ouverts pour la démonstration')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Autoriser l\'exécution même en production');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ('prod' === $this->environment && !(bool) $input->getOption('force')) {
            $io->error('Refus : commande destinée aux environnements de démonstration. Utilisez --force pour passer outre.');

            return Command::FAILURE;
        }

        $before = $this->granter->census();
        $io->table(
            ['Profils', 'Consentis', 'dont démonstration', 'dont consentement réel'],
            [[$before->total, $before->consented, $before->demonstration, $before->genuine()]],
        );

        if ((bool) $input->getOption('revoke')) {
            $closed = $this->granter->revoke();
            $io->success(\sprintf('%d profil(s) refermé(s). Les %d consentement(s) réel(s) sont intacts.', $closed, $before->genuine()));

            return Command::SUCCESS;
        }

        $opened = $this->granter->grant();
        $io->success(\sprintf(
            '%d profil(s) ouvert(s) pour la démonstration. Les %d consentement(s) réel(s) sont intacts.',
            $opened,
            $before->genuine(),
        ));
        $io->note('Recalculez les jeux de résultats ensuite : app:searches:seed-demo relance le recalcul des recherches existantes.');

        if (0 === $opened) {
            $io->comment('Rien à ouvrir : tous les profils portent déjà un consentement.');
        }

        return Command::SUCCESS;
    }
}
