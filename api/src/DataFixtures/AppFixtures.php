<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\AbstractEntity;
use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\CandidateJobExperience;
use App\Entity\CandidateJobTitle;
use App\Entity\CandidateSectorExperience;
use App\Entity\CreditTransaction;
use App\Entity\CreditWallet;
use App\Entity\Favorite;
use App\Entity\Invitation;
use App\Entity\MonetaryAmount;
use App\Entity\Notification;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\PresencePeriod;
use App\Entity\ProfileView;
use App\Entity\Recruiter;
use App\Entity\RevealedProfile;
use App\Entity\SavedSearch;
use App\Entity\SearchResultMembership;
use App\Entity\Subscription;
use App\Entity\User;
use App\Entity\VocabularyConcept;
use App\Enum\AdminRole;
use App\Enum\Availability;
use App\Enum\ConsentSource;
use App\Enum\CreditTransactionType;
use App\Enum\CurrentPositionCertainty;
use App\Enum\EducationLevel;
use App\Enum\InvitationStatus;
use App\Enum\NotificationChannel;
use App\Enum\NotificationType;
use App\Enum\OrganizationRole;
use App\Enum\OrganizationValidationStatus;
use App\Enum\ProfileVisibility;
use App\Enum\ReturnHorizon;
use App\Enum\RevealType;
use App\Enum\Sector;
use App\Enum\SeniorityLevel;
use App\Enum\SubscriptionStatus;
use App\Enum\SyncStatus;
use App\Enum\VocabularyStatus;
use App\Enum\VocabularyType;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use ReflectionProperty;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Seed de démo (tâche 002) : référentiels, comptes, candidats, entreprises, crédits,
 * collaboration, notifications et éditorial.
 */
final class AppFixtures extends Fixture
{
    /** Quantité de crédits offerts à la création d'une Organization (cf. data-model.md). */
    private const int WELCOME_CREDITS = 2;

    /**
     * UUID déterministes pour les entités démo référencées par la collection Insomnia
     * (dev-book/resources/insomnia/showcase-recruiter-webapp-saas.environment.json). Fixés ici pour que les
     * UUID publics restent stables d'un `fixtures:load` à l'autre.
     */
    public const string DEMO_ORGANIZATION_UUID = '01900000-0000-7000-8000-0000000000a1';
    public const string DEMO_CANDIDATE_UUID = '01900000-0000-7000-8000-0000000000c1';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $primaryOrganization = $this->seedOrganizationWithOwner($manager);
        $candidates = $this->seedCandidates($manager, $this->seedJobConcepts($manager));
        $secondaryOrganization = $this->seedSecondaryOrganization($manager);

        $this->seedDemoUsage($manager, $primaryOrganization, $secondaryOrganization, $candidates);
        $this->seedDeclarant($manager);

        $manager->flush();
    }

    /**
     * Force l'UUID public d'une entité démo (cf. self::DEMO_*_UUID). Réservé aux fixtures :
     * le modèle de domaine n'expose volontairement pas de setter d'UUID (UUIDv7 auto).
     */
    private function forceUuid(AbstractEntity $entity, string $uuid): void
    {
        (new ReflectionProperty(AbstractEntity::class, 'uuid'))->setValue($entity, Uuid::fromString($uuid));
    }

    /**
     * User sans entreprise (ni recruteur) : sert à tester la création d'une Organization
     * (POST /v1/organizations). Identifiants : nadia.cherkaoui@entreprise-abc.test / password.
     */
    private function seedDeclarant(ObjectManager $manager): void
    {
        $person = new Person('Nadia', 'Cherkaoui', contactEmail: 'nadia.cherkaoui@entreprise-abc.test', country: 'FR', city: 'Lyon');
        $manager->persist($person);

        $user = new User('nadia.cherkaoui@entreprise-abc.test', $person);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $manager->persist($user);
    }

    /** @return array{organization: Organization, owner: Recruiter, ownerPerson: Person, wallet: CreditWallet} */
    private function seedOrganizationWithOwner(ObjectManager $manager): array
    {
        $organization = new Organization(
            'showcase-recruiter-webapp-saas Demo SARL',
            legalName: 'showcase-recruiter-webapp-saas Demo SARL',
            taxId: 'ICE-000000001',
            city: 'Casablanca',
            billingAddress: '12 boulevard de démonstration, Casablanca',
            website: 'https://demo.showcase-recruiter-webapp-saas.example',
            stripeCustomerId: 'cus_demo_showcase_recruiter_webapp_saas',
        );
        $organization->validationStatus = OrganizationValidationStatus::VALIDEE;
        $this->forceUuid($organization, self::DEMO_ORGANIZATION_UUID);
        $manager->persist($organization);

        $wallet = new CreditWallet($organization, self::WELCOME_CREDITS);
        $manager->persist($wallet);
        $manager->persist(new CreditTransaction(
            $organization,
            CreditTransactionType::GRANT_FREE,
            self::WELCOME_CREDITS,
            self::WELCOME_CREDITS,
            reason: 'Crédits offerts à la création',
        ));

        $ownerPerson = new Person('Sara', 'Bennani', contactEmail: 'owner@demo.showcase-recruiter-webapp-saas.example', country: 'MA', city: 'Casablanca');
        $manager->persist($ownerPerson);

        $owner = new User('owner@demo.showcase-recruiter-webapp-saas.example', $ownerPerson);
        $owner->setRoles([OrganizationRole::COMPANY_OWNER->value]);
        $owner->setPassword($this->passwordHasher->hashPassword($owner, 'password'));
        $manager->persist($owner);

        $ownerRecruiter = new Recruiter($ownerPerson, $organization, OrganizationRole::COMPANY_OWNER, jobTitle: 'Responsable RH');
        $manager->persist($ownerRecruiter);

        $adminPerson = new Person('Adam', 'Admin', contactEmail: 'admin@demo.showcase-recruiter-webapp-saas.example', country: 'MA', city: 'Casablanca');
        $manager->persist($adminPerson);

        $admin = new User('admin@demo.showcase-recruiter-webapp-saas.example', $adminPerson);
        $admin->setRoles([AdminRole::ADMIN_OWNER->value]);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        $manager->persist(new Subscription(
            $organization,
            'starter',
            SubscriptionStatus::ACTIVE,
            // Relative : un abonnement ACTIF dont la fin est passée est une contradiction, et la
            // suite la découvrirait un matin sans que rien n'ait changé dans le code.
            new DateTimeImmutable('+2 months'),
            'sub_demo_starter',
        ));

        return ['organization' => $organization, 'owner' => $ownerRecruiter, 'ownerPerson' => $ownerPerson, 'wallet' => $wallet];
    }

    /**
     * Les métiers du dictionnaire dont les profils de démo ont besoin — libellé => code de taxonomie.
     *
     * ⚠️ **Le dictionnaire n'est pas chargé par le bootstrap local** : `dev-setup` enchaîne schéma →
     * fixtures → comptes sans jamais appeler `app:vocabulary:import`. Sur une base neuve la table des
     * concepts est vide, et le rattachement d'un profil à son métier n'accepte pas de concept nul :
     * sans ces lignes, aucun profil de démo ne serait trouvable par le filtre « poste ».
     *
     * Les CODES viennent de `api/data/vocabulary/taxonomy.json`, et ce n'est pas décoratif :
     * l'importeur retrouve un concept **par son code avant son libellé**, donc un
     * `app:vocabulary:import` ultérieur ADOPTE ces trois-là plutôt que d'en créer des jumeaux.
     *
     * ⚠️ Les libellés sont FIGÉS ici. Si la taxonomie renomme un métier, l'import corrigera le
     * concept — le fichier fait autorité — mais cette constante restera périmée, et le profil de
     * démo désignera un métier au nom d'hier. C'est le prix de l'indépendance des fixtures, et il se
     * paie en une ligne à corriger.
     */
    private const array DEMO_JOBS = [
        'Développeur logiciel' => 'poste-003',
        'Comptable' => 'poste-013',
        'Commercial' => 'poste-008',
    ];

    /** @return array<string, VocabularyConcept> */
    private function seedJobConcepts(ObjectManager $manager): array
    {
        $concepts = [];
        foreach (self::DEMO_JOBS as $label => $code) {
            $concept = new VocabularyConcept(VocabularyType::METIER, $label, $code, VocabularyStatus::VALIDE);
            $manager->persist($concept);
            $concepts[$label] = $concept;
        }

        return $concepts;
    }

    /**
     * Les profils de démonstration, **complets des deux côtés**.
     *
     * La base porte deux familles, et chacune est vide là où l'autre est pleine : les profils
     * importés de Jarvi ont un parcours et un métier reconnu mais aucun projet Maroc, les profils
     * de démo portaient le projet Maroc et rien d'autre. Aucun écran ne pouvait donc être éprouvé de
     * bout en bout. Ceux-ci cumulent les deux.
     *
     * @return list<array<string, mixed>>
     */
    private function demoProfiles(): array
    {
        return [
            [
                'given' => 'Yassine', 'family' => 'El Amrani', 'desiredSector' => 'Informatique', 'desiredFunction' => 'Développeur', 'city' => 'Paris', 'phone' => '+33600000001',
                'job' => 'Développeur logiciel', 'sector' => Sector::INFORMATIQUE,
                'title' => 'Développeur backend PHP', 'company' => 'Rivage Logiciels',
                'education' => EducationLevel::MASTER, 'seniority' => SeniorityLevel::SENIOR,
                'years' => 8, 'jobMonths' => 96, 'positions' => 3, 'roleMonths' => 38,
                'horizon' => ReturnHorizon::M6, 'availability' => Availability::NOTICE_1M,
                'visibility' => ProfileVisibility::VISIBLE,
                'skills' => ['PHP', 'Symfony', 'PostgreSQL', 'Docker', 'API REST', 'Tests automatisés'],
                'titles' => ['Développeur backend PHP', 'Développeur logiciel', 'Développeur PHP junior'],
                'experiences' => [
                    ['Développeur backend PHP', 'Rivage Logiciels', '2022-11-01', null],
                    ['Développeur logiciel', 'Atelier Numérique Lyon', '2019-03-01', '2022-10-31'],
                    ['Développeur PHP junior', 'Studio Cassiopée', '2017-09-01', '2019-02-28'],
                ],
                'educations' => [
                    ['Institut Supérieur de Démonstration', 'Master Génie logiciel', '2015-09-01', '2017-06-30'],
                    ['Université de démonstration', 'Licence Informatique', '2012-09-01', '2015-06-30'],
                ],
            ],
            [
                'given' => 'Imane', 'family' => 'Tazi', 'desiredSector' => 'Finance', 'desiredFunction' => 'Comptable', 'city' => 'Lyon', 'phone' => '+33600000002',
                'job' => 'Comptable', 'sector' => Sector::BANQUE_FINANCE,
                'title' => 'Comptable confirmée', 'company' => 'Cabinet Meridiem',
                'education' => EducationLevel::LICENCE, 'seniority' => SeniorityLevel::CONFIRME,
                'years' => 11, 'jobMonths' => 132, 'positions' => 3, 'roleMonths' => 52,
                'horizon' => ReturnHorizon::Y1, 'availability' => Availability::IMMEDIATE,
                'visibility' => ProfileVisibility::VISIBLE,
                'skills' => ['Comptabilité générale', 'Sage', 'Consolidation', 'Fiscalité', 'Excel avancé'],
                'titles' => ['Comptable confirmée', 'Comptable', 'Assistante comptable'],
                'experiences' => [
                    ['Comptable confirmée', 'Cabinet Meridiem', '2021-09-01', null],
                    ['Comptable', 'Groupe Alizé Distribution', '2017-01-01', '2021-08-31'],
                    ['Assistante comptable', 'Fiduciaire des Deux Rives', '2014-06-01', '2016-12-31'],
                ],
                'educations' => [
                    ['École de gestion de démonstration', 'Licence Comptabilité et gestion', '2011-09-01', '2014-06-30'],
                ],
            ],
            [
                // LE PROFIL QUI PROUVE LA RÈGLE : complet, qualifié, et sans consentement. Il ne doit
                // jamais sortir d'une recherche — c'est pour ça qu'on l'a rendu aussi riche que les
                // autres, un profil pauvre n'aurait rien prouvé.
                'given' => 'Karim', 'family' => 'Idrissi', 'desiredSector' => 'Informatique', 'desiredFunction' => 'Développeur', 'city' => 'Paris', 'phone' => '+33600000003',
                'job' => 'Développeur logiciel', 'sector' => Sector::INFORMATIQUE,
                'title' => 'Développeur full stack', 'company' => 'Fabrique Ondine',
                'education' => EducationLevel::INGENIEUR, 'seniority' => SeniorityLevel::CONFIRME,
                'years' => 7, 'jobMonths' => 84, 'positions' => 2, 'roleMonths' => 30,
                'horizon' => ReturnHorizon::THINKING, 'availability' => Availability::NOTICE_3M,
                'visibility' => ProfileVisibility::HIDDEN,
                'skills' => ['JavaScript', 'Vue.js', 'Node.js', 'PostgreSQL', 'CI/CD'],
                'titles' => ['Développeur full stack', 'Développeur logiciel'],
                'experiences' => [
                    ['Développeur full stack', 'Fabrique Ondine', '2023-03-01', null],
                    ['Développeur logiciel', 'Coopérative Solstice', '2018-09-01', '2023-02-28'],
                ],
                'educations' => [
                    ['École d’ingénieurs de démonstration', 'Diplôme d’ingénieur en informatique', '2013-09-01', '2018-06-30'],
                ],
            ],
            [
                'given' => 'Nabil', 'family' => 'Berrada', 'desiredSector' => 'Télécommunications', 'desiredFunction' => 'Développeur', 'city' => 'Toulouse', 'phone' => '+33600000004',
                'job' => 'Développeur logiciel', 'sector' => Sector::TELECOM,
                'title' => 'Ingénieur logiciel embarqué', 'company' => 'Orbite Télécom',
                'education' => EducationLevel::INGENIEUR, 'seniority' => SeniorityLevel::SENIOR,
                'years' => 12, 'jobMonths' => 144, 'positions' => 3, 'roleMonths' => 61,
                'horizon' => ReturnHorizon::M6, 'availability' => Availability::NOTICE_3M,
                'visibility' => ProfileVisibility::VISIBLE,
                'skills' => ['C++', 'Linux embarqué', 'Réseaux', 'Python', 'Git', 'Méthodes agiles'],
                'titles' => ['Ingénieur logiciel embarqué', 'Développeur logiciel', 'Ingénieur d’études'],
                'experiences' => [
                    ['Ingénieur logiciel embarqué', 'Orbite Télécom', '2020-10-01', null],
                    ['Développeur logiciel', 'Signaux du Sud', '2016-01-01', '2020-09-30'],
                    ['Ingénieur d’études', 'Bureau Technique Garonne', '2013-09-01', '2015-12-31'],
                ],
                'educations' => [
                    ['École d’ingénieurs de démonstration', 'Diplôme d’ingénieur télécommunications', '2008-09-01', '2013-06-30'],
                ],
            ],
            [
                'given' => 'Salma', 'family' => 'Ouazzani', 'desiredSector' => 'Finance', 'desiredFunction' => 'Comptable', 'city' => 'Bruxelles', 'country' => 'BE', 'phone' => '+32470000005',
                'job' => 'Comptable', 'sector' => Sector::CONSEIL_AUDIT,
                'title' => 'Auditrice financière', 'company' => 'Cabinet Vertumne',
                'education' => EducationLevel::MASTER, 'seniority' => SeniorityLevel::CONFIRME,
                'years' => 9, 'jobMonths' => 108, 'positions' => 2, 'roleMonths' => 44,
                'horizon' => ReturnHorizon::M6, 'availability' => Availability::NOTICE_1M,
                'visibility' => ProfileVisibility::VISIBLE,
                'skills' => ['Audit financier', 'Normes IFRS', 'Contrôle interne', 'Comptabilité générale', 'Power BI'],
                'titles' => ['Auditrice financière', 'Comptable', 'Auditrice junior'],
                'experiences' => [
                    ['Auditrice financière', 'Cabinet Vertumne', '2021-05-01', null],
                    ['Comptable', 'Groupe Ardennes Industries', '2016-09-01', '2021-04-30'],
                ],
                'educations' => [
                    ['École de gestion de démonstration', 'Master Audit et contrôle de gestion', '2014-09-01', '2016-06-30'],
                    ['Université de démonstration', 'Licence Sciences économiques', '2011-09-01', '2014-06-30'],
                ],
            ],
            [
                'given' => 'Hicham', 'family' => 'Benjelloun', 'desiredSector' => 'Distribution', 'desiredFunction' => 'Commercial', 'city' => 'Marseille', 'phone' => '+33600000006',
                'job' => 'Commercial', 'sector' => Sector::DISTRIBUTION,
                'title' => 'Responsable grands comptes', 'company' => 'Comptoir Méditerranée',
                'education' => EducationLevel::BAC_PLUS_2, 'seniority' => SeniorityLevel::SENIOR,
                'years' => 14, 'jobMonths' => 168, 'positions' => 3, 'roleMonths' => 72,
                'horizon' => ReturnHorizon::M3, 'availability' => Availability::IMMEDIATE,
                'visibility' => ProfileVisibility::VISIBLE,
                'skills' => ['Négociation', 'Grands comptes', 'Prospection', 'CRM', 'Anglais commercial'],
                'titles' => ['Responsable grands comptes', 'Commercial', 'Chargé de clientèle'],
                'experiences' => [
                    ['Responsable grands comptes', 'Comptoir Méditerranée', '2019-01-01', null],
                    ['Commercial', 'Distribution Calanques', '2013-04-01', '2018-12-31'],
                    ['Chargé de clientèle', 'Maison Ravel', '2010-09-01', '2013-03-31'],
                ],
                'educations' => [
                    ['Institut de commerce de démonstration', 'BTS Négociation et relation client', '2008-09-01', '2010-06-30'],
                ],
            ],
        ];
    }

    /**
     * @param array<string, VocabularyConcept> $jobConcepts
     *
     * @return array<string, Candidate>
     */
    private function seedCandidates(ObjectManager $manager, array $jobConcepts): array
    {
        $syncedAt = new DateTimeImmutable('2026-06-18 09:00:00');

        /** @var array<string, Candidate> $candidates */
        $candidates = [];
        foreach ($this->demoProfiles() as $profile) {
            $visibility = $profile['visibility'];
            $synced = ProfileVisibility::VISIBLE === $visibility;

            $person = new Person(
                $profile['given'],
                $profile['family'],
                contactEmail: strtolower($profile['given']).'@example.com',
                phone: $profile['phone'],
                country: $profile['country'] ?? 'FR',
                city: $profile['city'],
            );
            $manager->persist($person);

            $candidate = new Candidate(
                $person,
                jarviProfileId: Uuid::v7(),
                desiredSector: $profile['desiredSector'],
                desiredFunction: $profile['desiredFunction'],
                returnHorizon: $profile['horizon'],
                returnProject: 'Rejoindre un groupe marocain pour y exercer '.lcfirst($profile['title']).', idéalement à Casablanca.',
                hasPiedATerre: true,
                piedATerreCity: 'Casablanca',
                geoMobility: true,
                availability: $profile['availability'],
                // Sur le CANDIDAT et pas seulement sur le réplica : c'est cette colonne-là que le
                // filtre « niveau d'études » interroge.
                educationLevel: $profile['education'],
                visibility: $visibility,
                syncStatus: $synced ? SyncStatus::SYNCED : SyncStatus::PENDING,
                syncedAt: $synced ? $syncedAt : null,
            );
            $candidate->salaryExpected = new MonetaryAmount(4500000, 'MAD');
            $candidate->salaryMin = new MonetaryAmount(3800000, 'MAD');
            if ($synced) {
                $candidate->giveConsent(ConsentSource::ONBOARDING, $syncedAt);
            }
            $manager->persist($candidate);

            $manager->persist($this->buildJarvi($candidate, $profile, $synced, $syncedAt));
            $this->attachJob($manager, $candidate, $profile, $jobConcepts[$profile['job']]);

            $manager->persist(new PresencePeriod(
                $candidate,
                // Relatives, et la fenêtre garde ses quinze jours : une présence « à venir » déjà
                // passée ne dit plus rien du différenciant Maroc.
                new DateTimeImmutable('+1 month'),
                new DateTimeImmutable('+45 days'),
                'Casablanca',
            ));

            $candidates[$profile['family']] = $candidate;
        }

        // UUID public stable pour le candidat démo référencé par la collection Insomnia.
        $this->forceUuid($candidates['El Amrani'], self::DEMO_CANDIDATE_UUID);

        $candidates['Tazi']->starInterviewedAt = new DateTimeImmutable('2026-06-18 10:00:00');
        $candidates['Tazi']->starInterviewReport = 'Entretien showcase-recruiter-webapp-saas réalisé : disponibilité confirmée, projet Maroc cohérent, communication professionnelle claire.';

        return $candidates;
    }

    /**
     * Le réplica pro Jarvi — JSON opaque, lecture seule, tel que l'ingestion le dépose.
     *
     * ⚠️ `experienceYears` et `currentRoleMonths` sont ÉCRITS, pas calculés : en production
     * l'ingestion les dérive des périodes, mais les fixtures n'ont pas le droit de dépendre du
     * domaine (deptrac). Ils sont donc posés cohérents avec les expériences ci-dessus, et c'est une
     * cohérence à maintenir à la main.
     *
     * @param array<string, mixed> $profile
     */
    private function buildJarvi(Candidate $candidate, array $profile, bool $synced, DateTimeImmutable $syncedAt): CandidateJarvi
    {
        $experiences = array_map(
            static fn (array $row): array => [
                'title' => $row[0], 'companyName' => $row[1],
                'startDate' => $row[2], 'endDate' => $row[3], 'source' => 'RESUME',
            ],
            $profile['experiences'],
        );
        $educations = array_map(
            static fn (array $row): array => [
                'school' => $row[0], 'title' => $row[1], 'startDate' => $row[2], 'endDate' => $row[3],
            ],
            $profile['educations'],
        );

        $jarvi = new CandidateJarvi(
            $candidate,
            headline: $profile['title'],
            summary: $profile['title'].' — profil basé en Europe, projet de retour au Maroc.',
            // URL de démo : le storage Jarvi sert la vraie photo sans authentification.
            photoUrl: 'https://qimsyaozqntinmrokopq.storage.eu-west-2.nhost.run/v1/files/00000000-0000-7000-8000-0000000000d1',
            workExperiences: $experiences,
            educations: $educations,
            languages: [
                ['language' => 'fr', 'proficiency' => 'NATIVE_OR_BILINGUAL'],
                ['language' => 'ar', 'proficiency' => 'NATIVE_OR_BILINGUAL'],
                ['language' => 'en', 'proficiency' => 'PROFESSIONAL'],
            ],
            skills: $profile['skills'],
            // Le CV est un CONTENU PAYANT : il doit exister pour que l'écran ait les deux états à
            // montrer, verrouillé et dévoilé.
            resumes: [
                ['fileId' => '00000000-0000-7000-8000-0000000000f1', 'name' => 'cv-demo.pdf', 'mimeType' => 'application/pdf', 'parsedAt' => '2026-06-16T08:45:53Z'],
            ],
            contacts: [
                ['type' => 'phone', 'value' => $profile['phone'], 'source' => 'RESUME', 'whatsappIdentifier' => ltrim($profile['phone'], '+').'@s.whatsapp.net'],
            ],
            experienceYears: $profile['years'],
            currentRoleMonths: $profile['roleMonths'],
            currentPositionCertainty: CurrentPositionCertainty::CERTAIN,
            currentTitle: $profile['title'],
            currentCompanyName: $profile['company'],
            seniority: $profile['seniority'],
            educationLevel: $profile['education'],
            syncStatus: $synced ? SyncStatus::SYNCED : SyncStatus::PENDING,
            syncedAt: $synced ? $syncedAt : null,
        );
        $candidate->setJarvi($jarvi);

        return $jarvi;
    }

    /**
     * Les trois rattachements que l'ingestion produit et que les fixtures ne posaient pas : le
     * métier reconnu, les intitulés bruts du parcours, et le secteur d'expérience.
     *
     * C'est le PREMIER qui rend le profil trouvable : le filtre « poste » cherche un candidat dont
     * une expérience-métier pointe le concept portant le libellé demandé. Sans lui, un développeur
     * de démo restait absent d'une recherche « Développeur logiciel ».
     *
     * `exactPositions` égale `positions` : ces intitulés sont écrits pour tomber juste, là où
     * l'ingestion en rattrape une partie par reconnaissance partielle.
     *
     * @param array<string, mixed> $profile
     */
    private function attachJob(ObjectManager $manager, Candidate $candidate, array $profile, VocabularyConcept $concept): void
    {
        $manager->persist(new CandidateJobExperience(
            $candidate,
            $concept,
            months: $profile['jobMonths'],
            positions: $profile['positions'],
            exactPositions: $profile['positions'],
        ));

        foreach ($profile['titles'] as $title) {
            $manager->persist(new CandidateJobTitle($candidate, $title));
        }

        $manager->persist(new CandidateSectorExperience($candidate, $profile['sector'], $profile['jobMonths']));
    }

    /** @return array{organization: Organization, owner: Recruiter, member: Recruiter, ownerPerson: Person, memberPerson: Person} */
    private function seedSecondaryOrganization(ObjectManager $manager): array
    {
        $organization = new Organization(
            'Atlas Talent Conseil',
            legalName: 'Atlas Talent Conseil SARL',
            taxId: 'ICE-000000002',
            city: 'Rabat',
            billingAddress: '24 avenue Mohammed V, Rabat',
            website: 'https://atlas-talent.example',
            stripeCustomerId: 'cus_demo_atlas',
        );
        $organization->validationStatus = OrganizationValidationStatus::VALIDEE;
        $manager->persist($organization);

        $manager->persist(new CreditWallet($organization, 25));

        $ownerPerson = new Person('Omar', 'Alaoui', contactEmail: 'atlas.owner@demo.showcase-recruiter-webapp-saas.example', country: 'MA', city: 'Rabat');
        $memberPerson = new Person('Leila', 'Mansouri', contactEmail: 'atlas.member@demo.showcase-recruiter-webapp-saas.example', country: 'MA', city: 'Rabat');
        $manager->persist($ownerPerson);
        $manager->persist($memberPerson);

        $ownerUser = new User('atlas.owner@demo.showcase-recruiter-webapp-saas.example', $ownerPerson);
        $ownerUser->setRoles([OrganizationRole::COMPANY_OWNER->value]);
        $ownerUser->setPassword($this->passwordHasher->hashPassword($ownerUser, 'password'));
        $manager->persist($ownerUser);

        $memberUser = new User('atlas.member@demo.showcase-recruiter-webapp-saas.example', $memberPerson);
        $memberUser->setRoles([OrganizationRole::COMPANY_MEMBER->value]);
        $memberUser->setPassword($this->passwordHasher->hashPassword($memberUser, 'password'));
        $manager->persist($memberUser);

        $owner = new Recruiter($ownerPerson, $organization, OrganizationRole::COMPANY_OWNER, jobTitle: 'Directeur Talent');
        $member = new Recruiter($memberPerson, $organization, OrganizationRole::COMPANY_MEMBER, jobTitle: 'Talent Acquisition Specialist');
        $manager->persist($owner);
        $manager->persist($member);

        $manager->persist(new CreditTransaction(
            $organization,
            CreditTransactionType::PURCHASE,
            25,
            25,
            reason: 'Pack 25 crédits',
            recruiter: $owner,
            stripePaymentId: 'pi_demo_pack_25',
        ));

        return [
            'organization' => $organization,
            'owner' => $owner,
            'member' => $member,
            'ownerPerson' => $ownerPerson,
            'memberPerson' => $memberPerson,
        ];
    }

    /**
     * @param array{organization: Organization, owner: Recruiter, ownerPerson: Person, wallet: CreditWallet}                    $primary
     * @param array{organization: Organization, owner: Recruiter, member: Recruiter, ownerPerson: Person, memberPerson: Person} $secondary
     * @param array<string, Candidate>                                                                                          $candidates
     */
    private function seedDemoUsage(ObjectManager $manager, array $primary, array $secondary, array $candidates): void
    {
        $organization = $secondary['organization'];
        $owner = $secondary['owner'];
        $member = $secondary['member'];
        $candidate = $candidates['Tazi'];

        // Un seul des quatre accès est payé sur la démo : l'e-mail. Le téléphone, le CV et le
        // compte rendu restent à dévoiler — c'est ce qui montre que les quatre sont indépendants,
        // et qu'un canal payé n'ouvre pas l'autre.
        $revealed = new RevealedProfile($organization, $candidate, $owner, RevealType::EMAIL);
        $manager->persist($revealed);
        $manager->persist(new CreditTransaction(
            $organization,
            CreditTransactionType::CONSUMPTION,
            -1,
            24,
            reason: 'Dévoilement profil Imane Tazi',
            recruiter: $owner,
            revealedProfile: $revealed,
        ));

        $manager->persist(new ProfileView($candidate, $organization, $member));

        // ET UN DÉVOILEMENT CHEZ L'ENTREPRISE DE DÉMONSTRATION, pas seulement chez la seconde : sans
        // lui, voir l'état dévoilé sur le jeu de démo oblige à dépenser un crédit à la main après
        // CHAQUE rechargement de la base — plusieurs fois par jour en développement. Le CV plutôt
        // que l'e-mail : c'est le contenu dont les deux états se distinguent le plus à l'écran.
        // SUR UN PROFIL AJOUTÉ, pas sur El Amrani : lui est le témoin du retrait de consentement, et
        // un profil déjà payé reste lisible malgré ce retrait — le dévoiler ici ferait mentir cette
        // règle. Berrada porte le même métier : les deux états se comparent sur la même colonne.
        $demoRevealed = new RevealedProfile(
            $primary['organization'],
            $candidates['Berrada'],
            $primary['owner'],
            RevealType::RESUME,
        );
        $manager->persist($demoRevealed);
        $manager->persist(new CreditTransaction(
            $primary['organization'],
            CreditTransactionType::CONSUMPTION,
            -1,
            self::WELCOME_CREDITS - 1,
            reason: 'Dévoilement CV Nabil Berrada',
            recruiter: $primary['owner'],
            revealedProfile: $demoRevealed,
        ));
        // ...COMPENSÉ PAR UN ACHAT, pour que le solde retombe sur ses deux crédits d'origine. Sans
        // lui, l'entreprise de démonstration n'en aurait plus qu'un, et le premier dévoilement
        // essayé — à la main ou par un test — tomberait en « solde insuffisant ».
        $manager->persist(new CreditTransaction(
            $primary['organization'],
            CreditTransactionType::PURCHASE,
            1,
            self::WELCOME_CREDITS + 1,
            reason: 'Pack de dépannage démo',
            recruiter: $primary['owner'],
            stripePaymentId: 'pi_demo_pack_1',
        ));
        // Le solde SUIT la dépense : un portefeuille à deux crédits en face d'une consommation
        // écrite serait une base qui se contredit, et le premier écran de crédits le montrerait.
        // Deux offerts, un acheté, un consommé : le portefeuille retombe sur deux.
        $primary['wallet']->balance = self::WELCOME_CREDITS;

        $manager->persist(new Invitation(
            $organization,
            'future.recruiter@atlas-talent.example',
            'demo-invitation-atlas-member-token',
            $owner,
            OrganizationRole::COMPANY_MEMBER,
            InvitationStatus::PENDING,
            // Relative : figée au 2026-08-01, elle a rendu trois tests rouges le 2026-08-02 sans
            // qu'aucun code n'ait bougé. Une invitation EN ATTENTE est valable par définition.
            new DateTimeImmutable('+30 days'),
        ));

        // Critères ORDONNÉS : l'ordre de sélection est signifiant, il produit le nom auto côté front.
        $search = new SavedSearch(
            $owner,
            $organization,
            'Finance France vers Casablanca',
            [
                ['facet' => 'sector', 'value' => 'Finance', 'group' => 'metier'],
                ['facet' => 'availability', 'value' => 'IMMEDIATE', 'group' => 'dispo'],
                ['facet' => 'presenceCity', 'value' => 'Casablanca', 'group' => 'presence'],
            ],
            isAutoNamed: false,
            isShared: true,
            alertEnabled: true,
            channels: [NotificationChannel::IN_APP->value, NotificationChannel::EMAIL->value],
        );
        $manager->persist($search);

        // Les deux listes de la recherche. El Amrani n'est PAS dévoilé : il peut être favori (le
        // filet large : on retient avant de payer). Tazi, elle, est dévoilée ci-dessus — elle seule
        // peut donc être exportée.
        $manager->persist(new Favorite($search, $candidates['El Amrani'], $owner));
        $manager->persist(new Favorite($search, $candidate, $owner));

        // Jeu de résultats MATÉRIALISÉ de la recherche démo. En vrai il l'est par le processeur (au
        // changement de critères) et par l'ingestion Jarvi ; un chargement de fixtures ne passe par
        // aucun des deux. Sans ces lignes, GET /searches/{id}/results ne renverrait rien en dev alors
        // que la recherche a des critères — on chercherait le bug côté front.
        // Les deux profils sont ceux que les listes ci-dessus racontent déjà.
        $manager->persist(new SearchResultMembership(
            $search,
            $candidates['El Amrani'],
            new DateTimeImmutable('2026-06-10 09:00:00'),
        ));
        $manager->persist(new SearchResultMembership(
            $search,
            $candidate,
            new DateTimeImmutable('2026-06-12 09:00:00'),
        ));

        $manager->persist(new Notification(
            $owner->getPerson(),
            NotificationType::PROFILE_REVEALED,
            [NotificationChannel::IN_APP->value],
            relatedOrganization: $organization,
            relatedCandidate: $candidate,
            data: ['label' => 'Profil Imane Tazi dévoilé'],
            readAt: new DateTimeImmutable('2026-06-18 11:30:00'),
        ));
        $manager->persist(new Notification(
            $primary['ownerPerson'],
            NotificationType::CREDITS_LOW,
            [NotificationChannel::IN_APP->value, NotificationChannel::EMAIL->value],
            relatedOrganization: $primary['organization'],
            data: ['remainingCredits' => self::WELCOME_CREDITS],
            emailSentAt: new DateTimeImmutable('2026-06-18 08:30:00'),
        ));
    }
}
