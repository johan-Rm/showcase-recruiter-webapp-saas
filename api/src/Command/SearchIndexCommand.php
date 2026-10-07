<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Pose (ou vérifie) l'outillage de recherche : les extensions `pg_trgm` et `unaccent`, et les index
 * trigrammes.
 *
 * POURQUOI UNE COMMANDE, et pas le schéma — Doctrine déduit les tables des entités, mais il ne crée
 * ni extension, ni index sur une EXPRESSION (`lower(headline)`). Le projet n'a par ailleurs aucune
 * migration. Sans un endroit d'où ça se rejoue, l'index existe sur la machine de celui qui l'a tapé
 * et nulle part ailleurs.
 *
 * TOUT EST IDEMPOTENT (`IF NOT EXISTS`) : la commande se rejoue sans risque, ce qui permet de la
 * brancher dans l'amorçage de dev et dans la remise à zéro de preprod.
 *
 * ⚠️ CE N'EST PAS UNE DÉPENDANCE DURE. Sans l'extension, tout continue de fonctionner : les
 * recherches utilisent `LIKE`, qui n'a besoin d'aucun index — elles sont seulement plus lentes. C'est
 * délibéré : une base de production qui n'aurait pas encore reçu l'extension doit dégrader, pas
 * tomber. `--check` sert précisément à savoir où on en est.
 */
#[AsCommand(
    name: 'app:search:index',
    description: 'Installe pg_trgm, unaccent et les index trigrammes de la recherche (idempotent) ; --check vérifie sans rien créer.',
)]
final class SearchIndexCommand extends Command
{
    /**
     * Les index trigrammes, et ce que chacun sert.
     *
     * Ils portent sur des EXPRESSIONS `lower(...)` parce que les recherches comparent en minuscules :
     * un index sur la colonne brute ne serait jamais utilisé.
     *
     * @var array<string, string>
     */
    /**
     * Les extensions, et ce que chacune apporte.
     *
     * `unaccent` ne sert aucun index — elle est STABLE, donc inutilisable dans une expression
     * indexée — mais elle rend les suggestions insensibles aux accents, sans quoi « dev » ne trouve
     * pas « Développeur » et personne ne tape son accent dans un champ de recherche.
     *
     * @var list<string>
     */
    private const array EXTENSIONS = ['pg_trgm', 'unaccent'];

    private const array INDEXES = [
        // Le mot-clé cherché dans le titre — `LIKE '%…%'`, qu'aucun index classique ne sait servir.
        'idx_jarvi_headline_trgm' => 'CREATE INDEX IF NOT EXISTS idx_jarvi_headline_trgm ON candidate_jarvi USING gin (lower(headline) gin_trgm_ops)',
        // Le même mot-clé cherché dans les compétences, lues en texte faute d'index sur du JSON.
        'idx_jarvi_skills_trgm' => 'CREATE INDEX IF NOT EXISTS idx_jarvi_skills_trgm ON candidate_jarvi USING gin (lower(skills::text) gin_trgm_ops)',
        // La résolution d'un intitulé de poste par le dictionnaire, canonique ou variante.
        'idx_vocabulary_label_trgm' => 'CREATE INDEX IF NOT EXISTS idx_vocabulary_label_trgm ON vocabulary_label USING gin (lower(label) gin_trgm_ops)',
        'idx_vocabulary_concept_trgm' => 'CREATE INDEX IF NOT EXISTS idx_vocabulary_concept_trgm ON vocabulary_concept USING gin (lower(pref_label) gin_trgm_ops)',
        // Le repli sur l'intitulé écrit, quand le dictionnaire ne connaît pas le terme (task API-037).
        'idx_candidate_job_title_trgm' => 'CREATE INDEX IF NOT EXISTS idx_candidate_job_title_trgm ON candidate_job_title USING gin (lower(title) gin_trgm_ops)',
    ];

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('check', null, InputOption::VALUE_NONE, 'Vérifier sans rien créer — sort en échec si quelque chose manque.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        return true === $input->getOption('check') ? $this->check($io) : $this->install($io);
    }

    private function install(SymfonyStyle $io): int
    {
        foreach (self::EXTENSIONS as $extension) {
            $this->connection->executeStatement(\sprintf('CREATE EXTENSION IF NOT EXISTS %s', $extension));
            $io->text(\sprintf('Extension %s : en place.', $extension));
        }

        foreach (self::INDEXES as $name => $sql) {
            $this->connection->executeStatement($sql);
            $io->text(\sprintf('Index %s : en place.', $name));
        }

        $io->success(\sprintf(
            'Recherche outillée : %d extensions, %d index.',
            \count(self::EXTENSIONS),
            \count(self::INDEXES),
        ));

        return Command::SUCCESS;
    }

    /**
     * Dit ce qui manque, sans rien créer.
     *
     * C'est la moitié utile de cette commande : l'écart entre la production et le développement ne se
     * voit pas au déploiement — il se voit à la première recherche, quand une requête s'effondre.
     * Cette vérification le rend constatable avant.
     */
    private function check(SymfonyStyle $io): int
    {
        $missing = [];

        foreach (self::EXTENSIONS as $extension) {
            if (false === $this->connection->fetchOne('SELECT 1 FROM pg_extension WHERE extname = ?', [$extension])) {
                $missing[] = 'extension '.$extension;
            }
        }

        foreach (array_keys(self::INDEXES) as $name) {
            if (false === $this->connection->fetchOne('SELECT 1 FROM pg_indexes WHERE indexname = ?', [$name])) {
                $missing[] = 'index '.$name;
            }
        }

        if ([] !== $missing) {
            $io->warning('Manquant : '.implode(', ', $missing));
            // Ce que la commande apporte n'est pas de la même nature selon ce qui manque : les index
            // accélèrent, `unaccent` fait TROUVER ce qui n'était pas trouvé. Le dire évite de croire
            // qu'on ne perd que du temps.
            $io->text('Les recherches fonctionnent quand même : en parcours complet sans les index, et accentuées sans `unaccent`.');

            return Command::FAILURE;
        }

        $io->success('Extension et index en place.');

        return Command::SUCCESS;
    }
}
