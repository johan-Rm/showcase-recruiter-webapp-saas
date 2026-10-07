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
 * Ce que les recruteurs cherchent et que le dictionnaire ne connaît pas (task API-024).
 *
 * Sa sortie est une liste à INSTRUIRE, pas un enrichissement : rien n'est ajouté au vocabulaire ici.
 * Un terme fréquent peut être un métier qui manque, une compétence tapée dans le mauvais champ, ou
 * une faute que `API-023` n'a pas rattrapée — c'est un humain qui tranche.
 *
 * Une commande plutôt qu'un écran : la liste se lit une fois par mois, et la famille existe déjà
 * (`app:vocabulary:similarity`, `app:vocabulary:related`), hors du pipeline quotidien. Sans elle,
 * consulter cette information demanderait un accès SQL à la production, ce qui revient à ne jamais la
 * consulter.
 */
#[AsCommand(
    name: 'app:vocabulary:missing',
    description: 'Liste les intitulés de poste cherchés que le dictionnaire ne connaît pas, du plus fréquent au moins fréquent.',
)]
final class VocabularyMissingCommand extends Command
{
    private const int DEFAULT_LIMIT = 50;

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Nombre de termes affichés.', self::DEFAULT_LIMIT);
        $this->addOption('min', null, InputOption::VALUE_REQUIRED, 'Ne montrer qu’au-delà de ce nombre de recherches.', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var list<array{label: string, occurrences: int|string, last_searched_at: string}> $rows */
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT label, occurrences, last_searched_at
                FROM unresolved_job_term
                WHERE deleted_at IS NULL AND occurrences >= :min
                ORDER BY occurrences DESC, last_searched_at DESC
                LIMIT :max
                SQL,
            [
                'min' => max(1, (int) $input->getOption('min')),
                'max' => max(1, (int) $input->getOption('limit')),
            ],
        );

        if ([] === $rows) {
            $io->success('Aucun intitulé cherché n’est resté sans réponse.');

            return Command::SUCCESS;
        }

        $io->table(
            ['Intitulé cherché', 'Recherches', 'Dernière'],
            array_map(
                static fn (array $row): array => [
                    $row['label'],
                    (string) $row['occurrences'],
                    mb_substr($row['last_searched_at'], 0, 10),
                ],
                $rows,
            ),
        );

        $io->text(\sprintf('%d intitulé(s). Ce qui revient souvent mérite d’entrer au dictionnaire.', \count($rows)));

        return Command::SUCCESS;
    }
}
