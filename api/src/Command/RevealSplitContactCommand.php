<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\RevealType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Convertit les dévoilements `CONTACT` hérités en `EMAIL` + `PHONE` (task `API-018`).
 *
 * `CONTACT` vendait les deux canaux d'un bloc pour un crédit. Depuis qu'ils s'achètent séparément,
 * une ligne `CONTACT` ne correspond plus à aucun accès : sans conversion, une entreprise qui avait
 * payé perdrait ce qu'elle possède et devrait le racheter. Cette commande existe pour que cela
 * n'arrive pas.
 *
 * POURQUOI UNE COMMANDE ET PAS UNE MIGRATION : le dépôt n'introduit les migrations Doctrine qu'à la
 * mise en production (cf. `chore(db): différer les migrations à la prod`) — en phase MVP le schéma
 * dev est recréé depuis le mapping. Une commande versionnée se teste, se relit et se lance au bon
 * moment sur la base qui en a besoin ; une migration ici n'aurait eu ni véhicule ni destinataire.
 *
 * IDEMPOTENTE : elle ignore les canaux déjà présents et peut donc se relancer sans créer de doublon
 * — la contrainte d'unicité (organisation, candidat, type) la protégerait de toute façon, mais
 * échouer sur une seconde exécution serait un piège.
 *
 * Elle ne DÉBITE RIEN : ce qui est converti a déjà été payé.
 */
#[AsCommand(
    name: 'app:reveals:split-contact',
    description: 'Convertit les dévoilements CONTACT hérités en EMAIL + PHONE (sans débit)',
)]
final class RevealSplitContactCommand extends Command
{
    /**
     * La valeur n'existe plus dans l'enum : elle ne peut donc vivre qu'en littéral, et seulement
     * ici. C'est le prix de la suppression, et il est payé à un seul endroit.
     */
    private const string LEGACY_CONTACT = 'CONTACT';

    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Compte sans rien écrire.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        // Requête SQL et non DQL : `CONTACT` n'est plus une valeur de l'enum, donc Doctrine refuse
        // de l'hydrater en entité. On lit les colonnes brutes.
        $legacy = $this->em->getConnection()->fetchAllAssociative(
            'SELECT id, organization_id, candidate_id, revealed_by_id, created_at
             FROM revealed_profile
             WHERE type = :type AND deleted_at IS NULL',
            ['type' => self::LEGACY_CONTACT],
        );

        if ([] === $legacy) {
            $io->success('Aucun dévoilement CONTACT hérité : rien à convertir.');

            return Command::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($legacy as $row) {
            foreach ([RevealType::EMAIL, RevealType::PHONE] as $type) {
                if ($this->exists((int) $row['organization_id'], (int) $row['candidate_id'], $type)) {
                    ++$skipped;
                    continue;
                }
                if (!$dryRun) {
                    $this->insert($row, $type);
                }
                ++$created;
            }
        }

        if (!$dryRun) {
            $this->em->getConnection()->executeStatement(
                'DELETE FROM revealed_profile WHERE type = :type',
                ['type' => self::LEGACY_CONTACT],
            );
        }

        $io->success(\sprintf(
            '%d dévoilement(s) CONTACT → %d accès %s, %d déjà présent(s).',
            \count($legacy),
            $created,
            $dryRun ? 'à créer (dry-run)' : 'créés',
            $skipped,
        ));

        return Command::SUCCESS;
    }

    private function exists(int $organizationId, int $candidateId, RevealType $type): bool
    {
        return (bool) $this->em->getConnection()->fetchOne(
            'SELECT 1 FROM revealed_profile
             WHERE organization_id = :org AND candidate_id = :candidate AND type = :type',
            ['org' => $organizationId, 'candidate' => $candidateId, 'type' => $type->value],
        );
    }

    /**
     * L'auteur et la DATE du dévoilement d'origine sont conservés : l'historique dirait faux
     * autrement, et c'est lui qui sert à justifier une facturation.
     *
     * @param array<string, mixed> $row
     */
    private function insert(array $row, RevealType $type): void
    {
        $this->em->getConnection()->executeStatement(
            'INSERT INTO revealed_profile (uuid, organization_id, candidate_id, revealed_by_id, type, created_at, updated_at)
             VALUES (gen_random_uuid(), :org, :candidate, :by, :type, :created, :created)',
            [
                'org' => $row['organization_id'],
                'candidate' => $row['candidate_id'],
                'by' => $row['revealed_by_id'],
                'type' => $type->value,
                'created' => $row['created_at'],
            ],
        );
    }
}
