<?php

declare(strict_types=1);

namespace App\Domain\Candidate;

use App\Entity\Candidate;
use App\Enum\ConsentSource;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ouvre — et referme — un vivier de démonstration, task API-032.
 *
 * La recherche n'expose qu'un profil visible, actif ET consenti. Un vivier importé de Jarvi naît
 * fermé : sur un environnement de démonstration, toutes les recherches annoncent alors zéro profil
 * alors que le vivier est complet.
 *
 * Deux règles gouvernent l'écriture, et ce sont elles qui rendent le geste acceptable :
 *
 * - **on n'ouvre que ce qui est fermé** — un consentement donné par une personne n'est jamais
 *   réécrit, ni sa date, ni sa source ;
 * - **on ne referme que ce qu'on a ouvert** — la révocation filtre sur `DEMONSTRATION`, donc un
 *   consentement réel survit à la fermeture.
 *
 * Écriture en masse plutôt que par l'entité : trois mille profils passent en une requête, comme le
 * recalcul d'appartenance et pour la même raison. `Candidate::giveConsent()` reste le seul chemin
 * des parcours candidat.
 */
final readonly class DemoConsentGranter
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /** @return int profils ouverts */
    public function grant(): int
    {
        return (int) $this->em->createQuery(
            'UPDATE '.Candidate::class.' c SET c.consentGivenAt = :now, c.consentSource = :source '
            .'WHERE c.consentGivenAt IS NULL'
        )
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('source', ConsentSource::DEMONSTRATION)
            ->execute();
    }

    /** @return int profils refermés */
    public function revoke(): int
    {
        return (int) $this->em->createQuery(
            'UPDATE '.Candidate::class.' c SET c.consentGivenAt = NULL, c.consentSource = NULL '
            .'WHERE c.consentSource = :source'
        )
            ->setParameter('source', ConsentSource::DEMONSTRATION)
            ->execute();
    }

    /** Ce que la base porte déjà, pour que la commande dise où elle en est avant d'écrire. */
    public function census(): DemoConsentCensus
    {
        /** @var array{total: int|string, consented: int|string, demo: int|string} $row */
        $row = $this->em->createQuery(
            'SELECT COUNT(c.id) AS total, '
            .'SUM(CASE WHEN c.consentGivenAt IS NOT NULL THEN 1 ELSE 0 END) AS consented, '
            .'SUM(CASE WHEN c.consentSource = :source THEN 1 ELSE 0 END) AS demo '
            .'FROM '.Candidate::class.' c'
        )
            ->setParameter('source', ConsentSource::DEMONSTRATION)
            ->getSingleResult();

        return new DemoConsentCensus((int) $row['total'], (int) $row['consented'], (int) $row['demo']);
    }
}
