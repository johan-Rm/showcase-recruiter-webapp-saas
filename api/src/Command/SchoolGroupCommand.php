<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Vocabulary\SchoolGrouper;
use App\Domain\Vocabulary\SchoolGroupingReport;
use App\Domain\Vocabulary\SchoolHarvester;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Regroupement sémantique des écoles (task 115, chaîne D) : ramène les graphies d'un même
 * établissement à un nom canonique via LLM, en batch, et écrit `schools.json` — relu en `git diff`.
 *
 * DÉSACTIVÉE PAR DÉFAUT. La récolte (`app:school:harvest`) a montré que l'égalité de chaîne ne
 * fusionne que 3,2 % des noms ; le regroupement restant est sémantique et exige un LLM sur 2 366
 * entrées. La valeur produit — un filtre par école — n'est pas confirmée par le cadrage, donc on ne
 * dépense pas la clé. Le flag `SCHOOL_GROUPING_ENABLED` lève le verrou le jour où la décision est
 * prise ; d'ici là, la commande refuse de s'exécuter tout en restant complète, câblée et testée.
 */
#[AsCommand(
    name: 'app:school:group',
    description: 'Regroupe les écoles par nom canonique (LLM) → schools.json — DÉSACTIVÉE par défaut',
)]
final class SchoolGroupCommand extends Command
{
    public function __construct(
        private readonly SchoolHarvester $harvester,
        private readonly SchoolGrouper $grouper,
        private readonly ParameterBagInterface $params,
        private readonly bool $enabled,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Chemin du JSON d’écoles.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Interroge le modèle mais n’écrit pas le fichier.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->enabled) {
            $io->warning('Regroupement des écoles DÉSACTIVÉ (task 115). Aucun appel LLM émis.');
            $io->text([
                'Raison : l’égalité de chaîne ne fusionne que 3,2 % des noms ; le reste est sémantique',
                'et coûte un passage LLM sur ~2 366 entrées, pour un filtre par école dont la valeur',
                'produit n’est pas confirmée.',
                '',
                'Pour lever le verrou : SCHOOL_GROUPING_ENABLED=1 dans api/.env, une fois la décision prise.',
            ]);

            return Command::SUCCESS;
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $path = $this->filePath($input->getOption('file'));

        $baseline = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
        if (!\is_array($baseline)) {
            $io->error('JSON d’écoles invalide.');

            return Command::FAILURE;
        }

        $report = new SchoolGroupingReport();
        $schools = $this->harvester->harvest();
        $baseline = $this->grouper->group($schools, $baseline, $report, static function (string $line) use ($io): void {
            $io->text($line);
        });

        if (!$dryRun) {
            file_put_contents($path, json_encode($baseline, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));
        }

        $io->success(($dryRun ? '[dry-run] ' : '').\sprintf(
            'Regroupement : %d établissements soumis → %d regroupés, %d noms canoniques distincts.',
            $report->schoolsSubmitted,
            $report->schoolsGrouped,
            $report->canonicalNames,
        ));
        if (!$dryRun) {
            $io->note('Relire le git diff de schools.json avant tout usage.');
        }

        return Command::SUCCESS;
    }

    private function filePath(mixed $option): string
    {
        if (\is_string($option) && '' !== $option) {
            return $option;
        }

        return $this->params->get('kernel.project_dir').'/data/vocabulary/schools.json';
    }
}
