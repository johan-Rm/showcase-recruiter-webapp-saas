<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Jarvi\PerimeterReconciler;
use App\Domain\Jarvi\ProfileIngestor;
use App\Domain\Search\SearchMembershipRecalculator;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Import CLI du périmètre Jarvi vers la réplica locale showcase-recruiter-webapp-saas (tâche 034).
 * Par défaut : incrémental quotidien via curseur `updatedAt`. `--full` sert au chargement initial.
 */
#[AsCommand(
    name: 'app:jarvi:profiles:import',
    description: 'Importe les profils Jarvi du périmètre showcase-recruiter-webapp-saas',
    aliases: ['app:jarvi:ingest'],
)]
final class JarviIngestCommand extends Command
{
    public function __construct(
        private readonly ProfileIngestor $ingestor,
        private readonly PerimeterReconciler $reconciler,
        private readonly SearchMembershipRecalculator $recalculator,
        private readonly Connection $connection,
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('full', null, InputOption::VALUE_NONE, 'Import complet du périmètre Jarvi.')
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Import incrémental depuis une date ISO-8601 sans modifier le curseur persistant.')
            ->addOption('profile-id', null, InputOption::VALUE_REQUIRED, 'Rafraîchit un seul profil Jarvi par UUID.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule les créations/mises à jour sans écrire en base.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Borne le nombre de profils tirés depuis Jarvi.')
            ->addOption('reconcile', null, InputOption::VALUE_NONE, 'Réconcilie le périmètre après import, ou seul si utilisé avec --dry-run/--limit.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $limit = $this->limit($input->getOption('limit'));
        $profileId = $this->stringOption($input->getOption('profile-id'));

        if (null !== $profileId) {
            $found = $this->ingestor->refreshOne($profileId, $dryRun);
            if (!$found) {
                $io->warning(\sprintf('Profil Jarvi introuvable : %s', $profileId));

                return Command::FAILURE;
            }

            $this->grantDevConsent($dryRun, $io);
            $io->success(($dryRun ? '[dry-run] ' : '').\sprintf('Profil Jarvi rafraîchi : %s', $profileId));

            return Command::SUCCESS;
        }

        $report = (bool) $input->getOption('full')
            ? $this->ingestor->ingestFull($dryRun, $limit)
            : $this->ingestor->ingestUpdated($dryRun, $this->since($input->getOption('since')), $limit);

        $summary = ($dryRun ? '[dry-run] ' : '').\sprintf(
            'Import Jarvi : %d créés, %d mis à jour, %d ignorés, %d en erreur. Curseur : %s',
            $report->created,
            $report->updated,
            $report->skipped,
            $report->errors,
            $report->cursor?->format(DateTimeInterface::ATOM) ?? 'aucun',
        );

        // Curseur figé au dernier profil sain : le passage suivant reprendra les profils en erreur.
        $report->errors > 0
            ? $io->warning($summary.' Curseur figé au dernier profil sain — voir les logs pour le détail des erreurs.')
            : $io->success($summary);

        $this->grantDevConsent($dryRun, $io);

        if ((bool) $input->getOption('reconcile')) {
            $reconciliation = $this->reconciler->reconcile($dryRun, $limit);
            $io->success(($dryRun ? '[dry-run] ' : '').\sprintf(
                'Réconciliation Jarvi : périmètre %d, %d désactivés, %d réactivés.',
                $reconciliation->perimeterSize,
                $reconciliation->deactivated,
                $reconciliation->reactivated,
            ));
        }

        // Le badge `+N` du cockpit se date ici (task 072) : c'est l'ingestion qui fait rejoindre un
        // profil au jeu de résultats d'une recherche — y compris un profil ancien dont une fenêtre
        // de présence au Maroc vient de s'ouvrir. Rien à recalculer sur un dry-run : rien n'a bougé.
        if (!$dryRun) {
            $entered = $this->recalculator->recalculateAll();
            $io->success(\sprintf('Recherches : %d entrées dans un jeu de résultats.', $entered));
        }

        return Command::SUCCESS;
    }

    private function grantDevConsent(bool $dryRun, SymfonyStyle $io): void
    {
        if ($dryRun || 'dev' !== $this->environment) {
            return;
        }

        $updated = $this->connection->executeStatement(<<<'SQL'
            UPDATE candidate
            SET consent_given_at = COALESCE(visibility_updated_at, synced_at, updated_at, NOW()),
                consent_source = 'ONBOARDING',
                updated_at = NOW()
            WHERE active = true
              AND visibility = 'VISIBLE'
              AND deleted_at IS NULL
              AND consent_given_at IS NULL
            SQL);

        if ($updated > 0) {
            $io->note(\sprintf('Dev uniquement : %d profil(s) visible(s) marqué(s) consentis pour exposition vivier.', $updated));
        }
    }

    private function since(mixed $value): ?DateTimeImmutable
    {
        $date = $this->stringOption($value);
        if (null === $date) {
            return null;
        }

        try {
            return new DateTimeImmutable($date);
        } catch (Exception $e) {
            throw new InvalidArgumentException(\sprintf('Option --since invalide : %s', $date), previous: $e);
        }
    }

    private function limit(mixed $value): ?int
    {
        $limit = $this->stringOption($value);
        if (null === $limit) {
            return null;
        }

        return max(1, (int) $limit);
    }

    private function stringOption(mixed $value): ?string
    {
        return \is_string($value) && '' !== $value ? $value : null;
    }
}
