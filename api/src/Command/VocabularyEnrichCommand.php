<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Vocabulary\VocabularyEnricher;
use App\Domain\Vocabulary\VocabularyEnrichReport;
use App\Domain\Vocabulary\VocabularyHarvester;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Étape 2 du pipeline vocabulaire : range les termes récurrents pas encore rattachés dans
 * `taxonomy.json`, en un batch (jamais dans une boucle d'exploration). Le résultat est relu en
 * `git diff` avant d'être chargé par `app:vocabulary:import`. `--dry-run` interroge le modèle mais
 * n'écrit pas le fichier (l'appel LLM a donc bien lieu).
 */
#[AsCommand(
    name: 'app:vocabulary:enrich',
    description: 'Regroupe les termes récurrents en concepts (LLM) et enrichit la taxonomie',
)]
final class VocabularyEnrichCommand extends Command
{
    public function __construct(
        private readonly VocabularyHarvester $harvester,
        private readonly VocabularyEnricher $enricher,
        private readonly ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Chemin du JSON de taxonomie à enrichir.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Interroge le modèle mais n’écrit pas le fichier.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $path = $this->filePath($input->getOption('file'));

        if (!is_file($path)) {
            $io->error(\sprintf('Fichier introuvable : %s', $path));

            return Command::FAILURE;
        }

        $taxonomy = json_decode((string) file_get_contents($path), true);
        if (!\is_array($taxonomy)) {
            $io->error('JSON de taxonomie invalide.');

            return Command::FAILURE;
        }

        $report = new VocabularyEnrichReport();
        $harvested = $this->harvester->harvest();
        $taxonomy = $this->enricher->enrich($harvested, $taxonomy, $report, static function (string $line) use ($io): void {
            $io->text($line);
        });

        if (!$dryRun) {
            file_put_contents($path, json_encode($taxonomy, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));
        }

        $io->success(($dryRun ? '[dry-run] ' : '').\sprintf(
            'Enrichissement : %d termes soumis → %d concepts créés, %d synonymes rattachés, %d écartés (bruit).',
            $report->termsSubmitted,
            $report->conceptsCreated,
            $report->synonymsAttached,
            $report->noiseDropped,
        ));
        if (!$dryRun) {
            $io->note('Relire le git diff de taxonomy.json, puis charger via app:vocabulary:import.');
        }

        return Command::SUCCESS;
    }

    private function filePath(mixed $option): string
    {
        if (\is_string($option) && '' !== $option) {
            return $option;
        }

        return $this->params->get('kernel.project_dir').'/data/vocabulary/taxonomy.json';
    }
}
