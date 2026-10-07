<?php

declare(strict_types=1);

namespace App\Domain\Account;

use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\CreditTransaction;
use App\Entity\CreditWallet;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Recruiter;
use App\Entity\User;
use App\Enum\AdminRole;
use App\Enum\ConsentSource;
use App\Enum\CreditTransactionType;
use App\Enum\OrganizationRole;
use App\Enum\OrganizationValidationStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Amorçage des comptes de recette showcase-recruiter-webapp-saas, **spécialisés par rôle** : un compte candidat, un
 * compte entreprise, un compte admin, plus un compte « accès complet ». Permet de tester le
 * cloisonnement des 3 espaces (un candidat ne doit pas entrer sur `entreprise.`, etc.).
 *
 * Idempotent : rejouable sans purge (met à jour rôles/mot de passe, ne duplique pas).
 * Réservé aux environnements hors production.
 */
final class TeamAccountSeeder
{
    private const ORGANIZATION_NAME = 'showcase-recruiter-webapp-saas — Équipe (preprod)';

    /**
     * Comptes de recette (adresses @showcase-recruiter-webapp-saas.example). `candidate` → projection Candidate ;
     * `organizationRole` non nul → Recruiter rattaché à l'entreprise partagée ; `admin` → ADMIN_OWNER.
     * Un seul COMPANY_OWNER par entreprise.
     *
     * @var list<array{givenName: string, familyName: string, email: string, candidate: bool, organizationRole: OrganizationRole|null, admin: bool}>
     */
    private const MEMBERS = [
        [
            'givenName' => 'Candidat', 'familyName' => 'showcase-recruiter-webapp-saas',
            'email' => 'candidat@showcase-recruiter-webapp-saas.example',
            'candidate' => true, 'organizationRole' => null, 'admin' => false,
        ],
        [
            'givenName' => 'Entreprise', 'familyName' => 'showcase-recruiter-webapp-saas',
            'email' => 'entreprise@showcase-recruiter-webapp-saas.example',
            'candidate' => false, 'organizationRole' => OrganizationRole::COMPANY_OWNER, 'admin' => false,
        ],
        [
            'givenName' => 'Contact', 'familyName' => 'showcase-recruiter-webapp-saas',
            'email' => 'contact@showcase-recruiter-webapp-saas.example',
            'candidate' => false, 'organizationRole' => null, 'admin' => true,
        ],
        [
            'givenName' => 'Demo', 'familyName' => 'Member',
            'email' => 'demo.member@showcase-recruiter-webapp-saas.example',
            'candidate' => true, 'organizationRole' => OrganizationRole::COMPANY_MEMBER, 'admin' => true,
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function seed(string $password, int $credits): TeamSeedReport
    {
        $report = new TeamSeedReport();
        $organization = $this->getOrCreateOrganization($credits, $report);

        foreach (self::MEMBERS as $member) {
            $this->seedMember($member, $organization, $password, $report);
        }

        $this->em->flush();

        return $report;
    }

    private function getOrCreateOrganization(int $credits, TeamSeedReport $report): Organization
    {
        $organization = $this->em->getRepository(Organization::class)->findOneBy(['name' => self::ORGANIZATION_NAME]);
        if ($organization instanceof Organization) {
            return $organization;
        }

        $organization = new Organization(
            name: self::ORGANIZATION_NAME,
            country: 'MA',
            city: 'Casablanca',
            validationStatus: OrganizationValidationStatus::VALIDEE,
        );
        $this->em->persist($organization);

        $this->em->persist(new CreditWallet($organization, $credits));
        $this->em->persist(new CreditTransaction(
            $organization,
            CreditTransactionType::GRANT_FREE,
            $credits,
            $credits,
            reason: 'Crédits de test (amorçage comptes de recette)',
        ));

        $report->organizationCreated = true;

        return $organization;
    }

    /**
     * @param array{givenName: string, familyName: string, email: string, candidate: bool, organizationRole: OrganizationRole|null, admin: bool} $member
     */
    private function seedMember(array $member, Organization $organization, string $password, TeamSeedReport $report): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $member['email']]);

        if ($user instanceof User) {
            $person = $user->getPerson();
            ++$report->usersUpdated;
        } else {
            $person = new Person(
                $member['givenName'],
                $member['familyName'],
                contactEmail: $member['email'],
                country: 'MA',
            );
            $this->em->persist($person);

            $user = new User($member['email'], $person);
            $this->em->persist($user);
            ++$report->usersCreated;
        }

        // Rôles strictement dérivés du compte : pas de cumul par défaut.
        $roles = [];
        if ($member['candidate']) {
            $roles[] = 'ROLE_CANDIDATE';
        }
        if (null !== $member['organizationRole']) {
            $roles[] = $member['organizationRole']->value;
        }
        if ($member['admin']) {
            $roles[] = AdminRole::ADMIN_OWNER->value;
        }

        $user->setRoles($roles);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        if ($member['candidate']) {
            $this->ensureCandidate($person, $report);
        }
        if (null !== $member['organizationRole']) {
            $this->ensureRecruiter($person, $organization, $member['organizationRole'], $report);
        }
    }

    private function ensureCandidate(Person $person, TeamSeedReport $report): void
    {
        $existing = $this->em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        if ($existing instanceof Candidate) {
            // Auto-réparation : la réplica doit toujours exister (invariant supposé par CandidateMapper),
            // même vide (shell) — les comptes seedés avant ce correctif n'en avaient pas.
            if (null === $existing->getJarvi()) {
                $existing->setJarvi(new CandidateJarvi($existing));
            }

            return;
        }

        // Profil de test (jarviProfileId synthétique) : réplica Jarvi vide (shell), jamais ingérée.
        // Consentement + visibilité posés pour que le profil soit recherchable depuis l'espace entreprise.
        $candidate = new Candidate($person, Uuid::v7());
        $candidate->consentGivenAt = new DateTimeImmutable();
        $candidate->consentSource = ConsentSource::ONBOARDING;
        $candidate->setJarvi(new CandidateJarvi($candidate));
        $this->em->persist($candidate);

        ++$report->candidatesCreated;
    }

    private function ensureRecruiter(Person $person, Organization $organization, OrganizationRole $role, TeamSeedReport $report): void
    {
        $recruiter = $this->em->getRepository(Recruiter::class)->findOneBy(['person' => $person]);

        if ($recruiter instanceof Recruiter) {
            $recruiter->organization = $organization;
            $recruiter->organizationRole = $role;

            return;
        }

        $this->em->persist(new Recruiter($person, $organization, $role));
        ++$report->recruitersCreated;
    }
}
