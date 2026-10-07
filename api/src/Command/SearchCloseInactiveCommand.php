<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Search\InactiveSearchCloser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clôt les recherches que plus personne ne touche — task API-049.
 *
 * À passer sur le rythme quotidien : le seuil se compte en jours, une passe par jour suffit. Elle est
 * **idempotente**, une seconde exécution dans la même journée ne trouve plus rien à fermer.
 *
 * `--dry-run` parce que la première mise en service se regarde avant de s'appliquer : sur une base
 * qui n'a jamais rangé, la première passe peut fermer beaucoup d'un coup.
 */
#[AsCommand(
    name: 'app:searches:close-inactive',
    description: 'Clôt les recherches sans contact depuis le délai configuré (réouvrables)',
)]
final class SearchCloseInactiveCommand extends Command
{
    public function __construct(private readonly InactiveSearchCloser $closer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Lister ce qui serait clos, sans rien fermer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = $this->closer->inactivityDays();

        if (true === $input->getOption('dry-run')) {
            $candidates = $this->closer->pending();
            $io->section(\sprintf('Sans contact depuis %d jours : %d recherche(s)', $days, \count($candidates)));
            $this->listing($io, $candidates);
            $io->note('Aucune recherche fermée (--dry-run).');

            return Command::SUCCESS;
        }

        $closed = $this->closer->close();
        $io->success(\sprintf('%d recherche(s) close(s) — sans contact depuis %d jours.', \count($closed), $days));
        $this->listing($io, $closed);

        return Command::SUCCESS;
    }

    /**
     * Les recherches concernées, nommées.
     *
     * Une clôture est réversible mais elle SE VOIT : le recruteur retrouvera sa recherche sous un
     * pli. Une commande qui range en silence rend ce déplacement inexplicable le lendemain.
     *
     * @param list<array{uuid: string, name: string}> $searches
     */
    private function listing(SymfonyStyle $io, array $searches): void
    {
        if ([] === $searches) {
            return;
        }

        $io->listing(array_map(
            static fn (array $search): string => \sprintf(
                '%s — %s',
                $search['uuid'],
                '' === $search['name'] ? '(sans nom)' : $search['name'],
            ),
            $searches,
        ));
    }
}
