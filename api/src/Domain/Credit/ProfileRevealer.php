<?php

declare(strict_types=1);

namespace App\Domain\Credit;

use App\Domain\Candidate\RevealableContent;
use App\Domain\Notification\NotificationEmitter;
use App\Entity\Candidate;
use App\Entity\CreditTransaction;
use App\Entity\CreditWallet;
use App\Entity\Organization;
use App\Entity\Recruiter;
use App\Entity\RevealedProfile;
use App\Enum\CreditTransactionType;
use App\Enum\RevealType;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

/**
 * Dévoilement d'un ACCÈS à un profil pour une entreprise (cœur sensible, cf. data-integrity.md).
 *
 * Trois accès indépendants — coordonnées, CV, compte rendu — à un crédit chacun (task 155). Payer
 * l'un ne donne rien sur les autres : le débit et l'idempotence portent sur (entreprise, candidat,
 * type), jamais sur le seul candidat.
 *
 * Garanties :
 *  - ATOMIQUE  : débit wallet + CreditTransaction + RevealedProfile dans une transaction unique.
 *  - SÉRIALISÉ : verrou pessimiste sur le wallet → pas de double-dépense concurrente.
 *  - IDEMPOTENT: un accès déjà payé n'est pas re-débité (unicité (organization, candidate, type)).
 *  - TOUT OU RIEN: un lot passe entier ou pas du tout — jamais à moitié débité.
 *  - PLAFONNÉ  : au-delà de MAX_REVEAL_BATCH, refus sec (cf. CreditPolicy).
 *  - JAMAIS À VIDE: un accès dont le profil n'a rien à servir n'est ni facturé ni enregistré.
 *  - SOLDE ≥ 0 : refus si crédits insuffisants (backstop CHECK en base).
 */
final readonly class ProfileRevealer
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationEmitter $notifications,
    ) {
    }

    /** `null` quand ce profil n'a rien à servir sur cet accès : il n'y a alors rien à facturer. */
    public function reveal(Organization $organization, Candidate $candidate, Recruiter $revealedBy, RevealType $type): ?RevealedProfile
    {
        return $this->revealMany($organization, [$candidate], $revealedBy, $type)[0] ?? null;
    }

    /**
     * Dévoile un LOT, en TOUT OU RIEN.
     *
     * Le solde est vérifié pour l'ensemble avant le moindre débit : un lot à moitié passé laisserait
     * des crédits dépensés sans que l'appelant sache lesquels. Ce qui est déjà payé ne se repaie pas
     * et ne compte pas dans le coût — recocher un profil déjà dévoilé ne facture rien.
     *
     * Ce qui n'a RIEN À SERVIR ne se facture pas non plus, et ne laisse aucune ligne : la donnée qui
     * arriverait plus tard par une synchro se paiera à ce moment-là, contre quelque chose. Une ligne
     * gratuite posée ici rendrait le profil « déjà dévoilé » et l'offrirait alors pour rien.
     *
     * Un lot dont AUCUN profil n'a la donnée aboutit sans débit ni ligne — comme un lot entièrement
     * déjà payé, qui se comporte ainsi depuis toujours.
     *
     * @param list<Candidate> $candidates
     *
     * @return list<RevealedProfile> dans l'ordre reçu — les profils sans rien à servir n'y figurent
     *                               pas, la liste peut donc être plus courte que le lot, voire vide
     *
     * @throws RevealBatchTooLargeException au-delà du plafond
     * @throws InsufficientCreditsException si le solde ne couvre pas TOUT le lot
     */
    public function revealMany(Organization $organization, array $candidates, Recruiter $revealedBy, RevealType $type): array
    {
        if (\count($candidates) > CreditPolicy::MAX_REVEAL_BATCH) {
            throw new RevealBatchTooLargeException(\sprintf('Lot de %d profils : le maximum est %d.', \count($candidates), CreditPolicy::MAX_REVEAL_BATCH));
        }

        return $this->em->wrapInTransaction(function () use ($organization, $candidates, $revealedBy, $type): array {
            $wallet = $this->em->getRepository(CreditWallet::class)->findOneBy(['organization' => $organization]);
            if (!$wallet instanceof CreditWallet) {
                throw new LogicException('Wallet manquant pour cette entreprise.');
            }
            // Sérialise les dévoilements concurrents de cette entreprise.
            $this->em->lock($wallet, LockMode::PESSIMISTIC_WRITE);

            $already = $this->alreadyRevealed($organization, $candidates, $type);
            // DEUX EXCLUSIONS, et elles se cumulent : ce qui est déjà payé, et ce qui n'a rien à
            // servir. La seconde manquait — on pouvait débiter un crédit pour un e-mail inexistant,
            // sur un vivier où 1 % des profils en ont un. Le signal de joignabilité existait déjà
            // sur la carte pour éviter cette dépense ; personne ne s'en servait pour la refuser.
            $toPay = array_filter(
                $candidates,
                static fn (Candidate $candidate): bool => !isset($already[(int) $candidate->getId()])
                    && RevealableContent::has($candidate, $type),
            );

            if ($wallet->balance < \count($toPay) * CreditPolicy::REVEAL_COST) {
                throw new InsufficientCreditsException();
            }

            $revealed = $already;
            foreach ($toPay as $candidate) {
                $revealed[(int) $candidate->getId()] = $this->payFor($wallet, $organization, $candidate, $revealedBy, $type);
            }

            // Réordonné comme reçu, et RESSERRÉ : un profil sans rien à servir n'a pas de ligne, il
            // ne peut donc pas figurer dans le retour. L'appelant reçoit ce qui a été dévoilé, pas
            // ce qu'il a demandé.
            return array_values(array_filter(array_map(
                static fn (Candidate $candidate): ?RevealedProfile => $revealed[(int) $candidate->getId()] ?? null,
                $candidates,
            )));
        });
    }

    /**
     * @param list<Candidate> $candidates
     *
     * @return array<int, RevealedProfile> indexés par id interne de candidat
     */
    private function alreadyRevealed(Organization $organization, array $candidates, RevealType $type): array
    {
        if ([] === $candidates) {
            return [];
        }

        /** @var list<RevealedProfile> $rows */
        $rows = $this->em->getRepository(RevealedProfile::class)
            ->findBy(['organization' => $organization, 'candidate' => $candidates, 'type' => $type]);

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row->candidate->getId()] = $row;
        }

        return $indexed;
    }

    /** Un accès, un crédit, une ligne de journal — et la notification qui commit avec eux. */
    private function payFor(CreditWallet $wallet, Organization $organization, Candidate $candidate, Recruiter $revealedBy, RevealType $type): RevealedProfile
    {
        $wallet->balance -= CreditPolicy::REVEAL_COST;

        $revealed = new RevealedProfile($organization, $candidate, $revealedBy, $type);
        $this->em->persist($revealed);

        $this->em->persist(new CreditTransaction(
            $organization,
            CreditTransactionType::CONSUMPTION,
            -CreditPolicy::REVEAL_COST,
            $wallet->balance,
            reason: 'Dévoilement — '.$type->value,
            recruiter: $revealedBy,
            revealedProfile: $revealed,
        ));

        // Émission DANS la transaction (persist sans flush) : le dévoilement et sa notification
        // commitent ou rollbackent ensemble (invariant 058). Uniquement sur un accès NEUF — un accès
        // déjà payé n'a pas repassé par ici, donc ne re-notifie pas.
        $this->notifications->profileRevealed($candidate, $organization, $type);

        return $revealed;
    }
}
