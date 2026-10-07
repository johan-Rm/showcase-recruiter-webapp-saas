<?php

declare(strict_types=1);

namespace App\Domain\Candidate;

use App\Domain\Organization\EmailAlreadyRegisteredException;
use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\Person;
use App\Entity\User;
use App\Message\SubmitCandidateToJarvi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Inscription candidat (parcours public, tâche 015) : crée de façon atomique le Person, le compte
 * User (ROLE_CANDIDATE, mot de passe hashé) et la projection Candidate, puis déclenche la remontée
 * Jarvi en asynchrone (tâche 089).
 *
 * L'ancre `jarviProfileId` est mintée provisoirement en local (UUIDv7) pour satisfaire la contrainte
 * NOT NULL/unique : elle est remplacée par le `profileId` Jarvi réel une fois la soumission
 * `/applicants` aboutie (hors transaction, via Messenger). L'inscription ne contacte donc pas Jarvi
 * dans cette transaction — elle réussit même si Jarvi est indisponible.
 * Cf. data-model.md / jarvi-integration.md.
 */
final readonly class CandidateOnboarder
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private MessageBusInterface $bus,
    ) {
    }

    public function onboard(
        string $email,
        string $rawPassword,
        string $givenName,
        string $familyName,
        string $linkedinUrl,
        ?string $country = null,
        ?string $city = null,
        ?string $cvFileName = null,
        ?string $cvBase64 = null,
    ): Candidate {
        if (null !== $this->em->getRepository(User::class)->findOneBy(['email' => $email])) {
            throw new EmailAlreadyRegisteredException();
        }

        $candidate = $this->em->wrapInTransaction(function () use ($email, $rawPassword, $givenName, $familyName, $linkedinUrl, $country, $city): Candidate {
            $person = new Person($givenName, $familyName, contactEmail: $email, linkedinUrl: $linkedinUrl, country: $country, city: $city);

            $user = new User($email, $person);
            $user->setRoles(['ROLE_CANDIDATE']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $rawPassword));

            $candidate = new Candidate($person, jarviProfileId: Uuid::v7());
            // La projection porte toujours son conteneur de réplica pro (vide/PENDING tant que
            // l'ingestion Jarvi n'a pas tourné, tâche 020). Persisté en cascade via Candidate.jarvi.
            $candidate->setJarvi(new CandidateJarvi($candidate));

            $this->em->persist($person);
            $this->em->persist($user);
            $this->em->persist($candidate);

            return $candidate;
        });

        // Après commit uniquement : la remontée Jarvi part en asynchrone (retry + refresh différé),
        // sans jamais bloquer ni faire échouer l'inscription déjà persistée.
        $this->bus->dispatch(new SubmitCandidateToJarvi($candidate->getUuid()->toString(), $cvFileName, $cvBase64));

        return $candidate;
    }
}
