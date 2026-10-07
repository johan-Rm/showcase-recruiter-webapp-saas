<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Vocabulary\CompanySectorClassifier;
use App\Domain\Vocabulary\CompanySectorClassifyReport;
use App\Domain\Vocabulary\CompanySectorHarvester;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Chantier secteur (task 105) : classe les entreprises du vivier dans un secteur (LLM), en batch,
 * et écrit le résultat dans `sectors.json` — relu en `git diff`, chargé ensuite en base par un import
 * dédié. Incrémentale (ne reclasse pas l'existant). `--dry-run` interroge le modèle sans écrire.
 */
#[AsCommand(
    name: 'app:company:sectors:classify',
    description: 'Classe les entreprises du vivier dans un secteur (LLM) → sectors.json',
)]
final class CompanySectorsClassifyCommand extends Command
{
    public function __construct(
        private readonly CompanySectorHarvester $harvester,
        private readonly CompanySectorClassifier $classifier,
        private readonly ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Chemin du JSON de secteurs.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Interroge le modèle mais n’écrit pas le fichier.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $path = $this->filePath($input->getOption('file'));

        $sectors = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
        if (!\is_array($sectors)) {
            $io->error('JSON de secteurs invalide.');

            return Command::FAILURE;
        }

        $report = new CompanySectorClassifyReport();
        $companies = $this->harvester->harvest();
        $sectors = $this->classifier->classify($companies, $sectors, $report, static function (string $line) use ($io): void {
            $io->text($line);
        });

        if (!$dryRun) {
            file_put_contents($path, json_encode($sectors, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));
        }

        $io->success(($dryRun ? '[dry-run] ' : '').\sprintf(
            'Classification : %d entreprises soumises → %d classées.',
            $report->companiesSubmitted,
            $report->companiesClassified,
        ));
        if (!$dryRun) {
            $io->note('Relire le git diff de sectors.json, puis charger en base (import secteur).');
        }

        return Command::SUCCESS;
    }

    private function filePath(mixed $option): string
    {
        if (\is_string($option) && '' !== $option) {
            return $option;
        }

        return $this->params->get('kernel.project_dir').'/data/vocabulary/sectors.json';
    }
}
