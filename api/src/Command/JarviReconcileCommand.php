<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Jarvi\PerimeterReconciler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Réconciliation quotidienne du périmètre Jarvi (tâche 020) : désactive les projections sorties
 * du périmètre (jamais de suppression). Cadencé par cron (≈1×/jour) en prod.
 */
#[AsCommand(name: 'app:jarvi:reconcile', description: 'Désactive les projections hors du périmètre Jarvi')]
final class JarviReconcileCommand extends Command
{
    public function __construct(private readonly PerimeterReconciler $reconciler)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $report = $this->reconciler->reconcile();

        $io->success(\sprintf(
            'Réconciliation Jarvi : périmètre = %d profils, %d désactivée(s), %d réactivée(s).',
            $report->perimeterSize,
            $report->deactivated,
            $report->reactivated,
        ));

        return Command::SUCCESS;
    }
}
