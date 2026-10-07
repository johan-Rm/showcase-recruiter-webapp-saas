<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Search\SearchMembershipRecalculator;
use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\Person;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use App\Enum\ConsentSource;
use App\Enum\ProfileVisibility;
use App\Enum\SyncStatus;
use App\Repository\CandidateRepository;
use App\Repository\CandidateSearchCriteria;
use App\Repository\SearchResultMembershipRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Les mots-clés cherchent dans le titre ET dans les compétences, et se relient en OU (task API-011).
 *
 * Le champ ne faisait ni l'un ni l'autre : une sous-chaîne sur le titre seul, cumulée en ET. Mesuré
 * sur le vivier de dev, `développeur` ramenait 35 profils et `développeur` + `frontend` en ramenait
 * **zéro** — aucun titre ne contient les deux chaînes. Le champ rétrécissait là où le recruteur
 * croyait élargir.
 *
 * Le dernier test couvre l'ORDRE (task API-006) : il vit ici parce que la hiérarchie n'existe que
 * grâce au OU — sans correspondances partielles, tous les rangs seraient égaux.
 */
final class CandidateKeywordSearchTest extends ApiTestCase
{
    public function testKeywordsBroadenInsteadOfNarrowing(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);

        $tag = uniqid();
        $bythetitle = $this->persistProfile($em, 'Développeur backend '.$tag, []);
        $bytheskill = $this->persistProfile($em, 'Chef de projet '.$tag, ['Kubernetes '.$tag]);
        $em->flush();

        $titleOnly = $this->ids($repository, ['développeur backend '.$tag]);
        self::assertContains($bythetitle->getId(), $titleOnly);
        self::assertNotContains($bytheskill->getId(), $titleOnly);

        // Deux mots-clés : le second AJOUTE des profils, il n'en retire aucun.
        $both = $this->ids($repository, ['développeur backend '.$tag, 'kubernetes '.$tag]);
        self::assertContains($bythetitle->getId(), $both, 'le premier mot-clé retient toujours');
        self::assertContains($bytheskill->getId(), $both, 'le second ÉLARGIT — c’est un OU');
        self::assertGreaterThan(\count($titleOnly), \count($both));
    }

    public function testAKeywordFindsASkillAndNotOnlyATitle(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);

        $tag = uniqid();
        // Le titre ne dit rien de la compétence : sans la lecture des compétences, ce profil est
        // introuvable — c'est exactement ce que le champ promettait sans le faire.
        $candidate = $this->persistProfile($em, 'Consultant '.$tag, ['Symfony '.$tag, 'PostgreSQL '.$tag]);
        $em->flush();

        self::assertContains($candidate->getId(), $this->ids($repository, ['postgresql '.$tag]));
        self::assertNotContains($candidate->getId(), $this->ids($repository, ['terme-absent-'.$tag]));
    }

    public function testOtherCriteriaKeepNarrowing(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);

        $tag = uniqid();
        $candidate = $this->persistProfile($em, 'Développeur '.$tag, []);
        $em->flush();

        // Le OU ne vaut qu'ENTRE les mots-clés : un critère d'un autre ordre restreint toujours.
        self::assertContains($candidate->getId(), $this->ids($repository, ['développeur '.$tag]));
        self::assertNotContains(
            $candidate->getId(),
            $repository->matchingIds(new CandidateSearchCriteria(
                keywords: ['développeur '.$tag],
                country: 'MA',
            )),
            'le pays continue de restreindre malgré le mot-clé satisfait',
        );
    }

    /**
     * Le jeu se range par nombre de critères satisfaits — les mieux appariés d'abord.
     *
     * Ce test était **impossible à écrire** tant que les critères se cumulaient en ET : tout membre
     * du jeu les satisfaisait tous, et le rang était forcément identique pour chacun. C'est le OU
     * sur les mots-clés qui crée les correspondances partielles, donc la hiérarchie.
     */
    public function testTheSetIsOrderedByHowManyCriteriaEachProfileSatisfies(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $recalculator = self::getContainer()->get(SearchMembershipRecalculator::class);
        $memberships = self::getContainer()->get(SearchResultMembershipRepository::class);

        $tag = uniqid();
        $one = $this->persistProfile($em, 'Développeur '.$tag, []);
        $two = $this->persistProfile($em, 'Développeur '.$tag, ['Kubernetes '.$tag]);
        $em->flush();

        $owner = $em->getRepository(Recruiter::class)->findOneBy([]);
        self::assertInstanceOf(Recruiter::class, $owner);

        $search = new SavedSearch($owner, $owner->organization, 'Ordre '.$tag, [
            ['facet' => 'q', 'value' => 'développeur '.$tag, 'group' => 'motscles'],
            ['facet' => 'q', 'value' => 'kubernetes '.$tag, 'group' => 'motscles'],
        ]);
        $em->persist($search);
        $em->flush();

        $recalculator->recalculate($search);

        // Les deux entrent — c'est le OU. Mais l'un satisfait les DEUX mots-clés, l'autre un seul.
        $order = $memberships->orderedCandidateUuidsFor($search);
        self::assertSame(
            [$two->getUuid()->toString(), $one->getUuid()->toString()],
            $order,
            'le profil qui satisfait deux critères précède celui qui n’en satisfait qu’un',
        );

        [$rows] = $memberships->page($search, 1, 30);
        self::assertSame(2, $rows[0]->matchedCount);
        self::assertSame(1, $rows[1]->matchedCount);
        self::assertSame(
            array_map(static fn ($row) => $row->candidate->getUuid()->toString(), $rows),
            $order,
            'la page et la liste complète rangent PAREIL, sans quoi la pagination du client décale',
        );
    }

    /**
     * Le filtre d'ancienneté (task API-010) : une borne BASSE, et `0` ne restreint rien.
     *
     * Le champ existait à l'écran sans rien filtrer — ses plages (`0_2`, `3_5`…) étaient une
     * invention du front, sans équivalent serveur. Il donnait l'illusion d'un critère.
     */
    public function testExperienceMinimumFiltersAndZeroDoesNot(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(CandidateRepository::class);

        $tag = uniqid();
        $junior = $this->persistProfile($em, 'Développeur '.$tag, [], years: 2);
        $senior = $this->persistProfile($em, 'Développeur '.$tag, [], years: 11);
        // Expérience non dérivée à l'ingestion : elle vaut 0, et on ne lui prête aucune ancienneté.
        $unknown = $this->persistProfile($em, 'Développeur '.$tag, [], years: 0);
        $em->flush();

        $atLeastFive = $repository->matchingIds(new CandidateSearchCriteria(
            keywords: ['développeur '.$tag],
            experienceMin: 5,
        ));
        self::assertContains($senior->getId(), $atLeastFive);
        self::assertNotContains($junior->getId(), $atLeastFive);
        self::assertNotContains($unknown->getId(), $atLeastFive, 'une ancienneté inconnue ne se suppose pas');

        // `0` est le « peu importe » du curseur : il ne retire personne.
        $none = $repository->matchingIds(new CandidateSearchCriteria(
            keywords: ['développeur '.$tag],
            experienceMin: 0,
        ));
        self::assertContains($junior->getId(), $none);
        self::assertContains($unknown->getId(), $none);
    }

    /**
     * @param list<string> $keywords
     *
     * @return list<int>
     */
    private function ids(CandidateRepository $repository, array $keywords): array
    {
        return $repository->matchingIds(new CandidateSearchCriteria(keywords: $keywords));
    }

    /** @param list<string> $skills */
    private function persistProfile(EntityManagerInterface $em, string $headline, array $skills, int $years = 0): Candidate
    {
        $person = new Person('Test', 'Keyword-'.uniqid(), contactEmail: uniqid('kw-', true).'@example.test', country: 'FR', city: 'Paris');
        $em->persist($person);

        $candidate = new Candidate(
            $person,
            jarviProfileId: Uuid::v7(),
            visibility: ProfileVisibility::VISIBLE,
            syncStatus: SyncStatus::SYNCED,
            syncedAt: new DateTimeImmutable(),
        );
        $candidate->giveConsent(ConsentSource::ONBOARDING, new DateTimeImmutable());
        $em->persist($candidate);
        $em->persist(new CandidateJarvi($candidate, headline: $headline, skills: $skills, experienceYears: $years));

        return $candidate;
    }
}
