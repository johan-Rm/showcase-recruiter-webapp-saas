<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Search\InactiveSearchCloser;
use App\Domain\Search\SearchMembershipRecalculator;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * La clôture des recherches que plus personne ne touche — task `API-049`.
 *
 * Deux choses se vérifient ici, et la seconde est la plus importante : que la clôture ferme ce qu'il
 * faut, et que **la trace d'activité ne bouge QUE sur un geste humain**. Si un recalcul de jeu la
 * repoussait, plus aucune recherche ne se fermerait jamais — l'ingestion Jarvi rejoue toutes les
 * recherches de la base, tous les jours.
 *
 * L'horloge est injectée plutôt qu'attendue : un test ne peut pas patienter quinze jours.
 */
final class SearchInactivityClosureTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';

    public function testASearchWithoutContactIsClosedAndOneTouchedRecentlyIsNot(): void
    {
        $em = $this->em();
        $closer = $this->closer();

        $abandonnee = $this->searchWithActivity($em, 'Abandonnée', new DateTimeImmutable('-40 days'));
        $vivante = $this->searchWithActivity($em, 'Vivante', new DateTimeImmutable('-1 day'));
        $em->flush();

        $closed = $closer->close();

        self::assertContains(
            $abandonnee->getUuid()->toString(),
            array_column($closed, 'uuid'),
            'sans contact depuis quarante jours, elle se range',
        );
        self::assertNotContains(
            $vivante->getUuid()->toString(),
            array_column($closed, 'uuid'),
            'touchée hier, elle reste ouverte',
        );

        $em->clear();
        self::assertNotNull($this->reload($em, $abandonnee)->closedAt);
        self::assertNull($this->reload($em, $vivante)->closedAt);
    }

    /**
     * Une recherche DÉJÀ close est laissée intacte.
     *
     * La refermer déplacerait sa date de clôture, et effacerait le moment où son auteur a vraiment
     * arrêté — la seule information que cette date porte.
     */
    public function testAnAlreadyClosedSearchIsLeftIntact(): void
    {
        $em = $this->em();
        $closed = $this->searchWithActivity($em, 'Déjà close', new DateTimeImmutable('-40 days'));
        $closed->closedAt = new DateTimeImmutable('2026-01-15 10:00:00');
        $em->flush();

        $this->closer()->close();

        $em->clear();
        self::assertSame(
            '2026-01-15 10:00:00',
            $this->reload($em, $closed)->closedAt?->format('Y-m-d H:i:s'),
        );
    }

    /** Deux passes le même jour : la seconde ne trouve plus rien. */
    public function testTheSecondPassOfTheDayFindsNothing(): void
    {
        $em = $this->em();
        $this->searchWithActivity($em, 'Abandonnée', new DateTimeImmutable('-40 days'));
        $em->flush();

        $closer = $this->closer();
        self::assertNotSame([], $closer->close());
        self::assertSame([], $closer->close(), 'la clôture est idempotente');
    }

    /**
     * **LE TEST QUI PROTÈGE TOUTE LA TÂCHE.**.
     *
     * Le jeu se recalcule à chaque ingestion, sur toutes les recherches. Si ce recalcul comptait
     * comme un contact, la clôture automatique ne fermerait plus jamais rien — et le défaut serait
     * invisible : tout paraîtrait fonctionner, simplement aucune recherche ne se rangerait.
     */
    public function testRecalculatingTheSetIsNotAContact(): void
    {
        $em = $this->em();
        $search = $this->searchWithActivity($em, 'Recalculée', new DateTimeImmutable('-40 days'), [
            ['facet' => 'q', 'value' => 'développeur', 'group' => 'motscles'],
        ]);
        $em->flush();
        $before = $search->lastActivityAt;

        self::getContainer()->get(SearchMembershipRecalculator::class)->recalculate($search);

        $em->clear();
        // À la SECONDE : la colonne est un `timestamp(0)`, la valeur en mémoire porte ses
        // microsecondes. Comparer les objets ferait échouer un test qui a pourtant raison.
        self::assertSame(
            $before->format('Y-m-d H:i:s'),
            $this->reload($em, $search)->lastActivityAt->format('Y-m-d H:i:s'),
            'une ingestion ne réveille pas une recherche abandonnée',
        );
    }

    /**
     * Chaque geste humain repousse la clôture — et « ouvrir » en est un.
     *
     * Une recherche qu'on rouvre est vivante même sans y trancher quoi que ce soit : c'est la règle
     * la plus intuitive, et personne ne doit voir se fermer ce qu'il consultait la veille.
     */
    public function testOpeningTheSetPushesTheTrace(): void
    {
        $em = $this->em();
        $search = $this->searchWithActivity($em, 'Ouverte', new DateTimeImmutable('-40 days'));
        $em->flush();
        $before = $search->lastActivityAt;
        $searchId = $search->getUuid()->toString();

        $this->request('GET', "/v1/searches/{$searchId}/results", $this->loginToken(self::OWNER));
        self::assertResponseIsSuccessful();

        $em->clear();
        self::assertGreaterThan($before, $this->reload($em, $search)->lastActivityAt);
    }

    /** Trancher un profil aussi : c'est le geste de travail par excellence. */
    public function testReviewingAProfilePushesTheTrace(): void
    {
        $em = $this->em();
        $token = $this->loginToken(self::OWNER);
        $search = $em->getRepository(SavedSearch::class)->findOneBy(['name' => 'Finance France vers Casablanca']);
        self::assertInstanceOf(SavedSearch::class, $search);
        $searchId = $search->getUuid()->toString();

        $this->request('GET', "/v1/searches/{$searchId}/results", $token);
        $candidateId = $this->jsonResponse()['resultIds'][0] ?? null;
        self::assertIsString($candidateId);

        $search->touch(new DateTimeImmutable('-40 days'));
        $em->flush();
        $before = $search->lastActivityAt;

        $this->request('POST', "/v1/searches/{$searchId}/reviews", $token, [
            'candidateIds' => [$candidateId],
            'action' => 'SEEN',
        ]);
        self::assertResponseIsSuccessful();

        $em->clear();
        self::assertGreaterThan($before, $this->reload($em, $search)->lastActivityAt);
    }

    /**
     * Rouvrir repart pour un tour plein.
     *
     * Sans ce geste, une recherche rouverte sans être travaillée se refermerait à la passe suivante,
     * et le recruteur croirait à une panne.
     */
    public function testReopeningPushesTheTrace(): void
    {
        $em = $this->em();
        $search = $this->searchWithActivity($em, 'À rouvrir', new DateTimeImmutable('-40 days'));
        $search->closedAt = new DateTimeImmutable('-1 day');
        $em->flush();
        $before = $search->lastActivityAt;
        $searchId = $search->getUuid()->toString();

        $this->request('PATCH', "/v1/searches/{$searchId}", $this->loginToken(self::OWNER), [
            'isClosed' => false,
        ]);
        self::assertResponseIsSuccessful();

        $em->clear();
        $reopened = $this->reload($em, $search);
        self::assertNull($reopened->closedAt);
        self::assertGreaterThan($before, $reopened->lastActivityAt);
    }

    /**
     * Une recherche de l'auteur, dont on choisit la date de dernier contact.
     *
     * @param list<array{facet: string, value: string, group: string}> $criteria
     */
    private function searchWithActivity(
        EntityManagerInterface $em,
        string $name,
        DateTimeImmutable $lastActivityAt,
        array $criteria = [],
    ): SavedSearch {
        $owner = $this->recruiter(self::OWNER);
        $search = new SavedSearch($owner, $owner->organization, $name, $criteria, isAutoNamed: false);
        $search->touch($lastActivityAt);
        $em->persist($search);

        return $search;
    }

    private function reload(EntityManagerInterface $em, SavedSearch $search): SavedSearch
    {
        $reloaded = $em->getRepository(SavedSearch::class)->findOneBy(['uuid' => $search->getUuid()]);
        self::assertInstanceOf(SavedSearch::class, $reloaded);

        return $reloaded;
    }

    private function closer(): InactiveSearchCloser
    {
        return self::getContainer()->get(InactiveSearchCloser::class);
    }

    private function recruiter(string $email): Recruiter
    {
        $recruiter = $this->em()->createQueryBuilder()
            ->select('r')
            ->from(Recruiter::class, 'r')
            ->join('r.person', 'p')
            ->where('p.contactEmail = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();

        self::assertInstanceOf(Recruiter::class, $recruiter);

        return $recruiter;
    }

    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
