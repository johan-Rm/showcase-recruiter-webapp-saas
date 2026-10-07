<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Reporting\QualificationReporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * État chiffré de la qualification du vivier, dimension par dimension.
 *
 * Usage courant : une photo AVANT de lancer un pipeline, une APRÈS, et la comparaison des deux.
 * C'est la seule façon de constater qu'une chaîne a produit ce qu'on attendait — et d'attraper une
 * régression silencieuse, comme un référentiel non chargé qui viderait l'expérience par secteur.
 *
 * LECTURE SEULE : n'écrit jamais en base.
 */
#[AsCommand(
    name: 'app:qualification:report',
    description: 'État de qualification du vivier (photo, et comparaison avant/après traitement)',
)]
final class QualificationReportCommand extends Command
{
    public function __construct(private readonly QualificationReporter $reporter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Écrit la photo en JSON à ce chemin.')
            ->addOption('compare', null, InputOption::VALUE_REQUIRED, 'Photo JSON antérieure : affiche l’écart.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $snapshot = $this->reporter->snapshot();

        $previous = null;
        $comparePath = $input->getOption('compare');
        if (\is_string($comparePath) && '' !== $comparePath) {
            if (!is_file($comparePath)) {
                $io->error(\sprintf('Photo introuvable : %s', $comparePath));

                return Command::FAILURE;
            }
            $decoded = json_decode((string) file_get_contents($comparePath), true);
            if (!\is_array($decoded)) {
                $io->error('Photo antérieure illisible.');

                return Command::FAILURE;
            }
            /** @var array<string, array<string, int>> $previous */
            $previous = $decoded;
        }

        foreach ($snapshot as $section => $metrics) {
            $io->section($section);
            $io->table(
                null === $previous ? ['Métrique', 'Valeur'] : ['Métrique', 'Avant', 'Après', 'Écart'],
                $this->rows($metrics, $previous[$section] ?? null),
            );
        }

        $path = $input->getOption('output');
        if (\is_string($path) && '' !== $path) {
            $json = json_encode($snapshot, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE);
            if (false === file_put_contents($path, $json)) {
                $io->error(\sprintf('Écriture impossible : %s', $path));

                return Command::FAILURE;
            }
            $io->success(\sprintf('Photo écrite → %s', $path));
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<string, int>      $metrics
     * @param array<string, int>|null $previous
     *
     * @return list<list<string>>
     */
    private function rows(array $metrics, ?array $previous): array
    {
        $rows = [];
        foreach ($metrics as $label => $value) {
            if (null === $previous) {
                $rows[] = [$label, number_format($value, 0, ',', ' ')];

                continue;
            }

            $before = $previous[$label] ?? 0;
            $rows[] = [
                $label,
                number_format($before, 0, ',', ' '),
                number_format($value, 0, ',', ' '),
                $this->delta($value - $before),
            ];
        }

        return $rows;
    }

    private function delta(int $delta): string
    {
        if (0 === $delta) {
            return '=';
        }

        return \sprintf('%+s', number_format($delta, 0, ',', ' '));
    }
}
