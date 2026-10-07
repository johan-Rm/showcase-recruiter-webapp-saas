<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Jarvi\VocabularyReprojector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rejoue le dictionnaire sur le vivier déjà chargé (task API-035) — la règle vit dans
 * {@see VocabularyReprojector}, cette commande ne fait que la piloter et rendre compte.
 */
#[AsCommand(
    name: 'app:vocabulary:reproject',
    description: 'Rejoue le dictionnaire sur le vivier déjà chargé (métiers exercés et poste actuel), sans Jarvi',
)]
final class VocabularyReprojectCommand extends Command
{
    public function __construct(private readonly VocabularyReprojector $reprojector)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Borne le nombre de profils reprojetés.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Calcule et compte sans rien écrire.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $limit = null !== $input->getOption('limit') ? max(1, (int) $input->getOption('limit')) : null;

        // La barre ne s'ouvre qu'au premier lot : le nombre de profils n'est connu qu'une fois la
        // liste établie, et un vivier vide ne mérite pas de barre du tout.
        $started = false;
        $report = $this->reprojector->reproject($limit, $dryRun, static function (int $done, int $total) use ($io, &$started): void {
            if (!$started) {
                $io->progressStart($total);
                $started = true;
            }
            $io->progressAdvance($done);
        });
        if ($started) {
            $io->progressFinish();
        }

        if (0 === $report->profiles) {
            $io->warning('Aucun profil à reprojeter.');

            return Command::SUCCESS;
        }

        $io->success(($dryRun ? '[dry-run] ' : '').\sprintf(
            'Reprojection : %d profils parcourus, %d ligne(s) de projection posée(s) ou retirée(s),'
            .' %d poste(s) actuel(s) rerattaché(s).',
            $report->profiles,
            $report->moved,
            $report->retitled,
        ));

        return Command::SUCCESS;
    }
}
