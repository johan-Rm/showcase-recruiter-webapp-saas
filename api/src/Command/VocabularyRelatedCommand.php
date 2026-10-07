<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\VocabularyType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Inspecte les concepts PROCHES d'un concept donné (task 096) — lecture seule.
 *
 * La relation métier ↔ compétences n'a jamais eu besoin d'être calculée : la co-occurrence traverse
 * les deux sacs, donc `vocabulary_concept_similarity` contient déjà les paires croisées
 * (« Data Scientist » ~ TensorFlow, Deep learning). Ce qui manquait, c'est un moyen de LES VOIR.
 *
 * Sert à trancher sur pièces la question ouverte de `096` : faut-il élargir une recherche par métier
 * aux compétences associées, et à partir de quel score ? Tant que cette décision n'est pas prise,
 * aucun code de recherche ne doit dépendre de cette relation.
 */
#[AsCommand(
    name: 'app:vocabulary:related',
    description: 'Affiche les concepts proches d’un concept (métier ↔ compétences inclus)',
)]
final class VocabularyRelatedCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('concept', InputArgument::OPTIONAL, 'Libellé exact du concept (vide = panorama des paires croisées).')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Ne garder que les voisins de ce type : METIER ou COMPETENCE.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Nombre de lignes.', '20');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $concept = $input->getArgument('concept');

        $type = null;
        $rawType = $input->getOption('type');
        if (\is_string($rawType) && '' !== $rawType) {
            $type = VocabularyType::tryFrom(mb_strtoupper($rawType));
            if (!$type instanceof VocabularyType) {
                $io->error(\sprintf('Type inconnu : %s (attendu METIER ou COMPETENCE).', $rawType));

                return Command::FAILURE;
            }
        }

        $rows = \is_string($concept) && '' !== $concept
            ? $this->neighboursOf($concept, $type, $limit)
            : $this->crossTypePanorama($limit);

        if ([] === $rows) {
            $io->warning('Aucune relation trouvée. Lancer app:vocabulary:similarity d’abord, ou vérifier le libellé.');

            return Command::SUCCESS;
        }

        $io->table(['Concept', 'Proche', 'Type', 'Profils communs', 'NPMI'], array_map(
            static fn (array $r): array => [
                (string) $r['source'],
                (string) $r['voisin'],
                (string) $r['type'],
                (string) $r['cooccurrences'],
                number_format((float) $r['npmi'], 2),
            ],
            $rows,
        ));

        return Command::SUCCESS;
    }

    /**
     * La paire est stockée UNE fois, `conceptA` portant le plus petit identifiant : les voisins d'un
     * concept se lisent donc des DEUX côtés.
     *
     * @return list<array<string, mixed>>
     */
    private function neighboursOf(string $concept, ?VocabularyType $type, int $limit): array
    {
        // Filtre concaténé plutôt que paramétré : Postgres ne sait pas typer un paramètre qui
        // n'apparaît que dans `:type IS NULL`. La valeur vient d'un enum, jamais de l'entrée brute.
        $typeFilter = $type instanceof VocabularyType ? \sprintf(" AND nb.type = '%s'", $type->value) : '';

        return $this->connection->fetchAllAssociative(
            <<<SQL
                SELECT src.pref_label AS source, nb.pref_label AS voisin, nb.type AS type,
                       s.cooccurrences, s.npmi
                FROM vocabulary_concept_similarity s
                JOIN vocabulary_concept src ON src.id IN (s.concept_a_id, s.concept_b_id)
                JOIN vocabulary_concept nb ON nb.id IN (s.concept_a_id, s.concept_b_id) AND nb.id <> src.id
                WHERE lower(src.pref_label) = lower(:concept){$typeFilter}
                ORDER BY s.npmi DESC
                LIMIT :limit
                SQL,
            ['concept' => $concept, 'limit' => $limit],
        );
    }

    /**
     * Panorama des paires CROISÉES métier ↔ compétence — la relation que `096` laissait ouverte.
     *
     * @return list<array<string, mixed>>
     */
    private function crossTypePanorama(int $limit): array
    {
        return $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT m.pref_label AS source, c.pref_label AS voisin, c.type AS type,
                       s.cooccurrences, s.npmi
                FROM vocabulary_concept_similarity s
                JOIN vocabulary_concept m ON m.id IN (s.concept_a_id, s.concept_b_id) AND m.type = 'METIER'
                JOIN vocabulary_concept c ON c.id IN (s.concept_a_id, s.concept_b_id) AND c.type = 'COMPETENCE'
                ORDER BY s.npmi DESC
                LIMIT :limit
                SQL,
            ['limit' => $limit],
        );
    }
}
